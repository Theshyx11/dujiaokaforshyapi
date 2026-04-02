<?php
/**
 * The file was created by Assimon.
 *
 * @author    assimon<ashang@utf8.hk>
 * @copyright assimon<ashang@utf8.hk>
 * @link      http://utf8.hk/
 */

namespace App\Service;


use App\Exceptions\RuleValidationException;
use App\Models\Carmis;
use App\Models\Goods;
use App\Models\GoodsGroup;

/**
 * 商品服务层
 *
 * Class GoodsService
 * @package App\Service
 * @author: Assimon
 * @email: Ashang@utf8.hk
 * @blog: https://utf8.hk
 * Date: 2021/5/30
 */
class GoodsService
{
    /**
     * ShyAPI 发货服务层
     * @var \App\Service\ShyApiRedemptionService
     */
    private $shyApiRedemptionService;

    public function __construct()
    {
        $this->shyApiRedemptionService = app('Service\ShyApiRedemptionService');
    }

    /**
     * 获取所有分类并加载该分类下的商品
     *
     * @return array|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function withGroup(): ?array
    {
        $goods = GoodsGroup::query()
            ->with(['goods' => function($query) {
                $query->withCount(['carmis' => function($query) {
                    $query->where('status', Carmis::STATUS_UNSOLD);
                }])->where('is_open', Goods::STATUS_OPEN)->orderBy('ord', 'DESC');
            }])
            ->where('is_open', GoodsGroup::STATUS_OPEN)
            ->orderBy('ord', 'DESC')
            ->get();
        if ($goods) {
            foreach ($goods as $group) {
                foreach ($group->goods as $item) {
                    $this->hydrateDynamicStock($item);
                }
            }
        }
        // 将自动
        return $goods ? $goods->toArray() : null;
    }

    /**
     * 商品详情
     *
     * @param int $id 商品id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function detail(int $id)
    {
        $goods = Goods::query()
            ->with(['coupon'])
            ->withCount(['carmis' => function($query) {
                $query->where('status', Carmis::STATUS_UNSOLD);
            }])->where('id', $id)->first();
        if ($goods) {
            $this->hydrateDynamicStock($goods);
        }
        return $goods;
    }

    /**
     * 格式化商品信息
     *
     * @param Goods $goods 商品模型
     * @return Goods
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function format(Goods $goods)
    {
        // 格式化批发配置以及输入框配置
        $goods->wholesale_price_cnf = $goods->wholesale_price_cnf ?
            format_wholesale_price($goods->wholesale_price_cnf) :
            null;
        // 如果存在其他配置输入框且为代充
        $goods->other_ipu = $goods->other_ipu_cnf ?
            format_charge_input($goods->other_ipu_cnf) :
            null;
        return $goods;
    }

    public function refreshDynamicStock(Goods $goods): Goods
    {
        return $this->hydrateDynamicStock($goods, true);
    }

    public function buildStorefrontInsights(?array $groups): array
    {
        $plans = collect($groups ?? [])
            ->flatMap(function ($group) {
                return collect($group['goods'] ?? []);
            })
            ->map(function ($goods) {
                return $this->buildStorefrontPlan((array) $goods);
            })
            ->filter(function ($plan) {
                return !empty($plan['id']);
            })
            ->sortBy(function ($plan) {
                return sprintf('%012.4f-%08d', (float) ($plan['price'] ?? 0), (int) ($plan['quota'] ?? 0));
            })
            ->values();

        if ($plans->isEmpty()) {
            return [
                'plans' => [],
                'featured_plan' => null,
                'starter_plan' => null,
                'highest_plan' => null,
                'guides' => $this->buildDefaultStorefrontGuides(),
                'notes' => $this->buildStorefrontNotes(),
            ];
        }

        $starterPlan = $plans->sortBy('price')->first();
        $bestValuePlan = $plans
            ->filter(function ($plan) {
                return ($plan['unit_price'] ?? 0) > 0;
            })
            ->sortBy('unit_price')
            ->first();
        $highestQuotaPlan = $plans->sortByDesc('quota')->first();

        $starterPlanId = is_array($starterPlan) ? ($starterPlan['id'] ?? null) : null;
        $bestValuePlanId = is_array($bestValuePlan) ? ($bestValuePlan['id'] ?? null) : null;
        $highestQuotaPlanId = is_array($highestQuotaPlan) ? ($highestQuotaPlan['id'] ?? null) : null;

        $plans = $plans->map(function ($plan) use ($starterPlanId, $bestValuePlanId, $highestQuotaPlanId) {
            $badge = null;
            if ($plan['id'] === $bestValuePlanId) {
                $badge = '主推套餐';
            } elseif ($plan['id'] === $starterPlanId) {
                $badge = '入门首选';
            } elseif ($plan['id'] === $highestQuotaPlanId) {
                $badge = '进阶容量';
            }

            $plan['badge'] = $badge;
            $plan['is_featured'] = $plan['id'] === $bestValuePlanId;
            $plan['bullets'] = $this->buildPlanBullets($plan);

            return $plan;
        })->values();

        $starterPlan = $starterPlanId ? $plans->firstWhere('id', $starterPlanId) : null;
        $featuredPlan = $bestValuePlanId ? $plans->firstWhere('id', $bestValuePlanId) : ($plans->first() ?: null);
        $highestPlan = $highestQuotaPlanId ? $plans->firstWhere('id', $highestQuotaPlanId) : null;

        return [
            'plans' => $plans->all(),
            'featured_plan' => $featuredPlan,
            'starter_plan' => $starterPlan,
            'highest_plan' => $highestPlan,
            'guides' => $this->buildStorefrontGuides($starterPlan, $featuredPlan, $highestPlan),
            'notes' => $this->buildStorefrontNotes(),
        ];
    }

    public function redeemableShyApiGoods()
    {
        $goodsList = Goods::query()
            ->where('is_open', Goods::STATUS_OPEN)
            ->where('type', Goods::AUTOMATIC_DELIVERY)
            ->where('delivery_source', Goods::DELIVERY_SOURCE_SHYAPI)
            ->where('partner_redeem_enabled', Goods::STATUS_OPEN)
            ->orderBy('ord', 'DESC')
            ->get();

        foreach ($goodsList as $goods) {
            $this->hydrateDynamicStock($goods);
        }

        return $goodsList;
    }

    private function buildStorefrontPlan(array $goods): array
    {
        $price = round((float) ($goods['actual_price'] ?? 0), 2);
        $retailPrice = round((float) ($goods['retail_price'] ?? 0), 2);
        $quota = $this->extractDisplayQuota($goods);
        $stock = max(0, (int) ($goods['in_stock'] ?? 0));
        $saving = max(0, $retailPrice - $price);
        $summary = trim(strip_tags((string) ($goods['gd_description'] ?? '')));
        if ($summary === '') {
            $summary = trim(strip_tags((string) ($goods['description'] ?? '')));
        }

        $scenario = $this->resolvePlanScenario($quota);
        $unitPrice = $quota > 0 ? round($price / $quota, 2) : 0;

        return [
            'id' => (int) ($goods['id'] ?? 0),
            'name' => (string) ($goods['gd_name'] ?? ''),
            'summary' => $summary,
            'price' => $price,
            'price_formatted' => number_format($price, 2),
            'retail_price' => $retailPrice,
            'retail_price_formatted' => number_format($retailPrice, 2),
            'saving' => $saving,
            'saving_formatted' => number_format($saving, 2),
            'quota' => $quota,
            'quota_label' => $quota > 0 ? $quota . ' 刀额度' : '灵活额度',
            'unit_price' => $unitPrice,
            'unit_price_formatted' => $unitPrice > 0 ? number_format($unitPrice, 2) : null,
            'stock' => $stock,
            'stock_label' => $stock > 0 ? $stock . ' 份现货' : '暂时缺货',
            'delivery_label' => ((int) ($goods['delivery_source'] ?? 0) === Goods::DELIVERY_SOURCE_SHYAPI)
                ? '自动发放兑换码'
                : '本地卡密发货',
            'scenario_title' => $scenario['title'],
            'scenario_description' => $scenario['description'],
            'path' => '/buy/' . (int) ($goods['id'] ?? 0),
        ];
    }

    private function extractDisplayQuota(array $goods): int
    {
        $candidates = [
            $goods['shyapi_quota'] ?? null,
            $goods['shyapi_name_prefix'] ?? null,
            $goods['gd_name'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }

            if (is_string($candidate) && preg_match('/(\d+)/u', $candidate, $matches)) {
                $value = (int) ($matches[1] ?? 0);
                if ($value > 0) {
                    return $value;
                }
            }
        }

        return 0;
    }

    private function resolvePlanScenario(int $quota): array
    {
        if ($quota <= 0) {
            return [
                'title' => '灵活套餐',
                'description' => '适合定制化场景或后续继续扩展的额度方案。',
            ];
        }

        if ($quota <= 10) {
            return [
                'title' => '轻量体验',
                'description' => '适合第一次接入、调试 SDK、试通模型和小规模测试。',
            ];
        }

        if ($quota <= 50) {
            return [
                'title' => '稳定日用',
                'description' => '适合已经确认要长期使用，需要更低单刀成本和更稳额度储备。',
            ];
        }

        if ($quota <= 200) {
            return [
                'title' => '团队进阶',
                'description' => '适合多项目并行、多人共用或需要更大可用额度的使用方式。',
            ];
        }

        return [
            'title' => '高频容量',
            'description' => '适合高并发、高消耗和需要长期连续供给的场景。',
        ];
    }

    private function buildPlanBullets(array $plan): array
    {
        $bullets = [
            $plan['delivery_label'],
            $plan['scenario_description'],
            '当前库存 ' . $plan['stock'] . '，' . ($plan['unit_price_formatted'] ? ('折合 ¥' . $plan['unit_price_formatted'] . '/刀') : '支持灵活扩展'),
        ];

        if (($plan['saving'] ?? 0) > 0) {
            $bullets[] = '相对标价立省 ¥' . $plan['saving_formatted'];
        }

        return array_values(array_slice(array_unique($bullets), 0, 4));
    }

    private function buildStorefrontGuides(?array $starterPlan, ?array $featuredPlan, ?array $highestPlan): array
    {
        return [
            [
                'title' => '第一次接入先跑通',
                'description' => $starterPlan
                    ? ('建议先从 ' . $starterPlan['name'] . ' 开始，先验证充值、Key 创建和 SDK 接入链路。')
                    : '先选一档较低门槛套餐，把充值、Key 创建和 SDK 接入链路跑通。',
                'label' => $starterPlan ? ('推荐：' . $starterPlan['name']) : '推荐：入门档',
            ],
            [
                'title' => '准备长期使用就看单刀成本',
                'description' => $featuredPlan
                    ? ('当前综合性价比更高的是 ' . $featuredPlan['name'] . '，更适合稳定日用与持续调用。')
                    : '优先看单刀成本更低的套餐，长期使用会更省。',
                'label' => $featuredPlan ? ('主推：' . $featuredPlan['name']) : '推荐：主推套餐',
            ],
            [
                'title' => '有推广或多账号场景',
                'description' => $highestPlan
                    ? ('如果你要做分销、团队协作或更高频使用，可以优先看 ' . $highestPlan['name'] . ' 这类更高容量套餐。')
                    : '如果你要做分销、团队协作或更高频使用，建议直接选择更高容量方案。',
                'label' => '推荐：容量更高的一档',
            ],
        ];
    }

    private function buildDefaultStorefrontGuides(): array
    {
        return [
            [
                'title' => '先确认接入链路',
                'description' => '优先跑通控制台充值、API Key 创建和 SDK 示例，再决定要不要放大购买量。',
                'label' => '先小量验证',
            ],
            [
                'title' => '长期使用看成本',
                'description' => '当你已经确定会持续调用，再对比单刀成本和库存，选择更划算的档位。',
                'label' => '看单刀成本',
            ],
            [
                'title' => '团队场景看容量',
                'description' => '如果你要供多人、多项目共用，优先选择容量更高且更稳定的套餐。',
                'label' => '看稳定供给',
            ],
        ];
    }

    private function buildStorefrontNotes(): array
    {
        return [
            [
                'title' => '商城负责交易，控制台负责接口',
                'description' => '支付完成后拿到的是兑换码，真正的余额、API Key、模型和日志都统一在控制台管理。',
            ],
            [
                'title' => '先兑再调，不要跳步骤',
                'description' => '很多接入失败不是代码问题，而是还没有在控制台完成兑换充值或使用了错误账号。',
            ],
            [
                'title' => '按项目拆 Key 更利于运营',
                'description' => '后续做日志审计、额度控制、团队分账和问题排查时，会清晰很多。',
            ],
        ];
    }

    /**
     * 验证商品状态
     *
     * @param Goods $goods
     * @return Goods
     * @throws RuleValidationException
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function validatorGoodsStatus(Goods $goods): Goods
    {
        if (empty($goods)) {
            throw new RuleValidationException(__('dujiaoka.prompt.goods_does_not_exist'));
        }
        // 上架判断.
        if ($goods->is_open != Goods::STATUS_OPEN) {
            throw new RuleValidationException(__('dujiaoka.prompt.the_goods_is_not_on_the_shelves'));
        }
        return $goods;
    }

    /**
     * 库存减去
     *
     * @param int $id 商品id
     * @param int $number 出库数量
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function inStockDecr(int $id, int $number = 1): bool
    {
        return Goods::query()->where('id', $id)->decrement('in_stock', $number);
    }

    /**
     * 商品销量加
     *
     * @param int $id 商品id
     * @param int $number 数量
     * @return bool
     *
     * @author    assimon<ashang@utf8.hk>
     * @copyright assimon<ashang@utf8.hk>
     * @link      http://utf8.hk/
     */
    public function salesVolumeIncr(int $id, int $number = 1): bool
    {
        return Goods::query()->where('id', $id)->increment('sales_volume', $number);
    }

    private function hydrateDynamicStock(Goods $goods, bool $forceRefresh = false): Goods
    {
        if (!$goods->isShyApiDelivery()) {
            return $goods;
        }
        try {
            $stock = $this->shyApiRedemptionService->getAvailableStock($goods, !$forceRefresh);
            $goods->setAttribute('in_stock', $stock);
        } catch (\Throwable $exception) {
            report($exception);
        }
        return $goods;
    }

}

<?php

namespace App\Service;

use App\Models\Goods;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ShyApiRedemptionService
{
    private const STOCK_CACHE_TTL = 60;
    private const STATUS_CACHE_TTL = 300;
    private const QUOTA_MODE_AUTO = 'auto';
    private const QUOTA_MODE_DISPLAY = 'display';
    private const QUOTA_MODE_INTERNAL = 'internal';

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct()
    {
        $baseUrl = rtrim((string) config('services.shyapi.base_url'), '/');
        $config = [
            'timeout' => (float) config('services.shyapi.timeout', 15),
            'http_errors' => false,
        ];
        if ($baseUrl !== '') {
            $config['base_uri'] = $baseUrl.'/';
        }

        $this->client = new Client($config);
    }

    public function getAvailableStock(Goods $goods, bool $useCache = true): int
    {
        $this->ensureConfigured();
        $filters = $this->buildFilters($goods);
        $cacheKey = $this->getStockCacheKey($filters);

        if ($useCache) {
            return (int) Cache::remember($cacheKey, self::STOCK_CACHE_TTL, function () use ($filters) {
                return $this->requestAvailableStock($filters);
            });
        }

        $stock = $this->requestAvailableStock($filters);
        Cache::put($cacheKey, $stock, self::STOCK_CACHE_TTL);
        return $stock;
    }

    public function exportRedemptions(Goods $goods, Order $order): array
    {
        return $this->issueRedemptions($goods, (int) $order->buy_amount, $order->order_sn);
    }

    public function issueRedemptions(Goods $goods, int $count, string $batch): array
    {
        $this->ensureConfigured();
        $filters = $this->buildFilters($goods);
        $payload = array_merge($filters, [
            'count' => $count,
            'assigned_to' => $this->resolveAssignedTo($goods),
            'assigned_batch' => $batch,
        ]);

        $data = $this->postJson('api/redemption/export', $payload);
        $keys = $data['keys'] ?? [];
        if (!is_array($keys) || count($keys) !== $count) {
            throw new \RuntimeException('ShyAPI 返回的兑换码数量异常');
        }
        foreach ($keys as $key) {
            if (!is_string($key) || $key === '') {
                throw new \RuntimeException('ShyAPI 返回了无效兑换码');
            }
        }

        Cache::forget($this->getStockCacheKey($filters));
        return array_values($keys);
    }

    private function requestAvailableStock(array $filters): int
    {
        $data = $this->postJson('api/redemption/available-count', $filters);
        if (!isset($data['count'])) {
            throw new \RuntimeException('ShyAPI 库存接口返回异常');
        }
        return max(0, (int) $data['count']);
    }

    private function postJson(string $uri, array $payload): array
    {
        $response = $this->client->post(ltrim($uri, '/'), [
            'headers' => $this->buildHeaders(),
            'json' => $payload,
        ]);

        return $this->decodeJsonResponse($response->getBody()->getContents());
    }

    private function getJson(string $uri): array
    {
        $response = $this->client->get(ltrim($uri, '/'), [
            'headers' => $this->buildHeaders(),
        ]);

        return $this->decodeJsonResponse($response->getBody()->getContents());
    }

    private function decodeJsonResponse(string $body): array
    {
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('ShyAPI 返回了无法解析的响应');
        }
        if (($decoded['success'] ?? false) !== true) {
            throw new \RuntimeException($decoded['message'] ?? 'ShyAPI 请求失败');
        }
        if (!array_key_exists('data', $decoded) || !is_array($decoded['data'])) {
            throw new \RuntimeException('ShyAPI 返回数据缺失');
        }

        return $decoded['data'];
    }

    private function buildHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => (string) config('services.shyapi.access_token'),
            'New-Api-User' => (string) config('services.shyapi.user_id'),
        ];
    }

    private function ensureConfigured(): void
    {
        if (empty(config('services.shyapi.base_url'))) {
            throw new \RuntimeException('ShyAPI_BASE_URL 未配置');
        }
        if (empty(config('services.shyapi.access_token'))) {
            throw new \RuntimeException('ShyAPI_ACCESS_TOKEN 未配置');
        }
        if (empty(config('services.shyapi.user_id'))) {
            throw new \RuntimeException('ShyAPI_USER_ID 未配置');
        }
    }

    private function buildFilters(Goods $goods): array
    {
        if (!$goods->isShyApiDelivery()) {
            throw new \RuntimeException('当前商品未启用 ShyAPI 自动发货');
        }

        $namePrefix = trim((string) ($goods->shyapi_name_prefix ?? ''));
        $quota = $this->normalizeQuotaFilter((int) ($goods->shyapi_quota ?? 0));
        if ($namePrefix === '' && $quota <= 0) {
            throw new \RuntimeException('ShyAPI 商品至少需要配置名称前缀或固定额度');
        }

        return [
            'name_prefix' => $namePrefix,
            'quota' => $quota,
        ];
    }

    private function resolveAssignedTo(Goods $goods): string
    {
        $assignedTo = trim((string) ($goods->shyapi_assigned_to ?? ''));
        if ($assignedTo === '') {
            $assignedTo = trim((string) config('services.shyapi.default_assigned_to', 'shop'));
        }
        if ($assignedTo === '') {
            throw new \RuntimeException('ShyAPI 分配渠道未配置');
        }
        return $assignedTo;
    }

    private function getStockCacheKey(array $filters): string
    {
        return 'shyapi_stock_'.md5(json_encode($filters, JSON_UNESCAPED_UNICODE));
    }

    private function normalizeQuotaFilter(int $quota): int
    {
        if ($quota <= 0) {
            return 0;
        }

        $mode = strtolower((string) config('services.shyapi.quota_mode', self::QUOTA_MODE_AUTO));
        if ($mode === self::QUOTA_MODE_INTERNAL) {
            return $quota;
        }

        $status = $this->getStatus();
        $displayType = strtoupper((string) ($status['quota_display_type'] ?? 'USD'));
        if ($displayType === 'TOKENS') {
            return $quota;
        }

        $quotaPerUnit = (int) round((float) ($status['quota_per_unit'] ?? 0));
        if ($quotaPerUnit <= 0) {
            throw new \RuntimeException('ShyAPI quota_per_unit 配置异常');
        }

        // auto 模式兼容两种写法：
        // 1. 商品填写 10 / 50 这类售卖面值
        // 2. 商品已经直接填写内部 quota（如 5000000）
        if ($mode === self::QUOTA_MODE_AUTO && $quota >= $quotaPerUnit && $quota % $quotaPerUnit === 0) {
            return $quota;
        }

        return (int) round($quota * $quotaPerUnit);
    }

    private function getStatus(): array
    {
        return Cache::remember('shyapi_status', self::STATUS_CACHE_TTL, function () {
            return $this->getJson('api/status');
        });
    }
}

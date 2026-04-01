<?php

namespace App\Service;

use App\Models\Goods;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ShyApiRedemptionService
{
    private const STOCK_CACHE_TTL = 60;

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
        $this->ensureConfigured();
        $filters = $this->buildFilters($goods);
        $payload = array_merge($filters, [
            'count' => (int) $order->buy_amount,
            'assigned_to' => $this->resolveAssignedTo($goods),
            'assigned_batch' => $order->order_sn,
        ]);

        $data = $this->postJson('api/redemption/export', $payload);
        $keys = $data['keys'] ?? [];
        if (!is_array($keys) || count($keys) !== (int) $order->buy_amount) {
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
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => (string) config('services.shyapi.access_token'),
                'New-Api-User' => (string) config('services.shyapi.user_id'),
            ],
            'json' => $payload,
        ]);

        $body = (string) $response->getBody();
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
        $quota = (int) ($goods->shyapi_quota ?? 0);
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
}

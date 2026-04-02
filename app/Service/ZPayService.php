<?php

namespace App\Service;

use App\Exceptions\RuleValidationException;
use App\Models\Order;
use App\Models\Pay;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ZPayService
{
    private const DEFAULT_GATEWAY_URL = 'https://zpayz.cn/submit.php';
    private const DEFAULT_API_URL = 'https://zpayz.cn/api.php';
    private const SUCCESS_STATUS = 'TRADE_SUCCESS';

    /**
     * @var \App\Service\OrderProcessService
     */
    private $orderProcessService;

    /**
     * @var \App\Service\OrderService
     */
    private $orderService;

    public function __construct()
    {
        $this->orderProcessService = app('Service\OrderProcessService');
        $this->orderService = app('Service\OrderService');
    }

    public function isManagedGateway(?Pay $payGateway): bool
    {
        if (!$payGateway) {
            return false;
        }

        return trim((string) $payGateway->pay_handleroute, '/') === 'pay/yipay';
    }

    public function resolveGatewayUrl(?Pay $payGateway): string
    {
        $configuredUrl = trim((string) optional($payGateway)->merchant_key);
        return $configuredUrl !== '' ? $configuredUrl : self::DEFAULT_GATEWAY_URL;
    }

    public function buildSignature(array $payload, string $key): string
    {
        ksort($payload);
        reset($payload);

        $query = [];
        foreach ($payload as $field => $value) {
            if (in_array($field, ['sign', 'sign_type'], true)) {
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $query[] = $field . '=' . $value;
        }

        return md5(implode('&', $query) . $key);
    }

    public function verifySignature(array $payload, string $key): bool
    {
        $signature = strtolower((string) ($payload['sign'] ?? ''));
        if ($signature === '' || $key === '') {
            return false;
        }

        return hash_equals($this->buildSignature($payload, $key), $signature);
    }

    public function isSuccessfulCallback(array $payload): bool
    {
        return strtoupper((string) ($payload['trade_status'] ?? '')) === self::SUCCESS_STATUS;
    }

    public function normalizeAmount($amount): float
    {
        return (float) number_format((float) $amount, 2, '.', '');
    }

    public function completeOrderFromPayload(Order $order, array $payload): bool
    {
        $payGateway = $order->pay;
        if (!$this->isManagedGateway($payGateway)) {
            return false;
        }

        if ((string) ($payload['out_trade_no'] ?? '') !== (string) $order->order_sn) {
            return false;
        }

        if ((string) ($payload['pid'] ?? '') !== '' && (string) $payload['pid'] !== (string) $payGateway->merchant_id) {
            Log::warning('zpay callback merchant mismatch', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
                'payload_pid' => $payload['pid'] ?? null,
                'expected_pid' => $payGateway->merchant_id,
            ]);
            return false;
        }

        if ((string) ($payload['type'] ?? '') !== '' && (string) $payload['type'] !== (string) $payGateway->pay_check) {
            Log::warning('zpay callback channel mismatch', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
                'payload_type' => $payload['type'] ?? null,
                'expected_type' => $payGateway->pay_check,
            ]);
            return false;
        }

        if (!$this->verifySignature($payload, (string) $payGateway->merchant_pem)) {
            Log::warning('zpay callback signature verification failed', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
            ]);
            return false;
        }

        if (!$this->isSuccessfulCallback($payload)) {
            return false;
        }

        return $this->completeOrderSafely(
            $order,
            $this->normalizeAmount($payload['money'] ?? 0),
            (string) ($payload['trade_no'] ?? '')
        );
    }

    public function syncOrderFromRemote(Order $order): bool
    {
        if ($order->status > Order::STATUS_WAIT_PAY) {
            return true;
        }

        $payGateway = $order->pay;
        if (!$this->isManagedGateway($payGateway)) {
            return false;
        }

        try {
            $response = $this->queryOrder($payGateway, $order->order_sn);
        } catch (\Throwable $exception) {
            Log::warning('zpay order query failed', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }

        if ((int) ($response['code'] ?? 0) !== 1) {
            return false;
        }

        if ((string) ($response['out_trade_no'] ?? '') !== (string) $order->order_sn) {
            Log::warning('zpay order query returned mismatched order', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
                'response_out_trade_no' => $response['out_trade_no'] ?? null,
            ]);
            return false;
        }

        if ((string) ($response['type'] ?? '') !== '' && (string) $response['type'] !== (string) $payGateway->pay_check) {
            Log::warning('zpay order query returned mismatched channel', [
                'order_sn' => $order->order_sn,
                'pay_id' => $payGateway->id,
                'response_type' => $response['type'] ?? null,
                'expected_type' => $payGateway->pay_check,
            ]);
            return false;
        }

        if ((int) ($response['status'] ?? 0) !== 1) {
            return false;
        }

        return $this->completeOrderSafely(
            $order,
            $this->normalizeAmount($response['money'] ?? $order->actual_price),
            (string) ($response['trade_no'] ?? '')
        );
    }

    private function queryOrder(Pay $payGateway, string $orderSN): array
    {
        $client = new Client([
            'timeout' => 8,
            'connect_timeout' => 5,
            'http_errors' => false,
        ]);

        $response = $client->get($this->resolveApiUrl($payGateway), [
            'query' => [
                'act' => 'order',
                'pid' => $payGateway->merchant_id,
                'key' => $payGateway->merchant_pem,
                'out_trade_no' => $orderSN,
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload)) {
            throw new \RuntimeException('invalid zpay response');
        }

        return $payload;
    }

    private function resolveApiUrl(Pay $payGateway): string
    {
        $configuredUrl = trim((string) $payGateway->merchant_key);
        if ($configuredUrl === '') {
            return self::DEFAULT_API_URL;
        }

        $parts = parse_url($configuredUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return self::DEFAULT_API_URL;
        }

        return $parts['scheme'] . '://' . $parts['host'] . '/api.php';
    }

    private function completeOrderSafely(Order $order, float $actualPrice, string $tradeNo = ''): bool
    {
        if ($order->status > Order::STATUS_WAIT_PAY) {
            return true;
        }

        try {
            $this->orderProcessService->completedOrder($order->order_sn, $actualPrice, $tradeNo);
            return true;
        } catch (RuleValidationException $exception) {
            if ($this->hasOrderBeenMarkedPaid($order->order_sn)) {
                return true;
            }

            if (in_array($exception->getMessage(), [
                __('dujiaoka.prompt.order_status_completed'),
                __('dujiaoka.prompt.order_already_paid'),
            ], true)) {
                return true;
            }

            Log::warning('zpay order completion failed', [
                'order_sn' => $order->order_sn,
                'message' => $exception->getMessage(),
                'trade_no' => $tradeNo,
                'actual_price' => $actualPrice,
            ]);
            return false;
        } catch (\Throwable $exception) {
            if ($this->hasOrderBeenMarkedPaid($order->order_sn)) {
                return true;
            }

            Log::warning('zpay order completion raised unexpected error', [
                'order_sn' => $order->order_sn,
                'message' => $exception->getMessage(),
                'trade_no' => $tradeNo,
                'actual_price' => $actualPrice,
            ]);
            return false;
        }
    }

    private function hasOrderBeenMarkedPaid(string $orderSN): bool
    {
        $latestOrder = $this->orderService->detailOrderSN($orderSN);
        return $latestOrder && $latestOrder->status > Order::STATUS_WAIT_PAY;
    }
}

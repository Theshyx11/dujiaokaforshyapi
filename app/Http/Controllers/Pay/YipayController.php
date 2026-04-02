<?php
namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;

class YipayController extends PayController
{
    /**
     * @var \App\Service\ZPayService
     */
    private $zPayService;

    public function __construct()
    {
        parent::__construct();
        $this->zPayService = app('Service\ZPayService');
    }

    public function gateway(string $payway, string $orderSN)
    {
        try {
            $this->loadGateWay($orderSN, $payway);
            $parameter = [
                'pid' =>  $this->payGateway->merchant_id,
                'type' => $payway,
                'out_trade_no' => $this->order->order_sn,
                'return_url' => route('yipay-return'),
                'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
                'name'   => $this->order->title ?: $this->order->order_sn,
                'money'  => $this->zPayService->normalizeAmount($this->order->actual_price),
                'sign_type' =>'MD5',
            ];

            $parameter['sign'] = $this->zPayService->buildSignature($parameter, (string) $this->payGateway->merchant_pem);
            $gatewayUrl = $this->zPayService->resolveGatewayUrl($this->payGateway);
            $sHtml = "<form id='zpaysubmit' name='zpaysubmit' action='" . e($gatewayUrl) . "' method='post'>";

            foreach($parameter as $key => $val) {
                $sHtml.= "<input type='hidden' name='".e($key)."' value='".e((string) $val)."'/>";
            }

            $sHtml = $sHtml."<input type='submit' value=''></form>";
            $sHtml = $sHtml."<script>document.forms['zpaysubmit'].submit();</script>";
            return $sHtml;
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }

    public function notifyUrl(Request $request)
    {
        $data = $request->all();
        $orderSN = (string) ($data['out_trade_no'] ?? '');
        if ($orderSN === '') {
            return 'fail';
        }

        $order = $this->orderService->detailOrderSN($orderSN);
        if (!$order) {
            return 'fail';
        }

        if (!$this->zPayService->isManagedGateway($order->pay)) {
            return 'fail';
        }

        if ($this->zPayService->completeOrderFromPayload($order, $data)) {
            return 'success';
        }

        return 'fail';
    }

    public function returnUrl(Request $request)
    {
        $orderSN = (string) $request->get('out_trade_no', $request->get('order_id', ''));
        if ($orderSN === '') {
            return redirect(url('order-search'));
        }

        $order = $this->orderService->detailOrderSN($orderSN);
        if ($order && $this->zPayService->isManagedGateway($order->pay)) {
            $payload = $request->all();
            $synced = $this->zPayService->completeOrderFromPayload($order, $payload);
            if (!$synced) {
                $this->zPayService->syncOrderFromRemote($order);
            }
        }

        return redirect(url('detail-order-sn', ['orderSN' => $orderSN]));
    }

}

<?php

namespace App\Jobs;

use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class WorkWeiXinPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数。
     *
     * @var int
     */
    public $tries = 1;

    /**
     * 任务运行的超时时间。
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * @var Order
     */
    private $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = trim((string) dujiaoka_config_get('qywxbot_key'));
        if ($key === '') {
            return;
        }

        try {
            $goodInfo = app('Service\GoodsService')->detail($this->order->goods_id);
            $client = new Client(['timeout' => 15]);
            $apiUrl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=' . $key;
            $params = [
                "msgtype" => "markdown",
                "markdown" => [
                    "content" => __('dujiaoka.prompt.new_order_push').'(<font color="warning">'.$this->order->actual_price."</font>元)\n"
                    .'>'.__('order.fields.order_id') .': <font color="comment">'.$this->order->id."</font>\n"
                    .'>'.__('order.fields.order_sn') .': <font color="comment">'.$this->order->order_sn."</font>\n"
                    .'>'.__('order.fields.pay_id') .': <font color="comment">'.optional($this->order->pay)->pay_name."</font>\n"
                    .'>'.__('order.fields.title') .': <font color="comment">'.$this->order->title."</font>\n"
                    .'>'.__('order.fields.actual_price') .': <font color="comment">'.$this->order->actual_price."</font>\n"
                    .'>'.__('order.fields.email') .': <font color="comment">'.$this->order->email."</font>\n"
                    .'>'.__('goods.fields.gd_name') .': <font color="comment">'.optional($goodInfo)->gd_name."</font>\n"
                    .'>'.__('goods.fields.in_stock') .': <font color="comment">'.optional($goodInfo)->in_stock."</font>\n"
                    .'>'.__('order.fields.order_created') .': <font color="comment">'.$this->order->created_at."</font>"
                ]
            ];
            $client->post($apiUrl, ['json' => $params, 'verify' => false]);
        } catch (\Throwable $exception) {
            Log::warning('work weixin push failed', [
                'order_sn' => $this->order->order_sn,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

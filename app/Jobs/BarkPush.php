<?php

namespace App\Jobs;

use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BaseModel;
use Illuminate\Support\Facades\Log;


class BarkPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数。
     *
     * @var int
     */
    public $tries = 2;

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
        $server = rtrim((string) dujiaoka_config_get('bark_server'), '/');
        $token = trim((string) dujiaoka_config_get('bark_token'));
        if ($server === '' || $token === '') {
            return;
        }

        try {
            $goodInfo = app('Service\GoodsService')->detail($this->order->goods_id);
            $client = new Client(['timeout' => 15]);
            $apiUrl = $server . '/' . $token;
            $params = [
                "title" => __('dujiaoka.prompt.new_order_push').'('.$this->order->actual_price.'元)',
                "body" => __('order.fields.order_id') .': '.$this->order->id."\n"
                    . __('order.fields.order_sn') .': '.$this->order->order_sn."\n"
                    . __('order.fields.pay_id') .': '.optional($this->order->pay)->pay_name."\n"
                    . __('order.fields.title') .': '.$this->order->title."\n"
                    . __('order.fields.actual_price') .': '.$this->order->actual_price."\n"
                    . __('order.fields.email') .': '.$this->order->email."\n"
                    . __('goods.fields.gd_name') .': '.optional($goodInfo)->gd_name."\n"
                    . __('goods.fields.in_stock') .': '.optional($goodInfo)->in_stock."\n"
                    . __('order.fields.order_created') .': '.$this->order->created_at,
                "icon" => url('assets/common/images/default.jpg'),
                "level" => "timeSensitive",
                "group" => dujiaoka_config_get('text_logo', '独角数卡')
            ];
            if (dujiaoka_config_get('is_open_bark_push_url', 0) == BaseModel::STATUS_OPEN) {
                $params["url"] = url('detail-order-sn/'.$this->order->order_sn);
            }
            $client->post($apiUrl,['form_params' => $params, 'verify' => false]);
        } catch (\Throwable $exception) {
            Log::warning('bark push failed', [
                'order_sn' => $this->order->order_sn,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

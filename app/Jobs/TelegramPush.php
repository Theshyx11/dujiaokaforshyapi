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


class TelegramPush implements ShouldQueue
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
        $botToken = trim((string) dujiaoka_config_get('telegram_bot_token'));
        $chatId = trim((string) dujiaoka_config_get('telegram_userid'));
        if ($botToken === '' || $chatId === '') {
            return;
        }

        try {
            $goodInfo = app('Service\GoodsService')->detail($this->order->goods_id);
            $formatText = '*'. __('dujiaoka.prompt.new_order_push').'('.$this->order->actual_price.'元)*%0A'
            . __('order.fields.order_id') .': `'.$this->order->id.'`%0A'
            . __('order.fields.order_sn') .': `'.$this->order->order_sn.'`%0A'
            . __('order.fields.pay_id') .': `'.optional($this->order->pay)->pay_name.'`%0A'
            . __('order.fields.title') .': '.$this->order->title.'%0A'
            . __('order.fields.actual_price') .': '.$this->order->actual_price.'%0A'
            . __('order.fields.email') .': `'.$this->order->email.'`%0A'
            . __('goods.fields.gd_name') .': `'.optional($goodInfo)->gd_name.'`%0A'
            . __('goods.fields.in_stock') .': `'.optional($goodInfo)->in_stock.'`%0A'
            . __('order.fields.order_created') .': '.$this->order->created_at;
            $client = new Client([
                'timeout' => 15,
                'proxy'=> ''
            ]);
            $apiUrl = 'https://api.telegram.org/bot' . $botToken .
                '/sendMessage?chat_id=' . $chatId . '&parse_mode=Markdown&text='.$formatText;
            $client->post($apiUrl);
        } catch (\Throwable $exception) {
            Log::warning('telegram push failed', [
                'order_sn' => $this->order->order_sn,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

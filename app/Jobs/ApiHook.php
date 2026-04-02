<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApiHook implements ShouldQueue
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
        $goodInfo = app('Service\GoodsService')->detail($this->order->goods_id);
        if (!$goodInfo || empty($goodInfo->api_hook)) {
            return;
        }

        try {
            $postdata = [
                'title' => $this->order->title,
                'order_sn' => $this->order->order_sn,
                'email' => $this->order->email,
                'actual_price' => $this->order->actual_price,
                'order_info' => $this->order->info,
                'good_id' => $goodInfo->id,
                'gd_name' => $goodInfo->gd_name,
            ];

            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/json',
                    'content' => json_encode($postdata, JSON_UNESCAPED_UNICODE),
                    'timeout' => 10,
                ],
            ];
            $context = stream_context_create($opts);
            file_get_contents($goodInfo->api_hook, false, $context);
        } catch (\Throwable $exception) {
            Log::warning('api hook notify failed', [
                'order_sn' => $this->order->order_sn,
                'hook' => $goodInfo->api_hook,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailSend implements ShouldQueue
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

    private $to;

    private $content;

    private $title;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $to, string $title, string $content)
    {
        $this->to = $to;
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!dujiaoka_mail_is_ready() || !dujiaoka_mail_can_send_to($this->to)) {
            return;
        }

        $body = $this->content;
        $title = $this->title;
        $sysConfig = dujiaoka_mail_config();
        $mailConfig = [
            'driver' => $sysConfig['driver'] ?? 'smtp',
            'host' => $sysConfig['host'] ?? '',
            'port' => $sysConfig['port'] ?? '465',
            'username' => $sysConfig['username'] ?? '',
            'from' => [
                'address' => $sysConfig['from_address'] ?? '',
                'name' => $sysConfig['from_name'] ?? '独角发卡',
            ],
            'password' => $sysConfig['password'] ?? '',
            'encryption' => $sysConfig['encryption'] ?? '',
        ];
        $to = trim($this->to);

        try {
            config([
                'mail' => array_merge(config('mail'), $mailConfig),
            ]);
            (new MailServiceProvider(app()))->register();
            Mail::send(['html' => 'email.mail'], ['body' => $body], function ($message) use ($to, $title) {
                $message->to($to)->subject($title);
            });
        } catch (\Throwable $exception) {
            Log::warning('mail send skipped after transport failure', [
                'to' => $to,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

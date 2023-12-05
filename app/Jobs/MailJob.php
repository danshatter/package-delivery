<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Mail;
use App\Mail\Message;
use App\Services\Settings\Application;

class MailJob extends Job
{

    public $user;
    public $subject;
    public $messageBody;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $subject, $messageBody)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->messageBody = $messageBody;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Set the application settings
        app()->make(Application::class)->set();

        Mail::to($this->user->email)
            ->send((new Message($this->user, $this->messageBody))->subject($this->subject));
    }
}

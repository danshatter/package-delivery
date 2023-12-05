<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Message extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $information;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $information)
    {
        $this->user = $user;
        $this->information = $information;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.message');
    }
}

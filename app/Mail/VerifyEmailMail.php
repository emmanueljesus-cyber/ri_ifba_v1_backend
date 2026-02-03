<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $url;

    public function __construct($user, $url)
    {
        $this->user = $user;
        $this->url = $url;
    }

    public function build()
    {
        return $this
            ->subject('✅ Confirme seu Email - RI-IFBA')
            ->view('emails.auth.verify-email')
            ->with([
                'user' => $this->user,
                'url' => $this->url,
                'titulo' => 'Confirmar Email'
            ]);
    }
}
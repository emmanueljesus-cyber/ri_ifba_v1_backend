<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordMail extends Mailable
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
            ->subject('🔐 Redefinir Senha - RI-IFBA')
            ->view('emails.auth.reset-password')
            ->with([
                'user' => $this->user,
                'url' => $this->url,
                'titulo' => 'Redefinir Senha'
            ]);
    }
}
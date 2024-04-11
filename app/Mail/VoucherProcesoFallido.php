<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherProcesoFallido extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public $error;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $error)
    {
        $this->user = $user;
        $this->error = $error;
    }

    /**
     * Get the message envelope.
     */
    public function build(): self
    {
        return $this->view('emails.fallo')
            ->with(['error' => $this->error, 'user' => $this->user]);
    }
}

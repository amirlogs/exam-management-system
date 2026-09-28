<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $password,
        ?string $loginUrl = null
    ) {
        $this->afterCommit();
        $this->loginUrl = $loginUrl ?? rtrim((string) (env('FRONTEND_URL') ?? config('app.url')), '/') . '/login';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your University Exam System Account Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user-registered',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\BookingRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Értesítés az ütemezési ablak utolsó futása után.
 */
class BookingRunFinished extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingRun $run) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A foglaló program lefutott.',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-run-finished',
            with: [
                'dashboardUrl' => rtrim((string) config('app.url'), '/'),
            ],
        );
    }
}

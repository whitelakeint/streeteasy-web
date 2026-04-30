<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyScrapeReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $report,
    ) {}

    public function envelope(): Envelope
    {
        $date = $this->report['date'];
        return new Envelope(
            subject: "StreetEasy Scrape Report — {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-scrape-report',
        );
    }
}

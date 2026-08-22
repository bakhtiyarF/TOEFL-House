<?php

namespace App\Mail;

use App\Modules\Academic\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Enrollment Confirmation Email
 *
 * Sent when a student is enrolled in a program/class.
 * Includes enrollment details, class schedule, and fee information.
 */
class EnrollmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Enrollment Confirmation - ' . $this->enrollment->student->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.enrollment-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

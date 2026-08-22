<?php

namespace App\Mail;

use App\Modules\Academic\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Student Welcome Email
 *
 * Sent when a new student is registered in the system.
 * Includes student details, next steps, and contact information.
 */
class StudentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to TOEFL House - ' . $this->student->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-welcome',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

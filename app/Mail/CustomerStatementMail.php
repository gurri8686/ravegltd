<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $attachmentBinary;

    public function __construct(array $data, $attachmentBinary)
    {
        $this->data = $data;
        $this->attachmentBinary = $attachmentBinary;
    }

    public function build()
    {
        $name = $this->data['attachment_name'] ?? ($this->data['pdf_name'] ?? 'statement.xlsx');
        $mime = str_ends_with(strtolower($name), '.pdf')
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $mail = $this->subject($this->data['subject'])
            ->view('emails.customer-statement', ['data' => $this->data])
            ->attachData($this->attachmentBinary, $name, ['mime' => $mime]);

        if (!empty($this->data['cc_email'])) {
            $mail->cc($this->data['cc_email']);
        }

        return $mail;
    }
}

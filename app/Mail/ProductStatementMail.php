<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $pdfContent;

    public function __construct(array $data, $pdfContent)
    {
        $this->data = $data;
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        $mail = $this->subject($this->data['subject'])
            ->view('emails.product-statement', ['data' => $this->data]);

        if (!empty($this->pdfContent)) {
            $mail->attachData($this->pdfContent, $this->data['pdf_name'], [
                'mime' => 'application/pdf',
            ]);
        }

        if (!empty($this->data['cc_email'])) {
            $mail->cc($this->data['cc_email']);
        }

        return $mail;
    }
}

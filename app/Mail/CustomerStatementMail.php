<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $pdfContent;

    /**
     * @param array  $data        view data (customer, period, message, etc.)
     * @param string $pdfContent  raw PDF bytes to attach
     */
    public function __construct(array $data, $pdfContent)
    {
        $this->data = $data;
        $this->pdfContent = $pdfContent;
    }

    public function build()
    {
        $mail = $this->subject($this->data['subject'])
            ->view('emails.customer-statement', ['data' => $this->data])
            ->attachData($this->pdfContent, $this->data['pdf_name'], [
                'mime' => 'application/pdf',
            ]);

        if (!empty($this->data['cc_email'])) {
            $mail->cc($this->data['cc_email']);
        }

        return $mail;
    }
}

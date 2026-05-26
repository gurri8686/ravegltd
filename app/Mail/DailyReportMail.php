<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = $this->data['subject'] ?? ('Daily Report — ' . ($this->data['period'] ?? ''));

        $mail = $this->subject($subject)
            ->view('emails.daily-report', ['data' => $this->data]);

        if (!empty($this->data['excel_binary'])) {
            $mail->attachData($this->data['excel_binary'], $this->data['excel_name'] ?? 'report.xlsx', [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        if (!empty($this->data['cc_email'])) {
            $mail->cc($this->data['cc_email']);
        }

        return $mail;
    }
}

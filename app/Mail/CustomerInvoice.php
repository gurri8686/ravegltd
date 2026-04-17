<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CompanyDetailModel;

class CustomerInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
		// Generate PDF here (inside the queued job)
        $pdf = Pdf::loadView('invoice', [
            'data' => (object)$this->data['invoice'],
            'companyDetails' => (object)$this->data['companyDetails'],
        ]);
		
        return $this->subject('Your Invoice #'.$this->data['invoice']['id'])
            ->view('emails.customer-invoice', [
                'invoice' => $this->data['invoice'],
                'companyDetails' => $this->data['companyDetails'],
            ])
            ->attachData($pdf->output(), 'invoice.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}

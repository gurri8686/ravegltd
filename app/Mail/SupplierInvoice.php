<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplierInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $invoice = $this->data['invoice'];
        $currency = env('CURRENCY_SYMBOL', '£');

        $rows = [];
        foreach (($invoice['product'] ?? []) as $p) {
            $name = '';
            if (!empty($p['product_info'])) {
                $info = is_string($p['product_info']) ? json_decode($p['product_info'], true) : $p['product_info'];
                $name = $info['name'] ?? '';
            }
            if (empty($name) && !empty($p['product']['name'])) {
                $name = $p['product']['name'];
            }
            $sellPrice = $p['sale_price'] ?? null;
            $rows[] = [
                $name,
                $p['remarks'] ?? '',
                $p['quantity'] ?? '',
                number_format((float) ($p['unit_price'] ?? 0), 2),
                !empty($sellPrice) ? number_format((float) $sellPrice, 2) : '',
                number_format((float) ($p['sub_total'] ?? 0), 2),
            ];
        }

        $headings = ['Product', 'Remarks', 'Qty', 'Price (' . $currency . ')', 'Sell Price (' . $currency . ')', 'Total (' . $currency . ')'];

        $export = new class($rows, $headings) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $rows; protected $headings;
            public function __construct($rows, $headings) { $this->rows = $rows; $this->headings = $headings; }
            public function array(): array { return $this->rows; }
            public function headings(): array { return $this->headings; }
        };

        $excelBinary = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $excelName = 'purchase-invoice-' . $invoice['id'] . '.xlsx';

        $subject = $this->data['subject'] ?? ('Purchase Invoice #' . $invoice['id']);

        $mail = $this->subject($subject)
            ->view('emails.supplier-invoice', ['data' => $this->data])
            ->attachData($excelBinary, $excelName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        if (!empty($this->data['cc_email'])) {
            $mail->cc($this->data['cc_email']);
        }

        return $mail;
    }
}

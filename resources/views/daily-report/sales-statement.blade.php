<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Daily Sales Statement</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #F27420; padding-bottom: 15px; }
    .header h1 { font-size: 20px; color: #F27420; margin: 0 0 5px; }
    .header h2 { font-size: 14px; color: #555; margin: 0 0 3px; font-weight: 400; }
    .header p { font-size: 11px; color: #888; margin: 3px 0 0; }
    .company-name { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .summary-box { display: flex; margin-bottom: 15px; }
    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .summary-table td { padding: 6px 12px; font-size: 12px; }
    .summary-table .label { color: #888; font-weight: 600; width: 140px; }
    .summary-table .value { color: #333; font-weight: 700; }
    table.invoice-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.invoice-table th { background: #FFF7F2; color: #374151; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; border-bottom: 2px solid #F27420; text-align: left; }
    table.invoice-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 11px; color: #444; }
    table.invoice-table tr:nth-child(even) { background: #fafafa; }
    table.invoice-table .text-right { text-align: right; }
    table.invoice-table .text-center { text-align: center; }
    .totals-row { background: #FFF7F2 !important; font-weight: 700; }
    .totals-row td { border-top: 2px solid #F27420; color: #1e293b; font-size: 12px; padding: 10px; }
    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    .badge-paid { color: #16a34a; font-weight: 600; }
    .badge-partial { color: #F27420; font-weight: 600; }
    .badge-unpaid { color: #ef4444; font-weight: 600; }
</style>
</head>
<body>

<div class="header">
    @if($companyDetails)
        <div class="company-name">{{ $companyDetails->name ?? 'Company' }}</div>
    @endif
    <h1>Daily Sales Statement</h1>
    <h2>{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} @if($start_date != $end_date) - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }} @endif</h2>
    <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>
</div>

<table class="summary-table">
    <tr>
        <td class="label">Total Invoices:</td>
        <td class="value">{{ $invoices->count() }}</td>
        <td class="label">Total Sales:</td>
        <td class="value">{{ $currency }} {{ number_format($invoices->sum('total'), 2) }}</td>
    </tr>
    <tr>
        <td class="label">Total Paid:</td>
        <td class="value" style="color:#16a34a;">{{ $currency }} {{ number_format($invoices->sum('total_paid'), 2) }}</td>
        <td class="label">Total Pending:</td>
        <td class="value" style="color:#F27420;">{{ $currency }} {{ number_format($invoices->sum('total') - $invoices->sum('total_paid'), 2) }}</td>
    </tr>
</table>

<table class="invoice-table">
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Invoice No.</th>
            <th>Date</th>
            <th>Customer</th>
            <th class="text-right">Amount</th>
            <th class="text-right">Paid</th>
            <th class="text-right">Pending</th>
            <th class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoices as $index => $invoice)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td><strong>#{{ $invoice->id }}</strong></td>
            <td>{{ uk_ts($invoice->created_at, 'd M Y') }}</td>
            <td>{{ $invoice->customer->name ?? '-' }}</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoice->total ?? 0, 2) }}</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoice->total_paid ?? 0, 2) }}</td>
            <td class="text-right">{{ $currency }} {{ number_format(($invoice->total ?? 0) - ($invoice->total_paid ?? 0), 2) }}</td>
            <td class="text-center">
                @if(($invoice->total_paid ?? 0) >= ($invoice->total ?? 0) && ($invoice->total ?? 0) > 0)
                    <span class="badge-paid">Paid</span>
                @elseif(($invoice->total_paid ?? 0) > 0)
                    <span class="badge-partial">Partial</span>
                @else
                    <span class="badge-unpaid">Unpaid</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center; color:#aaa; padding:20px;">No invoices found for this period.</td>
        </tr>
        @endforelse
    </tbody>
    @if($invoices->count() > 0)
    <tfoot>
        <tr class="totals-row">
            <td colspan="4" style="text-align:right;">Total:</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoices->sum('total'), 2) }}</td>
            <td class="text-right" style="color:#16a34a;">{{ $currency }} {{ number_format($invoices->sum('total_paid'), 2) }}</td>
            <td class="text-right" style="color:#F27420;">{{ $currency }} {{ number_format($invoices->sum('total') - $invoices->sum('total_paid'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    This is a system-generated document. &copy; {{ date('Y') }} {{ $companyDetails->name ?? '' }}
</div>

</body>
</html>

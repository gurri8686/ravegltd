<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Daily Sales Print</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #F27420; }
    .header-left h1 { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0 0 3px; }
    .header-left p { font-size: 11px; color: #888; margin: 3px 0 0; }
    .header-right { text-align: right; font-size: 11px; color: #555; }
    .header-right .company-name { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
    .summary { display: flex; gap: 12px; margin-bottom: 16px; }
    .summary-card { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; }
    .summary-card .label { font-size: 10px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
    .summary-card .value { font-size: 16px; font-weight: 700; color: #1e293b; margin-top: 2px; }
    .summary-card.paid .value { color: #16a34a; }
    .summary-card.pending .value { color: #F27420; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: #FFF7F2; color: #374151; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 10px; border-bottom: 2px solid #F27420; text-align: left; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 11px; color: #444; }
    tbody tr:nth-child(even) { background: #fafafa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    tfoot td { background: #FFF7F2; font-weight: 700; border-top: 2px solid #F27420; padding: 9px 10px; font-size: 12px; color: #1e293b; }
    .badge-paid { color: #16a34a; font-weight: 600; }
    .badge-partial { color: #F27420; font-weight: 600; }
    .badge-unpaid { color: #ef4444; font-weight: 600; }
    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    @media print { body { padding: 0; } .no-print { display: none !important; } }
</style>
</head>
<body onload="window.print()">

<div class="header">
    <div class="header-left">
        <h1>Daily Sales Report</h1>
        <p>{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }}@if($start_date != $end_date) &ndash; {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}@endif</p>
        <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
    </div>
    <div class="header-right">
        @if($companyDetails)
            <div class="company-name">{{ $companyDetails->company_name ?? $companyDetails->name ?? '' }}</div>
            @if($companyDetails->address1)<div>{{ $companyDetails->address1 }}</div>@endif
            @if($companyDetails->telephone)<div>Tel: {{ $companyDetails->telephone }}</div>@endif
        @endif
    </div>
</div>

<div class="summary">
    <div class="summary-card">
        <div class="label">Total Invoices</div>
        <div class="value">{{ $invoices->count() }}</div>
    </div>
    <div class="summary-card">
        <div class="label">Total Amount</div>
        <div class="value">{{ $currency }} {{ number_format($invoices->sum('total'), 2) }}</div>
    </div>
    <div class="summary-card paid">
        <div class="label">Total Paid</div>
        <div class="value">{{ $currency }} {{ number_format($invoices->sum('total_paid'), 2) }}</div>
    </div>
    <div class="summary-card pending">
        <div class="label">Total Pending</div>
        <div class="value">{{ $currency }} {{ number_format($invoices->sum('total') - $invoices->sum('total_paid'), 2) }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
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
        @forelse($invoices as $i => $invoice)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>#{{ $invoice->id }}</strong></td>
            <td>{{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</td>
            <td>{{ $invoice->customer->name ?? '-' }}</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoice->total ?? 0, 2) }}</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoice->total_paid ?? 0, 2) }}</td>
            <td class="text-right">{{ $currency }} {{ number_format(($invoice->total ?? 0) - ($invoice->total_paid ?? 0), 2) }}</td>
            <td class="text-center">
                @php $paid = $invoice->total_paid ?? 0; $total = $invoice->total ?? 0; @endphp
                @if($paid >= $total && $total > 0)
                    <span class="badge-paid">Paid</span>
                @elseif($paid > 0)
                    <span class="badge-partial">Partial</span>
                @else
                    <span class="badge-unpaid">Unpaid</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#aaa;padding:20px;">No invoices found for this period.</td>
        </tr>
        @endforelse
    </tbody>
    @if($invoices->count() > 0)
    <tfoot>
        <tr>
            <td colspan="4" class="text-right">Total:</td>
            <td class="text-right">{{ $currency }} {{ number_format($invoices->sum('total'), 2) }}</td>
            <td class="text-right" style="color:#16a34a;">{{ $currency }} {{ number_format($invoices->sum('total_paid'), 2) }}</td>
            <td class="text-right" style="color:#F27420;">{{ $currency }} {{ number_format($invoices->sum('total') - $invoices->sum('total_paid'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    System-generated document &copy; {{ date('Y') }} {{ $companyDetails->company_name ?? $companyDetails->name ?? '' }}
</div>

</body>
</html>

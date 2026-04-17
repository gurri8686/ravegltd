<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Product History Statement</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
    .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #F27420; padding-bottom: 12px; }
    .header h1 { font-size: 18px; color: #F27420; margin: 0 0 4px; }
    .header h2 { font-size: 13px; color: #555; margin: 0; font-weight: 400; }
    .company-name { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
    .header p { font-size: 10px; color: #888; margin: 3px 0 0; }
    .section-title { font-size: 13px; font-weight: 700; color: #F27420; margin: 15px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #fed7aa; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th { background: #FFF7F2; color: #374151; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; padding: 6px 8px; border-bottom: 1.5px solid #F27420; text-align: left; }
    td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; font-size: 10px; color: #444; }
    tr:nth-child(even) { background: #fafafa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .totals-row { background: #FFF7F2 !important; }
    .totals-row td { border-top: 1.5px solid #F27420; font-weight: 700; color: #1e293b; }
    .summary-box { display: inline-block; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 16px; margin-right: 10px; margin-bottom: 8px; }
    .summary-label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .summary-value { font-size: 14px; font-weight: 700; color: #1e293b; }
    .footer { margin-top: 15px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    .empty-msg { text-align: center; color: #aaa; padding: 12px; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
    @if($companyDetails)
        <div class="company-name">{{ $companyDetails->name ?? 'Company' }}</div>
    @endif
    <h1>Product History Statement</h1>
    <h2>{{ $productName }} | {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</h2>
    <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>
</div>

@php
    $totalPurchaseQty = collect($suppliers)->sum('quantity');
    $totalPurchaseAmt = collect($suppliers)->sum('sub_total');
    $totalSalesQty = collect($sales)->sum('quantity');
    $totalSalesAmt = collect($sales)->sum('sub_total');
    $totalSupRetQty = collect($supplier_returns)->sum('stock');
    $totalCustRetQty = collect($customer_returns)->sum('stock');
    $totalDumpsQty = collect($dumps)->sum('stock');
@endphp

<div style="margin-bottom:12px;">
    <div class="summary-box">
        <div class="summary-label">Total Purchases</div>
        <div class="summary-value">{{ $currency }}{{ number_format($totalPurchaseAmt, 2) }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Total Sales</div>
        <div class="summary-value">{{ $currency }}{{ number_format($totalSalesAmt, 2) }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Purchase Qty</div>
        <div class="summary-value">{{ $totalPurchaseQty }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Sales Qty</div>
        <div class="summary-value">{{ $totalSalesQty }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Sup. Returns</div>
        <div class="summary-value">{{ $totalSupRetQty }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Cust. Returns</div>
        <div class="summary-value">{{ $totalCustRetQty }}</div>
    </div>
    <div class="summary-box">
        <div class="summary-label">Dumps</div>
        <div class="summary-value">{{ $totalDumpsQty }}</div>
    </div>
</div>

{{-- Supplier Invoices --}}
<div class="section-title">From Supplier (Purchases)</div>
@if(count($suppliers) > 0)
<table>
    <thead><tr><th>#</th><th>Date</th><th>Supplier</th><th class="text-center">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @foreach($suppliers as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}</td>
            <td>{{ $row['supplier']['name'] ?? '-' }}</td>
            <td class="text-center">{{ $row['quantity'] }}</td>
            <td class="text-right">{{ $currency }}{{ number_format($row['unit_price'], 2) }}</td>
            <td class="text-right">{{ $currency }}{{ number_format($row['sub_total'], 2) }}</td>
        </tr>
    @endforeach
    <tr class="totals-row"><td colspan="3" class="text-right">Total:</td><td class="text-center">{{ $totalPurchaseQty }}</td><td></td><td class="text-right">{{ $currency }}{{ number_format($totalPurchaseAmt, 2) }}</td></tr>
    </tbody>
</table>
@else
<div class="empty-msg">No purchase records found.</div>
@endif

{{-- Supplier Returns --}}
<div class="section-title">Supplier Returns</div>
@if(count(collect($supplier_returns)->toArray()) > 0)
<table>
    <thead><tr><th>#</th><th>Date</th><th>Supplier</th><th class="text-center">Qty</th></tr></thead>
    <tbody>
    @foreach($supplier_returns as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}</td>
            <td>{{ $row['supplier']['name'] ?? '-' }}</td>
            <td class="text-center">{{ $row['stock'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="empty-msg">No supplier return records found.</div>
@endif

{{-- Sales --}}
<div class="section-title">Sales</div>
@if(count($sales) > 0)
<table>
    <thead><tr><th>#</th><th>Date</th><th>Customer</th><th class="text-center">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @foreach($sales as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}</td>
            <td>{{ $row['customer']['name'] ?? '-' }}</td>
            <td class="text-center">{{ $row['quantity'] }}</td>
            <td class="text-right">{{ $currency }}{{ number_format($row['unit_price'], 2) }}</td>
            <td class="text-right">{{ $currency }}{{ number_format($row['sub_total'], 2) }}</td>
        </tr>
    @endforeach
    <tr class="totals-row"><td colspan="3" class="text-right">Total:</td><td class="text-center">{{ $totalSalesQty }}</td><td></td><td class="text-right">{{ $currency }}{{ number_format($totalSalesAmt, 2) }}</td></tr>
    </tbody>
</table>
@else
<div class="empty-msg">No sales records found.</div>
@endif

{{-- Customer Returns --}}
<div class="section-title">Customer Returns</div>
@if(count($customer_returns) > 0)
<table>
    <thead><tr><th>#</th><th>Date</th><th>Customer</th><th class="text-center">Qty</th></tr></thead>
    <tbody>
    @foreach($customer_returns as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}</td>
            <td>{{ $row['customer']['name'] ?? '-' }}</td>
            <td class="text-center">{{ $row['stock'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="empty-msg">No customer return records found.</div>
@endif

{{-- Dumps --}}
<div class="section-title">Dumps</div>
@if(count($dumps) > 0)
<table>
    <thead><tr><th>#</th><th>Date</th><th>Supplier</th><th class="text-center">Qty</th></tr></thead>
    <tbody>
    @foreach($dumps as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($row['created_at'])->format('d M Y') }}</td>
            <td>{{ $row['supplier']['name'] ?? '-' }}</td>
            <td class="text-center">{{ $row['stock'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="empty-msg">No dump records found.</div>
@endif

<div class="footer">
    This is a system-generated document. &copy; {{ date('Y') }} {{ $companyDetails->name ?? '' }}
</div>

</body>
</html>

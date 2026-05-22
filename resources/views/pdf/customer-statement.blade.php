<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Account Statement</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #1f2937; font-size: 12px; }
    .wrap { padding: 28px 34px; }

    /* Header */
    .head { width: 100%; border-bottom: 3px solid #ea580c; padding-bottom: 14px; margin-bottom: 18px; }
    .head td { vertical-align: top; }
    .company-name { font-size: 21px; font-weight: bold; color: #0f1115; letter-spacing: -0.3px; }
    .company-sub { font-size: 10.5px; color: #6b7280; margin-top: 2px; }
    .doc-title { font-size: 18px; font-weight: bold; color: #ea580c; text-align: right; }
    .doc-meta { font-size: 10.5px; color: #6b7280; text-align: right; margin-top: 3px; }

    /* Info boxes */
    .info { width: 100%; margin-bottom: 16px; }
    .info td { vertical-align: top; width: 50%; padding: 0; }
    .info-box { background: #fafafb; border: 1px solid #e8e8ec; border-radius: 8px; padding: 11px 14px; }
    .info-label { font-size: 9px; font-weight: bold; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
    .info-value { font-size: 12.5px; font-weight: bold; color: #0f1115; }
    .info-line { font-size: 10.5px; color: #6b7280; margin-top: 2px; }

    /* Summary cards */
    .summary { width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 8px 0; }
    .summary td { width: 33.33%; }
    .sum-card { border: 1px solid #e8e8ec; border-radius: 8px; padding: 10px 12px; }
    .sum-card .lbl { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6px; color: #9ca3af; }
    .sum-card .val { font-size: 16px; font-weight: bold; margin-top: 3px; }

    /* Table */
    table.tbl { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.tbl thead td { background: #fff7f0; border-bottom: 2px solid #fbe2cf; padding: 8px 8px; font-size: 9px; font-weight: bold; color: #1f2937; text-transform: uppercase; letter-spacing: 0.5px; }
    table.tbl tbody td { border-bottom: 1px solid #f0f0f2; padding: 8px 8px; font-size: 10.5px; }
    table.tbl tbody tr.opening td { background: #fcfcfd; font-weight: bold; color: #6b7280; }
    table.tbl tfoot td { border-top: 2px solid #fbe2cf; padding: 9px 8px; font-size: 11px; font-weight: bold; color: #0f1115; background: #fff7f0; }
    .num { text-align: right; }
    .inv-badge { color: #F27420; font-weight: bold; }

    /* Footer */
    .terms { margin-top: 22px; border-top: 1px solid #e8e8ec; padding-top: 12px; }
    .terms-title { font-size: 10px; font-weight: bold; color: #0f1115; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .terms-text { font-size: 10px; color: #6b7280; line-height: 1.55; }
    .pagefoot { margin-top: 16px; text-align: center; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="wrap">

    @php
        $cur = env('CURRENCY_SYMBOL', '£');
        $money = function ($v) use ($cur) { return $cur . ' ' . number_format((float)$v, 2); };

        $totalNet = 0; $totalPaid = 0; $totalCreditAdj = 0; $totalDiscountAdj = 0; $totalBalance = 0;
        $running = (float) $pastBalance;
        $rows = [];
        $i = 1;
        foreach ($invoices as $invoice) {
            if (($invoice['is_credited'] ?? 0) == 1) { continue; }
            $net      = (float) ($invoice['net_amount'] ?? 0);
            $paid     = (float) ($invoice['total_paid'] ?? 0);
            $creditAdj= (float) ($invoice['credit_adj'] ?? 0);
            $discAdj  = (float) ($invoice['total_discounted'] ?? 0);
            $bal      = (float) ($invoice['balance'] ?? 0);
            $running += $bal;
            $totalNet += $net; $totalPaid += $paid; $totalCreditAdj += $creditAdj;
            $totalDiscountAdj += $discAdj; $totalBalance += $bal;
            $rows[] = [
                'i' => $i++, 'date' => $invoice['created_at'] ?? '', 'id' => $invoice['id'] ?? '',
                'net' => $net, 'paid' => $paid, 'creditAdj' => $creditAdj,
                'discAdj' => $discAdj, 'bal' => $bal, 'running' => $running,
            ];
        }
        $closingBalance = (float) $pastBalance + $totalBalance;
    @endphp

    {{-- Header --}}
    <table class="head">
        <tr>
            <td>
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-sub">Account Statement</div>
            </td>
            <td>
                <div class="doc-title">STATEMENT</div>
                <div class="doc-meta">Generated: {{ date('d M Y') }}</div>
            </td>
        </tr>
    </table>

    {{-- Customer + period --}}
    <table class="info">
        <tr>
            <td style="padding-right:5px;">
                <div class="info-box">
                    <div class="info-label">Statement For</div>
                    <div class="info-value">{{ $customer->name ?? 'Customer' }}</div>
                    @if(!empty($customer->customer_id))<div class="info-line">Customer ID: {{ $customer->customer_id }}</div>@endif
                    @if(!empty($customer->email))<div class="info-line">{{ $customer->email }}</div>@endif
                    @if(!empty($customer->mobile))<div class="info-line">{{ $customer->mobile }}</div>@endif
                    @if(!empty($customer->address1))<div class="info-line">{{ $customer->address1 }}</div>@endif
                </div>
            </td>
            <td style="padding-left:5px;">
                <div class="info-box">
                    <div class="info-label">Statement Period</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</div>
                    <div class="info-line">Opening Balance: {{ $money($pastBalance) }}</div>
                    <div class="info-line">Closing Balance: <strong style="color:#b91c1c;">{{ $money($closingBalance) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Invoice table --}}
    <table class="tbl">
        <thead>
            <tr>
                <td style="width:28px;">#</td>
                <td>Date</td>
                <td>Invoice</td>
                <td class="num">Amount</td>
                <td class="num">Paid</td>
                <td class="num">Credit/Adj</td>
                <td class="num">Discount/Adj</td>
                <td class="num">Balance</td>
                <td class="num">Running Bal</td>
            </tr>
        </thead>
        <tbody>
            <tr class="opening">
                <td colspan="8">Opening Balance</td>
                <td class="num">{{ $money($pastBalance) }}</td>
            </tr>
            @forelse($rows as $r)
            <tr>
                <td>{{ $r['i'] }}</td>
                <td>{{ $r['date'] }}</td>
                <td><span class="inv-badge">#{{ $r['id'] }}</span></td>
                <td class="num">{{ $money($r['net']) }}</td>
                <td class="num">{{ $money($r['paid']) }}</td>
                <td class="num">{{ $money($r['creditAdj']) }}</td>
                <td class="num">{{ $money($r['discAdj']) }}</td>
                <td class="num">{{ $money($r['bal']) }}</td>
                <td class="num">{{ $money($r['running']) }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:22px 8px;">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="num">{{ $money($totalNet) }}</td>
                <td class="num">{{ $money($totalPaid) }}</td>
                <td class="num">{{ $money($totalCreditAdj) }}</td>
                <td class="num">{{ $money($totalDiscountAdj) }}</td>
                <td class="num">{{ $money($totalBalance) }}</td>
                <td class="num" style="color:#b91c1c;">{{ $money($closingBalance) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Terms --}}
    <div class="terms">
        <div class="terms-title">Payment Information</div>
        <div class="terms-text">
            The closing balance of <strong>{{ $money($closingBalance) }}</strong> is due. Please settle outstanding invoices at the earliest.
            For any queries regarding this statement, kindly contact us. This statement was generated on {{ date('d M Y') }}.
        </div>
    </div>

    <div class="pagefoot">{{ $companyName }} &mdash; Account Statement &mdash; This is a computer-generated document.</div>

</div>
</body>
</html>

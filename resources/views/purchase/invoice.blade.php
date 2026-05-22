<!DOCTYPE html>
<html>
<head>
<title>Purchase Invoice #{{$data->id}} - R & A Veg Ltd</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .invoice-wrap { max-width: 800px; margin: 0 auto; padding: 30px 40px; }

  /* Header */
  .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #F27420; }
  .company-logo { display: flex; align-items: center; gap: 14px; }
  .company-logo .logo-box { width: 50px; height: 50px; background: #F27420; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
  .company-logo .logo-box span { color: #fff; font-weight: 900; font-size: 18px; letter-spacing: -0.5px; }
  .company-name { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
  .company-detail { font-size: 11px; color: #64748b; line-height: 1.7; }
  .invoice-badge { text-align: right; }
  .invoice-badge .badge-label { font-size: 12px; font-weight: 700; color: #F27420; letter-spacing: 2px; text-transform: uppercase; }
  .invoice-badge .badge-number { font-size: 36px; font-weight: 900; color: #0f172a; line-height: 1.1; }
  .invoice-badge .badge-date { font-size: 12px; color: #64748b; margin-top: 4px; }

  /* Info cards */
  .info-row { display: flex; gap: 20px; margin-bottom: 28px; }
  .info-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; }
  .info-card .info-label { font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 6px; }
  .info-card .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
  .info-card .info-sub { font-size: 11px; color: #64748b; margin-top: 2px; }

  /* Table */
  .order-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .order-table thead th { background: #F27420; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; padding: 10px 14px; }
  .order-table thead th:first-child { border-radius: 8px 0 0 0; }
  .order-table thead th:last-child { border-radius: 0 8px 0 0; text-align: right; }
  .order-table tbody td { padding: 12px 14px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
  .order-table tbody tr:last-child td { border-bottom: 2px solid #e2e8f0; }
  .order-table .text-right { text-align: right; }
  .order-table .text-center { text-align: center; }
  .order-table .product-name { font-weight: 600; color: #1e293b; }
  .order-table .product-remarks { font-size: 11px; color: #94a3b8; font-style: italic; }

  /* Summary */
  .summary-wrap { display: flex; justify-content: flex-end; }
  .summary-table { width: 280px; }
  .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  .summary-row .label { color: #64748b; font-weight: 500; }
  .summary-row .value { color: #1e293b; font-weight: 600; }
  .summary-total { display: flex; justify-content: space-between; padding: 12px 0; margin-top: 4px; border-top: 2px solid #F27420; }
  .summary-total .label { font-size: 15px; font-weight: 800; color: #0f172a; }
  .summary-total .value { font-size: 18px; font-weight: 800; color: #F27420; }

  /* Footer */
  .invoice-footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #94a3b8; }

  @media print {
    body { background: #fff; }
    .invoice-wrap { padding: 20px; }
  }
</style>
</head>
<body onload="window.print()">
<div class="invoice-wrap">

  <!-- Header -->
  <div class="invoice-header">
    <div class="company-logo">
      <div class="logo-box"><span>R&A</span></div>
      <div>
        <div class="company-name">
          @isset($companyDetails->company_name){{$companyDetails->company_name}}@else R & A Veg Ltd @endisset
        </div>
        <div class="company-detail">
          @isset($companyDetails->address1){{$companyDetails->address1}}<br>@endisset
          @isset($companyDetails->country){{$companyDetails->country}}@endisset
          @isset($companyDetails->zipcode) · {{$companyDetails->zipcode}}@endisset
          @isset($companyDetails->email)<br>{{$companyDetails->email}}@endisset
          @isset($companyDetails->telephone)<br>+{{$companyDetails->telephone}}@endisset
        </div>
      </div>
    </div>
    <div class="invoice-badge">
      <div class="badge-label">Purchase Invoice</div>
      <div class="badge-number">#{{$data->id}}</div>
      <div class="badge-date">{{ $data->created_at }}</div>
    </div>
  </div>

  <!-- Supplier + Invoice info -->
  <div class="info-row">
    <div class="info-card">
      <div class="info-label">Supplier</div>
      <?php $supplier = \App\Models\Supplier::where(['id' => $data->supplier_id])->first(); ?>
      <div class="info-value">{{$supplier->name ?? '—'}}</div>
      @if(!empty($supplier->email))<div class="info-sub">{{$supplier->email}}</div>@endif
      @if(!empty($supplier->address1))<div class="info-sub">{{$supplier->address1}}</div>@endif
    </div>
    <div class="info-card">
      <div class="info-label">Invoice Details</div>
      <div class="info-value">Invoice #{{$data->id}}</div>
      <div class="info-sub">Date: {{ $data->created_at }}</div>
      @if(!empty($data->other_invoice_id))<div class="info-sub">Ref: {{$data->other_invoice_id}}</div>@endif
    </div>
  </div>

  <!-- Order table -->
  <table class="order-table">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th>Item</th>
        <th class="text-center">Price</th>
        <th class="text-center">Qty</th>
        <th class="text-right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $subtotal = 0; $i = 1; ?>
      @foreach ($data->product as $item)
      <tr>
        <td style="color:#94a3b8">{{$i++}}</td>
        <td>
          <?php $getjson = json_decode($item->product_info); ?>
          <span class="product-name">{{ $getjson->name ?? '—' }}</span>
          @if($item->remarks)<br><span class="product-remarks">{{$item->remarks}}</span>@endif
        </td>
        <td class="text-center">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($item->unit_price, 2)}}</td>
        <td class="text-center">{{$item->quantity}}</td>
        <td class="text-right" style="font-weight:700">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($item->sub_total, 2)}}</td>
        <?php $subtotal += $item->sub_total; ?>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Summary -->
  <?php
    $vatPercent = (float)env('VAT', 0);
    $porterage = (float)env('PORTERAGE', 0);
    $vatAmount = ($subtotal * $vatPercent) / 100;
    $total = $subtotal + $vatAmount + $porterage;
  ?>
  <div class="summary-wrap">
    <div class="summary-table">
      <div class="summary-row">
        <span class="label">Sub Total</span>
        <span class="value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($subtotal, 2)}}</span>
      </div>
      <div class="summary-row">
        <span class="label">Porterage</span>
        <span class="value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($porterage, 2)}}</span>
      </div>
      <div class="summary-row">
        <span class="label">VAT ({{$vatPercent}}%)</span>
        <span class="value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($vatAmount, 2)}}</span>
      </div>
      <div class="summary-total">
        <span class="label">Total</span>
        <span class="value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($total, 2)}}</span>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <div class="invoice-footer">
    Thank you for your business · @isset($companyDetails->company_name){{$companyDetails->company_name}}@else R & A Veg Ltd @endisset
  </div>

</div>
</body>
</html>

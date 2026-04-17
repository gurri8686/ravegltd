<!DOCTYPE html>
<html>
<head>
<title>Invoice #{{$data->id}}</title>
<style>
  body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 13px; margin: 0; padding: 30px 40px; }

  .header-table { width: 100%; margin-bottom: 10px; }
  .header-table td { vertical-align: top; padding: 0; }
  .logo-box { display: inline-block; width: 44px; height: 44px; background: #F27420; border-radius: 8px; text-align: center; line-height: 44px; color: #fff; font-weight: 900; font-size: 16px; }
  .company-name { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; }
  .company-detail { font-size: 10px; color: #64748b; line-height: 1.6; }
  .inv-badge-label { font-size: 11px; font-weight: 700; color: #F27420; letter-spacing: 2px; text-transform: uppercase; }
  .inv-badge-number { font-size: 32px; font-weight: 900; color: #0f172a; }
  .inv-badge-date { font-size: 11px; color: #64748b; }

  .orange-line { border: none; border-top: 3px solid #F27420; margin: 16px 0 20px 0; }

  .info-table { width: 100%; margin-bottom: 24px; }
  .info-table td { vertical-align: top; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
  .info-label { font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
  .info-value { font-size: 13px; font-weight: 700; color: #1e293b; }
  .info-sub { font-size: 10px; color: #64748b; margin-top: 2px; }

  .order-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  .order-table th { background: #F27420; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; padding: 9px 12px; text-align: left; }
  .order-table th:last-child { text-align: right; }
  .order-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
  .order-table .text-right { text-align: right; }
  .order-table .text-center { text-align: center; }
  .product-name { font-weight: 700; color: #1e293b; }
  .product-remarks { font-size: 10px; color: #94a3b8; font-style: italic; }

  .summary-table { width: 240px; float: right; margin-top: 4px; }
  .summary-table td { padding: 6px 0; font-size: 12px; }
  .summary-label { color: #64748b; }
  .summary-value { text-align: right; font-weight: 600; color: #1e293b; }
  .summary-total-row td { padding-top: 10px; border-top: 2.5px solid #F27420; }
  .summary-total-label { font-size: 14px; font-weight: 800; color: #0f172a; }
  .summary-total-value { text-align: right; font-size: 16px; font-weight: 800; color: #F27420; }

  .footer { clear: both; margin-top: 50px; padding-top: 12px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 10px; color: #94a3b8; }
</style>
</head>
<body>

  <!-- Header -->
  <table class="header-table">
    <tr>
      <td style="width:60%">
        <table cellpadding="0" cellspacing="0"><tr>
          <td style="width:54px;vertical-align:top;padding-right:12px;"><div class="logo-box">R&A</div></td>
          <td style="vertical-align:top;">
            <p class="company-name">@isset($companyDetails->company_name){{$companyDetails->company_name}}@else R & A Veg Ltd @endisset</p>
            <div class="company-detail">
              @isset($companyDetails->address1){{$companyDetails->address1}}<br>@endisset
              @isset($companyDetails->country){{$companyDetails->country}}@endisset @isset($companyDetails->zipcode)&middot; {{$companyDetails->zipcode}}@endisset
              @isset($companyDetails->email)<br>{{$companyDetails->email}}@endisset
              @isset($companyDetails->telephone)<br>+{{$companyDetails->telephone}}@endisset
            </div>
          </td>
        </tr></table>
      </td>
      <td style="text-align:right;">
        <div class="inv-badge-label">INVOICE</div>
        <div class="inv-badge-number">#{{$data->id}}</div>
        <div class="inv-badge-date">{{ $data->created_at }}</div>
      </td>
    </tr>
  </table>

  <hr class="orange-line">

  <!-- Customer + Invoice Info -->
  <table class="info-table" cellspacing="8">
    <tr>
      <td style="width:50%">
        <div class="info-label">Customer</div>
        <?php $customer = \App\Models\Customer::where(['id' => $data->customer_id])->first(); ?>
        <div class="info-value">{{$customer->name}}</div>
        @if($customer->email)<div class="info-sub">{{$customer->email}}</div>@endif
        @if($customer->address1)<div class="info-sub">{{$customer->address1}}</div>@endif
      </td>
      <td style="width:50%">
        <div class="info-label">Invoice Details</div>
        <div class="info-value">Invoice #{{$data->id}}</div>
        <div class="info-sub">Date: {{ $data->created_at }}</div>
      </td>
    </tr>
  </table>

  <!-- Order Table -->
  <table class="order-table">
    <thead>
      <tr>
        <th style="width:30px">#</th>
        <th>Item</th>
        <th class="text-center">Price</th>
        <th class="text-center">Qty</th>
        <th class="text-right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php $subtotal = 0; $i = 1; ?>
      @foreach ($data->product as $item)
      <?php $item = is_array($item) ? (object)$item : $item; ?>
      <tr>
        <td style="color:#94a3b8">{{$i++}}</td>
        <td>
          <?php $getjson = is_array($item) ? json_decode($item->product_info) : json_decode($item->product_info); ?>
          <span class="product-name">{{$getjson->name}}</span>
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
  <table class="summary-table">
    <tr>
      <td class="summary-label">Sub Total</td>
      <td class="summary-value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($subtotal, 2)}}</td>
    </tr>
    <tr>
      <td class="summary-label">Porterage</td>
      <td class="summary-value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($porterage, 2)}}</td>
    </tr>
    <tr>
      <td class="summary-label">VAT ({{$vatPercent}}%)</td>
      <td class="summary-value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($vatAmount, 2)}}</td>
    </tr>
    <tr class="summary-total-row">
      <td class="summary-total-label">Total</td>
      <td class="summary-total-value">{{env('CURRENCY_SYMBOL', '£')}} {{number_format($total, 2)}}</td>
    </tr>
  </table>

  <div class="footer">
    Thank you for your business &middot; @isset($companyDetails->company_name){{$companyDetails->company_name}}@else R & A Veg Ltd @endisset
  </div>

</body>
</html>

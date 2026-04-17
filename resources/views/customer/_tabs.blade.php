@php
$currentRoute = Route::currentRouteName();
$customerTabs = [
    ['route' => 'management.customers.view.index', 'label' => 'All Customers', 'icon' => 'fa fa-th-list', 'matches' => ['management.customers.view.index']],
    ['route' => 'payments.customer_payment.view.index', 'label' => 'Payments', 'icon' => 'fa fa-credit-card', 'matches' => ['payments.customer_payment.view.index','management.customers.on_account_payment.view.index','customer_payment_history.view.index']],
    ['route' => 'customer_return.view.index', 'label' => 'Returns', 'icon' => 'fa fa-undo', 'matches' => ['customer_return.view.index']],
    ['route' => 'customer_history.view.index', 'label' => 'History', 'icon' => 'fa fa-history', 'matches' => ['customer_history.view.index']],
];
@endphp
<div style="display:flex;gap:4px;padding:12px 22px 0;border-bottom:2px solid #eef2f7;margin-bottom:0;">
@foreach($customerTabs as $tab)
    @php $isActive = in_array($currentRoute, $tab['matches']); @endphp
    <a href="{{ route($tab['route']) }}" style="padding:10px 18px;font-size:12.5px;font-weight:600;text-decoration:none;border-bottom:{{ $isActive ? '2px solid #F27420' : '2px solid transparent' }};color:{{ $isActive ? '#F27420' : '#64748b' }};background:{{ $isActive ? '#fff7ed' : 'transparent' }};border-radius:8px 8px 0 0;transition:all 0.15s;margin-bottom:-2px;display:inline-flex;align-items:center;gap:6px;">
        <i class="{{ $tab['icon'] }}"></i>{{ $tab['label'] }}
    </a>
@endforeach
</div>

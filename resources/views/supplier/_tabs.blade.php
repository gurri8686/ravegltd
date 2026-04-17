@php
$currentRoute = Route::currentRouteName();
$supplierTabs = [
    ['route' => 'management.suppliers.view.index', 'label' => 'All Suppliers', 'icon' => 'fa fa-th-list', 'matches' => ['management.suppliers.view.index']],
    ['route' => 'payments.supplier_payment.view.index', 'label' => 'Payments', 'icon' => 'fa fa-credit-card', 'matches' => ['payments.supplier_payment.view.index','management.suppliers.on_account_payment.view.index','supplier_payment_history.view.index']],
    ['route' => 'supplier_return.view.index', 'label' => 'Returns', 'icon' => 'fa fa-undo', 'matches' => ['supplier_return.view.index','dump_return.view.index']],
    ['route' => 'supplier_history.view.index', 'label' => 'History', 'icon' => 'fa fa-history', 'matches' => ['supplier_history.view.index']],
];
@endphp
<div style="display:flex;gap:4px;padding:12px 22px 0;border-bottom:2px solid #eef2f7;margin-bottom:0;">
@foreach($supplierTabs as $tab)
    @php $isActive = in_array($currentRoute, $tab['matches']); @endphp
    <a href="{{ route($tab['route']) }}" style="padding:10px 18px;font-size:12.5px;font-weight:600;text-decoration:none;border-bottom:{{ $isActive ? '2px solid #F27420' : '2px solid transparent' }};color:{{ $isActive ? '#F27420' : '#64748b' }};background:{{ $isActive ? '#fff7ed' : 'transparent' }};border-radius:8px 8px 0 0;transition:all 0.15s;margin-bottom:-2px;display:inline-flex;align-items:center;gap:6px;">
        <i class="{{ $tab['icon'] }}"></i>{{ $tab['label'] }}
    </a>
@endforeach
</div>

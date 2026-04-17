@php
$currentRoute = Route::currentRouteName();
$productTabs = [
    ['route' => 'management.products.view.index', 'label' => 'List', 'icon' => 'fa fa-th-list', 'matches' => ['management.products.view.index']],
    ['route' => 'product_history.view.index',     'label' => 'History', 'icon' => 'fa fa-history', 'matches' => ['product_history.view.index','product_history.view.customers','product_history.view.suppliers','product_history.view.products']],
    ['route' => 'product_profit_loss.view.index', 'label' => 'Profit Loss', 'icon' => 'fa fa-line-chart', 'matches' => ['product_profit_loss.view.index','product_profit_loss.view.customers','product_profit_loss.view.suppliers','product_profit_loss.view.products']],
];
@endphp
<div style="display:flex;gap:4px;padding:12px 22px 0;border-bottom:2px solid #eef2f7;margin-bottom:0;">
@foreach($productTabs as $tab)
    @php $isActive = in_array($currentRoute, $tab['matches']); @endphp
    <a href="{{ route($tab['route']) }}" style="padding:10px 18px;font-size:12.5px;font-weight:600;text-decoration:none;border-bottom:{{ $isActive ? '2px solid #F27420' : '2px solid transparent' }};color:{{ $isActive ? '#F27420' : '#64748b' }};background:{{ $isActive ? '#fff7ed' : 'transparent' }};border-radius:8px 8px 0 0;transition:all 0.15s;margin-bottom:-2px;display:inline-flex;align-items:center;gap:6px;">
        <i class="{{ $tab['icon'] }}"></i>{{ $tab['label'] }}
    </a>
@endforeach
</div>

@extends('layouts.main')
@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection
@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush
@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
    <div style="display:flex;align-items:center;gap:14px;">
        <a href="{{route('dump_return.view.index')}}" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;"
           onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#fff7ed';"
           onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
            <i class="fa fa-history" style="color:#fff;font-size:20px;"></i>
        </div>
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Dump History</h1>
            <p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">View all dump records</p>
        </div>
    </div>
</div>
<div id="dump-return-history-app"
    data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
    data-returns-api="{{route('dump_return.view.returns')}}"
    data-suppliers-api="{{route('dump_return.view.suppliers')}}"
></div>
</section>
@endsection

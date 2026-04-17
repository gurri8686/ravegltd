@extends('layouts.test')

@push('stylesheets')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
body { background: #f8f9fc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; -webkit-font-smoothing: antialiased; }

.stmt-wrap { max-width: 1440px; margin: 0 auto; padding: 32px 28px; }

.stmt-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.stmt-title { font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
.stmt-title span { color: #F27420; }
.stmt-subtitle { font-size: 13px; color: #94a3b8; font-weight: 500; margin-top: 2px; }

.filter-card {
	background: #fff; border-radius: 20px; border: 1px solid #e8ecf2;
	box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04);
	padding: 24px 28px; margin-bottom: 24px;
}
.filter-row { display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-group.grow { flex: 1; min-width: 200px; }
.filter-label { font-size: 10.5px; font-weight: 700; color: #64748b; letter-spacing: 0.8px; text-transform: uppercase; }
.filter-input {
	height: 44px; border-radius: 12px; border: 1.5px solid #e2e8f0;
	font-size: 13.5px; font-weight: 500; background: #f8fafc; padding: 0 14px;
	color: #1e293b; outline: none; transition: all 0.2s ease;
}
.filter-input:focus { border-color: #F27420; background: #fff; box-shadow: 0 0 0 4px rgba(242,116,32,0.08); }
.filter-divider { width: 1px; height: 44px; background: linear-gradient(to bottom, transparent, #e2e8f0, transparent); align-self: flex-end; }
.filter-dash { color: #cbd5e1; font-size: 16px; font-weight: 600; align-self: flex-end; padding-bottom: 10px; }

.btn-search {
	height: 44px; padding: 0 28px; border-radius: 12px; border: none;
	background: #F27420; color: #fff; font-size: 13.5px; font-weight: 700; cursor: pointer;
	transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(242,116,32,0.3);
	display: inline-flex; align-items: center; gap: 8px;
}
.btn-search:hover { background: #e0600e; }
.btn-search:focus, .btn-search:active:focus { outline: none !important; }
.btn-reset {
	height: 44px; padding: 0 22px; border-radius: 12px;
	border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
	font-size: 13.5px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
}
.btn-reset:hover { border-color: #F27420; color: #F27420; background: #FFF8F3; }
.btn-reset:focus, .btn-reset:active:focus { outline: none !important; }

/* Select2 */
.select2-container--default .select2-selection--single {
	height: 44px !important; border-radius: 12px !important;
	border: 1.5px solid #e2e8f0 !important; background: #f8fafc !important;
	display: flex !important; align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: normal !important; font-size: 13.5px; font-weight: 600; color: #1e293b; padding-left: 14px; flex: 1;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
	position: static !important; height: auto !important; display: flex; align-items: center; padding-right: 8px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
	border-color: #94a3b8 transparent transparent transparent !important; margin-top: 0 !important; position: static !important;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder { color: #94a3b8 !important; font-weight: 500; }
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
	border-color: #F27420 !important; background: #fff !important;
	box-shadow: 0 0 0 4px rgba(242,116,32,0.08) !important;
}
.select2-dropdown { border-radius: 12px !important; border: 1.5px solid #e2e8f0 !important; box-shadow: 0 12px 36px rgba(0,0,0,0.1) !important; overflow: hidden; margin-top: 4px !important; }
.select2-container--default .select2-results__option { padding: 10px 14px; font-size: 13px; font-weight: 500; }
.select2-container--default .select2-results__option--highlighted[aria-selected],
.select2-container--default .select2-results__option--highlighted { background: #F27420 !important; color: #fff !important; }
.select2-container--default .select2-search--dropdown .select2-search__field {
	border: 1.5px solid #e2e8f0 !important; border-radius: 8px !important; padding: 8px 12px; font-size: 13px; outline: none !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus {
	border-color: #F27420 !important; box-shadow: 0 0 0 3px rgba(242,116,32,0.08) !important; outline: none !important;
}

/* Flatpickr */
.flatpickr-calendar { border-radius: 16px !important; border: 1px solid #e8ecf2 !important; box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important; font-family: 'Inter', sans-serif !important; overflow: hidden; }
.flatpickr-months { background: #FFF8F3; padding: 4px 0; }
.flatpickr-current-month { font-size: 14px !important; font-weight: 700 !important; color: #1e293b !important; }
.flatpickr-months .flatpickr-prev-month svg, .flatpickr-months .flatpickr-next-month svg { fill: #F27420 !important; }
.flatpickr-weekdays { background: #FFF8F3; }
span.flatpickr-weekday { color: #F27420 !important; font-size: 11px !important; font-weight: 700 !important; }
.flatpickr-day { border-radius: 10px !important; font-weight: 500 !important; font-size: 13px !important; color: #334155; border: none !important; }
.flatpickr-day:hover { background: #FFF5ED !important; color: #F27420 !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: #F27420 !important; color: #fff !important; box-shadow: 0 2px 8px rgba(242,116,32,0.35) !important; }
.flatpickr-day.today { border: 2px solid #F27420 !important; color: #F27420 !important; font-weight: 700 !important; }
.flatpickr-day.today.selected { color: #fff !important; }

/* Empty State */
.empty-state {
	background: #fff; border-radius: 20px; border: 1px solid #e8ecf2;
	box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04);
	padding: 64px 40px; text-align: center;
}
.empty-icon {
	width: 72px; height: 72px; border-radius: 50%;
	background: linear-gradient(135deg, #FFF5ED 0%, #FEE8D6 100%);
	display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;
}
.empty-icon svg { width: 32px; height: 32px; color: #F27420; }
.empty-title { font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.empty-desc { font-size: 14px; color: #94a3b8; font-weight: 500; }
.empty-desc b { color: #F27420; font-weight: 700; }

/* Balance */
.balance-chip {
	display: inline-flex; align-items: center; gap: 8px;
	padding: 10px 20px; border-radius: 14px; font-size: 14px; font-weight: 700;
}
.balance-chip.owed { background: linear-gradient(135deg, #FEF2F2, #FEE2E2); color: #DC2626; border: 1px solid #FECACA; }
.balance-chip.advance { background: linear-gradient(135deg, #F0FDF4, #DCFCE7); color: #16A34A; border: 1px solid #BBF7D0; }
.balance-dot { width: 8px; height: 8px; border-radius: 50%; }
.balance-chip.owed .balance-dot { background: #DC2626; }
.balance-chip.advance .balance-dot { background: #16A34A; }

/* Table */
.table-card {
	background: #fff; border-radius: 20px; border: 1px solid #e8ecf2;
	box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04); overflow: hidden;
}
.table-card-header {
	padding: 18px 24px; border-bottom: 1px solid #f1f5f9;
	display: flex; align-items: center; justify-content: space-between;
}
.stmt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.stmt-table thead th {
	padding: 13px 16px; background: #fafbfc; color: #64748b; font-size: 10.5px; font-weight: 700;
	text-transform: uppercase; letter-spacing: 0.6px; border-bottom: 2px solid #eef2f7; white-space: nowrap; text-align: left;
}
.stmt-table tbody td {
	padding: 13px 16px; border-bottom: 1px solid #f3f4f8; color: #334155; font-weight: 500; vertical-align: middle;
}
.stmt-table tbody tr { transition: all 0.12s; }
.stmt-table tbody tr:hover { background: #FEFAF6; }
.stmt-table tbody tr:last-child td { border-bottom: none; }

.row-num {
	width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9;
	display: inline-flex; align-items: center; justify-content: center;
	font-size: 11px; font-weight: 700; color: #64748b;
}
.tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; }
.tag-on-account { background: #FEF3C7; color: #92400E; }
.tag-refund { background: #FEE2E2; color: #991B1B; }
.tag-discount { background: #DBEAFE; color: #1E40AF; }
.tag-paid { background: #F0FDF4; color: #16A34A; }
.tag-dot { width: 6px; height: 6px; border-radius: 50%; }
.tag-on-account .tag-dot { background: #92400E; }
.tag-refund .tag-dot { background: #991B1B; }
.tag-discount .tag-dot { background: #1E40AF; }
.rb-positive { color: #DC2626; font-weight: 700; }
.rb-negative { color: #16A34A; font-weight: 700; }
.amt { font-variant-numeric: tabular-nums; }
.amt-bold { font-weight: 700; color: #1e293b; }

.tl-btn {
	background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
	padding: 6px 14px; font-size: 12px; font-weight: 600; color: #475569;
	cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
}
.tl-btn:hover { border-color: #F27420; color: #F27420; background: #FFF8F3; }
.tl-btn:focus, .tl-btn:active:focus { outline: none !important; box-shadow: none !important; }
.tl-btn svg { width: 14px; height: 14px; }
.dropdown-menu.tl-dropdown {
	padding: 16px !important; min-width: 320px !important; border-radius: 14px !important;
	border: 1px solid #e8ecf2 !important; box-shadow: 0 12px 36px rgba(0,0,0,0.12) !important;
	white-space: normal !important; width: auto !important;
}
.tl-list { padding: 0; margin: 0; list-style: none; }
.tl-item { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; font-weight: 500; color: #475569; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tl-item:last-child { border-bottom: none; }
.tl-amount { font-weight: 700; color: #1e293b; }
.tl-date { color: #94a3b8; font-size: 11px; }

@media (max-width: 768px) {
	.stmt-wrap { padding: 16px 12px; }
	.filter-row { gap: 12px; }
	.filter-card { padding: 18px; }
	.filter-divider { display: none; }
}
</style>
@endpush

@section('content')
<div class="stmt-wrap">
	<div class="stmt-header">
		<div>
			<h1 class="stmt-title">Supplier <span>Statements</span></h1>
			<p class="stmt-subtitle">View supplier payment history and running balances</p>
		</div>
	</div>

	<form action="" method="get">
	<div class="filter-card">
		<div class="filter-row">
			<div class="filter-group grow">
				<span class="filter-label">Supplier</span>
				<select class="select2" name="supplier" style="width:100%;">
					<option value="">-- Select --</option>
					@foreach($suppliers as $c)
						<option {{ $supplier == $c->id ? "selected" : "" }} value="{{ $c->id }}">{{ $c->name }}</option>
					@endforeach
				</select>
			</div>
			<div class="filter-divider"></div>
			<div class="filter-group">
				<span class="filter-label">From</span>
				<input type="text" name="start_date" id="start_date" value="{{ $start_date }}" class="filter-input" placeholder="Select date" readonly />
			</div>
			<span class="filter-dash">&mdash;</span>
			<div class="filter-group">
				<span class="filter-label">To</span>
				<input type="text" name="end_date" id="end_date" value="{{ $end_date }}" class="filter-input" placeholder="Select date" readonly />
			</div>
			<div class="filter-divider"></div>
			<div class="filter-group" style="flex-direction:row;gap:10px;align-items:flex-end;">
				<button type="submit" class="btn-search">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					Search
				</button>
				<button type="button" class="btn-reset" onclick="window.location.href=window.location.pathname">Reset</button>
			</div>
		</div>
	</div>
	</form>

	@if($error == 1)
		<div class="empty-state">
			<div class="empty-icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
			</div>
			<div class="empty-title">No statement to display</div>
			<div class="empty-desc">Select a <b>Supplier</b>, <b>Start Date</b> and <b>End Date</b> to view statements.</div>
		</div>
	@else
		<div style="margin-bottom:20px;">
			@if($balance >= 0)
				<div class="balance-chip owed"><span class="balance-dot"></span> Past Balance: {{ number_format($balance, 2) }} / Owed</div>
			@else
				<div class="balance-chip advance"><span class="balance-dot"></span> Past Balance: {{ number_format(abs($balance), 2) }} / Advance</div>
			@endif
		</div>

		<div class="table-card">
			<div class="table-card-header">
				<span style="font-size:15px;font-weight:700;color:#1e293b;">Transaction History</span>
			</div>
			<div style="overflow-x:auto;">
				<table class="stmt-table">
					<thead>
						<tr>
							<th>#</th><th>Date</th><th>Invoice</th><th>Credit Inv.</th><th>Cash Inv.</th>
							<th>Paid</th><th>Refund / Adj.</th><th>Discount / Adj.</th><th>Note</th>
							<th>Balance</th><th>Running Bal.</th><th style="text-align:right;">History</th>
						</tr>
					</thead>
					<tbody>
						@php $calc_balance = $balance; $i = 0; @endphp
						@foreach($payments as $p)
							@if(empty($p->child_payments_sum_amount))
								@php $calc_balance += $p->debt; @endphp
								<tr>
									<td><span class="row-num">{{ $i + 1 }}</span></td>
									<td style="white-space:nowrap;">{{ $p->created_at }}</td>
									<td>
										@if($p->is_credited == 1)
											<span class="tag tag-on-account"><span class="tag-dot"></span> On Account</span>
										@elseif($p->is_refunded == 1)
											<span class="tag tag-refund"><span class="tag-dot"></span> Refund</span>
										@elseif($p->is_discounted == 1)
											<span class="tag tag-discount"><span class="tag-dot"></span> Discount</span>
										@else
											<span class="amt-bold">{{ isset($p->group_key) ? str_replace('inv_', '', $p->group_key) : str_replace('inv_', '', $p->id) }}</span>
										@endif
									</td>
									<td class="amt">{{ $p->order_start_sum_sub_total }}</td>
									<td class="amt"></td>
									<td class="amt">{{ $p->credit }}</td>
									<td class="amt"></td>
									<td class="amt"></td>
									<td><span style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#94a3b8;font-style:italic;display:block;" title="{{ $p->note }}">{{ $p->note }}</span></td>
									<td class="amt amt-bold">{{ $p->debt }}</td>
									<td class="{{ $calc_balance >= 0 ? 'rb-positive' : 'rb-negative' }}">{{ number_format($calc_balance, 2) }}</td>
									<td style="text-align:right;">
										<div class="btn-group dropleft">
											<button type="button" class="tl-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
												<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
												Timeline
											</button>
											<div class="dropdown-menu tl-dropdown">
												<ul class="tl-list">
													@if($p->is_credited == 1)
														<li class="tl-item"><span class="tl-amount">{{ $p->total_amount }}</span> <span class="tag tag-on-account" style="font-size:10px;padding:2px 8px;">Credit</span></li>
													@elseif($p->is_refunded == 1)
														<li class="tl-item"><span class="tl-amount">{{ $p->total_amount }}</span> <span class="tag tag-refund" style="font-size:10px;padding:2px 8px;">Refund</span></li>
													@elseif($p->is_discounted == 1)
														<li class="tl-item"><span class="tl-amount">{{ $p->total_amount }}</span> <span class="tag tag-discount" style="font-size:10px;padding:2px 8px;">Discount</span></li>
													@endif
													@foreach($p->payments as $p2)
														<li class="tl-item">
															<span class="tl-amount">{{ $p2->amount }}</span>
															<span class="tl-date">{{ $p2->created_at_full }}</span>
															@if($p2->is_discounted == 1) <span class="tag tag-discount" style="font-size:10px;padding:2px 8px;">Discounted</span> @endif
															@if($p2->is_refunded == 1) <span class="tag tag-refund" style="font-size:10px;padding:2px 8px;">Refunded</span> @endif
															@if($p2->is_credited == 1)
																<span class="tag tag-on-account" style="font-size:10px;padding:2px 8px;">Credited</span>
															@else
																<span class="tag tag-paid" style="font-size:10px;padding:2px 8px;">Paid</span>
															@endif
															@if($p2->supplier_payment_id > 0)
																<span style="color:#64748b;font-size:10.5px;font-style:italic;">On Account({{ $p2->supplier_payment_id }})</span>
															@endif
														</li>
													@endforeach
												</ul>
											</div>
										</div>
									</td>
								</tr>
								@php $i++; @endphp
							@endif
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	@endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var cfg = { dateFormat: "Y-m-d", altInput: true, altFormat: "d M Y", disableMobile: true, animate: true };
	flatpickr("#start_date", cfg);
	flatpickr("#end_date", cfg);
});
</script>
@endpush

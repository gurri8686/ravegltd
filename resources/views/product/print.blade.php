<?php
$companyDetails = \App\Models\CompanyDetailModel::info();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }} - Products</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f3f4f6; color: #0f1115; -webkit-font-smoothing: antialiased; line-height: 1.5; }
		.page { max-width: 1280px; margin: 24px auto; background: #ffffff; border-radius: 16px; box-shadow: 0 1px 3px rgba(15,17,21,0.04); overflow: hidden; }
		.header { display: flex; justify-content: space-between; align-items: flex-start; padding: 32px 40px 28px; }
		.brand-name { font-size: 30px; font-weight: 800; color: #f97316; letter-spacing: -0.5px; line-height: 1.1; }
		.brand-tagline { margin-top: 4px; font-size: 11px; font-weight: 600; color: #9ca3af; letter-spacing: 1.5px; text-transform: uppercase; }
		.meta-block { display: flex; gap: 56px; text-align: right; }
		.meta-item .meta-label { font-size: 11px; font-weight: 800; color: #f97316; letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 6px; }
		.meta-item .meta-value { font-size: 14px; font-weight: 400; color: #0f1115; }
		.contact-bar { background: #f3f4f6; padding: 20px 40px; display: flex; justify-content: space-between; align-items: flex-start; font-size: 13px; color: #4b5563; }
		.contact-left p, .contact-right p { margin: 2px 0; line-height: 1.6; }
		.contact-left strong { font-weight: 700; color: #0f1115; }
		.contact-left a { color: #f97316; text-decoration: none; font-weight: 500; }
		.contact-right { text-align: right; }
		.contact-right .report-title { font-size: 17px; font-weight: 800; color: #0f1115; margin-bottom: 4px; }
		.contact-right .report-sub { font-size: 12px; color: #6b7280; }
		.content { padding: 28px 40px 32px; }
		.data-table { width: 100%; border-collapse: collapse; }
		.data-table thead th { font-size: 13px; font-weight: 700; color: #f97316; text-align: left; padding: 12px 14px; border-bottom: 1.5px solid #fde6d3; white-space: nowrap; }
		.data-table thead th.right { text-align: right; }
		.data-table thead th.center { text-align: center; }
		.data-table tbody td { padding: 14px; font-size: 13.5px; color: #0f1115; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
		.data-table tbody tr:last-child td { border-bottom: none; }
		.data-table .right { text-align: right; }
		.data-table .center { text-align: center; }
		.data-table .row-num { color: #9ca3af; font-weight: 500; }
		.data-table .row-name { font-weight: 600; color: #0f1115; }
		.badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; display: inline-block; letter-spacing: 0.2px; border: 1px solid; }
		.badge-ok { background: #ecfdf5; color: #15803d; border-color: #bde5c9; }
		.badge-off { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
		.notes-section { margin: 24px 40px 32px; padding: 20px 24px; background: #fffaf5; border: 1px solid #fde6d3; border-left: 4px solid #f97316; border-radius: 12px; }
		.notes-section .notes-label { color: #f97316; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; }
		.notes-section .notes-text { color: #4b5563; font-size: 13px; line-height: 1.6; font-style: italic; }
		.loading-state { padding: 80px 40px; text-align: center; color: #94a3b8; font-size: 14px; }
		@media print {
			body { background: #ffffff; }
			.page { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
			@page { margin: 16mm 12mm; }
		}
	</style>
</head>
<body>
	<div class="page">
		<div class="header">
			<div>
				<div class="brand-name">{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }}</div>
				<div class="brand-tagline">Fruit and Veg is Power</div>
			</div>
			<div class="meta-block">
				<div class="meta-item">
					<div class="meta-label">Report Date</div>
					<div class="meta-value" id="report-date">—</div>
				</div>
				<div class="meta-item">
					<div class="meta-label">Generated</div>
					<div class="meta-value" id="report-time">—</div>
				</div>
			</div>
		</div>
		<div class="contact-bar">
			<div class="contact-left">
				<p>Phone: <strong>{{ $companyDetails->telephone ?: '—' }}</strong></p>
				<p>VAT: <strong>{{ $companyDetails->vat_no ?: '—' }}</strong></p>
				<p>Email: <a href="mailto:{{ $companyDetails->email ?? '' }}">{{ $companyDetails->email ?: '—' }}</a></p>
				@if(!empty($companyDetails->address1))
				<p>{{ $companyDetails->address1 }}{{ !empty($companyDetails->zipcode) ? ', ' . $companyDetails->zipcode : '' }}</p>
				@endif
			</div>
			<div class="contact-right">
				<div class="report-title">Products</div>
				<div class="report-sub">Product catalog listing</div>
			</div>
		</div>
		<div class="loading-state" id="loading">Loading data...</div>
		<div id="content" style="display:none">
			<div class="content">
				<table class="data-table">
					<thead>
						<tr>
							<th style="width:50px">#</th>
							<th>Name</th>
							<th class="right">Selling Price</th>
							<th class="right">Weight / Unit</th>
							<th class="right">Tax / VAT</th>
							<th>Date</th>
							<th class="center">Status</th>
							<th>Profit / Loss</th>
						</tr>
					</thead>
					<tbody id="table-body"></tbody>
				</table>
			</div>
			<div class="notes-section">
				<div class="notes-label">Notes</div>
				<div class="notes-text" id="footer-text">Product catalog as of generation time.</div>
			</div>
		</div>
	</div>

<script>
(async function(){
	const params = new URLSearchParams(window.location.search);
	const status = params.get('status') || 'all';
	const search = (params.get('search') || '').toLowerCase().trim();
	const currency = '{{env('CURRENCY_SYMBOL', '£')}}';
	const now = new Date();
	document.getElementById('report-date').textContent = now.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
	document.getElementById('report-time').textContent = now.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
	document.title = 'Products — ' + now.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});

	try {
		const res = await fetch('{{ route("management.products.view.list") }}', {
			method:'GET',
			headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
			credentials:'same-origin'
		});
		const json = await res.json();
		let products = json.payload || [];

		// Fetch profit/loss data (same as UI does)
		const plMap = {};
		try {
			const plRes = await fetch('{{ route("product_profit_loss.view.profit_loss") }}', {
				method:'POST',
				headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
				credentials:'same-origin',
				body: JSON.stringify({ product_id: [], start_date: '2000-01-01', end_date: new Date().toISOString().slice(0,10) })
			});
			const plJson = await plRes.json();
			(plJson.payload || []).forEach(function(pl){
				if (!pl.id) return;
				const qty = Number(pl.stock_quantity || 0);
				const avg = Number(pl.avg_profit || 0);
				plMap[pl.id] = {
					value: Math.round(qty * avg),
					qty: qty,
					pct: Number(pl.avg_profit_percentage || 0),
				};
			});
		} catch(e) {}

		products = products.filter(p => {
			if (status === 'active' && p.is_active != 1) return false;
			if (status === 'inactive' && p.is_active == 1) return false;
			if (search) {
				const hay = ((p.name||'') + ' ' + (p.selling_price||'') + ' ' + (p.unit_weight||'') + ' ' + (p.tax_vat||'')).toLowerCase();
				if (hay.indexOf(search) === -1) return false;
			}
			return true;
		});

		let tbody = '';
		products.forEach(function(p, i){
			const sp = (p.selling_price !== null && p.selling_price !== '') ? (currency + ' ' + p.selling_price) : '';
			const dt = p.created_at ? new Date(p.created_at).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '';
			const isActive = p.is_active == 1;
			const pl = plMap[p.id];
			let plCell = '';
			if (pl && (pl.value !== 0 || pl.qty > 0)) {
				const isProfit = pl.value >= 0;
				const arrow = isProfit ? '▲' : '▼';
				const lbl = isProfit ? 'Profit' : 'Loss';
				const color = isProfit ? '#15803d' : '#dc2626';
				plCell = '<div style="display:flex;flex-direction:column;gap:2px;">' +
					'<span style="font-weight:700;font-size:12.5px;color:'+color+';">'+arrow+' '+lbl+' '+currency+' '+Math.abs(pl.value).toLocaleString()+'</span>' +
					'<span style="font-size:10.5px;color:#94a3b8;font-weight:500;">'+pl.qty+' sold · '+(pl.pct>=0?'+':'')+pl.pct+'% margin</span>' +
					'</div>';
			}
			tbody += '<tr>'+
				'<td class="row-num">'+(i+1)+'</td>'+
				'<td class="row-name">'+(p.name||'')+'</td>'+
				'<td class="right">'+sp+'</td>'+
				'<td class="right">'+(p.unit_weight!=null?p.unit_weight:'')+'</td>'+
				'<td class="right">'+(p.tax_vat!=null?p.tax_vat:'')+'</td>'+
				'<td>'+dt+'</td>'+
				'<td class="center"><span class="badge '+(isActive?'badge-ok':'badge-off')+'">'+(isActive?'Active':'Inactive')+'</span></td>'+
				'<td>'+plCell+'</td>'+
			'</tr>';
		});
		document.getElementById('table-body').innerHTML = tbody || '<tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8">No records found</td></tr>';
		document.getElementById('footer-text').textContent = 'Generated on ' + now.toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) + ' · ' + products.length + ' product' + (products.length === 1 ? '' : 's') + ' shown.';
		document.getElementById('loading').style.display = 'none';
		document.getElementById('content').style.display = 'block';
		setTimeout(function(){ window.print(); }, 250);
	} catch(e) {
		document.getElementById('loading').innerHTML = '<div style="color:#ef4444;font-weight:600;">Failed to load data</div><div style="color:#94a3b8;font-size:12px;margin-top:8px;">'+e.message+'</div>';
	}
})();
</script>
</body>
</html>

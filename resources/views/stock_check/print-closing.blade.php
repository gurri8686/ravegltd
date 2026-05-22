<?php
$companyDetails = \App\Models\CompanyDetailModel::info();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }} - Stock Closing</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body { font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #f3f4f6; color: #0f1115; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; line-height: 1.5; }
		.page { max-width: 1280px; margin: 24px auto; background: #ffffff; border-radius: 16px; box-shadow: 0 1px 3px rgba(15, 17, 21, 0.04), 0 1px 2px rgba(15, 17, 21, 0.03); overflow: hidden; }
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
		.data-table .row-product { font-weight: 600; color: #0f1115; }
		.data-table .stock-val { font-weight: 700; color: #0f1115; }
		.data-table .cl-val { font-weight: 600; color: #2563eb; }
		.data-table .cl-empty { color: #d1d5db; font-style: italic; }
		.notes-section { margin: 24px 40px 32px; padding: 20px 24px; background: #fffaf5; border: 1px solid #fde6d3; border-left: 4px solid #f97316; border-radius: 12px; }
		.notes-section .notes-label { color: #f97316; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 8px; }
		.notes-section .notes-text { color: #4b5563; font-size: 13px; line-height: 1.6; font-style: italic; }
		.loading-state { padding: 80px 40px; text-align: center; color: #94a3b8; font-size: 14px; }
		@media print {
			body { background: #ffffff; }
			.page { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
			.no-print { display: none !important; }
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
				<div class="report-title" id="report-title">Stock Closing</div>
				<div class="report-sub">Product-wise closing stock and system stock comparison</div>
			</div>
		</div>
		<div class="loading-state" id="loading">Loading data...</div>
		<div id="content" style="display:none">
			<div class="content">
				<table class="data-table">
					<thead>
						<tr>
							<th style="width:50px">#</th>
							<th>Product</th>
							<th>Remark</th>
							<th class="right">System Stock</th>
							<th class="right">Closing Stock</th>
						</tr>
					</thead>
					<tbody id="table-body"></tbody>
				</table>
			</div>
			<div class="notes-section">
				<div class="notes-label">Notes</div>
				<div class="notes-text" id="footer-text">Records reflect the latest stock state.</div>
			</div>
		</div>
	</div>

<script>
(async function(){
	const params = new URLSearchParams(window.location.search);
	const date = params.get('date') || new Date().toISOString().slice(0,10);
	const stock = params.get('stock') || 'in-stock';
	const tab = params.get('tab') || 'unreviewed';
	const dateFormatted = new Date(date+'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
	document.getElementById('report-date').textContent = dateFormatted;
	document.getElementById('report-time').textContent = new Date().toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
	document.getElementById('report-title').textContent = 'Stock Closing — ' + (tab === 'reviewed' ? 'Reviewed' : 'Unreviewed');
	document.title = 'Stock Closing Report — ' + dateFormatted;

	try {
		const res = await fetch('{{ route("stock_closing.view.products") }}', {
			method:'POST',
			headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
			credentials:'same-origin',
			body: JSON.stringify({date})
		});
		const json = await res.json();
		let products = json.payload || [];

		products = products.filter(p => {
			const system = Number(p.system_stock || 0);
			const reviewed = !!(p.stock_closing && p.stock_closing.is_reviewed);

			if (stock === 'in-stock' && system <= 0) return false;
			if (stock === 'out-of-stock' && system > 0) return false;

			if (tab === 'reviewed' && !reviewed) return false;
			if (tab === 'unreviewed' && reviewed) return false;

			return true;
		});

		let tbody = '';
		products.forEach(function(p, i){
			const totalIn = Number(p.total_in || 0);
			const totalOut = Number(p.total_out || 0);
			const system = Number(p.system_stock || 0);
			const recorded = (p.stock_closing && p.stock_closing.stock) ? Number(p.stock_closing.stock) : '';
			const remark = (p.stock_closing && p.stock_closing.remark) ? p.stock_closing.remark : '';
			const productLabel = (p.name || '—') + ' <span style="font-size:11px;color:#9ca3af;font-weight:500;">(In:' + totalIn + ' Out:' + totalOut + ')</span>';
			tbody += '<tr>'+
				'<td class="row-num">'+(i+1)+'</td>'+
				'<td class="row-product">'+productLabel+'</td>'+
				'<td style="color:#94a3b8;font-style:italic;font-size:12px;">'+(remark || '')+'</td>'+
				'<td class="right stock-val">'+system+'</td>'+
				'<td class="right '+(recorded !== '' ? 'cl-val' : 'cl-empty')+'">'+(recorded !== '' ? recorded : '')+'</td>'+
			'</tr>';
		});
		document.getElementById('table-body').innerHTML = tbody || '<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">No records found</td></tr>';
		document.getElementById('footer-text').textContent = 'Generated on ' + new Date().toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) + ' · ' + products.length + ' product' + (products.length === 1 ? '' : 's') + ' shown.';
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

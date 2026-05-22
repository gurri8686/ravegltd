<?php
$companyDetails = \App\Models\CompanyDetailModel::info();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }} - Stock Check Report</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: #f3f4f6;
			color: #0f1115;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			line-height: 1.5;
		}
		.page {
			max-width: 1280px;
			margin: 24px auto;
			background: #ffffff;
			border-radius: 16px;
			box-shadow: 0 1px 3px rgba(15, 17, 21, 0.04), 0 1px 2px rgba(15, 17, 21, 0.03);
			overflow: hidden;
		}

		/* HEADER */
		.header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			padding: 32px 40px 28px;
		}
		.brand-name {
			font-size: 30px;
			font-weight: 800;
			color: #f97316;
			letter-spacing: -0.5px;
			line-height: 1.1;
		}
		.brand-tagline {
			margin-top: 4px;
			font-size: 11px;
			font-weight: 600;
			color: #9ca3af;
			letter-spacing: 1.5px;
			text-transform: uppercase;
		}
		.meta-block {
			display: flex;
			gap: 56px;
			text-align: right;
		}
		.meta-item .meta-label {
			font-size: 11px;
			font-weight: 800;
			color: #f97316;
			letter-spacing: 1.2px;
			text-transform: uppercase;
			margin-bottom: 6px;
		}
		.meta-item .meta-value {
			font-size: 14px;
			font-weight: 400;
			color: #0f1115;
		}

		/* CONTACT BAR */
		.contact-bar {
			background: #f3f4f6;
			padding: 20px 40px;
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			font-size: 13px;
			color: #4b5563;
		}
		.contact-left p, .contact-right p { margin: 2px 0; line-height: 1.6; }
		.contact-left strong { font-weight: 700; color: #0f1115; }
		.contact-left a { color: #f97316; text-decoration: none; font-weight: 500; }
		.contact-right { text-align: right; }
		.contact-right .report-title {
			font-size: 17px;
			font-weight: 800;
			color: #0f1115;
			margin-bottom: 4px;
		}
		.contact-right .report-sub {
			font-size: 12px;
			color: #6b7280;
		}

		/* CONTENT */
		.content { padding: 28px 40px 32px; }

		/* TABLE */
		.data-table {
			width: 100%;
			border-collapse: collapse;
		}
		.data-table thead th {
			font-size: 13px;
			font-weight: 700;
			color: #f97316;
			text-align: left;
			padding: 12px 14px;
			border-bottom: 1.5px solid #fde6d3;
			white-space: nowrap;
		}
		.data-table thead th.num { width: 50px; }
		.data-table thead th.right { text-align: right; }
		.data-table thead th.center { text-align: center; }
		.data-table tbody td {
			padding: 14px;
			font-size: 13.5px;
			color: #0f1115;
			border-bottom: 1px solid #f3f4f6;
			vertical-align: middle;
		}
		.data-table tbody tr:last-child td { border-bottom: none; }
		.data-table .row-num { color: #9ca3af; font-weight: 500; }
		.data-table .row-product { font-weight: 600; color: #0f1115; }
		.data-table .right { text-align: right; }
		.data-table .center { text-align: center; }
		.data-table .val-zero { color: #d1d5db; }
		.data-table .stock-val { font-weight: 700; color: #0f1115; }
		.data-table .cl-val { font-weight: 600; color: #2563eb; }
		.data-table .cl-empty { color: #d1d5db; font-style: italic; }

		/* Badges */
		.badge {
			padding: 4px 10px;
			border-radius: 999px;
			font-size: 11px;
			font-weight: 700;
			white-space: nowrap;
			display: inline-block;
			letter-spacing: 0.2px;
			border: 1px solid;
		}
		.badge-ok     { background: #ecfdf5; color: #15803d; border-color: #bde5c9; }
		.badge-excess { background: #fffbeb; color: #b45309; border-color: #fde68a; }
		.badge-short  { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

		/* NOTES */
		.notes-section {
			margin: 24px 40px 32px;
			padding: 20px 24px;
			background: #fffaf5;
			border: 1px solid #fde6d3;
			border-left: 4px solid #f97316;
			border-radius: 12px;
		}
		.notes-section .notes-label {
			color: #f97316;
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 1.2px;
			margin-bottom: 8px;
		}
		.notes-section .notes-text {
			color: #4b5563;
			font-size: 13px;
			line-height: 1.6;
			font-style: italic;
		}

		.loading-state {
			padding: 80px 40px; text-align: center;
			color: #94a3b8; font-size: 14px;
		}

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
		<!-- HEADER -->
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

		<!-- CONTACT BAR -->
		<div class="contact-bar">
			<div class="contact-left">
				<p>Phone: <strong>{{ $companyDetails->telephone ?: '—' }}</strong></p>
				<p>VAT: <strong>{{ $companyDetails->vat_no ?: '23456789' }}</strong></p>
				<p>Email: <a href="mailto:{{ $companyDetails->email ?? '' }}">{{ $companyDetails->email ?: '—' }}</a></p>
				@if(!empty($companyDetails->address1))
				<p>{{ $companyDetails->address1 }}{{ !empty($companyDetails->zipcode) ? ', ' . $companyDetails->zipcode : '' }}</p>
				@endif
			</div>
			<div class="contact-right">
				<div class="report-title">Stock Check Report</div>
				<div class="report-sub">Stock movement, opening, closing &amp; reconciliation</div>
			</div>
		</div>

		<!-- LOADING -->
		<div class="loading-state" id="loading">Loading stock data...</div>

		<!-- CONTENT -->
		<div id="content" style="display:none">
			<div class="content">
				<table class="data-table">
					<thead>
						<tr>
							<th class="num">#</th>
							<th>Product</th>
							<th class="right">O.S</th>
							<th class="right">N.S</th>
							<th class="right">Sales</th>
							<th class="right">C.Rtn</th>
							<th class="right">Dumps</th>
							<th class="right">S.Rtn</th>
							<th class="right">Stock</th>
							<th class="right">Cl. Stock</th>
							<th class="center">Result</th>
						</tr>
					</thead>
					<tbody id="table-body"></tbody>
				</table>
			</div>

			<!-- NOTES -->
			<div class="notes-section">
				<div class="notes-label">Notes</div>
				<div class="notes-text" id="footer-text">This report reflects stock movements as of the date shown. Please review and contact us for any discrepancies.</div>
			</div>
		</div>
	</div>

<script>
(async function(){
	const params = new URLSearchParams(window.location.search);
	const date = params.get('date') || new Date().toISOString().slice(0,10);
	const stock = params.get('stock') || 'in-stock';
	const dateFormatted = new Date(date+'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
	document.getElementById('report-date').textContent = dateFormatted;
	document.getElementById('report-time').textContent = new Date().toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
	document.title = 'Stock Check Report — ' + dateFormatted;

	try {
		const res = await fetch('{{ route("stock_check.view.list") }}', {
			method:'POST',
			headers:{
				'Content-Type':'application/json',
				'X-CSRF-TOKEN':'{{ csrf_token() }}',
				'Accept':'application/json',
				'X-Requested-With':'XMLHttpRequest'
			},
			credentials:'same-origin',
			body: JSON.stringify({date:date, to_date:date, mode:'show-all'})
		});
		if(!res.ok) throw new Error('HTTP ' + res.status);
		const json = await res.json();
		if(!json.success) throw new Error(json.message || 'Failed');
		let products = json.payload || [];

		products = products.filter(p => {
			const os = parseInt(p.os)||0;
			const ns = (p.ns||[]).reduce((a,b)=>a+b,0);
			const sales = (p.sales||[]).reduce((a,b)=>a+b,0);
			const crtn = (p.crtn||[]).reduce((a,b)=>a+b,0);
			const srtn = (p.srtn||[]).reduce((a,b)=>a+b,0);
			const dmps = (p.dmps||[]).reduce((a,b)=>a+b,0);
			p._expected = os + ns - sales + crtn - srtn - dmps;
			const hasCl = p.cl_stock !== null && p.cl_stock !== undefined && p.cl_stock !== '' && p.cl_stock !== 0 && p.cl_stock !== '0';
			p._diff = hasCl ? Number(p.cl_stock) - p._expected : 0;
			p._hasCl = hasCl;
			// Match UI table filter: skip only when product has zero opening AND zero activity
			if (os <= 0 && ns <= 0 && sales <= 0 && crtn <= 0 && srtn <= 0 && dmps <= 0) return false;
			return true;
		});

		const total = products.length;

		const vc = function(v){ return Number(v) > 0 ? '' : 'val-zero'; };
		const fv = function(v){ return Number(v) > 0 ? v : '0'; };
		let tbody = '';
		products.forEach(function(p, i) {
			const os = parseInt(p.os)||0;
			const ns = (p.ns||[]).reduce(function(a,b){return a+b;},0);
			const sales = (p.sales||[]).reduce(function(a,b){return a+b;},0);
			const crtn = (p.crtn||[]).reduce(function(a,b){return a+b;},0);
			const dmps = (p.dmps||[]).reduce(function(a,b){return a+b;},0);
			const srtn = (p.srtn||[]).reduce(function(a,b){return a+b;},0);
			let resultText, badgeClass;
			if(!p._hasCl){ resultText = ''; badgeClass = ''; }
			else if(p._diff===0){ resultText = 'OK'; badgeClass = 'badge badge-ok'; }
			else if(p._diff>0){ resultText = '+'+p._diff+' Excess'; badgeClass = 'badge badge-excess'; }
			else { resultText = Math.abs(p._diff)+' Short'; badgeClass = 'badge badge-short'; }
			tbody += '<tr>'+
				'<td class="row-num">'+(i+1)+'</td>'+
				'<td class="row-product">'+p.product_name+'</td>'+
				'<td class="right '+vc(os)+'">'+fv(os)+'</td>'+
				'<td class="right '+vc(ns)+'">'+fv(ns)+'</td>'+
				'<td class="right '+vc(sales)+'">'+fv(sales)+'</td>'+
				'<td class="right '+vc(crtn)+'">'+fv(crtn)+'</td>'+
				'<td class="right '+vc(dmps)+'">'+fv(dmps)+'</td>'+
				'<td class="right '+vc(srtn)+'">'+fv(srtn)+'</td>'+
				'<td class="right stock-val">'+p._expected+'</td>'+
				'<td class="right '+(p._hasCl?'cl-val':'cl-empty')+'">'+(p._hasCl?Number(p.cl_stock):'')+'</td>'+
				'<td class="center">'+(badgeClass?'<span class="'+badgeClass+'">'+resultText+'</span>':resultText)+'</td>'+
			'</tr>';
		});
		document.getElementById('table-body').innerHTML = tbody;
		document.getElementById('footer-text').textContent = 'Generated on ' + new Date().toLocaleString('en-GB',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) + ' · ' + total + ' product' + (total === 1 ? '' : 's') + ' shown. Please review and contact us for any discrepancies.';
		document.getElementById('loading').style.display = 'none';
		document.getElementById('content').style.display = 'block';
		// Auto-open system print dialog after data fully rendered
		setTimeout(function(){ window.print(); }, 250);
	} catch(e) {
		document.getElementById('loading').innerHTML = '<div style="color:#ef4444;font-weight:600;">Failed to load data</div><div style="color:#94a3b8;font-size:12px;margin-top:8px;">'+e.message+'</div>';
	}
})();
</script>
</body>
</html>

import React, { useEffect, useState,useMemo,useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray,Formik,Form,Field,useFormikContext  } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import _ from "lodash";
import axios from 'axios';
import logger from 'redux-logger';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faChevronRight, faChevronDown } from "@fortawesome/free-solid-svg-icons";
import Select from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import { useToast } from "./../hooks/useToast";
import useOpenInNewTab from "./../hooks/useOpenInNewTab";
import useDataTableStyles from "../hooks/useDataTableStyles";
import { useWindowSize } from "./../hooks/useWindowSize";
import useTableSearch from "./../hooks/useTableSearch";
import Icon from "./../hooks/Icons";
import useDropdownFix from "./../hooks/useDropdownFix";
import useDiggPagination from "./../hooks/useDiggPagination";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";

const slice = createSlice({
    name: 'properties',
    initialState: {
		refresh: 0,
	},
    reducers: {
		triggerRefresh: (state) => {
            state.refresh = Date.now(); // unique timestamp every trigger
        },
    },
});

const { triggerRefresh } = slice.actions;
	
const store = configureStore({
    reducer: { properties: slice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

function ActionsDropdown({ row }) {
  const { width } = useWindowSize();
  const isMobile = width < 600;
  // Shared action-icon style — matches the Sales page action icons exactly
  // (white rounded square, grey icon, cream/orange hover, inline SVG).
  const iconStyle = {
    width: isMobile ? '38px' : '30px', height: isMobile ? '38px' : '30px',
    borderRadius:'7px', background:'#fff', border:'1px solid #e8e8ec', color:'#6b7280',
    display:'flex', alignItems:'center', justifyContent:'center',
    cursor:'pointer', padding:0, outline:'none', boxShadow:'none',
    transition:'all 0.15s', textDecoration:'none', flexShrink:0,
  };
  const hoverIn  = e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';};
  const hoverOut = e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';};
  return (
    <div style={{display:'flex',gap:'6px',justifyContent:'flex-end'}}>
      <a href={`/product_history/view?product=${row.id}`} title="History" style={iconStyle} onMouseEnter={hoverIn} onMouseLeave={hoverOut}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
      </a>
      <a href={`/management/products/edit/${row.id}/edit`} title="Edit" style={iconStyle} onMouseEnter={hoverIn} onMouseLeave={hoverOut}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </a>
    </div>
  );
}

function MobileProductCard({ row, currency }) {
	const val = Number(row.profit_loss ?? 0);
	const pct = Number(row.avg_profit_percentage ?? 0);
	const soldQty = Number(row.stock_quantity ?? 0);
	const isProfit = val >= 0;
	const hasSales = val !== 0 || soldQty > 0;
	return (
		<div style={{background:'#fff',borderRadius:'16px',padding:'16px',marginBottom:'10px',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',border:'1px solid #f2f2f2'}}>
			{/* Header: Name + Price */}
			<div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'10px'}}>
				<div style={{flex:1,minWidth:0}}>
					<div style={{fontSize:'15px',fontWeight:700,color:'#111',lineHeight:1.3}}>{row.name}</div>
					<div style={{display:'flex',alignItems:'center',gap:'6px',marginTop:'3px'}}>
						<span style={{background:'#FFF5ED',color:'rgb(234, 88, 12)',borderRadius:'6px',padding:'2px 7px',fontSize:'10px',fontWeight:600}}>{row.product_id}</span>
						<span style={{background:'#FFF5ED',color:'rgb(234, 88, 12)',borderRadius:'6px',padding:'2px 7px',fontSize:'10px',fontWeight:600}}>{row.created_at}</span>
					</div>
				</div>
				<span style={{fontSize:'10px',fontWeight:700,padding:'3px 9px',borderRadius:'20px',background:row.is_active==1?'#ecfdf5':'#fef2f2',color:row.is_active==1?'#059669':'#dc2626',flexShrink:0,marginLeft:'10px'}}>{row.is_active==1?'Active':'Inactive'}</span>
			</div>

			{/* Info: Weight + Tax as inline badges */}
			<div style={{display:'flex',gap:'6px',marginBottom:'10px',flexWrap:'wrap'}}>
				<span style={{fontSize:'11px',color:'#666',background:'#f5f5f5',borderRadius:'8px',padding:'4px 10px',fontWeight:600}}>
					<span style={{color:'#aaa',fontSize:'9px',marginRight:'3px'}}>WT</span> {row.unit_weight ?? '—'}
				</span>
				<span style={{fontSize:'11px',color:'#666',background:'#f5f5f5',borderRadius:'8px',padding:'4px 10px',fontWeight:600}}>
					<span style={{color:'#aaa',fontSize:'9px',marginRight:'3px'}}>TAX</span> {row.tax_vat ?? '—'}
				</span>
			</div>

			{/* Stats row */}
			<div style={{display:'flex',alignItems:'center',gap:'6px',flexWrap:'wrap',marginBottom:'10px'}}>
				{hasSales ? (<>
					<span style={{fontSize:'10.5px',fontWeight:700,color:isProfit?'#166534':'#991b1b',background:isProfit?'#ecfdf5':'#fef2f2',padding:'3px 8px',borderRadius:'6px',display:'inline-flex',alignItems:'center',gap:'3px'}}>
						<span style={{fontSize:'9px'}}>{isProfit?'▲':'▼'}</span>{isProfit?'Profit':'Loss'} {currency}{Math.abs(val).toLocaleString()}
					</span>
					<span style={{fontSize:'10px',color:'#aaa'}}>{soldQty} sold</span>
					<span style={{fontSize:'10px',color:'#aaa'}}>{pct>=0?'+':''}{pct}% margin</span>
				</>) : (
					<span style={{fontSize:'11px',color:'#bbb'}}>No sales yet</span>
				)}
				<span style={{fontSize:'16px',fontWeight:800,color:'#111',marginLeft:'auto'}}>{currency}{row.selling_price ?? '—'}</span>
			</div>

			{/* Actions */}
			<div style={{display:'flex',alignItems:'center',justifyContent:'flex-end',gap:'8px',paddingTop:'8px',borderTop:'1px solid #f5f5f5'}}>
				<a href={'/management/products/edit/'+row.id+'/edit'} style={{height:'30px',padding:'0 12px',borderRadius:'8px',border:'1.5px solid rgb(234, 88, 12)',background:'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',color:'rgb(234, 88, 12)',fontSize:'11px',gap:'5px',fontWeight:600}}>
					<i className="fa fa-pencil" style={{fontSize:'10px'}}></i> Edit
				</a>
				<a href={'/product_history/view?product='+row.id} style={{height:'30px',padding:'0 12px',borderRadius:'8px',border:'none',background:'#FF781F',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',color:'#fff',fontSize:'11px',gap:'5px',fontWeight:700,boxShadow:'0 2px 6px rgba(255,120,31,0.25)'}}>
					<i className="fa fa-history" style={{fontSize:'10px'}}></i> History
				</a>
			</div>
		</div>
	);
}

// Products table paginator — bigger page sizes (default 50). Reuses SpecPagination.
const ProductsPagination = (p) => <SpecPagination {...p} rowsPerPageOptions={[50, 100, 150, 200, 250, 500]} />;

export default function ProductsIndexApp(props) {
	const dispatch = useDispatch();
	const [data, setData] = useState([]);
	const [loading, setLoading] = useState(true);
	const [searchText, setSearchText] = useState('');
	const [statusFilter, setStatusFilter] = useState({label:'All',value:'all'});
	const [downloadingExcel, setDownloadingExcel] = useState(false);
	const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
	const [isTablet, setIsTablet] = useState(false); // iPad/tablets (768-1024) now render the desktop UI
	const [viewMode, setViewMode] = useState('card');   // mobile card/list toggle
	const [cardPage, setCardPage] = useState(1);        // mobile card view pagination
	const [cardPerPage, setCardPerPage] = useState(50); // default 50 cards/page (options 50–500)
	useEffect(() => { setCardPage(1); }, [searchText, statusFilter, cardPerPage]);

	// Force remove overflow:hidden from DataTable internals on mobile/tablet
	useEffect(() => {
		if (window.innerWidth > 767) return; // surgery only for real mobile; iPad/tablet use desktop layout
		const fix = setInterval(() => {
			const area = document.querySelector('.pi-scroll-area');
			if (!area) return;
			// Remove overflow:hidden from ALL descendants
			area.querySelectorAll('*').forEach(el => {
				if (el.style.overflow === 'hidden' || el.style.overflowX === 'hidden') {
					el.style.overflow = 'visible';
					el.style.overflowX = 'visible';
				}
			});
			// Force inner content width
			const inner = area.firstElementChild;
			if (inner) inner.style.minWidth = '950px';
			if (area.scrollWidth > area.clientWidth + 5) clearInterval(fix);
		}, 500);
		return () => clearInterval(fix);
	}, [filteredData]);

	// Custom scrollbar sync
	useEffect(() => {
		if (window.innerWidth > 767) return; // surgery only for real mobile; iPad/tablet use desktop layout
		let rafId;
		const init = setInterval(() => {
			const scrollEl = document.querySelector('.pi-table-scroll');
			const bar = document.querySelector('.pi-scroll-bar');
			const thumb = document.querySelector('.pi-scroll-thumb');
			if (!scrollEl || !bar || !thumb) return;
			if (scrollEl.scrollWidth <= scrollEl.clientWidth + 5) return;
			clearInterval(init);
			let lastLeft = -1;
			const sync = () => {
				const sw = scrollEl.scrollWidth, cw = scrollEl.clientWidth;
				if (sw <= cw + 1) { bar.style.display = 'none'; rafId = requestAnimationFrame(sync); return; }
				bar.style.display = 'block';
				if (scrollEl.scrollLeft !== lastLeft) {
					lastLeft = scrollEl.scrollLeft;
					const barW = bar.offsetWidth;
					const thumbPx = Math.max(30, (cw / sw) * barW);
					const maxLeft = barW - thumbPx;
					const maxScroll = sw - cw;
					const pct = maxScroll > 0 ? scrollEl.scrollLeft / maxScroll : 0;
					thumb.style.width = thumbPx + 'px';
					thumb.style.left = (pct * maxLeft) + 'px';
				}
				rafId = requestAnimationFrame(sync);
			};
			sync();
		}, 500);
		return () => { clearInterval(init); if (rafId) cancelAnimationFrame(rafId); };
	}, [filteredData]);

	useEffect(() => {
		const handle = () => {
			setIsMobile(window.innerWidth <= 767);
			setIsTablet(false); // iPad/tablets (768-1024) now render the desktop UI
		};
		window.addEventListener('resize', handle);
		return () => window.removeEventListener('resize', handle);
	}, []);

	const filteredData = useMemo(() => {
		let result = data;
		// Status filter
		const sf = statusFilter?.value || 'all';
		if (sf === 'active') result = result.filter(p => p.is_active == 1);
		else if (sf === 'inactive') result = result.filter(p => p.is_active != 1);
		// Search filter
		if (searchText.trim()) {
			const q = searchText.trim().toLowerCase();
			result = result.filter(p => {
				if ((p.name || '').toLowerCase().includes(q)) return true;
				if (String(p.selling_price ?? '').toLowerCase().includes(q)) return true;
				if (String(p.unit_weight ?? '').toLowerCase().includes(q)) return true;
				if (String(p.tax_vat ?? '').toLowerCase().includes(q)) return true;
				if ((p.created_at || '').toLowerCase().includes(q)) return true;
				if ((p._status || '').toLowerCase().includes(q)) return true;
				if ((p._pl_status || '').toLowerCase().includes(q)) return true;
				return false;
			});
		}
		return result;
	}, [data, searchText, statusFilter]);

    const { pageData, Pagination } = useDiggPagination(filteredData, 10);

	const hs = {fontSize:'11px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13px',color:'#374151',fontWeight:'500'};

	const columns = [
        {
            name: <span style={hs}>Name</span>,
            selector: row => row.name,
			cell: row => (
				<div style={{whiteSpace:'nowrap'}}>
					<span style={{fontWeight:'600',color:'#111827',fontSize:'13px'}}>{row.name}</span>
					<span className="pid-badge">{row.product_id}</span>
				</div>
			),
            sortable: true,
            grow: 2,
        },
		{
            name: <span style={{...hs, paddingLeft:'20px'}}>Selling Price</span>,
            selector: row => row.selling_price,
			cell: row => <span style={{...cellStyle,fontWeight:'700',color:'#111827',paddingLeft:'20px'}}>{row.selling_price ? (props.currency || '£') + ' ' + row.selling_price : '—'}</span>,
            sortable: true,
            minWidth: '140px',
        },
		{
            name: <span style={hs}>Weight / Unit</span>,
            selector: row => row.unit_weight,
			cell: row => <span style={cellStyle}>{row.unit_weight ?? '—'}</span>,
            sortable: true,
            minWidth: '120px',
        },
		{
            name: <span style={hs}>Tax / VAT</span>,
            selector: row => row.tax_vat,
			cell: row => <span style={cellStyle}>{row.tax_vat ?? '—'}</span>,
            sortable: true,
        },
		{
            name: <span style={hs}>Date</span>,
            selector: row => row.created_at,
			cell: row => <span style={cellStyle}>{row.created_at}</span>,
            sortable: true,
        },
		{
            name: <span style={hs}>Status</span>,
            selector: row => row.is_active,
			cell: row => (row.is_active == 1
				? <span style={{background:'#ecfdf5',color:'#059669',border:'1px solid #a7f3d0',borderRadius:'20px',padding:'3px 12px',fontSize:'11px',fontWeight:'600'}}>Active</span>
				: <span style={{background:'#fef2f2',color:'#dc2626',border:'1px solid #fecaca',borderRadius:'20px',padding:'3px 12px',fontSize:'11px',fontWeight:'600'}}>Inactive</span>),
            sortable: true,
        },
		{
            name: <span style={hs}>Profit / Loss</span>,
            selector: row => Number(row.profit_loss ?? 0),
			cell: row => {
				const val = Number(row.profit_loss ?? 0);
				const pct = Number(row.avg_profit_percentage ?? 0);
				const soldQty = Number(row.stock_quantity ?? 0);
				if (val === 0 && soldQty === 0) return <span style={{color:'#94a3b8',fontSize:'12px'}}>No sales yet</span>;
				const isProfit = val >= 0;
				return (
					<div style={{display:'flex',flexDirection:'column',gap:'2px'}}>
						<span style={{
							fontWeight:'700', fontSize:'12.5px',
							color: isProfit ? '#15803d' : '#dc2626',
						}}>
							{isProfit ? '▲' : '▼'} {isProfit ? 'Profit' : 'Loss'} {(props.currency || '£')} {Math.abs(val).toLocaleString()}
						</span>
						<span style={{fontSize:'10.5px',color:'#94a3b8',fontWeight:'500'}}>
							{soldQty} sold · {pct >= 0 ? '+' : ''}{pct}% margin
						</span>
					</div>
				);
			},
            sortable: true, minWidth:'160px',
        },
        {
            name: <span style={{...hs,display:'block',textAlign:'right'}}>Actions</span>,
            cell: row => <ActionsDropdown row={row} />,
			sortable: false,
			right: true,
			width:'120px', grow:0
        },
    ];

	const customStyles = {
		table: {
			style: {
				fontSize: '13px',
				overflow: 'visible',
				minHeight: '250px',
			},
		},
		headRow: {
			style: {
				backgroundColor: '#fafbfc',
				borderBottomColor: '#eef2f7',
				borderBottomWidth: '1.5px',
				borderBottomStyle: 'solid',
				minHeight: '42px',
			},
		},
		headCells: {
			style: {
				fontSize: '11px',
				fontWeight: '700',
				color: '#9ca3af',
				letterSpacing: '0.6px',
				textTransform: 'uppercase',
				padding: '12px 16px',
			},
		},
		rows: {
			style: {
				borderBottomColor: '#f3f4f6',
				fontSize: '13px',
				minHeight: '52px',
				overflow: 'visible',
			},
			highlightOnHoverStyle: {
				backgroundColor: '#fff7ed',
				borderBottomColor: '#f1d9c4',
				outlineColor: '#fed7aa',
			},
		},
		cells: {
			style: {
				fontSize: '13px',
				padding: '14px 16px',
				display: 'flex',
				alignItems: 'center',
				overflow: 'visible',
			},
		},
	};
	
	useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await axios.get(props.listApi);
				if(response.data.success === true){
					let products = response.data.payload;

					// Fetch profit/loss for all products
					if (props.profitLossApi) {
						try {
							const plRes = await axios.post(props.profitLossApi, {
								product_id: products.map(p => p.id),
								start_date: '2000-01-01',
								end_date: new Date().toISOString().slice(0, 10),
							});
							if (plRes.data.success && plRes.data.payload) {
								const plMap = {};
								plRes.data.payload.forEach(item => { plMap[item.id] = item; });
								products = products.map(p => {
									const pl = plMap[p.id];
									if (pl) {
										p.avg_profit = pl.avg_profit ?? 0;
										p.avg_profit_percentage = pl.avg_profit_percentage ?? 0;
										p.stock_quantity = pl.stock_quantity ?? 0;
										p.profit_loss = Math.round((pl.stock_quantity ?? 0) * (pl.avg_profit ?? 0));
									}
									return p;
								});
							}
						} catch (e) {
							console.error('Failed to load profit/loss', e);
						}
					}

					// Add searchable text fields for deep search
					products = products.map(p => ({
						...p,
						_status: p.is_active == 1 ? 'Active' : 'Inactive',
						_pl_status: Number(p.profit_loss ?? 0) > 0 ? 'Profit' : Number(p.profit_loss ?? 0) < 0 ? 'Loss' : '',
					})).sort((a, b) => b.id - a.id);

					setData(products);
				}
            } catch (err) {
                console.error('Failed to load products', err);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

	// ───────────────────────── Mobile (≤767px) card UI ─────────────────────────
	// Pixel build of the reference design. UI only — uses existing product data.
	if (isMobile) {
		const totalCount = data.length;
		const activeCount = data.filter(p => Number(p.is_active) === 1).length;
		const inactiveCount = totalCount - activeCount;
		const fmtDate = (v) => { if (!v) return ''; const d = new Date(v); return isNaN(d.getTime()) ? String(v) : d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); };
		const cur = props.currency || '£';
		const chip = {display:'inline-flex',alignItems:'center',fontSize:'11px',fontWeight:'600',color:'#475569',background:'#f8fafc',border:'1px solid #eaecf2',borderRadius:'7px',padding:'4px 9px',whiteSpace:'nowrap'};
		const actBtn = {width:'38px',height:'38px',borderRadius:'10px',border:'1px solid #eaecf2',background:'#fff',color:'#64748b',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',fontSize:'14px'};
		const tabs = [{k:'all',l:'All',c:totalCount},{k:'active',l:'Active',c:activeCount},{k:'inactive',l:'Inactive',c:inactiveCount}];
		// Card-view pagination (default 50/page)
		const cardTotalPages = Math.max(1, Math.ceil(filteredData.length / cardPerPage));
		const cardSafePage = Math.min(cardPage, cardTotalPages);
		const cardSlice = filteredData.slice((cardSafePage - 1) * cardPerPage, cardSafePage * cardPerPage);
		const cardFrom = filteredData.length === 0 ? 0 : (cardSafePage - 1) * cardPerPage + 1;
		const cardTo = Math.min(cardSafePage * cardPerPage, filteredData.length);
		const navBtnStyle = (d) => ({width:'32px',height:'32px',borderRadius:'7px',background:'#fff',border:'1px solid #e8e8ec',color:d?'#c8c8cf':'#6b7280',cursor:d?'not-allowed':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',padding:0});
		const paginatorInner = (
			<>
				<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600'}}>Rows:</span>
				<select value={cardPerPage} onChange={e=>setCardPerPage(Number(e.target.value))} style={{height:'32px',padding:'0 10px',borderRadius:'8px',border:'1px solid #e8e8ec',background:'#fff',fontSize:'12px',color:'#0f1115',fontFamily:'inherit',cursor:'pointer'}}>
					{[50,100,150,200,250,500].map(n=><option key={n} value={n}>{n}</option>)}
				</select>
				<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600',marginLeft:'auto'}}>{cardFrom}&ndash;{cardTo} of {filteredData.length}</span>
				<div style={{display:'flex',gap:'4px'}}>
					<button type="button" disabled={cardSafePage<=1} onClick={()=>setCardPage(1)} style={navBtnStyle(cardSafePage<=1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg></button>
					<button type="button" disabled={cardSafePage<=1} onClick={()=>setCardPage(p=>Math.max(1,p-1))} style={navBtnStyle(cardSafePage<=1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
					<button type="button" disabled={cardSafePage>=cardTotalPages} onClick={()=>setCardPage(p=>Math.min(cardTotalPages,p+1))} style={navBtnStyle(cardSafePage>=cardTotalPages)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
					<button type="button" disabled={cardSafePage>=cardTotalPages} onClick={()=>setCardPage(cardTotalPages)} style={navBtnStyle(cardSafePage>=cardTotalPages)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg></button>
				</div>
			</>
		);
		return (
			<div style={{maxWidth:'1440px',margin:'0 auto'}}>
				<style>{`
					#products-index-app .pi-m-search input::placeholder{color:#9ca3af;}
					.pi-scroll-area::-webkit-scrollbar{display:none;}
					.pi-range-scroll{-webkit-appearance:none;width:100%;height:6px;border-radius:10px;background:#f0f0f0;outline:none;}
					.pi-range-scroll::-webkit-slider-thumb{-webkit-appearance:none;width:50px;height:6px;border-radius:10px;background:rgb(234,88,12);cursor:pointer;}
					.pi-range-scroll::-moz-range-thumb{width:50px;height:6px;border-radius:10px;background:rgb(234,88,12);cursor:pointer;border:none;}
				`}</style>

				{/* Search (full width) */}
				<div style={{marginBottom:'14px'}}>
					<div className="pi-m-search" style={{display:'flex',alignItems:'center',gap:'9px',background:'#fff',border:'1px solid #eaecf2',borderRadius:'11px',padding:'0 13px',height:'46px',boxShadow:'0 1px 2px rgba(16,24,40,0.04)'}}>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/></svg>
						<input placeholder="Search product…" value={searchText} onChange={e=>setSearchText(e.target.value)} style={{flex:1,border:'none',background:'transparent',outline:'none',fontSize:'13.5px',color:'#1e293b',height:'100%'}}/>
					</div>
				</div>

				{/* Tabs */}
				<div style={{display:'flex',alignItems:'center',borderBottom:'1px solid #eef2f7',marginBottom:'12px'}}>
					{tabs.map(t=>{const on=statusFilter.value===t.k;return (
						<button key={t.k} type="button" onClick={()=>setStatusFilter({label:t.l,value:t.k})} style={{flex:1,display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',padding:'11px 4px',background:'none',border:'none',borderBottom:on?'2px solid rgb(234,88,12)':'2px solid transparent',marginBottom:'-1px',cursor:'pointer',fontSize:'13px',fontWeight:on?'800':'600',color:on?'rgb(234,88,12)':'#64748b'}}>
							{t.l}
							<span style={{fontSize:'10px',fontWeight:'700',minWidth:'18px',height:'18px',borderRadius:'9px',display:'inline-flex',alignItems:'center',justifyContent:'center',padding:'0 5px',background:on?'rgb(234,88,12)':'#e5e7eb',color:on?'#fff':'#64748b'}}>{t.c}</span>
						</button>
					);})}
				</div>

				{/* Count + view toggle */}
				<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'12px',flexWrap:'wrap',gap:'8px'}}>
					<span style={{display:'inline-flex',alignItems:'center',gap:'7px',fontSize:'12px',fontWeight:'700',color:'rgb(234,88,12)',background:'#fff7ed',border:'1px solid #ffedd5',borderRadius:'999px',padding:'5px 11px'}}>
						<i className="fa fa-cube" style={{fontSize:'11px'}}></i>{filteredData.length} products
					</span>
					<div style={{display:'flex',gap:'8px'}}>
						{[{k:'card',l:'Card View',i:'fa-th-large'},{k:'list',l:'Table View',i:'fa-table'}].map(v=>{const on=viewMode===v.k;return (
							<button key={v.k} type="button" onClick={()=>setViewMode(v.k)} style={{display:'inline-flex',alignItems:'center',gap:'7px',padding:'8px 13px',borderRadius:'10px',border:on?'1px solid rgb(234,88,12)':'1px solid #eaecf2',cursor:'pointer',fontSize:'12.5px',fontWeight:'700',background:on?'rgb(234,88,12)':'#fff',color:on?'#fff':'#374151',boxShadow:on?'0 2px 6px rgba(234,88,12,0.25)':'0 1px 2px rgba(16,24,40,0.04)'}}>
								<i className={'fa '+v.i} style={{fontSize:'12px'}}></i>{v.l}
							</button>
						);})}
					</div>
				</div>

				{/* Product list */}
				{loading ? (
					<SpecTableLoading label="Loading products…" />
				) : filteredData.length === 0 ? (
					<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'40px 24px',textAlign:'center'}}>
						<div style={{width:'60px',height:'60px',margin:'0 auto 14px',borderRadius:'14px',background:'#f8fafc',border:'1px solid #eaecf2',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}>
							<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
						</div>
						<div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No records found</div>
						<div style={{fontSize:'13px',color:'#6b7280',marginTop:'6px',lineHeight:'1.5',maxWidth:'320px',marginInline:'auto'}}>Try changing the search term or filter. New products appear here as soon as you create them.</div>
						<button type="button" onClick={()=>{setSearchText('');setStatusFilter({label:'All',value:'all'});}} style={{marginTop:'16px',height:'38px',padding:'0 16px',borderRadius:'10px',background:'#fff',color:'#0f1115',border:'1px solid #e8e8ec',fontWeight:'700',fontSize:'12.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',cursor:'pointer'}}>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
							Clear filters
						</button>
					</div>
				) : viewMode === 'card' ? (
					<div style={{display:'flex',flexDirection:'column',gap:'12px'}}>
						{cardSlice.map((row,i)=>{
							const active = Number(row.is_active)===1;
							const pct = Number(row.avg_profit_percentage ?? 0);
							const sold = Number(row.stock_quantity ?? 0);
							return (
								<div key={row.id||i} style={{position:'relative',background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'15px 16px 14px',overflow:'hidden'}}>
									<div style={{position:'absolute',left:0,top:0,bottom:0,width:'4px',background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}></div>
									<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'10px'}}>
										<div style={{minWidth:0}}>
											<div style={{fontSize:'16px',fontWeight:'800',color:'#0f172a',lineHeight:'1.25'}}>{row.name}</div>
											<div style={{fontSize:'12px',color:'#94a3b8',marginTop:'3px'}}>{fmtDate(row.created_at)}</div>
										</div>
										<span style={{flexShrink:0,display:'inline-flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',padding:'3px 9px',borderRadius:'999px',background:active?'#dcfce7':'#f1f5f9',color:active?'#15803d':'#64748b'}}>
											<span style={{width:'6px',height:'6px',borderRadius:'50%',background:active?'#22c55e':'#94a3b8'}}></span>{active?'Active':'Inactive'}
										</span>
									</div>
									<div style={{display:'flex',flexWrap:'wrap',gap:'7px',marginTop:'12px'}}>
										<span style={chip}>{row.unit_weight ? (String(row.unit_weight).toLowerCase().includes('kg') ? row.unit_weight : row.unit_weight + ' kg') : '—'}</span>
										<span style={chip}>VAT {row.tax_vat ?? '—'}</span>
										<span style={chip}>Sold {sold}</span>
									</div>
									<div style={{display:'flex',alignItems:'flex-end',justifyContent:'space-between',marginTop:'14px',gap:'10px'}}>
										<div style={{minWidth:0}}>
											<div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase'}}>Selling Price</div>
											<div style={{fontSize:'22px',fontWeight:'800',color:'#0f172a',lineHeight:'1.1',marginTop:'2px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{row.selling_price ? cur+row.selling_price : '—'}</div>
											{(() => {
												const val = Number(row.profit_loss ?? 0);
												if (val === 0 && sold === 0) return <div style={{fontSize:'12px',fontWeight:'600',color:'#94a3b8',marginTop:'5px'}}>No sales yet</div>;
												const isProfit = val >= 0;
												return (
													<div style={{fontSize:'12px',fontWeight:'700',color:isProfit?'#15803d':'#dc2626',marginTop:'5px'}}>
														{isProfit?'▲':'▼'} {isProfit?'Profit':'Loss'} {cur}{Math.abs(val).toLocaleString()}
														<span style={{color:'#94a3b8',fontWeight:'500'}}> · {pct>=0?'+':''}{pct}% margin</span>
													</div>
												);
											})()}
										</div>
										<div style={{display:'flex',gap:'8px',flexShrink:0}}>
											<a href={'/product_history/view?product='+row.id} title="History" style={actBtn}><i className="fa fa-history"></i></a>
											<a href={'/management/products/edit/'+row.id+'/edit'} title="Edit" style={actBtn}><i className="fa fa-pencil"></i></a>
										</div>
									</div>
								</div>
							);
						})}
					</div>
				) : (
					/* List view — table UI matching the Sales page (white card + native scroll + paginator inside) */
					<div style={{borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 6px rgba(0,0,0,0.05)',background:'#fff',overflow:'hidden',position:'relative'}}>
						<div style={{overflowX:'auto',WebkitOverflowScrolling:'touch',minWidth:0}}>
							<div style={{minWidth:'950px'}}>
								<DataTable columns={columns} data={cardSlice} responsive={false} highlightOnHover customStyles={customStyles} persistTableHead noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>No products found</div>} />
							</div>
						</div>
						<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'10px 14px',borderTop:'1px solid #f1f5f9'}}>{paginatorInner}</div>
					</div>
				)}

				{!loading && viewMode === 'card' && filteredData.length > 0 && (
					<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'16px 2px 4px'}}>{paginatorInner}</div>
				)}
				<ToastContainer position="bottom-right" autoClose={3000} />
			</div>
		);
	}

    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
	<style>{`
		@media (max-width: 767px) {
			.pi-desktop-header { display: none !important; }
			.pi-desktop-wrapper { border: none !important; box-shadow: none !important; background: transparent !important; border-radius: 0 !important; }
		}
		.pi-scroll-area { scrollbar-width: none; }
		.pi-scroll-area::-webkit-scrollbar { display: none; }
		.pi-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; }
		.pi-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; }
		.pi-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; border: none; }
		@media (min-width: 768px) {
			.pi-mobile-view { display: none !important; }
		}
		@media (max-width: 575px) {
			.pi-action-btns { gap: 4px !important; }
			.pi-action-btns a { width: 30px !important; height: 30px !important; font-size: 12px !important; }
		}
	`}</style>
	<div style={{
		borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',background:'#fff',
		boxShadow:'0 4px 16px rgba(0,0,0,0.04)',marginBottom:'16px',
	}}>
		{/* Header */}
		<div className="pi-header" style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',gap:'12px'}}>
				{!isMobile && (
				<div style={{display:'flex',alignItems:'center',gap:'10px',width:'100%'}}>
					<div style={{width:'200px',flexShrink:0}}>
						<Select
							options={[{label:'All',value:'all'},{label:'Active',value:'active'},{label:'Inactive',value:'inactive'}]}
							value={statusFilter}
							onChange={(val) => setStatusFilter(val || {label:'All',value:'all'})}
							isSearchable={false}
							classNamePrefix="react-select"
							menuPortalTarget={document.body}
							styles={{
						control: (base, state) => ({...base, minHeight:'40px', height:'40px', borderRadius:'10px',
							border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
							background:'#fff', boxShadow: state.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : '0 1px 3px rgba(0,0,0,0.04)',
							'&:hover':{borderColor:'#cbd5e1'}, cursor:'pointer',
						}),
						valueContainer: (base) => ({...base, height:'40px', padding:'0 12px'}),
						indicatorsContainer: (base) => ({...base, height:'40px'}),
						indicatorSeparator: () => ({display:'none'}),
						singleValue: (base) => ({...base, fontSize:'13px', fontWeight:'600', color:'#1e293b'}),
						menu: (base) => ({...base, borderRadius:'10px', border:'1px solid #e8ecf2', boxShadow:'0 8px 24px rgba(0,0,0,0.1)', marginTop:'4px', zIndex:10}),
						menuPortal: (base) => ({...base, zIndex:9999}),
						option: (base, state) => ({...base, fontSize:'13px', fontWeight:'500', padding:'10px 14px', cursor:'pointer',
							backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
							color: state.isSelected ? '#fff' : state.isFocused ? 'rgb(234, 88, 12)' : '#334155',
						}),
					}}
						/>
					</div>
					<div className="pi-search" style={{position:'relative',flex:1,minWidth:'200px'}}>
						<svg style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',pointerEvents:'none'}} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<input type="text" placeholder="Search by name, price, status, profit/loss..."
							value={searchText}
							onChange={(e) => setSearchText(e.target.value)}
							style={{paddingRight:'32px',height:'40px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',
								fontSize:'13px',fontWeight:'500',background:'#fff',padding:'0 14px 0 36px',
								outline:'none',transition:'all 0.2s',color:'#1e293b',boxShadow:'0 1px 3px rgba(0,0,0,0.04)'}}
							onFocus={(e) => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';e.target.style.boxShadow='0 0 0 4px rgba(234,88,12,0.08)';}}
							onBlur={(e) => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#fff';e.target.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)';}}
						/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
					</div>
					{/* Print */}
					{props.printUrl && (
						<button type="button" className="icon-tip" data-tip="Print" onClick={() => {
							const params = new URLSearchParams({ status: statusFilter?.value || 'all', search: searchText || '' });
							window.open(props.printUrl + '?' + params.toString(), '_blank');
						}}
							style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0,boxShadow:'0 1px 3px rgba(0,0,0,0.04)'}}
							onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
							onMouseLeave={e=>{e.currentTarget.style.borderColor='#e2e8f0';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
						>
							<i className="fa fa-print" style={{fontSize:'14px'}}></i>
						</button>
					)}
					{/* Download Excel */}
					{props.excelUrl && (
						<button type="button" className="icon-tip" data-tip="Download Excel" disabled={downloadingExcel} onClick={() => {
							if (downloadingExcel) return;
							setDownloadingExcel(true);
							const params = new URLSearchParams({ status: statusFilter?.value || 'all', search: searchText || '' });
							window.location.href = props.excelUrl + '?' + params.toString();
							setTimeout(() => setDownloadingExcel(false), 2500);
						}}
							style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid '+(downloadingExcel?'rgb(234, 88, 12)':'#e2e8f0'),background:downloadingExcel?'#fff7ed':'#fff',color:downloadingExcel?'rgb(234, 88, 12)':'#64748b',cursor:downloadingExcel?'default':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0,boxShadow:'0 1px 3px rgba(0,0,0,0.04)'}}
							onMouseEnter={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}}
							onMouseLeave={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='#e2e8f0';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}}
						>
							<i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'14px'}}></i>
						</button>
					)}
				</div>
			)}
		</div>
		{/* Mobile: status dropdown + search row */}
		{isMobile && (
			<div style={{padding:'10px 12px',display:'flex',gap:'8px',borderBottom:'1px solid #eef2f7',alignItems:'center'}}>
				<div style={{width:'110px',flexShrink:0}}>
					<Select
						options={[{label:'All',value:'all'},{label:'Active',value:'active'},{label:'Inactive',value:'inactive'}]}
						value={statusFilter}
						onChange={(val) => setStatusFilter(val || {label:'All',value:'all'})}
						isSearchable={false}
						classNamePrefix="react-select"
						menuPortalTarget={document.body}
						styles={{
					control: (base, state) => ({...base, minHeight:'36px', height:'36px', borderRadius:'9px',
						border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
						background:'#fff', boxShadow:'none', cursor:'pointer',
					}),
					valueContainer: (base) => ({...base, height:'36px', padding:'0 8px'}),
					indicatorsContainer: (base) => ({...base, height:'36px'}),
					indicatorSeparator: () => ({display:'none'}),
					singleValue: (base) => ({...base, fontSize:'12px', fontWeight:'600', color:'#1e293b'}),
					menu: (base) => ({...base, borderRadius:'10px', border:'1px solid #e8ecf2', boxShadow:'0 8px 24px rgba(0,0,0,0.1)', marginTop:'4px', zIndex:10}),
					menuPortal: (base) => ({...base, zIndex:9999}),
					option: (base, state) => ({...base, fontSize:'12px', fontWeight:'500', padding:'8px 12px', cursor:'pointer',
						backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
						color: state.isSelected ? '#fff' : state.isFocused ? 'rgb(234, 88, 12)' : '#334155',
					}),
				}}
					/>
				</div>
				<div style={{flex:1,position:'relative'}}>
					<svg style={{position:'absolute',left:'10px',top:'50%',transform:'translateY(-50%)',pointerEvents:'none'}} width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" placeholder="Search products..."
						value={searchText}
						onChange={(e) => setSearchText(e.target.value)}
						style={{paddingRight:'32px',height:'36px',width:'100%',borderRadius:'9px',border:'1.5px solid #e2e8f0',
							fontSize:'12px',fontWeight:'500',background:'#f8fafc',padding:'0 10px 0 32px',
							outline:'none',color:'#1e293b'}}
						onFocus={(e) => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';}}
						onBlur={(e) => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
					/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
				</div>
			</div>
		)}
				<div style={{overflow:'hidden',position:'relative'}}>
			<div className="pi-scroll-inner" style={{transition:'transform 0.2s ease',minWidth: filteredData.length > 0 ? (isMobile ? '950px' : isTablet ? '1100px' : 'auto') : 'auto'}}>
				<DataTable
					columns={columns}
					data={filteredData}
					pagination
					highlightOnHover
					customStyles={customStyles}
					paginationPerPage={10}
					paginationRowsPerPageOptions={[5, 10, 20, 50]}
					paginationComponent={SpecPagination}
					progressPending={loading}
					progressComponent={<SpecTableLoading label="Loading products…" />}
					persistTableHead
					noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>No products found</div>}
				/>
			</div>
		</div>
		{/* Orange draggable scrollbar */}
		{(isMobile || isTablet) && filteredData.length > 0 && (
			<div style={{padding:'0 12px 8px'}}>
				<input type="range" min="0" max="100" defaultValue="0"
					className="pi-range-scroll"
					onChange={(e) => {
						const inner = document.querySelector('.pi-scroll-inner');
						if (!inner) return;
						const parent = inner.parentElement;
						const maxMove = inner.scrollWidth - parent.clientWidth;
						const pct = e.target.value / 100;
						inner.style.transform = 'translateX(-' + (pct * maxMove) + 'px)';
					}}
				/>
			</div>
		)}
	</div>
	<ToastContainer position="bottom-right" autoClose={3000} />
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('products-index-app')) {
    const id = "products-index-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<ProductsIndexApp {...props} />
		</Provider>
    );
}
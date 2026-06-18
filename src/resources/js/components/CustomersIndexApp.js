import React, { useEffect, useState, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import DataTable from 'react-data-table-component';
import _ from "lodash";
import axios from 'axios';
import logger from 'redux-logger';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import useDataTableStyles from "../hooks/useDataTableStyles";
import useDropdownFix from "./../hooks/useDropdownFix";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";
import DateRangePicker from "./../hooks/DateRangePicker";
import { useWindowSize } from "./../hooks/useWindowSize";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const slice = createSlice({
    name: 'properties',
    initialState: {
		refresh: 0,
		fromDate: "",
		toDate: "",
		status: "",
	},
    reducers: {
		triggerRefresh: (state) => { state.refresh = Date.now(); },
		setFromDate: (state, action) => { state.fromDate = action.payload; },
		setToDate: (state, action) => { state.toDate = action.payload; },
		setStatus: (state, action) => { state.status = action.payload; },
    },
});

const { triggerRefresh, setFromDate, setToDate, setStatus } = slice.actions;

const store = configureStore({
    reducer: { properties: slice.reducer },
	middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(logger),
	devTools: process.env.NODE_ENV !== 'production',
});

function ActionsDropdown({ row }) {
  const btns = [
    {href:`/payments/customer_payment/view?customer=${row.id}`,icon:'fa fa-credit-card',title:'Payments',color:'rgb(234, 88, 12)'},
    {href:`/customer_return/view?customer=${row.id}`,icon:'fa fa-undo',title:'Returns',color:'#6366f1'},
    {href:`/customer_history/view?customer=${row.id}`,icon:'fa fa-history',title:'History',color:'#0ea5e9'},
    {href:`/management/customers/edit/${row.id}/edit`,icon:'fa fa-pencil',title:'Edit',color:'#64748b'},
  ];
  return (
    <div style={{display:'flex',gap:'4px',justifyContent:'flex-end'}}>
      {btns.map((btn,i) => (
        <a key={i} href={btn.href} title={btn.title}
          style={{
            width:'30px',height:'30px',borderRadius:'8px',
            border:'none',background:'transparent',
            display:'inline-flex',alignItems:'center',justifyContent:'center',
            textDecoration:'none',color:'#9ca3af',fontSize:'13px',
            transition:'all 0.15s',cursor:'pointer',
          }}
          onMouseOver={(e) => {e.currentTarget.style.color=btn.color;e.currentTarget.style.background=btn.color+'12';}}
          onMouseOut={(e) => {e.currentTarget.style.color='#9ca3af';e.currentTarget.style.background='transparent';}}
        >
          <i className={btn.icon}></i>
        </a>
      ))}
    </div>
  );
}

// --- Date Range Picker ---
export default function CustomersIndexApp(props) {
	const dispatch = useDispatch();
	const { width } = useWindowSize();
	const { fromDate, toDate, status } = useSelector(state => state.properties);

	const [data, setData] = useState([]);
	const [isLoading, setIsLoading] = useState(false);
	const [searchTerm, setSearchTerm] = useState('');
	const [mobilePage, setMobilePage] = useState(0);
	const [viewMode, setViewMode] = useState('card');
	const [cardPerPage, setCardPerPage] = useState(15);
	const mobilePerPage = cardPerPage;
	useEffect(() => { setMobilePage(0); }, [searchTerm, status, fromDate, toDate, cardPerPage, viewMode]);
	const isMobile = window.innerWidth <= 767;
	const isTablet = false; // iPad/tablets (768-1024) now render the desktop UI

	const customStyles = useDataTableStyles();

	const mergedStyles = useMemo(() => ({
        ...customStyles,
        table: { style: { fontSize:'13px', overflow:'visible', minHeight:'250px', background:'#fff' } },
        headRow: { style: { backgroundColor:'#fafbfc', borderBottomColor:'#eef2f7', borderBottomWidth:'1.5px', borderBottomStyle:'solid', minHeight:'40px' } },
        headCells: { style: { fontSize:'10.5px', fontWeight:'700', color:'#94a3b8', letterSpacing:'0.8px', textTransform:'uppercase', padding:'10px 16px' } },
        rows: { style: { borderBottomColor:'#f8fafc', borderBottomWidth:'1px', borderBottomStyle:'solid', minHeight:'64px', background:'#fff' }, highlightOnHoverStyle: { backgroundColor:'#fffbf7', borderBottomColor:'#f1f5f9', outlineColor:'transparent', transition:'background 0.12s' } },
        cells: { style: { padding:'10px 16px', display:'flex', alignItems:'center' } },
        pagination: { style: { borderTop:'1px solid #f1f5f9', background:'#fff', minHeight:'52px', fontSize:'12px', color:'#6b7280', fontWeight:'500' }, pageButtonsStyle: { borderRadius:'8px', height:'28px', width:'28px', padding:'4px', cursor:'pointer', fill:'#9ca3af', '&:hover:not(:disabled)':{backgroundColor:'#fff7ed',fill:'rgb(234, 88, 12)'}, '&:disabled':{fill:'#e2e8f0'} } },
    }), [customStyles]);

	const hs = {fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.8px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const fmt = (v) => { const n = Number(v) || 0; return n.toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2}); };

	const columns = [
        {
            name: <span style={hs}>Customer</span>,
            selector: row => row.name,
            sortable: true,
			cell: row => (
				<div style={{display:'flex',alignItems:'center',gap:'10px',padding:'4px 0'}}>
					{row.image
						? <img src={row.image} alt={row.name} style={{width:'36px',height:'36px',borderRadius:'50%',objectFit:'cover',flexShrink:0,border:'2px solid #f1f5f9'}} />
						: <span style={{width:'36px',height:'36px',borderRadius:'50%',background:'linear-gradient(135deg,#f97316,#fb923c)',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0,fontSize:'13px',fontWeight:'700',color:'#fff'}}>{(row.name || '?').charAt(0).toUpperCase()}</span>
					}
					<div style={{minWidth:0,flex:1}}>
						<div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',lineHeight:'1.3',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{row.name}</div>
						<div style={{fontSize:'11.5px',color:'#94a3b8',fontWeight:'400',marginTop:'1px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
							{row.email || row.mobile || <span style={{color:'#cbd5e1'}}>No contact info</span>}
						</div>
					</div>
				</div>
			),
			grow: 2, minWidth: '210px',
        },
		{
            name: <span style={{...hs,width:'100%',textAlign:'center',display:'block'}}>Invoices</span>,
            selector: row => Number(row.total_invoices) || 0,
            sortable: true,
			cell: row => {
				const count = Number(row.total_invoices) || 0;
				return <div style={{width:'100%',textAlign:'center'}}>
					<span style={{fontWeight:'700',fontSize:'14px',color: count > 0 ? '#1e293b' : '#d1d5db'}}>{count > 0 ? count : '—'}</span>
				</div>;
			},
			width: '90px',
        },
		{
            name: <span style={{...hs,width:'100%',textAlign:'center',display:'block',color:'rgb(234, 88, 12)'}}>Pending</span>,
            selector: row => Number(row.unpaid_invoices) || 0,
            sortable: true,
			cell: row => {
				const count = Number(row.unpaid_invoices) || 0;
				return <div style={{width:'100%',textAlign:'center'}}>{count > 0
					? <span style={{fontWeight:'700',fontSize:'12px',color:'#fff',background:'rgb(234, 88, 12)',borderRadius:'20px',padding:'3px 11px',display:'inline-block'}}>{count}</span>
					: <span style={{color:'#d1d5db',fontSize:'13px',fontWeight:'600'}}>—</span>}</div>;
			},
			width: '90px',
        },
		{
            name: <span style={{...hs,width:'100%',textAlign:'right',display:'block'}}>Unpaid</span>,
            selector: row => Number(row.unpaid_balance) || 0,
            sortable: true,
			cell: row => {
				const v = Number(row.unpaid_balance) || 0;
				return <div style={{width:'100%',textAlign:'right'}}>{v > 0
					? <span style={{fontWeight:'700',fontSize:'13px',color:'#ef4444',fontVariantNumeric:'tabular-nums'}}>{fmt(v)}</span>
					: <span style={{color:'#d1d5db',fontSize:'12px'}}>—</span>}</div>;
			},
			width: '120px',
        },
		{
            name: <span style={{...hs,width:'100%',textAlign:'right',display:'block'}}>Paid</span>,
            selector: row => Number(row.total_paid) || 0,
            sortable: true,
			cell: row => {
				const v = Number(row.total_paid) || 0;
				return <div style={{width:'100%',textAlign:'right'}}>{v > 0
					? <span style={{fontWeight:'600',fontSize:'13px',color:'#16a34a',fontVariantNumeric:'tabular-nums'}}>{fmt(v)}</span>
					: <span style={{color:'#d1d5db',fontSize:'12px'}}>—</span>}</div>;
			},
			width: '120px',
        },
		{
            name: <span style={{...hs,width:'100%',textAlign:'right',display:'block'}}>Total</span>,
            selector: row => Number(row.total_sales) || 0,
            sortable: true,
			cell: row => {
				const v = Number(row.total_sales) || 0;
				return <div style={{width:'100%',textAlign:'right'}}>{v > 0
					? <span style={{fontWeight:'700',fontSize:'13px',color:'#1e293b',fontVariantNumeric:'tabular-nums'}}>{fmt(v)}</span>
					: <span style={{color:'#d1d5db',fontSize:'12px'}}>—</span>}</div>;
			},
			width: '120px',
        },
        {
            name: '',
            cell: row => (<ActionsDropdown row={row} />),
			sortable: false,
			right: true,
			width: '140px',
        },
    ];

	// Fetch data — shows orange spinner overlay while AJAX is in flight
	useEffect(() => {
        let cancelled = false;
        const fetchData = async () => {
            setIsLoading(true);
            try {
                const params = {};
                if (fromDate) params.start_date = fromDate;
                if (toDate) params.end_date = toDate;
                if (status) params.status = status;
                const response = await axios.post(props.listApi, params);
                if (cancelled) return;
				if(response.data.success === true){
					setData(response.data.payload);
				}
            } catch (err) {
                if (!cancelled) console.error('Failed to load customers', err);
            } finally {
                if (!cancelled) setIsLoading(false);
            }
        };
        fetchData();
        return () => { cancelled = true; };
    }, [fromDate, toDate, status]);

	// Search filter
	const filteredData = useMemo(() => {
		if (!searchTerm) return data;
		const term = searchTerm.toLowerCase();
		return data.filter(row =>
			['name','email','mobile','customer_id','created_at'].some(field =>
				String(row[field] || '').toLowerCase().includes(term)
			)
		);
	}, [data, searchTerm]);

	const statusOptions = [
		{ value: '', label: 'All Status' },
		{ value: 'active', label: 'Active' },
		{ value: 'disabled', label: 'Inactive' },
	];

	useDropdownFix();

    return (
	<>
	<style>{`
		.cust-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; cursor: pointer; }
		.cust-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: #F27420; cursor: pointer; }
		.cust-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: #F27420; cursor: pointer; border: none; }
		.rdt_Pagination select {
			appearance: none !important;
			-webkit-appearance: none !important;
			background-color: #fff !important;
			border: 1.5px solid #e2e8f0 !important;
			border-radius: 8px !important;
			padding: 0 28px 0 10px !important;
			height: 32px !important;
			font-size: 12px !important;
			font-weight: 600 !important;
			color: #374151 !important;
			cursor: pointer !important;
			outline: none !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23F27420' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important;
			background-position: right 8px center !important;
			transition: border-color 0.15s !important;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
		}
		.rdt_Pagination select:focus {
			border-color: #F27420 !important;
			box-shadow: 0 0 0 3px rgba(242,116,32,0.1) !important;
		}
		@media (max-width: 767px) {
			.cust-filter-bar { padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important; margin-bottom: 12px !important; }
			.cust-filter-row { flex-direction: column !important; align-items: stretch !important; gap: 10px !important; flex-wrap: nowrap !important; }
			.cust-search-col { min-width: unset !important; flex: unset !important; width: 100% !important; }
			.cust-inline-row { display: flex !important; gap: 8px !important; }
			.cust-status-col { flex: 1 1 0 !important; min-width: 0 !important; }
			.cust-date-col { flex: 1 1 0 !important; min-width: 0 !important; }
			.cust-date-col > button { min-width: unset !important; width: 100% !important; font-size: 12px !important; padding: 0 10px !important; }
		}
	`}</style>
	{/* Filters bar */}
	<div className="cust-filter-bar" style={{background:'#fff',borderRadius:'0',border:'1px solid #eaecf2',borderTop:'none',borderBottom:'none',boxShadow:'none',padding:'14px 20px',marginBottom:'0'}}>
		<div className="cust-filter-row" style={{display:'flex',alignItems:'center',gap:'12px',flexWrap:'wrap'}}>
			{/* Search */}
			{isMobile ? (
			<div className="cust-search-col" style={{display:'flex',alignItems:'center',gap:'9px',background:'#fff',border:'1px solid #ececec',borderRadius:'12px',padding:'0 13px',height:'46px'}}>
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" style={{flexShrink:0}}><circle cx="11" cy="11" r="6.5" stroke="#6B6B6B" strokeWidth="1.7"/><path d="M20 20l-4-4" stroke="#6B6B6B" strokeWidth="1.7" strokeLinecap="round"/></svg>
				<input placeholder="Search customers…" value={searchTerm} onChange={e => { setSearchTerm(e.target.value); setMobilePage(0); }} style={{flex:1,minWidth:0,border:'none',outline:'none',fontSize:'14px',fontFamily:'inherit',background:'transparent'}}/>
			</div>
			) : (
			<div className="cust-search-col" style={{position:'relative',flex:'1',minWidth:'200px'}}>
				<i className="fa fa-search" style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',fontSize:'13px',zIndex:2,pointerEvents:'none'}}></i>
				<input
					placeholder="Search customers..."
					value={searchTerm}
					onChange={e => { setSearchTerm(e.target.value); setMobilePage(0); }}
					style={{width:'100%',paddingLeft:'36px',paddingRight:'12px',height:'38px',borderRadius:'9px',border:'1.5px solid #e5e7eb',fontSize:'13px',outline:'none',boxSizing:'border-box',transition:'border-color 0.15s'}}
					onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'}
					onBlur={e => e.target.style.borderColor='#e5e7eb'}
				/>
{searchTerm && <button type="button" onClick={() => {setSearchTerm(''); setMobilePage(0)}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
			</div>
			)}

			{/* Status + Date inline row on mobile */}
			<div className="cust-inline-row" style={{display:'contents'}}>
				{/* Status filter */}
				<div className="cust-status-col" style={{minWidth:'150px'}}>
					<Select
						options={statusOptions}
						value={statusOptions.find(o => o.value === status)}
						onChange={opt => dispatch(setStatus(opt.value))}
						styles={{
							...orangeSelectStyles,
							control: (base, state) => ({
								...base,
								minHeight:'38px',height:'38px',
								borderRadius:'9px',
								border: state.isFocused ? '1.5px solid #f97316' : '1.5px solid #e5e7eb',
								boxShadow:'none',
								fontSize:'13px',
								'&:hover': { borderColor:'rgb(234, 88, 12)' },
							}),
							valueContainer: base => ({ ...base, padding:'0 12px' }),
							indicatorSeparator: () => ({ display:'none' }),
						}}
						isSearchable={false}
					/>
				</div>

				{/* Date range */}
				<div className="cust-date-col">
					<DateRangePicker
						fromDate={fromDate}
						toDate={toDate}
						onFromChange={val => dispatch(setFromDate(val))}
						onToChange={val => dispatch(setToDate(val))}
					/>
				</div>
			</div>

		</div>
	</div>

	{/* Table / Mobile Cards */}
	{isMobile ? (() => {
		const cur = props.currency || '£';
		const totalItems = filteredData.length;
		const cardTotalPages = Math.max(1, Math.ceil(totalItems / cardPerPage));
		const cardSafePage = Math.min(mobilePage, cardTotalPages - 1);
		const cardSlice = filteredData.slice(cardSafePage * cardPerPage, (cardSafePage + 1) * cardPerPage);
		const cardFrom = totalItems === 0 ? 0 : cardSafePage * cardPerPage + 1;
		const cardTo = Math.min((cardSafePage + 1) * cardPerPage, totalItems);
		const fmt2 = (v) => { const n = Number(v) || 0; return n.toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2}); };
		const chip = {display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'600',color:'#475569',background:'#f8fafc',border:'1px solid #eaecf2',borderRadius:'7px',padding:'4px 9px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',maxWidth:'100%'};
		const actBtn = {width:'36px',height:'36px',borderRadius:'9px',border:'1px solid #eaecf2',background:'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',fontSize:'13px',flexShrink:0};
		const navBtnStyle = (d) => ({width:'32px',height:'32px',borderRadius:'7px',background:'#fff',border:'1px solid #e8e8ec',color:d?'#c8c8cf':'#6b7280',cursor:d?'not-allowed':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',padding:0});
		const paginatorInner = (
			<>
				<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600'}}>Rows:</span>
				<select value={cardPerPage} onChange={e=>setCardPerPage(Number(e.target.value))} style={{height:'32px',padding:'0 10px',borderRadius:'8px',border:'1px solid #e8e8ec',background:'#fff',fontSize:'12px',color:'#0f1115',fontFamily:'inherit',cursor:'pointer'}}>
					{[15,30,50,100,200].map(n=><option key={n} value={n}>{n}</option>)}
				</select>
				<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600',marginLeft:'auto'}}>{cardFrom}&ndash;{cardTo} of {totalItems}</span>
				<div style={{display:'flex',gap:'4px'}}>
					<button type="button" disabled={cardSafePage<=0} onClick={()=>setMobilePage(0)} style={navBtnStyle(cardSafePage<=0)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg></button>
					<button type="button" disabled={cardSafePage<=0} onClick={()=>setMobilePage(p=>Math.max(0,p-1))} style={navBtnStyle(cardSafePage<=0)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
					<button type="button" disabled={cardSafePage>=cardTotalPages-1} onClick={()=>setMobilePage(p=>Math.min(cardTotalPages-1,p+1))} style={navBtnStyle(cardSafePage>=cardTotalPages-1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
					<button type="button" disabled={cardSafePage>=cardTotalPages-1} onClick={()=>setMobilePage(cardTotalPages-1)} style={navBtnStyle(cardSafePage>=cardTotalPages-1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg></button>
				</div>
			</>
		);
		return (
			<div>
				{/* Count + view toggle */}
				<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',flexWrap:'wrap',gap:'8px',marginBottom:'12px'}}>
					<span style={{display:'inline-flex',alignItems:'center',gap:'8px',fontSize:'14px',fontWeight:'700',color:'rgb(234,88,12)'}}>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgb(234,88,12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>{totalItems} customers
					</span>
					<div style={{display:'flex',gap:'8px'}}>
						{[{k:'card',l:'Card View',i:'fa-th-large'},{k:'list',l:'Table View',i:'fa-table'}].map(v=>{const on=viewMode===v.k;return (
							<button key={v.k} type="button" onClick={()=>setViewMode(v.k)} style={{display:'inline-flex',alignItems:'center',gap:'7px',padding:'8px 13px',borderRadius:'10px',border:on?'1px solid rgb(234,88,12)':'1px solid #eaecf2',cursor:'pointer',fontSize:'12.5px',fontWeight:'700',background:on?'rgb(234,88,12)':'#fff',color:on?'#fff':'#374151',boxShadow:on?'0 2px 6px rgba(234,88,12,0.25)':'0 1px 2px rgba(16,24,40,0.04)'}}>
								<i className={'fa '+v.i} style={{fontSize:'12px'}}></i>{v.l}
							</button>
						);})}
					</div>
				</div>

				{/* Body */}
				{isLoading && data.length === 0 ? (
					<SpecTableLoading label="Loading customers…" />
				) : totalItems === 0 ? (
					<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'40px 24px',textAlign:'center'}}>
						<div style={{width:'60px',height:'60px',margin:'0 auto 14px',borderRadius:'14px',background:'#f8fafc',border:'1px solid #eaecf2',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}>
							<i className="fa fa-users" style={{fontSize:'24px'}}></i>
						</div>
						<div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No customers found</div>
						<div style={{fontSize:'13px',color:'#6b7280',marginTop:'6px',lineHeight:'1.5',maxWidth:'320px',marginInline:'auto'}}>{(searchTerm || status || fromDate || toDate) ? 'Try changing the search term or filters.' : 'New customers appear here as soon as you add them.'}</div>
						<button type="button" onClick={()=>{setSearchTerm('');dispatch(setStatus(''));dispatch(setFromDate(''));dispatch(setToDate(''));}} style={{marginTop:'16px',height:'38px',padding:'0 16px',borderRadius:'10px',background:'#fff',color:'#0f1115',border:'1px solid #e8e8ec',fontWeight:'700',fontSize:'12.5px',display:(searchTerm || status || fromDate || toDate)?'inline-flex':'none',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',cursor:'pointer'}}>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
							Clear filters
						</button>
					</div>
				) : viewMode === 'card' ? (
					<div style={{display:'flex',flexDirection:'column',gap:'12px'}}>
						{cardSlice.map((row,i)=>{
							const invoices = Number(row.total_invoices) || 0;
							const pending  = Number(row.unpaid_invoices) || 0;
							const unpaid   = Number(row.unpaid_balance) || 0;
							const paid     = Number(row.total_paid) || 0;
							const total    = Number(row.total_sales) || 0;
							const pill = pending > 0
								? {bg:'#fff7ed',color:'rgb(234,88,12)',dot:'#f97316',label:pending+' pending'}
								: invoices > 0 ? {bg:'#dcfce7',color:'#15803d',dot:'#22c55e',label:'Paid up'}
								: {bg:'#f1f5f9',color:'#64748b',dot:'#94a3b8',label:'No invoices'};
							const acts = [
								{href:`/payments/customer_payment/view?customer=${row.id}`,icon:'fa fa-credit-card',title:'Payments',color:'rgb(234, 88, 12)'},
								{href:`/customer_return/view?customer=${row.id}`,icon:'fa fa-undo',title:'Returns',color:'#6366f1'},
								{href:`/customer_history/view?customer=${row.id}`,icon:'fa fa-history',title:'History',color:'#0ea5e9'},
								{href:`/management/customers/edit/${row.id}/edit`,icon:'fa fa-pencil',title:'Edit',color:'#64748b'},
							];
							return (
								<div key={row.id||i} style={{position:'relative',background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'15px 16px 14px',overflow:'hidden'}}>
									<div style={{position:'absolute',left:0,top:0,bottom:0,width:'4px',background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}></div>
									<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'10px'}}>
										<div style={{display:'flex',alignItems:'center',gap:'11px',minWidth:0}}>
											{row.image
												? <img src={row.image} alt={row.name} style={{width:'42px',height:'42px',borderRadius:'50%',objectFit:'cover',flexShrink:0,border:'2px solid #f1f5f9'}}/>
												: <span style={{width:'42px',height:'42px',borderRadius:'50%',background:'linear-gradient(135deg,#f97316,#fb923c)',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0,fontSize:'16px',fontWeight:'800',color:'#fff'}}>{(row.name||'?').charAt(0).toUpperCase()}</span>}
											<div style={{minWidth:0}}>
												<div style={{fontSize:'15.5px',fontWeight:'800',color:'#0f172a',lineHeight:'1.25',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{row.name}</div>
												<div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{row.email || row.mobile || 'No contact info'}</div>
											</div>
										</div>
										<span style={{flexShrink:0,display:'inline-flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',padding:'3px 9px',borderRadius:'999px',background:pill.bg,color:pill.color}}>
											<span style={{width:'6px',height:'6px',borderRadius:'50%',background:pill.dot}}></span>{pill.label}
										</span>
									</div>
									<div style={{display:'flex',flexWrap:'wrap',gap:'7px',marginTop:'12px'}}>
										<span style={chip}><i className="fa fa-file-text-o" style={{fontSize:'10px',color:'#94a3b8'}}></i> {invoices} invoices</span>
										<span style={chip}>Unpaid <b style={{color:unpaid>0?'#ef4444':'#64748b',marginLeft:'3px'}}>{cur}{fmt2(unpaid)}</b></span>
										<span style={chip}>Paid <b style={{color:'#16a34a',marginLeft:'3px'}}>{cur}{fmt2(paid)}</b></span>
									</div>
									<div style={{display:'flex',alignItems:'flex-end',justifyContent:'space-between',flexWrap:'wrap',gap:'10px',marginTop:'14px'}}>
										<div style={{minWidth:0}}>
											<div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase'}}>Total Sales</div>
											<div style={{fontSize:'22px',fontWeight:'800',color:'#0f172a',lineHeight:'1.1',marginTop:'2px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{cur}{fmt2(total)}</div>
										</div>
										<div style={{display:'flex',gap:'7px'}}>
											{acts.map((a,j)=>{const ed=a.title==='Edit';return (<a key={j} href={a.href} title={a.title} style={{...actBtn,color:ed?'rgb(234, 88, 12)':'#1f2937',background:ed?'#fff5ed':'#fff',border:ed?'1px solid #ffedd5':'1px solid #eaecf2'}}><i className={a.icon}></i></a>);})}
										</div>
									</div>
								</div>
							);
						})}
					</div>
				) : (
					<div style={{borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 6px rgba(0,0,0,0.05)',background:'#fff',overflow:'hidden'}}>
						<div style={{overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
							<div style={{minWidth:'900px'}}>
								<DataTable columns={columns} data={cardSlice} responsive={false} highlightOnHover customStyles={mergedStyles} persistTableHead noDataComponent={<div style={{padding:'40px',textAlign:'center',color:'#94a3b8',fontSize:'13px'}}>No customers found</div>} />
							</div>
						</div>
						<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'10px 14px',borderTop:'1px solid #f1f5f9'}}>{paginatorInner}</div>
					</div>
				)}

				{!(isLoading && data.length === 0) && viewMode === 'card' && totalItems > 0 && (
					<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'16px 2px 4px'}}>{paginatorInner}</div>
				)}
			</div>
		);
	})() : isTablet ? (() => {
		const totalItems = filteredData.length;
		const totalPages = Math.ceil(totalItems / mobilePerPage) || 1;
		const safePage = Math.min(mobilePage, totalPages - 1);
		const pageItems = filteredData.slice(safePage * mobilePerPage, (safePage + 1) * mobilePerPage);
		const fmt2 = (v) => { const n = Number(v)||0; return n.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2}); };
		const colGrid = isTablet ? '220px 90px 110px 120px 120px 130px 140px' : '180px 90px 100px 100px 100px 100px 116px';
		const mobileActionBtns = (row) => [
			{href:`/payments/customer_payment/view?customer=${row.id}`,icon:'fa fa-credit-card',title:'Payments',color:'rgb(234, 88, 12)',bg:'#FFF5ED'},
			{href:`/customer_return/view?customer=${row.id}`,icon:'fa fa-undo',title:'Returns',color:'#6366f1',bg:'#ede9fe'},
			{href:`/customer_history/view?customer=${row.id}`,icon:'fa fa-history',title:'History',color:'#0ea5e9',bg:'#e0f2fe'},
			{href:`/management/customers/edit/${row.id}/edit`,icon:'fa fa-pencil',title:'Edit',color:'#64748b',bg:'#f1f5f9'},
		];
		return (
		<div style={{background:'#fff',borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',overflow:'hidden'}}>
			{/* Scrollable area */}
			<div style={{overflow:'hidden',position:'relative'}}>
			<div className="cust-scroll-inner" style={{minWidth: filteredData.length > 0 ? '786px' : 'auto',transition:'transform 0.15s ease'}}>
			{/* Column headers */}
			<div style={{display:'grid',gridTemplateColumns:colGrid,padding:'10px 14px',background:'#fff',borderBottom:'1.5px solid #f1f5f9'}}>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px'}}>Customer</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'center'}}>Invoices</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'center'}}>Pending</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'right'}}>Unpaid</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'right'}}>Paid</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'right',paddingRight:'14px'}}>Total</span>
				<span style={{fontSize:'10.5px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.8px',textAlign:'right',paddingRight:'14px',paddingLeft:'10px',borderLeft:'1px solid #f1f5f9'}}>Actions</span>
			</div>
			{/* Rows */}
			{pageItems.length === 0 ? (
				isLoading ? (
					<div style={{padding:'40px 16px',textAlign:'center'}}>
						<div style={{display:'inline-flex',alignItems:'center',gap:'8px',padding:'9px 16px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'12.5px',fontWeight:'600'}}>
							<i className="fa fa-spinner fa-spin" style={{fontSize:'13px'}}></i>
							<span>Loading customers…</span>
						</div>
					</div>
				) : (
					<div style={{padding:'40px 16px',textAlign:'center'}}>
						<i className="fa fa-users" style={{fontSize:'32px',color:'#e5e7eb',display:'block',marginBottom:'10px'}}></i>
						<div style={{fontSize:'13px',fontWeight:'600',color:'#9ca3af'}}>No customers found</div>
					</div>
				)
			) : pageItems.map((row, i) => {
				const invoices = Number(row.total_invoices) || 0;
				const pending  = Number(row.unpaid_invoices) || 0;
				const unpaid   = Number(row.unpaid_balance) || 0;
				const paid     = Number(row.total_paid) || 0;
				const total    = Number(row.total_sales) || 0;
				return (
				<div key={row.id || i} style={{display:'grid',gridTemplateColumns:colGrid,alignItems:'center',padding:'12px 14px',borderBottom:'1px solid #f8fafc',background:'#fff'}}>
					{/* Customer */}
					<div style={{display:'flex',alignItems:'center',gap:'10px'}}>
						{row.image
							? <img src={row.image} alt={row.name} style={{width:'36px',height:'36px',borderRadius:'50%',objectFit:'cover',flexShrink:0,border:'2px solid #f1f5f9'}}/>
							: <span style={{width:'36px',height:'36px',borderRadius:'50%',background:'linear-gradient(135deg,#f97316,#fb923c)',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0,fontSize:'13px',fontWeight:'700',color:'#fff'}}>{(row.name||'?').charAt(0).toUpperCase()}</span>
						}
						<div style={{minWidth:0}}>
							<div style={{fontSize:'13px',fontWeight:'700',color:'#1e293b',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',lineHeight:'1.3'}}>{row.name}</div>
							<div style={{fontSize:'11.5px',color:'#94a3b8',fontWeight:'400',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',marginTop:'1px'}}>
								{row.email || row.mobile || <span style={{color:'#cbd5e1'}}>No contact info</span>}
							</div>
						</div>
					</div>
					{/* Invoices */}
					<div style={{textAlign:'center'}}>
						<span style={{fontSize:'14px',fontWeight:'700',color: invoices > 0 ? '#1e293b' : '#d1d5db'}}>{invoices > 0 ? invoices : '—'}</span>
					</div>
					{/* Pending */}
					<div style={{textAlign:'center'}}>
						{pending > 0
							? <span style={{fontSize:'12px',fontWeight:'700',color:'#fff',background:'rgb(234, 88, 12)',borderRadius:'20px',padding:'3px 10px',display:'inline-block'}}>{pending}</span>
							: <span style={{fontSize:'13px',color:'#d1d5db'}}>—</span>
						}
					</div>
					{/* Unpaid */}
					<div style={{textAlign:'right'}}>
						<span style={{fontSize:'12px',fontWeight:'700',color: unpaid > 0 ? '#ef4444' : '#d1d5db',fontVariantNumeric:'tabular-nums'}}>{unpaid > 0 ? fmt2(unpaid) : '—'}</span>
					</div>
					{/* Paid */}
					<div style={{textAlign:'right'}}>
						<span style={{fontSize:'12px',fontWeight:'600',color: paid > 0 ? '#16a34a' : '#d1d5db',fontVariantNumeric:'tabular-nums'}}>{paid > 0 ? fmt2(paid) : '—'}</span>
					</div>
					{/* Total */}
					<div style={{textAlign:'right',paddingRight:'14px'}}>
						<span style={{fontSize:'12px',fontWeight:'700',color: total > 0 ? '#1e293b' : '#d1d5db',fontVariantNumeric:'tabular-nums'}}>{total > 0 ? fmt2(total) : '—'}</span>
					</div>
					{/* Actions */}
					<div style={{display:'flex',gap:'4px',justifyContent:'flex-end',paddingRight:'14px',paddingLeft:'10px',alignItems:'center',borderLeft:'1px solid #f1f5f9'}}>
						{mobileActionBtns(row).map((btn,i) => (
							<a key={i} href={btn.href} title={btn.title} style={{width:'24px',height:'24px',borderRadius:'6px',background:btn.bg,display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',color:btn.color,fontSize:'11px',flexShrink:0}}>
								<i className={btn.icon}></i>
							</a>
						))}
					</div>
				</div>
				);
			})}
			</div>{/* end cust-scroll-inner */}
			</div>{/* end overflow hidden */}
			{/* Orange range scrollbar */}
			{filteredData.length > 0 && <div style={{padding:'12px 14px 10px',borderTop:'1px solid #f1f5f9'}}>
				<input type="range" min="0" max="100" defaultValue="0"
					className="cust-range-scroll"
					onChange={(e) => {
						const inner = document.querySelector('.cust-scroll-inner');
						if (!inner) return;
						const parent = inner.parentElement;
						const maxMove = inner.scrollWidth - parent.clientWidth;
						if (maxMove <= 0) return;
						const pct = e.target.value / 100;
						inner.style.transform = 'translateX(-' + (pct * maxMove) + 'px)';
					}}
				/>
			</div>}
			{/* Pagination */}
			{totalItems > mobilePerPage && (
				<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'10px 4px'}}>
					<span style={{fontSize:'12px',color:'#94a3b8',fontWeight:'500'}}>{safePage*mobilePerPage+1}–{Math.min((safePage+1)*mobilePerPage,totalItems)} of {totalItems}</span>
					<div style={{display:'flex',gap:'6px'}}>
						<button type="button" onClick={()=>setMobilePage(p=>Math.max(0,p-1))} disabled={safePage===0}
							style={{width:'32px',height:'32px',borderRadius:'8px',border:'1.5px solid #e2e8f0',background:'#fff',cursor:safePage===0?'default':'pointer',color:safePage===0?'#e2e8f0':'#64748b',display:'flex',alignItems:'center',justifyContent:'center',outline:'none'}}>
							<i className="fa fa-chevron-left" style={{fontSize:'10px'}}></i>
						</button>
						<button type="button" onClick={()=>setMobilePage(p=>Math.min(totalPages-1,p+1))} disabled={safePage===totalPages-1}
							style={{width:'32px',height:'32px',borderRadius:'8px',border:'1.5px solid #e2e8f0',background:'#fff',cursor:safePage===totalPages-1?'default':'pointer',color:safePage===totalPages-1?'#e2e8f0':'#64748b',display:'flex',alignItems:'center',justifyContent:'center',outline:'none'}}>
							<i className="fa fa-chevron-right" style={{fontSize:'10px'}}></i>
						</button>
					</div>
				</div>
			)}
		</div>
		);
	})() : (
	<div style={{borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',background:'#fff',overflow:'hidden',position:'relative'}}>
		<DataTable
			columns={columns}
			data={filteredData}
			pagination
			paginationPerPage={10}
			paginationRowsPerPageOptions={[5, 10, 20, 50]}
			paginationComponent={SpecPagination}
			highlightOnHover
			customStyles={mergedStyles}
			progressPending={isLoading && data.length === 0}
			progressComponent={<SpecTableLoading label="Loading customers…" />}
			noDataComponent={
				<div style={{padding:'48px 24px',textAlign:'center'}}>
					<i className="fa fa-users" style={{fontSize:'36px',color:'#e5e7eb',display:'block',marginBottom:'12px'}}></i>
					<div style={{fontSize:'14px',fontWeight:'600',color:'#9ca3af'}}>No customers found</div>
					<div style={{fontSize:'12px',color:'#d1d5db',marginTop:'4px'}}>Try changing the filters or search term</div>
				</div>
			}
		/>
		{isLoading && data.length > 0 && (
			<div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
				<div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
					<i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
					<span>Loading…</span>
				</div>
			</div>
		)}
	</div>
	)}
	<ToastContainer autoClose={3000} />
	</>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('customers-index-app')) {
    const id = "customers-index-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<CustomersIndexApp {...props} />
		</Provider>
    );
}

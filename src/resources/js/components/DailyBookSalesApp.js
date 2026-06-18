import React, { useEffect, useState,useMemo,useRef } from 'react';
import ReactDOM from 'react-dom';
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
import Select, { components as rsComponents } from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import { useToast } from "./../hooks/useToast";
import useOpenInNewTab from "./../hooks/useOpenInNewTab";
import useDataTableStyles from "../hooks/useDataTableStyles";
import Icon from "./../hooks/Icons";
import useDropdownFix from "./../hooks/useDropdownFix";
import { useWindowSize } from "./../hooks/useWindowSize";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DateRangePicker from "./../hooks/DateRangePicker";
import SpecPagination from "./../elements/SpecPagination";
import SpecTableLoading from "./../elements/SpecTableLoading";
import DailyReportEmailModal from "./../elements/DailyReportEmailModal";
import DatePicker from "react-datepicker";
const ReactDatePicker = DatePicker;
import "react-datepicker/dist/react-datepicker.css";

// ----------------- Slice + Store -----------------
const today = new Date();
const priorDate = new Date();
priorDate.setDate(today.getDate() - 30);
const weekAgo = new Date();
weekAgo.setDate(today.getDate() - 7);
const formatDate = (date) => {
	const y = date.getFullYear();
	const m = String(date.getMonth() + 1).padStart(2, '0');
	const d = String(date.getDate()).padStart(2, '0');
	return `${y}-${m}-${d}`;
};

const slice = createSlice({
    name: 'properties',
    initialState: {
		timeSlotMonths:6,
		
		customers: [],
		suppliers: [],
		
		selectedInvoices: [], 
		
		currentCustomer:"",
		currentSupplier:"",
		
		currentCustomerInfo:"", 
		currentSuppleirInfo:"",
		
		loading: false, 
		refreshPayments: 0,
		toDate: formatDate(today),
		fromDate: formatDate(today),
		
		option:{label:"All",value:"all"},
		searchTerm: "",
		fullView: false,
	},
    reducers: {
        setCustomers: (state, action) => { state.customers = action.payload },
        setSuppliers: (state, action) => { state.suppliers = action.payload },

		setToDate: (state, action) => { state.toDate = action.payload },
		setFromDate: (state, action) => { state.fromDate = action.payload },

		setSelectedInvoices: (state, action) => { state.selectedInvoices = action.payload },

		setOption: (state, action) => { state.option = action.payload },

        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
        setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },

		setCurrentCustomerInfo: (state, action) => { state.currentCustomerInfo = action.payload; },
		setCurrentSupplierInfo: (state, action) => { state.currentSupplierInfo = action.payload; },

		setCustomersLoading: (state, action) => { state.loading = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now();
        },
		setSearchTerm: (state, action) => { state.searchTerm = action.payload },
		setFullView: (state, action) => { state.fullView = action.payload },
    },
});

const { setCustomers,setSuppliers, setSelectedInvoices, setToDate, setFromDate, setOption,
	setCurrentCustomer, setCurrentSupplier, setCurrentCustomerInfo, setCustomersLoading, setCurrentSupplierInfo,
	triggerPaymentRefresh, setFullView } = slice.actions;
	
const store = configureStore({
    reducer: { properties: slice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

export default function DailyBookSalesApp(props) {
	const dispatch = useDispatch();
	const { width } = useWindowSize();
	const isMobile = width < 600;
	const isTablet = width >= 600 && width < 768; // iPad (768+) now renders the desktop UI

	useEffect(() => {

    },[])

    const listMarginTop = '0';

    return (
	<>
	{/* Page title bar */}
	<div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom: isMobile ? '10px' : '0',background:'#fff',borderRadius: isMobile ? '14px' : '14px 14px 0 0',padding: isMobile ? '12px 14px' : '16px 20px',boxShadow: isMobile ? '0 1px 4px rgba(0,0,0,0.06)' : 'none',border:'1px solid #eaecf2',borderBottom: isMobile ? '1px solid #eaecf2' : 'none',flexDirection:'row',gap:'8px'}}>
		<div style={{display:'flex',alignItems:'center',gap:'12px',flex:1,minWidth:0}}>
			<div style={{width: isMobile ? '36px' : '40px',height: isMobile ? '36px' : '40px',borderRadius:'12px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:'0 4px 14px rgba(234,88,12,0.3)',flexShrink:0}}>
				<i className="fa fa-bar-chart" style={{fontSize: isMobile ? '14px' : '18px',color:'#fff'}}></i>
			</div>
			<div>
				<h2 style={{margin:0,fontSize: isMobile ? '17px' : '18px',fontWeight:'800',color:'#0f172a',lineHeight:'1.2',letterSpacing:'-0.3px',fontFamily:'inherit'}}>Sales</h2>
				<span style={{fontSize: isMobile ? '11px' : '12px',color:'#94a3b8',fontWeight:'500'}}>Manage invoices and track payments</span>
			</div>
		</div>
		<a href="/data_entry/sales_entry/invoice/" style={{background:'rgb(234, 88, 12)',color:'#fff',border:'none',borderRadius:'10px',padding: isMobile ? '0 14px' : '0 20px',height: isMobile ? '34px' : '40px',fontWeight:'700',fontSize: isMobile ? '12px' : '14px',boxShadow:'0 3px 10px rgba(234,88,12,0.3)',textDecoration:'none',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'8px',flexShrink:0}}>
			<i className="fa fa-plus"></i>{!isMobile && ' New Invoice'}{isMobile && ' New Invoice'}
		</a>
	</div>
	<style>{`
		.sales-action-btn:focus,
		.sales-action-btn:focus-visible,
		.sales-action-btn:active {
			outline: none !important;
			box-shadow: none !important;
		}
		.sales-dropdown-item:hover {
			background: #FFF5ED !important;
			color: rgb(234, 88, 12) !important;
		}
		input[type="date"]::-webkit-calendar-picker-indicator {
			opacity: 0.4;
			cursor: pointer;
		}
		input[type="date"]::-webkit-calendar-picker-indicator:hover {
			opacity: 0.7;
		}
		.sales-scroll-area { scrollbar-width: none; }
		.sales-scroll-area::-webkit-scrollbar { display: none; }
		.sales-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; }
		.sales-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; }
		.sales-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; border: none; }
	`}</style>
	<div className="row" style={isMobile ? {margin:'0 -8px'} : {}}>
		{/*<div className="col-4 col-md-12 mb-md-1">
			<SupplierSelect apiUrl={props.customerListApi} />
		</div>*/}
		<div className="col-12 col-md-12" style={isMobile ? {padding:'0 8px'} : {}}>
			<FilterAndOptionsPanel {...props} />
		</div>
		<div className="col-12" style={isMobile ? { marginTop:'8px', marginBottom: '70px', padding:'0 8px' } : { marginTop: listMarginTop, marginBottom: '70px' }}>
			<List {...props} />
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</>
    );
}

function FilterAndOptionsPanel(props) {
    const dispatch = useDispatch();
    const { customers, toDate, fromDate, currentCustomer, selectedInvoices, fullView } = useSelector(state => state.properties);
    const openInNewTab = useOpenInNewTab();
    const { width } = useWindowSize();
    const isMobile = width < 600;
    const isTablet = width >= 600 && width < 768;
    const isDesktop = width >= 768;
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [downloadingExcel, setDownloadingExcel] = useState(false);

    useEffect(() => {
        const fetchCustomers = async () => {
            try {
                setLoading(true);
                const response = await axios.get(props.customerListApi);
                if (response.data.success === true) {
                    dispatch(setCustomers(response.data.payload));
                }
            } catch (err) {
                console.error('Failed to load customers', err);
                setError('Failed to load customers');
            } finally {
                setLoading(false);
            }
        };
        fetchCustomers();
    }, [props.customerListApi, dispatch]);

    const customerOptions = customers.map(c => ({ value: c.id, label: c.name }))
        .sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' }));

    const handleCustomerChange = (selected) => {
        if (Array.isArray(selected) && selected.length > 0) {
            dispatch(setCurrentCustomer(selected.map(s => s.value)));
            dispatch(setCurrentCustomerInfo(selected.map(s => customers.find(c => c.id === s.value))));
        } else if (selected && !Array.isArray(selected)) {
            dispatch(setCurrentCustomer(selected.value));
            dispatch(setCurrentCustomerInfo(customers.find(c => c.id === selected.value)));
        } else {
            dispatch(setCurrentCustomer(null));
            dispatch(setCurrentCustomerInfo(null));
        }
    };

    const statementInvoice = (e) => {
        if (downloadingExcel) return;
        setDownloadingExcel(true);
        const qs = new URLSearchParams();
        if (currentCustomer) qs.set('customer_id', currentCustomer);
        if (fromDate) qs.set('start_date', fromDate);
        if (toDate) qs.set('end_date', toDate);
        const url = (props.statementApi || '/data_entry/sales_entry/daily_report/daily_book_sales/view/statement') + '?' + qs.toString();
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => setDownloadingExcel(false), 2500);
    };

    const printInvoice = (e) => {
        openInNewTab(props.printApi, {
            customer_id: currentCustomer,
            start_date: fromDate,
            end_date: toDate,
            invoices: selectedInvoices,
            type: e,
        });
    };

    const [emailModalOpen, setEmailModalOpen] = useState(false);
    const emailInvoice = () => setEmailModalOpen(true);

    /* ── Mobile button style — grid style with vertical dividers ── */
    const mobileBtnStyle = {
        display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',
        width:'100%',
        background:'transparent',border:'none',color:'#6b7280',
        padding:'6px 0',fontSize:'11px',fontWeight:'500',cursor:'pointer',
        whiteSpace:'nowrap',lineHeight:'1',gap:'4px',
        outline:'none',boxShadow:'none',
    };

    const dropdownMenuStyle = {borderRadius:'10px',boxShadow:'0 6px 20px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',padding:'4px'};

    /* ── Grid action buttons block — used by mobile ── */
    const actionButtonsGrid = (
        <div style={{display:'flex',borderTop:'1px solid #e5e7eb',margin:'0 -16px'}}>
            <button style={{...mobileBtnStyle,flex:1,borderRight:'1px solid #e5e7eb'}} type="button" onClick={() => emailInvoice('all')}>
                <i className="fa fa-envelope" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>
                <span>Send Email</span>
            </button>
            <button style={{...mobileBtnStyle,flex:1,borderRight:'1px solid #e5e7eb'}} type="button" onClick={() => printInvoice('all')}>
                <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>
                <span>Print</span>
            </button>
            <button style={{...mobileBtnStyle,flex:1}} type="button" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}>
                <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'14px',color:'#16a34a'}}></i>
                <span>{downloadingExcel ? 'Preparing…' : 'Excel'}</span>
            </button>
        </div>
    );

    const mobileSearchTerm = useSelector(state => state.properties.searchTerm) || '';
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const [pendingFrom, setPendingFrom] = useState(null);
    const [pendingTo, setPendingTo] = useState(null);
    const [pendingCustomer, setPendingCustomer] = useState(null);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [rangeStart, setRangeStart] = useState(null);
    const [rangeEnd, setRangeEnd] = useState(null);
    const [sMonthDd, setSMonthDd] = useState(false);
    // Mobile calendar mode: 'range' (From→To) or 'single' (one day). Same UX as Customer History.
    const [mobileDateMode, setMobileDateMode] = useState('range');
    const [sYearDd, setSYearDd] = useState(false);
    const toYMD = (d) => { const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); return y+'-'+m+'-'+dd; };
    const fmtDisp = (v) => { if (!v) return ''; const MON=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; const [y,m,d]=String(v).split('-').map(Number); if(!y||!m||!d) return ''; return `${String(d).padStart(2,'0')} ${MON[m-1]} ${y}`; };
    const handleRangeChange = (dates) => {
        let [s,e]=dates;
        // Ensure From <= To: if user picks an end date earlier than start, swap them.
        if (s && e && s > e) { const t=s; s=e; e=t; }
        setRangeStart(s); setRangeEnd(e||null);
        if(s) setPendingFrom(toYMD(s)); else setPendingFrom(null);
        if(e) setPendingTo(toYMD(e)); else if(!e && s) setPendingTo(null);
    };
    // Single-date mode: one click sets both From and To to the same day.
    const handleSingleChange = (d) => {
        const day = Array.isArray(d) ? d[0] : d;
        if (!day) return;
        setRangeStart(day); setRangeEnd(day);
        const ymd = toYMD(day);
        setPendingFrom(ymd); setPendingTo(ymd);
        setActivePreset(null);
    };
    const openSalesCalendar = () => { setRangeStart(pendingFrom?new Date(pendingFrom+'T00:00:00'):null); setRangeEnd(pendingTo?new Date(pendingTo+'T00:00:00'):null); setCalendarOpen(true); setMobileFilterOpen(false); };
    const [activePreset, setActivePreset] = useState(null);
    const applyMobilePreset = (label) => {
        // Custom Range → clear any preset selection so user picks dates manually
        if (label === 'Custom Range') {
            setPendingFrom(null); setPendingTo(null);
            setRangeStart(null); setRangeEnd(null);
            setActivePreset('Custom Range');
            return;
        }
        const now = new Date(); let from, to;
        if (label === 'Today') { from = to = now; }
        else if (label === 'Yesterday') { from = to = new Date(now.getTime()-86400000); }
        else if (label === 'Last 7d') { from = new Date(now.getTime()-6*86400000); to = now; }
        else if (label === 'This month') { from = new Date(now.getFullYear(), now.getMonth(), 1); to = now; }
        setPendingFrom(toYMD(from)); setPendingTo(toYMD(to));
        setRangeStart(from); setRangeEnd(to);
        setActivePreset(label);
    };
    const hasActiveFilter = !!(fromDate || toDate || currentCustomer);

    if (isMobile) {
        return (
            <>
            {/* ── Search + Filter row (no card wrapper on mobile) ── */}
            <div style={{marginBottom:'10px'}}>
                <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                    <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'38px',border:'1.5px solid #e5e7eb',borderRadius:'10px',background:'#fff',padding:'0 10px',minWidth:0}}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" placeholder="Search invoice, customer..."
                            value={mobileSearchTerm}
                            onChange={e => dispatch(slice.actions.setSearchTerm(e.target.value))}
                            style={{flex:1,border:'none',outline:'none',fontSize:'12px',color:'#374151',background:'transparent',minWidth:0}}
                        />
                        {!!mobileSearchTerm && (
                            <button type="button" onClick={() => dispatch(slice.actions.setSearchTerm(''))} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0}}>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        )}
                    </div>
                    <button type="button" onClick={() => { setPendingFrom(fromDate||null); setPendingTo(toDate||null); setPendingCustomer(currentCustomer||null); setMobileFilterOpen(v=>!v); }}
                        style={{flexShrink:0,height:'38px',width:'38px',borderRadius:'10px',border:'none',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none',boxShadow:'0 2px 6px rgba(234,88,12,0.3)'}}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        {hasActiveFilter && <span style={{position:'absolute',top:'4px',right:'4px',width:'7px',height:'7px',borderRadius:'50%',background:'#fff',border:'1.5px solid rgb(234, 88, 12)'}}/>}
                    </button>
                </div>
            </div>

            {/* ── Filter bottom sheet ── */}
            {mobileFilterOpen && (
                <>
                    <div onMouseDown={()=>setMobileFilterOpen(false)} onTouchStart={()=>setMobileFilterOpen(false)}
                        style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
                    <div onMouseDown={e=>e.stopPropagation()} onTouchStart={e=>e.stopPropagation()}
                        style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'92vh',overflowY:'auto'}}>
                        <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                            <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
                        </div>
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'8px 18px 12px'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'7px'}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Filters</span>
                            </div>
                            <button type="button" onClick={()=>setMobileFilterOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'8px',width:'28px',height:'28px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div style={{padding:'0 18px 18px',display:'flex',flexDirection:'column',gap:'16px'}}>
                            {/* Customer */}
                            <div>
                                <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Customer</div>
                                <Select styles={{
                                    control: (b,s) => ({...b,minHeight:'44px',height:'44px',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:'none',background:'#fff',cursor:'pointer',paddingLeft:'8px'}),
                                    valueContainer: b => ({...b,height:'44px',padding:'0 12px'}),
                                    indicatorsContainer: b => ({...b,height:'44px'}),
                                    indicatorSeparator: () => ({display:'none'}),
                                    dropdownIndicator: b => ({...b,padding:'0 8px 0 0',color:'#94a3b8'}),
                                    clearIndicator: b => ({...b,padding:'0 4px',color:'#cbd5e1'}),
                                    singleValue: b => ({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
                                    placeholder: b => ({...b,fontSize:'13px',color:'#94a3b8'}),
                                    menu: b => ({...b,borderRadius:'12px',border:'1px solid #eaecf2',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',zIndex:9999}),
                                    menuPortal: b => ({...b,zIndex:9999}),
                                    option: (b,s) => ({...b,fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#334155'}),
                                }}
                                components={{
                                    Control: ({children, ...cprops}) => {
                                        const active = cprops.isFocused || cprops.hasValue;
                                        return (
                                            <rsComponents.Control {...cprops}>
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke={active ? 'rgb(234, 88, 12)' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{marginLeft:'12px',flexShrink:0,transition:'stroke 0.15s'}}><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                                                {children}
                                            </rsComponents.Control>
                                        );
                                    },
                                }}
                                options={customerOptions} isClearable isSearchable value={pendingCustomer ? customerOptions.find(o=>o.value===pendingCustomer)||null : null}
                                onChange={v=>setPendingCustomer(v?.value||null)} placeholder="Select customer" menuPortalTarget={document.body} menuShouldScrollIntoView={false} />
                            </div>
                            {/* Date Range — single button opens calendar */}
                            <div>
                                <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date Range</div>
                                <button type="button" onClick={openSalesCalendar}
                                    style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'10px',cursor:'pointer',outline:'none'}}>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {pendingFrom&&pendingTo ? (
                                        <span style={{display:'flex',alignItems:'center',gap:'8px',flex:1}}>
                                            <span style={{fontSize:'13px',fontWeight:'600',color:'#1e293b'}}>{fmtDisp(pendingFrom)}</span>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                            <span style={{fontSize:'13px',fontWeight:'600',color:'#1e293b'}}>{fmtDisp(pendingTo)}</span>
                                        </span>
                                    ) : (
                                        <span style={{fontSize:'13px',fontWeight:'600',color:'#9ca3af',flex:1,textAlign:'left'}}>Select date range</span>
                                    )}
                                    <i className="fa fa-chevron-right" style={{fontSize:'10px',color:'#d1d5db'}}></i>
                                </button>
                            </div>
                            {/* Actions */}
                            <div style={{display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',paddingTop:'8px'}}>
                                <button type="button" onClick={()=>{ setPendingFrom(null); setPendingTo(null); setPendingCustomer(null); dispatch(setFromDate('')); dispatch(setToDate('')); dispatch(setCurrentCustomer(null)); setMobileFilterOpen(false); }}
                                    style={{height:'50px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'14px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',transition:'all 0.15s'}}
                                    onMouseDown={e=>{e.currentTarget.style.background='#f8fafc';}} onMouseUp={e=>{e.currentTarget.style.background='#fff';}}>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    Clear
                                </button>
                                <button type="button" onClick={()=>{ let f=pendingFrom, t=pendingTo; if(f&&t&&f>t){[f,t]=[t,f];} if(f) dispatch(setFromDate(f)); if(t) dispatch(setToDate(t)); if(pendingCustomer!==null) dispatch(setCurrentCustomer(pendingCustomer)); setMobileFilterOpen(false); }}
                                    style={{height:'50px',borderRadius:'14px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 6px 16px rgba(234,88,12,0.35)',transition:'all 0.15s'}}>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </>
            )}
            {/* Mobile date range calendar bottom sheet */}
            {calendarOpen && (<>
                <div onMouseDown={()=>setCalendarOpen(false)} style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.4)'}}/>
                <div onMouseDown={e=>e.stopPropagation()} style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'85vh',overflowY:'auto'}}>
                    <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}><div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/></div>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'4px 18px 14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{width:'30px',height:'30px',borderRadius:'9px',background:'#fff7ed',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>{mobileDateMode==='single'?'Select Date':'Select Date Range'}</span>
                        </div>
                        <button type="button" onClick={()=>setCalendarOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'50%',width:'30px',height:'30px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    {/* Single / Range toggle */}
                    <div style={{padding:'0 18px 14px'}}>
                        <div style={{display:'inline-flex',width:'100%',background:'#f4f4f6',borderRadius:'12px',padding:'4px',gap:'4px'}}>
                            {[{k:'single',label:'Single Date'},{k:'range',label:'Date Range'}].map(opt=>{
                                const on=mobileDateMode===opt.k;
                                return (<button key={opt.k} type="button" onClick={()=>{ setMobileDateMode(opt.k); if(opt.k==='single'){ setRangeEnd(rangeStart); if(pendingFrom) setPendingTo(pendingFrom); } }}
                                    style={{flex:1,border:'none',outline:'none',cursor:'pointer',borderRadius:'9px',padding:'9px 0',fontSize:'13.5px',fontWeight:on?'800':'600',background:on?'#fff':'transparent',color:on?'rgb(234, 88, 12)':'#6b7280',boxShadow:on?'0 1px 3px rgba(15,17,21,0.12)':'none',transition:'all 0.12s'}}>{opt.label}</button>);
                            })}
                        </div>
                    </div>
                    <div style={{padding:'0 18px 14px'}}>
                        {mobileDateMode==='single' ? (
                        <div style={{background:'#fff',border:'2px solid '+(pendingFrom?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'10px 14px',boxShadow:pendingFrom?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>Date</span>
                            </div>
                            <div style={{fontSize:'14px',fontWeight:'700',color:pendingFrom?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingFrom?fmtDisp(pendingFrom):'Select'}</div>
                        </div>
                        ) : (
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{flex:1,minWidth:0,background:'#fff',border:'2px solid '+(pendingFrom?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingFrom?'0 0 0 3px rgba(234,88,12,0.08)':'none',transition:'all 0.15s'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>From</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingFrom?'#0f172a':'#cbd5e1',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{pendingFrom?fmtDisp(pendingFrom):'Select'}</div>
                            </div>
                            <div style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 10px rgba(234,88,12,0.35)'}}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </div>
                            <div style={{flex:1,minWidth:0,background:'#fff',border:'2px solid '+(pendingTo?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingTo?'0 0 0 3px rgba(234,88,12,0.08)':'none',transition:'all 0.15s'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>To</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingTo?'#0f172a':'#cbd5e1',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{pendingTo?fmtDisp(pendingTo):'Select'}</div>
                            </div>
                        </div>
                        )}
                    </div>
                    {/* Quick preset chips — only in Range mode */}
                    {mobileDateMode==='range' && (
                    <div className="sp-presets" style={{display:'flex',gap:'8px',padding:'0 18px 14px',overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
                        {['Today','Yesterday','Last 7d','This month','Custom Range'].map(label => {
                            // Active if explicitly chosen OR if the current From/To range matches this preset's range
                            const presetRange = (() => {
                                const now = new Date(); let f, t;
                                if (label === 'Today') { f = t = now; }
                                else if (label === 'Yesterday') { f = t = new Date(now.getTime()-86400000); }
                                else if (label === 'Last 7d') { f = new Date(now.getTime()-6*86400000); t = now; }
                                else if (label === 'This month') { f = new Date(now.getFullYear(), now.getMonth(), 1); t = now; }
                                else return { f: null, t: null }; // Custom Range — no preset range
                                return { f: toYMD(f), t: toYMD(t) };
                            })();
                            const active = label === 'Custom Range' ? activePreset === 'Custom Range' : (activePreset === label || (pendingFrom===presetRange.f && pendingTo===presetRange.t));
                            return (
                                <button key={label} type="button" onClick={() => applyMobilePreset(label)}
                                    style={{flexShrink:0,height:'34px',padding:'0 16px',borderRadius:'999px',border: active ? 'none' : '1.5px solid #e5e7eb',background: active ? '#111827' : '#fff',color: active ? '#fff' : '#475569',fontSize:'13px',fontWeight:'700',cursor:'pointer',outline:'none',whiteSpace:'nowrap',transition:'all 0.15s'}}>
                                    {label}
                                </button>
                            );
                        })}
                    </div>
                    )}
                    <style>{`.sp-range .react-datepicker{width:100%;border:none;font-family:inherit;background:#fff !important;box-shadow:none !important}.sp-range .react-datepicker__month-container{width:100%;float:none;background:#fff !important}.sp-range .react-datepicker__month{background:#fff !important;margin:0 !important}.sp-range .react-datepicker__week{background:#fff !important}.sp-range .react-datepicker__header{background:#fff !important;border-bottom:none;padding:0}.sp-range .react-datepicker__header--custom{background:#fff !important;border-bottom:none !important;padding:0 !important}.sp-range .react-datepicker__day-names,.sp-range .react-datepicker__week{display:flex;justify-content:space-around}.sp-range .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0}.sp-range .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:42px;font-size:14px;font-weight:500;color:#334155;margin:0;border-radius:50%;transition:background 0.12s,color 0.12s;position:relative}.sp-range .react-datepicker__day:hover:not(.react-datepicker__day--selected):not(.react-datepicker__day--range-start):not(.react-datepicker__day--range-end){background:#f1f5f9;color:#0f172a}.sp-range .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.sp-range .react-datepicker__day--in-range,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start){background:transparent !important;color:rgb(234, 88, 12) !important;font-weight:600;position:relative}.sp-range .react-datepicker__day--in-range::before,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start)::before{content:'';position:absolute;top:4px;bottom:4px;left:0;right:0;background:#fff7f0;z-index:-1}.sp-range .react-datepicker__day--selected,.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--selecting-range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--selected,.sp-range .react-datepicker__day--today.react-datepicker__day--range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--range-end{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;position:relative;z-index:1}
/* range band behind start/end (half-inset like reference) */
.sp-range .react-datepicker__day--range-start:not(.react-datepicker__day--range-end)::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;right:0;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--range-end:not(.react-datepicker__day--range-start)::after{content:'';position:absolute;top:4px;bottom:4px;left:0;right:50%;background:#fff7f0;z-index:-2}
/* orange circle for selected/start/end */
.sp-range .react-datepicker__day--selected::before,.sp-range .react-datepicker__day--range-start::before,.sp-range .react-datepicker__day--range-end::before,.sp-range .react-datepicker__day--selecting-range-start::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50% !important}.sp-range .react-datepicker__day--outside-month{color:#d1d5db}.sp-range .react-datepicker__day--disabled{color:#e5e7eb !important;background:transparent !important}.sp-range .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b}.sp-range .react-datepicker__navigation{display:none !important}.sp-range .react-datepicker__current-month{display:none !important}.sp-dd{position:relative;display:inline-block}.sp-dd-btn{border:1.5px solid #e5e7eb;border-radius:9px;padding:7px 26px 7px 14px;font-size:13px;font-weight:700;color:#1e293b;cursor:pointer;outline:none;background:#f4f4f6;position:relative}.sp-dd-btn:focus,.sp-dd-btn:active{outline:none;border-color:rgb(234, 88, 12)}.sp-dd-btn::after{content:'';position:absolute;right:10px;top:50%;transform:translateY(-50%);border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #94a3b8}.sp-dd-list{position:absolute;top:calc(100% + 4px);left:50%;transform:translateX(-50%);background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:99;max-height:180px;overflow-y:auto;min-width:84px;padding:4px}.sp-dd-list::-webkit-scrollbar{width:3px}.sp-dd-list::-webkit-scrollbar-thumb{background:#fed7aa;border-radius:3px}.sp-dd-item{padding:6px 12px;font-size:12px;font-weight:600;border-radius:6px;cursor:pointer;text-align:center;color:#374151;transition:all 0.1s}.sp-dd-item:hover{background:#fff7ed;color:rgb(234, 88, 12)}.sp-dd-item.active{background:rgb(234, 88, 12);color:#fff;font-weight:700}.sp-presets{scrollbar-width:none;-ms-overflow-style:none}.sp-presets::-webkit-scrollbar{display:none;width:0;height:0}`}</style>
                    <div className="sp-range" style={{padding:'4px 16px 0'}}>
                        <ReactDatePicker inline selected={rangeStart} onChange={mobileDateMode==='single'?handleSingleChange:handleRangeChange} startDate={mobileDateMode==='single'?undefined:rangeStart} endDate={mobileDateMode==='single'?undefined:rangeEnd} selectsRange={mobileDateMode==='range'} maxDate={new Date()}
                            renderCustomHeader={({date,changeYear,changeMonth,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                const mnthsFull=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                const mnths=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                const cy=new Date().getFullYear(); const yrs=Array.from({length:10},(_,i)=>cy-5+i);
                                const nb={width:'34px',height:'34px',border:'1.5px solid #e5e7eb',borderRadius:'50%',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',outline:'none',flexShrink:0};
                                return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'12px',gap:'6px'}}>
                                    <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{...nb,opacity:prevMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                                    <div className="sp-dd" style={{flex:1,display:'flex',justifyContent:'center'}}>
                                        <button type="button" onClick={()=>{setSMonthDd(v=>!v);setSYearDd(false);}} style={{display:'inline-flex',alignItems:'center',gap:'7px',background:'transparent',border:'none',outline:'none',cursor:'pointer',fontSize:'16px',fontWeight:'800',color:'#0f172a',padding:'4px 8px'}}>
                                            {mnthsFull[date.getMonth()]} {date.getFullYear()}
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        {sMonthDd&&(
                                            <div className="sp-dd-list" style={{minWidth:'200px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'4px',padding:'8px'}}>
                                                <div style={{gridColumn:'1 / -1',display:'flex',alignItems:'center',justifyContent:'space-between',padding:'2px 4px 6px'}}>
                                                    <span style={{fontSize:'11px',fontWeight:'700',color:'#94a3b8'}}>MONTH</span>
                                                    <select value={date.getFullYear()} onChange={e=>changeYear(Number(e.target.value))} style={{border:'1.5px solid #e5e7eb',borderRadius:'7px',fontSize:'12px',fontWeight:'700',color:'#0f172a',padding:'3px 6px',outline:'none',cursor:'pointer'}}>
                                                        {yrs.map(y=><option key={y} value={y}>{y}</option>)}
                                                    </select>
                                                </div>
                                                {mnths.map((m,i)=><div key={m} className={'sp-dd-item'+(date.getMonth()===i?' active':'')} onClick={()=>{changeMonth(i);setSMonthDd(false);}}>{m}</div>)}
                                            </div>
                                        )}
                                    </div>
                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...nb,opacity:nextMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                </div>);
                            }}
                        />
                    </div>
                    <div style={{display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',padding:'8px 18px 16px'}}>
                        <button type="button" onClick={()=>{ setPendingFrom(null); setPendingTo(null); setRangeStart(null); setRangeEnd(null); setActivePreset(null); setCalendarOpen(false); setMobileFilterOpen(true); }}
                            style={{height:'52px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'15px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px'}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel
                        </button>
                        {(() => { const applyDisabled = mobileDateMode==='single' ? !pendingFrom : (!pendingFrom||!pendingTo); return (
                        <button type="button" onClick={()=>{setCalendarOpen(false);setMobileFilterOpen(true);}} disabled={applyDisabled}
                            style={{height:'52px',borderRadius:'14px',border:'none',background:applyDisabled?'#e2e8f0':'rgb(234, 88, 12)',color:applyDisabled?'#94a3b8':'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:applyDisabled?'default':'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:applyDisabled?'none':'0 6px 16px rgba(234,88,12,0.35)'}}>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply
                        </button>
                        ); })()}
                    </div>
                </div>
            </>)}
            {/* Action buttons: Email, Print, Statement — outside filter popup */}
            <div style={{display:'flex',gap:'10px',marginTop:'10px',marginBottom:'12px'}}>
                <button type="button" onClick={()=>emailInvoice('all')} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                    <i className="fa fa-envelope-o" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Email
                </button>
                <button type="button" onClick={()=>printInvoice('all')} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                    <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
                </button>
                <button type="button" onClick={()=>statementInvoice('excel')} disabled={downloadingExcel} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:downloadingExcel?'default':'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                    <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{downloadingExcel ? 'Preparing…' : 'Excel'}
                </button>
            </div>
            <DailyReportEmailModal
                open={emailModalOpen}
                onClose={() => setEmailModalOpen(false)}
                apiUrl={props.emailApi}
                listApi={props.listApi}
                reportTitle="Daily Sales Report"
                fromDate={fromDate}
                toDate={toDate}
                customerId={currentCustomer || ''}
            />
            </>
        );
    }

    /* ── Desktop / Tablet — layout ── */
    const desktopBtnPadding = isTablet ? '0 14px' : '0 18px';
    const desktopBtnGap = isTablet ? '8px' : '10px';
    const desktopDatePadding = isTablet ? '10px 14px' : '10px 18px';

    /* ── Tablet: use desktop layout (same filters) ── */
    if (false && isTablet) {
        const tBtnStyle = {display:'inline-flex',alignItems:'center',gap:'4px',height:'34px',background:'#fff',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',borderRadius:'8px',padding:'0 8px',fontSize:'11px',fontWeight:'600',cursor:'pointer',outline:'none',boxShadow:'none',whiteSpace:'nowrap'};
        const tDdMenu = {borderRadius:'10px',boxShadow:'0 6px 20px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',padding:'4px',minWidth:'auto',width:'auto'};
        const tDdItem = {borderRadius:'6px',fontSize:'13px',padding:'8px 16px',whiteSpace:'nowrap'};
        return (
            <div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',background:'#fff',padding:'14px 14px'}}>
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:'8px'}}>
                    {/* Date Range — no calendar icons, minimal padding */}
                    <div style={{display:'inline-flex',alignItems:'stretch',background:'#fff',border:'1.5px solid #e5e7eb',borderRadius:'10px',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',flexShrink:1,minWidth:0}}>
                        <div style={{padding:'6px 8px',borderRight:'1px solid #f0f0f0',display:'flex',alignItems:'center',gap:'5px'}}>
                            <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',fontSize:'10px',flexShrink:0}}></i>
                            <div>
                                <div style={{fontSize:'8px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.5px',textTransform:'uppercase',lineHeight:1}}>From</div>
                                <input
                                    type="date"
                                    value={fromDate}
                                    onChange={(e) => dispatch(setFromDate(e.target.value))}
                                    style={{border:'none',outline:'none',fontSize:'11px',fontWeight:'600',color:'#111827',padding:'0',background:'transparent',width:'95px',display:'block',fontFamily:'inherit',WebkitAppearance:'none',MozAppearance:'none'}}
                                />
                            </div>
                        </div>
                        <div style={{display:'flex',alignItems:'center',padding:'0 4px',fontSize:'11px'}}>
                            <i className="fa fa-long-arrow-right" style={{color:'rgb(234, 88, 12)',opacity:0.5}}></i>
                        </div>
                        <div style={{padding:'6px 8px',display:'flex',alignItems:'center',gap:'5px'}}>
                            <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',fontSize:'10px',flexShrink:0}}></i>
                            <div>
                                <div style={{fontSize:'8px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.5px',textTransform:'uppercase',lineHeight:1}}>To</div>
                                <input
                                    type="date"
                                    value={toDate}
                                    onChange={(e) => dispatch(setToDate(e.target.value))}
                                    style={{border:'none',outline:'none',fontSize:'11px',fontWeight:'600',color:'#111827',padding:'0',background:'transparent',width:'95px',display:'block',fontFamily:'inherit',WebkitAppearance:'none',MozAppearance:'none'}}
                                />
                            </div>
                        </div>
                    </div>
                    {/* Action Buttons */}
                    <div style={{display:'flex',alignItems:'center',gap:'5px',flexShrink:0}}>
                        <button className="sales-action-btn" style={tBtnStyle} type="button" onClick={() => emailInvoice('all')}>
                            <i className="fa fa-envelope" style={{fontSize:'10px'}}></i> Email
                        </button>
                        <button className="sales-action-btn" style={tBtnStyle} type="button" onClick={() => printInvoice('all')}>
                            <i className="fa fa-print" style={{fontSize:'10px'}}></i> Print
                        </button>
                        <button className="sales-action-btn" style={tBtnStyle} type="button" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}>
                            <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'10px'}}></i> {downloadingExcel ? 'Preparing…' : 'Excel'}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    /* ── Desktop — single row inline layout ── */
    if (isDesktop) {
        const iconBtnStyle = {
            width:'42px',height:'42px',borderRadius:'10px',border:'1px solid #e8e8ec',
            background:'#fff',color:'#6b7280',display:'flex',alignItems:'center',
            justifyContent:'center',cursor:'pointer',outline:'none',boxShadow:'none',
            transition:'all 0.15s',padding:0,
        };

        return (
            <div style={{borderRadius:'0',border:'1px solid #eaecf2',borderTop:'none',borderBottom:'none',boxShadow:'none',background:'#fff',padding: width < 1024 ? '10px 12px' : '12px 16px',display:'flex',alignItems:'center',gap: width < 1024 ? '8px' : '12px',width:'100%'}}>
                {/* ── Search ── */}
                <div style={{flex:'1 1 0%',maxWidth:'520px'}}>
                    <div style={{height:'42px',borderRadius:'10px',border:'1px solid #e8e8ec',background:'#fafafb',display:'flex',alignItems:'center',gap:'8px',padding:'0 12px',transition:'border-color 0.15s'}}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input
                            type="text"
                            placeholder="Search invoices…"
                            value={mobileSearchTerm}
                            onChange={(e) => dispatch(slice.actions.setSearchTerm(e.target.value))}
                            style={{flex:'1 1 0%',border:'none',outline:'none',background:'transparent',fontSize:'13.5px',color:'#0f1115',minWidth:0,padding:0,fontFamily:'inherit'}}
                        />
{!!mobileSearchTerm && <button type="button" onClick={() => dispatch(slice.actions.setSearchTerm(''))} style={{background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
                    </div>
                </div>

                {/* ── Customer Select ── */}
                <div style={{width:'260px',flexShrink:0}}>
                    {!loading && !error && (
                        <Select styles={{
                            ...orangeSelectStyles,
                            control: (base, state) => ({
                                ...orangeSelectStyles.control(base, state),
                                minHeight:'42px',borderRadius:'10px',
                                border: state.isFocused ? '1px solid rgb(234, 88, 12)' : '1px solid #e8e8ec',
                                background:'#fafafb',
                                boxShadow: state.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none',
                            }),
                            valueContainer: (base) => ({...base,padding:'2px 10px',flexWrap:'wrap'}),
                            indicatorsContainer: (base) => ({...base,minHeight:'38px'}),
                            placeholder: (base) => ({...base,fontSize:'12px',color:'#9ca3af'}),
                            multiValue: (base) => ({...base,background:'#fff7ed',borderRadius:'6px',border:'1px solid #fed7aa'}),
                            multiValueLabel: (base) => ({...base,fontSize:'11px',fontWeight:'600',color:'#c2410c',padding:'2px 6px'}),
                            multiValueRemove: (base) => ({...base,color:'rgb(234, 88, 12)',borderRadius:'0 6px 6px 0','&:hover':{background:'rgb(234, 88, 12)',color:'#fff'}}),
                            menu: (base) => ({...base,zIndex:50,borderRadius:'10px',boxShadow:'0 6px 20px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',marginTop:'4px'}),
                            menuList: (base) => ({...base,padding:'4px',maxHeight:'200px'}),
                            option: (base, state) => ({...orangeSelectStyles.option(base, state),fontSize:'12px',padding:'8px 12px',borderRadius:'6px',marginBottom:'2px'}),
                            input: (base) => ({...base,fontSize:'12px',color:'#111827',margin:0,padding:0}),
                            clearIndicator: (base) => ({...base,padding:'0 4px',color:'#9ca3af','&:hover':{color:'#ef4444'}}),
                        }}
                            options={customerOptions}
                            isClearable isSearchable={true}
                            isMulti
                            onChange={(selected) => handleCustomerChange(selected)}
                            components={{ DropdownIndicator: () => null, IndicatorSeparator: () => null }}
                            placeholder={<><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{marginRight:'6px',verticalAlign:'middle'}}><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>Customer</>}
                        />
                    )}
                </div>

                {/* ── Date Range Picker ── */}
                <div style={{flex:'0 0 auto',minWidth:0}}>
                    <DateRangePicker fromDate={fromDate} toDate={toDate} onFromChange={(val) => dispatch(setFromDate(val))} onToChange={(val) => dispatch(setToDate(val))} width={width} compact={true} variant="spec" />
                </div>

                {/* ── Action Icon Buttons ── */}
                <div style={{display:'flex',alignItems:'center',gap:'6px',marginLeft:'6px'}}>
                    <button className="sales-action-btn" style={iconBtnStyle} type="button" title="Email statements" onClick={() => emailInvoice('all')}
                        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
                        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                    </button>
                    <button className="sales-action-btn" style={iconBtnStyle} type="button" title="Print list" onClick={() => printInvoice('all')}
                        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
                        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
                    </button>
                    <button className="sales-action-btn" style={iconBtnStyle} type="button" title="Download Excel" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}
                        onMouseEnter={e => {if(!downloadingExcel){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                        onMouseLeave={e => {if(!downloadingExcel){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}}>
                        {downloadingExcel
                            ? <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                            : <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>}
                    </button>
                </div>
                <DailyReportEmailModal
                    open={emailModalOpen}
                    onClose={() => setEmailModalOpen(false)}
                    apiUrl={props.emailApi}
                    listApi={props.listApi}
                    reportTitle="Daily Sales Report"
                    fromDate={fromDate}
                    toDate={toDate}
                    customerId={currentCustomer || ''}
                />
            </div>
        );
    }

    /* ── Tablet / default — original layout ── */
    return (
        <div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',background:'#fff',padding:'24px 28px',overflow:'hidden'}}>
            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',flexWrap:'wrap',gap:'16px'}}>
                {/* Date Range */}
                <div style={{display:'inline-flex',alignItems:'stretch',background:'#fff',border:'1.5px solid #e5e7eb',borderRadius:'12px',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
                    <div style={{padding:'10px 18px',borderRight:'1px solid #f0f0f0',display:'flex',alignItems:'center',gap:'10px'}}>
                        <div style={{width:'32px',height:'32px',borderRadius:'8px',background:'#FFF5ED',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                            <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',fontSize:'13px'}}></i>
                        </div>
                        <div>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'2px'}}>From</div>
                            <OrangeDatePicker value={fromDate} onChange={(val) => dispatch(setFromDate(val))} />
                        </div>
                    </div>
                    <div style={{display:'flex',alignItems:'center',padding:'0 10px',color:'#d1d5db',fontSize:'14px'}}>
                        <i className="fa fa-long-arrow-right" style={{color:'rgb(234, 88, 12)',opacity:0.5}}></i>
                    </div>
                    <div style={{padding:'10px 18px',display:'flex',alignItems:'center',gap:'10px'}}>
                        <div style={{width:'32px',height:'32px',borderRadius:'8px',background:'#FFF5ED',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                            <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',fontSize:'13px'}}></i>
                        </div>
                        <div>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'2px'}}>To</div>
                            <OrangeDatePicker value={toDate} onChange={(val) => dispatch(setToDate(val))} />
                        </div>
                    </div>
                </div>
                {/* Action Buttons */}
                <div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
                    <button className="sales-action-btn" style={{display:'inline-flex',alignItems:'center',gap:'5px',height:'36px',background:'#fff',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',borderRadius:'8px',padding:'0 12px',fontSize:'12px',fontWeight:'600',cursor:'pointer',outline:'none',boxShadow:'none'}} type="button" onClick={() => emailInvoice('all')}>
                        <i className="fa fa-envelope" style={{fontSize:'11px'}}></i> Email
                    </button>
                    <button className="sales-action-btn" style={{display:'inline-flex',alignItems:'center',gap:'5px',height:'36px',background:'#fff',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',borderRadius:'8px',padding:'0 12px',fontSize:'12px',fontWeight:'600',cursor:'pointer',outline:'none',boxShadow:'none'}} type="button" onClick={() => printInvoice('all')}>
                        <i className="fa fa-print" style={{fontSize:'11px'}}></i> Print
                    </button>
                    <button className="sales-action-btn" style={{display:'inline-flex',alignItems:'center',gap:'5px',height:'36px',background:'#fff',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',borderRadius:'8px',padding:'0 12px',fontSize:'12px',fontWeight:'600',cursor:downloadingExcel?'default':'pointer',outline:'none',boxShadow:'none'}} type="button" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}>
                        <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'11px'}}></i> {downloadingExcel ? 'Preparing…' : 'Excel'}
                    </button>
                </div>
            </div>
            <DailyReportEmailModal
                open={emailModalOpen}
                onClose={() => setEmailModalOpen(false)}
                apiUrl={props.emailApi}
                listApi={props.listApi}
                reportTitle="Daily Sales Report"
                fromDate={fromDate}
                toDate={toDate}
                customerId={currentCustomer || ''}
            />
        </div>
    );
}

function SupplierSelect({ apiUrl, onSubmit }) {
    const dispatch = useDispatch();
    const customers = useSelector(state => state.properties.customers);
    const loading = useSelector(state => state.properties.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetchCustomers = async () => {
            try {
                //dispatch(setLoading(true));
                const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setCustomers(response.data.payload)); // store suppliers in Redux
				}
            } catch (err) {
                console.error('Failed to load suppliers', err);
            } finally {
                //dispatch(setLoading(false));
            }
        };
        fetchCustomers();
    }, [apiUrl, dispatch]);
	
	const options = [
		{ value: '', label: '-- Select Customer --' }, // 👈 fake empty option
		...customers.map(c => ({
			value: c.id,
			label: c.name,
		})).sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' })),
	];
	
	const handleChange = (selected) => {
        dispatch(setCurrentCustomer(selected ? selected.value : null));
		dispatch(setCurrentCustomerInfo(customers.find(c => c.id === selected.value)));
    };

    const formik = useFormik({
        initialValues: {
            supplier_id: { label: '', value: '' },
        },
        validationSchema: Yup.object({
            customer_id: Yup.object({
                label: Yup.string().required(),
                value: Yup.string().required('Customer is required'),
            }).required('Customer is required'),
        }),
        onSubmit: values => {
            onSubmit(values);
        },
    });

    return (
        <div className="card">
            <div className="card-body mb-0 pb-0">
                <form onSubmit={formik.handleSubmit}>
                    <div className="mb-1">
                        <label className="form-label">Select Customer*</label>
                        {loading ? (
                            <p>Loading...</p>
                        ) : error ? (
                            <p className="text-danger">{error}</p>
                        ) : (
                            <Select styles={orangeSelectStyles}
								options={options}
								isLoading={loading}
								isClearable
								isSearchable
								onChange={handleChange}
								classNamePrefix="react-select"
							/>
                        )}
                        <div style={{ minHeight: '1em' }}>
                            {formik.touched.customer_id && formik.errors.customer_id ? (
                                <div className="invalid-feedback d-block">{formik.errors.customer_id}</div>
                            ) : null}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}

function FiltersForm(props) {
    const apiUrl = props.apiUrl;
    const onSubmit = props.onSubmit;
	const dispatch = useDispatch();
	const {customers, toDate, fromDate, option} = useSelector(state => state.properties);
	const [open, setOpen] = useState(false);
	
	const handleChange = (e) => {
		formik.setFieldValue(option, e);
		dispatch(setOption(e))
	}
	
	const formik = useFormik({
        initialValues: {
            from_date: "",
			to_date: "",
			options: [
				{label: "All",value:"all"}, 
				{label:"Cash",value:"cash"},
				{label:"Credit",value:"credit"},
				{label:"Cheque",value:"cheque"},
				{label:"Bank Transfer",value:"bank transfer"}
			],
        },
        validationSchema: Yup.object({
            from_date: Yup.date().required('From Date is required'),
			to_date: Yup.date().required('To Date is required'),
			option: Yup.object({
                label: Yup.string().required(),
                value: Yup.string().required('Option is required'),
            }).required('option is required'),
        }),
        onSubmit: values => {
            onSubmit(values);
        },
    });	
	
	return (
        <div className="pb-0">
            {/* Header */}
            <div
                className="pb-0 text-right"
            >
                <div className='row'>
                    <div className='col-lg-10 col-md-8 text-right mt-1'>
                        <span className='p-1'><i className='fa fa-print'></i> <b>Print</b></span> |
                        <span className='p-1'><i className='fa fa-file-pdf-o'></i> <b>Download</b></span> |
                        <span className='p-1'><i className='fa fa-envelope-o'></i> <b>Email</b></span>
                    </div>
                    <div className='col-lg-2 col-md-4 text-right'>
                        <h5 
                        style={{ cursor: "pointer" }}
                        data-bs-toggle="collapse"
                        data-bs-target="#filtersFormCollapse"
                        onClick={() => setOpen(!open)}
                        className="m-0 btn btn-secondary w-100"><i className='fa fa-filter'></i> Filters
                        {/* Arrow rotation */}
                        <FontAwesomeIcon
                            icon={open ? faChevronDown : faChevronRight}
                            className="ms-2"
                            style={{ fontSize: "1.1rem", transition: "0.2s" }}
                        />
                        </h5>    
                    </div>    
                </div>
                
            </div>

            {/* Collapsible Body */}
            <div id="filtersFormCollapse" className="collapse mb-0 mt-1">
                <div className="card mb-0 pb-0">
                <div className="card-body mb-0 pb-0">
                    <form onSubmit={formik.handleSubmit}>
                        <div className="row g-3">
                            {/* Date */}
                            <div className="col-lg-6 col-md-6 mb-md-2">
                                <label className="form-label">From Date*</label>
                                <input
                                    type="date"
                                    className={`form-control ${formik.touched.from_date && formik.errors.from_date ? 'is-invalid' : ''}`}
                                    name="from_date"
                                    value={fromDate}
                                    onChange={(e) => {formik.handleChange(e); dispatch(setFromDate(e.target.value))}}
                                    onBlur={formik.handleBlur}
                                />
                                {formik.touched.from_date && formik.errors.from_date ? (
                                    <div className="invalid-feedback">{formik.errors.from_date}</div>
                                ) : null}
                            </div>
                            <div className="col-lg-6 col-md-6">
                                <label className="form-label">To Date*</label>
                                <input
                                    type="date"
                                    className={`form-control ${formik.touched.to_date && formik.errors.to_date ? 'is-invalid' : ''}`}
                                    name="to_date"
                                    value={toDate}
                                    onChange={(e) => {formik.handleChange(e); dispatch(setToDate(e.target.value))}}
                                    onBlur={formik.handleBlur}
                                />
                                {formik.touched.to_date && formik.errors.to_date ? (
                                    <div className="invalid-feedback">{formik.errors.to_date}</div>
                                ) : null}
                            </div>
                            {/*<div className="col-lg-4 col-md-12">
                                <label className="form-label">Options*</label>
                                <Select styles={orangeSelectStyles}
                                    options={formik.values.options}
                                    isClearable
                                    isSearchable
                                    onChange={handleChange}
                                    classNamePrefix="react-select"
                                    value={option}
                                />
                                {formik.touched.option && formik.errors.option ? (
                                    <div className="invalid-feedback">{formik.errors.option}</div>
                                ) : null}
                            </div>*/}
                        </div>
                    </form>
                </div>
                </div>
            </div>
        </div>
    );
	
}

function ExtraOptions(props) {
	const dispatch = useDispatch();
	const {currentCustomer, selectedInvoices, currentCustomerInfo, customers, toDate, fromDate, 
		option} = useSelector(state => state.properties);
		
	const [open, setOpen] = useState(false);
	const openInNewTab = useOpenInNewTab();
	
	const statementInvoice = (e) => {
		openInNewTab(props.statementApi, {
			customer_id: currentCustomer,
			start_date: fromDate,
			end_date: toDate,
		});
	}

	const printInvoice = (e) => {
		openInNewTab(props.printApi, {
			customer_id: currentCustomer,
			start_date: fromDate,
			end_date: toDate,
			invoices: selectedInvoices,
			type: e,
		});
	}

	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const emailInvoice = () => setEmailModalOpen(true);
	const _unused_emailInvoice_legacy = async (e) => {
		try {
			const params = new URLSearchParams({
				customer_id: currentCustomer || '',
				start_date: fromDate,
				end_date: toDate,
			});
			const response = await axios.get(`${props.emailApi}?${params.toString()}`);
			if (response.data.success) {
				toast.success(response.data.payload || 'Email sent successfully!', { position:'top-right', autoClose:3000, theme:'light' });
			} else {
				toast.warning(response.data.payload || 'Failed to send email', { position:'top-right', autoClose: 3000, theme:'light' });
			}
		} catch (err) {
			toast.warning('Failed to send email. Please try again.', { position:'top-right', autoClose: 3000, theme:'light' });
		}
	}
	
	return (
        <div className="card pb-0">
            {/* Header */}
            <div
                className="card-header d-flex justify-content-between align-items-center pb-0"
                style={{ cursor: "pointer" }}
                data-bs-toggle="collapse"
                data-bs-target="#extraOptionsCollapse"
                onClick={() => setOpen(!open)}
            >
                <h5 className="m-0">Extra Options
				{/* Arrow rotation */}
                <FontAwesomeIcon
                    icon={open ? faChevronDown : faChevronRight}
                    style={{ fontSize: "1.1rem", transition: "0.2s" }}
                />
				</h5>
            </div>

            {/* Collapsible Body */}
            <div id="extraOptionsCollapse" className="collapse mb-0">
                <div className="card-body">
					<div className="row g-3">
						<div className="col-4">
							<button className="btn btn-info w-100" type="button" onClick={() => emailInvoice('all')}>Send Email</button>
						</div>
						<div className="col-4">
							<button className="btn btn-info w-100" type="button" onClick={() => printInvoice('all')}>Print</button>
						</div>
						<div className="col-4">
							<button className="btn btn-info w-100" type="button" onClick={() => statementInvoice('pdf')}>Statement</button>
						</div>
					</div>
				</div>
            </div>
			<DailyReportEmailModal
				open={emailModalOpen}
				onClose={() => setEmailModalOpen(false)}
				apiUrl={props.emailApi}
				listApi={props.listApi}
				reportTitle="Daily Sales Report"
				fromDate={fromDate}
				toDate={toDate}
				customerId={currentCustomer || ''}
			/>
		</div>
	);
}

function ActionsDropdown({ row }) {
  const { width } = useWindowSize();
  const isMobile = width < 600;

  const itemStyle = {
    fontSize:'13px',padding: isMobile ? '9px 14px' : '7px 12px',
    display:'flex',alignItems:'center',gap:'8px',borderRadius:'6px',
  };

  const iconStyle = {
    width: isMobile ? '38px' : '30px',height: isMobile ? '38px' : '30px',
    borderRadius:'7px',background:'#fff',border:'1px solid #e8e8ec',color:'#6b7280',
    display:'flex',alignItems:'center',justifyContent:'center',
    cursor:'pointer',padding:0,outline:'none',boxShadow:'none',
    transition:'all 0.15s',textDecoration:'none',flexShrink:0,
  };

  return (
    <div style={{display:'flex',gap:'6px',justifyContent:'flex-end'}}>
      <a href={`/data_entry/sales_entry/invoice/invoiceview/${row.id}`} target="_blank" title="Print Invoice"
        style={iconStyle}
        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
      </a>
      <a href={`/data_entry/sales_entry/invoice/invoiceexcel/${row.id}`} title="Download Invoice (Excel)"
        style={iconStyle}
        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
      </a>
      <a href={`/data_entry/sales_entry/invoice/${row.id}`} title="Edit Invoice"
        style={iconStyle}
        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </a>
    </div>
  );
}

function List(props) {
    const dispatch = useDispatch();
    const { currentCustomer, toDate, fromDate, option, fullView } =
        useSelector(state => state.properties);
    const { width } = useWindowSize();

    const [data, setData] = useState([]);
    const [page, setPage] = useState(1);
    // Loading flag — true while the list AJAX is in flight. Shows a spinner overlay over the table
    // so the user knows the data is being refreshed when they change filters/dates.
    const [isLoading, setIsLoading] = useState(false);
    const searchTerm = useSelector(state => state.properties.searchTerm);

    const isMobile = width < 600;
    const isTablet = width >= 600 && width < 768; // iPad (768+) now renders the desktop UI
    const [mobileTableView, setMobileTableView] = useState(() => localStorage.getItem('ts_sales_view') === 'table');
    const [expandedCard, setExpandedCard] = useState(null);
    const [showColFilter, setShowColFilter] = useState(false);
    const colFilterRef = useRef(null);
    const [payTip, setPayTip] = useState({show:false, entries:[], x:0, y:0, align:'center'});
    const payTipPortalRef = useRef(null);
    const [payTipPortalReady, setPayTipPortalReady] = useState(false);
    useEffect(() => {
        const d = document.createElement('div');
        document.body.appendChild(d);
        payTipPortalRef.current = d;
        setPayTipPortalReady(true);
        return () => { if (d.parentNode) d.parentNode.removeChild(d); payTipPortalRef.current = null; };
    }, []);
    const showPayTip = (e, entries) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = rect.left + rect.width/2;
        const vw = window.innerWidth;
        const align = x > vw - 160 ? 'right' : x < 160 ? 'left' : 'center';
        setPayTip({show:true, entries, x, y: rect.bottom + 8, align});
    };
    const hidePayTip = () => setPayTip(s => ({...s, show:false}));
    // Payments "+N" hover tooltip — defined once so BOTH the desktop/tablet DataTable return
    // and the mobile card return can mount it (the early desktop return previously omitted it,
    // so hovering only showed the cursor:help "?" with no tooltip).
    const payTipTotal = (payTip.entries || []).reduce((s, e) => s + (e.total || 0), 0);
    const payTipDotColor = { 'Cash':'#22c55e', 'Card':'#3b82f6', 'Cheque':'#a78bfa', 'Bank Transfer':'#22d3ee', 'Credit':'#fb923c' };
    const fmtMoney = (n) => '£' + (n || 0).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2});
    const payTipPortal = (payTip.show && payTipPortalReady && payTipPortalRef.current) ? ReactDOM.createPortal(
        <div style={{position:'fixed',left:payTip.x,top:payTip.y,
            transform: payTip.align==='right' ? 'translateX(-90%)' : payTip.align==='left' ? 'translateX(-10%)' : 'translateX(-50%)',
            background:'#0f172a',border:'1px solid rgba(255,255,255,0.07)',color:'#fff',padding:'12px 14px',borderRadius:'12px',whiteSpace:'normal',zIndex:99999,boxShadow:'0 12px 30px rgba(2,6,23,0.45)',pointerEvents:'none',minWidth:'200px'}}>
            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'11px'}}>
                <span style={{fontSize:'10px',fontWeight:'800',color:'#94a3b8',letterSpacing:'0.9px',textTransform:'uppercase'}}>Payments</span>
                <span style={{fontSize:'10px',fontWeight:'700',color:'#cbd5e1',background:'rgba(255,255,255,0.08)',borderRadius:'999px',padding:'1px 7px'}}>{payTip.entries.length}</span>
            </div>
            <div style={{display:'grid',gridTemplateColumns:'1fr auto',alignItems:'center',rowGap:'10px',columnGap:'16px'}}>
                {payTip.entries.map(({mode, total, style:st}, i) => (
                    <React.Fragment key={i}>
                        <span style={{display:'inline-flex',alignItems:'center',gap:'9px'}}>
                            <span style={{width:'8px',height:'8px',borderRadius:'50%',background:(payTipDotColor[mode] || st.color),flexShrink:0}}></span>
                            <span style={{fontSize:'12px',fontWeight:'600',color:'#e2e8f0'}}>{mode}</span>
                        </span>
                        <span style={{fontSize:'12px',fontWeight:'700',color:'#fff',textAlign:'right',fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace'}}>{fmtMoney(total)}</span>
                    </React.Fragment>
                ))}
                <div style={{gridColumn:'1 / -1',height:'1px',background:'rgba(255,255,255,0.08)',margin:'2px 0'}}></div>
                <span style={{fontSize:'11px',fontWeight:'700',color:'#94a3b8'}}>Total</span>
                <span style={{fontSize:'13px',fontWeight:'800',color:'#fff',textAlign:'right',fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace'}}>{fmtMoney(payTipTotal)}</span>
            </div>
            <div style={{position:'absolute',bottom:'100%',
                left: payTip.align==='right' ? '85%' : payTip.align==='left' ? '15%' : '50%',
                transform:'translateX(-50%)',border:'6px solid transparent',borderBottomColor:'#0f172a'}}></div>
        </div>,
        payTipPortalRef.current
    ) : null;
    const [visibleCols, setVisibleCols] = useState(() => {
        try { const s = localStorage.getItem('ts_sales_cols'); if (s) return JSON.parse(s); } catch(e) {}
        return { date: true, customer: true, amount: true, status: true, payments: false };
    });
    const toggleCol = (col) => { const next = { ...visibleCols, [col]: !visibleCols[col] }; setVisibleCols(next); localStorage.setItem('ts_sales_cols', JSON.stringify(next)); };
    useEffect(() => {
        if (!showColFilter) return;
        const handler = (e) => { if (colFilterRef.current && !colFilterRef.current.contains(e.target)) setShowColFilter(false); };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [showColFilter]);

    const customStyles = useDataTableStyles();

    const mergedStyles = useMemo(() => ({
        ...customStyles,
        table: { ...customStyles?.table, style: { ...(customStyles?.table?.style || {}), overflow:'visible', minHeight:'250px', tableLayout:'fixed', width:'100%' } },
        headRow: { ...customStyles?.headRow, style: { ...(customStyles?.headRow?.style || {}), backgroundColor:'#fafbfc', borderBottomColor:'#eef2f7', borderBottomWidth:'1px', borderBottomStyle:'solid', overflow:'visible', minHeight:'42px' } },
        headCells: { ...customStyles?.headCells, style: { ...(customStyles?.headCells?.style || {}), fontSize:'10.5px', fontWeight:'800', color:'#1f2937', letterSpacing:'0.7px', textTransform:'uppercase', padding: isTablet ? '10px 8px' : '12px 14px', whiteSpace:'nowrap' } },
        rows: { ...customStyles?.rows, style: { ...(customStyles?.rows?.style || {}), overflow:'visible', borderBottomColor:'#f0f0f2', fontSize:'13px', minHeight:'56px' }, highlightOnHoverStyle: { backgroundColor:'#fffaf5', borderBottomColor:'#f0f0f2', outlineColor:'#fed7aa' } },
        cells: { ...customStyles?.cells, style: { ...(customStyles?.cells?.style || {}), overflow:'visible', padding: isTablet ? '10px 8px' : '14px', display:'flex', alignItems:'center', color:'#374151' } },
    }), [customStyles]);

    const searchFields = [
        "created_at",
        "id",
        "other_invoice_id",
        "customer.name",
        "total"
    ];

    const filteredData = useMemo(() => {
        if (!searchTerm) return data;
        const term = searchTerm.toLowerCase();
        return data.filter(row =>
            searchFields.some(field => String(_.get(row, field, ""))
                .toLowerCase()
                .includes(term))
        );
    }, [data, searchTerm]);

    // Load history
    useEffect(() => {
        let cancelled = false;
        const fetchData = async () => {
            setIsLoading(true);
            try {
                const response = await axios.post(props.listApi, {
                    customer_id: currentCustomer,
                    end_date: toDate,
                    start_date: fromDate,
                    option: option.value
                });
                if (cancelled) return;
                if (response.data.success) {
                    setData(response.data.payload);
                    setPage(1);
                }
            } catch (err) {
                if (!cancelled) console.error("Failed to load history", err);
            } finally {
                if (!cancelled) setIsLoading(false);
            }
        };
        fetchData();
        return () => { cancelled = true; };
    }, [currentCustomer, toDate, fromDate]);

    useEffect(() => { setPage(1); }, [searchTerm]);

    const toNum = v => Number(v) || 0;

    const formatAmount = (amount) => {
        const num = Number(amount);
        if (!num || num === 0) return null;
        return num.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const paidOrNot = (row) => {
        const alertIcon = <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>;
        const checkIcon = <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>;
        const clockIcon = <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>;
        const pill = (bg,ink,line) => ({display:'inline-flex',alignItems:'center',gap:'5px',padding:'3px 9px',borderRadius:'999px',background:bg,color:ink,border:`1px solid ${line}`,fontSize:'11px',fontWeight:'700',letterSpacing:'0.1px',lineHeight:'1.4',whiteSpace:'nowrap'});
        if (row.paid_type === 'not-paid')
            return <span style={pill('#fef2f2','#b91c1c','#f8d2d2')}>{alertIcon} Unpaid</span>;
        if (row.paid_type === 'partial-paid')
            return <span style={pill('#fef7e5','#b45309','#f5d98c')}>{clockIcon} Partial</span>;
        if (row.paid_type === 'overpaid')
            return <span style={pill('#fef2f2','#b91c1c','#f8d2d2')}>{alertIcon} Credit</span>;
        if (row.paid_type === 'all-paid')
            return <span style={pill('#e8f8ee','#15803d','#bde5c9')}>{checkIcon} Paid</span>;
        return null;
    };

    const paymentsDisplay = (payments) => {
        const totals = {};
        (payments || []).forEach(item => {
            const rawMode = item.payment_mode_type || item.payment_mode?.type;
            if (!rawMode || rawMode === 'Unknown') return;
            totals[rawMode] = (totals[rawMode] || 0) + (parseFloat(item.total_amount) || 0);
        });
        const entries = Object.entries(totals);
        if (entries.length === 0) return <span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>;
        const modeStyle = (mode) => {
            const styles = {
                'Cash':          { bg:'#f0fdf4', color:'#15803d', border:'#86efac', icon:'fa-money' },
                'Card':          { bg:'#eff6ff', color:'#1d4ed8', border:'#93c5fd', icon:'fa-credit-card' },
                'Cheque':        { bg:'#f5f3ff', color:'#6d28d9', border:'#c4b5fd', icon:'fa-file-text-o' },
                'Bank Transfer': { bg:'#ecfeff', color:'#0e7490', border:'#67e8f9', icon:'fa-university' },
                'Credit':        { bg:'#fff7ed', color:'rgb(234, 88, 12)', border:'#fed7aa', icon:'fa-rotate-left' },
            };
            return styles[mode] || { bg:'#f8fafc', color:'#475569', border:'#e2e8f0', icon:'fa-circle' };
        };
        const [firstMode, firstTotal] = entries[0];
        const s = modeStyle(firstMode);
        const remaining = entries.length - 1;
        const styledEntries = entries.map(([m, t]) => ({mode:m, total:t, style:modeStyle(m)}));
        return (
            <div style={{display:'flex',alignItems:'center',gap:'4px',flexWrap:'nowrap'}}
                onMouseEnter={remaining > 0 ? (e) => showPayTip(e, styledEntries) : undefined}
                onMouseLeave={remaining > 0 ? hidePayTip : undefined}>
                <div style={{display:'inline-flex',alignItems:'center',gap:'5px',background:s.bg,border:`1px solid ${s.border}`,borderRadius:'6px',padding:'2px 7px 2px 5px',width:'fit-content'}}>
                    <i className={`fa ${s.icon}`} style={{fontSize:'9px',color:s.color,opacity:0.8}}></i>
                    <span style={{fontSize:'10px',fontWeight:'600',color:s.color,whiteSpace:'nowrap'}}>{firstMode}</span>
                    <span style={{fontSize:'10px',fontWeight:'700',color:s.color,borderLeft:`1px solid ${s.border}`,paddingLeft:'5px',marginLeft:'1px',whiteSpace:'nowrap'}}>{firstTotal.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
                </div>
                {remaining > 0 && (
                    <span style={{fontSize:'10px',fontWeight:'700',color:'#6b7280',background:'#f3f4f6',border:'1px solid #e5e7eb',borderRadius:'10px',padding:'2px 7px',cursor:'help',whiteSpace:'nowrap'}}>
                        +{remaining}
                    </span>
                )}
            </div>
        );
    };

    const headerStyle = {fontSize:'10.5px',fontWeight:'800',color:'#1f2937',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace: isTablet ? 'normal' : 'nowrap'};

    const columns = [
        { name:<span style={headerStyle}>Invoice No.</span>, selector:row=>row.other_invoice_id||row.id, cell:row=>(
            <a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{textDecoration:'none'}}>
                <span style={{display:'inline-block',background:'#fff7f0',border:'1px solid #f6c9a8',borderRadius:'6px',padding:'3px 9px',color:'rgb(234, 88, 12)',fontWeight:'800',fontSize:'13px',fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',whiteSpace:'nowrap'}}>#{row.other_invoice_id||row.id}</span>
            </a>
        ), sortable:true, minWidth:'110px', width:'110px', grow:0 },
        { name:<span style={headerStyle}>Date</span>, selector:row=>row.created_at, cell:row=>(
            <span style={{fontSize:'13px',color:'#374151',whiteSpace:'nowrap'}}>{row.created_at}</span>
        ), sortable:true, minWidth:'115px', width:'115px', grow:0 },
        { name:<span style={headerStyle}>Customer</span>, selector:row=>row.customer?.name||'', cell:row=>(
            <a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{color:'#0f1115',fontWeight:'700',textDecoration:'none',fontSize:'13px',whiteSpace:'nowrap'}}>
                {row.customer?.name||'Guest'}
            </a>
        ), sortable:true, minWidth:'130px', grow:1 },
        { name:<span style={headerStyle}>Total</span>, selector:row=>Number(row.total)||0, cell:row=>{
            const total = Number(row.total) || 0;
            return <span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontWeight:'800',fontSize:'13px',color:'#0f1115',whiteSpace:'nowrap'}}>£{total.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>;
        }, sortable:true, right:true, minWidth:'120px', width:'120px', grow:0 },
        { name:<span style={headerStyle}>Paid</span>, selector:row=>Number(row.total_paid)||0, cell:row=>{const p=Number(row.total_paid)||0;return p>0?<span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontSize:'13px',fontWeight:'800',color:'#15803d',whiteSpace:'nowrap'}}>£{p.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>:<span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontSize:'13px',color:'#9ca3af'}}>—</span>;}, sortable:true, right:true, minWidth:'110px', width:'110px', grow:0 },
        { name:<span style={headerStyle}>Status</span>, selector:row=>row.paid_type||'', cell:row=>paidOrNot(row), sortable:true, center:true, minWidth:'110px', width:'110px', grow:0 },
        { name:<span style={headerStyle}>Payments</span>, selector:row=>(row.payments_list||row.payments)?.length||0, cell:row=>paymentsDisplay(row.payments_list||row.payments), sortable:false, minWidth:'160px', width:'160px', grow:0 },
        { name:<span style={headerStyle}>Ref #</span>, selector:row=>row.other_invoice_id||'', cell:row=>row.other_invoice_id?<span style={{fontSize:'12px',color:'#6b7280',fontWeight:'500',whiteSpace:'nowrap'}}>{row.other_invoice_id}</span>:<span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>, sortable:true, minWidth:'100px', width:'100px', grow:0, omit:!fullView },
        { name:<span style={headerStyle}>Salesman</span>, selector:row=>row.salesman?.name||'', cell:row=>row.salesman?.name?<span style={{fontSize:'12px',color:'#374151',fontWeight:'500',whiteSpace:'nowrap'}}>{row.salesman.name}</span>:<span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>, sortable:true, minWidth:'120px', width:'120px', grow:0, omit:!fullView },
        { name:<span style={headerStyle}>Products</span>, selector:row=>row.products_count||0, cell:row=><span style={{fontSize:'12px',color:'#374151',fontWeight:'600',whiteSpace:'nowrap'}}>{row.products_count??0}</span>, sortable:true, minWidth:'90px', width:'90px', grow:0, omit:!fullView },
        { name:<span style={headerStyle}>Balance</span>, selector:row=>(Number(row.total)||0)-(Number(row.total_paid)||0), cell:row=>{const b=(Number(row.total)||0)-(Number(row.total_paid)||0);if(b<=0)return<span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>;return<span style={{fontSize:'12px',fontWeight:'700',color:'#ef4444',whiteSpace:'nowrap'}}>{b.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>;}, sortable:true, right:true, minWidth:'100px', width:'100px', grow:0, omit:!fullView },
        { name:<span style={headerStyle}>Notes</span>, selector:row=>row.notes||'', cell:row=>row.notes?<span style={{fontSize:'12px',color:'#374151',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',maxWidth:'160px'}} title={row.notes}>{row.notes}</span>:<span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>, sortable:false, minWidth:'160px', grow:1, omit:!fullView },
        { name:'', cell:row=>(<ActionsDropdown row={row} />), sortable:false, right:true, minWidth:'100px', width:'100px', grow:0 },
    ];

    useDropdownFix();

    // ── Desktop / Tablet: original DataTable ──
    if (!isMobile) {
        return (
            <div style={{borderRadius:'0 0 14px 14px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',background:'#fff',overflow:'hidden'}}>
                <div style={{overflowX:'auto', position:'relative'}}>
                    <div className="sales-scroll-inner" style={{transition:'transform 0.2s ease', minWidth: fullView ? '1200px' : (isTablet ? '900px' : '850px')}}>
                        <DataTable
                            columns={columns}
                            data={filteredData}
                            responsive={false}
                            paginationPerPage={10}
                            paginationRowsPerPageOptions={[10,25,50,100]}
                            pagination
                            paginationComponent={SpecPagination}
                            highlightOnHover
                            customStyles={mergedStyles}
                            progressPending={isLoading && data.length === 0}
                            progressComponent={<SpecTableLoading label="Loading invoices…" />}
                            noDataComponent={
                                <div style={{textAlign:'center',padding:'40px 20px',width:'100%'}}>
                                    <div style={{width:'60px',height:'60px',margin:'0 auto 12px',borderRadius:'14px',background:'#fafafb',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}>
                                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                                    </div>
                                    <div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No records found</div>
                                    <div style={{fontSize:'13px',color:'#6b7280',marginTop:'4px',maxWidth:'380px',marginInline:'auto'}}>Try changing the date range or search term. New invoices appear here as soon as you create them.</div>
                                </div>
                            }
                        />
                    </div>
                    {/* Subtle dimming overlay over already-rendered rows while a refresh is in flight */}
                    {isLoading && data.length > 0 && (
                        <div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
                            <div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
                                <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                                <span>Loading invoices…</span>
                            </div>
                        </div>
                    )}
                </div>
                {isTablet && filteredData.length > 0 && !fullView && (
                    <div style={{padding:'0 12px 8px'}}>
                        <input type="range" min="0" max="100" defaultValue="0" className="sales-range-scroll"
                            onChange={(e) => {
                                const inner = document.querySelector('.sales-scroll-inner');
                                if (!inner) return;
                                const maxMove = inner.scrollWidth - inner.parentElement.clientWidth;
                                inner.style.transform = 'translateX(-' + (e.target.value / 100 * maxMove) + 'px)';
                            }}
                        />
                    </div>
                )}
                {payTipPortal}
            </div>
        );
    }

    // ── Mobile: card view ──

    // Pagination
    const perPage = 10;
    const totalPages = Math.ceil(filteredData.length / perPage);
    const paginatedData = filteredData.slice((page - 1) * perPage, page * perPage);

    // While the first fetch is in flight (no rows yet) show the loader, not the empty
    // state — otherwise the user sees "No records found" before any data has arrived.
    if (isLoading && filteredData.length === 0) {
        return (
            <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03)'}}>
                <SpecTableLoading label="Loading invoices…" />
            </div>
        );
    }
    if (filteredData.length === 0) {
        return (
            <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03)',padding:'40px 24px',textAlign:'center'}}>
                <div style={{width:'60px',height:'60px',margin:'0 auto 12px',borderRadius:'14px',background:'#fafafb',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No records found</div>
                <div style={{fontSize:'13px',color:'#6b7280',marginTop:'4px',maxWidth:'380px',marginInline:'auto'}}>Try changing the date range or search term. New invoices appear here as soon as you create them.</div>
            </div>
        );
    }

    // Mobile columns for table view — filtered by visibleCols
    const colKeyMap = {'Invoice No.':'invoice','Date':'date','Customer':'customer','Amount':'amount','Status':'status','Payments':'payments'};
    const mobileColumns = columns.filter(c => {
        if (c.omit) return false;
        const hdr = c.name?.props?.children || '';
        const key = colKeyMap[hdr];
        if (!key) return true; // actions column or unknown — always show
        if (key === 'invoice') return true; // always show invoice (locked)
        return visibleCols[key] !== false;
    }).map(c => {
        // On mobile the action icons are 38px (vs 30px desktop): 3 icons + gaps need ~140px,
        // and the status pill needs room not to clip. The shared desktop widths are too tight
        // here, so widen just these two columns for the mobile table view.
        const hdr = c.name?.props?.children;
        if (hdr === 'Status') return { ...c, width:'130px', minWidth:'130px' };
        if (!hdr) return { ...c, width:'140px', minWidth:'140px' }; // actions column (empty header)
        return c;
    });

    // Sum the visible columns' widths so the inner table is exactly as wide as its
    // columns. A fixed minWidth left dead space when a column was hidden, which made
    // the right-aligned Actions cell overlap the Status cell under tableLayout:fixed.
    const mobileMinWidth = mobileColumns.reduce((sum, c) => sum + parseInt(c.width || c.minWidth || '120', 10), 0);

    return (
        <div>
            {/* ── View Switcher + Column Filter ── */}
            <div style={{display:'flex',justifyContent:'flex-end',alignItems:'center',gap:'8px',marginBottom: isMobile ? '12px' : '8px'}}>
                {mobileTableView && (
                    <div ref={colFilterRef} style={{position:'relative'}}>
                        <button onClick={() => setShowColFilter(!showColFilter)} style={{display:'inline-flex',alignItems:'center',gap:'5px',height:'30px',padding:'0 10px',borderRadius:'8px',border:'1.5px solid #e2e8f0',background: showColFilter ? '#fff7ed' : '#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                            <i className="fa fa-columns" style={{fontSize:'11px',color: showColFilter ? 'rgb(234, 88, 12)' : '#64748b'}}></i>
                            <span style={{fontSize:'12px',fontWeight:'700',color: showColFilter ? 'rgb(234, 88, 12)' : '#64748b'}}>Columns</span>
                        </button>
                        {showColFilter && (() => {
                            const colItems = [{key:'invoice',label:'Invoice No.',fixed:true},{key:'date',label:'Date'},{key:'customer',label:'Customer'},{key:'amount',label:'Amount'},{key:'status',label:'Status'},{key:'payments',label:'Payments'},{key:'actions',label:'Actions',fixed:true}];
                            const checkedCount = colItems.filter(c => c.fixed || visibleCols[c.key] !== false).length;
                            return (
                            <div style={{position:'absolute',top:'40px',...(isMobile ? {left:0} : {right:0}),background:'#fff',borderRadius:'14px',boxShadow:'0 8px 28px rgba(15,23,42,0.16)',border:'1px solid #eef0f3',padding:'14px 8px 8px',zIndex:9999,minWidth:'210px'}}>
                                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'0 10px 10px'}}>
                                    <span style={{fontSize:'11px',fontWeight:'800',color:'#64748b',letterSpacing:'0.7px',textTransform:'uppercase'}}>Show Columns</span>
                                    <span style={{fontSize:'12px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{checkedCount}/{colItems.length}</span>
                                </div>
                                {colItems.map(({key,label,fixed}) => {
                                    const isChecked = fixed || visibleCols[key] !== false;
                                    return (
                                    <label key={key} style={{display:'flex',alignItems:'center',gap:'11px',padding:'9px 10px',borderRadius:'8px',cursor: fixed ? 'default' : 'pointer',fontSize:'14px',fontWeight:'600',color: fixed ? '#1e293b' : (isChecked ? '#1e293b' : '#94a3b8')}} onClick={() => !fixed && toggleCol(key)}>
                                        <div style={{width:'22px',height:'22px',borderRadius:'6px',border: isChecked ? '2px solid rgb(234, 88, 12)' : '2px solid #d1d5db',background: isChecked ? 'rgb(234, 88, 12)' : '#fff',display:'flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}>
                                            {isChecked && <i className="fa fa-check" style={{fontSize:'12px',color:'#fff'}}></i>}
                                        </div>
                                        {label}
                                    </label>
                                    );
                                })}
                            </div>
                            );
                        })()}
                    </div>
                )}
                <div style={{display:'inline-flex',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e2e8f0',boxShadow: isMobile ? '0 1px 3px rgba(0,0,0,0.05)' : 'none'}}>
                    <button onClick={() => { if(mobileTableView){localStorage.setItem('ts_sales_view','card');setMobileTableView(false);setShowColFilter(false);} }} style={{display:'inline-flex',alignItems:'center',gap:'6px',height: isMobile ? '34px' : '30px',padding: isMobile ? '0 16px' : '0 12px',border:'none',background: !mobileTableView ? 'rgb(234, 88, 12)' : '#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                        <i className="fa fa-th-large" style={{fontSize:'11px',color: !mobileTableView ? '#fff' : '#64748b'}}></i>
                        <span style={{fontSize:'12px',fontWeight:'700',color: !mobileTableView ? '#fff' : '#64748b'}}>Card View</span>
                    </button>
                    <button onClick={() => { if(!mobileTableView){localStorage.setItem('ts_sales_view','table');setMobileTableView(true);} }} style={{display:'inline-flex',alignItems:'center',gap:'6px',height: isMobile ? '34px' : '30px',padding: isMobile ? '0 16px' : '0 12px',border:'none',borderLeft:'1.5px solid #e2e8f0',background: mobileTableView ? 'rgb(234, 88, 12)' : '#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                        <i className="fa fa-table" style={{fontSize:'11px',color: mobileTableView ? '#fff' : '#64748b'}}></i>
                        <span style={{fontSize:'12px',fontWeight:'700',color: mobileTableView ? '#fff' : '#64748b'}}>Table View</span>
                    </button>
                </div>
            </div>

            {mobileTableView ? (
                /* ── Mobile Table View ── */
                <div style={{borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 6px rgba(0,0,0,0.05)',background:'#fff',overflow:'hidden',position:'relative'}}>
                    <div style={{overflowX:'auto',minWidth:0}}>
                        <div style={{minWidth: mobileMinWidth + 'px'}}>
                        <DataTable
                            columns={mobileColumns}
                            data={paginatedData}
                            responsive={false}
                            highlightOnHover
                            customStyles={mergedStyles}
                            progressPending={isLoading && paginatedData.length === 0}
                            progressComponent={<SpecTableLoading label="Loading invoices…" />}
                            noDataComponent={
                                <div style={{padding:'40px 24px',textAlign:'center'}}>
                                    <div style={{width:'60px',height:'60px',margin:'0 auto 12px',borderRadius:'14px',background:'#fafafb',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}>
                                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                                    </div>
                                    <div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No records found</div>
                                    <div style={{fontSize:'13px',color:'#6b7280',marginTop:'4px',maxWidth:'380px',marginInline:'auto'}}>Try changing the date range or search term. New invoices appear here as soon as you create them.</div>
                                </div>
                            }
                        />
                        </div>
                    </div>
                    {isLoading && paginatedData.length > 0 && (
                        <div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'56px',zIndex:5}}>
                            <div style={{display:'inline-flex',alignItems:'center',gap:'8px',padding:'9px 16px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'12.5px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
                                <i className="fa fa-spinner fa-spin" style={{fontSize:'13px'}}></i>
                                <span>Loading…</span>
                            </div>
                        </div>
                    )}
                    {totalPages > 1 && (
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'10px 14px',borderTop:'1px solid #f1f5f9',flexWrap:'wrap',gap:'8px'}}>
                            <span style={{fontSize:'12px',color:'#6b7280',fontWeight:'500'}}>
                                {(page-1)*perPage+1}–{Math.min(page*perPage,filteredData.length)} of {filteredData.length}
                            </span>
                            <div style={{display:'flex',alignItems:'center',gap:'4px'}}>
                                <button onClick={()=>setPage(p=>Math.max(1,p-1))} disabled={page===1} style={{height:'30px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e5e7eb',background:'#fff',color:page===1?'#d1d5db':'#374151',fontWeight:'600',fontSize:'12px',cursor:page===1?'default':'pointer',outline:'none'}}>←</button>
                                <span style={{fontSize:'12px',fontWeight:'600',color:'#374151',padding:'0 6px'}}>{page} / {totalPages}</span>
                                <button onClick={()=>setPage(p=>Math.min(totalPages,p+1))} disabled={page===totalPages} style={{height:'30px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e5e7eb',background:'#fff',color:page===totalPages?'#d1d5db':'#374151',fontWeight:'600',fontSize:'12px',cursor:page===totalPages?'default':'pointer',outline:'none'}}>→</button>
                            </div>
                        </div>
                    )}
                </div>
            ) : (<>
            {/* ── Card View ── */}
            {isLoading && (
                <div style={{display:'flex',justifyContent:'center',padding:'16px 0 10px'}}>
                    <div style={{display:'inline-flex',alignItems:'center',gap:'8px',padding:'9px 16px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'12.5px',fontWeight:'600'}}>
                        <i className="fa fa-spinner fa-spin" style={{fontSize:'13px'}}></i>
                        <span>Loading invoices…</span>
                    </div>
                </div>
            )}
            <div style={{display:'flex',flexDirection:'column',gap:'10px',opacity:isLoading?0.6:1,transition:'opacity 0.15s'}}>
                {paginatedData.map(row => {
                    const total = toNum(row.total);
                    const paid = toNum(row.total_paid);
                    const balance = total - paid;
                    const hasProducts = (row.products_count || 0) > 0;

                    // Build payments totals
                    const paymentTotals = {};
                    (row.payments_list || row.payments || []).forEach(item => {
                        const mode = item.payment_mode_type || item.payment_mode?.type || 'Other';
                        paymentTotals[mode] = (paymentTotals[mode] || 0) + (parseFloat(item.total_amount) || 0);
                    });
                    const paymentEntries = Object.entries(paymentTotals);

                    const isExpanded = expandedCard === row.id;
                    const totalFmt = hasProducts ? formatAmount(total) : null;

                    return (
                        <div key={row.id} style={{display:'flex',marginBottom:'0',borderRadius:'14px',border:'1px solid #f1f5f9',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                            {/* Left orange gradient bar */}
                            <div style={{width:'4px',flexShrink:0,background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}/>
                            <div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
                                {/* Top row: #invoice · date + amount + chevron */}
                                <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px'}}>
                                    <div style={{minWidth:0}}>
                                        <div style={{fontSize:'11px',color:'rgb(234, 88, 12)',fontWeight:'700',marginBottom:'4px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',display:'flex',alignItems:'center',gap:'8px'}}>
                                            <a href={`/data_entry/sales_entry/invoice/${row.id}`} onClick={e=>e.stopPropagation()} style={{color:'rgb(234, 88, 12)',textDecoration:'none'}}>#{row.other_invoice_id||row.id}</a>
                                            {row.created_at ? <span style={{fontWeight:'500',color:'#6b7280'}}>{row.created_at}</span> : ''}
                                        </div>
                                        {/* Customer name */}
                                        <div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',marginBottom:'6px'}}>{row.customer?.name || 'Guest'}</div>
                                        {/* Badges row: status + payment modes */}
                                        <div style={{display:'flex',flexWrap:'wrap',gap:'8px',alignItems:'center',marginTop:'6px'}}>
                                            {paidOrNot(row)}
                                            {row.paid_type === 'not-paid' && paymentEntries.length === 0 && (
                                                <span style={{fontSize:'11px',fontWeight:'700',color:'#dc2626',whiteSpace:'nowrap'}}>No Payment</span>
                                            )}
                                            {!hasProducts && <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'10px',fontWeight:'700',color:'#f59e0b',background:'#fffbeb',border:'1px solid #fde68a',padding:'2px 8px',borderRadius:'20px',whiteSpace:'nowrap'}}><i className="fa fa-exclamation-triangle" style={{fontSize:'9px'}}></i> No Products</span>}
                                            {paymentEntries.map(([mode, amt2]) => {
                                                const modeColors = {'Cash':{bg:'#f0fdf4',color:'#15803d',border:'#86efac',icon:'fa-money'},'Card':{bg:'#eff6ff',color:'#1d4ed8',border:'#93c5fd',icon:'fa-credit-card'},'Cheque':{bg:'#f5f3ff',color:'#6d28d9',border:'#c4b5fd',icon:'fa-file-text-o'},'Bank Transfer':{bg:'#ecfeff',color:'#0e7490',border:'#67e8f9',icon:'fa-university'},'Credit':{bg:'#fff7ed',color:'rgb(234, 88, 12)',border:'#fed7aa',icon:'fa-rotate-left'}};
                                                const s2 = modeColors[mode] || {bg:'#f8fafc',color:'#475569',border:'#e2e8f0',icon:'fa-circle'};
                                                return <span key={mode} style={{display:'inline-flex',alignItems:'center',gap:'4px',background:s2.bg,border:`1px solid ${s2.border}`,borderRadius:'6px',padding:'2px 7px',fontSize:'10px',fontWeight:'600',color:s2.color,whiteSpace:'nowrap',maxWidth:'100%',overflow:'hidden',textOverflow:'ellipsis'}}><i className={`fa ${s2.icon}`} style={{fontSize:'9px'}}></i>{mode} {amt2.toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>;
                                            })}
                                        </div>
                                    </div>
                                    {/* Amount + chevron */}
                                    <div style={{display:'flex',flexDirection:'column',alignItems:'flex-end',gap:'6px',flexShrink:0}}>
                                        {hasProducts && totalFmt ? (
                                            <span style={{background:'#FFF7ED',border:'1px solid #fed7aa',borderRadius:'8px',padding:'3px 10px',fontWeight:'800',color:'rgb(234, 88, 12)',fontSize:'13px',whiteSpace:'nowrap'}}>{props.currency} {totalFmt}</span>
                                        ) : null}
                                        <button onClick={() => setExpandedCard(isExpanded ? null : row.id)} style={{background:'none',border:'none',cursor:'pointer',padding:'4px',display:'flex',alignItems:'center',justifyContent:'center',outline:'none'}}>
                                            <i className={"fa fa-chevron-" + (isExpanded ? 'up' : 'down')} style={{fontSize:'12px',color: isExpanded ? 'rgb(234, 88, 12)' : '#94a3b8',transition:'all 0.2s'}}></i>
                                        </button>
                                    </div>
                                </div>
                                {/* Expanded: action buttons */}
                                {isExpanded && (
                                    <div style={{display:'flex',gap:'8px',marginTop:'10px',paddingTop:'10px',borderTop:'1px solid #f1f5f9'}}>
                                        <a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{flex:1,height:'36px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-pencil" style={{fontSize:'11px'}}></i> Edit
                                        </a>
                                        <a href={`/data_entry/sales_entry/invoice/invoiceview/${row.id}`} target="_blank" style={{flex:1,height:'36px',background:'#fff',border:'1.5px solid #e2e8f0',color:'#64748b',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-print" style={{fontSize:'11px'}}></i> Print
                                        </a>
                                        <a href={`/data_entry/sales_entry/invoice/invoiceexcel/${row.id}`} style={{flex:1,height:'36px',background:'#fff',border:'1.5px solid #e2e8f0',color:'#64748b',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-download" style={{fontSize:'11px'}}></i> DL
                                        </a>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* ── Pagination ── */}
            {totalPages > 1 && (
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'18px 0 4px',flexWrap:'wrap',gap:'10px'}}>
                    <div style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>
                        Showing {(page - 1) * perPage + 1}–{Math.min(page * perPage, filteredData.length)} of {filteredData.length}
                    </div>
                    <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                        <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                            style={{height:'32px',padding:'0 12px',borderRadius:'8px',border:'1.5px solid #e5e7eb',background:'#fff',color: page === 1 ? '#d1d5db' : '#374151',fontWeight:'600',fontSize:'13px',cursor: page === 1 ? 'default' : 'pointer',outline:'none'}}>
                            ← Prev
                        </button>
                        {Array.from({length: totalPages}, (_, i) => i + 1)
                            .filter(p => p === 1 || p === totalPages || Math.abs(p - page) <= 1)
                            .reduce((acc, p, idx, arr) => {
                                if (idx > 0 && arr[idx - 1] !== p - 1) acc.push('...');
                                acc.push(p);
                                return acc;
                            }, [])
                            .map((item, idx) => item === '...' ? (
                                <span key={'e' + idx} style={{padding:'0 4px',color:'#9ca3af',fontSize:'13px'}}>…</span>
                            ) : (
                                <button key={item} onClick={() => setPage(item)}
                                    style={{height:'32px',minWidth:'32px',padding:'0 8px',borderRadius:'8px',border:'1.5px solid',borderColor: page === item ? 'rgb(234, 88, 12)' : '#e5e7eb',background: page === item ? 'rgb(234, 88, 12)' : '#fff',color: page === item ? '#fff' : '#374151',fontWeight: page === item ? '700' : '500',fontSize:'13px',cursor:'pointer',outline:'none'}}>
                                    {item}
                                </button>
                            ))
                        }
                        <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages}
                            style={{height:'32px',padding:'0 12px',borderRadius:'8px',border:'1.5px solid #e5e7eb',background:'#fff',color: page === totalPages ? '#d1d5db' : '#374151',fontWeight:'600',fontSize:'13px',cursor: page === totalPages ? 'default' : 'pointer',outline:'none'}}>
                            Next →
                        </button>
                    </div>
                </div>
            )}
            </>)}
            {payTipPortal}
        </div>
    );
}

// ----------------- Date Range Picker -----------------
function DateRangePickerLocal_UNUSED({ fromDate, toDate, onFromChange, onToChange }) {
    const [isOpen, setIsOpen] = useState(false);
    const ref = useRef(null);
    const startDate = fromDate ? new Date(fromDate + 'T00:00:00') : null;
    const endDate = toDate ? new Date(toDate + 'T00:00:00') : null;

    const formatDisplay = (date) => {
        // Parse "YYYY-MM-DD" by its own parts — never via new Date(str), which reads it
        // as UTC and shifts the shown day one back in timezones behind UTC.
        if (!date) return '—';
        const MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const [y, m, d] = String(date).split('-').map(Number);
        if (!y || !m || !d) return '—';
        return `${String(d).padStart(2,'0')} ${MON[m-1]} ${y}`;
    };

    const toYMD = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth()+1).padStart(2,'0');
        const d = String(date.getDate()).padStart(2,'0');
        return `${y}-${m}-${d}`;
    };

    const handleChange = (dates) => {
        const [start, end] = dates;
        if (start) onFromChange(toYMD(start));
        if (end) { onToChange(toYMD(end)); setIsOpen(false); }
        else { onToChange(''); }
    };

    // Quick presets
    const applyPreset = (label) => {
        const now = new Date();
        let from, to;
        if (label === 'Today') { from = to = now; }
        else if (label === 'Yesterday') { from = to = new Date(now.getTime()-86400000); }
        else if (label === 'Last 7 days') { from = new Date(now.getTime()-6*86400000); to = now; }
        else if (label === 'Last 30 days') { from = new Date(now.getTime()-29*86400000); to = now; }
        else if (label === 'This month') { from = new Date(now.getFullYear(), now.getMonth(), 1); to = now; }
        onFromChange(toYMD(from)); onToChange(toYMD(to)); setIsOpen(false);
    };

    useEffect(() => {
        const handle = (e) => { if (ref.current && !ref.current.contains(e.target)) setIsOpen(false); };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, []);

    return (
        <div ref={ref} style={{position:'relative',flexShrink:0}}>
            <button type="button" onClick={() => setIsOpen(!isOpen)} style={{
                display:'inline-flex',alignItems:'center',gap:'8px',height:'38px',
                background:'#fafafa',border:'1.5px solid #e5e7eb',borderRadius:'9px',
                padding:'0 14px',cursor:'pointer',outline:'none',transition:'border-color 0.15s',minWidth:'220px',
                ...(isOpen ? {borderColor:'rgb(234, 88, 12)',background:'#fff'} : {})
            }}>
                <i className="fa fa-calendar" style={{fontSize:'12px',color:'rgb(234, 88, 12)'}}></i>
                {(!fromDate && !toDate) ? (
                    <span style={{fontSize:'13px',fontWeight:'500',color:'#9ca3af'}}>Select date range</span>
                ) : (<>
                    <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(fromDate)}</span>
                    <i className="fa fa-arrow-right" style={{fontSize:'9px',color:'#d1d5db'}}></i>
                    <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(toDate)}</span>
                </>)}
            </button>
            {isOpen && (
                <>
                {/* Backdrop */}
                <div style={{position:'fixed',top:0,left:0,right:0,bottom:0,zIndex:9998}} onClick={() => setIsOpen(false)}></div>
                <div style={{position:'absolute',top:'calc(100% + 8px)',right:0,zIndex:9999,background:'#fff',borderRadius:'16px',boxShadow:'0 16px 48px rgba(0,0,0,0.18),0 0 0 1px rgba(0,0,0,0.05)',display:'flex',overflow:'hidden',animation:'drpFadeIn 0.15s ease-out'}}>
                    <style>{`
                        @keyframes drpFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
                        .drp-cal .react-datepicker{border:none;font-family:inherit;background:transparent;display:flex!important;flex-direction:row!important;}
                        .drp-cal .react-datepicker__month-container{padding:0 8px;}
                        .drp-cal .react-datepicker__month-container+.react-datepicker__month-container{border-left:1px solid #f0f0f0;}
                        .drp-cal .react-datepicker__header{background:transparent;border-bottom:none;padding-top:4px;}
                        .drp-cal .react-datepicker__current-month{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:6px;padding:2px 0;}
                        .drp-cal .react-datepicker__day-names{margin-bottom:4px;}
                        .drp-cal .react-datepicker__day-name{font-size:10px;font-weight:700;color:#94a3b8;width:32px;line-height:24px;text-transform:uppercase;letter-spacing:0.5px;}
                        .drp-cal .react-datepicker__day{width:32px;height:32px;line-height:32px;font-size:12px;border-radius:8px;margin:1px;font-weight:600;color:#1e293b;transition:all 0.1s;}
                        .drp-cal .react-datepicker__day:hover{background:#1e293b;color:#fff;}
                        .drp-cal .react-datepicker__day--today{background:#fef3e2;font-weight:700;color:#ea580c;}
                        .drp-cal .react-datepicker__day--selected,
                        .drp-cal .react-datepicker__day--range-start,
                        .drp-cal .react-datepicker__day--range-end{background:rgb(234, 88, 12)!important;color:#fff!important;font-weight:700;position:relative;z-index:2;box-shadow:inset 0 0 0 2px rgba(255,255,255,0.3);}
                        .drp-cal .react-datepicker__day--in-range{background:rgb(234, 88, 12)!important;color:#fff!important;border-radius:3px!important;font-weight:500;box-shadow:none;margin:1px 0!important;}
                        .drp-cal .react-datepicker__day--in-range:hover{background:#ea580c!important;color:#fff!important;}
                        .drp-cal .react-datepicker__day--range-start{border-radius:8px 3px 3px 8px!important;}
                        .drp-cal .react-datepicker__day--range-end{border-radius:3px 8px 8px 3px!important;}
                        .drp-cal .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:8px!important;}
                        .drp-cal .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--range-start){background:#fb923c!important;color:#fff!important;border-radius:3px!important;}
                        .drp-cal .react-datepicker__day--outside-month{color:#cbd5e1!important;font-weight:400;}
                        .drp-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b;}
                        .drp-cal .react-datepicker__day--disabled{color:#94a3b8!important;cursor:not-allowed;background:transparent!important;font-weight:400;}
                        .drp-cal .react-datepicker__navigation{top:14px;width:28px;height:28px;border:1.5px solid #e5e7eb;border-radius:6px;background:#fff;}
                        .drp-cal .react-datepicker__navigation:hover{background:#1e293b;border-color:#1e293b;}
                        .drp-cal .react-datepicker__navigation-icon::before{border-color:#6b7280;border-width:2px 2px 0 0;width:7px;height:7px;top:8px;}
                        .drp-cal .react-datepicker__navigation:hover .react-datepicker__navigation-icon::before{border-color:#fff;}
                    `}</style>
                    {/* Presets sidebar */}
                    <div style={{borderRight:'1px solid #f0f0f0',padding:'16px 10px',display:'flex',flexDirection:'column',gap:'2px',minWidth:'130px',background:'#fafafa'}}>
                        <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'1.2px',textTransform:'uppercase',padding:'4px 10px 10px',borderBottom:'1px solid #e5e7eb',marginBottom:'6px'}}>Quick Select</div>
                        {['Today','Yesterday','Last 7 days','Last 30 days','This month'].map(label => (
                            <button key={label} type="button" onClick={() => applyPreset(label)} style={{
                                border:'none',background:'transparent',padding:'9px 12px',fontSize:'13px',
                                fontWeight:'500',color:'#374151',cursor:'pointer',borderRadius:'8px',
                                textAlign:'left',transition:'all 0.15s',lineHeight:'1.3',
                            }}
                            onMouseEnter={e => {e.target.style.background='#fff7ed';e.target.style.color='#c2410c';e.target.style.fontWeight='600';}}
                            onMouseLeave={e => {e.target.style.background='transparent';e.target.style.color='#374151';e.target.style.fontWeight='500';}}>
                                {label}
                            </button>
                        ))}
                    </div>
                    {/* Calendar */}
                    <div style={{padding:'16px 12px 12px'}} className="drp-cal">
                        <DatePicker
                            selected={startDate}
                            onChange={handleChange}
                            startDate={startDate}
                            endDate={endDate}
                            selectsRange
                            inline
                            monthsShown={2}
                            maxDate={new Date()}
                            openToDate={new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1)}
                        />
                        {/* Selected range display */}
                        <div style={{display:'flex',alignItems:'center',justifyContent:'center',gap:'0',padding:'12px 0 4px',borderTop:'1px solid #f0f0f0',marginTop:'8px'}}>
                            <div style={{background:'#1e293b',borderRadius:'8px 0 0 8px',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color:'#fff',display:'flex',alignItems:'center',gap:'8px'}}>
                                <i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>
                                {formatDisplay(fromDate)}
                            </div>
                            <div style={{background:'#334155',padding:'8px 12px',display:'flex',alignItems:'center'}}>
                                <i className="fa fa-arrow-right" style={{fontSize:'10px',color:'#94a3b8'}}></i>
                            </div>
                            <div style={{background: toDate ? '#1e293b' : '#475569',borderRadius:'0 8px 8px 0',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color: toDate ? '#fff' : '#94a3b8',display:'flex',alignItems:'center',gap:'8px'}}>
                                <i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>
                                {toDate ? formatDisplay(toDate) : 'Select end'}
                            </div>
                        </div>
                    </div>
                </div>
                </>
            )}
        </div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('daily-book-sales-app')) {
    const id = "daily-book-sales-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<DailyBookSalesApp {...props} />
		</Provider>
    );
}

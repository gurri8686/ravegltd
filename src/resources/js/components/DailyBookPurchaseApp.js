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
import useTableSearch from "./../hooks/useTableSearch";
import SpecTableLoading from "./../elements/SpecTableLoading";
import Icon from "./../hooks/Icons";
import useDropdownFix from "./../hooks/useDropdownFix"
import { useWindowSize } from "./../hooks/useWindowSize"
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import DailyReportEmailModal from "./../elements/DailyReportEmailModal";
import "react-datepicker/dist/react-datepicker.css";
const ReactDatePicker = DatePicker;
import DateRangePicker from "./../hooks/DateRangePicker";
import SpecPagination from "./../elements/SpecPagination";

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
		searchTerm: ""
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
    },
});

const { setCustomers,setSuppliers, setSelectedInvoices, setToDate, setFromDate, setOption, 
	setCurrentCustomer, setCurrentSupplier, setCurrentCustomerInfo, setCustomersLoading, setCurrentSupplierInfo,
	triggerPaymentRefresh } = slice.actions;
	
const store = configureStore({
    reducer: { properties: slice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

export default function DailyBookPurchaseApp(props) {
	const dispatch = useDispatch();
	const { width } = useWindowSize();
	const isDesktop = width >= 768;
	const isTablet = width >= 600 && width < 768;
	const isMobile = width < 600;

	useEffect(() => {

    },[])

    const listMarginTop = '0';

    return (
	<>
	{/* Page title bar */}
	<div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom: isMobile ? '10px' : '0',background:'#fff',borderRadius: isMobile ? '14px' : '14px 14px 0 0',padding: isMobile ? '12px 14px' : '16px 20px',boxShadow: isMobile ? '0 1px 4px rgba(0,0,0,0.06)' : 'none',border:'1px solid #eaecf2',borderBottom: isMobile ? '1px solid #eaecf2' : 'none',flexDirection:'row',gap:'8px'}}>
		<div style={{display:'flex',alignItems:'center',gap:'12px',flex:1,minWidth:0}}>
			<div style={{width: isMobile ? '36px' : '40px',height: isMobile ? '36px' : '40px',borderRadius:'12px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:'0 4px 14px rgba(234,88,12,0.3)',flexShrink:0}}>
				<i className="fa fa-book" style={{fontSize: isMobile ? '14px' : '18px',color:'#fff'}}></i>
			</div>
			<div>
				<h2 style={{margin:0,fontSize: isMobile ? '17px' : '18px',fontWeight:'800',color:'#0f172a',lineHeight:'1.2',letterSpacing:'-0.3px',fontFamily:'inherit'}}>Purchases</h2>
				<span style={{fontSize: isMobile ? '11px' : '12px',color:'#94a3b8',fontWeight:'500'}}>Manage purchase invoices and suppliers</span>
			</div>
		</div>
		<a href="/data_entry/purchase_entry/invoice/" style={{background:'rgb(234, 88, 12)',color:'#fff',border:'none',borderRadius:'10px',padding: isMobile ? '0 14px' : '0 20px',height: isMobile ? '34px' : '40px',fontWeight:'700',fontSize: isMobile ? '12px' : '14px',boxShadow:'0 3px 10px rgba(234,88,12,0.3)',textDecoration:'none',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'8px',flexShrink:0}}>
			<i className="fa fa-plus"></i> New Purchase
		</a>
	</div>
	<style>{`
		.purchase-action-btn:focus,
		.purchase-action-btn:focus-visible,
		.purchase-action-btn:active,
		.purchase-action-btn:focus:active {
			outline: none !important;
			box-shadow: none !important;
			border-color: inherit !important;
		}
		button:focus, button:focus-visible, button:active {
			outline: none !important;
			box-shadow: none !important;
		}
		.purchase-dropdown-item:hover {
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
		.react-select__dropdown-indicator,
		.react-select__indicator-separator {
			display: none !important;
		}
		.dropdown .dropdown-menu {
			transform: none !important;
			will-change: auto !important;
		}
	`}</style>
	<div className="row">
		<div className="col-12 col-md-12">
			<FilterOptionsSearchPanel {...props} />
		</div>
		<div className="col-12" style={{ marginTop: listMarginTop, marginBottom: '70px' }}>
			<List {...props} />
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</>
    );
}

function FilterOptionsSearchPanel(props) {
    const dispatch = useDispatch();
    const { suppliers, toDate, fromDate, option, currentSupplier, selectedInvoices } = useSelector(state => state.properties);
    const [activeTab, setActiveTab] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [downloadingExcel, setDownloadingExcel] = useState(false);
    const openInNewTab = useOpenInNewTab();
    const { width } = useWindowSize();
    const isDesktop = width >= 768;
    const isTablet = width >= 600 && width < 768;

    useEffect(() => {
        const fetchSuppliers = async () => {
            try {
                setLoading(true);
                const response = await axios.get(props.supplierListApi);
                if (response.data.success === true) {
                    dispatch(setSuppliers(response.data.payload));
                }
            } catch (err) {
                console.error('Failed to load suppliers', err);
                setError('Failed to load suppliers');
            } finally {
                setLoading(false);
            }
        };

        fetchSuppliers();
    }, [props.supplierListApi, dispatch]);

    const supplierOptions = [
        { value: '', label: '-- Select Supplier --' },
        ...suppliers.map(c => ({ value: c.id, label: c.name })),
    ];

    const handleSupplierChange = (selected) => {
        if (Array.isArray(selected) && selected.length > 0) {
            dispatch(setCurrentSupplier(selected.map(s => s.value)));
            dispatch(setCurrentSupplierInfo(selected.map(s => suppliers.find(c => c.id === s.value))));
        } else if (selected && !Array.isArray(selected)) {
            dispatch(setCurrentSupplier(selected.value));
            dispatch(setCurrentSupplierInfo(suppliers.find(c => c.id === selected.value)));
        } else {
            dispatch(setCurrentSupplier(null));
            dispatch(setCurrentSupplierInfo(null));
        }
    };

    const formik = useFormik({
        initialValues: {
            from_date: '',
            to_date: '',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Cash', value: 'cash' },
                { label: 'Credit', value: 'credit' },
                { label: 'Cheque', value: 'cheque' },
                { label: 'Bank Transfer', value: 'bank transfer' },
            ],
        },
        validationSchema: Yup.object({
            from_date: Yup.date().required('From Date is required'),
            to_date: Yup.date().required('To Date is required'),
        }),
        onSubmit: values => {},
    });

    const statementInvoice = (e) => {
        if (downloadingExcel) return;
        setDownloadingExcel(true);
        const qs = new URLSearchParams();
        if (currentSupplier) qs.set('supplier_id', currentSupplier);
        if (fromDate) qs.set('start_date', fromDate);
        if (toDate) qs.set('end_date', toDate);
        const url = (props.statementApi || '/data_entry/purchase_entry/daily_report/daily_book_purchase/view/statement') + '?' + qs.toString();
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
            supplier_id: currentSupplier,
            start_date: fromDate,
            end_date: toDate,
            invoices: selectedInvoices,
            type: e,
        });
    };

    const [emailModalOpen, setEmailModalOpen] = useState(false);
    const emailInvoice = () => setEmailModalOpen(true);

    /* ── Tablet — 2-row card: Supplier+Dates on top, 3 buttons below ── */
    if (isTablet) {
        const tDdMenu = {borderRadius:'10px',boxShadow:'0 6px 20px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',padding:'4px',minWidth:'auto',width:'auto',zIndex:9999};
        const tBtnStyle = {
            display:'inline-flex',alignItems:'center',gap:'5px',height:'36px',
            background:'#fff',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',
            borderRadius:'8px',padding:'0 10px',fontSize:'11px',fontWeight:'600',
            cursor:'pointer',outline:'none',boxShadow:'none',whiteSpace:'nowrap',
        };
        const tDdItem = {borderRadius:'6px',fontSize:'13px',padding:'8px 16px',whiteSpace:'nowrap'};

        return (
            <div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',background:'#fff',padding:'14px 14px'}}>
                {/* ── Row 1: Supplier + Date Range inline ── */}
                <div style={{display:'flex',alignItems:'center',gap:'8px',marginBottom:'12px'}}>
                    {/* Supplier Select */}
                    <div style={{flexShrink:0,width:'130px'}}>
                        {loading ? (
                            <p style={{margin:0,color:'#9ca3af',fontSize:'12px'}}>Loading...</p>
                        ) : error ? (
                            <p style={{margin:0}} className="text-danger">{error}</p>
                        ) : (
                            <Select styles={{
                                ...orangeSelectStyles,
                                control: (base, state) => ({
                                    ...orangeSelectStyles.control(base, state),
                                    height:'36px',minHeight:'36px',
                                    borderRadius:'10px',
                                    border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e5e7eb',
                                    background:'#f9fafb',
                                    boxShadow: state.isFocused ? '0 0 0 0.15rem rgba(234,88,12,0.15)' : '0 1px 3px rgba(0,0,0,0.04)',
                                }),
                                valueContainer: (base) => ({...base,padding:'0 8px',height:'36px'}),
                                indicatorsContainer: (base) => ({...base,height:'36px'}),
                                placeholder: (base) => ({...base,fontSize:'11px',color:'#9ca3af'}),
                                singleValue: (base) => ({...base,fontSize:'11px',fontWeight:'600',color:'#111827'}),
                                menu: (base) => ({...base,zIndex:50,borderRadius:'10px',boxShadow:'0 6px 20px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',overflow:'hidden',marginTop:'4px'}),
                                menuList: (base) => ({...base,padding:'4px',maxHeight:'200px'}),
                                option: (base, state) => ({
                                    ...orangeSelectStyles.option(base, state),
                                    fontSize:'12px',padding:'8px 12px',borderRadius:'6px',marginBottom:'2px',
                                }),
                                input: (base) => ({...base,fontSize:'11px',color:'#111827',margin:0,padding:0}),
                                clearIndicator: (base) => ({...base,padding:'0 4px',color:'#9ca3af','&:hover':{color:'#ef4444'}}),
                                noOptionsMessage: (base) => ({...base,fontSize:'11px',color:'#9ca3af'}),
                            }}
                                options={supplierOptions}
                                isLoading={loading}
                                isClearable
                                isSearchable={false}
                                onChange={handleSupplierChange}
                                classNamePrefix="react-select"
                                components={{ DropdownIndicator: () => null, IndicatorSeparator: () => null }}
                                placeholder={<><i className="fa fa-truck" style={{color:'rgb(234, 88, 12)',marginRight:'4px',fontSize:'9px'}}></i>Supplier</>}
                            />
                        )}
                    </div>

                    {/* Date Range — compact inline */}
                    <div style={{display:'flex',alignItems:'center',background:'#f9fafb',border:'1.5px solid #e5e7eb',borderRadius:'10px',overflow:'hidden',boxShadow:'0 1px 3px rgba(0,0,0,0.04)',height:'36px',flex:1,minWidth:0}}>
                        <div style={{flex:1,padding:'0 6px',borderRight:'1px solid #e5e7eb',display:'flex',alignItems:'center',gap:'4px',height:'100%',minWidth:0}}>
                            <span style={{fontSize:'8px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase',flexShrink:0}}>
                                <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',marginRight:'2px',fontSize:'8px'}}></i>From
                            </span>
                            <OrangeDatePicker value={fromDate} onChange={(val) => dispatch(setFromDate(val))} />
                        </div>
                        <div style={{display:'flex',alignItems:'center',padding:'0 3px',color:'rgb(234, 88, 12)',fontSize:'9px',opacity:0.5,flexShrink:0}}>
                            <i className="fa fa-long-arrow-right"></i>
                        </div>
                        <div style={{flex:1,padding:'0 6px',display:'flex',alignItems:'center',gap:'4px',height:'100%',minWidth:0}}>
                            <span style={{fontSize:'8px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase',flexShrink:0}}>
                                <i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',marginRight:'2px',fontSize:'8px'}}></i>To
                            </span>
                            <OrangeDatePicker value={toDate} onChange={(val) => dispatch(setToDate(val))} />
                        </div>
                    </div>
                </div>

                {/* ── Row 2: Action Buttons ── */}
                <div style={{display:'flex',alignItems:'center',gap:'6px',justifyContent:'flex-end',flexWrap:'wrap'}}>
                    <button className="purchase-action-btn" style={tBtnStyle} type="button" onClick={() => emailInvoice('all')}>
                        <i className="fa fa-envelope" style={{fontSize:'11px'}}></i> Email
                    </button>
                    <button className="purchase-action-btn" style={tBtnStyle} type="button" onClick={() => printInvoice('all')}>
                        <i className="fa fa-print" style={{fontSize:'11px'}}></i> Print
                    </button>
                    <button className="purchase-action-btn" style={tBtnStyle} type="button" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}>
                        <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'11px'}}></i> {downloadingExcel ? 'Preparing…' : 'Excel'}
                    </button>
                </div>
                <DailyReportEmailModal
                    open={emailModalOpen}
                    onClose={() => setEmailModalOpen(false)}
                    apiUrl={props.emailApi}
                    listApi={props.listApi}
                    reportTitle="Daily Purchase Report"
                    fromDate={fromDate}
                    toDate={toDate}
                    supplierId={currentSupplier || ''}
                />
            </div>
        );
    }

    /* ── Desktop — single-row inline layout (matching Sales page) ── */
    if (isDesktop) {
        const iconBtnStyle = {
            width:'38px',height:'38px',borderRadius:'9px',border:'1.5px solid #e5e7eb',
            background:'#fff',color:'#6b7280',display:'inline-flex',alignItems:'center',
            justifyContent:'center',cursor:'pointer',outline:'none',boxShadow:'none',
            transition:'all 0.15s',fontSize:'14px',padding:0,
        };

        return (
            <div style={{borderRadius:'0',border:'1px solid #eaecf2',borderTop:'none',borderBottom:'none',boxShadow:'none',background:'#fff',padding: width < 1024 ? '10px 12px' : '12px 16px',display:'flex',alignItems:'center',gap: width < 1024 ? '8px' : '12px',width:'100%'}}>
                {/* ── Search ── */}
                <div style={{position:'relative',flex:3,minWidth:'150px'}}>
                    <i className="fa fa-search" style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',fontSize:'12px',pointerEvents:'none',zIndex:1}}></i>
                    <input
                        type="text"
                        placeholder="Search invoices..."
                        value={useSelector(state => state.properties.searchTerm) || ''}
                        onChange={(e) => dispatch(slice.actions.setSearchTerm(e.target.value))}
                        style={{paddingLeft:'34px',paddingRight:'10px',paddingTop:'0',paddingBottom:'0',height:'38px',borderRadius:'9px',border:'1.5px solid #e5e7eb',fontSize:'13px',background:'#fafafa',transition:'border-color 0.15s',width:'100%',boxSizing:'border-box',outline:'none',fontFamily:'inherit'}}
                        onFocus={e => { e.target.style.borderColor='rgb(234, 88, 12)'; e.target.style.background='#fff'; }}
                        onBlur={e => { e.target.style.borderColor='#e5e7eb'; e.target.style.background='#fafafa'; }}
                    />
{!!(useSelector(state => state.properties.searchTerm)) && <button type="button" onClick={() => dispatch(slice.actions.setSearchTerm(''))} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
                </div>

                {width >= 1024 && <div style={{width:'1px',height:'28px',background:'#e5e7eb',flexShrink:0}}></div>}

                {/* ── Supplier Select ── */}
                <div style={{minWidth: width < 1024 ? '140px' : '180px',maxWidth: width < 1024 ? '200px' : '280px',flex: width < 1024 ? '1 1 auto' : '0 1 250px'}}>
                    {!loading && !error && (
                        <Select styles={{
                            ...orangeSelectStyles,
                            control: (base, state) => ({
                                ...orangeSelectStyles.control(base, state),
                                minHeight:'38px',borderRadius:'9px',
                                border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e5e7eb',
                                background:'#fafafa',
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
                            options={supplierOptions}
                            isClearable isSearchable={true}
                            isMulti
                            onChange={(selected) => handleSupplierChange(selected)}
                            components={{ DropdownIndicator: () => null, IndicatorSeparator: () => null }}
                            placeholder={<><i className="fa fa-truck" style={{color:'rgb(234, 88, 12)',marginRight:'6px',fontSize:'10px'}}></i>Supplier</>}
                        />
                    )}
                </div>

                {/* ── Date Range Picker ── */}
                <div style={{flex:'0 0 auto',maxWidth:'260px'}}>
                <DateRangePicker fromDate={fromDate} toDate={toDate} onFromChange={(val) => dispatch(setFromDate(val))} onToChange={(val) => dispatch(setToDate(val))} width={width} />
                </div>

                {width >= 1024 && <div style={{width:'1px',height:'28px',background:'#e5e7eb',flexShrink:0}}></div>}

                {/* ── Action Icon Buttons ── */}
                <div style={{display:'flex',alignItems:'center',gap:'6px',flexShrink:0}}>
                    <button className="purchase-action-btn" style={iconBtnStyle} type="button" title="Send Email" onClick={() => emailInvoice('all')}
                        onMouseEnter={e=>{e.target.style.background='#fff7ed';e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.color='rgb(234, 88, 12)';}}
                        onMouseLeave={e=>{e.target.style.background='#fff';e.target.style.borderColor='#e5e7eb';e.target.style.color='#6b7280';}}>
                        <i className="fa fa-envelope-o"></i>
                    </button>
                    <button className="purchase-action-btn" style={iconBtnStyle} type="button" title="Print" onClick={() => printInvoice('all')}
                        onMouseEnter={e=>{e.target.style.background='#fff7ed';e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.color='rgb(234, 88, 12)';}}
                        onMouseLeave={e=>{e.target.style.background='#fff';e.target.style.borderColor='#e5e7eb';e.target.style.color='#6b7280';}}>
                        <i className="fa fa-print"></i>
                    </button>
                    <button className="purchase-action-btn" style={iconBtnStyle} type="button" title="Download Excel" onClick={() => statementInvoice('excel')} disabled={downloadingExcel}
                        onMouseEnter={e=>{if(!downloadingExcel){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                        onMouseLeave={e=>{if(!downloadingExcel){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}>
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
                    reportTitle="Daily Purchase Report"
                    fromDate={fromDate}
                    toDate={toDate}
                    supplierId={currentSupplier || ''}
                />
            </div>
        );
    }

    /* ── Mobile (< 768px) — same style as Sales page ── */
    const mobileSearchTerm = useSelector(state => state.properties.searchTerm) || '';
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [pendingSupplier, setPendingSupplier] = useState(currentSupplier || null);
    const [pendingFrom, setPendingFrom] = useState(fromDate || null);
    const [pendingTo, setPendingTo] = useState(toDate || null);
    const [rangeStart, setRangeStart] = useState(null);
    const [rangeEnd, setRangeEnd] = useState(null);
    const [sMonthDd, setSMonthDd] = useState(false);
    const [sYearDd, setSYearDd] = useState(false);
    const [activePreset, setActivePreset] = useState(null);
    const hasActiveFilter = !!(fromDate || toDate || currentSupplier);

    const toYMD = (d) => { const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${dd}`; };
    const fmtDisp = (v) => { if (!v) return ''; const d=new Date(v+'T00:00:00'); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); };
    const handleRangeChange = (dates) => {
        let [s,e]=dates;
        if (s && e && s > e) { const t=s; s=e; e=t; }
        setRangeStart(s); setRangeEnd(e||null);
        if(s) setPendingFrom(toYMD(s)); else setPendingFrom(null);
        if(e) setPendingTo(toYMD(e)); else if(!e && s) setPendingTo(null);
    };
    const applyMobilePreset = (label) => {
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
    const openPurchaseCalendar = () => { setRangeStart(pendingFrom?new Date(pendingFrom+'T00:00:00'):null); setRangeEnd(pendingTo?new Date(pendingTo+'T00:00:00'):null); setCalendarOpen(true); setMobileFilterOpen(false); };

    return (
        <>
        {/* ── Search + Filter row (Sales-style) ── */}
        <div style={{display:'flex',alignItems:'center',gap:'8px',marginBottom:'10px'}}>
            <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'44px',border:'1.5px solid #e5e7eb',borderRadius:'12px',background:'#fff',padding:'0 12px',minWidth:0}}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search invoice, supplier..."
                    value={mobileSearchTerm}
                    onChange={e => dispatch(slice.actions.setSearchTerm(e.target.value))}
                    style={{flex:1,border:'none',outline:'none',fontSize:'13px',color:'#374151',background:'transparent',minWidth:0}}
                />
                {!!mobileSearchTerm && (
                    <button type="button" onClick={() => dispatch(slice.actions.setSearchTerm(''))} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0}}>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                )}
            </div>
            <button type="button" onClick={() => { setPendingFrom(fromDate||null); setPendingTo(toDate||null); setPendingSupplier(currentSupplier||null); setMobileFilterOpen(v=>!v); }}
                style={{flexShrink:0,height:'44px',width:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none',boxShadow:'0 3px 10px rgba(234,88,12,0.3)'}}>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                {hasActiveFilter && <span style={{position:'absolute',top:'5px',right:'5px',width:'7px',height:'7px',borderRadius:'50%',background:'#fff',border:'1.5px solid rgb(234, 88, 12)'}}/>}
            </button>
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
                        {/* Supplier */}
                        <div>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Supplier</div>
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
                            options={supplierOptions} isClearable isSearchable value={pendingSupplier ? supplierOptions.find(o=>o.value===pendingSupplier)||null : null}
                            onChange={v=>setPendingSupplier(v?.value||null)} placeholder="Select Supplier" menuPortalTarget={document.body} menuShouldScrollIntoView={false} />
                        </div>
                        {/* Date Range — single button opens calendar */}
                        <div>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date Range</div>
                            <button type="button" onClick={openPurchaseCalendar}
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
                            <button type="button" onClick={()=>{ setPendingFrom(null); setPendingTo(null); setPendingSupplier(null); setActivePreset(null); dispatch(setFromDate('')); dispatch(setToDate('')); dispatch(setCurrentSupplier(null)); setMobileFilterOpen(false); }}
                                style={{height:'50px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'14px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px'}}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                Clear
                            </button>
                            <button type="button" onClick={()=>{ let f=pendingFrom,t=pendingTo; if(f&&t&&f>t){[f,t]=[t,f];} if(f) dispatch(setFromDate(f)); if(t) dispatch(setToDate(t)); if(pendingSupplier!==null) dispatch(setCurrentSupplier(pendingSupplier)); setMobileFilterOpen(false); }}
                                style={{height:'50px',borderRadius:'14px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 6px 16px rgba(234,88,12,0.35)'}}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </>
        )}
        {/* ── Calendar bottom sheet ── */}
        {calendarOpen && (<>
            <div onMouseDown={()=>setCalendarOpen(false)} style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.4)'}}/>
            <div onMouseDown={e=>e.stopPropagation()} style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'85vh',overflowY:'auto'}}>
                <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}><div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/></div>
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'4px 18px 14px'}}>
                    <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                        <div style={{width:'30px',height:'30px',borderRadius:'9px',background:'#fff7ed',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <span style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Select Date Range</span>
                    </div>
                    <button type="button" onClick={()=>setCalendarOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'50%',width:'30px',height:'30px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div style={{padding:'0 18px 14px'}}>
                    <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                        <div style={{flex:1,background:'#fff',border:'2px solid '+(pendingFrom?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingFrom?'0 0 0 3px rgba(234,88,12,0.08)':'none',transition:'all 0.15s'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>From</span>
                            </div>
                            <div style={{fontSize:'14px',fontWeight:'700',color:pendingFrom?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingFrom?fmtDisp(pendingFrom):'Select'}</div>
                        </div>
                        <div style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 10px rgba(234,88,12,0.35)'}}>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </div>
                        <div style={{flex:1,background:'#fff',border:'2px solid '+(pendingTo?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingTo?'0 0 0 3px rgba(234,88,12,0.08)':'none',transition:'all 0.15s'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>To</span>
                            </div>
                            <div style={{fontSize:'14px',fontWeight:'700',color:pendingTo?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingTo?fmtDisp(pendingTo):'Select'}</div>
                        </div>
                    </div>
                </div>
                {/* Quick preset chips — horizontal scroll, hidden scrollbar */}
                <div className="sp-presets" style={{display:'flex',gap:'8px',padding:'0 18px 14px',overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
                    {['Today','Yesterday','Last 7d','This month','Custom Range'].map(label => {
                        const presetRange = (() => {
                            const now = new Date(); let f, t;
                            if (label === 'Today') { f = t = now; }
                            else if (label === 'Yesterday') { f = t = new Date(now.getTime()-86400000); }
                            else if (label === 'Last 7d') { f = new Date(now.getTime()-6*86400000); t = now; }
                            else if (label === 'This month') { f = new Date(now.getFullYear(), now.getMonth(), 1); t = now; }
                            else return { f: null, t: null };
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
                <style>{`.sp-range .react-datepicker{width:100%;border:none;font-family:inherit;background:#fff !important;box-shadow:none !important}.sp-range .react-datepicker__month-container{width:100%;float:none;background:#fff !important}.sp-range .react-datepicker__month{background:#fff !important;margin:0 !important}.sp-range .react-datepicker__week{background:#fff !important}.sp-range .react-datepicker__header{background:#fff !important;border-bottom:none;padding:0}.sp-range .react-datepicker__header--custom{background:#fff !important;border-bottom:none !important;padding:0 !important}.sp-range .react-datepicker__day-names,.sp-range .react-datepicker__week{display:flex;justify-content:space-around}.sp-range .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0}.sp-range .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:42px;font-size:14px;font-weight:500;color:#334155;margin:0;border-radius:50%;transition:background 0.12s,color 0.12s;position:relative}.sp-range .react-datepicker__day:hover:not(.react-datepicker__day--selected):not(.react-datepicker__day--range-start):not(.react-datepicker__day--range-end){background:#f1f5f9;color:#0f172a}.sp-range .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.sp-range .react-datepicker__day--in-range,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start){background:transparent !important;color:rgb(234, 88, 12) !important;font-weight:600;position:relative}.sp-range .react-datepicker__day--in-range::before,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start)::before{content:'';position:absolute;top:4px;bottom:4px;left:0;right:0;background:#fff7f0;z-index:-1}.sp-range .react-datepicker__day--selected,.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--selecting-range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--selected,.sp-range .react-datepicker__day--today.react-datepicker__day--range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--range-end{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;position:relative;z-index:1}
/* range band behind start/end (half-inset like reference) */
.sp-range .react-datepicker__day--range-start:not(.react-datepicker__day--range-end)::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;right:0;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--range-end:not(.react-datepicker__day--range-start)::after{content:'';position:absolute;top:4px;bottom:4px;left:0;right:50%;background:#fff7f0;z-index:-2}
/* orange circle for selected/start/end */
.sp-range .react-datepicker__day--selected::before,.sp-range .react-datepicker__day--range-start::before,.sp-range .react-datepicker__day--range-end::before,.sp-range .react-datepicker__day--selecting-range-start::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50% !important}.sp-range .react-datepicker__day--outside-month{color:#d1d5db}.sp-range .react-datepicker__day--disabled{color:#e5e7eb !important;background:transparent !important}.sp-range .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b}.sp-range .react-datepicker__navigation{display:none !important}.sp-range .react-datepicker__current-month{display:none !important}.sp-dd{position:relative;display:inline-block}.sp-dd-btn{border:1.5px solid #e5e7eb;border-radius:9px;padding:7px 26px 7px 14px;font-size:13px;font-weight:700;color:#1e293b;cursor:pointer;outline:none;background:#f4f4f6;position:relative}.sp-dd-btn:focus,.sp-dd-btn:active{outline:none;border-color:rgb(234, 88, 12)}.sp-dd-btn::after{content:'';position:absolute;right:10px;top:50%;transform:translateY(-50%);border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #94a3b8}.sp-dd-list{position:absolute;top:calc(100% + 4px);left:50%;transform:translateX(-50%);background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:99;max-height:180px;overflow-y:auto;min-width:84px;padding:4px}.sp-dd-list::-webkit-scrollbar{width:3px}.sp-dd-list::-webkit-scrollbar-thumb{background:#fed7aa;border-radius:3px}.sp-dd-item{padding:6px 12px;font-size:12px;font-weight:600;border-radius:6px;cursor:pointer;text-align:center;color:#374151;transition:all 0.1s}.sp-dd-item:hover{background:#fff7ed;color:rgb(234, 88, 12)}.sp-dd-item.active{background:rgb(234, 88, 12);color:#fff;font-weight:700}.sp-presets{scrollbar-width:none;-ms-overflow-style:none}.sp-presets::-webkit-scrollbar{display:none;width:0;height:0}`}</style>
                <div className="sp-range" style={{padding:'4px 16px 0'}}>
                    <ReactDatePicker inline selected={rangeStart} onChange={handleRangeChange} startDate={rangeStart} endDate={rangeEnd} selectsRange maxDate={new Date()}
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
                    <button type="button" onClick={()=>{setCalendarOpen(false);setMobileFilterOpen(true);}} disabled={!pendingFrom||!pendingTo}
                        style={{height:'52px',borderRadius:'14px',border:'none',background:(!pendingFrom||!pendingTo)?'#e2e8f0':'rgb(234, 88, 12)',color:(!pendingFrom||!pendingTo)?'#94a3b8':'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:(!pendingFrom||!pendingTo)?'default':'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:(!pendingFrom||!pendingTo)?'none':'0 6px 16px rgba(234,88,12,0.35)'}}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Apply
                    </button>
                </div>
            </div>
        </>)}
        {/* Action buttons — Email / Print / Excel cards (Sales-style) */}
        <div style={{display:'flex',gap:'10px',marginBottom:'12px'}}>
            <button type="button" onClick={()=>emailInvoice('all')} style={{flex:1,height:'46px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                <i className="fa fa-envelope-o" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Email
            </button>
            <button type="button" onClick={()=>printInvoice('all')} style={{flex:1,height:'46px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
            </button>
            <button type="button" onClick={()=>statementInvoice('excel')} disabled={downloadingExcel} style={{flex:1,height:'46px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:downloadingExcel?'default':'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{downloadingExcel ? 'Preparing…' : 'Excel'}
            </button>
        </div>
        <DailyReportEmailModal
            open={emailModalOpen}
            onClose={() => setEmailModalOpen(false)}
            apiUrl={props.emailApi}
            listApi={props.listApi}
            reportTitle="Daily Purchase Report"
            fromDate={fromDate}
            toDate={toDate}
            supplierId={currentSupplier || ''}
        />
        </>
    );
}

function SupplierSelect({ apiUrl, onSubmit }) {
    const dispatch = useDispatch();
    const suppliers = useSelector(state => state.properties.suppliers);
    const loading = useSelector(state => state.properties.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetchSuppliers = async () => {
            try {
                //dispatch(setLoading(true));
                const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setSuppliers(response.data.payload)); // store suppliers in Redux
				}
            } catch (err) {
                console.error('Failed to load suppliers', err);
            } finally {
                //dispatch(setLoading(false));
            }
        };
        fetchSuppliers();
    }, [apiUrl, dispatch]);
	
	const options = [
		{ value: '', label: '-- Select Supplier --' }, // 👈 fake empty option
		...suppliers.map(c => ({
			value: c.id,
			label: c.name,
		})),
	];
	
	const handleChange = (selected) => {
        dispatch(setCurrentSupplier(selected ? selected.value : null));
		dispatch(setCurrentSupplierInfo(suppliers.find(c => c.id === selected.value)));
    };

    const formik = useFormik({
        initialValues: {
            supplier_id: { label: '', value: '' },
        },
        validationSchema: Yup.object({
            supplier_id: Yup.object({
                label: Yup.string().required(),
                value: Yup.string().required('Supplier is required'),
            }).required('Supplier is required'),
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
                        <label className="form-label">Select Supplier*</label>
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
                            {formik.touched.supplier_id && formik.errors.supplier_id ? (
                                <div className="invalid-feedback d-block">{formik.errors.supplier_id}</div>
                            ) : null}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}

function FiltersForm({ apiUrl, onSubmit }) {
	const dispatch = useDispatch();
	const {suppliers, toDate, fromDate, option} = useSelector(state => state.properties);
	
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
        <div className="card">
            <div className="card-body mb-0 pb-0">
                <form onSubmit={formik.handleSubmit}>
                    <div className="row g-3">
                        {/* Date */}
                        <div className="col-lg-6 col-md-12 mb-md-2">
                            <label className="form-label">From Date*</label>
                            <OrangeDatePicker value={fromDate} onChange={(val) => dispatch(setFromDate(val))} />
                            {formik.touched.from_date && formik.errors.from_date ? (
                                <div className="invalid-feedback">{formik.errors.from_date}</div>
                            ) : null}
                        </div>
						<div className="col-lg-6 col-md-12">
                            <label className="form-label">To Date*</label>
                            <OrangeDatePicker value={toDate} onChange={(val) => dispatch(setToDate(val))} />
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
    );
	
}

function ExtraOptions(props) {
	const dispatch = useDispatch();
	const {currentSupplier, selectedInvoices, currentSupplierInfo, suppliers, toDate, fromDate, 
		option} = useSelector(state => state.properties);
		
	const [open, setOpen] = useState(false);
	const openInNewTab = useOpenInNewTab();
	
	const statementInvoice = (e) => {
		const qs = new URLSearchParams();
		if (currentSupplier) qs.set('supplier_id', currentSupplier);
		if (fromDate) qs.set('start_date', fromDate);
		if (toDate) qs.set('end_date', toDate);
		const url = (props.statementApi || '/data_entry/purchase_entry/daily_report/daily_book_purchase/view/statement') + '?' + qs.toString();
		const a = document.createElement('a');
		a.href = url;
		a.download = '';
		document.body.appendChild(a);
		a.click();
		a.remove();
	}
	
	
	const printInvoice = (e) => {
		let url = props.printApi;
			openInNewTab(props.printApi, {
			supplier_id: currentSupplier,
			start_date: fromDate,
			end_date: toDate,
			invoices: selectedInvoices,
			type: e,
		});
	}
	
	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const emailInvoice = () => setEmailModalOpen(true);
	
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
				reportTitle="Daily Purchase Report"
				fromDate={fromDate}
				toDate={toDate}
				supplierId={currentSupplier || ''}
			/>
		</div>
	);
}

function ActionsDropdown({ row }) {
  const { width } = useWindowSize();
  const isMobile = width < 600;

  const iconStyle = {
    width: isMobile ? '38px' : '30px',height: isMobile ? '38px' : '30px',
    borderRadius:'7px',background:'#fff',border:'1px solid #e8e8ec',color:'#6b7280',
    display:'flex',alignItems:'center',justifyContent:'center',
    cursor:'pointer',padding:0,outline:'none',boxShadow:'none',
    transition:'all 0.15s',textDecoration:'none',flexShrink:0,
  };

  return (
    <div style={{display:'flex',gap:'6px',justifyContent:'flex-end'}}>
      <a href={`/data_entry/purchase_entry/invoice/invoiceview/${row.id}`} target="_blank" title="Print Invoice"
        style={iconStyle}
        onMouseEnter={e => {e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}
        onMouseLeave={e => {e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e8e8ec';e.currentTarget.style.color='#6b7280';}}>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
      </a>
      <a href={`/data_entry/purchase_entry/invoice/${row.id}`} title="Edit Invoice"
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
    const { currentSupplier, selectedInvoices, currentSupplierInfo, suppliers, toDate, fromDate, option } =
        useSelector(state => state.properties);
    const { width } = useWindowSize();
    const isDesktop = width >= 768;
    const isTablet = width >= 600 && width < 768;
    const isMobile = !isDesktop && !isTablet;

    const [data, setData] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [pastBalance, setPastBalance] = useState(0);
    const [filterText, setFilterText] = useState("");
    const [selectedRows, setSelectedRows] = useState([]);
    const searchTerm = useSelector(state => state.properties.searchTerm);
    // ── Mobile card/table view state (parity with Sales) ──
    const [mobileTableView, setMobileTableView] = useState(() => localStorage.getItem('ts_purchase_view') === 'table');
    const [expandedCard, setExpandedCard] = useState(null);
    const [page, setPage] = useState(1);
    const [showColFilter, setShowColFilter] = useState(false);
    const colFilterRef = useRef(null);
    const [visibleCols, setVisibleCols] = useState(() => {
        try { const s = localStorage.getItem('ts_purchase_cols'); if (s) return JSON.parse(s); } catch(e) {}
        return { date: true, supplier: true, amount: true };
    });
    const toggleCol = (col) => { const next = { ...visibleCols, [col]: !visibleCols[col] }; setVisibleCols(next); localStorage.setItem('ts_purchase_cols', JSON.stringify(next)); };
    useEffect(() => {
        if (!showColFilter) return;
        const handler = (e) => { if (colFilterRef.current && !colFilterRef.current.contains(e.target)) setShowColFilter(false); };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [showColFilter]);

    const customStyles = useDataTableStyles();

    const mergedStyles = useMemo(() => {
        const cellPad = isMobile ? '10px 8px' : isTablet ? '10px 6px' : '12px 24px';
        return {
            ...customStyles,
            table: {
                ...customStyles?.table,
                style: {
                    ...(customStyles?.table?.style || {}),
                    overflow: 'visible',
                    minHeight: '250px',
                    width: '100%',
                },
            },
            headRow: {
                ...customStyles?.headRow,
                style: {
                    ...(customStyles?.headRow?.style || {}),
                    backgroundColor: '#fafbfc',
                    borderBottomColor: '#eef2f7',
                    borderBottomWidth: '1.5px',
                    borderBottomStyle: 'solid',
                    overflow: 'visible',
                },
            },
            headCells: {
                ...customStyles?.headCells,
                style: {
                    ...(customStyles?.headCells?.style || {}),
                    fontSize: '11px',
                    fontWeight: '700',
                    color: '#9ca3af',
                    letterSpacing: '0.6px',
                    textTransform: 'uppercase',
                    padding: cellPad,
                    whiteSpace: 'nowrap',
                },
            },
            rows: {
                ...customStyles?.rows,
                style: {
                    ...(customStyles?.rows?.style || {}),
                    overflow: 'visible',
                    borderBottomColor: '#f3f4f6',
                    fontSize: '13px',
                    minHeight: '52px',
                },
                highlightOnHoverStyle: {
                    backgroundColor: '#fff7ed',
                    borderBottomColor: '#f1d9c4',
                    outlineColor: '#fed7aa',
                },
            },
            cells: {
                ...customStyles?.cells,
                style: {
                    ...(customStyles?.cells?.style || {}),
                    overflow: 'visible',
                    padding: cellPad,
                    display: 'flex',
                    alignItems: 'center',
                },
            },
        };
    }, [customStyles, isDesktop, isTablet, isMobile]);

    const searchFields = [
        "created_at",
        "id",
        "other_invoice_id",
        "supplier.name",
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

    // Load history — shows orange spinner overlay while AJAX is in flight
    useEffect(() => {
        let cancelled = false;
        const fetchData = async () => {
            setIsLoading(true);
            try {
                const response = await axios.post(props.listApi, {
                    supplier_id:currentSupplier,
                    end_date:toDate,
                    start_date:fromDate,
                    option: option.value
                });
                if (cancelled) return;
                if (response.data.success) {
                    setData(response.data.payload);
                }
            } catch (err) {
                if (!cancelled) console.error("Failed to load history", err);
            } finally {
                if (!cancelled) setIsLoading(false);
            }
        };

        fetchData();
        return () => { cancelled = true; };
    }, [currentSupplier, toDate, fromDate]);


    // selected rows
    const handleRowSelected = (state) => {
		const ids = state.selectedRows.map(r => r.id);
        setSelectedRows(state.selectedRows);
		dispatch(setSelectedInvoices(ids))
    };

    const selectedInvoiceIds = selectedRows.map(r => r.id);

	const toNum = v => Number(v) || 0;

	const edit = (id) => {
		window.location.href = '/data_entry/purchase_entry/invoice/'+id
	}

    const headerStyle = {fontSize:'11px',fontWeight:'800',color:'rgb(31, 41, 55)',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};

    const columns = [
        {
            name: <span style={headerStyle}>Invoice No.</span>,
            selector: row => row.other_invoice_id || row.id,
            cell: row => <a href={`/data_entry/purchase_entry/invoice/${row.id}`} style={{textDecoration:'none'}}><span style={{display:'inline-block',background:'#fff7f0',border:'1px solid #f6c9a8',borderRadius:'6px',padding:'3px 9px',color:'rgb(234, 88, 12)',fontWeight:'800',fontSize:'13px',fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',whiteSpace:'nowrap'}}>#{row.other_invoice_id || row.id}</span></a>,
            sortable: true,
            width: '125px',
        },
        {
            name: <span style={headerStyle}>Date</span>,
            selector: row => row.created_at,
            cell: row => <span style={{fontSize:'12px',color:'#374151',whiteSpace:'nowrap'}}>{row.created_at}</span>,
            sortable: true,
            width: '115px',
        },
        {
            name: <span style={headerStyle}>Supplier Name</span>,
            selector: row => row.supplier?.name || "",
            cell: row => <a href={`/data_entry/purchase_entry/invoice/${row.id}`} style={{color:'#111827',fontWeight:'600',textDecoration:'none',fontSize:'13px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{row.supplier?.name || ''}</a>,
            sortable: true,
            grow: 1,
            minWidth: '140px',
        },
        {
            name: <span style={headerStyle}>Amount</span>,
            selector: row => Number(row.total) || 0,
            cell: row => {
                const hasProducts = row.products_count > 0;
                if (!hasProducts) {
                    return <a href={`/data_entry/purchase_entry/invoice/${row.id}`} style={{display:'inline-flex',alignItems:'center',gap:'3px',fontSize:'10px',fontWeight:'700',color:'#f59e0b',background:'#fffbeb',border:'1px solid #fde68a',padding:'3px 6px',borderRadius:'6px',textDecoration:'none',whiteSpace:'nowrap'}}>
                        <i className="fa fa-exclamation-triangle" style={{fontSize:'9px'}}></i> No Products
                    </a>;
                }
                return <span style={{paddingLeft:'20px',fontWeight:'700',fontSize:'13px',color:'#111827'}}>{props.currency}{Number(row.total).toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>;
            },
            sortable: true,
            width: '155px',
        },
        {
            name: <span style={headerStyle}></span>,
            cell: row => (<div style={{display:'flex',justifyContent:'flex-end',width:'100%'}}><ActionsDropdown row={row} /></div>),
            sortable: false,
            right: true,
            width: '80px',
        },
    ];

    useDropdownFix();

    // ── Desktop / Tablet: original DataTable ──
    if (!isMobile) {
        return (
            <div style={{borderRadius:'0 0 14px 14px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',background:'#fff',overflow:'hidden'}}>
                <style>{`
                    .purchase-scroll-area { -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none; }
                    .purchase-scroll-area::-webkit-scrollbar { display: none; width: 0; height: 0; }
                `}</style>
                <div className="purchase-scroll-area" style={{overflowX:'auto',overflowY:'hidden',position:'relative'}}>
                    <div className="purchase-scroll-inner" style={{minWidth: isTablet && filteredData.length > 0 ? '650px' : 'auto'}}>
                        <DataTable
                            columns={columns}
                            data={filteredData}
                            pagination
                            paginationPerPage={10}
                            paginationRowsPerPageOptions={[10, 25, 50, 100]}
                            paginationComponent={SpecPagination}
                            highlightOnHover
                            customStyles={mergedStyles}
                            progressPending={isLoading && data.length === 0}
                            progressComponent={<SpecTableLoading label="Loading purchases…" />}
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
                    {isLoading && data.length > 0 && (
                        <div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
                            <div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
                                <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                                <span>Loading…</span>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        );
    }

    // ── Mobile: card / table view (parity with Sales) ──
    const perPage = 10;
    const totalPages = Math.ceil(filteredData.length / perPage);
    const safePage = Math.min(page, Math.max(1, totalPages));
    const paginatedData = filteredData.slice((safePage - 1) * perPage, safePage * perPage);
    const fmtAmt = v => Number(v||0).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2});

    // While the first fetch is in flight (no rows yet) show the loader, not the empty
    // state — otherwise the user sees "No records found" before any data has arrived.
    if (isLoading && filteredData.length === 0) {
        return (
            <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 2px rgba(0,0,0,0.03)'}}>
                <SpecTableLoading label="Loading purchases…" />
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

    // Mobile columns for table view — filtered by visibleCols (Invoice + Actions locked)
    const colKeyMap = {'Invoice No.':'invoice','Date':'date','Supplier Name':'supplier','Amount':'amount'};
    const mobileColumns = columns.filter(c => {
        if (c.omit) return false;
        const hdr = c.name?.props?.children || '';
        const key = colKeyMap[hdr];
        if (!key) return true; // actions column or unknown — always show
        if (key === 'invoice') return true; // always show invoice (locked)
        return visibleCols[key] !== false;
    });

    return (
        <div>
            {/* ── View Switcher + Column Filter ── */}
            <div style={{display:'flex',justifyContent: isMobile ? 'flex-end' : 'flex-start',alignItems:'center',gap:'8px',marginBottom:'12px'}}>
                {mobileTableView && (
                    <div ref={colFilterRef} style={{position:'relative'}}>
                        <button onClick={() => setShowColFilter(!showColFilter)} style={{display:'inline-flex',alignItems:'center',gap:'5px',height:'34px',padding:'0 12px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background: showColFilter ? '#fff7ed' : '#fff',cursor:'pointer',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)',transition:'all 0.2s'}}>
                            <i className="fa fa-columns" style={{fontSize:'11px',color: showColFilter ? 'rgb(234, 88, 12)' : '#64748b'}}></i>
                            <span style={{fontSize:'12px',fontWeight:'700',color: showColFilter ? 'rgb(234, 88, 12)' : '#64748b'}}>Columns</span>
                        </button>
                        {showColFilter && (() => {
                            const colItems = [{key:'invoice',label:'Invoice No.',fixed:true},{key:'date',label:'Date'},{key:'supplier',label:'Supplier Name'},{key:'amount',label:'Amount'},{key:'actions',label:'Actions',fixed:true}];
                            const checkedCount = colItems.filter(c => c.fixed || visibleCols[c.key] !== false).length;
                            return (
                            <div style={{position:'absolute',top:'40px',left:0,background:'#fff',borderRadius:'14px',boxShadow:'0 8px 28px rgba(15,23,42,0.16)',border:'1px solid #eef0f3',padding:'14px 8px 8px',zIndex:9999,minWidth:'210px'}}>
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
                <div style={{display:'inline-flex',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e2e8f0',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
                    <button onClick={() => { if(mobileTableView){localStorage.setItem('ts_purchase_view','card');setMobileTableView(false);setShowColFilter(false);} }} style={{display:'inline-flex',alignItems:'center',gap:'6px',height:'34px',padding:'0 16px',border:'none',background: !mobileTableView ? 'rgb(234, 88, 12)' : '#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                        <i className="fa fa-th-large" style={{fontSize:'11px',color: !mobileTableView ? '#fff' : '#64748b'}}></i>
                        <span style={{fontSize:'12px',fontWeight:'700',color: !mobileTableView ? '#fff' : '#64748b'}}>Card View</span>
                    </button>
                    <button onClick={() => { if(!mobileTableView){localStorage.setItem('ts_purchase_view','table');setMobileTableView(true);} }} style={{display:'inline-flex',alignItems:'center',gap:'6px',height:'34px',padding:'0 16px',border:'none',borderLeft:'1.5px solid #e2e8f0',background: mobileTableView ? 'rgb(234, 88, 12)' : '#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                        <i className="fa fa-table" style={{fontSize:'11px',color: mobileTableView ? '#fff' : '#64748b'}}></i>
                        <span style={{fontSize:'12px',fontWeight:'700',color: mobileTableView ? '#fff' : '#64748b'}}>Table View</span>
                    </button>
                </div>
            </div>

            {mobileTableView ? (
                /* ── Mobile Table View ── */
                <div style={{borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 6px rgba(0,0,0,0.05)',background:'#fff',overflow:'hidden',position:'relative'}}>
                    <style>{`.purchase-scroll-area{-webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none}.purchase-scroll-area::-webkit-scrollbar{display:none;width:0;height:0}`}</style>
                    <div className="purchase-scroll-area" style={{overflowX:'auto'}}>
                        <div style={{minWidth:'600px'}}>
                        <DataTable
                            columns={mobileColumns}
                            data={paginatedData}
                            highlightOnHover
                            customStyles={mergedStyles}
                            progressPending={isLoading && paginatedData.length === 0}
                            progressComponent={<SpecTableLoading label="Loading purchases…" />}
                            noDataComponent={<div style={{padding:'40px 24px',textAlign:'center'}}><div style={{width:'60px',height:'60px',margin:'0 auto 12px',borderRadius:'14px',background:'#fafafb',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#9ca3af'}}><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></div><div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No records found</div><div style={{fontSize:'13px',color:'#6b7280',marginTop:'4px',maxWidth:'380px',marginInline:'auto'}}>Try changing the date range or search term. New invoices appear here as soon as you create them.</div></div>}
                        />
                        </div>
                    </div>
                    {totalPages > 1 && (
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'10px 14px',borderTop:'1px solid #f1f5f9',flexWrap:'wrap',gap:'8px'}}>
                            <span style={{fontSize:'12px',color:'#6b7280',fontWeight:'500'}}>{(safePage-1)*perPage+1}–{Math.min(safePage*perPage,filteredData.length)} of {filteredData.length}</span>
                            <div style={{display:'flex',alignItems:'center',gap:'4px'}}>
                                <button onClick={()=>setPage(p=>Math.max(1,p-1))} disabled={safePage===1} style={{height:'30px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e5e7eb',background:'#fff',color:safePage===1?'#d1d5db':'#374151',fontWeight:'600',fontSize:'12px',cursor:safePage===1?'default':'pointer',outline:'none'}}>←</button>
                                <span style={{fontSize:'12px',fontWeight:'600',color:'#374151',padding:'0 6px'}}>{safePage} / {totalPages}</span>
                                <button onClick={()=>setPage(p=>Math.min(totalPages,p+1))} disabled={safePage===totalPages} style={{height:'30px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e5e7eb',background:'#fff',color:safePage===totalPages?'#d1d5db':'#374151',fontWeight:'600',fontSize:'12px',cursor:safePage===totalPages?'default':'pointer',outline:'none'}}>→</button>
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
                        <span>Loading purchases…</span>
                    </div>
                </div>
            )}
            <div style={{display:'flex',flexDirection:'column',gap:'10px',opacity:isLoading?0.6:1,transition:'opacity 0.15s'}}>
                {paginatedData.map(row => {
                    const total = toNum(row.total);
                    const hasProducts = (row.products_count || 0) > 0;
                    const isExpanded = expandedCard === row.id;
                    return (
                        <div key={row.id} style={{display:'flex',marginBottom:'0',borderRadius:'14px',border:'1px solid #f1f5f9',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                            <div style={{width:'4px',flexShrink:0,background:'rgb(234, 88, 12)'}}/>
                            <div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
                                <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px'}}>
                                    <div style={{minWidth:0}}>
                                        <div style={{fontSize:'11px',color:'rgb(234, 88, 12)',fontWeight:'700',marginBottom:'4px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',display:'flex',alignItems:'center',gap:'8px'}}>
                                            <a href={`/data_entry/purchase_entry/invoice/${row.id}`} onClick={e=>e.stopPropagation()} style={{color:'rgb(234, 88, 12)',textDecoration:'none'}}>#{row.other_invoice_id||row.id}</a>
                                            {row.created_at ? <span style={{fontWeight:'500',color:'#6b7280'}}>{row.created_at}</span> : ''}
                                        </div>
                                        <div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',marginBottom:'6px'}}>{row.supplier?.name || '—'}</div>
                                        {!hasProducts && (
                                            <div style={{display:'flex',flexWrap:'wrap',gap:'8px',alignItems:'center',marginTop:'6px'}}>
                                                <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'10px',fontWeight:'700',color:'#f59e0b',background:'#fffbeb',border:'1px solid #fde68a',padding:'2px 8px',borderRadius:'20px',whiteSpace:'nowrap'}}><i className="fa fa-exclamation-triangle" style={{fontSize:'9px'}}></i> No Products</span>
                                            </div>
                                        )}
                                    </div>
                                    <div style={{display:'flex',flexDirection:'column',alignItems:'flex-end',gap:'6px',flexShrink:0}}>
                                        {hasProducts ? (
                                            <span style={{background:'#FFF7ED',border:'1px solid #fed7aa',borderRadius:'8px',padding:'3px 10px',fontWeight:'800',color:'rgb(234, 88, 12)',fontSize:'13px',whiteSpace:'nowrap'}}>{props.currency} {fmtAmt(total)}</span>
                                        ) : null}
                                        <button onClick={() => setExpandedCard(isExpanded ? null : row.id)} style={{background:'none',border:'none',cursor:'pointer',padding:'4px',display:'flex',alignItems:'center',justifyContent:'center',outline:'none'}}>
                                            <i className={"fa fa-chevron-" + (isExpanded ? 'up' : 'down')} style={{fontSize:'12px',color: isExpanded ? 'rgb(234, 88, 12)' : '#94a3b8',transition:'all 0.2s'}}></i>
                                        </button>
                                    </div>
                                </div>
                                {isExpanded && (
                                    <div style={{display:'flex',gap:'8px',marginTop:'10px',paddingTop:'10px',borderTop:'1px solid #f1f5f9'}}>
                                        <a href={`/data_entry/purchase_entry/invoice/${row.id}`} style={{flex:1,height:'36px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-pencil" style={{fontSize:'11px'}}></i> Edit
                                        </a>
                                        <a href={`/data_entry/purchase_entry/invoice/invoiceview/${row.id}`} target="_blank" style={{flex:1,height:'36px',background:'#fff',border:'1.5px solid #e2e8f0',color:'#64748b',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-print" style={{fontSize:'11px'}}></i> Print
                                        </a>
                                        <a href={`/data_entry/purchase_entry/invoice/invoiceexcel/${row.id}`} style={{flex:1,height:'36px',background:'#fff',border:'1.5px solid #e2e8f0',color:'#64748b',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'5px',textDecoration:'none'}}>
                                            <i className="fa fa-download" style={{fontSize:'11px'}}></i> DL
                                        </a>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {totalPages > 1 && (
                <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'18px 0 4px',flexWrap:'wrap',gap:'10px'}}>
                    <div style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>Showing {(safePage - 1) * perPage + 1}–{Math.min(safePage * perPage, filteredData.length)} of {filteredData.length}</div>
                    <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                        <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={safePage === 1} style={{height:'32px',padding:'0 12px',borderRadius:'8px',border:'1.5px solid #e5e7eb',background:'#fff',color: safePage === 1 ? '#d1d5db' : '#374151',fontWeight:'600',fontSize:'13px',cursor: safePage === 1 ? 'default' : 'pointer',outline:'none'}}>← Prev</button>
                        {Array.from({length: totalPages}, (_, i) => i + 1).filter(p => p === 1 || p === totalPages || Math.abs(p - safePage) <= 1).reduce((acc, p, idx, arr) => { if (idx > 0 && arr[idx - 1] !== p - 1) acc.push('...'); acc.push(p); return acc; }, []).map((item, idx) => item === '...' ? (<span key={'e' + idx} style={{padding:'0 4px',color:'#9ca3af',fontSize:'13px'}}>…</span>) : (<button key={item} onClick={() => setPage(item)} style={{height:'32px',minWidth:'32px',padding:'0 8px',borderRadius:'8px',border:'1.5px solid',borderColor: safePage === item ? 'rgb(234, 88, 12)' : '#e5e7eb',background: safePage === item ? 'rgb(234, 88, 12)' : '#fff',color: safePage === item ? '#fff' : '#374151',fontWeight: safePage === item ? '700' : '500',fontSize:'13px',cursor:'pointer',outline:'none'}}>{item}</button>))}
                        <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={safePage === totalPages} style={{height:'32px',padding:'0 12px',borderRadius:'8px',border:'1.5px solid #e5e7eb',background:'#fff',color: safePage === totalPages ? '#d1d5db' : '#374151',fontWeight:'600',fontSize:'13px',cursor: safePage === totalPages ? 'default' : 'pointer',outline:'none'}}>Next →</button>
                    </div>
                </div>
            )}
            </>)}
        </div>
    );
}

// ----------------- Date Range Picker (using imported hooks/DateRangePicker.js) -----------------
function DateRangePickerLocal_UNUSED({ fromDate, toDate, onFromChange, onToChange }) {
    const [isOpen, setIsOpen] = useState(false);
    const ref = useRef(null);
    const startDate = fromDate ? new Date(fromDate + 'T00:00:00') : null;
    const endDate = toDate ? new Date(toDate + 'T00:00:00') : null;

    const formatDisplay = (date) => {
        if (!date) return '—';
        const d = new Date(date + 'T00:00:00');
        return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
    };
    const toYMD = (date) => { const y=date.getFullYear(),m=String(date.getMonth()+1).padStart(2,'0'),d=String(date.getDate()).padStart(2,'0'); return `${y}-${m}-${d}`; };
    const handleChange = (dates) => { const [start,end]=dates; if(start) onFromChange(toYMD(start)); if(end){onToChange(toYMD(end));setIsOpen(false);} else{onToChange('');} };
    const applyPreset = (label) => {
        const now=new Date(); let from,to;
        if(label==='Today'){from=to=now;} else if(label==='Yesterday'){from=to=new Date(now.getTime()-86400000);} else if(label==='Last 7 days'){from=new Date(now.getTime()-6*86400000);to=now;} else if(label==='Last 30 days'){from=new Date(now.getTime()-29*86400000);to=now;} else if(label==='This month'){from=new Date(now.getFullYear(),now.getMonth(),1);to=now;}
        onFromChange(toYMD(from)); onToChange(toYMD(to)); setIsOpen(false);
    };
    useEffect(() => { const handle=(e)=>{if(ref.current&&!ref.current.contains(e.target))setIsOpen(false);}; document.addEventListener('mousedown',handle); return ()=>document.removeEventListener('mousedown',handle); }, []);

    return (
        <div ref={ref} style={{position:'relative',flexShrink:0}}>
            <button type="button" onClick={()=>setIsOpen(!isOpen)} style={{display:'inline-flex',alignItems:'center',gap:'8px',height:'38px',background:'#fafafa',border:'1.5px solid #e5e7eb',borderRadius:'9px',padding:'0 14px',cursor:'pointer',outline:'none',transition:'border-color 0.15s',minWidth:'220px',...(isOpen?{borderColor:'rgb(234, 88, 12)',background:'#fff'}:{})}}>
                <i className="fa fa-calendar" style={{fontSize:'12px',color:'rgb(234, 88, 12)'}}></i>
                {(!fromDate && !toDate) ? (
                    <span style={{fontSize:'13px',fontWeight:'500',color:'#9ca3af'}}>Select date range</span>
                ) : (<>
                    <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(fromDate)}</span>
                    <i className="fa fa-arrow-right" style={{fontSize:'9px',color:'#d1d5db'}}></i>
                    <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(toDate)}</span>
                </>)}
            </button>
            {isOpen && (<>
                <div style={{position:'fixed',top:0,left:0,right:0,bottom:0,zIndex:9998}} onClick={()=>setIsOpen(false)}></div>
                <div style={{position:'absolute',top:'calc(100% + 8px)',right:0,zIndex:9999,background:'#fff',borderRadius:'16px',boxShadow:'0 16px 48px rgba(0,0,0,0.18),0 0 0 1px rgba(0,0,0,0.05)',display:'flex',overflow:'hidden',animation:'drpFadeIn 0.15s ease-out'}}>
                    <style>{`
                        @keyframes drpFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
                        .drp-cal2 .react-datepicker{border:none;font-family:inherit;background:transparent;display:flex!important;flex-direction:row!important;}
                        .drp-cal2 .react-datepicker__month-container{padding:0 8px;}
                        .drp-cal2 .react-datepicker__month-container+.react-datepicker__month-container{border-left:1px solid #f0f0f0;}
                        .drp-cal2 .react-datepicker__header{background:transparent;border-bottom:none;padding-top:4px;}
                        .drp-cal2 .react-datepicker__current-month{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:6px;padding:2px 0;}
                        .drp-cal2 .react-datepicker__day-names{margin-bottom:4px;}
                        .drp-cal2 .react-datepicker__day-name{font-size:10px;font-weight:700;color:#94a3b8;width:32px;line-height:24px;text-transform:uppercase;letter-spacing:0.5px;}
                        .drp-cal2 .react-datepicker__day{width:32px;height:32px;line-height:32px;font-size:12px;border-radius:8px;margin:1px;font-weight:600;color:#1e293b;transition:all 0.1s;}
                        .drp-cal2 .react-datepicker__day:hover{background:#1e293b;color:#fff;}
                        .drp-cal2 .react-datepicker__day--today{background:#fef3e2;font-weight:700;color:#ea580c;}
                        .drp-cal2 .react-datepicker__day--selected,.drp-cal2 .react-datepicker__day--range-start,.drp-cal2 .react-datepicker__day--range-end{background:rgb(234, 88, 12)!important;color:#fff!important;font-weight:700;position:relative;z-index:2;box-shadow:inset 0 0 0 2px rgba(255,255,255,0.3);}
                        .drp-cal2 .react-datepicker__day--in-range{background:#ea580c!important;color:#fff!important;border-radius:3px!important;font-weight:500;box-shadow:none;margin:1px 0!important;}
                        .drp-cal2 .react-datepicker__day--in-range:hover{background:rgb(234, 88, 12)!important;color:#fff!important;}
                        .drp-cal2 .react-datepicker__day--range-start{border-radius:8px 3px 3px 8px!important;}
                        .drp-cal2 .react-datepicker__day--range-end{border-radius:3px 8px 8px 3px!important;}
                        .drp-cal2 .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:8px!important;}
                        .drp-cal2 .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--range-start){background:#fb923c!important;color:#fff!important;border-radius:3px!important;}
                        .drp-cal2 .react-datepicker__day--outside-month{color:#cbd5e1!important;font-weight:400;}
                        .drp-cal2 .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b;}
                        .drp-cal2 .react-datepicker__day--disabled{color:#94a3b8!important;cursor:not-allowed;background:transparent!important;font-weight:400;}
                        .drp-cal2 .react-datepicker__navigation{top:14px;width:28px;height:28px;border:1.5px solid #e5e7eb;border-radius:6px;background:#fff;}
                        .drp-cal2 .react-datepicker__navigation:hover{background:#1e293b;border-color:#1e293b;}
                        .drp-cal2 .react-datepicker__navigation-icon::before{border-color:#6b7280;border-width:2px 2px 0 0;width:7px;height:7px;top:8px;}
                        .drp-cal2 .react-datepicker__navigation:hover .react-datepicker__navigation-icon::before{border-color:#fff;}
                    `}</style>
                    <div style={{borderRight:'1px solid #f0f0f0',padding:'16px 10px',display:'flex',flexDirection:'column',gap:'2px',minWidth:'130px',background:'#fafafa'}}>
                        <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'1.2px',textTransform:'uppercase',padding:'4px 10px 10px',borderBottom:'1px solid #e5e7eb',marginBottom:'6px'}}>Quick Select</div>
                        {['Today','Yesterday','Last 7 days','Last 30 days','This month'].map(label=>(
                            <button key={label} type="button" onClick={()=>applyPreset(label)} style={{border:'none',background:'transparent',padding:'9px 12px',fontSize:'13px',fontWeight:'500',color:'#374151',cursor:'pointer',borderRadius:'8px',textAlign:'left',transition:'all 0.15s'}}
                            onMouseEnter={e=>{e.target.style.background='#fff7ed';e.target.style.color='#c2410c';e.target.style.fontWeight='600';}}
                            onMouseLeave={e=>{e.target.style.background='transparent';e.target.style.color='#374151';e.target.style.fontWeight='500';}}>{label}</button>
                        ))}
                    </div>
                    <div style={{padding:'16px 12px 12px'}} className="drp-cal2">
                        <DatePicker selected={startDate} onChange={handleChange} startDate={startDate} endDate={endDate} selectsRange inline monthsShown={2} maxDate={new Date()} openToDate={new Date(new Date().getFullYear(), new Date().getMonth()-1, 1)} />
                        <div style={{display:'flex',alignItems:'center',justifyContent:'center',gap:'0',padding:'12px 0 4px',borderTop:'1px solid #f0f0f0',marginTop:'8px'}}>
                            <div style={{background:'#1e293b',borderRadius:'8px 0 0 8px',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color:'#fff',display:'flex',alignItems:'center',gap:'8px'}}><i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>{formatDisplay(fromDate)}</div>
                            <div style={{background:'#334155',padding:'8px 12px',display:'flex',alignItems:'center'}}><i className="fa fa-arrow-right" style={{fontSize:'10px',color:'#94a3b8'}}></i></div>
                            <div style={{background:toDate?'#1e293b':'#475569',borderRadius:'0 8px 8px 0',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color:toDate?'#fff':'#94a3b8',display:'flex',alignItems:'center',gap:'8px'}}><i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>{toDate?formatDisplay(toDate):'Select end'}</div>
                        </div>
                    </div>
                </div>
            </>)}
        </div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('daily-book-purchase-app')) {
    const id = "daily-book-purchase-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<DailyBookPurchaseApp {...props} />
		</Provider>
    );
}

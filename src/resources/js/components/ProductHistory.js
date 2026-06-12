import React, { useEffect, useState,useMemo,useRef,useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray,Formik,Form,Field,useFormikContext  } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import axios from 'axios';
import logger from 'redux-logger';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faChevronRight, faChevronDown } from "@fortawesome/free-solid-svg-icons";
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';

import { useToast } from "./../hooks/useToast";
import useOpenInNewTab from "./../hooks/useOpenInNewTab";
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import DateRangePicker from "./../hooks/DateRangePicker";

const formatDate = (date) => date.toISOString().slice(0, 10);

// Convert single-or-array select state into a comma-joined string for the backend.
const valueCsv = (v) => Array.isArray(v) ? v.map(x => x?.value ?? x).filter(Boolean).join(',') : (v?.value ?? v ?? '');

const slice = createSlice({
    name: 'products',
    initialState: {
		timeSlotMonths:6,
		suppliers: [],
		customers: [],
		products: [],
		selectedInvoices: [],

		currentSupplier:[],
		currentCustomer:[],
		currentProduct:"",

		currentSupplierInfo:"",
		currentCustomerInfo:"",
		currentProductInfo:"",

		loading: false,
		refreshPayments: 0,

		toDate: formatDate(new Date()),
		fromDate: formatDate(new Date()),

		option:{label:"All",value:"all"},
		movementType:[]
	},
    reducers: {
        setSuppliers: (state, action) => { state.suppliers = action.payload },
        setCustomers: (state, action) => { state.customers = action.payload },
        setProducts: (state, action) => { state.products = action.payload },

		setToDate: (state, action) => { state.toDate = action.payload },
		setFromDate: (state, action) => { state.fromDate = action.payload },
		setSelectedInvoices: (state, action) => { state.selectedInvoices = action.payload },
		setOption: (state, action) => { state.option = action.payload },

        setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
        setCurrentProduct: (state, action) => { state.currentProduct = action.payload; },

		setCurrentSupplierInfo: (state, action) => { state.currentSupplierInfo = action.payload; },
		setCurrentCustomerInfo: (state, action) => { state.currentCustomerInfo = action.payload; },
		setCurrentProductInfo: (state, action) => { state.currentProductInfo = action.payload; },

		setSuppliersLoading: (state, action) => { state.loading = action.payload; },
		setMovementType: (state, action) => { state.movementType = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now();
        },
    },
});

const { setSelectedInvoices, setToDate, setFromDate, setOption, setMovementType, setSuppliers, setCustomers, setProducts,
	setCurrentProductInfo, setCurrentCustomerInfo,
setCurrentSupplier, setCurrentProduct, setCurrentCustomer, setCurrentSupplierInfo, setSuppliersLoading, triggerPaymentRefresh } = slice.actions;

const store = configureStore({
    reducer: { products: slice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger),
	devTools: process.env.NODE_ENV !== 'production',
});

// ---- Email Report Modal ----
function EmailReportModal({ open, onClose, emailApi, product, customer, supplier, movementType, fromDate, toDate }) {
	const periodLabel = () => {
		const fmt = (d) => {
			if (!d) return '';
			const dt = new Date(d);
			return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
		};
		if (!fromDate && !toDate) return 'All time';
		return `${fmt(fromDate) || '—'} – ${fmt(toDate) || 'Today'}`;
	};

	const productName = product ? product.label : '';
	const defaultSubject = () => `Product History Report — ${productName} — ${periodLabel()}`;
	const defaultMessage = () =>
		`Hello,\n\nPlease find attached the product history report for "${productName}" for the period ${periodLabel()}.\n\nKindly review and let us know if you have any questions.\n\nThank you.`;

	const [toEmail, setToEmail] = useState('');
	const [subject, setSubject] = useState('');
	const [message, setMessage] = useState('');
	const [sending, setSending] = useState(false);
	const [errors, setErrors] = useState({});

	useEffect(() => {
		if (open) {
			setToEmail('');
			setSubject(defaultSubject());
			setMessage(defaultMessage());
			setErrors({});
			setSending(false);
		}
	}, [open]);

	if (!open) return null;

	const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	const toEmailError = !toEmail.trim()
		? 'Recipient email is required'
		: (!emailRe.test(toEmail.trim()) ? 'Enter a valid email address (e.g. name@gmail.com)' : '');
	const toEmailValid = toEmailError === '';

	const valueCsv = (val) => Array.isArray(val) ? val.join(',') : (val?.value ?? val ?? '');

	const validate = () => {
		const e = {};
		if (toEmailError) e.toEmail = toEmailError;
		if (!subject.trim()) e.subject = 'Subject is required';
		if (!message.trim()) e.message = 'Message is required';
		setErrors(e);
		return Object.keys(e).length === 0;
	};

	const handleSend = () => {
		if (sending) return;
		if (!validate()) return;
		setSending(true);
		axios.post(emailApi, {
			product_id: product?.value,
			customer_id: valueCsv(customer),
			supplier_id: valueCsv(supplier),
			start_date: fromDate || '',
			end_date: toDate || '',
			movement_type: valueCsv(movementType) || 'all',
			to_email: toEmail.trim(),
			subject: subject.trim(),
			message: message.trim(),
		})
		.then(res => {
			if (res.data && res.data.success === true) {
				toast.success((res.data.payload && res.data.payload.message) || 'Report emailed successfully');
				onClose();
			} else {
				const msg = res.data && typeof res.data.payload === 'string' ? res.data.payload : 'Could not send the report email.';
				toast.error(msg);
			}
		})
		.catch(() => toast.error('Something went wrong while sending the email.'))
		.finally(() => setSending(false));
	};

	const label = { fontSize: '11px', fontWeight: '700', color: '#6b7280', letterSpacing: '0.4px', textTransform: 'uppercase', marginBottom: '6px', display: 'block' };
	const inputBase = { width: '100%', height: '40px', borderRadius: '9px', border: '1.5px solid #e8e8ec', padding: '0 12px', fontSize: '13px', color: '#0f1115', outline: 'none', fontFamily: 'inherit', boxSizing: 'border-box' };
	const errText = (m) => m ? <span style={{ fontSize: '11px', color: '#dc2626', fontWeight: '600', marginTop: '4px', display: 'block' }}>{m}</span> : null;

	return (
		<div onClick={onClose} style={{ position: 'fixed', inset: 0, background: 'rgba(15,17,21,0.45)', zIndex: 9000, display: 'flex', alignItems: 'flex-start', justifyContent: 'center', padding: '70px 16px 24px', overflowY: 'auto' }}>
			<div onClick={e => e.stopPropagation()} style={{ background: '#fff', borderRadius: '14px', width: '100%', maxWidth: '460px', boxShadow: '0 24px 60px -12px rgba(15,17,21,0.4)', overflow: 'hidden' }}>
				<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 22px', borderBottom: '1px solid #eeeeef' }}>
					<span style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fff7ed', border: '1px solid #fed7aa', color: '#c2410c', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
					</span>
					<div style={{ flex: 1, minWidth: 0 }}>
						<h3 style={{ margin: 0, fontSize: '15.5px', fontWeight: '800', color: '#0f1115' }}>Email Report</h3>
						<p style={{ margin: '2px 0 0', fontSize: '12px', color: '#6b7280' }}>Send the product history report</p>
					</div>
					<button onClick={onClose} style={{ width: '30px', height: '30px', borderRadius: '8px', border: '1px solid #e8e8ec', background: '#fff', color: '#6b7280', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
				</div>

				<div style={{ padding: '20px 22px', maxHeight: '60vh', overflowY: 'auto' }}>
					<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#fafafb', border: '1px solid #e8e8ec', borderRadius: '9px', padding: '10px 13px', marginBottom: '16px' }}>
						<div>
							<div style={{ fontSize: '10px', fontWeight: '700', color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.4px' }}>Product</div>
							<div style={{ fontSize: '13px', fontWeight: '700', color: '#0f1115', marginTop: '2px' }}>{productName}</div>
						</div>
						<div style={{ textAlign: 'right' }}>
							<div style={{ fontSize: '10px', fontWeight: '700', color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.4px' }}>Period</div>
							<div style={{ fontSize: '13px', fontWeight: '700', color: '#0f1115', marginTop: '2px' }}>{periodLabel()}</div>
						</div>
					</div>

					<div style={{ marginBottom: '14px' }}>
						<label style={label}>To</label>
						<input type="email" value={toEmail} onChange={e => setToEmail(e.target.value)}
							placeholder="recipient@email.com"
							style={{ ...inputBase, borderColor: toEmailValid ? '#e8e8ec' : '#dc2626' }} />
						{errText(toEmailError)}
					</div>

					<div style={{ marginBottom: '14px' }}>
						<label style={label}>Subject</label>
						<input type="text" value={subject} onChange={e => setSubject(e.target.value)}
							style={{ ...inputBase, borderColor: errors.subject ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.subject)}
					</div>

					<div>
						<label style={label}>Message</label>
						<textarea value={message} onChange={e => setMessage(e.target.value)} rows={6}
							style={{ ...inputBase, height: 'auto', padding: '10px 12px', resize: 'vertical', lineHeight: '1.5', borderColor: errors.message ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.message)}
					</div>
				</div>

				<div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', padding: '14px 22px', borderTop: '1px solid #eeeeef', background: '#fafafb' }}>
					<button onClick={onClose} disabled={sending}
						style={{ height: '40px', padding: '0 18px', borderRadius: '9px', border: '1.5px solid #e8e8ec', background: '#fff', color: '#6b7280', fontWeight: '700', fontSize: '13px', cursor: sending ? 'not-allowed' : 'pointer' }}>
						Cancel
					</button>
					<button onClick={handleSend} disabled={sending || !toEmailValid}
						style={{ height: '40px', padding: '0 20px', borderRadius: '9px', border: 'none', background: (sending || !toEmailValid) ? '#fdba74' : 'rgb(234, 88, 12)', color: '#fff', fontWeight: '700', fontSize: '13px', cursor: (sending || !toEmailValid) ? 'not-allowed' : 'pointer', display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
						{sending
							? <><svg width="14" height="14" viewBox="0 0 24 24" style={{ animation: 'ph-spin 0.7s linear infinite' }}><circle cx="12" cy="12" r="9" fill="none" stroke="rgba(255,255,255,0.4)" strokeWidth="3"/><path d="M12 3a9 9 0 0 1 9 9" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round"/></svg> Sending…</>
							: <><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Report</>}
					</button>
				</div>
				<style>{`@keyframes ph-spin{to{transform:rotate(360deg)}}`}</style>
			</div>
		</div>
	);
}

function Filters(props) {
    const dispatch = useDispatch();
    const {
		currentSupplier, currentCustomer, currentProduct, currentSupplierInfo, currentCustomerInfo, currentProductInfo,
		suppliers, fromDate, toDate, customers, products, movementType
	} = useSelector(state => state.products);

    const loading = useSelector(state => state.products.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetch = async () => {
            try {
                const response1 = await axios.get(props.productListApi);
				if(response1.data.success === true){
					dispatch(setProducts(response1.data.payload));
					const urlProductId = new URLSearchParams(window.location.search).get('product');
					if (urlProductId) {
						const found = response1.data.payload.find(p => String(p.id) === String(urlProductId));
						if (found) {
							const selected = { value: found.id, label: found.name };
							dispatch(setCurrentProduct(selected));
							dispatch(setCurrentProductInfo(found));
						}
					}
				}

				const response2 = await axios.get(props.customerListApi);
				if(response2.data.success === true){
					dispatch(setCustomers(response2.data.payload));
				}

				const response3 = await axios.get(props.supplierListApi);
				if(response3.data.success === true){
					dispatch(setSuppliers(response3.data.payload));
				}
            } catch (err) {
                console.error('Failed to load ', err);
            }
        };
        fetch();
    }, []);

	const supplier_options = suppliers.map(c => ({ value: c.id, label: c.name }));
	const customer_options = customers.map(c => ({ value: c.id, label: c.name }));
	const product_options  = products.map(c => ({ value: c.id, label: c.name }));

	const [phIsMobile, setPhIsMobile] = useState(typeof window !== 'undefined' && window.innerWidth <= 767);
	useEffect(() => {
		const h = () => setPhIsMobile(window.innerWidth <= 767);
		window.addEventListener('resize', h);
		return () => window.removeEventListener('resize', h);
	}, []);

	const formik = useFormik({
        initialValues: {
            supplier_id: { label: '', value: '' },
            product_id: { label: '', value: '' },
            customer_id: { label: '', value: '' },
            start_date: fromDate,
            end_date: toDate,
        },
        validationSchema: Yup.object({
            product_id: Yup.object({
                label: Yup.string().required(),
                value: Yup.string().required('Product is required'),
            }).required('Product is required'),
        }),
        onSubmit: values => {},
    });

	const handleSupplierChange = (selected) => {
		const arr = selected || [];
		formik.setFieldValue("supplier_id", arr);
        dispatch(setCurrentSupplier(arr));
    };

	const handleProductChange = (selected) => {
		formik.setFieldValue("product_id", selected);
		dispatch(setCurrentProduct(selected));
		dispatch(setCurrentProductInfo(selected ? products.find(c => c.id === selected.value) : null));
    };

	const handleCustomerChange = (selected) => {
		const arr = selected || [];
		formik.setFieldValue("customer_id", arr);
        dispatch(setCurrentCustomer(arr));
    };

	const h = '44px';
	const lblStyle = {fontSize:'10.5px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'8px',display:'block'};
	const selectCtrl = {
		control: (base, state) => ({
			...base,
			minHeight:h,borderRadius:'12px',
			border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
			background: state.isFocused ? '#fff' : '#f8fafc',
			boxShadow: state.isFocused ? '0 0 0 4px rgba(234,88,12,0.08)' : 'none',
			transition:'all 0.2s ease','&:hover':{borderColor:'#cbd5e1'},
		}),
		valueContainer: (base) => ({...base,minHeight:h,padding:'4px 12px',gap:'4px'}),
		indicatorsContainer: (base) => ({...base,minHeight:h}),
		placeholder: (base) => ({...base,fontSize:'13px',color:'#94a3b8',fontWeight:'500'}),
		singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
		menu: (base) => ({...base,borderRadius:'12px',border:'1px solid #e8ecf2',boxShadow:'0 12px 36px rgba(0,0,0,0.1)',overflow:'hidden',marginTop:'4px',zIndex:10}),
		menuPortal: (base) => ({...base,zIndex:9999}),
		option: (base, state) => ({
			...base,fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',
			backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : state.isFocused ? 'rgb(234, 88, 12)' : '#334155',
		}),
		input: (base) => ({...base,fontSize:'13px'}),
		multiValue: (base) => ({...base,background:'#FFF5ED',border:'1px solid #fed7aa',borderRadius:'6px'}),
		multiValueLabel: (base) => ({...base,color:'rgb(234, 88, 12)',fontWeight:'600',fontSize:'12.5px'}),
		multiValueRemove: (base) => ({...base,color:'rgb(234, 88, 12)',borderRadius:'4px',':hover':{background:'#fed7aa',color:'rgb(234, 88, 12)'}}),
	};

    const txnOptions = [
		{value:'supplier',  label:'Purchase'},
		{value:'supplier-returns', label:'Sup. Return'},
		{value:'sales',     label:'Sales'},
		{value:'customer-returns', label:'Cus. Return'},
		{value:'dumps',     label:'Dump'},
	];

	const iconBtn = {
		width:'42px',height:'42px',borderRadius:'10px',padding:0,
		background:'#fff',border:'1px solid #e8e8ec',color:'#6b7280',
		display:'flex',alignItems:'center',justifyContent:'center',
		cursor:'pointer',outline:'none',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',
		transition:'all 0.15s',
	};
	const hoverIn  = e => { e.currentTarget.style.borderColor='rgb(234, 88, 12)'; e.currentTarget.style.color='rgb(234, 88, 12)'; };
	const hoverOut = e => { e.currentTarget.style.borderColor='#e8e8ec'; e.currentTarget.style.color='#6b7280'; };

	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const openEmailModal = () => {
		if (!currentProduct?.value) { toast.error('Please select a product first.'); return; }
		setEmailModalOpen(true);
	};

    return (
        <div className="ph-filters" style={ phIsMobile ? {
			background:'transparent',
		} : {
			borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',background:'#fff',
			boxShadow:'0 4px 16px rgba(0,0,0,0.04)',
		}}>
			<div className="ph-filter-panel" style={{padding:'16px 20px 18px',display:'flex',flexDirection:'column',gap:'14px',background:'#fff', ...(phIsMobile ? {borderRadius:'18px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'} : {})}}>
				{/* Row 1 — Primary entity filters: Product, Customer, Supplier */}
				<div className="ph-filter-row-primary" style={{display:'grid',gridTemplateColumns:'repeat(3, 1fr)',gap:'12px'}}>
					<div className="ph-filter-product">
						<label style={lblStyle}>Product <span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
						<div style={{position:'relative'}}>
							<span style={{position:'absolute',left:'13px',top:'50%',transform:'translateY(-50%)',color:'rgb(234, 88, 12)',zIndex:1,pointerEvents:'none',display:'flex',alignItems:'center'}}>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8M12 13v8"/></svg>
							</span>
							<Select styles={{...selectCtrl, valueContainer: (base) => ({...base,minHeight:h,padding:'4px 12px 4px 38px',gap:'4px'})}}
								options={product_options}
								value={product_options.find(o => o.value === (currentProduct?.value ?? currentProduct))}
								onChange={handleProductChange}
								placeholder="Select product"
								isClearable isSearchable
								classNamePrefix="react-select"
								menuPortalTarget={document.body}
							/>
						</div>
					</div>
					<div className="ph-filter-customer">
						<label style={lblStyle}>Customers</label>
						<Select styles={selectCtrl}
							options={customer_options}
							value={Array.isArray(currentCustomer) ? currentCustomer : []}
							onChange={handleCustomerChange}
							placeholder="All customers"
							isMulti isClearable isSearchable closeMenuOnSelect={false}
							classNamePrefix="react-select"
							menuPortalTarget={document.body}
						/>
					</div>
					<div className="ph-filter-supplier">
						<label style={lblStyle}>Suppliers</label>
						<Select styles={selectCtrl}
							options={supplier_options}
							value={Array.isArray(currentSupplier) ? currentSupplier : []}
							onChange={handleSupplierChange}
							placeholder="All suppliers"
							isMulti isClearable isSearchable closeMenuOnSelect={false}
							classNamePrefix="react-select"
							menuPortalTarget={document.body}
						/>
					</div>
				</div>

				{/* Row 2 — Movement type, date range, action icons */}
				<div className="ph-filter-row-secondary" style={{display:'grid',gridTemplateColumns:'1fr auto auto',gap:'12px',alignItems:'end'}}>
					<div className="ph-filter-txn">
						<label style={lblStyle}>Movement Type</label>
						<div style={{position:'relative'}}>
							<span style={{position:'absolute',left:'13px',top:'50%',transform:'translateY(-50%)',color:'rgb(234, 88, 12)',zIndex:1,pointerEvents:'none',display:'flex',alignItems:'center'}}>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 5h18l-7 8v6l-4-2v-4z"/></svg>
							</span>
							<Select styles={{...selectCtrl, valueContainer: (base) => ({...base,minHeight:h,padding:'4px 12px 4px 38px',gap:'4px'})}}
								options={txnOptions}
								value={Array.isArray(movementType) ? txnOptions.filter(o => movementType.includes(o.value)) : []}
								onChange={(selected) => dispatch(setMovementType(selected ? selected.map(o => o.value) : []))}
								placeholder="All movements"
								isMulti isClearable closeMenuOnSelect={false}
								classNamePrefix="react-select"
								menuPortalTarget={document.body}
							/>
						</div>
					</div>
					<div className="ph-filter-date">
						<label style={lblStyle}>Date Range</label>
						<DateRangePicker
							fromDate={fromDate}
							toDate={toDate}
							onFromChange={(val) => dispatch(setFromDate(val))}
							onToChange={(val) => dispatch(setToDate(val))}
						/>
					</div>
					<div style={{flex:'1 1 0%'}}></div>
					<div className="ph-filter-actions" style={{display:'flex',alignItems:'center',gap:'6px',flexShrink:0}}>
						<button style={iconBtn} title="Email Report" onClick={openEmailModal} onMouseEnter={hoverIn} onMouseLeave={hoverOut}>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M4 7l8 6 8-6"/></svg>
						</button>
						<button style={iconBtn} title="Print" onClick={() => phOpenReport(props, 'print', { product_id: currentProduct?.value, customer_id: valueCsv(currentCustomer), supplier_id: valueCsv(currentSupplier), start_date: fromDate, end_date: toDate, movement_type: valueCsv(movementType) || 'all' })} onMouseEnter={hoverIn} onMouseLeave={hoverOut}>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V3.5h12V9M6 17.5H4.5A1.5 1.5 0 013 16v-4.5A1.5 1.5 0 014.5 10h15A1.5 1.5 0 0121 11.5V16a1.5 1.5 0 01-1.5 1.5H18M7 14.5h10v6H7z"/><path d="M16.5 12.5h.01"/></svg>
						</button>
						<button style={iconBtn} title="Download" onClick={() => phOpenReport(props, 'download', { product_id: currentProduct?.value, customer_id: valueCsv(currentCustomer), supplier_id: valueCsv(currentSupplier), start_date: fromDate, end_date: toDate, movement_type: valueCsv(movementType) || 'all' })} onMouseEnter={hoverIn} onMouseLeave={hoverOut}>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 20h14"/></svg>
						</button>
					</div>
				</div>
			</div>

			<EmailReportModal
				open={emailModalOpen}
				onClose={() => setEmailModalOpen(false)}
				emailApi={props.emailApi}
				product={currentProduct}
				customer={currentCustomer}
				supplier={currentSupplier}
				movementType={movementType}
				fromDate={fromDate}
				toDate={toDate}
			/>
        </div>
    );
}

function PHActionButton({ title, icon, onClick }) {
    return (
        <button type="button" onClick={onClick} title={title}
            style={{width:'42px',height:'42px',borderRadius:'10px',padding:0,background:'#fff',border:'1px solid #e8e8ec',color:'#6b7280',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',transition:'all 0.15s',fontSize:'14px'}}
            onMouseEnter={e => { e.currentTarget.style.borderColor='rgb(234, 88, 12)'; e.currentTarget.style.color='rgb(234, 88, 12)'; }}
            onMouseLeave={e => { e.currentTarget.style.borderColor='#e8e8ec'; e.currentTarget.style.color='#6b7280'; }}>
            <i className={`fa ${icon}`}></i>
        </button>
    );
}

function phOpenReport(props, kind, params) {
    if (!params.product_id) { toast.error('Please select a product first'); return; }
    // For 'download' kind, hit the Excel endpoint so user gets a real .xlsx file.
    const isExcel = kind === 'download';
    const base = isExcel
        ? (props.excelApi || '/excel/product_history')
        : (props.printApi || '/print/product_history');
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '') qs.set(k, v); });
    if (!isExcel) qs.set('type', kind);
    const url = `${base}?${qs.toString()}`;
    if (isExcel) {
        const a = document.createElement('a');
        a.href = url; a.download = '';
        document.body.appendChild(a); a.click(); a.remove();
    } else {
        window.open(url, '_blank');
    }
}

// ----------------- Movement meta (type → label, direction, badge colours) -----------------
const TYPE_META = {
    'supplier':         { label:'Purchase',    dir:'in',  badgeBg:'#dcfce7', badgeColor:'#15803d' },
    'supplier-returns': { label:'Sup. Return', dir:'out', badgeBg:'#fef9c3', badgeColor:'#a16207' },
    'sales':            { label:'Sale',        dir:'out', badgeBg:'#fee2e2', badgeColor:'#dc2626' },
    'customer-returns': { label:'Cus. Return', dir:'in',  badgeBg:'#dbeafe', badgeColor:'#1d4ed8' },
    'dumps':            { label:'Dump',        dir:'out', badgeBg:'#f3f4f6', badgeColor:'#4b5563' },
};

function normalizeRow(raw, type) {
    const m = TYPE_META[type];
    let ref, party, qty, price, product;
    if (type === 'supplier') {
        ref     = raw.invoice?.id ?? '-';
        party   = raw.supplier?.name ?? '-';
        product = raw.product?.name ?? '-';
        qty     = parseFloat(raw.quantity) || 0;
        price   = parseFloat(raw.unit_price) || 0;
    } else if (type === 'supplier-returns') {
        ref     = raw.invoice_id ?? '-';
        party   = raw.supplier?.name ?? '-';
        product = raw.product?.name ?? '-';
        qty     = parseFloat(raw.stock) || 0;
        price   = parseFloat(raw.price) || 0;
    } else if (type === 'sales') {
        ref     = raw.customer_invoice?.id ?? '-';
        party   = raw.customer?.name ?? '-';
        product = raw.product?.name ?? '-';
        qty     = parseFloat(raw.quantity) || 0;
        price   = parseFloat(raw.unit_price) || 0;
    } else if (type === 'customer-returns') {
        ref     = raw.customer_invoice?.id ?? '-';
        party   = raw.customer?.name ?? '-';
        product = raw.product?.name ?? '-';
        qty     = parseFloat(raw.stock) || 0;
        price   = parseFloat(raw.price) || 0;
    } else if (type === 'dumps') {
        ref     = raw.supplier_invoice?.id ?? '-';
        party   = raw.supplier?.name ?? '-';
        product = raw.product?.name ?? '-';
        qty     = parseFloat(raw.stock) || 0;
        price   = parseFloat(raw.price) || 0;
    }
    return {
        date:    raw.created_at,
        type,
        dir:     m.dir,
        ref,
        party,
        product,
        qty,
        price,
        total:   qty * price,
        balance: 0,
    };
}

// ----------------- Combined Grid -----------------
function CombinedGrid(props) {
    const { currentSupplier, currentCustomer, currentProduct, fromDate, toDate, movementType } = useSelector(state => state.products);
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
    const [sectionTotals, setSectionTotals] = useState({});

    useEffect(() => {
        const handle = () => setIsMobile(window.innerWidth <= 767);
        window.addEventListener('resize', handle);
        return () => window.removeEventListener('resize', handle);
    }, []);

    const productId  = currentProduct?.value ?? '';
    const supplierId = valueCsv(currentSupplier);
    const customerId = valueCsv(currentCustomer);
    const startDate  = fromDate || '2000-01-01';
    const endDate    = toDate || new Date().toISOString().slice(0,10);

    // Section grand totals depend only on the data filters — clear them when those change.
    useEffect(() => {
        setSectionTotals({});
    }, [productId, supplierId, customerId, startDate, endDate]);

    const handleSectionTotals = useCallback((type, t) => {
        setSectionTotals(prev => ({ ...prev, [type]: t }));
    }, []);

    const apiMap = {
        'supplier':         props.supplierInvoicesListApi,
        'supplier-returns': props.supplierReturnsListApi,
        'sales':            props.salesListApi,
        'customer-returns': props.customerReturnsApi,
        'dumps':            props.dumpsApi,
    };

    const sectionDefs = {
        'supplier':         { title: 'From Supplier',         subtitle: 'Purchase invoices where this product was bought',     showInvoice: true,  showAction: true, partyHeader: 'Supplier Name',
                              emptyIcon: 'fa-truck',         emptyLabel: 'No purchases yet',          emptyDesc: 'This product wasn’t purchased from any supplier in the selected period.' },
        'supplier-returns': { title: 'From Supplier Returns', subtitle: 'Stock of this product returned back to suppliers',                    showInvoice: true,  partyHeader: 'Supplier Name',
                              emptyIcon: 'fa-reply',         emptyLabel: 'No supplier returns',       emptyDesc: 'No stock of this product was returned to suppliers in the selected period.' },
        'sales':            { title: 'From Sales',            subtitle: 'Customer invoices where this product was sold',                      showInvoice: true,  partyHeader: 'Customer Name', invoiceHeader: 'Invoice No',
                              emptyIcon: 'fa-shopping-cart', emptyLabel: 'No sales yet',              emptyDesc: 'This product wasn’t sold to any customer in the selected period.' },
        'customer-returns': { title: 'From Customer Returns', subtitle: 'Stock of this product returned back by customers',                   showInvoice: true,  partyHeader: 'Customer Name',
                              emptyIcon: 'fa-undo',          emptyLabel: 'No customer returns',       emptyDesc: 'No customers returned this product in the selected period.' },
        'dumps':            { title: 'From Dumps',            subtitle: 'Damaged or expired stock written off',               showInvoice: false, partyHeader: null, isDumps: true,
                              emptyIcon: 'fa-trash',         emptyLabel: 'No dumps recorded',         emptyDesc: 'No stock of this product was dumped or written off in the selected period.' },
    };

    const selectedMovs = Array.isArray(movementType) ? movementType : (movementType ? [movementType] : []);
    const visible = (selectedMovs.length === 0)
        ? ['supplier','supplier-returns','sales','customer-returns','dumps']
        : selectedMovs;

    const showSummary = !!productId && selectedMovs.length === 0 && Object.keys(sectionTotals).length === 5;

    return (
        <>
        {/* Summary Cards — one per movement section + a derived Net P/L */}
        {showSummary && (() => {
            const g = (k) => sectionTotals[k] || { qty: 0, total: 0 };

            const purQty   = g('supplier').qty,           purVal    = g('supplier').total;
            const salesQty = g('sales').qty,              salesVal  = g('sales').total;
            const supRetQ  = g('supplier-returns').qty,   supRetV   = g('supplier-returns').total;
            const cusRetQ  = g('customer-returns').qty,   cusRetV   = g('customer-returns').total;
            const dumpQty  = g('dumps').qty,              dumpVal   = g('dumps').total;

            const totalReturns = supRetQ + cusRetQ;
            const totalStockIn  = purQty + cusRetQ;
            const totalStockOut = salesQty + supRetQ + dumpQty;

            // P/L = Sales revenue – Purchase cost – Dump cost (write-off); supplier returns recoup cost, customer returns refund revenue.
            const netProfit = (salesVal - cusRetV) - (purVal - supRetV) - dumpVal;
            const grossRevenue = salesVal - cusRetV;
            const margin = grossRevenue > 0 ? (netProfit / grossRevenue) * 100 : 0;
            const avgPur   = purQty   > 0 ? purVal   / purQty   : 0;
            const avgSales = salesQty > 0 ? salesVal / salesQty : 0;

            const fmtMoney = (n) => Number(n||0).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fmtN     = (n) => Number(n||0).toLocaleString('en-GB', { maximumFractionDigits: 2 });

            const cards = [
                { label:'Purchased',
                  value:fmtN(purQty),
                  sub: purQty > 0 ? '£'+fmtMoney(purVal)+' · Avg £'+fmtMoney(avgPur) : 'No purchases',
                  icon:'fa-truck', color:'#0ea5e9', bg:'#f0f9ff' },
                { label:'Sold',
                  value:fmtN(salesQty),
                  sub: salesQty > 0 ? '£'+fmtMoney(salesVal)+' · Avg £'+fmtMoney(avgSales) : 'No sales',
                  icon:'fa-shopping-cart', color:'#8b5cf6', bg:'#f5f3ff' },
                { label:'Returns',
                  value:fmtN(totalReturns),
                  sub: totalReturns > 0 ? fmtN(supRetQ)+' supplier · '+fmtN(cusRetQ)+' customer' : 'No returns',
                  icon:'fa-undo', color:'#f59e0b', bg:'#fffbeb' },
                { label:'Dumped',
                  value:fmtN(dumpQty),
                  sub: dumpQty > 0 ? '£'+fmtMoney(dumpVal)+' written off' : 'No dumps',
                  icon:'fa-trash', color:'#ef4444', bg:'#fef2f2' },
                { label: netProfit >= 0 ? 'Net Profit' : 'Net Loss',
                  value: (netProfit>=0?'+£':'-£')+fmtMoney(Math.abs(netProfit)),
                  sub: grossRevenue > 0 ? margin.toFixed(1)+'% margin' : 'No sales yet',
                  icon:'fa-line-chart',
                  color: netProfit>=0?'#15803d':'#dc2626',
                  bg:    netProfit>=0?'#f0fdf4':'#fef2f2' },
            ];

            const stockRatio = totalStockIn > 0 ? Math.min(100, Math.round(totalStockOut / totalStockIn * 100)) : 0;

            return isMobile ? (
                /* Mobile: movement summary card — matches reference exactly */
                <div style={{marginBottom:'12px',borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                    {/* Header */}
                    <div style={{padding:'13px 16px',display:'flex',alignItems:'center',justifyContent:'space-between',borderBottom:'1px solid #f1f5f9'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            <span style={{fontSize:'12px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Movement Summary</span>
                        </div>
                        <span style={{fontSize:'12px',color:'#94a3b8',fontWeight:'500'}}>{new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</span>
                    </div>
                    {/* PURCHASED | SOLD */}
                    <div style={{display:'flex',borderBottom:'1px solid #f1f5f9'}}>
                        <div style={{flex:1,padding:'14px 16px',borderRight:'1px solid #f1f5f9'}}>
                            <div style={{fontSize:'10px',color:'#94a3b8',fontWeight:'700',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'5px'}}>Purchased</div>
                            <div style={{fontSize:'26px',fontWeight:'800',color:'#2563eb',lineHeight:1,fontVariantNumeric:'tabular-nums'}}>{fmtN(purQty)}</div>
                            <div style={{fontSize:'11px',color:'#94a3b8',marginTop:'4px'}}>£{fmtMoney(purVal)} cost</div>
                        </div>
                        <div style={{flex:1,padding:'14px 16px'}}>
                            <div style={{fontSize:'10px',color:'#94a3b8',fontWeight:'700',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'5px'}}>Sold</div>
                            <div style={{fontSize:'26px',fontWeight:'800',color:'rgb(234, 88, 12)',lineHeight:1,fontVariantNumeric:'tabular-nums'}}>{fmtN(salesQty)}</div>
                            <div style={{fontSize:'11px',color:'#94a3b8',marginTop:'4px'}}>£{fmtMoney(salesVal)} revenue</div>
                        </div>
                    </div>
                    {/* Returns + Dumped chips */}
                    <div style={{display:'flex',gap:'8px',flexWrap:'wrap',padding:'12px 16px',borderBottom:'1px solid #f1f5f9'}}>
                        <span style={{display:'inline-flex',alignItems:'center',gap:'6px',padding:'4px 11px',background:'#fffbeb',border:'1px solid #fde68a',borderRadius:'999px',fontSize:'11.5px',color:'#92400e',fontWeight:'700'}}>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>{fmtN(totalReturns)} returns
                        </span>
                        <span style={{display:'inline-flex',alignItems:'center',gap:'6px',padding:'4px 11px',background:'#fef2f2',border:'1px solid #fecaca',borderRadius:'999px',fontSize:'11.5px',color:'#991b1b',fontWeight:'700'}}>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>{fmtN(dumpQty)} dumped
                        </span>
                    </div>
                    {/* Stock flow row */}
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'13px 16px'}}>
                        <span style={{fontSize:'11px',color:'#94a3b8',fontWeight:'700',letterSpacing:'0.6px',textTransform:'uppercase'}}>Stock Flow</span>
                        <span style={{fontSize:'13px',color:'#374151',fontWeight:'700',fontVariantNumeric:'tabular-nums'}}>IN {fmtN(totalStockIn)} &middot; OUT {fmtN(totalStockOut)}</span>
                    </div>
                    {/* Net Profit band */}
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'14px 16px',background: netProfit>=0?'#ecfdf3':'#fef2f2'}}>
                        <span style={{fontSize:'14px',color:'#0f1115',fontWeight:'800'}}>{netProfit>=0?'Net Profit':'Net Loss'}</span>
                        <span style={{fontSize:'19px',fontWeight:'800',color: netProfit>=0?'#15803d':'#dc2626',fontVariantNumeric:'tabular-nums'}}>{(netProfit>=0?'+£':'-£')+fmtMoney(Math.abs(netProfit))}</span>
                    </div>
                </div>
            ) : (
                /* Desktop: 5 cards mapping to the section tables below */
                <div className="ph-summary-grid" style={{display:'grid',gridTemplateColumns:'repeat(auto-fit, minmax(170px, 1fr))',gap:'10px',marginBottom:'16px'}}>
                    {cards.map((c,i) => (
                        <div key={i} style={{display:'flex',alignItems:'center',gap:'12px',padding:'14px 16px',borderRadius:'12px',background:'#fff',border:'1px solid #edf2f7',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',minWidth:0}}>
                            <div style={{width:'40px',height:'40px',borderRadius:'50%',background:c.bg,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                <i className={'fa '+c.icon} style={{fontSize:'15px',color:c.color}}></i>
                            </div>
                            <div style={{minWidth:0,flex:1}}>
                                <div style={{fontSize:'20px',fontWeight:'800',color:'#1a2332',lineHeight:1.1,fontVariantNumeric:'tabular-nums',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{c.value}</div>
                                <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',marginTop:'3px'}}>{c.label}</div>
                                {c.sub && <div style={{fontSize:'10.5px',fontWeight:'500',color:'#64748b',marginTop:'2px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}} title={c.sub}>{c.sub}</div>}
                            </div>
                        </div>
                    ))}
                </div>
            );
        })()}

        {!productId && (
            <div style={{borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',padding:'56px 24px',textAlign:'center',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                <div style={{width:'72px',height:'72px',borderRadius:'50%',background:'#fff7ed',display:'flex',alignItems:'center',justifyContent:'center',border:'1px solid #fed7aa',margin:'0 auto 18px'}}>
                    <i className="fa fa-cube" style={{fontSize:'26px',color:'rgb(234, 88, 12)'}}></i>
                </div>
                <div style={{fontSize:'17px',fontWeight:'800',color:'#0f1115',marginBottom:'8px'}}>Select a product to begin</div>
                <div style={{fontSize:'13px',color:'#6b7280',maxWidth:'420px',margin:'0 auto',lineHeight:1.6}}>
                    Pick a product from the dropdown above to see its full movement history — purchases, supplier returns, sales, customer returns and dumps — for the selected date range.
                </div>
            </div>
        )}

        {productId && visible.map(type => {
            const def = sectionDefs[type];
            return (
                <SectionTable
                    key={`${type}|${productId}|${supplierId}|${customerId}|${startDate}|${endDate}`}
                    type={type}
                    title={def.title}
                    subtitle={def.subtitle}
                    api={apiMap[type]}
                    productId={productId}
                    supplierId={supplierId}
                    customerId={customerId}
                    startDate={startDate}
                    endDate={endDate}
                    showInvoice={def.showInvoice}
                    showAction={def.showAction}
                    invoiceHeader={def.invoiceHeader || 'Invoice#'}
                    partyHeader={def.partyHeader}
                    isDumps={def.isDumps}
                    emptyIcon={def.emptyIcon}
                    emptyLabel={def.emptyLabel}
                    emptyDesc={def.emptyDesc}
                    currentProductLabel={currentProduct?.label}
                    onTotals={handleSectionTotals}
                />
            );
        })}
        </>
    );
}

export default function ProductHistory(props) {
    return (
        <div style={{maxWidth:'1440px', margin:'0 auto'}}>
            <style>{`
                .ph-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; }
                .ph-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; }
                .ph-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; border: none; }
                @keyframes drpFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
                @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
                .drp-preset-chip::-webkit-scrollbar{display:none;}
                .ph-filter-date .ph-drp-btn svg:first-of-type { stroke: rgb(234, 88, 12) !important; }
                @media (max-width: 768px) {
                    .ph-filter-panel { padding: 14px !important; gap: 12px !important; }
                    .ph-filter-row-primary,
                    .ph-filter-row-secondary { grid-template-columns: 1fr !important; }
                    .ph-filter-date > div > button { width: 100% !important; justify-content: flex-start !important; }
                    .ph-filter-actions { justify-content: flex-end !important; }
                    .ph-filter-actions { justify-content: flex-end !important; height: auto !important; }
                    .ph-tabs-card { border-radius: 12px !important; }
                }
            `}</style>
            <div style={{marginBottom:'16px'}}>
                <Filters {...props} />
            </div>
            <div style={{marginBottom:'20px'}}>
                <CombinedGrid {...props} />
            </div>
            <ToastContainer autoClose={3000} />
        </div>
    );
}

// ----------------- Sectioned report table -----------------
function SectionTable({ type, title, subtitle, api, productId, supplierId, customerId, startDate, endDate,
                        showInvoice, showAction, invoiceHeader, partyHeader, isDumps,
                        emptyIcon, emptyLabel, emptyDesc,
                        currentProductLabel, onTotals }) {
    const PER_PAGE = 10;
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [totalCount, setTotalCount] = useState(0);
    const [totals, setTotals] = useState({ qty: 0, price: 0, total: 0 });
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');

    // Debounce the search box; committing a new term also resets to page 1.
    useEffect(() => {
        const t = setTimeout(() => { setSearch(searchInput.trim()); setPage(1); }, 300);
        return () => clearTimeout(t);
    }, [searchInput]);

    useEffect(() => {
        if (!productId) {
            setRows([]); setTotalCount(0); setTotals({ qty: 0, price: 0, total: 0 });
            return;
        }
        let cancelled = false;
        setLoading(true);
        axios.post(api, {
            product_id: productId,
            supplier_id: supplierId,
            customer_id: customerId,
            start_date: startDate,
            end_date: endDate,
            search,
            page,
            per_page: PER_PAGE,
        }).then(res => {
            if (cancelled) return;
            const ok = res.data && res.data.success;
            const p = ok ? (res.data.payload || {}) : {};
            const list = Array.isArray(p.data) ? p.data : [];
            setRows(list.map(r => normalizeRow(r, type)));
            setTotalCount(Number(p.total_count) || 0);
            const t = p.totals || { qty: 0, price: 0, total: 0 };
            const safeT = { qty: Number(t.qty) || 0, price: Number(t.price) || 0, total: Number(t.total) || 0 };
            setTotals(safeT);
            if (onTotals) onTotals(type, { qty: safeT.qty, total: safeT.total });
        }).catch(() => {
            if (cancelled) return;
            setRows([]); setTotalCount(0); setTotals({ qty: 0, price: 0, total: 0 });
            if (onTotals) onTotals(type, { qty: 0, total: 0 });
        }).finally(() => {
            if (!cancelled) setLoading(false);
        });
        return () => { cancelled = true; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [api, productId, supplierId, customerId, startDate, endDate, page, search, type]);

    const totalPages = Math.max(1, Math.ceil(totalCount / PER_PAGE));
    const goPage = (p) => { if (p >= 1 && p <= totalPages && p !== page) setPage(p); };
    const firstRowNo = totalCount === 0 ? 0 : (page - 1) * PER_PAGE + 1;
    const lastRowNo = Math.min(page * PER_PAGE, totalCount);
    const pageList = (() => {
        const arr = [];
        let s = Math.max(1, page - 2);
        let e = Math.min(totalPages, s + 4);
        s = Math.max(1, e - 4);
        for (let i = s; i <= e; i++) arr.push(i);
        return arr;
    })();

    const hs = { fontSize:'10.5px', fontWeight:'700', color:'#64748b', textTransform:'uppercase', letterSpacing:'0.7px', whiteSpace:'nowrap' };
    const th = { padding:'12px 16px', borderBottom:'2px solid #eef2f7', background:'#fafbfc', textAlign:'left' };
    const td = { padding:'10px 16px', borderBottom:'1px solid #f3f4f8', fontSize:'13px', color:'#334155', fontWeight:'500' };
    const totalRowStyle = { background:'#fff7ed', fontWeight:'800', borderTop:'2px solid rgb(234, 88, 12)' };
    const fmt = v => { const n = Number(v) || 0; return n.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    const pgBtn = (disabled, active) => ({
        minWidth:'32px', height:'32px', padding:'0 8px',
        display:'inline-flex', alignItems:'center', justifyContent:'center',
        border:'1px solid ' + (active ? 'rgb(234, 88, 12)' : '#e5e7eb'),
        background: active ? 'rgb(234, 88, 12)' : '#fff',
        color: active ? '#fff' : (disabled ? '#cbd5e1' : '#475569'),
        borderRadius:'8px', fontSize:'12.5px', fontWeight:'600',
        cursor: disabled ? 'not-allowed' : 'pointer', outline:'none',
    });

    const colCount = (isDumps ? 4 : (showInvoice ? 8 : 7)) + (showAction ? 1 : 0);

    const accent = ({ 'supplier': { c:'#2563eb', bg:'#eff6ff' }, 'supplier-returns': { c:'#d97706', bg:'#fef3c7' }, 'sales': { c:'#16a34a', bg:'#dcfce7' }, 'customer-returns': { c:'#e11d48', bg:'#ffe4e6' }, 'dumps': { c:'#dc2626', bg:'#fee2e2' } })[type] || { c:'#2563eb', bg:'#eff6ff' };
    const phIsMobile = typeof window !== 'undefined' && window.innerWidth <= 767;

    const headerIconPaths = {
        'supplier':         <><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></>,
        'supplier-returns': <><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></>,
        'sales':            <><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></>,
        'customer-returns': <><circle cx="9" cy="7" r="3.2"/><path d="M3.5 20.5a5.5 5.5 0 0 1 11 0"/><path d="M18 12v6"/><polyline points="15.5 15.5 18 18 20.5 15.5"/></>,
        'dumps':            <><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></>,
    };

    return (
        <div style={{
            borderRadius:'16px', border:'1px solid #eaecf2',
            boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
            background:'#fff', marginBottom:'20px', overflow:'hidden',
        }}>
                <div style={{padding:'14px 20px', borderBottom:'1px solid #f0f4f8'}}>
                    <div style={{display:'flex', alignItems:'center', gap:'11px'}}>
                        <div style={{width:'36px', height:'36px', borderRadius:'10px', background:accent.bg, display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0}}>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke={accent.c} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{headerIconPaths[type] || headerIconPaths['supplier']}</svg>
                        </div>
                        <div style={{minWidth:0, flex:1}}>
                            <div style={{fontSize:'15px', fontWeight:'700', color:'#1e293b'}}>{title}</div>
                            {subtitle && <div style={{fontSize:'12px', fontWeight:'500', color:'#64748b', whiteSpace: phIsMobile ? 'normal' : 'nowrap', overflow: phIsMobile ? 'visible' : 'hidden', textOverflow:'ellipsis'}}>{subtitle}</div>}
                        </div>
                        {!phIsMobile && productId && (
                            <div style={{display:'flex', alignItems:'center', gap:'10px', flexShrink:0}}>
                                <div style={{position:'relative'}}>
                                    <i className="fa fa-search" style={{position:'absolute', left:'11px', top:'50%', transform:'translateY(-50%)', fontSize:'11px', color:'#94a3b8', pointerEvents:'none'}}></i>
                                    <input type="text" value={searchInput} onChange={e => setSearchInput(e.target.value)} placeholder="Search name / invoice…" style={{height:'36px', width:'220px', maxWidth:'100%', padding:'0 28px 0 30px', border:'1.5px solid #e2e8f0', borderRadius:'10px', fontSize:'12.5px', color:'#334155', outline:'none', background:'#f8fafc', boxSizing:'border-box'}} onFocus={e => { e.target.style.borderColor = 'rgb(234, 88, 12)'; e.target.style.background = '#fff'; }} onBlur={e => { e.target.style.borderColor = '#e2e8f0'; e.target.style.background = '#f8fafc'; }} />
                                    {searchInput && <i className="fa fa-times" onClick={() => setSearchInput('')} style={{position:'absolute', right:'10px', top:'50%', transform:'translateY(-50%)', fontSize:'12px', color:'#94a3b8', cursor:'pointer'}}></i>}
                                </div>
                                {currentProductLabel && <span style={{fontSize:'12px', fontWeight:'700', color:accent.c, background:accent.bg, borderRadius:'20px', padding:'4px 14px', whiteSpace:'nowrap'}}>{currentProductLabel}</span>}
                                <span style={{fontSize:'12px', color:'#64748b', fontWeight:'500', whiteSpace:'nowrap'}}>{rows.length} record{rows.length === 1 ? '' : 's'}</span>
                            </div>
                        )}
                    </div>
                    {phIsMobile && productId && (
                        <>
                        <div style={{position:'relative', marginTop:'12px'}}>
                            <i className="fa fa-search" style={{position:'absolute', left:'14px', top:'50%', transform:'translateY(-50%)', fontSize:'13px', color:'#94a3b8', pointerEvents:'none'}}></i>
                            <input type="text" value={searchInput} onChange={e => setSearchInput(e.target.value)} placeholder="Search name / invoice…" style={{height:'44px', width:'100%', padding:'0 34px 0 38px', border:'1.5px solid #e2e8f0', borderRadius:'12px', fontSize:'13.5px', color:'#334155', outline:'none', background:'#f8fafc', boxSizing:'border-box'}} onFocus={e => { e.target.style.borderColor = 'rgb(234, 88, 12)'; e.target.style.background = '#fff'; }} onBlur={e => { e.target.style.borderColor = '#e2e8f0'; e.target.style.background = '#f8fafc'; }} />
                            {searchInput && <i className="fa fa-times" onClick={() => setSearchInput('')} style={{position:'absolute', right:'12px', top:'50%', transform:'translateY(-50%)', fontSize:'13px', color:'#94a3b8', cursor:'pointer'}}></i>}
                        </div>
                        <div style={{display:'flex', alignItems:'center', gap:'10px', marginTop:'12px'}}>
                            {currentProductLabel && <span style={{fontSize:'12.5px', fontWeight:'700', color:accent.c, background:accent.bg, borderRadius:'20px', padding:'5px 14px', whiteSpace:'nowrap'}}>{currentProductLabel}</span>}
                            <span style={{fontSize:'12.5px', color:'#64748b', fontWeight:'500'}}>{rows.length} record{rows.length === 1 ? '' : 's'}</span>
                        </div>
                        </>
                    )}
                </div>

            <div style={{overflowX:'auto', position:'relative'}}>
                {loading && rows.length > 0 && (
                    <div style={{position:'absolute', top:0, left:0, right:0, bottom:0, background:'rgba(255,255,255,0.65)', display:'flex', alignItems:'flex-start', justifyContent:'center', paddingTop:'70px', zIndex:3}}>
                        <span style={{display:'inline-flex', alignItems:'center', gap:'10px', padding:'10px 18px', background:'#fff7ed', border:'1px solid #fed7aa', borderRadius:'9999px', color:'#c2410c', fontSize:'13px', fontWeight:'600', boxShadow:'0 4px 14px rgba(0,0,0,0.08)'}}>
                            <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                            <span>Loading…</span>
                        </span>
                    </div>
                )}
                <table style={{width:'100%', borderCollapse:'collapse'}}>
                    <thead>
                        <tr>
                            <th style={{...th, width:'70px'}}><span style={hs}>Sl.No</span></th>
                            {showInvoice && <th style={{...th, minWidth:'110px'}}><span style={hs}>{invoiceHeader}</span></th>}
                            <th style={{...th, minWidth:'110px'}}><span style={hs}>Date</span></th>
                            {partyHeader && <th style={{...th, minWidth:'180px'}}><span style={hs}>{partyHeader}</span></th>}
                            <th style={{...th}}><span style={hs}>Product</span></th>
                            <th style={{...th, textAlign:'right'}}><span style={hs}>{isDumps ? 'Total Qty' : 'Quantity'}</span></th>
                            {!isDumps && <th style={{...th, textAlign:'right'}}><span style={hs}>Unit Price</span></th>}
                            {!isDumps && <th style={{...th, textAlign:'right'}}><span style={hs}>Total</span></th>}
                            {showAction && <th style={{...th, textAlign:'center', width:'90px'}}><span style={hs}>Action</span></th>}
                        </tr>
                    </thead>
                    <tbody>
                        {loading && rows.length === 0 ? (
                            <tr>
                                <td colSpan={colCount} style={{padding:'40px 16px', textAlign:'center'}}>
                                    <span style={{display:'inline-flex', alignItems:'center', gap:'10px', padding:'10px 18px', background:'#fff7ed', border:'1px solid #fed7aa', borderRadius:'9999px', color:'#c2410c', fontSize:'13px', fontWeight:'600'}}>
                                        <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                                        <span>Loading…</span>
                                    </span>
                                </td>
                            </tr>
                        ) : rows.length === 0 ? (
                            <tr>
                                <td colSpan={colCount} style={{padding:'40px 16px', textAlign:'center'}}>
                                    <div style={{display:'flex', flexDirection:'column', alignItems:'center', gap:'10px'}}>
                                        <div style={{width:'52px', height:'52px', borderRadius:'50%', background:'#f8fafc', display:'flex', alignItems:'center', justifyContent:'center', border:'1px solid #eaecf2'}}>
                                            <i className={`fa ${emptyIcon || 'fa-inbox'}`} style={{fontSize:'18px', color:'#94a3b8'}}></i>
                                        </div>
                                        <div style={{fontSize:'14px', fontWeight:'700', color:'#475569'}}>{emptyLabel || 'No records found'}</div>
                                        {emptyDesc && <div style={{fontSize:'12.5px', color:'#94a3b8', maxWidth:'380px', lineHeight:1.5}}>{emptyDesc}</div>}
                                    </div>
                                </td>
                            </tr>
                        ) : (<>
                            {rows.map((r, idx) => (
                                <tr key={`${type}-${idx}`}>
                                    <td style={{...td, color:'#6b7280'}}>{firstRowNo + idx}</td>
                                    {showInvoice && (
                                        <td style={td}>
                                            <a href={type === 'sales' || type === 'customer-returns' ? `/data_entry/sales_entry/invoice/${r.ref}` : `/data_entry/purchase_entry/invoice/${r.ref}`} style={{textDecoration:'none', color:'rgb(234, 88, 12)', fontWeight:'700'}}>{r.ref}</a>
                                        </td>
                                    )}
                                    <td style={{...td, whiteSpace:'nowrap'}}>{r.date}</td>
                                    {partyHeader && <td style={td}>{r.party}</td>}
                                    <td style={td}>{r.product}</td>
                                    <td style={{...td, textAlign:'right', fontVariantNumeric:'tabular-nums'}}>{fmt(r.qty)}</td>
                                    {!isDumps && <td style={{...td, textAlign:'right', fontVariantNumeric:'tabular-nums'}}>£{fmt(r.price)}</td>}
                                    {!isDumps && <td style={{...td, textAlign:'right', fontWeight:'700', color:'#1e293b', fontVariantNumeric:'tabular-nums'}}>£{fmt((r.qty || 0) * (r.price || 0))}</td>}
                                    {showAction && (
                                        <td style={{...td, textAlign:'center'}}>
                                            <a href={`/data_entry/purchase_entry/invoice/${r.ref}`} title="Edit Invoice"
                                                style={{display:'inline-flex', alignItems:'center', justifyContent:'center', width:'30px', height:'30px', borderRadius:'8px', border:'1px solid #fed7aa', background:'#fff7ed', color:'rgb(234, 88, 12)', textDecoration:'none'}}>
                                                <i className="fa fa-pencil" style={{fontSize:'13px'}}></i>
                                            </a>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            <tr style={totalRowStyle}>
                                <td style={{...td, ...totalRowStyle, color:'rgb(234, 88, 12)'}} colSpan={1 + 1 + (showInvoice ? 1 : 0) + (partyHeader ? 1 : 0)}>
                                    <span style={{fontWeight:'800', fontSize:'12px', letterSpacing:'0.5px', color:'rgb(234, 88, 12)'}}>TOTAL</span>
                                </td>
                                <td style={{...td, ...totalRowStyle}}></td>
                                <td style={{...td, ...totalRowStyle, textAlign:'right', color:'#1e293b'}}>{fmt(totals.qty)}</td>
                                {!isDumps && <td style={{...td, ...totalRowStyle, textAlign:'right', color:'#1e293b'}}>£{fmt(totals.price)}</td>}
                                {!isDumps && <td style={{...td, ...totalRowStyle, textAlign:'right', color:'#1e293b'}}>£{fmt(totals.total)}</td>}
                                {showAction && <td style={{...td, ...totalRowStyle}}></td>}
                            </tr>
                        </>)}
                    </tbody>
                </table>
            </div>

            {totalCount > 0 && (
                <div style={{display:'flex', alignItems:'center', justifyContent:'space-between', gap:'12px', flexWrap:'wrap', padding:'12px 20px', borderTop:'1px solid #f0f4f8', background:'#fafbfc'}}>
                    <span style={{fontSize:'12px', color:'#64748b', fontWeight:'600'}}>
                        Showing {firstRowNo}–{lastRowNo} of {totalCount}
                    </span>
                    {totalPages > 1 && (
                        <div style={{display:'flex', alignItems:'center', gap:'4px'}}>
                            <button type="button" onClick={() => goPage(page - 1)} disabled={page <= 1} style={pgBtn(page <= 1)} title="Previous">‹</button>
                            {pageList[0] > 1 && (
                                <>
                                    <button type="button" onClick={() => goPage(1)} style={pgBtn(false)}>1</button>
                                    {pageList[0] > 2 && <span style={{padding:'0 2px', color:'#94a3b8'}}>…</span>}
                                </>
                            )}
                            {pageList.map(p => (
                                <button key={p} type="button" onClick={() => goPage(p)} style={pgBtn(false, p === page)}>{p}</button>
                            ))}
                            {pageList[pageList.length - 1] < totalPages && (
                                <>
                                    {pageList[pageList.length - 1] < totalPages - 1 && <span style={{padding:'0 2px', color:'#94a3b8'}}>…</span>}
                                    <button type="button" onClick={() => goPage(totalPages)} style={pgBtn(false)}>{totalPages}</button>
                                </>
                            )}
                            <button type="button" onClick={() => goPage(page + 1)} disabled={page >= totalPages} style={pgBtn(page >= totalPages)} title="Next">›</button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('product-history-app')) {
    const id = "product-history-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<ProductHistory {...props} />
		</Provider>
    );
}

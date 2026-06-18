import React, { useEffect, useState, useMemo, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import DataTable from 'react-data-table-component';
import axios from 'axios';
import logger from 'redux-logger';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import useOpenInNewTab from "./../hooks/useOpenInNewTab";
import useDataTableStyles from "../hooks/useDataTableStyles";
import useDropdownFix from "./../hooks/useDropdownFix";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";
import DateRangePicker from "./../hooks/DateRangePicker";
import { useWindowSize } from "./../hooks/useWindowSize";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

// ----------------- Date defaults -----------------
const today = new Date();
const formatDate = (date) => {
	const y = date.getFullYear();
	const m = String(date.getMonth() + 1).padStart(2, '0');
	const d = String(date.getDate()).padStart(2, '0');
	return `${y}-${m}-${d}`;
};
const todayStr = formatDate(today);

const slice = createSlice({
    name: 'suppliers',
    initialState: {
		suppliers: [], selectedInvoices: [], currentSupplier: "", currentSupplierInfo: "",
		loading: false, toDate: todayStr, fromDate: todayStr, option: [],
	},
    reducers: {
        setSuppliers: (s, a) => { s.suppliers = a.payload },
		setToDate: (s, a) => { s.toDate = a.payload }, setFromDate: (s, a) => { s.fromDate = a.payload },
		setSelectedInvoices: (s, a) => { s.selectedInvoices = a.payload },
		setOption: (s, a) => { s.option = a.payload },
        setCurrentSupplier: (s, a) => { s.currentSupplier = a.payload },
		setCurrentSupplierInfo: (s, a) => { s.currentSupplierInfo = a.payload },
    },
});

const { setSuppliers, setSelectedInvoices, setToDate, setFromDate, setOption, setCurrentSupplier, setCurrentSupplierInfo } = slice.actions;

const store = configureStore({
    reducer: { suppliers: slice.reducer },
	middleware: (gDM) => gDM().concat(logger),
	devTools: process.env.NODE_ENV !== 'production',
});

// ---- Email Statement Modal ----
function EmailStatementModal({ open, onClose, emailApi, supplier, fromDate, toDate }) {
	const periodLabel = () => {
		const fmt = (d) => {
			if (!d) return '';
			const dt = new Date(d);
			return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
		};
		if (!fromDate && !toDate) return 'All time';
		return `${fmt(fromDate) || '—'} – ${fmt(toDate) || 'Today'}`;
	};

	const defaultSubject = () => `Account Statement — ${supplier ? supplier.name : ''} — ${periodLabel()}`;
	const defaultMessage = () =>
		`Dear ${supplier ? supplier.name : 'Supplier'},\n\nPlease find attached the account statement for the period ${periodLabel()}.\n\nKindly review and let us know if you have any questions.\n\nThank you for your business.`;

	const [toEmail, setToEmail] = useState('');
	const [subject, setSubject] = useState('');
	const [message, setMessage] = useState('');
	const [sending, setSending] = useState(false);
	const [errors, setErrors] = useState({});

	useEffect(() => {
		if (open) {
			setToEmail(supplier && supplier.email ? supplier.email : '');
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
			currentSupplier: supplier.id,
			fromDate: fromDate || '',
			toDate: toDate || '',
			to_email: toEmail.trim(),
			subject: subject.trim(),
			message: message.trim(),
		})
		.then(res => {
			if (res.data && res.data.success === true) {
				toast.success((res.data.payload && res.data.payload.message) || 'Statement emailed successfully');
				onClose();
			} else {
				const msg = res.data && typeof res.data.payload === 'string' ? res.data.payload : 'Could not send the statement email.';
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
				{/* Header */}
				<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 22px', borderBottom: '1px solid #eeeeef' }}>
					<span style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fff7ed', border: '1px solid #fed7aa', color: '#c2410c', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
					</span>
					<div style={{ flex: 1, minWidth: 0 }}>
						<h3 style={{ margin: 0, fontSize: '15.5px', fontWeight: '800', color: '#0f1115' }}>Email Statement</h3>
						<p style={{ margin: '2px 0 0', fontSize: '12px', color: '#6b7280' }}>Send the supplier statement as a PDF</p>
					</div>
					<button onClick={onClose} style={{ width: '30px', height: '30px', borderRadius: '8px', border: '1px solid #e8e8ec', background: '#fff', color: '#6b7280', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
				</div>

				{/* Body */}
				<div style={{ padding: '20px 22px', maxHeight: '60vh', overflowY: 'auto' }}>
					{/* Period info */}
					<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#fafafb', border: '1px solid #e8e8ec', borderRadius: '9px', padding: '10px 13px', marginBottom: '16px' }}>
						<div>
							<div style={{ fontSize: '10px', fontWeight: '700', color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.4px' }}>Statement Period</div>
							<div style={{ fontSize: '13px', fontWeight: '700', color: '#0f1115', marginTop: '2px' }}>{periodLabel()}</div>
						</div>
					</div>

					{/* To */}
					<div style={{ marginBottom: '14px' }}>
						<label style={label}>To</label>
						<input type="email" value={toEmail} onChange={e => setToEmail(e.target.value)}
							placeholder="supplier@email.com"
							style={{ ...inputBase, borderColor: toEmailValid ? '#e8e8ec' : '#dc2626' }} />
						{errText(toEmailError)}
					</div>

					{/* Subject */}
					<div style={{ marginBottom: '14px' }}>
						<label style={label}>Subject</label>
						<input type="text" value={subject} onChange={e => setSubject(e.target.value)}
							style={{ ...inputBase, borderColor: errors.subject ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.subject)}
					</div>

					{/* Message */}
					<div>
						<label style={label}>Message</label>
						<textarea value={message} onChange={e => setMessage(e.target.value)} rows={6}
							style={{ ...inputBase, height: 'auto', padding: '10px 12px', resize: 'vertical', lineHeight: '1.5', borderColor: errors.message ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.message)}
					</div>
				</div>

				{/* Footer */}
				<div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', padding: '14px 22px', borderTop: '1px solid #eeeeef', background: '#fafafb' }}>
					<button onClick={onClose} disabled={sending}
						style={{ height: '40px', padding: '0 18px', borderRadius: '9px', border: '1.5px solid #e8e8ec', background: '#fff', color: '#6b7280', fontWeight: '700', fontSize: '13px', cursor: sending ? 'not-allowed' : 'pointer' }}>
						Cancel
					</button>
					<button onClick={handleSend} disabled={sending || !toEmailValid}
						style={{ height: '40px', padding: '0 20px', borderRadius: '9px', border: 'none', background: (sending || !toEmailValid) ? '#fdba74' : 'rgb(234, 88, 12)', color: '#fff', fontWeight: '700', fontSize: '13px', cursor: (sending || !toEmailValid) ? 'not-allowed' : 'pointer', display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
						{sending
							? <><svg width="14" height="14" viewBox="0 0 24 24" style={{ animation: 'sh-spin 0.7s linear infinite' }}><circle cx="12" cy="12" r="9" fill="none" stroke="rgba(255,255,255,0.4)" strokeWidth="3"/><path d="M12 3a9 9 0 0 1 9 9" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round"/></svg> Sending…</>
							: <><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Statement</>}
					</button>
				</div>
				<style>{`@keyframes sh-spin{to{transform:rotate(360deg)}}`}</style>
			</div>
		</div>
	);
}

// --- Date Range Picker ---
function FilterBar({ apiUrl, printApi, excelApi, emailApi, initialSupplierId }) {
	const dispatch = useDispatch();
	const { currentSupplier, currentSupplierInfo, selectedInvoices, toDate, fromDate, option, suppliers } = useSelector(s => s.suppliers);
	const openInNewTab = useOpenInNewTab();
	const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
	const [isTablet, setIsTablet] = useState(false); // iPad/tablets (768-1024) now render the desktop UI
	useEffect(() => {
		const handle = () => { setIsMobile(window.innerWidth <= 767); setIsTablet(false); };
		window.addEventListener('resize', handle);
		return () => window.removeEventListener('resize', handle);
	}, []);

	const filterOptions = [
		{label:"Cash",value:"cash"},{label:"Credit",value:"credit"},{label:"Cheque",value:"cheque"},{label:"Bank Transfer",value:"bank transfer"},
	];

	useEffect(() => {
		(async () => {
			try {
				const res = await axios.get(apiUrl);
				if (res.data.success) {
					dispatch(setSuppliers(res.data.payload));
					if (initialSupplierId) {
						const s = res.data.payload.find(c => c.id == initialSupplierId);
						if (s) { dispatch(setCurrentSupplier(s.id)); dispatch(setCurrentSupplierInfo(s)); }
					}
				}
			} catch (err) { console.error(err); }
		})();
	}, [apiUrl, initialSupplierId]);

	const printInvoice = (type) => {
		if (!currentSupplier) { toast.error('Please select a supplier first.'); return; }
		openInNewTab(printApi, { supplier_id: currentSupplier, start_date: fromDate, end_date: toDate, invoices: selectedInvoices, type });
	};

	const downloadExcel = () => {
		if (!currentSupplier) { toast.error('Please select a supplier first.'); return; }
		const qs = new URLSearchParams();
		qs.set('supplier_id', currentSupplier);
		if (fromDate) qs.set('start_date', fromDate);
		if (toDate) qs.set('end_date', toDate);
		if (selectedInvoices) qs.set('invoices', selectedInvoices);
		qs.set('type', 'with-balance');
		const url = (excelApi || '/excel/supplier_history') + '?' + qs.toString();
		const a = document.createElement('a');
		a.href = url;
		a.download = '';
		document.body.appendChild(a);
		a.click();
		a.remove();
	};

	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const [ptOpen, setPtOpen] = useState(false);
	const ptRef = useRef(null);
	useEffect(() => {
		if (!ptOpen) return;
		const handle = (e) => { if (ptRef.current && !ptRef.current.contains(e.target)) setPtOpen(false); };
		document.addEventListener('mousedown', handle);
		return () => document.removeEventListener('mousedown', handle);
	}, [ptOpen]);
	const ptLabel = (option && option.length > 0) ? option.map(o => o.label).join(', ') : 'All';
	const filterCardStyle = { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', width: '100%', height: '54px', padding: '8px 12px', borderRadius: '12px', background: '#fff', border: '1px solid #eaecf2', boxShadow: '0 1px 2px rgba(16,24,40,0.04)', cursor: 'pointer', outline: 'none', boxSizing: 'border-box', transition: 'border-color 0.15s', textAlign: 'left' };
	const cardLabelStyle = { fontSize: '10px', fontWeight: '700', color: '#94a3b8', letterSpacing: '0.7px', textTransform: 'uppercase', marginBottom: '3px', lineHeight: 1 };
	const cardValueStyle = { fontSize: '15px', fontWeight: '700', color: '#0f1115', lineHeight: 1.1 };
	const openEmailModal = () => {
		if (!currentSupplier) { toast.error('Please select a supplier first.'); return; }
		setEmailModalOpen(true);
	};

	const iconBtn = {
		width: '42px', height: '42px', borderRadius: '10px', padding: 0,
		background: '#fff', border: '1px solid #e8e8ec', color: '#6b7280',
		display: 'flex', alignItems: 'center', justifyContent: 'center',
		cursor: 'pointer', outline: 'none', boxShadow: '0 1px 2px rgba(15,17,21,0.04)',
		transition: 'all 0.15s',
	};

	const selectCtrl = {
		...orangeSelectStyles,
		control: (base, state) => ({
			...orangeSelectStyles.control(base, state),
			minHeight: '38px', borderRadius: '9px',
			border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e5e7eb',
			boxShadow: 'none', fontSize: '13px',
			'&:hover': { borderColor: 'rgb(234, 88, 12)' },
		}),
		valueContainer: (base) => ({ ...base, padding: '2px 10px', gap: '4px' }),
		indicatorSeparator: () => ({ display: 'none' }),
		menuPortal: (base) => ({ ...base, zIndex: 9999 }),
		multiValue: (base) => ({ ...base, background: '#FFF5ED', border: '1px solid #fed7aa', borderRadius: '6px' }),
		multiValueLabel: (base) => ({ ...base, color: 'rgb(234, 88, 12)', fontWeight: '600', fontSize: '12.5px' }),
		multiValueRemove: (base) => ({ ...base, color: 'rgb(234, 88, 12)', borderRadius: '4px', ':hover': { background: '#fed7aa', color: 'rgb(234, 88, 12)' } }),
	};

	const supplierOptions = suppliers.map(c => ({ value: c.id, label: c.name }));
	const selectedSupplierOption = supplierOptions.find(o => o.value === currentSupplier) || null;
	const handleSupplierChange = (sel) => {
		dispatch(setCurrentSupplier(sel ? sel.value : null));
		dispatch(setCurrentSupplierInfo(sel ? suppliers.find(c => c.id === sel.value) : null));
	};

	const actionIcons = (
		<div style={{ display: 'flex', gap: '6px', flexShrink: 0 }}>
			<button style={iconBtn} title="Email Statement" onClick={openEmailModal}
				onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgb(234, 88, 12)'; e.currentTarget.style.color = 'rgb(234, 88, 12)'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
			</button>
			<button style={iconBtn} title="Print" onClick={() => printInvoice('with-balance')}
				onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgb(234, 88, 12)'; e.currentTarget.style.color = 'rgb(234, 88, 12)'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
			</button>
			<button style={iconBtn} title="Download" onClick={downloadExcel}
				onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgb(234, 88, 12)'; e.currentTarget.style.color = 'rgb(234, 88, 12)'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
			</button>
		</div>
	);

	if (isMobile) {
		return (
			<div className="sh-mob-filters" style={{ background: 'transparent', padding: '0', marginBottom: '14px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
				<style>{`.sh-mob-filters .ph-drp-btn{width:100%!important;}`}</style>
				{/* Row 1: Supplier select + action icons */}
				<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
					<div style={{ flex: 1, minWidth: 0, position: 'relative' }}>
						<span style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#F27420', zIndex: 1, pointerEvents: 'none', display: 'flex', alignItems: 'center' }}>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
						</span>
						<Select
							styles={{ ...selectCtrl, control: (base, state) => ({ ...selectCtrl.control(base, state), minHeight: '40px' }), valueContainer: (base) => ({ ...base, padding: '2px 10px 2px 34px', gap: '4px' }) }}
							options={supplierOptions}
							value={selectedSupplierOption}
							onChange={handleSupplierChange}
							isSearchable isClearable
							placeholder="Select Supplier"
							menuPortalTarget={document.body}
						/>
					</div>
					{actionIcons}
				</div>
				{/* Row 2: Payment Type + Date Range — labeled filter cards */}
				<div style={{ display: 'flex', gap: '10px' }}>
					<div style={{ flex: 1, minWidth: 0, position: 'relative' }} ref={ptRef}>
						<button type="button" onClick={() => setPtOpen(o => !o)} style={{ ...filterCardStyle, borderColor: ptOpen ? 'rgb(234, 88, 12)' : '#eaecf2' }}>
							<span style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', minWidth: 0, flex: 1 }}>
								<span style={cardLabelStyle}>Payment Type</span>
								<span style={{ ...cardValueStyle, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: '100%' }}>{ptLabel}</span>
							</span>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }}><path d="M6 9l6 6 6-6"/></svg>
						</button>
						{ptOpen && (
							<div style={{ position: 'absolute', top: 'calc(100% + 6px)', left: 0, right: 0, background: '#fff', border: '1px solid #eaecf2', borderRadius: '12px', boxShadow: '0 8px 28px rgba(15,23,42,0.16)', zIndex: 9999, padding: '6px', maxHeight: '260px', overflowY: 'auto' }}>
								{filterOptions.map(opt => {
									const checked = (option || []).some(o => o.value === opt.value);
									return (
										<div key={opt.value} onClick={() => { const cur = option || []; dispatch(setOption(checked ? cur.filter(o => o.value !== opt.value) : [...cur, opt])); }} style={{ display: 'flex', alignItems: 'center', gap: '10px', padding: '9px 10px', borderRadius: '8px', cursor: 'pointer', fontSize: '13px', fontWeight: '600', color: checked ? '#0f1115' : '#374151' }}>
											<span style={{ width: '20px', height: '20px', borderRadius: '6px', border: checked ? '2px solid rgb(234, 88, 12)' : '2px solid #d1d5db', background: checked ? 'rgb(234, 88, 12)' : '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
												{checked && <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>}
											</span>
											{opt.label}
										</div>
									);
								})}
							</div>
						)}
					</div>
					<div style={{ flex: 1, minWidth: 0 }}>
						<DateRangePicker
							variant="labeled"
							fromDate={fromDate}
							toDate={toDate}
							onFromChange={val => dispatch(setFromDate(val))}
							onToChange={val => dispatch(setToDate(val))}
						/>
					</div>
				</div>

				<EmailStatementModal
					open={emailModalOpen}
					onClose={() => setEmailModalOpen(false)}
					emailApi={emailApi}
					supplier={currentSupplierInfo}
					fromDate={fromDate}
					toDate={toDate}
				/>
			</div>
		);
	}

	return (
		<div style={{background:'#fff',borderRadius:'0',border:'1px solid #eaecf2',borderTop:'none',borderBottom:'none',boxShadow:'none',padding:'14px 20px',marginBottom:'0'}}>
			<div style={{display:'flex',alignItems:'center',gap:'12px',flexWrap:isTablet?'nowrap':'wrap'}}>
				<div style={{flex:1,minWidth:0}}>
					<Select styles={selectCtrl} options={supplierOptions} value={selectedSupplierOption} onChange={handleSupplierChange} isSearchable isClearable placeholder="Select Supplier" menuPortalTarget={document.body} />
				</div>
				<div style={{flex:'1 1 0%',minWidth:'160px'}}>
					<Select styles={selectCtrl} options={filterOptions} isMulti isClearable closeMenuOnSelect={false} placeholder="All" onChange={e=>dispatch(setOption(e||[]))} value={option} isSearchable={false} menuPortalTarget={document.body} />
				</div>
				<div style={{flex:'0 0 auto',minWidth:0}}>
					<DateRangePicker fromDate={fromDate} toDate={toDate} onFromChange={v=>dispatch(setFromDate(v))} onToChange={v=>dispatch(setToDate(v))} />
				</div>
				{actionIcons}
			</div>

			<EmailStatementModal
				open={emailModalOpen}
				onClose={() => setEmailModalOpen(false)}
				emailApi={emailApi}
				supplier={currentSupplierInfo}
				fromDate={fromDate}
				toDate={toDate}
			/>
		</div>
	);
}

// --- List ---
function List(props) {
    const dispatch = useDispatch();
    const { currentSupplier, currentSupplierInfo, toDate, fromDate, option } = useSelector(s => s.suppliers);
    const [data, setData] = useState([]);
	const [pastBalance, setPastBalance] = useState(0);
    const [filterText, setFilterText] = useState("");
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
    const customStyles = useDataTableStyles();
    const [historyData, setHistoryData] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
	const [statusTab, setStatusTab] = useState('all');
	const [expandedInv, setExpandedInv] = useState(null);
	const [viewMode, setViewMode] = useState('card');
	const [summaryOpen, setSummaryOpen] = useState(false);

    useEffect(() => {
        const handle = () => setIsMobile(window.innerWidth <= 767);
        window.addEventListener('resize', handle);
        return () => window.removeEventListener('resize', handle);
    }, []);

    useEffect(() => {
        let cancelled = false;
        const fetchHistory = async () => {
            setIsLoading(true);
            try {
                const res = await axios.post(props.historyListApi, { currentSupplier, toDate: toDate||'', fromDate: fromDate||'', option: (Array.isArray(option) && option.length > 0) ? option.map(o => o.value).join(',') : 'all' });
                if (cancelled) return;
                if (res.data.success) { setHistoryData(res.data.payload.invoices); setData(res.data.payload.invoices); setPastBalance(res.data.payload.past_balance); }
            } catch (err) { if (!cancelled) console.error(err); }
            finally { if (!cancelled) setIsLoading(false); }
        };
        if (currentSupplier) fetchHistory();
        return () => { cancelled = true; };
    }, [currentSupplier, toDate, fromDate, option]);

    const filteredData = useMemo(() => {
		if (!filterText) return historyData;
		const q = filterText.toLowerCase();
		return historyData.filter(i => ['invoice_date','created_at','id'].some(f => String(i[f]||'').toLowerCase().includes(q)));
	}, [historyData, filterText]);

	const toNum = v => Number(v) || 0;
	const fmt = v => '£' + Number(v||0).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2});

    // Chronological order with a cumulative running balance starting from past balance.
    const statementRows = useMemo(() => {
		const rows = [...filteredData].sort((a, b) => new Date(a.invoice_date || a.created_at) - new Date(b.invoice_date || b.created_at));
		let running = toNum(pastBalance);
		return rows.map((r, idx) => {
			running += toNum(r.balance);
			return { ...r, _sl: idx + 1, running_balance: running };
		});
	}, [filteredData, pastBalance]);

	const summary = useMemo(() => {
		if (!statementRows.length) return null;
		return {
			isSummary: true,
			net_amount: statementRows.reduce((a,b) => a + toNum(b.net_amount), 0),
			total_paid: statementRows.reduce((a,b) => a + toNum(b.total_paid), 0),
			credit_adj: statementRows.reduce((a,b) => a + toNum(b.credit_adj), 0),
			total_discounted: statementRows.reduce((a,b) => a + toNum(b.total_discounted), 0),
			balance: statementRows.reduce((a,b) => a + toNum(b.balance), 0),
		};
	}, [statementRows]);

	const finalData = currentSupplier ? [...statementRows, ...(summary ? [summary] : [])] : [];

	const hs = {fontSize:'11px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const hsR = hs;
	const rcell = (val, opts={}) => {
		const n = toNum(val);
		if (n === 0) return <div style={{width:'100%',textAlign:'left'}}><span style={{color:'#d1d5db',fontSize:'12px'}}>—</span></div>;
		return <div style={{width:'100%',textAlign:'left'}}><span style={{fontWeight:opts.bold?'800':'600',fontSize:'13px',color:opts.color||'#374151',fontVariantNumeric:'tabular-nums'}}>{fmt(n)}</span></div>;
	};

    const columns = [
        { name: <span style={hs}>Sl.No</span>, width:'66px',
		  cell: r => (r.isSummary) ? <span/> : <span style={{fontSize:'13px',color:'#94a3b8',fontWeight:'600'}}>{r._sl}</span> },
        { name: <span style={hs}>Date</span>, grow: 1,
		  cell: r => r.isSummary ? <span style={{fontSize:'12px',fontWeight:'800',color:'#1e293b',letterSpacing:'0.4px'}}>TOTAL</span>
			: <span style={{fontSize:'13px',color:'#64748b',fontWeight:'500',whiteSpace:'nowrap'}}>{r.invoice_date || r.created_at}</span> },
        { name: <span style={hs}>Delivery # | Remarks</span>, grow: 1.4,
		  cell: r => (r.isSummary) ? <span/> : <span style={{fontSize:'12.5px',color:'#64748b',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}} title={r.delivery_no || r.remarks || ''}>{r.delivery_no || r.remarks || <span style={{color:'#d1d5db'}}>—</span>}</span> },
        { name: <span style={hs}>Invoice #</span>, width:'130px',
		  cell: r => {
			if (r.isSummary) return <span/>;
			if (toNum(r.is_credited) === 1) return <span style={{background:'#f0fdf4',color:'#16a34a',border:'1px solid #86efac',borderRadius:'6px',padding:'3px 8px',fontSize:'10.5px',fontWeight:'700',whiteSpace:'nowrap'}}>On Acc Payment</span>;
			if (toNum(r.id) > 0) return <a href={`/data_entry/purchase_entry/invoice/${r.id}`} style={{textDecoration:'none'}}><span style={{background:'#FFF7ED',border:'1px solid #fed7aa',borderRadius:'6px',padding:'3px 10px',fontWeight:'700',fontSize:'13px',color:'rgb(234, 88, 12)'}}>#{r.id}</span></a>;
			return <span style={{fontSize:'13px',color:'#94a3b8'}}>0</span>;
		  } },
		{ name: <span style={hsR}>Credit</span>, width:'120px',
		  cell: r => rcell(r.net_amount, {bold:r.isSummary}) },
        { name: <span style={hsR}>Paid</span>, width:'135px',
		  cell: r => {
			const paid = toNum(r.total_paid);
			if (r.isSummary) return <div style={{width:'100%',textAlign:'left'}}><span style={{fontWeight:'800',fontSize:'13px',color:'#16a34a',fontVariantNumeric:'tabular-nums'}}>{fmt(paid)}</span></div>;
			const methods = [];
			if (toNum(r.paid_by_cash) > 0) methods.push('Cash');
			if (toNum(r.paid_by_card) > 0) methods.push('Card');
			if (toNum(r.paid_by_cheque) > 0) methods.push('Cheque');
			if (toNum(r.paid_by_bank) > 0) methods.push('Bank Transfer');
			return (
				<div style={{width:'100%',textAlign:'left'}}>
					<span style={{fontWeight:'600',fontSize:'13px',color: paid>0 ? '#16a34a' : '#94a3b8',fontVariantNumeric:'tabular-nums'}}>{fmt(paid)}</span>
					{methods.length > 0 && <div style={{fontSize:'10px',color:'#94a3b8',fontWeight:'600',marginTop:'1px'}}>{methods.join(', ')}</div>}
				</div>
			);
		  } },
        { name: <span style={hsR}>Returns/Adj</span>, width:'120px',
		  cell: r => rcell(r.credit_adj, {bold:r.isSummary, color:'#7c3aed'}) },
        { name: <span style={hsR}>Discount/Adj</span>, width:'120px',
		  cell: r => rcell(r.total_discounted, {bold:r.isSummary}) },
        { name: <span style={hsR}>Balance</span>, width:'120px',
		  cell: r => rcell(r.balance, {bold:r.isSummary, color:'#ef4444'}) },
        { name: <span style={hsR}>Running Balance</span>, width:'150px',
		  cell: r => <div style={{width:'100%',textAlign:'left'}}><span style={{fontWeight:'800',fontSize:'13px',color: toNum(r.running_balance) >= 0 ? '#1e293b' : '#ef4444',fontVariantNumeric:'tabular-nums'}}>{fmt(r.running_balance)}</span></div> },
    ];

	const conditionalRowStyles = [
		{ when: r => r.isSummary, style: { background:'#fafbfc', borderTop:'2px solid #eef2f7' } },
	];

	const mergedStyles = useMemo(() => ({
        ...customStyles,
        headRow: { style: { backgroundColor: '#fafbfc', borderBottom: '2px solid #eef2f7', minHeight: '44px' } },
        headCells: { style: { fontSize: '10.5px', fontWeight: '700', color: '#64748b', letterSpacing: '0.6px', textTransform: 'uppercase', padding: '12px 16px', whiteSpace: 'nowrap' } },
        rows: { style: { borderBottom: '1px solid #f3f4f8', fontSize: '13px', minHeight: '50px', transition: 'all 0.12s' }, highlightOnHoverStyle: { backgroundColor: '#FEFAF6', borderBottomColor: '#fed7aa' } },
        cells: { style: { fontSize: '13px', padding: '12px 16px', display: 'flex', alignItems: 'center', whiteSpace: 'nowrap' } },
        pagination: { style: { borderTop: '1px solid #eef2f7', fontSize: '13px' } },
    }), [customStyles]);

	useDropdownFix();

	const totalAmount = summary ? toNum(summary.net_amount) : 0;
	const totalPaid = summary ? toNum(summary.total_paid) : 0;
	const totalReturns = summary ? toNum(summary.credit_adj) : 0;
	const totalPending = summary ? toNum(summary.balance) : 0;
	const paidPercent = totalAmount > 0 ? Math.round((totalPaid / totalAmount) * 100) : 0;

	// ── Mobile invoice-card list helpers (ported from Customer History) ──
	const chUnpaid = statementRows.filter(r => toNum(r.balance) > 0);
	const chPaid = statementRows.filter(r => toNum(r.balance) <= 0);
	const chCounts = { all: statementRows.length, unpaid: chUnpaid.length, paid: chPaid.length };
	const chCards = statusTab === 'unpaid' ? chUnpaid : statusTab === 'paid' ? chPaid : statementRows;
	const chTableData = statusTab === 'all' ? finalData : chCards;
	const statusBadge = (row) => {
		if (row.isSummary) return null;
		const balance = toNum(row.balance), paid = toNum(row.total_paid), amount = toNum(row.net_amount), credit = toNum(row.credit_adj);
		if (amount <= 0 && balance <= 0) return <span style={{ fontSize: '11px', fontWeight: '700', color: '#d1d5db' }}>—</span>;
		if (balance <= 0 && paid <= 0 && credit > 0) return <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11px', fontWeight: '700', color: '#7c3aed', background: '#f5f3ff', border: '1px solid #c4b5fd', padding: '2px 8px', borderRadius: '20px', whiteSpace: 'nowrap' }}><i className="fa fa-undo" style={{ fontSize: '9px' }}></i>Returned</span>;
		if (balance <= 0) return <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11px', fontWeight: '700', color: '#16a34a', background: '#f0fdf4', border: '1px solid #86efac', padding: '2px 8px', borderRadius: '20px' }}><i className="fa fa-check-circle" style={{ fontSize: '9px' }}></i>Paid</span>;
		if (paid > 0 || credit > 0) return <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11px', fontWeight: '700', color: '#f59e0b', background: '#fffbeb', border: '1px solid #fde68a', padding: '2px 8px', borderRadius: '20px' }}><i className="fa fa-clock-o" style={{ fontSize: '9px' }}></i>Partial</span>;
		return <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', fontSize: '11px', fontWeight: '700', color: '#ef4444', background: '#fef2f2', border: '1px solid #fecaca', padding: '2px 8px', borderRadius: '20px' }}><i className="fa fa-times-circle" style={{ fontSize: '9px' }}></i>Unpaid</span>;
	};
	const clearFilters = () => {
		setFilterText(''); setStatusTab('all');
		dispatch(setFromDate('')); dispatch(setToDate('')); dispatch(setOption([]));
	};
	const chEmptyCard = (
		<div style={{ background: '#fff', border: '1px solid #eaecf2', borderRadius: '14px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', padding: '40px 24px', textAlign: 'center' }}>
			<div style={{ width: '60px', height: '60px', margin: '0 auto 14px', borderRadius: '14px', background: '#f8fafc', border: '1px solid #eaecf2', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#9ca3af' }}>
				<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
			</div>
			<div style={{ fontSize: '15px', fontWeight: '800', color: '#0f1115' }}>No records found</div>
			<div style={{ fontSize: '13px', color: '#6b7280', marginTop: '6px', lineHeight: '1.5', maxWidth: '320px', marginInline: 'auto' }}>Try changing the date range or search term. New invoices appear here as soon as you create them.</div>
			<button type="button" onClick={clearFilters} style={{ marginTop: '16px', height: '38px', padding: '0 16px', borderRadius: '10px', background: '#fff', color: '#0f1115', border: '1px solid #e8e8ec', fontWeight: '700', fontSize: '12.5px', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: '7px', boxShadow: '0 1px 2px rgba(15,17,21,0.04)', cursor: 'pointer' }}>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
				Clear filters
			</button>
		</div>
	);

    return (
        <div style={{marginTop:'0'}}>
			{/* Summary Cards */}
			{currentSupplierInfo && (
				isMobile ? (
					/* Mobile: financial summary card (collapsible) — matches Customer History */
					<div style={{ marginBottom: '14px', borderRadius: '16px', border: '1px solid #eaecf2', background: '#fff', overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,0.05)', padding: '14px' }}>
						<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px' }}>
							<div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0 }}>
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }}><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
								<span style={{ fontSize: '11px', fontWeight: '800', color: '#6b7280', letterSpacing: '0.8px', textTransform: 'uppercase', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>Financial Summary</span>
							</div>
							<button type="button" onClick={() => setSummaryOpen(o => !o)} style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'none', border: 'none', padding: 0, cursor: 'pointer', outline: 'none', flexShrink: 0 }}>
								<span style={{ fontSize: '19px', fontWeight: '800', color: '#0f172a', letterSpacing: '-0.5px' }}>{fmt(totalAmount)}</span>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ transform: summaryOpen ? 'none' : 'rotate(-90deg)', transition: 'transform 0.15s', flexShrink: 0 }}><path d="M6 9l6 6 6-6"/></svg>
							</button>
						</div>
						{summaryOpen && (
						<>
						<div style={{ display: 'flex', border: '1px solid #eef0f3', borderRadius: '12px', overflow: 'hidden', marginTop: '12px' }}>
							{[{ label: 'Paid', value: fmt(totalPaid), color: '#16a34a' }, { label: 'Pending', value: fmt(totalPending), color: '#ea580c' }, { label: 'Invoices', value: String(statementRows.length), color: '#0f172a' }].map((s, i) => (
								<div key={s.label} style={{ flex: 1, minWidth: 0, padding: '12px 8px', textAlign: 'center', borderLeft: i === 0 ? 'none' : '1px solid #eef0f3' }}>
									<div style={{ fontSize: '10px', fontWeight: '700', color: '#94a3b8', letterSpacing: '0.6px', textTransform: 'uppercase', marginBottom: '6px' }}>{s.label}</div>
									<div style={{ fontSize: '16px', fontWeight: '800', color: s.color, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{s.value}</div>
								</div>
							))}
						</div>
						<div style={{ marginTop: '14px' }}>
							<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '7px' }}>
								<span style={{ fontSize: '10px', fontWeight: '700', color: '#94a3b8', letterSpacing: '0.6px', textTransform: 'uppercase' }}>Collection Rate</span>
								<span style={{ fontSize: '11px', fontWeight: '700', color: '#374151' }}>{paidPercent}%</span>
							</div>
							<div style={{ height: '6px', borderRadius: '99px', background: '#eef0f3', overflow: 'hidden' }}>
								<div style={{ height: '100%', width: paidPercent + '%', borderRadius: '99px', background: '#F27420', minWidth: '8px' }}></div>
							</div>
						</div>
						</>
						)}
					</div>
				) : (
					/* Desktop: grid cards */
					<div style={{display:'grid',gridTemplateColumns:'repeat(4, 1fr)',gap:'10px',background:'#fff',borderLeft:'1px solid #eaecf2',borderRight:'1px solid #eaecf2',padding:'14px 18px'}}>
						{[
							{label:'Total Purchases', value:fmt(totalAmount), icon:'fa-shopping-cart', color:'#3b82f6', light:'#eff6ff'},
							{label:'Paid', value:fmt(totalPaid), icon:'fa-check-circle', color:'#16a34a', light:'#f0fdf4'},
							{label:'Returns', value:fmt(totalReturns), icon:'fa-undo', color:'#8b5cf6', light:'#f5f3ff'},
							{label:'Pending', value:fmt(totalPending), icon:'fa-clock-o', color:'#dc2626', light:'#fef2f2'},
						].map(c => (
							<div key={c.label} style={{display:'flex',alignItems:'center',gap:'13px',padding:'16px 18px',borderRadius:'12px',background:'#fff',border:'1px solid #edf2f7',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
								<div style={{width:'42px',height:'42px',borderRadius:'50%',background:c.light,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}><i className={'fa '+c.icon} style={{color:c.color,fontSize:'16px'}}></i></div>
								<div>
									<div style={{fontSize:'22px',fontWeight:'800',color:'#1a2332',lineHeight:1,fontVariantNumeric:'tabular-nums'}}>{c.value}</div>
									<div style={{fontSize:'10px',fontWeight:'600',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',marginTop:'4px'}}>{c.label}</div>
								</div>
							</div>
						))}
					</div>
				)
			)}

			{/* Invoice list — mobile: cards/table toggle; desktop: table */}
			{isMobile ? (
				currentSupplierInfo ? (
				<div>
					{/* Header: title + Card/Table view toggle on the right end */}
					<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', marginBottom: '12px', padding: '0 2px' }}>
						<h3 style={{ margin: 0, fontSize: '18px', fontWeight: '800', color: '#0f1115', minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>Transaction History</h3>
						<div style={{ display: 'flex', gap: '8px', flexShrink: 0 }}>
							{[{ k: 'card', l: 'Card View', i: 'fa-th-large' }, { k: 'table', l: 'Table View', i: 'fa-table' }].map(v => { const von = viewMode === v.k; return (
								<button key={v.k} type="button" onClick={() => setViewMode(v.k)} style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', padding: '7px 11px', borderRadius: '10px', border: von ? '1px solid rgb(234,88,12)' : '1px solid #eaecf2', cursor: 'pointer', fontSize: '12px', fontWeight: '700', background: von ? 'rgb(234,88,12)' : '#fff', color: von ? '#fff' : '#374151', boxShadow: von ? '0 2px 6px rgba(234,88,12,0.25)' : '0 1px 2px rgba(16,24,40,0.04)' }}>
									<i className={'fa ' + v.i} style={{ fontSize: '11px' }}></i>{v.l}
								</button>
							); })}
						</div>
					</div>
					{/* Search */}
					<div style={{ position: 'relative', marginBottom: '12px' }}>
						<span style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', display: 'flex' }}>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
						</span>
						<input type="text" placeholder="Search invoices…" value={filterText} onChange={e => setFilterText(e.target.value)} onFocus={e => { e.target.style.borderColor = 'rgb(234, 88, 12)'; e.target.style.boxShadow = '0 0 0 3px rgba(234,88,12,0.12)'; }} onBlur={e => { e.target.style.borderColor = filterText ? 'rgb(234, 88, 12)' : '#eaecf2'; e.target.style.boxShadow = '0 1px 2px rgba(16,24,40,0.04)'; }} style={{ width: '100%', height: '44px', padding: '0 14px 0 40px', borderRadius: '12px', border: filterText ? '1px solid rgb(234, 88, 12)' : '1px solid #eaecf2', background: '#fff', fontSize: '13.5px', color: '#0f1115', outline: 'none', fontFamily: 'inherit', boxSizing: 'border-box', boxShadow: '0 1px 2px rgba(16,24,40,0.04)' }} />
					</div>
					{/* Status tabs (underline style — matches Products) */}
					<div style={{ display: 'flex', alignItems: 'center', borderBottom: '1px solid #eef2f7', marginBottom: '14px' }}>
						{[{ k: 'all', l: 'All', c: chCounts.all }, { k: 'unpaid', l: 'Unpaid', c: chCounts.unpaid }, { k: 'paid', l: 'Paid', c: chCounts.paid }].map(t => {
							const on = statusTab === t.k;
							return (
								<button key={t.k} type="button" onClick={() => setStatusTab(t.k)} style={{ flex: 1, display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: '7px', padding: '11px 4px', background: 'none', border: 'none', borderBottom: on ? '2px solid rgb(234,88,12)' : '2px solid transparent', marginBottom: '-1px', cursor: 'pointer', fontSize: '13px', fontWeight: on ? '800' : '600', color: on ? 'rgb(234,88,12)' : '#64748b' }}>
									{t.l}
									<span style={{ fontSize: '10px', fontWeight: '700', minWidth: '18px', height: '18px', borderRadius: '9px', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', padding: '0 5px', background: on ? 'rgb(234,88,12)' : '#e5e7eb', color: on ? '#fff' : '#64748b' }}>{t.c}</span>
								</button>
							);
						})}
					</div>
					{/* Content */}
					{(isLoading && statementRows.length === 0) ? (
						<div style={{ background: '#fff', borderRadius: '14px', border: '1px solid #eaecf2' }}><SpecTableLoading label="Loading history…" /></div>
					) : (viewMode === 'table' ? chTableData : chCards).length === 0 ? (
						chEmptyCard
					) : viewMode === 'table' ? (
						<div style={{ borderRadius: '14px', border: '1px solid #eaecf2', boxShadow: '0 1px 6px rgba(0,0,0,0.05)', background: '#fff', overflow: 'hidden' }}>
							<div style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
								<div style={{ minWidth: '900px' }}>
									<DataTable columns={columns} data={chTableData} responsive={false} customStyles={mergedStyles} conditionalRowStyles={conditionalRowStyles} pagination paginationPerPage={10} paginationRowsPerPageOptions={[10, 20, 50, 100]} paginationComponent={SpecPagination} persistTableHead noDataComponent={<div style={{ padding: '40px 24px', textAlign: 'center', color: '#9ca3af', fontSize: '14px', fontWeight: '600' }}>No invoices found</div>} />
								</div>
							</div>
						</div>
					) : (
						<div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
							{chCards.map(row => {
								const bal = toNum(row.balance);
								const open = expandedInv === (row.id + '-' + row._sl);
								const payEntries = [];
								if (toNum(row.paid_by_cash) > 0) payEntries.push(['Cash', toNum(row.paid_by_cash)]);
								if (toNum(row.paid_by_card) > 0) payEntries.push(['Card', toNum(row.paid_by_card)]);
								if (toNum(row.paid_by_bank) > 0) payEntries.push(['Bank Transfer', toNum(row.paid_by_bank)]);
								if (toNum(row.paid_by_cheque) > 0) payEntries.push(['Cheque', toNum(row.paid_by_cheque)]);
								const payModeColors = { 'Cash': { bg: '#f0fdf4', color: '#15803d', border: '#86efac', icon: 'fa-money' }, 'Card': { bg: '#eff6ff', color: '#1d4ed8', border: '#93c5fd', icon: 'fa-credit-card' }, 'Cheque': { bg: '#f5f3ff', color: '#6d28d9', border: '#c4b5fd', icon: 'fa-file-text-o' }, 'Bank Transfer': { bg: '#ecfeff', color: '#0e7490', border: '#67e8f9', icon: 'fa-university' } };
								return (
									<div key={row.id + '-' + row._sl} style={{ display: 'flex', borderRadius: '14px', border: '1px solid #eaecf2', overflow: 'hidden', background: '#fff', boxShadow: '0 1px 4px rgba(0,0,0,0.05)' }}>
										<div style={{ width: '4px', flexShrink: 0, background: 'linear-gradient(180deg, rgb(234, 88, 12), #ea580c)' }}></div>
										<div style={{ flex: 1, padding: '12px 12px 10px', minWidth: 0 }}>
											<div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '8px' }}>
												<div style={{ minWidth: 0 }}>
													<div style={{ fontSize: '11px', color: 'rgb(234, 88, 12)', fontWeight: '700', marginBottom: '4px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', display: 'flex', alignItems: 'center', gap: '8px' }}>
														<span>{row.id == 0 ? 'On Account' : '#' + row.id}</span>
														{(row.invoice_date || row.created_at) ? <span style={{ fontWeight: '500', color: '#6b7280' }}>{row.invoice_date || row.created_at}</span> : ''}
													</div>
													{currentSupplierInfo?.name ? <div style={{ fontWeight: '700', color: '#1e293b', fontSize: '13px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', marginBottom: '6px' }}>{currentSupplierInfo.name}</div> : null}
													<div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center', marginTop: '2px' }}>
														{statusBadge(row)}
														{payEntries.length === 0 && bal > 0 && <span style={{ fontSize: '11px', fontWeight: '700', color: '#dc2626', whiteSpace: 'nowrap' }}>No Payment</span>}
														{payEntries.map(([mode, amt2]) => { const s2 = payModeColors[mode] || { bg: '#f8fafc', color: '#475569', border: '#e2e8f0', icon: 'fa-circle' }; return <span key={mode} style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', background: s2.bg, border: '1px solid ' + s2.border, borderRadius: '6px', padding: '2px 7px', fontSize: '10px', fontWeight: '600', color: s2.color, whiteSpace: 'nowrap' }}><i className={'fa ' + s2.icon} style={{ fontSize: '9px' }}></i>{mode} {amt2.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>; })}
													</div>
												</div>
												<div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '6px', flexShrink: 0 }}>
													<span style={{ background: '#FFF7ED', border: '1px solid #fed7aa', borderRadius: '8px', padding: '3px 10px', fontWeight: '800', color: 'rgb(234, 88, 12)', fontSize: '13px', whiteSpace: 'nowrap' }}>{fmt(row.net_amount)}</span>
													<button type="button" onClick={() => setExpandedInv(open ? null : (row.id + '-' + row._sl))} style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '4px', display: 'flex', alignItems: 'center', justifyContent: 'center', outline: 'none' }}>
														<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={open ? 'rgb(234, 88, 12)' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ transform: open ? 'rotate(180deg)' : 'none', transition: 'all 0.2s' }}><path d="M6 9l6 6 6-6"/></svg>
													</button>
												</div>
											</div>
											{open && (
												<div style={{ marginTop: '10px', paddingTop: '10px', borderTop: '1px solid #f1f5f9', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
													{[{ l: 'Invoice Amount', v: fmt(row.net_amount), c: '#0f1115' }, { l: 'Paid', v: fmt(row.total_paid), c: '#16a34a' }, { l: 'Returns / Adj', v: fmt(row.credit_adj), c: '#7c3aed' }, { l: 'Balance', v: fmt(row.balance), c: bal > 0 ? '#ea580c' : '#16a34a' }].map(d => (
														<div key={d.l}>
															<div style={{ fontSize: '10px', fontWeight: '700', color: '#94a3b8', letterSpacing: '0.5px', textTransform: 'uppercase', marginBottom: '3px' }}>{d.l}</div>
															<div style={{ fontSize: '13.5px', fontWeight: '700', color: d.c, fontVariantNumeric: 'tabular-nums' }}>{d.v}</div>
														</div>
													))}
												</div>
											)}
										</div>
									</div>
								);
							})}
						</div>
					)}
				</div>
				) : (
				<div style={{ background: '#fff', border: '1px solid #eaecf2', borderRadius: '14px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', padding: '40px 24px', textAlign: 'center' }}>
					<span style={{ width: '64px', height: '64px', borderRadius: '50%', background: '#fff3e9', color: '#F27420', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '6px' }}>
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
					</span>
					<div style={{ fontSize: '15px', fontWeight: '800', color: '#0f1115' }}>Select a supplier</div>
					<div style={{ fontSize: '13px', color: '#6b7280', marginTop: '4px', maxWidth: '320px', marginInline: 'auto', lineHeight: 1.5 }}>Pick a supplier above to see their full transaction history and balance.</div>
				</div>
				)
			) : (
			<div style={{borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',background:'#fff',overflow:'hidden'}}>
				{currentSupplierInfo && (
					<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'12px 16px',borderBottom:'1px solid #f0f0f0'}}>
						{!isMobile && (
							<div style={{display:'flex',alignItems:'center',gap:'8px'}}>
								<i className="fa fa-list-alt" style={{fontSize:'13px',color:'rgb(234, 88, 12)'}}></i>
								<span style={{fontSize:'13px',fontWeight:'700',color:'#1e293b',letterSpacing:'-0.1px'}}>Transaction History</span>
							</div>
						)}
						<div style={{ position: 'relative', width: isMobile ? '100%' : 'auto' }}>
							<i className="fa fa-search" style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',fontSize:'12px',pointerEvents:'none'}}></i>
							<input type="text" placeholder="Search invoices..." value={filterText} onChange={e=>setFilterText(e.target.value)}
								style={{ height: '38px', borderRadius: '9px', border: '1.5px solid #e5e7eb', padding: '0 14px 0 34px', fontSize: '13px', background: '#fafafa', outline: 'none', width: isMobile ? '100%' : '220px' }}
								onFocus={e=>e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e=>e.target.style.borderColor='#e5e7eb'}
							/>
{filterText && <button type="button" onClick={() => {setFilterText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
						</div>
					</div>
				)}
				<div style={{ overflow: 'hidden', position: 'relative' }}>
					<div className="sh-scroll-inner" style={{ transition: 'transform 0.2s ease', minWidth: (isMobile && finalData.length > 0) ? '900px' : 'auto' }}>
						<DataTable columns={columns} data={finalData} pagination paginationPerPage={10} paginationRowsPerPageOptions={[10, 20, 50, 100]} paginationComponent={SpecPagination}
							customStyles={mergedStyles} conditionalRowStyles={conditionalRowStyles}
							progressPending={isLoading && historyData.length === 0}
							progressComponent={<SpecTableLoading label="Loading history…" />}
							noDataComponent={
								<div style={{padding:'48px 24px',width:'100%'}}>
									<div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:'8px'}}>
										<span style={{width:'56px',height:'56px',borderRadius:'50%',background:'#fff7ed',color:'rgb(234, 88, 12)',border:'1px solid #fed7aa',display:'flex',alignItems:'center',justifyContent:'center'}}>
											{currentSupplierInfo
												? <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
												: <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>}
										</span>
										<div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>
											{currentSupplierInfo ? 'No history found' : 'Select a supplier'}
										</div>
										<div style={{fontSize:'13px',color:'#6b7280',textAlign:'center',maxWidth:'320px'}}>
											{currentSupplierInfo
												? 'No transactions match the current date range or filters.'
												: 'Pick a supplier from the dropdown above to see their transaction history.'}
										</div>
									</div>
								</div>
							}
						/>
					</div>
					{isLoading && historyData.length > 0 && (
						<div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
							<div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#c2410c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
								<i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
								<span>Loading…</span>
							</div>
						</div>
					)}
				</div>
				{isMobile && finalData.length > 0 && (
					<div style={{ padding: '0 12px 10px' }}>
						<input type="range" min="0" max="100" defaultValue="0"
							className="sh-range-scroll"
							onChange={(e) => {
								const inner = document.querySelector('.sh-scroll-inner');
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
			)}
        </div>
    );
}

export default function SupplierHistoryApp(props) {
    return (
	<>
	<style>{`
		.sh-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; }
		.sh-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; }
		.sh-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: rgb(234, 88, 12); cursor: pointer; border: none; }
		@keyframes drpFadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
		@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
		.drp-preset-chip::-webkit-scrollbar { display: none; }
	`}</style>
	<div className="row">
		<div className="col-12"><FilterBar apiUrl={props.supplierListApi} printApi={props.printApi} excelApi={props.excelApi} emailApi={props.historyEmailApi} initialSupplierId={props.supplierId} /></div>
		<div className="col-12" style={{marginBottom:'70px'}}><List {...props} /></div>
	</div><ToastContainer autoClose={3000} />
	</>
    );
}

if (document.getElementById('supplier-history-app')) {
    const id = "supplier-history-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset);
    root.render(<Provider store={store}><SupplierHistoryApp {...props} /></Provider>);
}

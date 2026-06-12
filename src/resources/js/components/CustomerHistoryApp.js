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
import { useWindowSize } from "./../hooks/useWindowSize";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import DateRangePicker from "./../hooks/DateRangePicker";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";

/* ── Custom Pagination — exact spec footer ─────────────────── */
function CHPagination({ rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage }) {
  const totalPages = Math.ceil(rowCount / rowsPerPage) || 1;
  const from = rowCount === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
  const to   = Math.min(currentPage * rowsPerPage, rowCount);
  const isFirst = currentPage <= 1;
  const isLast  = currentPage >= totalPages;
  const navBtn = (disabled) => ({ width: '28px', height: '28px', borderRadius: '7px', background: '#fff', border: '1px solid #e8e8ec', color: disabled ? '#c8c8cf' : '#F27420', cursor: disabled ? 'not-allowed' : 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 0 });

  return (
    <div style={{ margin: '0 -22px', padding: '12px 22px', background: '#fafafb', borderTop: '1px solid #eeeeef', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '14px' }}>
      <span style={{ fontSize: '12.5px', color: '#6b7280', fontWeight: '600' }}>Rows per page:</span>
      <select value={rowsPerPage} onChange={e => onChangeRowsPerPage(Number(e.target.value), currentPage)}
        style={{ height: '30px', padding: '0 26px 0 10px', borderRadius: '7px', background: '#fff7f0', color: '#F27420', border: '1px solid #f6c9a8', fontSize: '12.5px', fontWeight: '700', fontFamily: 'inherit', cursor: 'pointer' }}>
        <option value={10}>10</option>
        <option value={25}>25</option>
        <option value={50}>50</option>
        <option value={100}>100</option>
      </select>
      <span style={{ fontSize: '12.5px', color: '#F27420', fontWeight: '800' }}>{from}&ndash;{to} of {rowCount}</span>
      <div style={{ display: 'flex', gap: '2px' }}>
        <button disabled={isFirst} onClick={() => onChangePage(1)} style={navBtn(isFirst)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>
        </button>
        <button disabled={isFirst} onClick={() => onChangePage(currentPage - 1)} style={navBtn(isFirst)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
        <button disabled={isLast} onClick={() => onChangePage(currentPage + 1)} style={navBtn(isLast)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
        <button disabled={isLast} onClick={() => onChangePage(totalPages)} style={navBtn(isLast)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg>
        </button>
      </div>
    </div>
  );
}

// ----------------- Date defaults -----------------
const today = new Date();
const formatDate = (date) => {
	const y = date.getFullYear();
	const m = String(date.getMonth() + 1).padStart(2, '0');
	const d = String(date.getDate()).padStart(2, '0');
	return `${y}-${m}-${d}`;
};
const todayStr = formatDate(today);

// ----------------- Slice + Store -----------------
const slice = createSlice({
    name: 'customers',
    initialState: {
		customers: [],
		selectedInvoices: [],
		currentCustomer: "",
		currentCustomerInfo: "",
		loading: false,
		toDate: todayStr,
		fromDate: todayStr,
		option: [],
	},
    reducers: {
        setCustomers: (state, action) => { state.customers = action.payload },
		setToDate: (state, action) => { state.toDate = action.payload },
		setFromDate: (state, action) => { state.fromDate = action.payload },
		setSelectedInvoices: (state, action) => { state.selectedInvoices = action.payload },
		setOption: (state, action) => { state.option = action.payload },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
		setCurrentCustomerInfo: (state, action) => { state.currentCustomerInfo = action.payload; },
		setCustomersLoading: (state, action) => { state.loading = action.payload; },
    },
});

const { setCustomers, setSelectedInvoices, setToDate, setFromDate, setOption, setCurrentCustomer, setCurrentCustomerInfo, setCustomersLoading } = slice.actions;

const store = configureStore({
    reducer: { customers: slice.reducer },
	middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(logger),
	devTools: process.env.NODE_ENV !== 'production',
});
// ---- Email Statement Modal ----
function EmailStatementModal({ open, onClose, emailApi, historyApi, customer, fromDate, toDate }) {
	const [invoiceCount, setInvoiceCount] = useState(0);
	const [countLoading, setCountLoading] = useState(false);
	const periodLabel = () => {
		const fmt = (d) => {
			if (!d) return '';
			const dt = new Date(d);
			return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
		};
		if (!fromDate && !toDate) return 'All time';
		return `${fmt(fromDate) || '—'} – ${fmt(toDate) || 'Today'}`;
	};

	const defaultSubject = () => `Account Statement — ${customer ? customer.name : ''} — ${periodLabel()}`;
	const defaultMessage = () =>
		`Dear ${customer ? customer.name : 'Customer'},\n\nPlease find attached your account statement for the period ${periodLabel()}.\n\nKindly review the statement and settle any outstanding balance at your earliest convenience.\n\nThank you for your business.`;

	const [toEmail, setToEmail] = useState('');
	const [subject, setSubject] = useState('');
	const [message, setMessage] = useState('');
	const [sending, setSending] = useState(false);
	const [errors, setErrors] = useState({});

	useEffect(() => {
		if (open) {
			setToEmail(customer && customer.email ? customer.email : '');
			setSubject(defaultSubject());
			setMessage(defaultMessage());
			setErrors({});
			setSending(false);
			setInvoiceCount(0);
			if (customer && customer.id) {
				setCountLoading(true);
				axios.post(historyApi, {
					currentCustomer: customer.id,
					fromDate: fromDate || '',
					toDate: toDate || '',
					option: 'all',
					page: 1,
					per_page: 1,
				})
				.then(res => {
					if (res.data && res.data.success === true) {
						setInvoiceCount(res.data.payload.total_count || 0);
					}
				})
				.catch(() => {})
				.finally(() => setCountLoading(false));
			}
		}
	}, [open]);

	if (!open) return null;

	const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	// Live errors — recomputed on every keystroke.
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
			currentCustomer: customer.id,
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
				const msg = res.data && typeof res.data.payload === 'string'
					? res.data.payload : 'Could not send the statement email.';
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
					<span style={{ width: '40px', height: '40px', borderRadius: '10px', background: '#fff7ed', border: '1px solid #fed7aa', color: '#ea580c', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
					</span>
					<div style={{ flex: 1, minWidth: 0 }}>
						<h3 style={{ margin: 0, fontSize: '15.5px', fontWeight: '800', color: '#0f1115' }}>Email Statement</h3>
						<p style={{ margin: '2px 0 0', fontSize: '12px', color: '#6b7280' }}>Send the account statement as a PDF</p>
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
						{countLoading
							? <span style={{ fontSize: '11.5px', fontWeight: '700', color: '#6b7280', background: '#f4f4f6', border: '1px solid #e8e8ec', borderRadius: '99px', padding: '4px 11px' }}>Loading…</span>
							: <span style={{ fontSize: '11.5px', fontWeight: '700', color: invoiceCount > 0 ? '#15803d' : '#b91c1c', background: invoiceCount > 0 ? '#e8f8ee' : '#fef2f2', border: `1px solid ${invoiceCount > 0 ? '#bde5c9' : '#f8d2d2'}`, borderRadius: '99px', padding: '4px 11px' }}>
								{invoiceCount} invoice{invoiceCount === 1 ? '' : 's'}
							</span>}
					</div>

					{!countLoading && invoiceCount === 0 && (
						<div style={{ background: '#fffbeb', border: '1px solid #f5d98c', borderRadius: '9px', padding: '10px 13px', marginBottom: '16px', fontSize: '12px', color: '#b45309', fontWeight: '600' }}>
							No transactions in this period — there is nothing to send.
						</div>
					)}

					{/* To */}
					<div style={{ marginBottom: '14px' }}>
						<label style={label}>To</label>
						<input type="email" value={toEmail} onChange={e => setToEmail(e.target.value)}
							placeholder="customer@email.com"
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
					<button onClick={handleSend} disabled={sending || countLoading || invoiceCount === 0 || !toEmailValid}
						style={{ height: '40px', padding: '0 20px', borderRadius: '9px', border: 'none', background: (sending || countLoading || invoiceCount === 0 || !toEmailValid) ? '#fdba74' : '#f97316', color: '#fff', fontWeight: '700', fontSize: '13px', cursor: (sending || countLoading || invoiceCount === 0 || !toEmailValid) ? 'not-allowed' : 'pointer', display: 'inline-flex', alignItems: 'center', gap: '8px' }}>
						{sending
							? <><svg width="14" height="14" viewBox="0 0 24 24" style={{ animation: 'ch-spin 0.7s linear infinite' }}><circle cx="12" cy="12" r="9" fill="none" stroke="rgba(255,255,255,0.4)" strokeWidth="3"/><path d="M12 3a9 9 0 0 1 9 9" fill="none" stroke="#fff" strokeWidth="3" strokeLinecap="round"/></svg> Sending…</>
							: <><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Send Statement</>}
					</button>
				</div>
				<style>{`@keyframes ch-spin{to{transform:rotate(360deg)}}`}</style>
			</div>
		</div>
	);
}

// ---- Filter Bar ----
function UnifiedBar({ apiUrl, printApi, excelApi, emailApi, historyApi, initialCustomerId }) {
	const dispatch = useDispatch();
	const { currentCustomer, currentCustomerInfo, selectedInvoices, toDate, fromDate, option, customers } = useSelector(state => state.customers);
	const openInNewTab = useOpenInNewTab();
	const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
	const [isTablet, setIsTablet] = useState(window.innerWidth >= 768 && window.innerWidth <= 1024);
	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const [ptOpen, setPtOpen] = useState(false);
	const ptRef = useRef(null);
	useEffect(() => {
		if (!ptOpen) return;
		const handle = (e) => { if (ptRef.current && !ptRef.current.contains(e.target)) setPtOpen(false); };
		document.addEventListener('mousedown', handle);
		return () => document.removeEventListener('mousedown', handle);
	}, [ptOpen]);
	useEffect(() => {
		const handle = () => { setIsMobile(window.innerWidth <= 767); setIsTablet(window.innerWidth >= 768 && window.innerWidth <= 1024); };
		window.addEventListener('resize', handle);
		return () => window.removeEventListener('resize', handle);
	}, []);

	const filterOptions = [
		{ label: "Cash", value: "cash" },
		{ label: "Credit", value: "credit" },
		{ label: "Cheque", value: "cheque" },
		{ label: "Bank Transfer", value: "bank transfer" },
	];

	useEffect(() => {
		const fetchCustomers = async () => {
			try {
				const response = await axios.get(apiUrl);
				if (response.data.success === true) {
					dispatch(setCustomers(response.data.payload));
					if (initialCustomerId) {
						const cust = response.data.payload.find(c => c.id == initialCustomerId);
						if (cust) {
							dispatch(setCurrentCustomer(cust.id));
							dispatch(setCurrentCustomerInfo(cust));
						}
					}
				}
			} catch (err) {
				console.error('Failed to load customers', err);
			}
		};
		fetchCustomers();
	}, [apiUrl, dispatch, initialCustomerId]);

	const printInvoice = (type) => {
		if (!currentCustomer) { toast.error('Please select a customer first.'); return; }
		openInNewTab(printApi, {
			customer_id: currentCustomer,
			start_date: fromDate,
			end_date: toDate,
			invoices: selectedInvoices,
			type,
		});
	};

	const downloadExcel = () => {
		if (!currentCustomer) { toast.error('Please select a customer first.'); return; }
		const qs = new URLSearchParams();
		qs.set('customer_id', currentCustomer);
		if (fromDate) qs.set('start_date', fromDate);
		if (toDate) qs.set('end_date', toDate);
		if (selectedInvoices) qs.set('invoices', selectedInvoices);
		qs.set('type', 'with-balance');
		const url = (excelApi || '/excel/customer_history') + '?' + qs.toString();
		const a = document.createElement('a');
		a.href = url;
		a.download = '';
		document.body.appendChild(a);
		a.click();
		a.remove();
	};

	const openEmailModal = () => {
		if (!currentCustomer) {
			toast.error('Please select a customer first.');
			return;
		}
		setEmailModalOpen(true);
	};

	const selectCtrl = {
		...orangeSelectStyles,
		control: (base, state) => ({
			...orangeSelectStyles.control(base, state),
			minHeight: '38px', borderRadius: '9px',
			border: state.isFocused ? '1.5px solid #f97316' : '1.5px solid #e5e7eb',
			boxShadow: 'none', fontSize: '13px',
			'&:hover': { borderColor: '#f97316' },
		}),
		valueContainer: (base) => ({ ...base, padding: '2px 10px', gap: '4px' }),
		indicatorSeparator: () => ({ display: 'none' }),
		menuPortal: (base) => ({ ...base, zIndex: 9999 }),
		multiValue: (base) => ({ ...base, background: '#FFF5ED', border: '1px solid #fed7aa', borderRadius: '6px' }),
		multiValueLabel: (base) => ({ ...base, color: '#F27420', fontWeight: '600', fontSize: '12.5px' }),
		multiValueRemove: (base) => ({ ...base, color: '#F27420', borderRadius: '4px', ':hover': { background: '#fed7aa', color: '#F27420' } }),
	};

	// Both dropdowns use the same plain selectCtrl — matches Supplier History.
	const customerSelectCtrl = selectCtrl;
	const filterSelectCtrl = selectCtrl;

	const iconBtn = {
		width: '42px', height: '42px', borderRadius: '10px', padding: 0,
		background: '#fff', border: '1px solid #e8e8ec', color: '#6b7280',
		display: 'flex', alignItems: 'center', justifyContent: 'center',
		cursor: 'pointer', outline: 'none', boxShadow: '0 1px 2px rgba(15,17,21,0.04)',
		transition: 'all 0.15s',
	};

	const chTipStyles = `
		.ch-tip{position:relative;}
		.ch-tip::after,.ch-tip::before{position:absolute;left:50%;opacity:0;pointer-events:none;transition:opacity .14s ease,transform .14s ease;z-index:50;}
		.ch-tip::after{content:attr(data-tip);bottom:calc(100% + 9px);transform:translateX(-50%) translateY(4px);background:#0f1115;color:#fff;font-size:11.5px;font-weight:600;line-height:1;letter-spacing:.2px;padding:7px 10px;border-radius:7px;white-space:nowrap;box-shadow:0 6px 18px -4px rgba(15,17,21,0.45),0 2px 6px rgba(15,17,21,0.25);}
		.ch-tip::before{content:'';bottom:calc(100% + 4px);transform:translateX(-50%) translateY(4px);border:5px solid transparent;border-top-color:#0f1115;}
		.ch-tip:hover::after,.ch-tip:hover::before{opacity:1;transform:translateX(-50%) translateY(0);}
	`;

	const actionIcons = (
		<div style={{ display: 'flex', gap: '6px', flexShrink: 0 }}>
			<style>{chTipStyles}</style>
			<button className="ch-tip" data-tip="Email Statement" style={iconBtn} onClick={openEmailModal}
				onMouseEnter={e => { e.currentTarget.style.borderColor = '#F27420'; e.currentTarget.style.color = '#F27420'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
			</button>
			<button className="ch-tip" data-tip="Print" style={iconBtn} onClick={() => printInvoice('with-balance')}
				onMouseEnter={e => { e.currentTarget.style.borderColor = '#F27420'; e.currentTarget.style.color = '#F27420'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
			</button>
			<button className="ch-tip" data-tip="Download Statement" style={iconBtn} onClick={downloadExcel}
				onMouseEnter={e => { e.currentTarget.style.borderColor = '#F27420'; e.currentTarget.style.color = '#F27420'; }}
				onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8e8ec'; e.currentTarget.style.color = '#6b7280'; }}>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
			</button>
		</div>
	);

	const emailModalEl = (
		<EmailStatementModal
			open={emailModalOpen}
			onClose={() => setEmailModalOpen(false)}
			emailApi={emailApi}
			historyApi={historyApi}
			customer={currentCustomerInfo || null}
			fromDate={fromDate}
			toDate={toDate}
		/>
	);

	const ptLabel = (option && option.length > 0) ? option.map(o => o.label).join(', ') : 'All';
	const filterCardStyle = { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', width: '100%', height: '54px', padding: '8px 12px', borderRadius: '12px', background: '#fff', border: '1px solid #eaecf2', boxShadow: '0 1px 2px rgba(16,24,40,0.04)', cursor: 'pointer', outline: 'none', boxSizing: 'border-box', transition: 'border-color 0.15s', textAlign: 'left' };
	const cardLabelStyle = { fontSize: '10px', fontWeight: '700', color: '#94a3b8', letterSpacing: '0.7px', textTransform: 'uppercase', marginBottom: '3px', lineHeight: 1 };
	const cardValueStyle = { fontSize: '15px', fontWeight: '700', color: '#0f1115', lineHeight: 1.1 };
	if (isMobile) {
		return (
			<>
			<style>{`.ch-mob-filters .ph-drp-btn{width:100%!important;}`}</style>
			<div className="ch-mob-filters" style={{ background: 'transparent', padding: '0', marginBottom: '14px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
				{/* Row 1: Customer + action icons */}
				<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
					<div style={{ flex: 1, minWidth: 0, position: 'relative' }}>
						<span style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#F27420', zIndex: 1, pointerEvents: 'none', display: 'flex', alignItems: 'center' }}>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
						</span>
						<Select
							styles={{ ...selectCtrl, control: (base, state) => ({ ...selectCtrl.control(base, state), minHeight: '40px' }), valueContainer: (base) => ({ ...base, padding: '2px 10px 2px 34px', gap: '4px' }) }}
							options={customers.map(c => ({ value: c.id, label: c.name }))}
							value={currentCustomerInfo ? { value: currentCustomerInfo.id, label: currentCustomerInfo.name } : null}
							onChange={(sel) => {
								if (sel) {
									const cust = customers.find(c => c.id == sel.value);
									dispatch(setCurrentCustomer(cust.id));
									dispatch(setCurrentCustomerInfo(cust));
								} else {
									dispatch(setCurrentCustomer(""));
									dispatch(setCurrentCustomerInfo(""));
								}
							}}
							isClearable isSearchable
							placeholder="Select Customer"
							classNamePrefix="react-select"
							menuPortalTarget={document.body}
						/>
					</div>
					{actionIcons}
				</div>
				{/* Row 2: Payment Type + Date Range — labeled filter cards (match reference) */}
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
			</div>
			{emailModalEl}
			</>
		);
	}

	return (
		<>
		<div style={{ background: '#ffffff', borderRadius: '0', border: '1px solid #eaecf2', borderTop: 'none', borderBottom: 'none', boxShadow: 'none', padding: '14px 18px' }}>
			<div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: isTablet ? 'nowrap' : 'wrap' }}>
				{/* Customer (editable dropdown) */}
				<div style={{ flex: isTablet ? 1 : '1.4 1 0%', minWidth: isTablet ? 0 : '240px' }}>
					<Select
						styles={{ ...customerSelectCtrl, control: (base, state) => ({ ...customerSelectCtrl.control(base, state), minHeight: '42px' }) }}
						options={customers.map(c => ({ value: c.id, label: c.name }))}
						value={currentCustomerInfo ? { value: currentCustomerInfo.id, label: currentCustomerInfo.name } : null}
						onChange={(sel) => {
							if (sel) {
								const cust = customers.find(c => c.id == sel.value);
								dispatch(setCurrentCustomer(cust.id));
								dispatch(setCurrentCustomerInfo(cust));
							} else {
								dispatch(setCurrentCustomer(""));
								dispatch(setCurrentCustomerInfo(""));
							}
						}}
						isClearable isSearchable
						placeholder="Select Customer"
						classNamePrefix="react-select"
						menuPortalTarget={document.body}
					/>
				</div>
				{/* Filter dropdown */}
				<div style={{ flex: isTablet ? undefined : '1 1 0%', minWidth: isTablet ? '120px' : '160px', flexShrink: isTablet ? 0 : 1 }}>
					<Select
						styles={{ ...filterSelectCtrl, control: (base, state) => ({ ...filterSelectCtrl.control(base, state), minHeight: '42px' }) }}
						options={filterOptions}
						isMulti isClearable closeMenuOnSelect={false}
						placeholder="All"
						onChange={(e) => dispatch(setOption(e || []))}
						value={option}
						isSearchable={false}
						menuPortalTarget={document.body}
					/>
				</div>
				{/* Date range */}
				<div style={{ flex: isTablet ? undefined : '1.2 1 0%', minWidth: isTablet ? undefined : '220px' }}>
					<DateRangePicker
						fromDate={fromDate}
						toDate={toDate}
						onFromChange={val => dispatch(setFromDate(val))}
						onToChange={val => dispatch(setToDate(val))}
					/>
				</div>
				{actionIcons}
			</div>
		</div>
		{emailModalEl}
		</>
	);
}

// ---- Data List ----
function List(props) {
    const dispatch = useDispatch();
    const { currentCustomer, selectedInvoices, currentCustomerInfo, toDate, fromDate, option } =
        useSelector(state => state.customers);

    const [data, setData] = useState([]);
	const [pastBalance, setPastBalance] = useState(0);
    const [filterText, setFilterText] = useState("");
    const [selectedRows, setSelectedRows] = useState([]);
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
    const [isTablet, setIsTablet] = useState(window.innerWidth >= 768 && window.innerWidth <= 1024);
    const customStyles = useDataTableStyles();

    useEffect(() => {
        const handle = () => {
            setIsMobile(window.innerWidth <= 767);
            setIsTablet(window.innerWidth >= 768 && window.innerWidth <= 1024);
        };
        window.addEventListener('resize', handle);
        return () => window.removeEventListener('resize', handle);
    }, []);

	const searchFields = ["created_at", "id"];

    const [historyData, setHistoryData] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

	// Server-side pagination state
	const [page, setPage] = useState(1);
	const [perPage, setPerPage] = useState(10);
	const [sortBy, setSortBy] = useState('created_at');
	const [sortDir, setSortDir] = useState('asc');
	const [totalCount, setTotalCount] = useState(0);
	const [summaryOpen, setSummaryOpen] = useState(false);
	const [statusTab, setStatusTab] = useState('all');
	const [expandedInv, setExpandedInv] = useState(null);
	const [viewMode, setViewMode] = useState('card');
	const [serverTotals, setServerTotals] = useState(null);

	// Debounce search input — only fire the request once typing stops.
	const [debouncedQuery, setDebouncedQuery] = useState("");
	useEffect(() => {
		const t = setTimeout(() => setDebouncedQuery(filterText.toLowerCase().trim()), 300);
		return () => clearTimeout(t);
	}, [filterText]);

	// Reset to page 1 whenever any filter/sort/search changes that affects the result set.
	useEffect(() => { setPage(1); }, [currentCustomer, toDate, fromDate, option, debouncedQuery, sortBy, sortDir, perPage]);

	useEffect(() => {
		if (!currentCustomer) {
			setHistoryData([]); setPastBalance(0); setTotalCount(0); setServerTotals(null);
			return;
		}
		let cancelled = false;
		(async () => {
			setIsLoading(true);
			try {
				const res = await axios.post(props.historyListApi, {
					currentCustomer,
					toDate: toDate || '',
					fromDate: fromDate || '',
					option: (Array.isArray(option) && option.length > 0) ? option.map(o => o.value).join(',') : 'all',
					page, per_page: perPage,
					sort_by: sortBy, sort_dir: sortDir,
					search: debouncedQuery,
				});
				if (cancelled) return;
				if (res.data.success) {
					setHistoryData(res.data.payload.invoices || []);
					setPastBalance(res.data.payload.past_balance || 0);
					setTotalCount(res.data.payload.total_count || 0);
					setServerTotals(res.data.payload.totals || null);
				}
			} catch (e) {
				if (!cancelled) console.error('Failed to load history', e);
			} finally {
				if (!cancelled) setIsLoading(false);
			}
		})();
		return () => { cancelled = true; };
	}, [currentCustomer, toDate, fromDate, option, page, perPage, sortBy, sortDir, debouncedQuery, props.historyListApi]);

	// Server already filtered + sorted + paged the rows; just pass through.
	const filteredData = historyData;

    const handleRowSelected = (state) => {
		const ids = state.selectedRows.map(r => r.id);
        setSelectedRows(state.selectedRows);
		dispatch(setSelectedInvoices(ids));
    };

	const toNum = v => Number(v) || 0;
	const fmt = v => { const n = Number(v) || 0; return '£' + n.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

	// Rows are already enriched on the server (balance, running_balance, credit/cash split).
	// Derive credit_inv/cash_inv here only if missing (defensive — works for both legacy + paged responses).
	const processedData = useMemo(() => {
		return filteredData.map(item => {
			if (item.credit_inv !== undefined && item.cash_inv !== undefined) return item;
			const isCashSale = toNum(item.paid_by_cash) > 0;
			return {
				...item,
				credit_inv: isCashSale ? 0 : toNum(item.net_amount),
				cash_inv: isCashSale ? toNum(item.net_amount) : 0,
			};
		});
	}, [filteredData]);

	// Use server-side totals for the summary row so it reflects the whole filtered set,
	// not just the current page.
	const summary = useMemo(() => {
		if (!processedData.length || !serverTotals) return null;
		return {
			isSummary: true,
			created_at: "",
			id: "TOTAL",
			net_amount:       toNum(serverTotals.net_amount),
			total:            toNum(serverTotals.net_amount),
			credit_inv:       toNum(serverTotals.credit_inv),
			cash_inv:         toNum(serverTotals.cash_inv),
			total_paid:       toNum(serverTotals.total_paid),
			credit_adj:       toNum(serverTotals.credit_adj),
			total_discounted: toNum(serverTotals.total_discounted),
			balance:          toNum(serverTotals.balance),
			running_balance:  processedData[processedData.length - 1]?.running_balance,
		};
	}, [processedData, serverTotals]);

    const finalData = summary ? [...processedData, summary] : processedData;

	// Mobile invoice-card list: status filtering + counts (client-side on the loaded page)
	const chUnpaid = processedData.filter(r => toNum(r.balance) > 0);
	const chPaid = processedData.filter(r => toNum(r.balance) <= 0);
	const chCounts = { all: processedData.length, unpaid: chUnpaid.length, paid: chPaid.length };
	const chCards = statusTab === 'unpaid' ? chUnpaid : statusTab === 'paid' ? chPaid : processedData;
	const chTotalPages = Math.max(1, Math.ceil((totalCount || 0) / (perPage || 10)));
	// Table view respects the active status tab (summary TOTAL row only on "All")
	const chTableData = statusTab === 'all' ? finalData : chCards;

	// Clear ALL filters (keep the selected customer) → fetches every record in the DB for that customer
	const clearFilters = () => {
		setFilterText(''); setStatusTab('all'); setPage(1);
		dispatch(setFromDate('')); dispatch(setToDate('')); dispatch(setOption([]));
	};
	// Shared "No records found" empty card (matches reference) with a Clear filters action
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

    const customSortFunction = (rows, selector, direction) => {
        const mainRows = rows.filter(r => !r.isSummary);
        const summaryRow = rows.find(r => r.isSummary);
        mainRows.sort((a, b) => {
            const aVal = selector(a);
            const bVal = selector(b);
            if (aVal < bVal) return direction === "asc" ? -1 : 1;
            if (aVal > bVal) return direction === "asc" ? 1 : -1;
            return 0;
        });
        return summaryRow ? [...mainRows, summaryRow] : mainRows;
    };

	const hs = {fontSize:'10.5px',fontWeight:'800',color:'#1f2937',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const summaryCell = (val) => <span style={{fontWeight:'800',fontSize:'13px',color:'#1e293b'}}>{fmt(val)}</span>;
	const numCell = (val, color) => { const n = toNum(val); return n !== 0 ? <span style={{fontWeight:'600',fontSize:'13px',color:color||'#374151',fontVariantNumeric:'tabular-nums'}}>{fmt(n)}</span> : <span style={{color:'#d1d5db',fontSize:'12px'}}>—</span>; };

	const paymentMethodBadge = (row) => {
		if (row.isSummary) return null;
		const methods = [];
		if (toNum(row.paid_by_cash) > 0) methods.push('Cash');
		if (toNum(row.paid_by_card) > 0) methods.push('Card');
		if (toNum(row.paid_by_bank) > 0) methods.push('Bank');
		if (toNum(row.paid_by_cheque) > 0) methods.push('Cheque');
		if (methods.length === 0) return <span style={{color:'#d1d5db',fontSize:'11px'}}>—</span>;
		return (
			<div style={{display:'flex',gap:'4px',flexWrap:'wrap'}}>
				{methods.map(m => <span key={m} style={{fontSize:'10px',fontWeight:'600',color:'#6b7280',background:'#f3f4f6',borderRadius:'4px',padding:'1px 6px'}}>{m}</span>)}
			</div>
		);
	};

	const statusBadge = (row) => {
		if (row.isSummary) return null;
		const balance = toNum(row.balance);
		const paid = toNum(row.total_paid);
		const amount = toNum(row.net_amount);
		const credit = toNum(row.credit_adj);
		if (amount <= 0 && balance <= 0) return <span style={{fontSize:'11px',fontWeight:'700',color:'#d1d5db'}}>—</span>;
		if (balance <= 0 && paid <= 0 && credit > 0) return <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'700',color:'#7c3aed',background:'#f5f3ff',border:'1px solid #c4b5fd',padding:'2px 8px',borderRadius:'20px',whiteSpace:'nowrap'}}><i className="fa fa-undo" style={{fontSize:'9px'}}></i>Returned</span>;
		if (balance <= 0) return <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'700',color:'#16a34a',background:'#f0fdf4',border:'1px solid #86efac',padding:'2px 8px',borderRadius:'20px'}}><i className="fa fa-check-circle" style={{fontSize:'9px'}}></i>Paid</span>;
		if (paid > 0 || credit > 0) return <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'700',color:'#f59e0b',background:'#fffbeb',border:'1px solid #fde68a',padding:'2px 8px',borderRadius:'20px'}}><i className="fa fa-clock-o" style={{fontSize:'9px'}}></i>Partial</span>;
		return <span style={{display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'700',color:'#ef4444',background:'#fef2f2',border:'1px solid #fecaca',padding:'2px 8px',borderRadius:'20px'}}><i className="fa fa-times-circle" style={{fontSize:'9px'}}></i>Unpaid</span>;
	};

	// Mobile card status pill — colored dot + label (matches reference)
	const chStatusPill = (row) => {
		const balance = toNum(row.balance), paid = toNum(row.total_paid), amount = toNum(row.net_amount), credit = toNum(row.credit_adj);
		let cfg;
		if (amount <= 0 && balance <= 0) return null;
		else if (balance <= 0 && paid <= 0 && credit > 0) cfg = { label: 'Returned', color: '#7c3aed', bg: '#f5f3ff', border: '#ddd6fe' };
		else if (balance <= 0) cfg = { label: 'Paid', color: '#16a34a', bg: '#f0fdf4', border: '#bbf7d0' };
		else if (paid > 0 || credit > 0) cfg = { label: 'Partial', color: '#d97706', bg: '#fffbeb', border: '#fde68a' };
		else cfg = { label: 'Unpaid', color: '#ef4444', bg: '#fef2f2', border: '#fecaca' };
		return <span style={{ display: 'inline-flex', alignItems: 'center', gap: '5px', fontSize: '11px', fontWeight: '700', color: cfg.color, background: cfg.bg, border: `1px solid ${cfg.border}`, padding: '3px 9px', borderRadius: '999px', whiteSpace: 'nowrap' }}><span style={{ width: '6px', height: '6px', borderRadius: '50%', background: cfg.color, flexShrink: 0 }}></span>{cfg.label}</span>;
	};

	const MONO = 'ui-monospace,SFMono-Regular,Menlo,monospace';
	const rightCell = (val, opts = {}) => {
		const n = toNum(val);
		const { isSummary, color = '#0f1115', summaryColor = color, zeroDash = true } = opts;
		return <div style={{width:'100%',textAlign:'left'}}>{isSummary
			? <span style={{fontWeight:'800',fontSize:'13px',color:summaryColor,fontVariantNumeric:'tabular-nums'}}>{fmt(val)}</span>
			: (n !== 0 || !zeroDash
				? <span style={{fontWeight:'800',fontSize:'13px',color,fontVariantNumeric:'tabular-nums'}}>{fmt(val)}</span>
				: <span style={{fontSize:'13px',fontWeight:'500',color:'#9ca3af',fontVariantNumeric:'tabular-nums'}}>£0.00</span>)
		}</div>;
	};

	const columns = [
		{ name: <span style={hs}>Sl.No</span>, selector: (_, idx) => idx + 1, sortable: false, width: '60px', grow: 0,
		  cell: (row, idx) => row.isSummary
			? <span style={{fontWeight:'800',fontSize:'12px',color:'#1e293b',letterSpacing:'0.4px'}}>TOTAL</span>
			: <span style={{fontSize:'13px',color:'#6b7280',fontWeight:'700'}}>{idx + 1}</span> },
		{ name: <span style={hs}>Date</span>, selector: row => row.created_at, sortable: true, minWidth: '100px', grow: 1,
		  cell: row => row.isSummary ? null : <span style={{fontSize:'13px',color:'#374151',whiteSpace:'nowrap'}}>{row.created_at}</span> },
		{ name: <span style={hs}>Invoice #</span>, selector: row => row.id, sortable: true, minWidth: '100px', grow: 0,
		  cell: row => row.isSummary
			? null
			: (row.id == 0
				? <span style={{background:'#fef2f2',color:'#ef4444',border:'1px solid #fecaca',borderRadius:'6px',padding:'3px 10px',fontSize:'11px',fontWeight:'700'}}>On Account</span>
				: <a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{textDecoration:'none'}}>
					<span style={{display:'inline-block',background:'#fff7f0',border:'1px solid #f6c9a8',borderRadius:'7px',padding:'4px 12px',fontWeight:'800',fontSize:'12.5px',color:'#F27420',fontFamily:MONO}}>#{row.id}</span>
				  </a>) },
		{ name: <span style={hs}>Credit Inv.</span>, selector: row => toNum(row.credit_inv), sortable: true, minWidth: '90px', grow: 1,
		  cell: row => rightCell(row.credit_inv, { isSummary: row.isSummary }) },
		{ name: <span style={hs}>Cash Inv.</span>, selector: row => toNum(row.cash_inv), sortable: true, minWidth: '80px', grow: 1,
		  cell: row => rightCell(row.cash_inv, { isSummary: row.isSummary }) },
		{ name: <span style={hs}>Paid</span>, selector: row => toNum(row.total_paid), sortable: true, minWidth: '70px', grow: 1,
		  cell: row => rightCell(row.total_paid, { isSummary: row.isSummary }) },
		{ name: <span style={hs}>Credit /Adj</span>, selector: row => toNum(row.credit_adj), sortable: true, minWidth: '90px', grow: 1,
		  cell: row => rightCell(row.credit_adj, { isSummary: row.isSummary }) },
		{ name: <span style={hs}>Discount /Adj</span>, selector: row => toNum(row.total_discounted), sortable: true, minWidth: '100px', grow: 1,
		  cell: row => rightCell(row.total_discounted, { isSummary: row.isSummary }) },
		{ name: <span style={hs}>Balance</span>, selector: row => toNum(row.balance), sortable: true, minWidth: '85px', grow: 1,
		  cell: row => rightCell(row.balance, { isSummary: row.isSummary, color:'#ef4444' }) },
		{ name: <span style={hs}>Running Balance</span>, selector: row => toNum(row.running_balance), sortable: false, minWidth: '110px', grow: 1,
		  cell: row => rightCell(row.running_balance, { isSummary: row.isSummary, color:'#0f1115', zeroDash: false }) },
	];

	const mobileColumns = [
		{
			name: <span style={hs}>Invoice</span>,
			selector: row => row.id,
			grow: 1,
			cell: row => row.isSummary
				? <span style={{fontWeight:'800',fontSize:'13px',color:'#1e293b',background:'#f1f5f9',padding:'4px 12px',borderRadius:'6px'}}>TOTAL</span>
				: (
					<div style={{padding:'3px 0'}}>
						{row.id == 0
							? <span style={{background:'#fef2f2',color:'#ef4444',border:'1px solid #fecaca',borderRadius:'6px',padding:'3px 10px',fontSize:'11px',fontWeight:'700'}}>On Account</span>
							: <a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{textDecoration:'none'}}>
								<span style={{background:'#FFF7ED',border:'1px solid #fed7aa',borderRadius:'6px',padding:'3px 9px',fontWeight:'700',fontSize:'12px',color:'#F27420'}}>#{row.id}</span>
							  </a>
						}
						<div style={{fontSize:'11px',color:'#94a3b8',marginTop:'4px'}}>{row.created_at}</div>
						<div style={{fontSize:'11px',color:'#94a3b8',marginTop:'1px'}}>Amt: {fmt(toNum(row.net_amount))}</div>
					</div>
				)
		},
		{
			name: <span style={hs}>Pending</span>,
			selector: row => toNum(row.balance),
			width: '100px',
			cell: row => <div style={{width:'100%',textAlign:'right'}}>{row.isSummary
				? <span style={{fontWeight:'800',fontSize:'13px',color:'#ef4444'}}>{fmt(row.balance)}</span>
				: (toNum(row.balance) > 0
					? <span style={{fontWeight:'700',fontSize:'13px',color:'#ef4444',fontVariantNumeric:'tabular-nums'}}>{fmt(row.balance)}</span>
					: <span style={{color:'#d1d5db',fontSize:'12px'}}>—</span>)
			}</div>
		},
		{
			name: <span style={{...hs,width:'100%',textAlign:'center',display:'block'}}>Status</span>,
			selector: row => toNum(row.balance),
			width: '80px',
			cell: row => <div style={{width:'100%',textAlign:'center'}}>{statusBadge(row)}</div>
		},
	];

	const conditionalRowStyles = [
		{ when: row => row.isSummary, style: { background: '#fafbfc', borderTop: '2px solid #eef2f7', fontWeight: '800', minHeight: '52px' } },
	];

	const mergedStyles = useMemo(() => ({
        ...customStyles,
        headRow: { style: { backgroundColor: '#fafbfc', borderBottomColor: '#eef2f7', borderBottomWidth: '1px', minHeight: '42px' } },
        headCells: { style: { fontSize: '10.5px', fontWeight: '800', color: '#1f2937', letterSpacing: '0.7px', textTransform: 'uppercase', padding: '10px 8px', whiteSpace: 'nowrap' } },
        rows: { style: { borderBottomColor: '#f0f0f2', borderBottomWidth: '1px', fontSize: '13px', minHeight: '56px', transition: 'background 0.12s' }, highlightOnHoverStyle: { backgroundColor: '#fffaf5', borderBottomColor: '#f0f0f2' } },
        cells: { style: { fontSize: '13px', padding: '10px 8px', display: 'flex', alignItems: 'center', whiteSpace: 'nowrap', color: '#374151' } },
        pagination: { style: { borderTopColor: '#f0f0f2', borderTopWidth: '1px', fontSize: '13px' } },
    }), [customStyles]);

	useDropdownFix();

	const totalAmount = summary ? toNum(summary.total) : 0;
	const totalPaid = summary ? toNum(summary.total_paid) : 0;
	const totalReturns = summary ? toNum(summary.credit_adj) : 0;
	const totalPending = summary ? toNum(summary.balance) : 0;
	const paidPercent = totalAmount > 0 ? Math.round((totalPaid / totalAmount) * 100) : 0;

    return (
        <div style={{ marginTop: '0' }}>
			{/* Summary Cards */}
			{currentCustomerInfo && (
				isMobile ? (
					/* Mobile: financial summary card — matches reference exactly */
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
							{[{ label: 'Paid', value: fmt(totalPaid), color: '#16a34a' }, { label: 'Pending', value: fmt(totalPending), color: '#ea580c' }, { label: 'Invoices', value: String(processedData.length), color: '#0f172a' }].map((s, i) => (
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
					/* Desktop: grid cards — Stock Check style */
					<div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: '10px', padding: '14px 18px', background: '#fff', borderLeft: '1px solid #eaecf2', borderRight: '1px solid #eaecf2' }}>
						{[
							{label:'Total Sales', value:fmt(totalAmount), icon:'fa-shopping-bag', color:'#3b82f6', light:'#eff6ff'},
							{label:'Paid', value:fmt(totalPaid), icon:'fa-check-circle', color:'#16a34a', light:'#f0fdf4'},
							{label:'Returns', value:fmt(totalReturns), icon:'fa-undo', color:'#8b5cf6', light:'#f5f3ff'},
							{label:'Pending', value:fmt(totalPending), icon:'fa-clock-o', color:'#dc2626', light:'#fef2f2'},
						].map(c => (
							<div key={c.label} style={{display:'flex',alignItems:'center',gap:'13px',padding:'16px 18px',borderRadius:'12px',background:'#fff',border:'1px solid #edf2f7',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
								<div style={{width:'42px',height:'42px',borderRadius:'50%',background:c.light,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
									<i className={'fa '+c.icon} style={{color:c.color,fontSize:'16px'}}/>
								</div>
								<div>
									<div style={{fontSize:'22px',fontWeight:'800',color:'#1a2332',lineHeight:1}}>{c.value}</div>
									<div style={{fontSize:'10px',fontWeight:'600',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',marginTop:'4px'}}>{c.label}</div>
								</div>
							</div>
						))}
					</div>
				)
			)}

			{/* Invoice list — mobile: cards; desktop/tablet: table */}
			{isMobile ? (
				currentCustomerInfo ? (
				<div>
					{/* Header: title + Card/Table view toggle on the right end */}
					<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', flexWrap: 'wrap', marginBottom: '12px', padding: '0 2px' }}>
						<h3 style={{ margin: 0, fontSize: '18px', fontWeight: '800', color: '#0f1115' }}>Invoice History</h3>
						<div style={{ display: 'flex', gap: '8px' }}>
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
					{/* Status tabs (underline style — matches Products) + sort — shown in both card & table views */}
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
					{/* Content: table view → full scrollable table; card view → cards/loading/empty */}
					{(isLoading && processedData.length === 0) ? (
						<div style={{ background: '#fff', borderRadius: '14px', border: '1px solid #eaecf2' }}><SpecTableLoading label="Loading history…" /></div>
					) : (viewMode === 'table' ? chTableData : chCards).length === 0 ? (
						chEmptyCard
					) : viewMode === 'table' ? (
						<div style={{ borderRadius: '14px', border: '1px solid #eaecf2', boxShadow: '0 1px 6px rgba(0,0,0,0.05)', background: '#fff', overflow: 'hidden' }}>
							<div style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
								<div style={{ minWidth: '950px' }}>
									<DataTable columns={columns} data={chTableData} responsive={false} highlightOnHover customStyles={mergedStyles} conditionalRowStyles={conditionalRowStyles} pagination paginationServer paginationTotalRows={totalCount} paginationPerPage={perPage} paginationRowsPerPageOptions={[10, 25, 50, 100]} paginationDefaultPage={page} paginationComponent={SpecPagination} onChangePage={(p) => setPage(p)} onChangeRowsPerPage={(np, npg) => { setPerPage(np); setPage(npg); }} persistTableHead progressPending={isLoading && historyData.length === 0} progressComponent={<SpecTableLoading label="Loading history…" />} noDataComponent={<div style={{ padding: '40px 24px', textAlign: 'center', color: '#9ca3af', fontSize: '14px', fontWeight: '600' }}>No invoices found</div>} />
								</div>
							</div>
						</div>
					) : (
						<div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
							{chCards.map(row => {
								const bal = toNum(row.balance);
								const open = expandedInv === row.id;
								const payEntries = [];
								if (toNum(row.paid_by_cash) > 0) payEntries.push(['Cash', toNum(row.paid_by_cash)]);
								if (toNum(row.paid_by_card) > 0) payEntries.push(['Card', toNum(row.paid_by_card)]);
								if (toNum(row.paid_by_bank) > 0) payEntries.push(['Bank Transfer', toNum(row.paid_by_bank)]);
								if (toNum(row.paid_by_cheque) > 0) payEntries.push(['Cheque', toNum(row.paid_by_cheque)]);
								const payModeColors = { 'Cash': { bg: '#f0fdf4', color: '#15803d', border: '#86efac', icon: 'fa-money' }, 'Card': { bg: '#eff6ff', color: '#1d4ed8', border: '#93c5fd', icon: 'fa-credit-card' }, 'Cheque': { bg: '#f5f3ff', color: '#6d28d9', border: '#c4b5fd', icon: 'fa-file-text-o' }, 'Bank Transfer': { bg: '#ecfeff', color: '#0e7490', border: '#67e8f9', icon: 'fa-university' } };
								return (
									<div key={row.id} style={{ display: 'flex', borderRadius: '14px', border: '1px solid #eaecf2', overflow: 'hidden', background: '#fff', boxShadow: '0 1px 4px rgba(0,0,0,0.05)' }}>
										<div style={{ width: '4px', flexShrink: 0, background: 'linear-gradient(180deg, rgb(234, 88, 12), #ea580c)' }}></div>
										<div style={{ flex: 1, padding: '12px 12px 10px', minWidth: 0 }}>
											<div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '8px' }}>
												<div style={{ minWidth: 0 }}>
													<div style={{ fontSize: '11px', color: 'rgb(234, 88, 12)', fontWeight: '700', marginBottom: '4px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', display: 'flex', alignItems: 'center', gap: '8px' }}>
														<span>{row.id == 0 ? 'On Account' : '#' + row.id}</span>
														{row.created_at ? <span style={{ fontWeight: '500', color: '#6b7280' }}>{row.created_at}</span> : ''}
													</div>
													{currentCustomerInfo?.name ? <div style={{ fontWeight: '700', color: '#1e293b', fontSize: '13px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', marginBottom: '6px' }}>{currentCustomerInfo.name}</div> : null}
													<div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'center', marginTop: '2px' }}>
														{statusBadge(row)}
														{payEntries.length === 0 && bal > 0 && <span style={{ fontSize: '11px', fontWeight: '700', color: '#dc2626', whiteSpace: 'nowrap' }}>No Payment</span>}
														{payEntries.map(([mode, amt2]) => { const s2 = payModeColors[mode] || { bg: '#f8fafc', color: '#475569', border: '#e2e8f0', icon: 'fa-circle' }; return <span key={mode} style={{ display: 'inline-flex', alignItems: 'center', gap: '4px', background: s2.bg, border: '1px solid ' + s2.border, borderRadius: '6px', padding: '2px 7px', fontSize: '10px', fontWeight: '600', color: s2.color, whiteSpace: 'nowrap' }}><i className={'fa ' + s2.icon} style={{ fontSize: '9px' }}></i>{mode} {amt2.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>; })}
													</div>
												</div>
												<div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: '6px', flexShrink: 0 }}>
													<span style={{ background: '#FFF7ED', border: '1px solid #fed7aa', borderRadius: '8px', padding: '3px 10px', fontWeight: '800', color: 'rgb(234, 88, 12)', fontSize: '13px', whiteSpace: 'nowrap' }}>{fmt(row.net_amount)}</span>
													<button type="button" onClick={() => setExpandedInv(open ? null : row.id)} style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '4px', display: 'flex', alignItems: 'center', justifyContent: 'center', outline: 'none' }}>
														<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke={open ? 'rgb(234, 88, 12)' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ transform: open ? 'rotate(180deg)' : 'none', transition: 'all 0.2s' }}><path d="M6 9l6 6 6-6"/></svg>
													</button>
												</div>
											</div>
											{open && (
												<div style={{ marginTop: '10px', paddingTop: '10px', borderTop: '1px solid #f1f5f9', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
													{[{ l: 'Invoice Amount', v: fmt(row.net_amount), c: '#0f1115' }, { l: 'Paid', v: fmt(row.total_paid), c: '#16a34a' }, { l: 'Credit / Adj', v: fmt(row.credit_adj), c: '#7c3aed' }, { l: 'Balance', v: fmt(row.balance), c: bal > 0 ? '#ea580c' : '#16a34a' }].map(d => (
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
							{totalCount > perPage && (
								<div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '6px 2px 0', flexWrap: 'wrap', gap: '8px' }}>
									<span style={{ fontSize: '12px', color: '#6b7280', fontWeight: '500' }}>Page {page} of {chTotalPages}</span>
									<div style={{ display: 'flex', gap: '6px' }}>
										<button type="button" disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))} style={{ height: '34px', padding: '0 14px', borderRadius: '9px', border: '1px solid #eaecf2', background: '#fff', color: page <= 1 ? '#c8c8cf' : '#374151', fontWeight: '700', fontSize: '13px', cursor: page <= 1 ? 'not-allowed' : 'pointer' }}>Prev</button>
										<button type="button" disabled={page >= chTotalPages} onClick={() => setPage(p => p + 1)} style={{ height: '34px', padding: '0 14px', borderRadius: '9px', border: '1px solid #eaecf2', background: '#fff', color: page >= chTotalPages ? '#c8c8cf' : '#374151', fontWeight: '700', fontSize: '13px', cursor: page >= chTotalPages ? 'not-allowed' : 'pointer' }}>Next</button>
									</div>
								</div>
							)}
						</div>
					)}
				</div>
				) : (
				<div style={{ background: '#fff', border: '1px solid #eaecf2', borderRadius: '14px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', padding: '40px 24px', textAlign: 'center' }}>
					<span style={{ width: '64px', height: '64px', borderRadius: '50%', background: '#fff3e9', color: '#F27420', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', marginBottom: '6px' }}>
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
					</span>
					<div style={{ fontSize: '15px', fontWeight: '800', color: '#0f1115' }}>Select a customer</div>
					<div style={{ fontSize: '13px', color: '#6b7280', marginTop: '4px', maxWidth: '320px', marginInline: 'auto', lineHeight: 1.5 }}>Pick a customer above to see their full transaction history and balance.</div>
				</div>
				)
			) : (
			<div style={{ borderRadius: isMobile ? '14px' : '0 0 16px 16px', border: '1px solid #eaecf2', borderTop: isMobile ? '1px solid #eaecf2' : 'none', boxShadow: isMobile ? '0 1px 4px rgba(0,0,0,0.06)' : '0 4px 16px rgba(0,0,0,0.04)', background: '#fff', overflow: 'hidden' }}>
				{currentCustomerInfo && (
					<div style={{ display: 'flex', alignItems: 'center', gap: '14px', padding: '18px 22px 14px', flexWrap: 'wrap' }}>
						<h3 style={{ margin: 0, fontSize: '16px', fontWeight: '800', color: '#0f1115' }}>Invoice History</h3>
						<div style={{ flex: '1 1 0%' }}></div>
						<div style={{ position: 'relative', width: isMobile ? '100%' : '280px' }}>
							<span style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', display: 'flex', alignItems: 'center' }}>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
							</span>
							<input
								type="text"
								placeholder="Search invoices..."
								value={filterText}
								onChange={e => setFilterText(e.target.value)}
								style={{ width: '100%', height: '34px', padding: '0 12px 0 34px', borderRadius: '99px', border: '1px solid #e8e8ec', background: '#fafafb', fontSize: '12.5px', color: '#0f1115', outline: 'none', fontFamily: 'inherit', boxSizing: 'border-box' }}
								onFocus={e => e.target.style.borderColor = '#f97316'}
								onBlur={e => e.target.style.borderColor = '#e8e8ec'}
							/>
{filterText && <button type="button" onClick={() => {setFilterText('')}} style={{position:'absolute',right:'12px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
						</div>
					</div>
				)}
				<div style={{ overflowX: 'auto', overflowY: 'visible', position: 'relative' }}>
					<div className="ch-scroll-inner" style={{ minWidth: ((isMobile || isTablet) && finalData.length > 0) ? '950px' : 'auto' }}>
						<DataTable
							columns={columns}
							data={finalData}
							pagination
							paginationServer
							paginationTotalRows={totalCount}
							paginationPerPage={perPage}
							paginationRowsPerPageOptions={[10, 25, 50, 100]}
							paginationDefaultPage={page}
							paginationComponent={SpecPagination}
							onChangePage={(p) => setPage(p)}
							onChangeRowsPerPage={(newPerPage, newPage) => { setPerPage(newPerPage); setPage(newPage); }}
							sortServer
							onSort={(col, dir) => {
								const map = { 'Date': 'created_at', 'Invoice #': 'id', 'Credit Inv.': 'net_amount', 'Cash Inv.': 'net_amount', 'Paid': 'total_paid', 'Balance': 'balance' };
								const label = (col && col.name && col.name.props && col.name.props.children) || 'created_at';
								setSortBy(map[label] || 'created_at');
								setSortDir(dir);
							}}
							highlightOnHover
							customStyles={mergedStyles}
							conditionalRowStyles={conditionalRowStyles}
							progressPending={isLoading && historyData.length === 0}
							progressComponent={<SpecTableLoading label="Loading history…" />}
							noDataComponent={
								<div style={{ padding: '48px 24px', width: '100%' }}>
									<div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '8px' }}>
										<span style={{ width: '64px', height: '64px', borderRadius: '50%', background: '#fff3e9', color: '#F27420', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '4px' }}>
											{currentCustomerInfo
												? <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
												: <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>}
										</span>
										<div style={{ fontSize: '15px', fontWeight: '800', color: '#0f1115' }}>
											{currentCustomerInfo ? 'No history found' : 'Select a customer'}
										</div>
										<div style={{ fontSize: '13px', color: '#6b7280', textAlign: 'center', maxWidth: '320px' }}>
											{currentCustomerInfo
												? 'No transactions match the current date range or filters.'
												: 'Pick a customer above to see their full transaction history and balance.'}
										</div>
									</div>
								</div>
							}
						/>
					</div>
					{isLoading && historyData.length > 0 && (
						<div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
							<div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#ea580c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
								<i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
								<span>Loading…</span>
							</div>
						</div>
					)}
				</div>
				{(isMobile || isTablet) && finalData.length > 0 && (
					<div style={{ padding: '0 12px 10px' }}>
						<input type="range" min="0" max="100" defaultValue="0"
							className="ph-range-scroll"
							onChange={(e) => {
								const inner = document.querySelector('.ch-scroll-inner');
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


export default function CustomerHistoryApp(props) {
	const dispatch = useDispatch();

    return (
	<>
	<style>{`
		.ph-range-scroll { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 10px; background: #f0f0f0; outline: none; }
		.ph-range-scroll::-webkit-slider-thumb { -webkit-appearance: none; width: 50px; height: 6px; border-radius: 10px; background: #F27420; cursor: pointer; }
		.ph-range-scroll::-moz-range-thumb { width: 50px; height: 6px; border-radius: 10px; background: #F27420; cursor: pointer; border: none; }
		@keyframes drpFadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
		@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
		.drp-preset-chip::-webkit-scrollbar { display: none; }
		@media (max-width: 767px) {
			.ch-filter-panel { flex-direction: column !important; align-items: stretch !important; gap: 10px !important; }
			.ch-filter-panel > div { min-width: unset !important; width: 100% !important; flex: unset !important; }
			.ch-filter-panel > div > button { width: 100% !important; justify-content: flex-start !important; min-width: unset !important; }
		}
	`}</style>
	<div className="row">
		<div className="col-12">
			<UnifiedBar apiUrl={props.customerListApi} printApi={props.printApi} excelApi={props.excelApi} emailApi={props.historyEmailApi} historyApi={props.historyListApi} initialCustomerId={props.customerId} />
		</div>
		<div className="col-12" style={{ marginBottom: '70px' }}>
			<List {...props} />
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('customer-history-app')) {
    const id = "customer-history-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<CustomerHistoryApp {...props} />
		</Provider>
    );
}

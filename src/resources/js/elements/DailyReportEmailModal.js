import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { toast } from 'react-toastify';

export default function DailyReportEmailModal({ open, onClose, apiUrl, listApi, reportTitle, fromDate, toDate, customerId, supplierId }) {
	const [toEmail, setToEmail] = useState('');
	const [subject, setSubject] = useState('');
	const [message, setMessage] = useState('');
	const [sending, setSending] = useState(false);
	const [errors, setErrors] = useState({});
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

	useEffect(() => {
		if (open) {
			setToEmail('');
			setSubject(`${reportTitle} — ${periodLabel()}`);
			setMessage(`Dear team,\n\nPlease find attached the ${reportTitle.toLowerCase()} for the period ${periodLabel()}.\n\nKindly review the attached Excel report for full details.\n\nThank you.`);
			setErrors({});
			setSending(false);
			// Pre-count invoices in the period so user sees "0 invoices" early and can't send when empty.
			setInvoiceCount(0);
			if (listApi) {
				setCountLoading(true);
				const body = {
					start_date: fromDate || '',
					end_date: toDate || '',
				};
				if (customerId) body.customer_id = customerId;
				if (supplierId) body.supplier_id = supplierId;
				axios.post(listApi, body)
					.then(res => {
						const arr = res.data?.payload || res.data?.data || res.data || [];
						const count = Array.isArray(arr) ? arr.length : (arr.count || 0);
						setInvoiceCount(count);
					})
					.catch(() => setInvoiceCount(0))
					.finally(() => setCountLoading(false));
			}
		}
	}, [open]);

	if (!open) return null;

	const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	const toEmailError = !toEmail.trim()
		? 'Recipient email is required'
		: (!emailRe.test(toEmail.trim()) ? 'Enter a valid email address' : '');
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
		const body = {
			start_date: fromDate || '',
			end_date: toDate || '',
			to_email: toEmail.trim(),
			subject: subject.trim(),
			message: message.trim(),
		};
		if (customerId) body.customer_id = customerId;
		if (supplierId) body.supplier_id = supplierId;

		axios.post(apiUrl, body)
			.then(res => {
				if (res.data && res.data.success === true) {
					toast.success(res.data.payload || 'Report emailed successfully');
					onClose();
				} else {
					toast.error((res.data && res.data.payload) || 'Could not send the email.');
				}
			})
			.catch(err => toast.error(err.response?.data?.payload || 'Something went wrong while sending.'))
			.finally(() => setSending(false));
	};

	const label = { fontSize: '11px', fontWeight: '700', color: '#6b7280', letterSpacing: '0.4px', textTransform: 'uppercase', marginBottom: '6px', display: 'block' };
	const inputBase = { width: '100%', height: '40px', borderRadius: '9px', border: '1.5px solid #e8e8ec', padding: '0 12px', fontSize: '13px', color: '#0f1115', outline: 'none', fontFamily: 'inherit', boxSizing: 'border-box' };
	const errText = (m) => m ? <span style={{ fontSize: '11px', color: '#dc2626', fontWeight: '600', marginTop: '4px', display: 'block' }}>{m}</span> : null;

	return (
		<div onClick={onClose} style={{ position:'fixed', inset:0, background:'rgba(15,17,21,0.45)', zIndex:9000, display:'flex', alignItems:'flex-start', justifyContent:'center', padding:'70px 16px 24px', overflowY:'auto' }}>
			<div onClick={e => e.stopPropagation()} style={{ background:'#fff', borderRadius:'14px', width:'100%', maxWidth:'460px', boxShadow:'0 24px 60px -12px rgba(15,17,21,0.4)', overflow:'hidden' }}>
				<div style={{ display:'flex', alignItems:'center', gap:'12px', padding:'18px 22px', borderBottom:'1px solid #eeeeef' }}>
					<span style={{ width:'40px', height:'40px', borderRadius:'10px', background:'#fff7ed', border:'1px solid #fed7aa', color:'#ea580c', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
					</span>
					<div style={{ flex:1, minWidth:0 }}>
						<h3 style={{ margin:0, fontSize:'15.5px', fontWeight:'800', color:'#0f1115' }}>Email {reportTitle}</h3>
						<p style={{ margin:'2px 0 0', fontSize:'12px', color:'#6b7280' }}>Send the report as an Excel attachment</p>
					</div>
					<button onClick={onClose} style={{ width:'30px', height:'30px', borderRadius:'8px', border:'1px solid #e8e8ec', background:'#fff', color:'#6b7280', cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
				</div>

				<div style={{ padding:'20px 22px', maxHeight:'60vh', overflowY:'auto' }}>
					<div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', background:'#fafafb', border:'1px solid #e8e8ec', borderRadius:'9px', padding:'10px 13px', marginBottom:'16px' }}>
						<div>
							<div style={{ fontSize:'10px', fontWeight:'700', color:'#9ca3af', textTransform:'uppercase', letterSpacing:'0.4px' }}>Report Period</div>
							<div style={{ fontSize:'13px', fontWeight:'700', color:'#0f1115', marginTop:'2px' }}>{periodLabel()}</div>
						</div>
						{listApi && (
							countLoading
								? <span style={{ fontSize:'11.5px', fontWeight:'700', color:'#6b7280', background:'#f4f4f6', border:'1px solid #e8e8ec', borderRadius:'99px', padding:'4px 11px' }}>Loading…</span>
								: <span style={{ fontSize:'11.5px', fontWeight:'700', color: invoiceCount > 0 ? '#15803d' : '#b91c1c', background: invoiceCount > 0 ? '#e8f8ee' : '#fef2f2', border:`1px solid ${invoiceCount > 0 ? '#bde5c9' : '#f8d2d2'}`, borderRadius:'99px', padding:'4px 11px' }}>
									{invoiceCount} invoice{invoiceCount === 1 ? '' : 's'}
								</span>
						)}
					</div>

					{listApi && !countLoading && invoiceCount === 0 && (
						<div style={{ background:'#fffbeb', border:'1px solid #f5d98c', borderRadius:'9px', padding:'10px 13px', marginBottom:'16px', fontSize:'12px', color:'#b45309', fontWeight:'600' }}>
							No invoices found in this period — there is nothing to send.
						</div>
					)}

					<div style={{ marginBottom:'14px' }}>
						<label style={label}>To</label>
						<input type="email" value={toEmail} onChange={e => setToEmail(e.target.value)} placeholder="recipient@email.com"
							style={{ ...inputBase, borderColor: toEmailValid ? '#e8e8ec' : '#dc2626' }} />
						{errText(toEmailError)}
					</div>

					<div style={{ marginBottom:'14px' }}>
						<label style={label}>Subject</label>
						<input type="text" value={subject} onChange={e => setSubject(e.target.value)}
							style={{ ...inputBase, borderColor: errors.subject ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.subject)}
					</div>

					<div>
						<label style={label}>Message</label>
						<textarea value={message} onChange={e => setMessage(e.target.value)} rows={6}
							style={{ ...inputBase, height:'auto', padding:'10px 12px', resize:'vertical', lineHeight:'1.5', borderColor: errors.message ? '#dc2626' : '#e8e8ec' }} />
						{errText(errors.message)}
					</div>
				</div>

				<div style={{ display:'flex', justifyContent:'flex-end', gap:'10px', padding:'14px 22px', borderTop:'1px solid #eeeeef', background:'#fafafb' }}>
					<button onClick={onClose} disabled={sending}
						style={{ height:'40px', padding:'0 18px', borderRadius:'9px', border:'1.5px solid #e8e8ec', background:'#fff', color:'#6b7280', fontWeight:'700', fontSize:'13px', cursor: sending ? 'not-allowed' : 'pointer' }}>
						Cancel
					</button>
					<button onClick={handleSend} disabled={sending || countLoading || (listApi && invoiceCount === 0) || !toEmailValid}
						style={{ height:'40px', padding:'0 20px', borderRadius:'9px', border:'none', background:(sending || countLoading || (listApi && invoiceCount === 0) || !toEmailValid) ? '#fdba74' : '#f97316', color:'#fff', fontWeight:'700', fontSize:'13px', cursor:(sending || countLoading || (listApi && invoiceCount === 0) || !toEmailValid) ? 'not-allowed' : 'pointer', display:'inline-flex', alignItems:'center', gap:'8px' }}>
						{sending
							? <><i className="fa fa-spinner fa-spin" style={{fontSize:'12px'}}></i> Sending…</>
							: <><i className="fa fa-paper-plane" style={{fontSize:'12px'}}></i> Send Email</>
						}
					</button>
				</div>
			</div>
		</div>
	);
}

import React, { useEffect, useRef, useState, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import Form from 'react-bootstrap/Form';
import Select, { components as rsComponents } from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import axios from "axios";
import { useSelector, useDispatch } from 'react-redux'
import SalesService from "./../services/SalesService";
import { getIn, Formik, Field, useField, useFormik }
    from 'formik';
import Button from 'react-bootstrap/Button';
import { ToastContainer, toast } from 'react-toastify';
import { Modal } from 'react-bootstrap';
import * as Yup from "yup";
import { compareAsc, format } from 'date-fns';
import dateFormat from 'dateformat';
import {AlertProvider, useAlert } from "./../hooks/AlertContext";
import AddStock from "./../elements/AddStock";
import AddProduct from "./../elements/AddProduct";
import { formatTwoDecimal, parseErrorMessage } from './../hooks/utils';
import { useWindowSize } from "./../hooks/useWindowSize";
import CustomerInvoicePaymentsPopup from "./../elements/CustomerInvoicePaymentsPopup"
import { fixedSelectStyles } from "./../utils/selectStyles";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import EmailInvoiceModal from "./../elements/EmailInvoiceModal";

function _UnusedEmailInvoiceModal({ open, onClose, apiUrl, invoiceId, invoiceNumber, partyLabel, partyName, partyEmail, invoiceDate, totalText }) {
	const [toEmail, setToEmail] = useState('');
	const [ccSelf, setCcSelf] = useState(false);
	const [ccEmail, setCcEmail] = useState('');
	const [subject, setSubject] = useState('');
	const [message, setMessage] = useState('');
	const [sending, setSending] = useState(false);
	const [errors, setErrors] = useState({});

	useEffect(() => {
		if (open) {
			setToEmail(partyEmail || '');
			setCcSelf(false);
			setCcEmail('');
			setSubject(`Invoice #${invoiceNumber || invoiceId} — ${partyName || ''}`);
			setMessage(`Dear ${partyName || partyLabel},\n\nPlease find attached invoice #${invoiceNumber || invoiceId} dated ${invoiceDate || ''}.\n\nKindly review and let us know if you have any questions.\n\nThank you.`);
			setErrors({});
			setSending(false);
		}
	}, [open]);

	if (!open) return null;

	const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	const toEmailError = !toEmail.trim()
		? 'Recipient email is required'
		: (!emailRe.test(toEmail.trim()) ? 'Enter a valid email address' : '');
	const ccEmailError = (ccSelf && ccEmail.trim() && !emailRe.test(ccEmail.trim()))
		? 'Enter a valid CC email' : '';
	const toEmailValid = toEmailError === '';

	const validate = () => {
		const e = {};
		if (toEmailError) e.toEmail = toEmailError;
		if (ccEmailError) e.ccEmail = ccEmailError;
		if (!subject.trim()) e.subject = 'Subject is required';
		if (!message.trim()) e.message = 'Message is required';
		setErrors(e);
		return Object.keys(e).length === 0;
	};

	const handleSend = () => {
		if (sending) return;
		if (!validate()) return;
		setSending(true);
		axios.post(apiUrl, {
			to_email: toEmail.trim(),
			cc_email: ccSelf && ccEmail.trim() ? ccEmail.trim() : '',
			subject: subject.trim(),
			message: message.trim(),
		})
		.then(res => {
			if (res.data && res.data.success === true) {
				toast.success(res.data.payload || 'Invoice emailed successfully');
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
					<span style={{ width:'40px', height:'40px', borderRadius:'10px', background:'#fff7ed', border:'1px solid #fed7aa', color:'#c2410c', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
					</span>
					<div style={{ flex:1, minWidth:0 }}>
						<h3 style={{ margin:0, fontSize:'15.5px', fontWeight:'800', color:'#0f1115' }}>Email Invoice</h3>
						<p style={{ margin:'2px 0 0', fontSize:'12px', color:'#6b7280' }}>Send invoice #{invoiceNumber || invoiceId} as a PDF attachment</p>
					</div>
					<button onClick={onClose} style={{ width:'30px', height:'30px', borderRadius:'8px', border:'1px solid #e8e8ec', background:'#fff', color:'#6b7280', cursor:'pointer', display:'flex', alignItems:'center', justifyContent:'center', flexShrink:0 }}>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</button>
				</div>

				<div style={{ padding:'20px 22px', maxHeight:'60vh', overflowY:'auto' }}>
					<div style={{ display:'flex', alignItems:'center', justifyContent:'space-between', background:'#fafafb', border:'1px solid #e8e8ec', borderRadius:'9px', padding:'10px 13px', marginBottom:'16px' }}>
						<div>
							<div style={{ fontSize:'10px', fontWeight:'700', color:'#9ca3af', textTransform:'uppercase', letterSpacing:'0.4px' }}>{partyLabel}</div>
							<div style={{ fontSize:'13px', fontWeight:'700', color:'#0f1115', marginTop:'2px' }}>{partyName || '—'}</div>
						</div>
						{totalText && <span style={{ fontSize:'12.5px', fontWeight:'700', color:'#b91c1c', background:'#fef2f2', border:'1px solid #f8d2d2', borderRadius:'99px', padding:'4px 11px' }}>{totalText}</span>}
					</div>

					<div style={{ marginBottom:'14px' }}>
						<label style={label}>To</label>
						<input type="email" value={toEmail} onChange={e => setToEmail(e.target.value)} placeholder="recipient@email.com"
							style={{ ...inputBase, borderColor: toEmailValid ? '#e8e8ec' : '#dc2626' }} />
						{errText(toEmailError)}
					</div>

					<div style={{ marginBottom:'14px' }}>
						<label style={{ display:'flex', alignItems:'center', gap:'8px', cursor:'pointer', fontSize:'12.5px', color:'#374151', fontWeight:'600' }}>
							<input type="checkbox" checked={ccSelf} onChange={e => setCcSelf(e.target.checked)}
								style={{ width:'15px', height:'15px', accentColor:'#c2410c', cursor:'pointer' }} />
							Send a copy (CC) to another email
						</label>
						{ccSelf && (
							<div style={{ marginTop:'8px' }}>
								<input type="email" value={ccEmail} onChange={e => setCcEmail(e.target.value)} placeholder="cc@email.com"
									style={{ ...inputBase, borderColor: ccEmailError ? '#dc2626' : '#e8e8ec' }} />
								{errText(ccEmailError)}
							</div>
						)}
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
					<button onClick={handleSend} disabled={sending || !toEmailValid || !!ccEmailError}
						style={{ height:'40px', padding:'0 20px', borderRadius:'9px', border:'none', background:(sending || !toEmailValid || !!ccEmailError) ? '#fdba74' : 'rgb(234, 88, 12)', color:'#fff', fontWeight:'700', fontSize:'13px', cursor:(sending || !toEmailValid || !!ccEmailError) ? 'not-allowed' : 'pointer', display:'inline-flex', alignItems:'center', gap:'8px' }}>
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

export default function CustomerInvoiceApp(props) {
    // const [rowsData, setRowsData] = useState([{
    //     product: '',
    //     quantity: '',
    //     price: '',
    //     totalPrice: '',
    //     fieldToggle: '',
    //     invoiceproductid: 0,
    // }]);
	const { showAlert } = useAlert();
	const { width } = useWindowSize();
	const [forceDesktop, setForceDesktop] = useState(() => localStorage.getItem('ts_invoice_view') === 'on');
	const effectiveWidth = forceDesktop ? 1200 : width;
	const [showSuppliers, setShowSuppliers] = useState(props.showSuppliers === '1');
	const [savingToggle, setSavingToggle] = useState(false);

	const handleSupplierToggle = () => {
		if (savingToggle) return;
		const newVal = !showSuppliers;
		setShowSuppliers(newVal);
		setSavingToggle(true);
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
		axios.post(props.toggleApi, {
			_token: csrfToken,
			show_suppliers: newVal ? 1 : 0
		}).catch(() => {
			setShowSuppliers(!newVal);
		}).finally(() => setSavingToggle(false));
	};
    const [rowsData, setRowsData] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	
	const [emailsend, setEmailsend] = useState(0);
	const [downloading, setDownloading] = useState(false);
	const [printing, setPrinting] = useState(false);

    const [isSubmitted, setIsSubmitted] = useState(0);
    const [isChecked, setIsChecked] = useState(false);
    const [paymentId, setPaymentId] = useState(0);
    const [productsList, setProductsList] = useState([]);
    const [paymentsList, setPaymentsList] = useState([]);
    const [customersList, setCustomersList] = useState([]);
    const [invoiceDetail, setinvoiceDetail] = useState({});
    const [errorData, setErrorData] = useState(false);
    const [isShowpdf, setisShowpdf] = useState(false);
    const [showInvoicePopup, setShowInvoicePopup] = useState(false);
    const [selectedDate, setSelectedDate] = useState(() => {
        if (!invoiceDetail.created_date) return '';
        const d = new Date(invoiceDetail.created_date);
        return d.getUTCFullYear() + '-' + String(d.getUTCMonth()+1).padStart(2,'0') + '-' + String(d.getUTCDate()).padStart(2,'0');
    });
    const [selectedCustomer, setSelectedCustomer] = useState('');
	const [isSavingNew, setIsSavingNew] = useState(false);
    const [showAddPanel, setShowAddPanel] = useState(false);
    const [panelSuccess, setPanelSuccess] = useState(false);
    const [editingNotes, setEditingNotes] = useState(false);
    const [editingCustomer, setEditingCustomer] = useState(false);
    const [savingCustomer, setSavingCustomer] = useState(false);
    const [editingDate, setEditingDate] = useState(false);
    const [savingDate, setSavingDate] = useState(false);
    const notesTextareaRef = useRef(null);
    const addPanelRef = useRef(null);
    const [invoiceNotes, setInvoiceNotes] = useState('');
    const [notesSaveStatus, setNotesSaveStatus] = useState(''); // '', 'saving', 'saved'
    const notesSaveTimer = useRef(null);
    const [pagePaymentSummary, setPagePaymentSummary] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [mobileSummaryOpen, setMobileSummaryOpen] = useState(false);
    const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
    const [mobileSearch, setMobileSearch] = useState('');
    const [activeDateField, setActiveDateField] = useState(null);
    const [expandedCardIndex, setExpandedCardIndex] = useState(null);
    const [showColFilter, setShowColFilter] = useState(false);
    const [visibleCols, setVisibleCols] = useState(() => {
        try { const s = localStorage.getItem('ts_inv_cols'); if (s) return JSON.parse(s); } catch(e) {}
        return { remarks: true, supplier: true };
    });
    const toggleCol = (col) => { const next = { ...visibleCols, [col]: !visibleCols[col] }; setVisibleCols(next); localStorage.setItem('ts_inv_cols', JSON.stringify(next)); };

	const notifyError = (error) => toast.error(error, {
		position: "top-right",
		autoClose: 3000,
		hideProgressBar: false,
		closeOnClick: false,
		pauseOnHover: true,
		draggable: true,
		progress: undefined,
		theme: "light",
		//transition: Bounce,
	});

	const notifySuccess = (success) => toast.success(success, {
		position: "top-right",
		autoClose: 3000,
		hideProgressBar: false,
		closeOnClick: false,
		pauseOnHover: true,
		draggable: true,
		progress: undefined,
		theme: "light",
		//transition: Bounce,
	});

	
	const loadList = () => {
		fetchCustomerList();
		fetchPagePaymentSummary();
        SalesService.allInvoiceDetail(props.id).then(response => {
            if(response.data){
                const productsData = response.data;
                if(productsData.length >=1){
                    setisShowpdf(true);
                }
                productsData.forEach(element => {
                    subTotal += parseFloat(element.totalPrice) || 0;
                });
                productsData.push({
                    product: '',
					supplier:[],
					supplier_id:{},
					supplier_selected_text:"",
					invoice:[],
					invoice_id:{},
                    quantity: '',
					selected : 0,
					remarks:'',
                    price: '',
                    totalPrice: '',
                    fieldToggle: '',
                    invoiceproductid: 0,
                });
                setRowsData(productsData);
            }
        }).finally(() => {
			setIsLoading(false);
		});
	}


    var subTotal = 0;
    useEffect(() => {
      loadList();
    },[])
    useEffect(()=>{
        setIsChecked(true);
        rowsData.forEach(row => {
            if(row.fieldToggle!='checked')
            {
              setIsChecked(false)
            }
            });
    },[rowsData])

    // sessionStorage helper — products & payment methods rarely change within a session,
    // so we cache them for 5 minutes to make subsequent invoice page loads near-instant.
    const _cacheGet = (key, ttlMs) => {
        try {
            const raw = sessionStorage.getItem(key);
            if (!raw) return null;
            const { t, v } = JSON.parse(raw);
            if (Date.now() - t > ttlMs) return null;
            return v;
        } catch (e) { return null; }
    };
    const _cacheSet = (key, v) => {
        try { sessionStorage.setItem(key, JSON.stringify({ t: Date.now(), v })); } catch (e) {}
    };
    useEffect(() => {
        const handler = (e) => { setForceDesktop(e.detail === 'on'); };
        window.addEventListener('ts-invoice-view', handler);
        return () => window.removeEventListener('ts-invoice-view', handler);
    }, []);
    const colFilterRef = useRef(null);
    useEffect(() => {
        if (!showColFilter) return;
        const handler = (e) => { if (colFilterRef.current && !colFilterRef.current.contains(e.target)) setShowColFilter(false); };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [showColFilter]);
    // On desktop/tablet: always keep an empty row at the end so the inline form shows
    useEffect(() => {
        if (isLoading || effectiveWidth < 768 || rowsData.length === 0) return;
        const lastRow = rowsData[rowsData.length - 1];
        const hasNewRow = lastRow && lastRow.fieldToggle !== 'checked' && lastRow.invoiceproductid === 0;
        if (!hasNewRow) {
            setRowsData(prev => [...prev, { product:'', supplier:'', invoice:'', quantity:'', price:'', totalPrice:'', fieldToggle:'', invoiceproductid:0, resetKey:0 }]);
        }
    }, [isLoading, rowsData, effectiveWidth]);
    // const [subTotal, setSubTotal] = useState();
    useEffect(() => {
        // Products & payments are session-wide reference lists — serve cached copies instantly,
        // then refresh from the network in the background so future edits see the latest data.
        const CACHE_TTL = 5 * 60 * 1000; // 5 min
        const cachedProducts = _cacheGet('ci_productsList', CACHE_TTL);
        const cachedPayments = _cacheGet('ci_paymentsList', CACHE_TTL);
        if (cachedProducts) setProductsList(cachedProducts);
        if (cachedPayments) setPaymentsList(cachedPayments);

        // Always re-fetch in the background so cache freshens — but UI already has data shown.
        SalesService.productsList().then(response => {
            if (response.data) { setProductsList(response.data); _cacheSet('ci_productsList', response.data); }
        });
        SalesService.paymentsList().then(response => {
            if (response.data) { setPaymentsList(response.data); _cacheSet('ci_paymentsList', response.data); }
        });

        // fetchInvoiceDetail covers what fetchPagePaymentSummary needed too — calling both was duplicate work.
        // fetchPagePaymentSummary is already invoked inside loadList() on mount; not needed again here.
        fetchInvoiceDetail({ getInvoiceId: props.id });

    }, []);

    // A product just created from the inline "+ Add" popup — add it to the list,
    // refresh the cache, and auto-select it into the row that triggered the popup.
    const onProductCreated = (item, reused, rowIndex) => {
        if (!item) return;
        setProductsList(prev => {
            const exists = prev.some(p => String(p.id) === String(item.id));
            const next = exists ? prev : [...prev, { id: item.id, name: item.name }];
            _cacheSet('ci_productsList', next);
            return next;
        });
        if (rowIndex !== undefined && rowIndex !== null) {
            handleProductChange(rowIndex, { label: item.name, value: item.id });
        }
    };

    useEffect(() => {
      if (invoiceDetail.created_date) {
        // Backend's CustomerInvoice model accessor pre-formats created_at as "DD Mon YYYY"
        // (e.g. "01 May 2026") via LogsActivityTrait::getCreatedAtAttribute().
        // Parsing that through `new Date()` and then calling getUTCDate() silently shifts the
        // date back by one day in any UTC+ timezone (UK BST = UTC+1 → "01 May" becomes "30 Apr").
        // Solution: parse the formatted string directly, no Date object involved.
        const raw = String(invoiceDetail.created_date).trim();
        let iso = '';

        // Format 1 — already ISO "YYYY-MM-DD" (with optional time)
        const isoMatch = raw.match(/^(\d{4})[-/](\d{2})[-/](\d{2})/);
        if (isoMatch) {
            iso = isoMatch[1] + '-' + isoMatch[2] + '-' + isoMatch[3];
        } else {
            // Format 2 — "DD Mon YYYY" or "D Mon YYYY" (what the backend accessor returns)
            const monthMap = { jan:'01', feb:'02', mar:'03', apr:'04', may:'05', jun:'06', jul:'07', aug:'08', sep:'09', oct:'10', nov:'11', dec:'12' };
            const m2 = raw.match(/^(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})/);
            if (m2) {
                const mm = monthMap[m2[2].toLowerCase().slice(0,3)];
                if (mm) iso = m2[3] + '-' + mm + '-' + m2[1].padStart(2, '0');
            }
            // Format 3 — "DD/MM/YYYY" UK style fallback
            if (!iso) {
                const ukMatch = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
                if (ukMatch) iso = ukMatch[3] + '-' + ukMatch[2] + '-' + ukMatch[1];
            }
        }

        if (iso) setSelectedDate(iso);
      }
      setSelectedCustomer(invoiceDetail.customer_id)
    }, [invoiceDetail])

    useEffect(() => {
        if (showAddPanel && rowsData.length > 0 && rowsData[rowsData.length - 1]?.fieldToggle === 'checked') {
            setShowAddPanel(false);
        }
    }, [rowsData, showAddPanel]);

    useEffect(() => {
        if (showAddPanel) {
            setTimeout(() => {
                if (addPanelRef.current) {
                    addPanelRef.current.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }, 80);
        }
    }, [showAddPanel]);

    var porterageVal = 0;
    var vatVal = 0;
    var invoiceTotal = 0;
    if (rowsData.length > 0) {
        rowsData.forEach(element => {
            subTotal += parseFloat(element.totalPrice) || 0;
        });
    }
    if (subTotal == 0) {
        invoiceTotal = 0;
    } else {
        invoiceTotal = (parseFloat(porterageVal) + parseFloat(vatVal) + parseFloat(subTotal));
    }

    const fetchInvoiceDetail = (getdatainvoiceid) => {
      SalesService.invoiceDetail(getdatainvoiceid).then(response => {
          setPaymentId("");
          if(response.data.invoice_payment){
              setPaymentId(response.data.invoice_payment.payment_id);
          }
			setinvoiceDetail({id:response.data.id,other_invoice_id:response.data.other_invoice_id,customer:response.data.customer.name,customer_id:response.data.customer_id,customer_email:response.data.customer.email,created_date:response.data.created_at});
          setInvoiceNotes(response.data.notes || '');
          if (response.data.payment_summary) {
            const ps = response.data.payment_summary;
            setPagePaymentSummary({ total: parseFloat(ps.total), paid: parseFloat(ps.total_paid), pending: parseFloat(ps.paid) });
          }
      });
    }

    const fetchPagePaymentSummary = async () => {
      try {
        const response = await axios.get(`/data_entry/sales_entry/invoice_payment/view/${props.id}`);
        const details = response.data.payload.details;
        if (details) {
          const total = parseFloat(details.total) || 0;
          const paid = parseFloat(details.total_paid) || 0;
          const pending = total - paid;
          setPagePaymentSummary({
            total,
            paid,
            pending: Math.max(0, pending),
            credit: pending < 0 ? Math.abs(pending) : 0,
          });
        }
      } catch (err) { /* silently fail */ }
    };

    const handleNotesChange = (e) => {
      setInvoiceNotes(e.target.value);
    };

    const saveNotes = async () => {
      setEditingNotes(false);
      setNotesSaveStatus('saving');
      try {
        await axios.post('/data_entry/sales_entry/ajax/save-invoice-notes', { id: props.id, notes: invoiceNotes });
        setNotesSaveStatus('saved');
        setTimeout(() => setNotesSaveStatus(''), 2000);
      } catch {
        setNotesSaveStatus('');
      }
    };

	const handleDownload = async (e) => {
		if (e) e.preventDefault();
		setDownloading(true);
		try {
			const response = await axios.get("/data_entry/sales_entry/invoice/invoiceexcel/"+props.id, {responseType: 'blob'});
			const url = window.URL.createObjectURL(new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
			const link = document.createElement('a');
			link.href = url;
			link.setAttribute('download', 'invoice_'+props.id+'.xlsx');
			document.body.appendChild(link);
			link.click();
			link.remove();
			window.URL.revokeObjectURL(url);
		} catch(err) {
			notifyError('Download failed');
		} finally { setDownloading(false); }
	};

	const handlePrint = (e) => {
		if (e) e.preventDefault();
		setPrinting(true);
		window.open("/data_entry/sales_entry/invoice/invoiceview/"+props.id, '_blank');
		setTimeout(() => setPrinting(false), 2000);
	};

	const [emailModalOpen, setEmailModalOpen] = useState(false);
	const sendEmail = (id) => {
		// Opens the email modal — actual send happens after user fills the form.
		setEmailModalOpen(true);
	}
	
	const onSaveStock = (index, data) => {
		let stock_suppliers = data.suppliers;
		let current_supplier = data.current;
		if(data != ""){
			const suppliers = [
			{
				label: "--Select Supplier--",
				value: ""
			},
			...stock_suppliers.flatMap((item) =>
				(item.supplier?.invoices || []).map((invoice) => ({
				  label: invoice.invoice_title || "Untitled Invoice",
				  supplier_name: item.supplier?.name || "Unknown Supplier",
				  available_qty: invoice.available_qty ?? null,
				  value:{
						supplier_invoice: invoice.supplier_invoice_id,
						supplier: invoice.supplier_id,
						product: invoice.product_id,
						supplier_invoice_product_id: invoice.id
				  }
				}))
			)
			];

			setRowsData(prevRows => {
				const updated = [...prevRows];
				updated[index] = {
					...updated[index],
					supplier: suppliers
				};
				updated[index] = {
					...updated[index],
					supplier_id: current_supplier
				};	
				return updated;
			});
		}
	}

    const addTableRows = () => {
        const rowsInput = {
            product: '',
			supplier:'',
			invoice:'',
            quantity: '',
            price: '',
            totalPrice: '',
            fieldToggle: '',
            invoiceproductid: 0,
            resetKey: 0,
        }
        setRowsData([...rowsData, rowsInput]);
    }

    const handleResetRow = (index) => {
        const rowsInput = [...rowsData];
        const current = rowsInput[index];
        rowsInput[index] = {
            product: '', supplier: '', supplier_id: '', invoice: '',
            quantity: '', price: '', totalPrice: '', remarks: '',
            fieldToggle: '', invoiceproductid: 0,
            resetKey: (current.resetKey || 0) + 1,
        };
        setRowsData(rowsInput);
    };
    const deleteTableRows = (index, invoiceproductid) => {
        if (invoiceproductid == 0) {
            const rows = [...rowsData];
            rows.splice(index, 1);
            setRowsData(rows);
            return;
        }
        setPendingDelete(index);
    };

    const performDelete = () => {
        const index = pendingDelete;
        const invoiceproductid = rowsData[index]?.invoiceproductid;
        setPendingDelete(null);
        const rows = [...rowsData];



        if(invoiceproductid != 0){

           const deleteData = {  invoiceproductid: invoiceproductid, invoiceId: props.id };

            SalesService.deleteSingleInvoice(deleteData)
                .then(response => {
                    if (response.data.success === true) {
                        setTimeout(() => {
							rows.splice(index, 1);
							setRowsData(rows);
						}, 200);
						setTimeout(() => {
							const rowsInput = [...rowsData];
							// all rows.
							rowsInput.forEach((row, rowIndex) => {
								if (typeof row.supplier_id?.value?.supplier_invoice_product_id !== "undefined") {
									// for selected.
									(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
										if(row.supplier_id.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
											//rowsInput[rowIndex]['supplier_id']['label'] = stockRow.label
										}
									});
									// for each row.
									(row.supplier).forEach((row2, rowIndex2) => {
									if(typeof row2.options != "undefined"){
										(row2.options).forEach((row3, rowIndex3) => {
											(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
												if(row3.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
													//rowsInput[rowIndex][rowIndex2][rowIndex3]['label'] = stockRow.label
													//console.log(111111)
													//console.log(rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'])
													rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'] = stockRow.label
												}
											});
										});
										}
									});
								}
								//console.log('row')
								//console.log(rowsInput[rowIndex])
							});
							console.log('delete-row')
							console.log(rowsInput)
								
							setRowsData(rowsInput);
						}, 100);
						fetchPagePaymentSummary();
						if(response.data.payload.pdfshow === 0){
                            setisShowpdf(false);

                        }
                    }else{
                        alert('There is Some Error!')
                    }
                });

        }else{
            rows.splice(index, 1);
            setRowsData(rows);

        }


        // rows.splice(index, 1);
        // setRowsData(rows);
    }
    /*const handleProductChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['product'] = evnt.target.value;
        setRowsData(rowsInput);
        setErrorData(false);
    }*/
	
	const updateLabelBySupplierInvoiceProductId = (data, targetId, newLabel) => {
	  if (!Array.isArray(data)) return data; // safety check for non-array data

	  let d = data.map(product => ({
		...product,
		supplier: Array.isArray(product.supplier)
		  ? product.supplier.map(supplierGroup => {
			  const optionsArray = Array.isArray(supplierGroup?.options)
				? supplierGroup.options
				: [];

			  return {
				...supplierGroup,
				options: optionsArray.map(option =>
				  option?.value?.supplier_invoice_product_id === targetId
					? { ...option, label: newLabel }
					: option
				),
			  };
			})
		  : [],

		// Also update selected supplier label if needed
		supplier_id:
		  product.supplier_id?.value?.supplier_invoice_product_id === targetId
			? { ...product.supplier_id, label: newLabel }
			: product.supplier_id,
	  }));
		return d;
	};
	const clearFieldError = (rowsInput, index, field) => {
		if (rowsInput[index]['fieldErrors']) {
			const errs = {...rowsInput[index]['fieldErrors']};
			delete errs[field];
			rowsInput[index]['fieldErrors'] = errs;
		}
	};

	const handleProductChange = (index, evnt) => {
		const rowsInput = [...rowsData];
		rowsInput[index]['product'] = evnt;
		rowsInput[index]['rowError'] = '';
		clearFieldError(rowsInput, index, 'product');
		setRowsData(rowsInput);
		setErrorData(false);

		// call api to get suppliers.
		SalesService.suppliersList(evnt.value).then(response => {
			if (response.data.success === true) {
				/*const suppliers = response.data.payload.map((supplier) => ({
					label: supplier.supplier.name,
					value: supplier.supplier_id
				}));*/
				const suppliers = [
				{
					label: "--Select Supplier--",
					value: ""
				},
				/*...response.data.payload.map((supplier) => ({
					label: supplier.supplier.name,
					value: supplier.supplier_id
					}))*/
					
					// full listing.
					/*...response.data.payload.flatMap((item) =>
					(item.supplier?.invoices || []).map((invoice) => ({
					  label: invoice.invoice_title || "Untitled Invoice",
					  value: invoice.supplier_id
					}))
					)*/
					// options flat (supplier_name shown via formatOptionLabel)
					...response.data.payload.flatMap((item) =>
						(item.supplier?.invoices || []).map((invoice) => ({
						  label: invoice.invoice_title || "Untitled Invoice",
						  label_short: invoice.invoice_title_short || "Untitled Invoice",
						  supplier_name: item.supplier?.name || "Unknown Supplier",
						  available_qty: invoice.available_qty ?? null,
						  sell_price: invoice.sale_price ?? invoice.sell_price ?? null,
						  value:{
								supplier_invoice: invoice.supplier_invoice_id,
								supplier: invoice.supplier_id,
								product: invoice.product_id,
								supplier_invoice_product_id: invoice.id
						  }
						}))
					)
				];
				
				//console.log(suppliers)
				// Calculate total available stock for this product
				const totalStock = suppliers.reduce((sum, s) => {
					if (!s.value) return sum; // skip "-- Select Supplier --"
					const qty = s.available_qty ? Number(s.available_qty) : 0;
					if (qty > 0) return sum + qty;
					// Fallback: parse qty from label (e.g. "Supplier...|Qty:5|P:10|")
					const match = (s.label || '').match(/Qty:(\d+)/);
					return sum + (match ? Number(match[1]) : 0);
				}, 0);

				// ✅ update state properly
				setRowsData(prevRows => {
					const updated = [...prevRows];
					updated[index] = {
						...updated[index],
						supplier: suppliers,
						supplier_id: suppliers[0],
						product_total_stock: totalStock,
						stockWarning: '',
					};
					return updated;
				});
			}
		});
	};
	
	const handleInvoiceChange = (index, evnt) => {
		const rowsInput = [...rowsData];
        rowsInput[index]['invoice_id'] = evnt;
        setRowsData(rowsInput);
        setErrorData(false);
	}
	
	const isAnySelected = (rowsData) => {
	  return rowsData.some(row => row.selected === 1);
	};
	
	const checkUncheckAll = (e) => {
		const updatedRows = rowsData.map(row => ({
		  ...row,
		  selected: e.target.checked === true ? 1 : 0
		}));

		setRowsData(updatedRows);
		setErrorData(false);
	}
	
	const handleSelection = (index,e) => {
		const rowsInput = [...rowsData];
        rowsInput[index]['selected'] = e.target.checked === true ? 1 : 0;
        setRowsData(rowsInput);
        setErrorData(false);
	}
	
	const deleteSelected = (e) => {
		let rows = rowsData
		  .map((row, index) => ({ row: index, selected: row.selected, invoiceproductid: row.invoiceproductid })) // attach index
		  .filter(row => row.selected === 1) // only selected rows
		  .map(row => ({ row: row.row, invoiceproductid: row.invoiceproductid })); // final structure
		
		/*PurchasesService.deleteInvoiceProducts(rows)
			.then(response => {
				if (response.data.success === true) {
					
				}else if(response.data.success === false){
					//showAlert(response.data.payload, "danger")
					//notifyError(response.data.payload)
				}else{
					alert('There is Some Error!')
				}
			});*/
	}
	
	const handleSupplierChange = (index,product, evnt) => {
        const rowsInput = [...rowsData];

		rowsInput[index]['supplier_id'] = evnt;
		clearFieldError(rowsInput, index, 'supplier');
		// Parse available qty from selected supplier label
		const qtyMatch = (evnt.label || '').match(/Qty:(\d+)/);
		const supplierQty = evnt.available_qty ? Number(evnt.available_qty) : (qtyMatch ? Number(qtyMatch[1]) : null);
		rowsInput[index]['available_qty'] = supplierQty;
		// Check qty warning against selected supplier stock
		const currentQty = parseFloat(rowsInput[index]['quantity']) || 0;
		if (supplierQty !== null && currentQty > supplierQty) {
			rowsInput[index]['qtyWarning'] = `Only ${supplierQty} available from this supplier.`;
		} else {
			rowsInput[index]['qtyWarning'] = '';
		}
		rowsInput[index]['stockWarning'] = '';
		rowsInput[index]['rowError'] = '';
		// Auto-fill qty to 1 if empty
		let qty = currentQty;
		if (!qty || qty <= 0) {
			qty = 1;
			rowsInput[index]['quantity'] = 1;
			clearFieldError(rowsInput, index, 'quantity');
		}
		// Auto-fill sell price into unit price if available
		if (evnt.sell_price && Number(evnt.sell_price) > 0) {
			rowsInput[index]['price'] = Number(evnt.sell_price);
			rowsInput[index]['totalPrice'] = Number(evnt.sell_price) * qty;
			clearFieldError(rowsInput, index, 'price');
		}
        setRowsData(rowsInput);
        setErrorData(false);

    }
	
	const changeLabelDisplay = (data) => {
		data.label = data.label_short;
		return data;
	}
	
    const handlePriceChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        const raw = evnt.target.value;
        rowsInput[index]['price'] = raw;
        rowsInput[index]['totalPrice'] = (parseFloat(raw) || 0) * (parseFloat(rowsInput[index]['quantity']) || 0);
        rowsInput[index]['rowError'] = '';
        clearFieldError(rowsInput, index, 'price');
        setRowsData(rowsInput);
        setErrorData(false);
    }
    const handleQtyChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        const raw = evnt.target.value;
        // Let the field go empty while the user clears/retypes — don't force it back to 1.
        const qty = raw === '' ? '' : Math.max(1, Math.floor(+raw || 1));
        rowsInput[index]['quantity'] = qty;
        clearFieldError(rowsInput, index, 'quantity');
        const qtyNum = qty === '' ? 0 : qty;
        rowsInput[index]['totalPrice'] = qtyNum * (parseFloat(rowsInput[index]['price']) || 0);
        // Per-supplier stock warning
        const avail = rowsInput[index]['available_qty'];
        if(avail !== null && avail !== undefined && qtyNum > avail){
            rowsInput[index]['qtyWarning'] = `You are adding ${qty} units but only ${avail} available from this supplier.`;
        } else {
            rowsInput[index]['qtyWarning'] = '';
        }
        rowsInput[index]['stockWarning'] = '';
        rowsInput[index]['rowError'] = '';
        setRowsData(rowsInput);
        setErrorData(false);
    }
	
	const handleRemarksChange = (index, evnt) => {
	  const rowsInput = [...rowsData];
        rowsInput[index]['remarks'] = evnt.target.value;
        setRowsData(rowsInput);
        setErrorData(false);
	};
	
    const handleToogleChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        let fieldData = rowsInput[index];
        // Validation on fieldData
        const { product, supplier,supplier_id,supplier_selected_text,selected,remarks, quantity, price, totalPrice, fieldToggle } = fieldData;
        // if(!product || !quantity || !price || !totalPrice || !fieldToggle) {
        //     // Show alert
        //     return "please fill the field";
        // };
        const fieldErrors = {};
        if(fieldData.product == "") fieldErrors.product = "Required";
        if(showSuppliers && (!fieldData.supplier_id || !fieldData.supplier_id.value)) fieldErrors.supplier = "Required";
        if(!fieldData.quantity) fieldErrors.quantity = "Required";
        if(Object.keys(fieldErrors).length > 0){
            rowsInput[index]['fieldErrors'] = fieldErrors;
            setRowsData(rowsInput);
        } else {
            /*setErrorData(false);
            rowsInput[index]['fieldToggle']='checked';*/
            //  if (evnt.target.checked) {
            //     rowsInput[index]['fieldToggle'] = "checked";

            // }
            // else {
            //     rowsInput[index]['fieldToggle'] = "";
            // }
            /*setRowsData(rowsInput);*/
            fieldData = { ...fieldData, invoiceId: props.id, indexvalue: index };
            // Submit form
			
			/*console.log('form enttries')
			console.log(fieldData); return;*/
			setIsSavingNew(true)
            SalesService.addSingleInvoice(fieldData)
                .then(response => {
                    if (response.data.success === true) {
					
						setTimeout(() => {
							setErrorData(false);
							rowsInput[index]['fieldToggle']='checked';
							rowsInput[index]['rowError'] = '';
						}, 100);
						
						/** earlier without set timeout **/
						setTimeout(() => {
							const rowsInput = [...rowsData];
							rowsInput[response.data.payload.indexvalue]['invoiceproductid'] = response.data.payload.invoiceproductid;
							
							console.log('save')
							console.log(rowsInput[response.data.payload.indexvalue]['invoiceproductid']);
							
							// all rows.
							/*rowsInput.forEach((row, rowIndex) => {
								if (typeof row.supplier_id?.value?.supplier_invoice_product_id !== "undefined") {
									(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
										if(row.supplier_id.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
											rowsInput[rowIndex]['supplier_id']['label'] = stockRow.label_short
										}
									});
									
									// for each row.
									(row.supplier).forEach((row2, rowIndex2) => {
										if(typeof row2.options != "undefined"){
											(row2.options).forEach((row3, rowIndex3) => {
												(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
													if(row3.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
														//rowsInput[rowIndex][rowIndex2][rowIndex3]['label'] = stockRow.label
														//console.log(111111)
														//console.log(rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'])
														rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'] = stockRow.label
													}
												});
											});
										}
										
									});
								}
								//console.log('row')
								//console.log(rowsInput[rowIndex])
							});*/
							rowsInput.forEach((row, rowIndex) => {
								if(row.invoiceproductid == response.data.payload.invoiceproductid){
									//alert(rowsInput[rowIndex]["supplier_id"]["label"]+'-'+row.supplier_id.label_short)
									if (response.data.payload.stock_selected_row) { rowsInput[rowIndex]["supplier_id"]["label"] = response.data.payload.stock_selected_row.label_short; }
									//rowsInput[rowIndex]["supplier_id"]["label"] = row.supplier_id.label_short
								}
							});
							
							setRowsData(rowsInput);
							addTableRows();
							setisShowpdf(true);
							
						}, 100);
						setPanelSuccess(true);
						setTimeout(() => setPanelSuccess(false), 3000);
						setIsSavingNew(false)
						setShowAddPanel(false); // mobile: close the add-product bottom sheet after a successful save
						fetchPagePaymentSummary();

                    }if (response.data.success === false) {
						setRowsData(prev => {
							const updated = [...prev];
							updated[index] = { ...updated[index], rowError: parseErrorMessage(response.data.payload) };
							return updated;
						});
						setIsSavingNew(false)
					}else{
                        if(response.data.status === '208'){
                            alert('Invoice Already Created .')
                            window.location.href = '/data_entry/sales_entry/create';
                        }else{
                        }
						setIsSavingNew(false)
                    }
                });
        }
    }
    const handleEditChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['fieldToggle']='';
        // Track if product originally had no supplier when edit started
        const product = rowsInput[index]['product'];
        const sid = rowsInput[index]['supplier_id'];
        const hasSupplier = sid && sid.value && typeof sid.value === 'object';
        rowsInput[index]['_editNoSupplier'] = !hasSupplier;
        rowsInput[index]['_origSupplierId'] = rowsInput[index]['supplier_id'];
        rowsInput[index]['_origPrice'] = rowsInput[index]['price'];
        rowsInput[index]['_origTotalPrice'] = rowsInput[index]['totalPrice'];
        setRowsData(rowsInput);
        // Load supplier options only if product has NO supplier assigned and options not loaded
        if (!hasSupplier && product && product.value) {
            SalesService.suppliersList(product.value).then(response => {
                if (response.data.success === true) {
                    const suppliers = [
                        { label: "--Select Supplier--", value: "" },
                        ...response.data.payload.flatMap((item) =>
                            (item.supplier?.invoices || []).map((invoice) => ({
                                label: invoice.invoice_title || "Untitled Invoice",
                                label_short: invoice.invoice_title_short || "Untitled Invoice",
                                supplier_name: item.supplier?.name || "Unknown Supplier",
                                available_qty: invoice.available_qty ?? null,
                                sell_price: invoice.sale_price ?? invoice.sell_price ?? null,
                                value: {
                                    supplier_invoice: invoice.supplier_invoice_id,
                                    supplier: invoice.supplier_id,
                                    product: invoice.product_id,
                                    supplier_invoice_product_id: invoice.id
                                }
                            }))
                        ).sort((a, b) => String(a.supplier_name).localeCompare(String(b.supplier_name), undefined, { sensitivity: 'base' }))
                    ];
                    setRowsData(prev => {
                        const updated = [...prev];
                        updated[index] = { ...updated[index], supplier: suppliers };
                        return updated;
                    });
                }
            });
        }
    }
    const handlePaymentChange = (evnt) => {
            var values = {customer_invoice_id: props.id, payment_id: evnt.target.value };
            SalesService.updatePaymentMethod(values)
                .then(response => {
                    //console.log(response);
                    if (response.data.success === true) {
                        setPaymentId(response.data.payload.payment_id);
                    }else{
                        alert('There is Some Error!');
                    }
                });
    }
    const handleUpdateChange = (index, evnt) => {

        const rowsInput = [...rowsData];
        let fieldData = rowsInput[index];
        rowsInput[index]['fieldToggle']='';

        const missingFields = [];
        if(!fieldData.product) missingFields.push("Product");
        if(!fieldData.quantity) missingFields.push("Quantity");
        if(missingFields.length > 0){
            notifyError(missingFields.length === 1 ? `${missingFields[0]} is required.` : `Required: ${missingFields.join(", ")}.`);
            setRowsData(rowsInput);
            return;
        }
        setRowsData(rowsInput);

        fieldData = { ...fieldData, invoiceId: props.id, indexvalue: index, customer_id:selectedCustomer };
        // Submit form

        SalesService.editSingleInvoice(fieldData)
                .then(response => {
                    if (response.data.success === true) {
                        const rowsInput = [...rowsData];
                        rowsInput[response.data.payload.indexvalue]['invoiceproductid'] = response.data.payload.invoiceproductid;
                        rowsInput[response.data.payload.indexvalue]['fieldToggle'] ="checked";
                        	
						// all rows.
						/*rowsInput.forEach((row, rowIndex) => {
							//console.log('update row');
							//console.log(row)
							if (typeof row.supplier_id?.value?.supplier_invoice_product_id !== "undefined") {
								// for selected.
								//console.log('updated')
								//console.log(row.invoiceproductid)
								(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
									if(row.supplier_id.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
										//rowsInput[rowIndex]['supplier_id']['label'] = stockRow.label
									}
								});
								// for each row.
								(row.supplier).forEach((row2, rowIndex2) => {
								if(typeof row2.options != "undefined"){
									(row2.options).forEach((row3, rowIndex3) => {
										(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
											if(row3.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
												//rowsInput[rowIndex][rowIndex2][rowIndex3]['label'] = stockRow.label
												//console.log('updated')
												//console.log(row.invoiceproductid)
												//console.log(rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'])
												rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'] = stockRow.label
											}
										});
									});
									}
								});
							}else{
								// mainly for add new row.
								if(typeof typeof row.supplier != "undefined"){
									console.log('updated empty')
									//console.log(response.data.payload.stock)
									//console.log((response.data.payload.stock).length)
									Object.values(row.supplier).forEach((row2, rowIndex2) => {
										//console.log(111)
										if(typeof row2.options != "undefined"){
											(row2.options).forEach((row3, rowIndex3) => {
												Object.values(response.data.payload.stock).forEach((stockRow, stockRowIndex) => {
													//console.log(222)
													//if(row3.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
														//rowsInput[rowIndex][rowIndex2][rowIndex3]['label'] = stockRow.label
														//console.log('updated')
														//console.log(row.invoiceproductid)
														//console.log(rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'])
														rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'] = stockRow.label
													}//
													if(row3.value.supplier_invoice_product_id == stockRow.supplier_invoice_product_id){
														rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'] = stockRow.label
													}
													//console.log(rowsInput[rowIndex]['supplier'][rowIndex2]['options'][rowIndex3]['label'])
												});
											});
											}
										});
									
								}
							}
							//console.log('row')
							//console.log(rowsInput[rowIndex])
						});*/
						
						/*rowsInput.forEach((row, rowIndex) => {
							if(row.invoiceproductid == response.data.payload.invoiceproductid){
								//console.log('edit')
								//console.log(rowsInput[rowIndex])
								//rowsInput[rowIndex]["supplier_id"]["label"] = response.data.payload.stock_selected_row.label_short
							}
						});*/
						
						setRowsData(rowsInput);
                        setisShowpdf(true);
						fetchPagePaymentSummary();
						notifySuccess("Product updated successfully!");
                    }else if (response.data.success === false) {
						//alert(response.data.payload)
						//showAlert(response.data.payload, "danger");
						notifyError(parseErrorMessage(response.data.payload));
					}else{
                        if(response.data.status === '208'){
                            alert('Invoice Already Created .')
                            window.location.href = '/data_entry/sales_entry/create';

                        }else{
							alert('There is Some Error!')

                        }
                    }
                });
    }

    const formik = useFormik({
        initialValues: {
            rowsdata: rowsData,
            invoiceId: props.id,
            status:''
        },
        enableReinitialize: true,
        onSubmit: (values, { resetForm }) => {

            //console.log('values'+ values)

            setIsSubmitted(1)
            SalesService.addInvoice(values)
                .then(response => {
                    //console.log(response);
                    if (response.data.success === true) {
                        window.location.href = '/data_entry/sales_entry/invoice/invoiceview/'+response.data.payload.id;
                    }else{

                        if(response.data.status === '208'){
                            alert('Invoice Already Created .')
                            window.location.href = '/data_entry/sales_entry/create';

                        }else{
                        alert('There is Some Error!')

                        }
                    }

                });
        }
    });
    const { handleSubmit, values, setFieldValue, errors } = formik;

    const handleonchange = (event) => {
        setFieldValue('status', event.target.value);
    }

    /*let allProducts = (productsList).map((product) => {
        return <option id={product.id} value={product.id}>{product.name}</option>;
    });*/
	let allProducts = productsList.map((product) => {
		return {
			label: product.name,
			value: product.id
		};
	}).sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' }));
	
	let allSuppliers = [{label:"A",value:10},{label:"B",value:12}];


    let allPayments = (paymentsList).map((payment) => {
        return <option id={payment.id} value={payment.id}>{payment.type}</option>;
    });

    const handleUpdateCustomer = () => {
      setSavingCustomer(true);
      SalesService.updateInvoiceDetailMethod({
        created_at: selectedDate,
        customer_id: selectedCustomer,
        id: invoiceDetail.id
      }).then(response => {
        setSavingCustomer(false);
        if (response.data.success === true) {
          setEditingCustomer(false);
          fetchInvoiceDetail({ getInvoiceId: props.id });
        } else {
          alert('There is Some Error!');
        }
      });
    };

    const handleUpdateDate = () => {
      setSavingDate(true);
      SalesService.updateInvoiceDetailMethod({
        created_at: selectedDate,
        customer_id: selectedCustomer,
        id: invoiceDetail.id
      }).then(response => {
        setSavingDate(false);
        if (response.data.success === true) {
          setEditingDate(false);
          fetchInvoiceDetail({ getInvoiceId: props.id });
        } else {
          alert('There is Some Error!');
        }
      });
    };

    const fetchCustomerList = () => {
      SalesService.FetchUser()
          .then(response => {
              if (response.data.success === true) {
                  const sorted = [...(response.data.payload || [])].sort((a, b) =>
                      String(a.name).localeCompare(String(b.name), undefined, { sensitivity: 'base' }));
                  setCustomersList(sorted)
              }else{
                  alert('There is Some Error!')
              }
          });
    }

    const handleEditInvoice = e => {
      e.preventDefault();
      //console.log(selectedDate, selectedCustomer, invoiceDetail.id);

      SalesService.updateInvoiceDetailMethod({
        created_at: selectedDate,
        customer_id: selectedCustomer,
        id: invoiceDetail.id
      })
      .then(response => {
          if (response.data.success === true) {
              setShowInvoicePopup(false);
              fetchInvoiceDetail({  getInvoiceId: props.id });
          }else{
              alert('There is Some Error!')
          }
      });
    }

    const forceScroll = forceDesktop && width < 768;
    return (
        <>
            <style>{`.inv-date-picker-wrap{position:relative;display:flex;align-items:center;background:#fafafa;border:1.5px solid #e5e7eb;border-radius:8px;height:36px;padding:0 10px;cursor:pointer;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;gap:6px;max-width:160px;}.inv-date-picker-wrap:hover{border-color:rgb(234, 88, 12);background:#fff;}.inv-date-picker-wrap:focus-within{border-color:rgb(234, 88, 12);box-shadow:0 0 0 3px rgba(234,88,12,0.08);background:#fff;}.inv-date-picker-wrap .inv-date-icon{color:rgb(234, 88, 12);font-size:13px;flex-shrink:0;pointer-events:none;position:static;transform:none;}.inv-date-picker{padding:0;font-size:13px;font-weight:600;border:none;height:100%;color:#1e293b;outline:none;cursor:pointer;background:transparent;width:110px;letter-spacing:0.2px;-webkit-appearance:none;appearance:none;}.inv-date-picker::placeholder{color:#94a3b8;font-weight:500;}@media(min-width:768px) and (max-width:1199px){.pay-invoice-btn{padding:6px 10px !important;font-size:11px !important;gap:5px !important;}}`}</style>
            {width < 768 ? (<>
            {/* ========== MOBILE INVOICE — matching Stock Check layout ========== */}
            <div style={{borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',marginBottom:'14px'}}>
                {/* Header: Back + Invoice # + Pay Invoice */}
                <div style={{display:'flex',alignItems:'center',gap:'12px',padding:'12px 16px'}}>
                    <a href="/data_entry/sales_entry/invoice" style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',textDecoration:'none',flexShrink:0,boxShadow:'0 2px 6px rgba(234,88,12,0.3)'}}>
                        <i className="fa fa-chevron-left" style={{fontSize:'14px',color:'#fff'}}></i>
                    </a>
                    <div style={{flex:1}}>
                        <div style={{fontSize:'17px',fontWeight:'800',color:'#1e293b'}}>Invoice #{invoiceDetail.other_invoice_id || invoiceDetail.id || '—'}</div>
                    </div>
                    {pagePaymentSummary && (
                        <CustomerInvoicePaymentsPopup currency={props.currency} total={formatTwoDecimal(invoiceTotal)} customer={invoiceDetail} onFormChange={fetchPagePaymentSummary} {...props}/>
                    )}
                </div>
            </div>
            {/* ── SUMMARY / Search / Actions — OUTSIDE the card (standalone) ── */}
            <div style={{marginBottom:'14px'}}>
                {/* Collapsible Summary — standalone card */}
                {pagePaymentSummary && (
                <div style={{marginBottom:'10px'}}>
                    <div onClick={()=>setMobileSummaryOpen(v=>!v)} style={{borderRadius: mobileSummaryOpen ? '14px 14px 0 0' : '14px',border:'1px solid #ecedf1',borderBottom: mobileSummaryOpen ? '1px solid #f0f0f0' : '1px solid #ecedf1',background:'#fff',boxShadow:'0 1px 3px rgba(15,23,42,0.06)',padding:'10px 12px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                            <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'rgb(234, 88, 12)'}}/>
                            <span style={{fontSize:'10px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Summary</span>
                        </div>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{display:'flex',gap:'8px'}}>
                                {[{v:formatTwoDecimal(pagePaymentSummary.total),c:'#374151'},{v:formatTwoDecimal(pagePaymentSummary.paid),c:'#16a34a'},{v:pagePaymentSummary.credit > 0 ? formatTwoDecimal(pagePaymentSummary.credit) : formatTwoDecimal(pagePaymentSummary.pending),c:pagePaymentSummary.credit > 0 ? '#dc2626' : 'rgb(234, 88, 12)'}].map((s,i)=>(
                                    <span key={i} style={{fontSize:'12px',fontWeight:'700',color:s.c}}>{s.v}</span>
                                ))}
                            </div>
                            <i className={'fa fa-chevron-'+(mobileSummaryOpen?'up':'down')} style={{fontSize:'9px',color:'#9ca3af'}}/>
                        </div>
                    </div>
                    {mobileSummaryOpen && (
                        <div style={{borderRadius:'0 0 14px 14px',border:'1px solid #ecedf1',borderTop:'none',background:'#fff',overflow:'hidden',boxShadow:'0 1px 3px rgba(15,23,42,0.06)'}}>
                            <div style={{display:'flex',padding:'10px 16px 12px'}}>
                                {[
                                    {label:'Total',   value:pagePaymentSummary.total,   color:'#374151'},
                                    {label:'Paid',    value:pagePaymentSummary.paid,    color:'#16a34a'},
                                    pagePaymentSummary.credit > 0
                                        ? {label:'Credit', value:pagePaymentSummary.credit, color:'#dc2626'}
                                        : {label:'Pending', value:pagePaymentSummary.pending, color:'rgb(234, 88, 12)'},
                                ].map(({label,value,color}, i, arr) => (
                                    <React.Fragment key={label}>
                                        <div style={{flex:1}}>
                                            <div style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'4px'}}>{label}</div>
                                            <div style={{fontSize:'16px',fontWeight:'600',color,lineHeight:1}}>{props.currency} {formatTwoDecimal(value)}</div>
                                        </div>
                                        {i < arr.length - 1 && <div style={{width:'1px',background:'#e5e7eb',margin:'0 8px',alignSelf:'stretch'}}/>}
                                    </React.Fragment>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
                )}

                {/* Action buttons — Email / Print / Download cards + Filter button at the right end */}
                <div style={{display:'flex',alignItems:'center',gap:'10px',padding:'0 0 10px'}}>
                    {[
                        {label:emailsend==1?'Sending...':'Email', icon:'fa-envelope-o', onClick:()=>sendEmail(props.id)},
                        {label:'Print',    icon:'fa-print',    href:"/data_entry/sales_entry/invoice/invoiceview/"+props.id, target:'_blank'},
                        {label:'Download', icon:'fa-download', href:"/data_entry/sales_entry/invoice/invoiceexcel/"+props.id},
                    ].map(({label,icon,href,onClick,target}) => {
                        const st = {flex:1,height:'46px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',textDecoration:'none',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'};
                        const inner = <><i className={"fa "+icon} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{label}</>;
                        return href ? <a key={label} href={href} target={target} style={st}>{inner}</a> : <button key={label} onClick={onClick} style={st}>{inner}</button>;
                    })}
                    {/* Filter button — right end of the action row */}
                    <button type="button" onClick={()=>setMobileFilterOpen(v=>!v)}
                        style={{flexShrink:0,height:'46px',width:'46px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none',boxShadow:'0 3px 10px rgba(234,88,12,0.3)'}}>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    </button>
                </div>

            </div>

            {/* Mobile filter bottom sheet */}
            {mobileFilterOpen && (<>
                <div onMouseDown={()=>setMobileFilterOpen(false)} onTouchStart={()=>setMobileFilterOpen(false)}
                    style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
                <div onMouseDown={e=>e.stopPropagation()} onTouchStart={e=>e.stopPropagation()}
                    style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'80vh',overflowY:'auto'}}>
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
                            <Select
                                value={customersList ? (() => { const cId = selectedCustomer || invoiceDetail.customer_id; const found = customersList.find(c => c.id == cId); return found ? {value: found.id, label: found.name} : null; })() : null}
                                onChange={selected => { if (!selected) return; setSelectedCustomer(selected.value); SalesService.updateInvoiceDetailMethod({ created_at: selectedDate, customer_id: selected.value, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); }}
                                options={customersList ? customersList.map(c => ({value: c.id, label: c.name})) : []}
                                isSearchable placeholder="Select customer" menuPortalTarget={document.body}
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
                                styles={{ control:(b,s)=>({...b,minHeight:'44px',height:'44px',fontSize:'13px',fontWeight:'600',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:'none',background:'#fff',cursor:'pointer',paddingLeft:'8px'}), valueContainer:b=>({...b,padding:'0 12px',height:'44px'}), indicatorsContainer:b=>({...b,height:'44px'}), indicatorSeparator:()=>({display:'none'}), dropdownIndicator:b=>({...b,padding:'0 8px 0 0',color:'#94a3b8'}), clearIndicator:b=>({...b,padding:'0 4px',color:'#cbd5e1'}), singleValue:b=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}), placeholder:b=>({...b,fontSize:'13px',color:'#94a3b8'}), menuPortal:b=>({...b,zIndex:9999}), menu:b=>({...b,zIndex:9999,borderRadius:'12px',border:'1px solid #eaecf2',boxShadow:'0 8px 24px rgba(0,0,0,0.12)'}), option:(b,s)=>({...b,fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#334155'}) }}
                            />
                        </div>
                        {/* Date — styled to match Sales filter date picker */}
                        <div>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date</div>
                            <button type="button" onClick={()=>setActiveDateField(activeDateField==='date'?null:'date')}
                                style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid '+(activeDateField==='date'?'rgb(234, 88, 12)':'#e2e8f0'),background:'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'10px',cursor:'pointer',outline:'none',transition:'all 0.15s'}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span style={{fontSize:'13px',fontWeight:'600',color:selectedDate?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{selectedDate ? (() => { const MON=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; const [y,m,d]=String(selectedDate).split('-').map(Number); return `${String(d).padStart(2,'0')} ${MON[m-1]} ${y}`; })() : 'Select date'}</span>
                                <i className="fa fa-chevron-right" style={{fontSize:'10px',color:'#d1d5db'}}></i>
                            </button>
                            {activeDateField === 'date' && (
                                <div style={{marginTop:'10px',padding:'14px',borderRadius:'14px',border:'1px solid #fed7aa',background:'#fffcf7'}}>
                                    <style>{`.inv-inline-cal .react-datepicker{width:100%;border:none;background:transparent !important;box-shadow:none !important}.inv-inline-cal .react-datepicker__month-container{width:100%;float:none;background:transparent !important}.inv-inline-cal .react-datepicker__month{background:transparent !important;margin:0 !important}.inv-inline-cal .react-datepicker__header{background:transparent !important;border-bottom:none;padding:0}.inv-inline-cal .react-datepicker__header--custom{background:transparent !important;border-bottom:none !important;padding:0 !important}.inv-inline-cal .react-datepicker__day-names,.inv-inline-cal .react-datepicker__week{display:flex;justify-content:space-around}.inv-inline-cal .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;margin:0}.inv-inline-cal .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:40px;font-size:13px;font-weight:600;color:#334155;margin:0;border-radius:50%;position:relative}.inv-inline-cal .react-datepicker__day:hover:not(.react-datepicker__day--selected){background:#fff;color:rgb(234, 88, 12)}.inv-inline-cal .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.inv-inline-cal .react-datepicker__day--selected,.inv-inline-cal .react-datepicker__day--today.react-datepicker__day--selected{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;z-index:1}.inv-inline-cal .react-datepicker__day--selected::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.inv-inline-cal .react-datepicker__day--outside-month{color:#cbd5e1}.inv-inline-cal .react-datepicker__day--disabled{color:#e5e7eb !important;cursor:default}.inv-inline-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#334155}.inv-inline-cal .react-datepicker__navigation{display:none !important}.inv-inline-cal .react-datepicker__current-month{display:none !important}`}</style>
                                    {/* Header: Select date label + close */}
                                    <div style={{display:'flex',alignItems:'center',gap:'6px',marginBottom:'10px'}}>
                                        <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.7px',textTransform:'uppercase'}}>Select date</span>
                                        <div style={{flex:1}}/>
                                        <button type="button" onClick={()=>setActiveDateField(null)} style={{width:'24px',height:'24px',borderRadius:'7px',background:'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',color:'#94a3b8',border:'none',outline:'none',cursor:'pointer'}}>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <div className="inv-inline-cal">
                                        <DatePicker inline selected={selectedDate?new Date(selectedDate+'T00:00:00'):new Date()} maxDate={new Date()}
                                            onChange={(d)=>{ if(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); const val=y+'-'+m+'-'+dd; setSelectedDate(val); SalesService.updateInvoiceDetailMethod({ created_at: val, customer_id: selectedCustomer, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); } setActiveDateField(null); }}
                                            renderCustomHeader={({date,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                                const mnthsFull=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                                return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'10px'}}>
                                                    <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{width:'26px',height:'26px',borderRadius:'7px',background:'#fff',border:'1px solid #fed7aa',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',color:'rgb(234, 88, 12)',opacity:prevMonthButtonDisabled?0.4:1}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                                                    <span style={{fontSize:'13.5px',fontWeight:'800',color:'#1e293b'}}>{mnthsFull[date.getMonth()]} {date.getFullYear()}</span>
                                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{width:'26px',height:'26px',borderRadius:'7px',background:'#fff',border:'1px solid #e5e7eb',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',color:'#cbd5e1',opacity:nextMonthButtonDisabled?0.4:1}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                                </div>);
                                            }}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                        {/* Action buttons */}
                        <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px',paddingTop:'4px'}}>
                            <button type="button" onClick={()=>setMobileFilterOpen(false)}
                                style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                                Clear
                            </button>
                            <button type="button" onClick={()=>setMobileFilterOpen(false)}
                                style={{height:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </>)}
            </>) : (
            /* ========== DESKTOP LAYOUT (>1024px only) ========== */
            <div className="row align-items-stretch">
                <div className="col-12 mb-0">
                    <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',width:'100%'}}>

                        {/* Full-width invoice header bar.
                            min-width:0 on the parent lets this card honor the available row width without overflowing horizontally.
                            We avoid overflow:hidden so the Pay Invoice button never gets clipped at narrow widths / browser zoom. */}
                        <div style={{background:'#fff',borderRadius:'14px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',width:'100%',display:'flex',minWidth:0}}>
                            {/* Invoice badge — spans full height */}
                            <div style={{background:'rgb(234, 88, 12)',padding: width >= 1200 ? '0 24px' : '0 14px',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,minWidth: width >= 1200 ? '80px' : '60px',borderTopLeftRadius:'14px',borderBottomLeftRadius:'14px'}}>
                                <div style={{display:'flex',flexDirection:'column',alignItems:'center',lineHeight:1.2}}>
                                    <span style={{fontSize:'10px',fontWeight:'700',color:'rgba(255,255,255,0.8)',letterSpacing:'1.5px',textTransform:'uppercase'}}>Invoice</span>
                                    <span style={{fontSize:'22px',fontWeight:'900',color:'#fff',whiteSpace:'nowrap'}}>#{invoiceDetail.other_invoice_id || invoiceDetail.id || '—'}</span>
                                </div>
                            </div>
                            {/* Right side */}
                            <div style={{flex:1,minWidth:0}}>
                            {/* Always render the single inline row at desktop widths (>1024px).
                                Customer shrinks freely (no min-width, minWidth:0), Date is fixed-width,
                                3 action icons + 3 metric cells + Pay Invoice all sit on the right with flexShrink:0.
                                The card's parent uses min-width:0 + no overflow:hidden, so even under browser zoom
                                the Pay Invoice button stays inside the card boundary without clipping. */}
                            {true ? (
                            /* ── Desktop single row ── */
                            <div style={{display:'flex',alignItems:'stretch'}}>
                                {/* Customer — shrinks freely (minWidth:0) so wider sections to the right always have room */}
                                <div style={{flex:1,padding:'10px 16px',borderRight:'1px solid #f0f0f0',minWidth:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'4px',marginBottom:'3px'}}>
                                        <i className="fa fa-user" style={{fontSize:'9px',color:'rgb(234, 88, 12)'}}></i>
                                        <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase'}}>Customer</span>
                                    </div>
                                    <Select
                                        value={customersList ? (() => { const cId = selectedCustomer || invoiceDetail.customer_id; const found = customersList.find(c => c.id == cId); return found ? {value: found.id, label: found.name} : null; })() : null}
                                        onChange={selected => { if (!selected) return; setSelectedCustomer(selected.value); SalesService.updateInvoiceDetailMethod({ created_at: selectedDate, customer_id: selected.value, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); }}
                                        options={customersList ? customersList.map(c => ({value: c.id, label: c.name})) : []}
                                        isSearchable placeholder="Select customer..." menuPortalTarget={document.body}
                                        styles={{ control:(b,s)=>({...b,minHeight:'34px',height:'34px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafafa',cursor:'pointer'}), valueContainer:b=>({...b,padding:'0 10px',height:'34px'}), indicatorsContainer:b=>({...b,height:'34px'}), singleValue:b=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}), placeholder:b=>({...b,fontSize:'12px',color:'#9ca3af'}), menuPortal:b=>({...b,zIndex:9999}), menu:b=>({...b,zIndex:9999,borderRadius:'10px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',minWidth:'220px'}), option:(b,s)=>({...b,fontSize:'13px',padding:'8px 12px',borderRadius:'6px',marginBottom:'2px',background:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':'#374151',cursor:'pointer'}) }}
                                    />
                                </div>
                                {/* Date */}
                                <div style={{padding:'10px 16px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#b0b0b0',letterSpacing:'1.2px',textTransform:'uppercase',marginBottom:'4px'}}>Date</div>
                                                                        <div className="inv-date-picker-wrap">
                                        <OrangeDatePicker value={selectedDate} onChange={val => { setSelectedDate(val); SalesService.updateInvoiceDetailMethod({ created_at: val, customer_id: selectedCustomer, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); }} className="inv-date-picker" popperPlacement="bottom-end" />
                                    </div>
                                </div>
                                {/* Action icons */}
                                <div style={{display:'flex',alignItems:'center',gap:'4px',padding:'0 10px',flexShrink:0,borderRight:'1px solid #f0f0f0'}}>
                                    <button onClick={() => sendEmail(props.id)} disabled={emailsend == 1} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: emailsend == 1 ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Email"
                                        onMouseEnter={e=>{if(emailsend!=1){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(emailsend!=1){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={emailsend == 1 ? "fa fa-spinner fa-spin" : "fa fa-envelope-o"} style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handlePrint} disabled={printing} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: printing ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Print"
                                        onMouseEnter={e=>{if(!printing){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(!printing){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={printing ? "fa fa-spinner fa-spin" : "fa fa-print"} style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handleDownload} disabled={downloading} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: downloading ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Download"
                                        onMouseEnter={e=>{if(!downloading){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(!downloading){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={downloading ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'13px'}}></i></button>
                                </div>
                                {/* Total */}
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>Total</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color:'#1e293b'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.total) : '0.00'}</span>
                                </div>
                                {/* Paid */}
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px',background:'#f0fdf4'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#22c55e',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>Paid</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color:'#22c55e'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.paid) : '0.00'}</span>
                                </div>
                                {/* Pending / Credit */}
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px',background: pagePaymentSummary?.credit > 0 ? '#fef2f2' : '#fff7ed'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color: pagePaymentSummary?.credit > 0 ? '#dc2626' : 'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>{pagePaymentSummary?.credit > 0 ? 'Credit' : 'Pending'}</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color: pagePaymentSummary?.credit > 0 ? '#dc2626' : 'rgb(234, 88, 12)'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.credit > 0 ? pagePaymentSummary.credit : pagePaymentSummary.pending) : '0.00'}</span>
                                </div>
                                {/* Pay Invoice */}
                                {pagePaymentSummary && (
                                <div style={{padding:'8px 14px',flexShrink:0,display:'flex',alignItems:'center'}}>
                                    <CustomerInvoicePaymentsPopup currency={props.currency} total={formatTwoDecimal(invoiceTotal)} customer={invoiceDetail} onFormChange={fetchPagePaymentSummary} {...props}/>
                                </div>
                                )}
                            </div>
                            ) : (
                            /* ── Tablet two rows ── */
                            <>
                            {/* Row 1: Customer + Date */}
                            <div style={{display:'flex',alignItems:'stretch'}}>
                                <div style={{flex:1,padding:'10px 16px',borderRight:'1px solid #f0f0f0',minWidth:'180px',display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'4px',marginBottom:'3px'}}>
                                        <i className="fa fa-user" style={{fontSize:'9px',color:'rgb(234, 88, 12)'}}></i>
                                        <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase'}}>Customer</span>
                                    </div>
                                    <Select
                                        value={customersList ? (() => { const cId = selectedCustomer || invoiceDetail.customer_id; const found = customersList.find(c => c.id == cId); return found ? {value: found.id, label: found.name} : null; })() : null}
                                        onChange={selected => { if (!selected) return; setSelectedCustomer(selected.value); SalesService.updateInvoiceDetailMethod({ created_at: selectedDate, customer_id: selected.value, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); }}
                                        options={customersList ? customersList.map(c => ({value: c.id, label: c.name})) : []}
                                        isSearchable placeholder="Select customer..." menuPortalTarget={document.body}
                                        styles={{ control:(b,s)=>({...b,minHeight:'34px',height:'34px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafafa',cursor:'pointer'}), valueContainer:b=>({...b,padding:'0 10px',height:'34px'}), indicatorsContainer:b=>({...b,height:'34px'}), singleValue:b=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}), placeholder:b=>({...b,fontSize:'12px',color:'#9ca3af'}), menuPortal:b=>({...b,zIndex:9999}), menu:b=>({...b,zIndex:9999,borderRadius:'10px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',border:'1px solid #f0f0f0',minWidth:'200px'}), option:(b,s)=>({...b,fontSize:'13px',padding:'8px 12px',borderRadius:'6px',marginBottom:'2px',background:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':'#374151',cursor:'pointer'}) }}
                                    />
                                </div>
                                <div style={{padding:'10px 16px',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#b0b0b0',letterSpacing:'1.2px',textTransform:'uppercase',marginBottom:'4px'}}>Date</div>
                                                                        <div className="inv-date-picker-wrap">
                                        <OrangeDatePicker value={selectedDate} onChange={val => { setSelectedDate(val); SalesService.updateInvoiceDetailMethod({ created_at: val, customer_id: selectedCustomer, id: invoiceDetail.id }).then(response => { if (response.data.success === true) { fetchInvoiceDetail({ getInvoiceId: props.id }); } }); }} className="inv-date-picker" popperPlacement="bottom-end" />
                                    </div>
                                </div>
                            </div>
                            {/* Row 2: Actions + Total + Paid + Pending + Pay
                                Actions on the left, metric blocks shrink to content (not flex:1) so the Pay Invoice
                                button on the right is never squeezed off the card. Spacer absorbs leftover width. */}
                            <div style={{display:'flex',alignItems:'center',borderTop:'1px solid #f0f0f0'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'3px',padding:'0 6px',flexShrink:0}}>
                                    <button onClick={() => sendEmail(props.id)} disabled={emailsend == 1} style={{width:'28px',height:'28px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: emailsend == 1 ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Email"
                                        onMouseEnter={e=>{if(emailsend!=1){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(emailsend!=1){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={emailsend == 1 ? "fa fa-spinner fa-spin" : "fa fa-envelope-o"} style={{fontSize:'12px'}}></i></button>
                                    <button onClick={handlePrint} disabled={printing} style={{width:'28px',height:'28px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: printing ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Print"
                                        onMouseEnter={e=>{if(!printing){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(!printing){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={printing ? "fa fa-spinner fa-spin" : "fa fa-print"} style={{fontSize:'12px'}}></i></button>
                                    <button onClick={handleDownload} disabled={downloading} style={{width:'28px',height:'28px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: downloading ? 'rgb(234, 88, 12)' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Download"
                                        onMouseEnter={e=>{if(!downloading){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';}}}
                                        onMouseLeave={e=>{if(!downloading){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={downloading ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'12px'}}></i></button>
                                </div>
                                {/* Spacer pushes metrics + Pay to the right edge */}
                                <div style={{flex:1}}></div>
                                <div style={{flex:'0 0 auto',padding:'6px 10px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',borderLeft:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>Total</span>
                                    <span style={{fontSize:'13px',fontWeight:'800',color:'#1e293b',whiteSpace:'nowrap'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.total) : '0.00'}</span>
                                </div>
                                <div style={{flex:'0 0 auto',padding:'6px 10px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',background:'#f0fdf4',borderLeft:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#22c55e',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>Paid</span>
                                    <span style={{fontSize:'13px',fontWeight:'800',color:'#22c55e',whiteSpace:'nowrap'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.paid) : '0.00'}</span>
                                </div>
                                <div style={{flex:'0 0 auto',padding:'6px 10px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',background: pagePaymentSummary?.credit > 0 ? '#fef2f2' : '#fff7ed',borderLeft:'1px solid #f0f0f0',borderRight:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color: pagePaymentSummary?.credit > 0 ? '#dc2626' : 'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>{pagePaymentSummary?.credit > 0 ? 'Credit' : 'Pending'}</span>
                                    <span style={{fontSize:'13px',fontWeight:'800',color: pagePaymentSummary?.credit > 0 ? '#dc2626' : 'rgb(234, 88, 12)',whiteSpace:'nowrap'}}>{props.currency} {pagePaymentSummary ? formatTwoDecimal(pagePaymentSummary.credit > 0 ? pagePaymentSummary.credit : pagePaymentSummary.pending) : '0.00'}</span>
                                </div>
                                {pagePaymentSummary && (
                                <div style={{padding:'6px 10px',flexShrink:0,display:'flex',alignItems:'center'}}>
                                    <CustomerInvoicePaymentsPopup currency={props.currency} total={formatTwoDecimal(invoiceTotal)} customer={invoiceDetail} onFormChange={fetchPagePaymentSummary} {...props}/>
                                </div>
                                )}
                            </div>
                            </>
                            )}
                            </div>{/* end right side */}
                        </div>

                        {/* Action buttons row */}
                        <style>{`
                            .inv-action-btn{display:inline-flex;align-items:center;gap:5px;text-decoration:none;font-size:12px;font-weight:600;padding:0 14px;height:36px;border-radius:8px;cursor:pointer;outline:none;box-shadow:none;transition:all 0.2s;}
                            .inv-action-btn-filled{background:rgb(234, 88, 12);color:#fff;border:none;line-height:36px;}
                            .inv-action-btn-filled:hover{background:#e0650f;color:#fff;text-decoration:none;}
                            .inv-action-btn-outline{background:#fff;color:rgb(234, 88, 12);border:1.5px solid rgb(234, 88, 12);line-height:34px;}
                            .inv-action-btn-outline:hover{background:rgb(234, 88, 12);color:#fff;text-decoration:none;}
                            .invoice-scroll-inner{}.invoice-full-scroll{}.invoice-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
                            @media (max-width: 767px) { .mt-1, .my-1 { margin-top: 1rem !important; } }
                        `}</style>

                    </div>
                </div>
				
				{/*<div className="col-xl-4 col-lg-12 col-12 col-md-6 mt-1">
                    <div className="card stretchclassName bg-success" >
                        <div className="card-content">
                            <div className="media align-items-stretch ">
                                <div className="p-2 text-center bg-success bg-darken-2">
                                    <i className="icon-basket-loaded font-large-2 white"></i>
                                </div>
                                <div className="p-2 white media-body">
                                    <h5>Print & Downloads</h5>
                                </div>
                            </div>
							<div className="row mt-2">
								<div className="col-lg-4 col-md-6 text-center">
									<a className="text-white" href={"/data_entry/sales_entry/invoice/invoicedownload/"+props.id} ><i className="fa fa-file-pdf-o fa-lg font-large-1"></i><br /><b>INVOICE PDF</b></a>
								</div>
								<div className="col-lg-4 col-md-6 text-center">
									<a className="text-white" href={"/data_entry/sales_entry/invoice/invoiceview/"+props.id} target="_blank"><i className="fa fa-print fa-lg font-large-1"></i><br /><b>INVOICE PRINT</b></a>
								</div>
								<div className="col-lg-4 text-center">
									<a className="text-white" href={"/data_entry/sales_entry/invoice/invoiceview-delivery/"+props.id} target="_blank"><i className="fa fa-print fa-lg font-large-1"></i><br /><b>INVOICE DELIVERY</b></a>
								</div>
							</div>
                        </div>
                    </div>
                </div>*/}
                {/*
                <div className="col-xl-4 col-lg-6 col-12 col-md-12 mt-1">
                    <div className="card stretchclassName bg-warning">
                        <div className="card-content">
                            <div className="media align-items-stretch">
                                <div className="p-2 text-center bg-warning bg-darken-2">
                                    <i className="icon-pencil font-large-2 white"></i>

                                </div>
                                <div className="p-2 white media-body">
                                    <h5 className="text-bold-400 mb-0 invoice-area" onClick={() => setShowInvoicePopup(true)}>Change Customer
									<br /></h5><br />
									<h5><a className="text-white" href="/data_entry/sales_entry/statements/view" target="_blank"><u>->Check Statement</u></a></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				*/}
				{/*<div className="col-12 mt-1">
					<CustomerInvoicePaymentsPopup
						currency={props.currency} 
						total={formatTwoDecimal(invoiceTotal)}
						customer={invoiceDetail}
						{...props}/>
				</div>*/}

                <Modal show={showInvoicePopup} onHide={() => setShowInvoicePopup(false)} centered>
                  <Modal.Header style={{borderBottom:'2px solid rgb(234, 88, 12)', paddingBottom:'12px'}}>
                    <Modal.Title style={{fontSize:'16px', fontWeight:'700', color:'rgb(234, 88, 12)', letterSpacing:'0.3px'}}>Edit Invoice</Modal.Title>
                  </Modal.Header>
                  <Modal.Body>
                  <form onSubmit={handleEditInvoice}>
                    <Form.Group className="mb-3">
                      <Form.Label>Date</Form.Label>
                      <input type="date" value={selectedDate} onChange={e => setSelectedDate(e.target.value)} className="form-control orange-input" />
                    </Form.Group>
                    <Form.Group className="mb-3">
                      <Form.Label>Customer</Form.Label>
                      <select className="form-control orange-input" defaultValue={invoiceDetail.customer_id} onChange={e => setSelectedCustomer(e.target.value)}>
                        {customersList && customersList.map(c => (
                          <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                      </select>
                    </Form.Group>
                    <div style={{marginTop:'16px', display:'flex', justifyContent:'flex-end', gap:'8px'}}>
                      <button type="button" onClick={() => setShowInvoicePopup(false)} style={{background:'#fff', border:'1px solid #ddd', color:'#555', padding:'7px 20px', borderRadius:'6px', fontSize:'13px', cursor:'pointer'}}>Cancel</button>
                      <button type="submit" style={{background:'rgb(234, 88, 12)', border:'none', color:'#fff', padding:'7px 20px', borderRadius:'6px', fontSize:'13px', fontWeight:'600', cursor:'pointer'}}>Update</button>
                    </div>
                  </form>
                  </Modal.Body>
                </Modal>

                {/* <div className="col-xl-3 col-lg-6 col-12">
                    <div className="card stretchclassName bg-danger">
                        <div className="card-content">
                            <div className="media align-items-stretch">
                                <div className="p-2 text-center bg-danger bg-darken-2">
                                    <i className="icon-user font-large-2 white"></i>
                                </div>
                                <div className="p-2 white media-body">
                                    <h5>Supplier Payment</h5>
                                    <h5 className="text-bold-400 mb-0"><i className="feather icon-arrow-down"></i> 1,238</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> */}
            </div>
            )}


                        <div className="row mt-1">
				{isAnySelected(rowsData) === true &&
					<div className="col-12 mb-1">
						<button onClick={(e) => deleteSelected(e)} className="btn btn-danger btn-sm"><i className="fa fa-trash"></i> Remove</button>
					</div>
				}
				<div className='col-lg-12 sm-p-0' key={forceDesktop ? 'table-view' : 'card-view'}>
				{/* Loader while invoice products are being fetched — prevents empty/unfilled UI flash on page load.
				    minHeight reserves enough vertical space so the page is tall enough to need a scrollbar even while
				    loading. Without this, the page starts short → no scrollbar → window.innerWidth is wider; after
				    rows render the scrollbar appears → window.innerWidth shrinks by ~15px → header card visibly shifts
				    horizontally on load. Reserving the height keeps the scrollbar present throughout, so the header
				    card never reflows. */}
				{isLoading ? (
					<div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',padding:'56px 24px',textAlign:'center',color:'#94a3b8',fontSize:'14px',minHeight:'500px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center'}}>
						<i className="fa fa-spinner fa-spin" style={{fontSize:'28px',marginBottom:'12px',display:'block',color:'rgb(234, 88, 12)',opacity:0.85}}></i>
						Loading invoice...
					</div>
				) : effectiveWidth >= 768 ? (<>
					<div style={forceScroll ? {paddingLeft:'15px',paddingRight:'15px'} : {}}>
					{/* Outer card — overflow hidden so border-radius stays clean; header sits outside the horizontal-scroll viewport */}
					<div style={{background:'#fff',borderRadius: forceScroll ? '14px' : '16px',border:'1px solid #eaecf2',boxShadow: forceScroll ? '0 2px 12px rgba(0,0,0,0.08)' : '0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden',position:'relative'}}>
					{forceScroll && (
						/* Header — NOT inside the horizontal scroll viewport, so product count + Card/List stay fixed while the table body scrolls */
						<div style={{padding:'8px 16px',display:'flex',alignItems:'center',justifyContent:'space-between',borderBottom:'1.5px solid #f0f0f0',background:'#fff',position:'relative',zIndex:2}}>
							<span style={{display:'inline-flex',alignItems:'center',gap:'4px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'20px',padding:'2px 10px',fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)'}}><i className="fa fa-cubes" style={{fontSize:'9px'}}></i>{rowsData.filter(r => r.fieldToggle === 'checked').length} products</span>
							<div style={{display:'inline-flex',borderRadius:'8px',overflow:'hidden',padding:'3px',gap:'3px',background:'#f1f5f9'}}>
								<button onClick={() => { localStorage.setItem('ts_invoice_view','off');setForceDesktop(false);setShowColFilter(false); }} style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background:'transparent',cursor:'pointer',boxShadow:'none',outline:'none'}}>
									<i className="fa fa-th-large" style={{fontSize:'10px',color:'#94a3b8'}}></i>
									<span style={{fontSize:'11px',fontWeight:'600',color:'#94a3b8'}}>Card</span>
								</button>
								<button style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background:'#fff',cursor:'default',boxShadow:'0 1px 3px rgba(0,0,0,0.1)',outline:'none'}}>
									<i className="fa fa-list" style={{fontSize:'10px',color:'rgb(234, 88, 12)'}}></i>
									<span style={{fontSize:'11px',fontWeight:'600',color:'rgb(234, 88, 12)'}}>List</span>
								</button>
							</div>
						</div>
					)}
					{/* Horizontal-scroll viewport — wraps ONLY the table; header above + footer/totals below stay fixed within the card */}
					<div style={forceScroll ? {overflowX:'auto',overflowY:'hidden',WebkitOverflowScrolling:'touch'} : {}}>
					<div className="invoice-full-scroll" style={forceScroll ? {minWidth:'960px'} : {}}>
						{/* Saved products table */}
						{rowsData.filter(r => r.fieldToggle === 'checked' || (r.fieldToggle === '' && r.invoiceproductid !== 0)).length > 0 && (
							<>
							<div className="invoice-table-scroll" style={{overflowX: (!forceScroll && effectiveWidth < 1200) ? 'auto' : 'visible', position:'relative'}}>
							<div className="invoice-scroll-inner" style={{minWidth: effectiveWidth < 1200 ? '700px' : 'auto'}}>
								<table style={{width:'100%',borderCollapse:'collapse'}}>
									<thead>
										<tr>
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',width:'40px'}}>#</th>
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'}}>Product</th>
											{(!forceScroll || visibleCols.remarks) && <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'}}>Remarks</th>}
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Qty</th>
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Price</th>
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Total</th>
											<th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',width:'90px'}}></th>
										</tr>
									</thead>
									<tbody>
										{(() => {
											const savedRows = rowsData.filter(r => r.fieldToggle === 'checked' || (r.fieldToggle === '' && r.invoiceproductid !== 0));
											const isLastProduct = savedRows.length <= 1;
											return savedRows.map((data, idx) => {
												const origIdx = rowsData.indexOf(data);
												const isEditing = data.fieldToggle === '' && data.invoiceproductid !== 0;
												const td = {padding:'10px 14px',borderBottom:'1px solid #f3f4f8',fontSize:'13px'};
												return (
													<tr key={'saved_'+idx} style={{background: idx%2===0 ? '#fff' : '#fcfcfd'}}>
														<td style={{...td,color:'#94a3b8'}}>{idx+1}</td>
														<td style={{...td,fontWeight:'600',color:'#1e293b'}}>
															<div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'nowrap'}}>
																<span style={{whiteSpace:'nowrap'}}>{data.product?.label || ''}</span>
																{isEditing && data._editNoSupplier ? (
																	data.supplier && data.supplier.length > 0 ? (<>
																		<div style={{minWidth:'160px',flex:'0 0 auto'}}>
																		<Select
																			key={'edit_supplier_'+origIdx}
																			isMulti={false}
																			menuPortalTarget={document.body}
																			options={data.supplier}
																			value={data.supplier_id && data.supplier_id.value ? data.supplier_id : null}
																			onChange={(evnt) => handleSupplierChange(origIdx, data.product, evnt)}
																			placeholder="+ Add Supplier"
																			formatOptionLabel={(option, { context }) => {
																				if (context === 'value') return <span style={{fontSize:'12px'}}>{option.supplier_name || option.label}</span>;
																				if (!option.value) return <span style={{color:'#aaa',fontSize:'12px'}}>-- Select Supplier --</span>;
																				const parts = (option.label || '').split('|');
																				const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
																				const remark = parts.filter(p => p && !p.startsWith('Qty:') && !p.startsWith('P:') && !p.includes('...')).join('').trim();
																				return (<div><div style={{fontWeight:'600',fontSize:'12px',color:'inherit'}}>{option.supplier_name || option.label}</div>{(qty || remark) && <div style={{fontSize:'11px',color:'inherit',marginTop:'1px'}}>{qty ? 'Qty: '+qty : ''}{qty && remark ? ' · ' : ''}{remark && <span style={{fontStyle:'italic',opacity:0.8}}>{remark}</span>}</div>}</div>);
																			}}
																			styles={{
																				control:(b,s)=>({...b,minHeight:'28px',height:'28px',fontSize:'12px',fontWeight:'500',borderRadius:'6px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #e2e8f0',boxShadow:'none',background:s.isFocused?'#fff':'#fafafa'}),
																				valueContainer:b=>({...b,padding:'0 6px',height:'28px'}),
																				indicatorsContainer:b=>({...b,height:'28px'}),
																				dropdownIndicator:b=>({...b,padding:'0 4px'}),
																				menuPortal:b=>({...b,zIndex:9999}),
																				option:(b,s)=>({...b,fontSize:'12px',padding:'6px 10px',backgroundColor:!s.data.value?'#fff':s.isSelected?'#c2410c':s.isFocused?'rgb(234, 88, 12)':'#fff',color:!s.data.value?'#aaa':(s.isSelected||s.isFocused)?'#fff':'#334155'}),
																			}}
																		/>
																		</div>
																		<div style={{flexShrink:0}}><AddStock key={'addstock_edit_'+origIdx} onSaveStock={onSaveStock} show={false} product={data.product} index={origIdx} apiKey="" invoiceId={invoiceDetail.id} /></div>
																	</>) : (
																		<span style={{fontSize:'11px',color:'#94a3b8'}}><i className="fa fa-spinner fa-spin" style={{fontSize:'10px',marginRight:'4px'}}></i>Loading...</span>
																	)
																) : (
																	(data.supplier_id && data.supplier_id.value ? <span style={{fontSize:'11px',color:'#94a3b8'}}>{data.supplier_id.supplier_name || data.supplier_id.label}</span> : <span style={{fontSize:'11px',color:'#d1d5db',fontStyle:'italic'}}>N/A</span>)
																)}
															</div>
														</td>
														{(!forceScroll || visibleCols.remarks) && <td style={{...td,color:'#64748b',fontStyle:'italic'}}>{(data.fieldToggle === '' && data.invoiceproductid !== 0) ? <input type="text" defaultValue={data.remarks || '' } onChange={e => handleRemarksChange(origIdx, e)} placeholder="—" style={{...{height:'30px',borderRadius:'6px',border:'1px solid #e2e8f0',fontSize:'13px',padding:'0 8px',outline:'none',fontWeight:'600',color:'#1e293b',background:'#fff',textAlign:'right',transition:'border 0.2s',fontStyle:'normal'},width:'100%',textAlign:'left'}} onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e => e.target.style.borderColor='#e2e8f0'} /> : (data.remarks || '—')}</td>}
														<td style={{...td,fontWeight:'500',textAlign:'right'}}>{(data.fieldToggle === '' && data.invoiceproductid !== 0) ? <input type="number" min="1" step="1" defaultValue={data.quantity} onChange={e => handleQtyChange(origIdx, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} style={{...{height:'30px',borderRadius:'6px',border:'1px solid #e2e8f0',fontSize:'13px',padding:'0 8px',outline:'none',fontWeight:'600',color:'#1e293b',background:'#fff',textAlign:'right',transition:'border 0.2s'},width:'70px'}} onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e => e.target.style.borderColor='#e2e8f0'} /> : data.quantity}</td>
														<td style={{...td,fontWeight:'500',textAlign:'right'}}>{(data.fieldToggle === '' && data.invoiceproductid !== 0) ? <input type="number" min="0" step="any" defaultValue={data.price} onChange={e => handlePriceChange(origIdx, e)} style={{...{height:'30px',borderRadius:'6px',border:'1px solid #e2e8f0',fontSize:'13px',padding:'0 8px',outline:'none',fontWeight:'600',color:'#1e293b',background:'#fff',textAlign:'right',transition:'border 0.2s'},width:'80px'}} onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e => e.target.style.borderColor='#e2e8f0'} /> : props.currency + ' ' + formatTwoDecimal(data.price)}</td>
														<td style={{...td,fontWeight:'700',color:(data.fieldToggle === '' && data.invoiceproductid !== 0) ? 'rgb(234, 88, 12)' : '#1e293b',textAlign:'right'}}>{props.currency} {formatTwoDecimal(data.totalPrice)}</td>
														<td style={{...td,textAlign:'center',width:'120px'}}>
{(data.fieldToggle === '' && data.invoiceproductid !== 0) ? (
<div style={{display:'flex',gap:'4px',justifyContent:'center'}}>
<button onClick={(evnt) => handleUpdateChange(origIdx, evnt)} style={{height:'28px',padding:'0 12px',borderRadius:'6px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',cursor:'pointer',fontSize:'11px',fontWeight:'700',display:'flex',alignItems:'center',justifyContent:'center',gap:'4px'}} title="Save"><i className="fa fa-check" style={{fontSize:'10px'}}></i> Save</button>
<button onClick={() => { const r = [...rowsData]; r[origIdx]['fieldToggle'] = 'checked'; if(r[origIdx]['_origSupplierId'] !== undefined) { r[origIdx]['supplier_id'] = r[origIdx]['_origSupplierId']; } if(r[origIdx]['_origPrice'] !== undefined) { r[origIdx]['price'] = r[origIdx]['_origPrice']; r[origIdx]['totalPrice'] = r[origIdx]['_origTotalPrice']; } delete r[origIdx]['_editNoSupplier']; delete r[origIdx]['_origSupplierId']; delete r[origIdx]['_origPrice']; delete r[origIdx]['_origTotalPrice']; setRowsData(r); }} style={{height:'28px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:'#94a3b8',cursor:'pointer',fontSize:'11px',fontWeight:'600',display:'flex',alignItems:'center',justifyContent:'center'}} title="Cancel">Cancel</button>
					</div>
) : (
<div style={{display:'flex',gap:'4px',justifyContent:'center'}}>
<button onClick={(evnt) => handleEditChange(origIdx, evnt)} style={{height:'28px',padding:'0 12px',borderRadius:'6px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',cursor:'pointer',fontSize:'11px',fontWeight:'700',display:'flex',alignItems:'center',justifyContent:'center',gap:'4px'}} title="Edit"><i className="fa fa-pencil" style={{fontSize:'10px'}}></i> Edit</button>
{!isLastProduct && (
<button onClick={() => deleteTableRows(origIdx, data.invoiceproductid)} style={{height:'28px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:'#ef4444',cursor:'pointer',fontSize:'11px',fontWeight:'600',display:'flex',alignItems:'center',justifyContent:'center',gap:'4px'}} title="Delete"><i className="fa fa-trash-o" style={{fontSize:'10px'}}></i> Delete</button>
)}
</div>
)}
</td>
													</tr>
												);
											});
										})()}
									</tbody>
								</table>
							</div>
							</div>
							</>
						)}

						{/* Add product row - orange gradient */}
						{(() => {
							const lastRow = rowsData[rowsData.length - 1];
							const isNewRow = lastRow && lastRow.fieldToggle !== 'checked' && lastRow.invoiceproductid === 0;
							const addIdx = isNewRow ? rowsData.length - 1 : -1;
							const addRow = isNewRow ? lastRow : null;
							const lblSt = {fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'};
							const errSt = {color:'#ef4444',fontSize:'9px',fontWeight:'600',position:'absolute',left:0,bottom:'-14px'};
							const inpSt = {height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b',boxSizing:'border-box'};
							const selSt = {control:(base,state)=>({...base,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',border:state.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e2e8f0',boxShadow:state.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:state.isFocused?'#fff':'#f8fafc'}),menuPortal:(base)=>({...base,zIndex:9999}),option:(base,state)=>({...base,fontSize:'13px',backgroundColor:!state.data.value?'#fff':state.isSelected?'#c2410c':state.isFocused?'rgb(234, 88, 12)':'#fff',color:!state.data.value?'#aaa':(state.isSelected||state.isFocused)?'#fff':'#334155'})};

							if (!isNewRow) { if (isLoading || effectiveWidth >= 768) return null;
								return (
									<div style={{padding:'16px 20px',background:'linear-gradient(135deg,#fff7ed,#fff)',borderBottom:'1px solid #eef2f7',textAlign:'center'}}>
										<button onClick={() => addTableRows()} style={{height:'40px',padding:'0 24px',borderRadius:'10px',border:'2px dashed rgb(234, 88, 12)',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'6px'}}>
											<i className="fa fa-plus"></i> Add Product
										</button>
									</div>
								);
							}

							const fmtOpt = (option, { context, selectValue }) => {
								if (context === 'value') return <span>{option.supplier_name || option.label}</span>;
								if (!option.value) return <span style={{color:'#aaa'}}>-- Select Supplier --</span>;
								const parts = (option.label || '').split('|');
								const qty2 = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
								const remark = parts.filter(p => p && !p.startsWith('Qty:') && !p.startsWith('P:') && !p.includes('...')).join('').trim();
								const isSelected = selectValue && selectValue.some(v => v.value === option.value);
								const subColor = isSelected ? 'rgba(255,255,255,0.75)' : 'inherit';
								return (<div><div style={{fontWeight:'500',fontSize:'13px'}}>{option.supplier_name || option.label}</div>{(qty2 || remark) && <div style={{fontSize:'11px',color:subColor,marginTop:'1px'}}>{qty2 ? 'Qty: '+qty2 : ''}{qty2 && remark ? ' · ' : ''}{remark && <span style={{fontStyle:'italic',opacity:0.8}}>{remark}</span>}</div>}</div>);
							};

							return (
								<div style={{padding: addRow && addRow.fieldErrors && Object.keys(addRow.fieldErrors).length > 0 ? '14px 16px 24px' : '14px 16px',background:'#fff',borderBottom:'1px solid #eef2f7'}}>
																		{effectiveWidth >= 1200 ? (<>
									<div style={{display:'flex',alignItems:'flex-end',gap:'10px'}}>
										<div style={{flex: showSuppliers ? 1 : 1.6, minWidth:0, position:'relative'}}>
											<div style={{display:'flex',alignItems:'center',justifyContent:'flex-start',gap:'8px',marginBottom:'6px'}}>
												<span style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase'}}>Product</span><AddProduct existingProducts={productsList} onCreated={(item, reused) => onProductCreated(item, reused, addIdx)} />
												<div style={{display:'flex',alignItems:'center',gap:'5px',marginLeft:'auto'}}>
													<span style={{fontSize:'10px',fontWeight:'600',color:showSuppliers?'rgb(234, 88, 12)':'#94a3b8',transition:'color 0.2s'}}>Supplier</span>
													<div onClick={savingToggle ? undefined : handleSupplierToggle} style={{position:'relative',width:'30px',height:'17px',cursor:savingToggle?'not-allowed':'pointer',display:'block',opacity:savingToggle?0.6:1,flexShrink:0,background:showSuppliers?'rgb(234, 88, 12)':'#d1d5db',borderRadius:'17px',transition:'background 0.2s'}}>
														<span style={{position:'absolute',width:'11px',height:'11px',left:showSuppliers?'16px':'3px',bottom:'3px',background:'#fff',borderRadius:'50%',boxShadow:'0 1px 3px rgba(0,0,0,0.25)',transition:'left 0.2s',display:'block'}}></span>
													</div>
												</div>
											</div>
											<Select key={'add_product_'+addIdx} options={allProducts} menuPortalTarget={document.body}
												styles={selSt} value={addRow.product || null}
												onChange={(evnt) => handleProductChange(addIdx, evnt)} placeholder="Select Product" />
											{addRow.fieldErrors?.product && <div style={errSt}>{addRow.fieldErrors.product}</div>}
										</div>
										{showSuppliers && (<div style={{flex:1,minWidth:0,position:'relative'}}>
											<div style={lblSt}>Supplier</div>
											<div style={{display:'flex',gap:'4px',alignItems:'center'}}>
												<div style={{flex:1,minWidth:0}}>
													<Select key={'add_supplier_'+addIdx} isMulti={false} menuPortalTarget={document.body}
														styles={selSt} options={addRow.supplier} value={addRow.supplier_id || null}
														formatOptionLabel={fmtOpt}
														onChange={(evnt) => handleSupplierChange(addIdx, addRow.product, evnt)} placeholder="Select Supplier" />
												</div>
												{addRow.product && typeof addRow.product === 'object' && addRow.product.value ? (
													<div style={{flexShrink:0}}><AddStock key={"addstock_top"} onSaveStock={onSaveStock} show={false} product={addRow.product} index={addIdx} apiKey="" invoiceId={invoiceDetail.id} /></div>
												) : null}
											</div>
											{addRow.fieldErrors?.supplier && <div style={errSt}>{addRow.fieldErrors.supplier}</div>}
										</div>)}
										<div style={{flex:0.6,minWidth:0}}>
											<div style={lblSt}>Remarks</div>
											<input type="text" value={addRow.remarks || ''} onChange={e => handleRemarksChange(addIdx, e)} placeholder="Remarks..." style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
										</div>
										<div style={{width:'80px',position:'relative'}}>
											<div style={lblSt}>Quantity</div>
											<input type="number" min="1" step="1" value={addRow.quantity || ''} onChange={e => handleQtyChange(addIdx, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} placeholder="Qty" style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
											{addRow.fieldErrors?.quantity && <div style={errSt}>{addRow.fieldErrors.quantity}</div>}
										</div>
										<div style={{width:'90px',position:'relative'}}>
											<div style={lblSt}>Unit Price</div>
											<input type="number" min="0" step="any" value={addRow.price != null && addRow.price !== '' ? addRow.price : ''} onChange={e => handlePriceChange(addIdx, e)} placeholder="Price" style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
											{addRow.fieldErrors?.price && <div style={errSt}>{addRow.fieldErrors.price}</div>}
										</div>
										<div style={{minWidth:'70px',textAlign:'right'}}>
											<div style={lblSt}>Price</div>
											<div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px',whiteSpace:'nowrap'}}>
												{props.currency} {formatTwoDecimal(addRow.totalPrice || 0)}
											</div>
										</div>
										<button onClick={(evnt) => handleToogleChange(addIdx, evnt)} disabled={isSavingNew}
											style={{height:'40px',padding:'0 20px',borderRadius:'10px',border:'none',outline:'none',
												background:'rgb(234, 88, 12)',color:'#fff',
												fontSize:'13px',fontWeight:'700',cursor: isSavingNew ? 'not-allowed' : 'pointer',
												display:'flex',alignItems:'center',gap:'6px',
												boxShadow:'0 2px 8px rgba(234,88,12,0.3)',opacity: isSavingNew ? 0.7 : 1,flexShrink:0}}>
											<i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-plus"}></i> {isSavingNew ? 'Saving...' : 'Add'}
										</button>
									</div>
									{(addRow.stockWarning || addRow.qtyWarning) && <div style={{marginTop:'8px',fontSize:'12px',fontWeight:'600',color:'#b45309',background:'#fffbeb',border:'1px solid #fcd34d',borderRadius:'8px',padding:'8px 14px',display:'inline-flex',alignItems:'center',gap:'6px',width:'auto'}}><i className="fa fa-exclamation-triangle" style={{fontSize:'12px',flexShrink:0}}></i><span>{addRow.stockWarning || addRow.qtyWarning}</span></div>}
									</>)
									 : (
									<>
									{/* Row 1: Product + Supplier */}
									<div style={{display:'flex',gap:'10px',marginBottom:'12px'}}>
										<div style={{flex:1,minWidth:0,position:'relative'}}>
											<div style={{display:'flex',alignItems:'center',justifyContent:'flex-start',gap:'8px',marginBottom:'6px'}}>
												<span style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase'}}>Product</span><AddProduct existingProducts={productsList} onCreated={(item, reused) => onProductCreated(item, reused, addIdx)} />
												<div style={{display:'flex',alignItems:'center',gap:'5px'}}>
													<span style={{fontSize:'10px',fontWeight:'600',color:showSuppliers?'rgb(234, 88, 12)':'#94a3b8',transition:'color 0.2s'}}>Supplier</span>
													<div onClick={savingToggle ? undefined : handleSupplierToggle} style={{position:'relative',width:'30px',height:'17px',cursor:savingToggle?'not-allowed':'pointer',display:'block',opacity:savingToggle?0.6:1,flexShrink:0,background:showSuppliers?'rgb(234, 88, 12)':'#d1d5db',borderRadius:'17px',transition:'background 0.2s'}}>
														<span style={{position:'absolute',width:'11px',height:'11px',left:showSuppliers?'16px':'3px',bottom:'3px',background:'#fff',borderRadius:'50%',boxShadow:'0 1px 3px rgba(0,0,0,0.25)',transition:'left 0.2s',display:'block'}}></span>
													</div>
												</div>
											</div>
											<Select key={'add_product_'+addIdx} options={allProducts} menuPortalTarget={document.body}
												styles={selSt} value={addRow.product || null}
												onChange={(evnt) => handleProductChange(addIdx, evnt)} placeholder="Select Product" />
											{addRow.fieldErrors?.product && <div style={errSt}>{addRow.fieldErrors.product}</div>}
										</div>
										{showSuppliers && (<div style={{flex:1,minWidth:0,position:'relative'}}>
											<div style={lblSt}>Supplier</div>
											<div style={{display:'flex',gap:'4px',alignItems:'center'}}>
												<div style={{flex:1,minWidth:0}}>
													<Select key={'add_supplier_'+addIdx} isMulti={false} menuPortalTarget={document.body}
														styles={selSt} options={addRow.supplier} value={addRow.supplier_id || null}
														formatOptionLabel={fmtOpt}
														onChange={(evnt) => handleSupplierChange(addIdx, addRow.product, evnt)} placeholder="Select Supplier" />
												</div>
												{addRow.product && typeof addRow.product === 'object' && addRow.product.value ? (
													<div style={{flexShrink:0}}><AddStock key={"addstock_top"} onSaveStock={onSaveStock} show={false} product={addRow.product} index={addIdx} apiKey="" invoiceId={invoiceDetail.id} /></div>
												) : null}
											</div>
											{addRow.fieldErrors?.supplier && <div style={errSt}>{addRow.fieldErrors.supplier}</div>}
										</div>)}
									</div>
									{/* Row 2: Remarks + Qty + Unit Price + Price + Add */}
									<div style={{display:'flex',alignItems:'flex-end',gap:'10px'}}>
										<div style={{flex:1,minWidth:0}}>
											<div style={lblSt}>Remarks</div>
											<input type="text" value={addRow.remarks || ''} onChange={e => handleRemarksChange(addIdx, e)} placeholder="Remarks..." style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
										</div>
										<div style={{width:'65px',position:'relative'}}>
											<div style={lblSt}>Quantity</div>
											<input type="number" min="1" step="1" value={addRow.quantity || ''} onChange={e => handleQtyChange(addIdx, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} placeholder="Qty" style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
											{addRow.fieldErrors?.quantity && <div style={errSt}>{addRow.fieldErrors.quantity}</div>}
										</div>
										<div style={{width:'70px',position:'relative'}}>
											<div style={lblSt}>Unit Price</div>
											<input type="number" min="0" step="any" value={addRow.price != null && addRow.price !== '' ? addRow.price : ''} onChange={e => handlePriceChange(addIdx, e)} placeholder="Price" style={inpSt}
												onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
												onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
											{addRow.fieldErrors?.price && <div style={errSt}>{addRow.fieldErrors.price}</div>}
										</div>
										<div style={{minWidth:'70px',textAlign:'right'}}>
											<div style={lblSt}>Price</div>
											<div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px',whiteSpace:'nowrap'}}>
												{props.currency} {formatTwoDecimal(addRow.totalPrice || 0)}
											</div>
										</div>
										<button onClick={(evnt) => handleToogleChange(addIdx, evnt)} disabled={isSavingNew}
											style={{height:'40px',padding:'0 20px',borderRadius:'10px',border:'none',outline:'none',
												background:'rgb(234, 88, 12)',color:'#fff',
												fontSize:'13px',fontWeight:'700',cursor: isSavingNew ? 'not-allowed' : 'pointer',
												display:'flex',alignItems:'center',gap:'6px',
												boxShadow:'0 2px 8px rgba(234,88,12,0.3)',opacity: isSavingNew ? 0.7 : 1,flexShrink:0}}>
											<i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-plus"}></i> {isSavingNew ? 'Saving...' : 'Add'}
										</button>
									</div>
									</>
									)}
									{addRow.rowError && <div style={{color:'#ef4444',fontSize:'12px',fontWeight:'600',marginTop:'8px'}}><i className="fa fa-exclamation-circle" style={{marginRight:'4px'}}></i>{addRow.rowError}</div>}
								</div>
							);
						})()}

											</div>
											</div>
											</div>
											</div>
				</>
				) : (
					<div style={{paddingLeft:'15px',paddingRight:'15px'}}>
					  <div style={{background:'#fff',borderRadius:'14px',boxShadow:'0 2px 12px rgba(0,0,0,0.08)',overflow:'visible'}}>
						{/* Section header — product count + Card/List */}
						<div style={{padding:'8px 16px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1.5px solid #f0f0f0'}}>
							<span style={{display:'inline-flex',alignItems:'center',gap:'4px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'20px',padding:'2px 10px',fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)'}}><i className="fa fa-cubes" style={{fontSize:'9px'}}></i>{rowsData.filter(r => r.fieldToggle === 'checked').length} products</span>
							<div style={{display:'inline-flex',borderRadius:'8px',overflow:'hidden',padding:'3px',gap:'3px',background:'#f1f5f9'}}>
								<button onClick={() => { if(forceDesktop){localStorage.setItem('ts_invoice_view','off');setForceDesktop(false);setShowColFilter(false);} }} style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background: !forceDesktop?'#fff':'transparent',cursor:'pointer',boxShadow: !forceDesktop?'0 1px 3px rgba(0,0,0,0.1)':'none',outline:'none'}}>
									<i className="fa fa-th-large" style={{fontSize:'10px',color: !forceDesktop?'rgb(234, 88, 12)':'#94a3b8'}}></i>
									<span style={{fontSize:'11px',fontWeight:'600',color: !forceDesktop?'rgb(234, 88, 12)':'#94a3b8'}}>Card</span>
								</button>
								<button onClick={() => { if(!forceDesktop){localStorage.setItem('ts_invoice_view','on');setForceDesktop(true);} }} style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background: forceDesktop?'#fff':'transparent',cursor:'pointer',boxShadow: forceDesktop?'0 1px 3px rgba(0,0,0,0.1)':'none',outline:'none'}}>
									<i className="fa fa-list" style={{fontSize:'10px',color: forceDesktop?'rgb(234, 88, 12)':'#94a3b8'}}></i>
									<span style={{fontSize:'11px',fontWeight:'600',color: forceDesktop?'rgb(234, 88, 12)':'#94a3b8'}}>List</span>
								</button>
							</div>
						</div>

						<div style={{padding:'12px 10px 10px'}}>
						{rowsData.map((mdata, index) => {
							const { product, quantity, supplier, supplier_id, remarks, price, totalPrice, fieldToggle, invoiceproductid } = mdata;
							if (fieldToggle === '' && invoiceproductid === 0) return null;
							if (mobileSearch && product && typeof product === 'object' && product.label && !product.label.toLowerCase().includes(mobileSearch.toLowerCase())) return null;
							if (fieldToggle === '' && invoiceproductid !== 0) {
								return (() => { const lbl = {fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'5px',display:'block'}; const cancelEdit = () => { const r = [...rowsData]; r[index]['fieldToggle'] = 'checked'; if(r[index]['_origSupplierId'] !== undefined) { r[index]['supplier_id'] = r[index]['_origSupplierId']; } if(r[index]['_origPrice'] !== undefined) { r[index]['price'] = r[index]['_origPrice']; r[index]['totalPrice'] = r[index]['_origTotalPrice']; } delete r[index]['_editNoSupplier']; delete r[index]['_origSupplierId']; delete r[index]['_origPrice']; delete r[index]['_origTotalPrice']; setRowsData(r); }; return (
									<div key={'card_edit_'+index} style={{position:'fixed',left:0,right:0,top:0,bottom:0,zIndex:6000,display:'flex',flexDirection:'column',justifyContent:'flex-end'}}>
									  <style>{`@keyframes ciaSheetUp{from{transform:translateY(100%);}to{transform:translateY(0);}}`}</style>
									  <div onClick={cancelEdit} style={{position:'absolute',inset:0,background:'rgba(15,17,21,0.45)'}} />
									  <div style={{position:'relative',background:'#fff',borderTopLeftRadius:'18px',borderTopRightRadius:'18px',outline:'2px solid rgb(234, 88, 12)',outlineOffset:'-2px',overflow:'hidden',boxShadow:'0 -8px 30px rgba(0,0,0,0.18)',maxHeight:'90vh',overflowY:'auto',animation:'ciaSheetUp 0.22s ease'}}>
									  <div style={{display:'flex',justifyContent:'center',padding:'8px 0 2px'}}><div style={{width:'38px',height:'4px',borderRadius:'2px',background:'#e2e8f0'}} /></div>
										{/* Edit Header */}
										<div style={{padding:'10px 14px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1.5px solid #f0f0f0'}}>
											<div style={{color:'#1e293b',fontSize:'14px',fontWeight:'700',display:'flex',alignItems:'center',gap:'6px',flex:1,minWidth:0,overflow:'hidden'}}>
												<i className="fa fa-pencil-square-o" style={{color:'rgb(234, 88, 12)'}}></i>
												<span style={{whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{product ? product.label : ''}</span>
												{supplier_id && supplier_id.value ? <span style={{fontWeight:'400',color:'#94a3b8',fontSize:'12px',flexShrink:0}}>· {supplier_id.label}</span> : null}
											</div>
											<button onClick={() => { const r = [...rowsData]; r[index]['fieldToggle'] = 'checked'; if(r[index]['_origSupplierId'] !== undefined) { r[index]['supplier_id'] = r[index]['_origSupplierId']; } if(r[index]['_origPrice'] !== undefined) { r[index]['price'] = r[index]['_origPrice']; r[index]['totalPrice'] = r[index]['_origTotalPrice']; } delete r[index]['_editNoSupplier']; delete r[index]['_origSupplierId']; delete r[index]['_origPrice']; delete r[index]['_origTotalPrice']; setRowsData(r); }} style={{background:'#f3f4f6',border:'none',color:'#666',fontSize:'13px',cursor:'pointer',padding:'4px 10px',borderRadius:'6px',flexShrink:0}}>✕</button>
										</div>
										{/* Edit Body */}
										<div style={{padding:'14px 16px'}}>
											{/* Supplier dropdown — only when product originally had no supplier */}
											{mdata._editNoSupplier && mdata.supplier && mdata.supplier.length > 0 && (
												<div style={{marginBottom:'12px'}}>
													<label style={lbl}>Supplier</label>
													<div style={{display:'flex',alignItems:'center',gap:'8px'}}>
														<div style={{flex:1,minWidth:0}}>
															<Select
																key={'edit_mob_supplier_'+index}
																isMulti={false}
																menuPortalTarget={document.body}
																options={mdata.supplier}
																value={supplier_id && supplier_id.value ? supplier_id : null}
																onChange={(evnt) => handleSupplierChange(index, product, evnt)}
																placeholder="+ Add Supplier"
																formatOptionLabel={(option, { context }) => {
																	if (context === 'value') return <span>{option.supplier_name || option.label}</span>;
																	if (!option.value) return <span style={{color:'#aaa'}}>-- Select Supplier --</span>;
																	const parts = (option.label || '').split('|');
																	const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
																	return (<div><div style={{fontWeight:'600',fontSize:'13px',color:'inherit'}}>{option.supplier_name || option.label}</div>{qty && <div style={{fontSize:'11px',color:'inherit',marginTop:'1px'}}>Qty: {qty}</div>}</div>);
																}}
																styles={{
																	control:(b,s)=>({...b,minHeight:'40px',fontSize:'13px',fontWeight:'600',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:s.isFocused?'#fff':'#fafbfc'}),
																	valueContainer:b=>({...b,padding:'0 10px'}),
																	menuPortal:b=>({...b,zIndex:9999}),
																	option:(b,s)=>({...b,fontSize:'13px',padding:'8px 12px',backgroundColor:!s.data.value?'#fff':s.isSelected?'#c2410c':s.isFocused?'rgb(234, 88, 12)':'#fff',color:!s.data.value?'#aaa':(s.isSelected||s.isFocused)?'#fff':'#334155'}),
																}}
															/>
														</div>
														{product && <AddStock key={'addstock_mob_edit_'+index} onSaveStock={onSaveStock} show={false} product={product} index={index} apiKey="" invoiceId={invoiceDetail.id} />}
													</div>
												</div>
											)}
											{/* Row 1: Qty + Price side by side */}
											<div style={{display:'flex',gap:'10px',marginBottom:'14px',alignItems:'flex-end'}}>
												<div style={{flex:1}}>
													<label style={lbl}>Quantity</label>
													<input type="number" min="1" step="1" defaultValue={quantity} onChange={e => handleQtyChange(index, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} placeholder="0" style={{width:'100%',height:'42px',padding:'0 12px',fontSize:'15px',fontWeight:'600',borderRadius:'10px',border: mdata.qtyWarning ? '2px solid #f59e0b' : '1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}} onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';}} onBlur={e=>{if(!mdata.qtyWarning){e.target.style.borderColor='#e5e7eb';}e.target.style.background='#fafbfc';}} />
													{mdata.qtyWarning && <div style={{color:'#b45309',fontSize:'11px',fontWeight:'600',marginTop:'4px',display:'flex',alignItems:'center',gap:'4px'}}><i className="fa fa-exclamation-triangle"></i> {mdata.qtyWarning}</div>}
												</div>
												<div style={{flex:1.3}}>
													<label style={lbl}>Unit Price</label>
													<div style={{display:'flex',height:'42px',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e5e7eb'}}>
														<span style={{padding:'0 10px',fontSize:'14px',background:'#fff7ed',color:'rgb(234, 88, 12)',fontWeight:'700',display:'flex',alignItems:'center',borderRight:'1.5px solid #e5e7eb'}}>{props.currency}</span>
														<input type="number" min="0" defaultValue={price} onChange={e => handlePriceChange(index, e)} placeholder="0.00" style={{flex:1,border:'none',outline:'none',padding:'0 12px',fontSize:'15px',fontWeight:'600',background:'#fafbfc',color:'#1e293b',minWidth:0}} onFocus={e=>{e.target.parentElement.style.borderColor='rgb(234, 88, 12)';}} onBlur={e=>{e.target.parentElement.style.borderColor='#e5e7eb';}} />
													</div>
												</div>
											</div>
											{/* Row 2: Remarks full width */}
											<div style={{marginBottom:'16px'}}>
												<label style={lbl}>Remarks</label>
												<input type="text" defaultValue={remarks} onChange={e => handleRemarksChange(index, e)} placeholder="Add a note..." style={{width:'100%',height:'42px',padding:'0 12px',fontSize:'14px',borderRadius:'10px',border:'1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}} onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';}} onBlur={e=>{e.target.style.borderColor='#e5e7eb';e.target.style.background='#fafbfc';}} />
											</div>
											{/* Save Button */}
											<button onClick={(evnt) => handleUpdateChange(index, evnt)} style={{width:'100%',height:'48px',fontSize:'15px',fontWeight:'700',borderRadius:'12px',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',cursor:'pointer',boxShadow:'0 3px 12px rgba(234,88,12,0.3)'}}>
												<i className="fa fa-check-circle"></i> Save Changes
											</button>
										</div>
									</div>
									</div>
								); })()
							}
							return (() => {
								const isExpanded = expandedCardIndex === index;
								const isDeleting = pendingDelete === index;
								return (
								<div key={'card_'+index} style={{
									background: isExpanded ? '#fffbf7' : '#fff',
									borderRadius: '10px',
									border: 'none',
									borderLeft: !isExpanded && !isDeleting ? '4px solid rgb(234, 88, 12)' : 'none',
									outline: isDeleting ? '2px solid #e74c3c' : isExpanded ? '2px solid rgb(234, 88, 12)' : 'none',
									outlineOffset: isExpanded || isDeleting ? '-2px' : '0',
									boxShadow: isDeleting ? '0 2px 8px rgba(231,76,60,0.12)' : isExpanded ? '0 4px 12px rgba(234,88,12,0.15)' : '0 1px 4px rgba(0,0,0,0.07)',
									marginBottom: '8px',
									transition:'all 0.2s ease',
									overflow:'hidden'
								}}>
									{/* Tappable area: Product info */}
									<div
										onClick={() => { if (!isDeleting) setExpandedCardIndex(isExpanded ? null : index); }}
										style={{cursor:'pointer',padding:'12px 14px 10px 14px'}}
									>
										{/* Row 1: Product name + Total badge */}
										<div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:'4px'}}>
											<div style={{flex:1,minWidth:0,overflow:'hidden',paddingRight:'8px'}}>
												<div style={{fontWeight:'700',fontSize:'14px',color:'#111827',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
													{product ? product.label : ''}
												</div>
												{supplier_id && supplier_id.value ? <div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px'}}>{supplier_id.label}</div> : null}
											</div>
											<div style={{display:'flex',alignItems:'center',gap:'8px',flexShrink:0}}>
												<span style={{background: isExpanded ? 'rgb(234, 88, 12)' : 'linear-gradient(135deg, #fff7ed, #ffedd5)',padding:'4px 10px',borderRadius:'8px',fontWeight:'800',fontSize:'14px',color: isExpanded ? '#fff' : '#c2410c',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(totalPrice)}</span>
												<i className={isExpanded ? "fa fa-chevron-up" : "fa fa-chevron-right"} style={{color: isExpanded ? 'rgb(234, 88, 12)' : '#d1d5db',fontSize:'12px',width:'14px',textAlign:'center',transition:'transform 0.2s'}}></i>
											</div>
										</div>
										{/* Row 2: Qty badge + Unit Price + Remarks */}
										<div style={{fontSize:'12px',color:'#555',display:'flex',gap:'8px',alignItems:'center',flexWrap:'wrap',marginTop:'6px'}}>
											<span style={{background:'#f1f5f9',borderRadius:'6px',padding:'3px 8px',fontWeight:'600',color:'#475569',fontSize:'12px',flexShrink:0}}>
												<i className="fa fa-cube" style={{fontSize:'10px',marginRight:'4px',color:'#94a3b8'}}></i>{quantity}
											</span>
											<span style={{color:'#94a3b8',flexShrink:0}}>{props.currency} {price}/unit</span>
											{remarks ? <span style={{color:'#e2e8f0',fontStyle:'italic',fontSize:'12px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>· {remarks}</span> : null}
										</div>
									</div>

									{/* Action Drawer - slides open on tap */}
									{isExpanded && !isDeleting && (
										<div style={{borderTop:'1px solid #f0f0f0',padding:'10px 14px',background:'#fafafa',display:'flex',gap:'10px',borderRadius:'0 0 10px 10px'}}>
											<button
												onClick={(evnt) => { setExpandedCardIndex(null); handleEditChange(index, evnt); }}
												value={invoiceproductid}
												style={{flex:1,height:'46px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',borderRadius:'8px',fontSize:'14px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}
											>
												<i className="fa fa-edit"></i> Edit
											</button>
											<button
												onClick={() => deleteTableRows(index, invoiceproductid)}
												style={{flex:1,height:'46px',background:'#fff',border:'2px solid #e74c3c',color:'#e74c3c',borderRadius:'8px',fontSize:'14px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}
											>
												<i className="fa fa-trash"></i> Delete
											</button>
										</div>
									)}

									{/* Delete confirmation banner */}
									{isDeleting && (
										<div style={{borderTop:'1.5px solid #f5c6cb',background:'#fff5f5',padding:'12px 14px'}}>
											<div style={{textAlign:'center',marginBottom:'10px'}}>
												<i className="fa fa-exclamation-triangle" style={{color:'#e74c3c',fontSize:'18px',marginBottom:'4px'}}></i>
												<div style={{fontSize:'14px',color:'#c0392b',fontWeight:'700'}}>Delete this item?</div>
												<div style={{fontSize:'12px',color:'#999',marginTop:'2px'}}>{product ? product.label : ''}</div>
											</div>
											<div style={{display:'flex',gap:'10px'}}>
												<button
													onClick={() => setPendingDelete(null)}
													style={{flex:1,height:'46px',background:'#fff',border:'1.5px solid #ddd',color:'#555',borderRadius:'8px',fontSize:'14px',fontWeight:'600',cursor:'pointer'}}
												>
													Cancel
												</button>
												<button
													onClick={performDelete}
													style={{flex:1,height:'46px',background:'#e74c3c',border:'none',color:'#fff',borderRadius:'8px',fontSize:'14px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}
												>
													<i className="fa fa-trash"></i> Yes, Delete
												</button>
											</div>
										</div>
									)}
								</div>
								);
							})()
						})}
						{errorData && <p className="text-danger" style={{fontSize:'12px',margin:'0 0 8px 0'}}>Please fill in all required fields.</p>}

						{/* Add Product button */}
						<button
							disabled={showAddPanel}
							onClick={() => { addTableRows(); setShowAddPanel(true); setExpandedCardIndex(null); }}
							style={{
								width:'100%',
								height:'48px',
								background: showAddPanel ? '#f5f5f5' : '#fff',
								border: showAddPanel ? '1.5px dashed #ccc' : '1.5px dashed rgb(234, 88, 12)',
								color: showAddPanel ? '#aaa' : 'rgb(234, 88, 12)',
								borderRadius:'10px',
								fontSize:'14px',
								fontWeight:'700',
								cursor: showAddPanel ? 'not-allowed' : 'pointer',
								display:'flex',
								alignItems:'center',
								justifyContent:'center',
								gap:'8px',
								marginTop:'4px',
								letterSpacing:'0.3px',outline:'none'
							}}
						>
							<i className="fa fa-plus-circle" style={{fontSize:'16px'}}></i> Add Product
						</button>
						</div>
					  </div>
					</div>
				)}
				<input type="hidden" {...formik.getFieldProps("rowsdata")} />
				</div>
            </div>

            {effectiveWidth < 768 && showAddPanel && rowsData.length > 0 && (() => {
                const panelIndex = rowsData.length - 1;
                const panelRow = rowsData[panelIndex];
                const { product, supplier, supplier_id, quantity, price, remarks } = panelRow;
                const fmtSupplierOpt = (option, { context, selectValue }) => {
                    if (context === 'value') return <span>{option.supplier_name || option.label}</span>;
                    if (!option.value) return <span style={{color:'#aaa'}}>-- Select Supplier --</span>;
                    const parts = (option.label || '').split('|');
                    const qty2 = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
                    const remark = parts.filter(p => p && !p.startsWith('Qty:') && !p.startsWith('P:') && !p.includes('...')).join('').trim();
                    const isSelected = selectValue && selectValue.some(v => v.value === option.value);
                    const subColor = isSelected ? 'rgba(255,255,255,0.75)' : 'inherit';
                    return (<div><div style={{fontWeight:'500',fontSize:'13px'}}>{option.supplier_name || option.label}</div>{(qty2 || remark) && <div style={{fontSize:'11px',color:subColor,marginTop:'1px'}}>{qty2 ? 'Qty: '+qty2 : ''}{qty2 && remark ? ' · ' : ''}{remark && <span style={{fontStyle:'italic',opacity:0.8}}>{remark}</span>}</div>}</div>);
                };
                const isCompact = effectiveWidth < 768;
                const lbl = {fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'5px',display:'block'};
                return (
                    <div style={isCompact ? {position:'fixed',left:0,right:0,top:0,bottom:0,zIndex:6000,display:'flex',flexDirection:'column',justifyContent:'flex-end'} : {}}>
                      <style>{`@keyframes ciaSheetUp{from{transform:translateY(100%);}to{transform:translateY(0);}}`}</style>
                      {isCompact && <div onClick={() => { deleteTableRows(panelIndex, 0); setShowAddPanel(false); }} style={{position:'absolute',inset:0,background:'rgba(15,17,21,0.45)'}} />}
                      <div ref={addPanelRef} style={isCompact
                        ? {position:'relative',background:'#fff',borderTopLeftRadius:'18px',borderTopRightRadius:'18px',overflow:'hidden',boxShadow:'0 -8px 30px rgba(0,0,0,0.18)',maxHeight:'90vh',overflowY:'auto',animation:'ciaSheetUp 0.22s ease'}
                        : {background:'#fff',borderRadius:'12px',overflow:'hidden',boxShadow:'0 4px 18px rgba(0,0,0,0.12)',marginTop:'6px'}}>
                        {/* Header */}
                        <div style={{padding:'10px 14px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1.5px solid #f0f0f0'}}>
                            <span style={{color:'#1e293b',fontSize:'14px',fontWeight:'700',display:'flex',alignItems:'center',gap:'6px'}}><i className="fa fa-plus-circle" style={{color:'rgb(234, 88, 12)'}}></i>Add New Product</span>
                            <button onClick={() => { deleteTableRows(panelIndex, 0); setShowAddPanel(false); }} style={{background:'#f3f4f6',border:'none',color:'#666',fontSize:'13px',cursor:'pointer',padding:'4px 10px',borderRadius:'6px'}}>✕</button>
                        </div>
                        {/* Body */}
                        <div style={{padding: isCompact ? '14px 16px' : '18px 16px'}}>
                        {isCompact ? (<>
                            {/* Product */}
                            <div style={{marginBottom:'14px'}}>
                                <div style={{display:'flex',alignItems:'center',justifyContent:'flex-start',gap:'8px',marginBottom:'6px'}}>
                                    <label style={{...lbl,marginBottom:0}}>Product</label>
                                    <AddProduct existingProducts={productsList} onCreated={(item, reused) => onProductCreated(item, reused, panelIndex)} />
                                    <div style={{display:'flex',alignItems:'center',gap:'6px',marginLeft:'auto'}}>
                                        <span style={{fontSize:'10px',fontWeight:'700',color:showSuppliers?'rgb(234, 88, 12)':'#94a3b8',letterSpacing:'0.3px'}}>Supplier</span>
                                        <div onClick={savingToggle ? undefined : handleSupplierToggle} style={{position:'relative',width:'34px',height:'18px',cursor:savingToggle?'not-allowed':'pointer',display:'block',opacity:savingToggle?0.6:1,flexShrink:0,background:showSuppliers?'rgb(234, 88, 12)':'#d1d5db',borderRadius:'17px',transition:'background 0.2s'}}>
                                            <span style={{position:'absolute',width:'12px',height:'12px',left:showSuppliers?'19px':'3px',bottom:'3px',background:'#fff',borderRadius:'50%',boxShadow:'0 1px 3px rgba(0,0,0,0.25)',transition:'left 0.2s',display:'block'}}></span>
                                        </div>
                                    </div>
                                </div>
                                <Select key={'panel_product_'+panelIndex} options={allProducts} menuPortalTarget={document.body}
                                    styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),control:(b,s)=>({...b,minHeight:'42px',fontSize:'14px',fontWeight:'600',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafbfc'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                    defaultValue={product} onChange={(evnt) => handleProductChange(panelIndex, evnt)} placeholder="Select product" />
                            </div>
                            {/* Supplier */}
                            {showSuppliers && (<div style={{marginBottom:'14px'}}>
                                <label style={lbl}>Supplier</label>
                                <div style={{display:'flex',gap:'8px',alignItems:'center'}}>
                                    <div style={{flex:1,minWidth:0}}>
                                        <Select key={'panel_supplier_'+panelIndex} isMulti={false} menuPortalTarget={document.body}
                                            styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),control:(b,s)=>({...b,minHeight:'42px',fontSize:'14px',fontWeight:'600',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafbfc'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                            options={supplier} defaultValue={supplier_id} formatOptionLabel={fmtSupplierOpt}
                                            onChange={(evnt) => handleSupplierChange(panelIndex, product, evnt)} placeholder="Select supplier..." />
                                    </div>
                                    {product !== '' && <AddStock key="addstock_panel" onSaveStock={onSaveStock} show={false} product={product} index={panelIndex} apiKey="" invoiceId={invoiceDetail.id} />}
                                </div>
                            </div>)}
                            {/* Qty + Price side by side */}
                            <div style={{display:'flex',gap:'10px',marginBottom:'14px',alignItems:'flex-end'}}>
                                <div style={{flex:1}}>
                                    <label style={lbl}>Quantity</label>
                                    <input key={'panel_qty_'+panelIndex} type="number" min="1" step="1" defaultValue={quantity} onChange={e => handleQtyChange(panelIndex, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} placeholder="0" style={{width:'100%',height:'42px',padding:'0 12px',fontSize:'15px',fontWeight:'600',borderRadius:'10px',border: rowsData[panelIndex]?.qtyWarning ? '2px solid #f59e0b' : '1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}} onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';}} onBlur={e=>{if(!rowsData[panelIndex]?.qtyWarning){e.target.style.borderColor='#e5e7eb';}e.target.style.background='#fafbfc';}} />
                                    {rowsData[panelIndex]?.qtyWarning && <div style={{color:'#b45309',fontSize:'11px',fontWeight:'600',marginTop:'4px',display:'flex',alignItems:'center',gap:'4px'}}><i className="fa fa-exclamation-triangle"></i> {rowsData[panelIndex].qtyWarning}</div>}
                                </div>
                                <div style={{flex:1.3}}>
                                    <label style={lbl}>Unit Price</label>
                                    <div style={{display:'flex',height:'42px',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e5e7eb'}}>
                                        <span style={{padding:'0 10px',fontSize:'14px',background:'#fff7ed',color:'rgb(234, 88, 12)',fontWeight:'700',display:'flex',alignItems:'center',borderRight:'1.5px solid #e5e7eb'}}>{props.currency}</span>
                                        <input key={'panel_price_'+panelIndex} type="number" min="0" defaultValue={price} onChange={e => handlePriceChange(panelIndex, e)} placeholder="0.00" style={{flex:1,border:'none',outline:'none',padding:'0 12px',fontSize:'15px',fontWeight:'600',background:'#fafbfc',color:'#1e293b',minWidth:0}} onFocus={e=>{e.target.parentElement.style.borderColor='rgb(234, 88, 12)';}} onBlur={e=>{e.target.parentElement.style.borderColor='#e5e7eb';}} />
                                    </div>
                                </div>
                            </div>
                            {/* Remarks */}
                            <div style={{marginBottom:'16px'}}>
                                <label style={lbl}>Remarks</label>
                                <input key={'panel_remarks_'+panelIndex} type="text" defaultValue={remarks} onChange={e => handleRemarksChange(panelIndex, e)} placeholder="Add a note..." style={{width:'100%',height:'42px',padding:'0 12px',fontSize:'14px',borderRadius:'10px',border:'1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}} onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#fff';}} onBlur={e=>{e.target.style.borderColor='#e5e7eb';e.target.style.background='#fafbfc';}} />
                            </div>
                            {/* Save Button */}
                            <button disabled={isSavingNew} onClick={(evnt) => handleToogleChange(panelIndex, evnt)} style={{width:'100%',height:'48px',fontSize:'15px',fontWeight:'700',borderRadius:'12px',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',cursor:'pointer',opacity: isSavingNew ? 0.7 : 1,boxShadow:'0 3px 12px rgba(234,88,12,0.3)'}}>
                                <i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-check-circle"}></i> {isSavingNew ? 'Saving...' : 'Save Product'}
                            </button>
                            {(rowsData[panelIndex]?.rowError || errorData) && (
                                <p className="text-danger" style={{fontSize:'12px',margin:'8px 0 0'}}>
                                    <i className="fa fa-exclamation-circle" style={{marginRight:'4px'}}></i>
                                    {rowsData[panelIndex]?.rowError || 'Please fill in product, quantity and price.'}
                                </p>
                            )}
                            {panelSuccess && (
                                <p style={{fontSize:'12px',margin:'8px 0 0',color:'#16a34a'}}>
                                    <i className="fa fa-check-circle" style={{marginRight:'4px'}}></i>Product added successfully!
                                </p>
                            )}
                        </>) : (<>
                            {/* Desktop: original layout */}
                            <div style={{marginBottom:'12px'}}>
                                <label style={lbl}>Product</label>
                                <Select options={allProducts} menuPortalTarget={document.body}
                                    styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                    defaultValue={product} onChange={(evnt) => handleProductChange(panelIndex, evnt)} placeholder="Select product..." />
                            </div>
                            {showSuppliers && (<div style={{marginBottom:'12px'}}>
                                <label style={lbl}>Supplier</label>
                                <div style={{display:'flex',gap:'6px',alignItems:'center'}}>
                                    <div style={{flex:1}}>
                                        <Select isMulti={false} menuPortalTarget={document.body}
                                            styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                            options={supplier} defaultValue={supplier_id} formatOptionLabel={fmtSupplierOpt}
                                            onChange={(evnt) => handleSupplierChange(panelIndex, product, evnt)} placeholder="Select supplier..." />
                                    </div>
                                    {product !== '' && <AddStock key="addstock_panel" onSaveStock={onSaveStock} show={false} product={product} index={panelIndex} apiKey="" invoiceId={invoiceDetail.id} />}
                                </div>
                            </div>)}
                            <div style={{display:'flex',gap:'10px',marginBottom:'12px'}}>
                                <div style={{flex:1}}>
                                    <label style={lbl}>Quantity</label>
                                    <input type="number" min="1" step="1" className="form-control" defaultValue={quantity} onChange={e => handleQtyChange(panelIndex, e)} onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }} placeholder="0" />
                                </div>
                                <div style={{flex:1}}>
                                    <label style={lbl}>Unit Price</label>
                                    <div className="input-group"><div className="input-group-prepend"><span className="input-group-text" style={{padding:'4px 8px'}}>{props.currency}</span></div>
                                        <input type="number" min="0" className="form-control" defaultValue={price} onChange={e => handlePriceChange(panelIndex, e)} placeholder="0.00" /></div>
                                </div>
                            </div>
                            <div style={{marginBottom:'14px'}}>
                                <label style={lbl}>Remarks</label>
                                <textarea className="form-control" rows={2} defaultValue={remarks} onChange={e => handleRemarksChange(panelIndex, e)} placeholder="Optional remarks..." />
                            </div>
                            {errorData && <p className="text-danger" style={{fontSize:'12px',marginBottom:'10px'}}>Please fill in product, quantity and price.</p>}
                            <div style={{display:'flex',gap:'8px',justifyContent:'flex-end'}}>
                                <button onClick={() => { deleteTableRows(panelIndex, 0); setShowAddPanel(false); }} style={{background:'#fff',border:'1px solid #ddd',color:'#555',padding:'7px 16px',borderRadius:'6px',fontSize:'13px',cursor:'pointer'}}>Cancel</button>
                                <button className="btn btn-success" disabled={isSavingNew} onClick={(evnt) => handleToogleChange(panelIndex, evnt)} style={{padding:'7px 18px',fontSize:'13px',fontWeight:'600'}}>
                                    <i className="fa fa-save" style={{marginRight:'4px'}}></i>{isSavingNew ? 'Saving...' : 'Save'}
                                </button>
                            </div>
                        </>)}
                        </div>
                      </div>
                    </div>
                );
            })()}

            {rowsData.length > 0 ? (width < 768 ? (
                <div className="row" style={{marginTop:'16px',marginBottom:'8px'}}><div className="col-lg-12 sm-p-0"><div style={{paddingLeft:'15px',paddingRight:'15px',display:'flex',flexDirection:'column',gap:'14px'}}>
                  {/* Additional Notes Card */}
                  <div style={{background:'#fff',borderRadius:'14px',boxShadow:'0 2px 12px rgba(0,0,0,0.08)',overflow:'visible'}}>
                    <div style={{padding:'10px 16px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1.5px solid #f0f0f0'}}>
                      <span style={{display:'inline-flex',alignItems:'center',gap:'8px',color:'#1e293b',fontSize:'13px',fontWeight:'700',letterSpacing:'0.3px'}}>
                        <span style={{width:'26px',height:'26px',borderRadius:'8px',background:'#fff7ed',border:'1px solid #fed7aa',color:'rgb(234, 88, 12)',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                        </span>
                        Additional Notes
                      </span>
                      <span style={{fontSize:'11px',color:'#94a3b8'}}>
                        {notesSaveStatus === 'saving' && <span><i className="fa fa-spinner fa-spin" style={{marginRight:'4px'}}></i>Saving...</span>}
                        {notesSaveStatus === 'saved' && !editingNotes && <span style={{color:'#16a34a'}}><i className="fa fa-check" style={{marginRight:'4px'}}></i>Saved</span>}
                      </span>
                    </div>
                    <div style={{padding:'14px 16px'}}>
                      {editingNotes ? (
                        <div>
                          <textarea
                            ref={notesTextareaRef}
                            value={invoiceNotes}
                            onChange={handleNotesChange}
                            placeholder="Add any additional notes for this invoice..."
                            rows={3}
                            style={{width:'100%',borderRadius:'8px',border:'1.5px solid rgb(234, 88, 12)',padding:'10px 12px',fontSize:'13px',color:'#333',resize:'vertical',outline:'none',fontFamily:'inherit',lineHeight:'1.5',background:'#fffbf7'}}
                          />
                          <button
                            onClick={saveNotes}
                            style={{marginTop:'8px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',padding:'8px 20px',borderRadius:'8px',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',gap:'6px'}}>
                            <i className="fa fa-check"></i>Done
                          </button>
                        </div>
                      ) : (
                        <div
                          onClick={() => { setEditingNotes(true); setTimeout(() => notesTextareaRef.current?.focus(), 50); }}
                          style={{minHeight:'50px',padding:'10px 12px',borderRadius:'8px',border:'1.5px dashed #d1d5db',fontSize:'13px',color: invoiceNotes ? '#333' : '#999',lineHeight:'1.5',cursor:'pointer',background:'#fafafa',whiteSpace:'pre-wrap',wordBreak:'break-word',display:'flex',alignItems:'center',gap:'8px'}}>
                          {invoiceNotes ? (
                            <span style={{flex:1}}>{invoiceNotes}</span>
                          ) : (
                            <><i className="fa fa-pencil" style={{fontSize:'14px',color:'#d1d5db'}}></i><span>Tap to add notes...</span></>
                          )}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Invoice Totals — Modern */}
                  <div style={{borderRadius:'14px',overflow:'hidden',border:'1px solid #e5e7eb',background:'#fff'}}>
                    {[
                      {label:'Sub Total', value: formatTwoDecimal(subTotal), icon:'fa-calculator', color:'#374151'},
                      {label:'Porterage', value: invoiceTotal > 0 ? formatTwoDecimal(porterageVal) : '0.00', icon:'fa-truck', color:'#6b7280'},
                      {label:'VAT', value: invoiceTotal > 0 ? formatTwoDecimal(vatVal) : '0.00', icon:'fa-percent', color:'#6b7280'}
                    ].map((item, i) => (
                      <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 14px',borderBottom:'1px solid #f1f5f9'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                          <div style={{width:'28px',height:'28px',borderRadius:'8px',background:'#f8fafc',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                            <i className={"fa "+item.icon} style={{fontSize:'11px',color:'#94a3b8'}}></i>
                          </div>
                          <span style={{fontSize:'13px',color:'#64748b',fontWeight:'500'}}>{item.label}</span>
                        </div>
                        <span style={{fontSize:'13px',fontWeight:'600',color:item.color,fontVariantNumeric:'tabular-nums'}}>{props.currency} {item.value}</span>
                      </div>
                    ))}
                    <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 14px',background:'linear-gradient(135deg,#fff7ed,#ffedd5)'}}>
                      <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                        <div style={{width:'28px',height:'28px',borderRadius:'8px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                          <i className="fa fa-tag" style={{fontSize:'11px',color:'#fff'}}></i>
                        </div>
                        <span style={{fontSize:'14px',fontWeight:'800',color:'#1e293b'}}>Total</span>
                      </div>
                      <span className="invoice-price" style={{fontSize:'18px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'-0.5px'}}>{props.currency} {formatTwoDecimal(invoiceTotal)}</span>
                    </div>
                  </div>
                </div></div></div>
              ) : (
                <div style={{display:'flex',flexDirection:'row',alignItems:'flex-start',gap:'16px',marginTop:'24px',marginBottom:'8px',width:'100%'}}>
                  {/* Additional Notes - Desktop */}
                  <div style={{flex:1,minWidth:0}}>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'flex-start',gap:'8px',marginBottom:'6px'}}>
                      <label onClick={() => { if (editingNotes) { setEditingNotes(false); } else { setEditingNotes(true); setTimeout(() => notesTextareaRef.current?.focus(), 50); } }} style={{fontSize:'13px',fontWeight:'600',color:'#555',margin:0,cursor:'pointer',userSelect:'none'}}>
                        Additional Notes <i className="fa fa-pencil" style={{marginLeft:'5px',fontSize:'11px',color:'rgb(234, 88, 12)'}}></i>
                      </label>
                      <span style={{fontSize:'11px',color:'#aaa'}}>
                        {notesSaveStatus === 'saving' && 'Saving...'}
                        {notesSaveStatus === 'saved' && !editingNotes && <span style={{color:'#16a34a'}}><i className="fa fa-check" style={{marginRight:'3px'}}></i>Saved</span>}
                      </span>
                    </div>
                    {editingNotes && (
                      <div style={{marginTop:'6px'}}>
                        <textarea
                          ref={notesTextareaRef}
                          value={invoiceNotes}
                          onChange={handleNotesChange}
                          placeholder="Add any additional notes for this invoice..."
                          rows={3}
                          style={{width:'100%',borderRadius:'8px',border:'1.5px solid rgb(234, 88, 12)',padding:'10px 12px',fontSize:'13px',color:'#333',resize:'vertical',outline:'none',fontFamily:'inherit',lineHeight:'1.5'}}
                        />
                        <button
                          onClick={saveNotes}
                          style={{marginTop:'4px',background:'rgb(234, 88, 12)',border:'none',color:'#fff',padding:'4px 14px',borderRadius:'5px',fontSize:'12px',fontWeight:'600',cursor:'pointer'}}>
                          <i className="fa fa-check" style={{marginRight:'4px'}}></i>Done
                        </button>
                      </div>
                    )}
                    {!editingNotes && invoiceNotes && (
                      <div style={{marginTop:'8px',fontSize:'13px',color:'#555',lineHeight:'1.6',whiteSpace:'pre-wrap',wordBreak:'break-word',background:'#fafafa',border:'1px solid #e5e7eb',borderRadius:'8px',padding:'10px 14px',fontFamily:'inherit'}}>{invoiceNotes}</div>
                    )}
                  </div>
                  {/* Totals - Desktop */}
                  <div className="invoice-total mt-0 mb-2 p-0" style={{flex:1,minWidth:0}}>
                    <div style={{borderRadius:'14px',overflow:'hidden',background:'#fff',boxShadow:'0 4px 20px rgba(0,0,0,0.08)',border:'1px solid #e5e7eb'}}>
                        {[
                            {label:'Sub Total', value: formatTwoDecimal(subTotal), cls:'sub-total'},
                            {label:'Porterage', value: invoiceTotal > 0 ? formatTwoDecimal(porterageVal) : '0.00', cls:'porterage'},
                            {label:'Vat', value: invoiceTotal > 0 ? formatTwoDecimal(vatVal) : '0.00', cls:'vat'},
                        ].map((item, i) => (
                            <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 20px',borderBottom:'1px solid #f0f0f0'}}>
                                <span style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>{item.label}</span>
                                <span className={item.cls} style={{fontSize:'14px',fontWeight:'700',color:'#374151'}}>{props.currency} {item.value}</span>
                            </div>
                        ))}
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'14px 20px',borderTop:'2px solid rgb(234, 88, 12)',background:'#fff'}}>
                            <span style={{fontSize:'15px',fontWeight:'800',color:'#111827'}}>Total</span>
                            <span className="invoice-price" style={{fontSize:'18px',fontWeight:'900',color:'rgb(234, 88, 12)'}}>{props.currency} {formatTwoDecimal(invoiceTotal)}</span>
                        </div>
                    </div>
                    {/* Pay Invoice — moved to header bar */}
                  </div>
                </div>
              ))
                : <></>
            }

            {/* <div style={{display:'flex',justifyContent:'flex-end',marginTop:'-20px'}}>
                <CustomerInvoicePaymentsPopup
                    currency={props.currency}
                    total={formatTwoDecimal(invoiceTotal)}
                    customer={invoiceDetail}
                    onFormChange={fetchPagePaymentSummary}
                    {...props}/>
            </div> */}

		<ToastContainer style={{zIndex:99999,marginTop:'60px'}} autoClose={3000} />

		<EmailInvoiceModal
			open={emailModalOpen}
			onClose={() => setEmailModalOpen(false)}
			apiUrl={`/data_entry/sales_entry/invoice_email/send/${props.id}`}
			invoiceId={invoiceDetail.id}
			invoiceNumber={invoiceDetail.other_invoice_id || invoiceDetail.id}
			partyLabel="Customer"
			partyName={invoiceDetail.customer}
			partyEmail={invoiceDetail.customer_email}
			invoiceDate={invoiceDetail.created_date ? new Date(String(invoiceDetail.created_date).replace(' ','T')).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : ''}
			totalText={invoiceTotal > 0 ? (props.currency || '£') + ' ' + invoiceTotal.toFixed(2) : ''}
		/>

		{/* Delete Confirmation Modal */}
		{pendingDelete !== null && (
			<div style={{position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.4)',zIndex:99998,display:'flex',alignItems:'center',justifyContent:'center',backdropFilter:'blur(2px)'}} onClick={() => setPendingDelete(null)}>
				<div style={{background:'#fff',borderRadius:'16px',padding:'32px 28px 24px',maxWidth:'380px',width:'90%',boxShadow:'0 20px 60px rgba(0,0,0,0.2)',textAlign:'center',animation:'fadeInScale 0.2s ease-out'}} onClick={e => e.stopPropagation()}>
					<style>{`@keyframes fadeInScale{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}`}</style>
					<div style={{width:'56px',height:'56px',borderRadius:'50%',background:'#fef2f2',display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 16px'}}>
						<i className="fa fa-trash-o" style={{fontSize:'24px',color:'#ef4444'}}></i>
					</div>
					<h4 style={{fontSize:'18px',fontWeight:'700',color:'#1e293b',marginBottom:'8px'}}>Delete Item?</h4>
					<p style={{fontSize:'14px',color:'#64748b',marginBottom:'24px',lineHeight:'1.5'}}>
						{rowsData[pendingDelete]?.product?.label ? (
							<>Are you sure you want to delete <strong style={{color:'#1e293b'}}>{rowsData[pendingDelete].product.label}</strong>? This action cannot be undone.</>
						) : 'Are you sure you want to delete this item? This action cannot be undone.'}
					</p>
					<div style={{display:'flex',gap:'10px',justifyContent:'center'}}>
						<button onClick={() => setPendingDelete(null)} style={{flex:1,padding:'10px 20px',borderRadius:'10px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#374151',fontSize:'14px',fontWeight:'600',cursor:'pointer',transition:'all 0.15s'}}>Cancel</button>
						<button onClick={performDelete} style={{flex:1,padding:'10px 20px',borderRadius:'10px',border:'none',background:'#ef4444',color:'#fff',fontSize:'14px',fontWeight:'600',cursor:'pointer',transition:'all 0.15s',boxShadow:'0 2px 8px rgba(239,68,68,0.3)'}}>
							<i className="fa fa-trash-o" style={{marginRight:'6px'}}></i>Delete
						</button>
					</div>
				</div>
			</div>
		)}

        </>
    );


    function TableRows({ allProducts, rowsData, deleteTableRows, pendingDelete, setPendingDelete, performDelete, handleResetRow, handleChange }) {

        const [qty, setQty] = useState('');
        const [amount, setAmount] = useState('');
		const [remark, setRemark] = useState('');
		const [showRemarksRows, setShowRemarksRows] = useState({});

		const toggleRemarksRow = (rowIndex) => {
			setShowRemarksRows(prev => ({ ...prev, [rowIndex]: !prev[rowIndex] }));
		};

		const formatSupplierOption = (option, { context, selectValue }) => {
			if (context === 'value') {
				return <span>{option.supplier_name || option.label}</span>;
			}
			if (!option.value) {
				return <span style={{color:'#aaa'}}>-- Select Supplier --</span>;
			}
			const parts = (option.label || '').split('|');
			const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:', '').trim();
			const remark = parts.filter(p => p && !p.startsWith('Qty:') && !p.startsWith('P:') && !p.includes('...')).join('').trim();
			const isSelected = selectValue && selectValue.some(v => v.value === option.value);
			const subColor = isSelected ? 'rgba(255,255,255,0.75)' : 'inherit';
			return (
				<div>
					<div style={{fontWeight:'500',fontSize:'13px'}}>{option.supplier_name || option.label}</div>
					{(qty || remark) && <div style={{fontSize:'11px',color:subColor,marginTop:'1px'}}>{qty ? 'Qty: '+qty : ''}{qty && remark ? ' · ' : ''}{remark && <span style={{fontStyle:'italic',opacity:0.8}}>{remark}</span>}</div>}
				</div>
			);
		};

        return (
            rowsData.map((data, index) => {
                const { product, quantity,supplier,supplier_id,s,selected,remarks,invoice,invoice_id, price, totalPrice, fieldToggle, invoiceproductid, resetKey, rowError } = data;
                //console.log(data)
                // On tablet/mobile, all unsaved new rows are rendered in the Add Product panel instead
                if (effectiveWidth < 768 && fieldToggle === '' && invoiceproductid === 0) return null;
				return (
					<React.Fragment key={"frag_"+index}>
					{(fieldToggle === "checked" || pendingDelete === index) ? (
                    <tr index={index} key={"row_"+index} className={pendingDelete === index ? 'row-pending-delete' : ''}>
                        <td style={{overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap'}}>
                            <span style={{fontWeight:'600', fontSize:'13px'}}>{product ? product.label : ''}</span>
                            <span style={{fontSize:'11px', color:'#999', marginLeft:'8px'}}>
                                {supplier_id && supplier_id.label ? supplier_id.label : 'N/A'}
                                {remarks ? <em style={{marginLeft:'4px'}}>· {remarks}</em> : null}
                            </span>
                        </td>
                        <td className='text-center' style={{verticalAlign:'middle'}}>{quantity}</td>
                        <td className='text-center' style={{verticalAlign:'middle'}}>{props.currency} {price}</td>
                        <td className='text-center' style={{verticalAlign:'middle',fontWeight:'700',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(totalPrice)}</td>
                        <td className='text-right' style={{width:'1%', whiteSpace:'nowrap'}}>
                                <>
                                    <button className="btn btn-sm" style={{width:'32px',height:'32px',padding:0,borderRadius:'8px',background:'#fff7ed',border:'1.5px solid rgb(234, 88, 12)',color:'rgb(234, 88, 12)',display:'inline-flex',alignItems:'center',justifyContent:'center',marginRight:'4px',cursor:'pointer'}} title="Edit" value={invoiceproductid} onClick={(evnt) =>handleEditChange(index,evnt)}><i className="fa fa-pencil" style={{fontSize:'13px'}}></i></button>
                                    <button className="btn btn-sm" style={{width:'32px',height:'32px',padding:0,borderRadius:'8px',background:'#fef2f2',border:'1.5px solid #fca5a5',color:'#ef4444',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer'}} title="Delete" onClick={() => deleteTableRows(index, invoiceproductid)}><i className="fa fa-trash-o" style={{fontSize:'13px'}}></i></button>
                                </>
                        </td>
                    </tr>
					) : (
					<>
                    <tr index={index} key={"row_"+index}>
                        <td>
                            {invoiceproductid !== 0 ? (
                                <>
                                    <div style={{display:'flex', flexDirection: effectiveWidth < 768 ? 'column' : 'row', alignItems: effectiveWidth < 768 ? 'stretch' : 'center', gap: effectiveWidth < 768 ? '5px' : '8px'}}>
                                        <div style={{flexShrink:0}}>
                                            <span style={{fontWeight:'600', fontSize:'13px'}}>{product ? product.label : ''}</span>
                                            <span style={{fontSize:'11px', color:'#999', marginLeft:'6px'}}>{supplier_id && supplier_id.value ? supplier_id.label : ""}</span>
                                        </div>
                                        <input
                                            type="text"
                                            defaultValue={remarks}
                                            onChange={e => handleRemarksChange(index, e)}
                                            placeholder="Remarks..."
                                            className="form-control"
                                            style={{fontStyle:'italic', flex: effectiveWidth < 768 ? undefined : 1}}
                                        />
                                    </div>
                                </>
                            ) : (
                                <>
                                    <input type="hidden" key={'product_'+index} value={invoiceproductid} name="invoiceproductid" className="form-control" />
                                    <div style={{display:'flex', alignItems:'center', gap:(effectiveWidth >= 768 && width < 1024) ? '3px' : '6px'}}>
                                        <div style={{flex:showSuppliers ? ((effectiveWidth >= 768 && width < 1024) ? 1.2 : 1.5) : ((effectiveWidth >= 768 && width < 1024) ? 2.2 : 3)}}>
                                            <Select key={'product_sel_'+index+'_'+(resetKey||0)} options={allProducts}
                                                menuPortalTarget={document.body}
                                                styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                                defaultValue={product} onChange={(evnt) => handleProductChange(index, evnt)} name="product" />
                                        </div>
                                        {showSuppliers && (<div style={{flex: (effectiveWidth >= 768 && width < 1024) ? 2.2 : 2.5, display:'flex', alignItems:'center', gap:(effectiveWidth >= 768 && width < 1024) ? '2px' : '4px'}}>
                                            <div style={{flex:1}}>
                                                <Select key={'supplier_sel_'+index+'_'+(resetKey||0)} isMulti={false}
                                                    menuPortalTarget={document.body}
                                                    styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                                                    options={supplier} defaultValue={supplier_id}
                                                    formatOptionLabel={formatSupplierOption}
                                                    onChange={(evnt) => handleSupplierChange(index, product, evnt)} name="supplier" />
                                            </div>
                                            {index == (rowsData.length - 1) && product && typeof product === 'object' && product.value ? (
                                                <div style={{flexShrink:0}}><AddStock key={"addstock_"+index} onSaveStock={onSaveStock} show={false} product={product} index={index} apiKey="" invoiceId={invoiceDetail.id} /></div>
                                            ) : null}
                                        </div>)}
                                        <div style={{flex: showSuppliers ? 0.8 : 1.2}}>
                                            <input
                                                key={'remarks_'+index+'_'+(resetKey||0)}
                                                type="text"
                                                defaultValue={remarks}
                                                onChange={e => handleRemarksChange(index, e)}
                                                placeholder="Remarks..."
                                                className="form-control"
                                                style={{fontStyle:'italic', padding: width < 1400 ? '5px 6px' : undefined}}
                                            />
                                        </div>
                                    </div>
                                    {rowError && (
                                        <div key={'err_'+index+'_'+rowError} className="row-inline-error">
                                            <i className="fa fa-exclamation-circle" style={{marginRight:'4px'}}></i>{rowError}
                                        </div>
                                    )}
                                </>
                            )}
                        </td>
                        <td style={{width:'70px'}}>
                            <input type="number" min="1" step="1" key={'quantity_'+index+'_'+(resetKey||0)} pattern="[0-9]*" onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }}
                                defaultValue={quantity}
                                disabled={fieldToggle}
                                onChange={e => { handleQtyChange(index, e); setQty(e.target.value); }}
                                name="qty" className="form-control product-qty" style={{padding:'5px 6px',width:'60px',border: rowsData[index]?.qtyWarning ? '2px solid #f59e0b' : undefined}} title={rowsData[index]?.available_qty != null ? 'Available: '+rowsData[index].available_qty : ''} />
                            {rowsData[index]?.qtyWarning && <div style={{color:'#b45309',fontSize:'10px',fontWeight:'600',marginTop:'2px',whiteSpace:'nowrap'}}>{rowsData[index].qtyWarning}</div>}
                        </td>
                        <td>
                            <div className="input-group" style={{flexWrap:'nowrap'}}>
                                <div className="input-group-prepend"><span className="input-group-text" style={{padding:'4px 6px',fontSize:'13px'}}>{props.currency}</span></div>
                                <input type="number" key={'price_'+index+'_'+(resetKey||0)} pattern="[0-9]*" min="0"
                                    defaultValue={price}
                                    disabled={fieldToggle}
                                    onChange={e => { handlePriceChange(index, e); setAmount(e.target.value); }}
                                    name="amount" className="form-control product-price" style={{padding:'5px 6px',minWidth:'100px'}} />
                            </div>
                        </td>
                        <td className='text-center' style={{verticalAlign:'middle',fontWeight:'700',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(totalPrice)}</td>
                        <td className='text-right text-nowrap' style={{width:'1%',verticalAlign:'middle'}}>
                            {((fieldToggle== "") && (invoiceproductid !="")) ?
                                <button className="btn btn-sm" style={{background:'rgb(234, 88, 12)',color:'#fff',border:'none',borderRadius:'8px',padding:'0 16px',fontSize:'12px',fontWeight:'600',height:'38px',lineHeight:'38px',marginRight:'4px'}} onClick={(evnt) =>handleUpdateChange(index,evnt)}><i className="fa fa-save" style={{marginRight:'4px'}}></i>Save</button>
                                : <div style={{display:'flex',gap:'6px'}}>
                                    <button className="btn btn-sm" style={{background:'rgb(234, 88, 12)',color:'#fff',border:'none',borderRadius:'8px',padding:'0 16px',fontSize:'12px',fontWeight:'600',height:'38px',lineHeight:'38px'}} disabled={isSavingNew} onClick={(evnt) =>handleToogleChange(index,evnt)}><i className="fa fa-save" style={{marginRight:'4px'}}></i>Save</button>
                                    <button className="btn btn-sm" style={{background:'#fff',border:'1.5px solid #e5e7eb',color:'#6b7280',borderRadius:'8px',padding:'0 16px',fontSize:'12px',fontWeight:'600',height:'38px',lineHeight:'36px'}} onClick={() => handleResetRow(index)}><i className="fa fa-undo" style={{marginRight:'4px'}}></i>Reset</button>
                                  </div>
                            }
                            {index == (rowsData.length - 1) ? null : (
                                    <button key={'submit_'+index} className="btn btn-sm" style={{width:'36px',height:'36px',padding:0,borderRadius:'8px',background:'#fef2f2',border:'1.5px solid #fca5a5',color:'#ef4444',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer'}} title="Delete" onClick={() => deleteTableRows(index, invoiceproductid)}><i className="fa fa-trash-o" style={{fontSize:'14px'}}></i></button>
                            )}
                        </td>
                    </tr>

					</>
					)}
					</React.Fragment>
                )
            })
        )
    }
}

if (document.getElementById('customer-invoice-app')) {
    const id = "customer-invoice-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<AlertProvider>
			<CustomerInvoiceApp {...props} />
		</AlertProvider>
    );
}

import React, {
  useEffect,
  useRef,
  useState,
  useImperativeHandle,
  forwardRef,
} from "react";
import { ToastContainer, toast } from "react-toastify";
import { Modal } from "react-bootstrap";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import axios from "axios";

import { formatTwoDecimal } from "./../hooks/utils";

const PAYMENT_METHODS = [
  { value: '2', label: 'Cash',          icon: 'fa-money' },
  { value: '4', label: 'Card',          icon: 'fa-credit-card' },
  { value: '5', label: 'Bank Transfer', icon: 'fa-university' },
  { value: '3', label: 'Cheque',        icon: 'fa-file-text-o' },
];

const PaymentSchema = Yup.object().shape({
  payment_method: Yup.string().required("Please select a payment method"),
  amount: Yup.number()
    .typeError("Amount must be a number")
    .min(0, "Amount cannot be negative")
    .required("Amount is required"),
  note: Yup.string().max(200, "Note cannot exceed 200 characters"),
});

export default function SupplierInvoicePayments(props) {
  const [showModal, setShowModal] = useState(false);
  const gridRef  = useRef();
  const formRef  = useRef();

  const handleClose = () => {
    setShowModal(false);
    if (props.onFormChange) props.onFormChange();
  };
  const handleShow  = () => setShowModal(true);

  const handleFormChange = () => {
    if (gridRef.current)  gridRef.current.reloadData();
    if (formRef.current)  formRef.current.refreshSummary();
    if (props.onFormChange) props.onFormChange();
  };

  return (
    <>
      <ToastContainer autoClose={3000} />
      <button
        onClick={handleShow}
        className="pay-invoice-btn"
        style={{background:'rgb(234, 88, 12)',color:'#fff',border:'none',borderRadius:'10px',padding:'8px 16px',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'8px',outline:'none',boxShadow:'0 2px 8px rgba(234,88,12,0.3)',transition:'all 0.2s ease',letterSpacing:'0.3px'}}
      >
        <i className="fa fa-credit-card" style={{fontSize:'13px'}}></i>
        {props.label || 'Pay Invoice'}
      </button>

      <Modal show={showModal} onHide={handleClose} dialogClassName="supp-payment-modal-wide">
        <style>{`
          .supp-payment-modal-wide { max-width: min(560px, 94vw) !important; margin-top: 80px !important; margin-bottom: 24px !important; }
          .supp-payment-modal-wide .modal-content{border-radius:18px;border:none;overflow:hidden;box-shadow:0 24px 60px -12px rgba(15,17,21,0.28),0 8px 20px -8px rgba(15,17,21,0.16);max-height:calc(100vh - 110px);}
          .supp-payment-modal-wide .modal-body{overflow-y:auto;}
          @media (max-width: 767px){ .supp-payment-modal-wide { margin-top: 64px !important; } .supp-payment-modal-wide .modal-content{ max-height:calc(100vh - 90px);} }
          /* Mobile: keep the Use Credit button on the right of the banner instead of wrapping below */
          @media (max-width: 767px){ .sip-credit-banner{ flex-wrap:nowrap !important; } .sip-credit-banner .sip-credit-actions{ flex-shrink:0; } }
        `}</style>
        <Modal.Header style={{borderBottom:'1px solid #eeeeef',padding:'18px 22px',display:'flex',alignItems:'center',gap:'10px'}}>
          <h2 style={{margin:0,fontSize:'17px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'-0.2px'}}>
            Invoice Payments
          </h2>
          {props.id && <span style={{padding:'2px 7px',borderRadius:'6px',background:'#fff7ed',color:'rgb(234, 88, 12)',fontSize:'11px',fontWeight:'800'}}>#{props.id}</span>}
          <div style={{flex:'1 1 0%'}}></div>
          <button type="button" onClick={handleClose} aria-label="Close" style={{width:'32px',height:'32px',borderRadius:'9px',background:'#f4f4f6',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#6b7280',cursor:'pointer',flexShrink:0,padding:0}}>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </Modal.Header>
        <Modal.Body className="bg-white text-dark" style={{padding:'18px 22px',overflowY:'auto',maxHeight:'75vh'}}>
          <PaymentForm ref={formRef} {...props} onClose={handleClose} onFormChange={handleFormChange} />
          <div style={{borderTop:'1px solid #f0f0f0',margin:'16px 0'}}></div>
          <PaymentGrid ref={gridRef} {...props} onPaymentDelete={handleFormChange} />
        </Modal.Body>
      </Modal>
    </>
  );
}

// ✅ Payment Form
const PaymentForm = forwardRef((props, ref) => {
  const [invoiceSummary, setInvoiceSummary]   = useState(null);
  const [creditBalance, setCreditBalance]     = useState(0);
  const [creditLoaded, setCreditLoaded]       = useState(false);
  const [applyCredit, setApplyCredit]         = useState(false);
  const [creditAmount, setCreditAmount]       = useState(0);
  const [creditSaving, setCreditSaving]       = useState(false);
  const formikRef = useRef();

  useImperativeHandle(ref, () => ({
    refreshSummary: () => fetchPendingAmount(),
  }));

  const fetchPendingAmount = async () => {
    try {
      const response = await axios.get(`/data_entry/purchase_entry/invoice_payment/view/${props.id}`);
      const details  = response.data.payload.details;
      if (details) {
        const total   = parseFloat(details.total)      || 0;
        const paid    = parseFloat(details.total_paid) || 0;
        const pending = Math.max(0, total - paid);
        setInvoiceSummary({ total, paid, pending });
        if (formikRef.current) {
          formikRef.current.setFieldValue('amount', pending > 0 ? formatTwoDecimal(pending) : '');
        }
        setApplyCredit(false);
        setCreditAmount(0);
      }
    } catch (err) {}
  };

  const fetchCreditBalance = async () => {
    const supplierId = props.supplier?.supplier_id || props.supplier?.id;
    if (!supplierId) return;
    try {
      const r = await axios.get('/supplier_return/view/credit-balance/' + supplierId);
      if (r.data.success) setCreditBalance(parseFloat(r.data.payload.available) || 0);
    } catch (err) {}
    setCreditLoaded(true);
  };

  useEffect(() => { fetchPendingAmount(); fetchCreditBalance(); }, [props.id, props.supplier]);

  const handleApplyCreditToggle = () => {
    const next = !applyCredit;
    setApplyCredit(next);
    if (next && invoiceSummary) {
      const auto    = Math.min(creditBalance, invoiceSummary.pending);
      const rounded = parseFloat(auto.toFixed(2));
      setCreditAmount(rounded);
    } else {
      setCreditAmount(0);
    }
  };

  const handleCreditAmountChange = (val) => {
    const maxCredit = invoiceSummary ? Math.min(creditBalance, invoiceSummary.pending) : creditBalance;
    const v = Math.min(Math.max(0, parseFloat(val) || 0), maxCredit);
    setCreditAmount(v);
  };

  const notifyError   = (msg) => toast.error(msg,   { position:"top-right", autoClose: 3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });
  const notifySuccess = (msg) => toast.success(msg, { position:"top-right", autoClose:3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });

  // Apply credit on its own (no cash) — saves a credit-only payment row.
  const handleApplyCreditNow = async () => {
    const curSym = props.currency || '£';
    if (creditSaving) return;
    if (!creditAmount || creditAmount <= 0) {
      notifyError("Please enter a credit amount.");
      return;
    }
    if (invoiceSummary && creditAmount > invoiceSummary.pending + 0.01) {
      notifyError(`Credit (${curSym} ${formatTwoDecimal(creditAmount)}) exceeds pending (${curSym} ${formatTwoDecimal(invoiceSummary.pending)}).`);
      return;
    }
    if (creditAmount > creditBalance + 0.01) {
      notifyError(`Credit exceeds available balance of ${curSym} ${formatTwoDecimal(creditBalance)}.`);
      return;
    }
    try {
      setCreditSaving(true);
      const supplierId = props.supplier?.supplier_id || props.supplier?.id;
      const payload = {
        id:             props.id,
        supplier_id:    supplierId,
        supplier:       props.supplier,
        payment_method: '2', // any valid method id, but actual amount is 0 so it just satisfies validation
        amount:         0,
        note:           '',
        creditAmount:   creditAmount,
      };
      const response = await axios.post("/data_entry/purchase_entry/invoice_payment/create", payload);
      if (response.data.success === true) {
        notifySuccess("Credit applied successfully!");
        setApplyCredit(false);
        setCreditAmount(0);
        fetchPendingAmount();
        fetchCreditBalance();
        if (props.onFormChange) props.onFormChange();
      } else if (response.data.success === false) {
        notifyError(response.data.payload || "Failed to apply credit.");
      }
    } catch (error) {
      notifyError("Something went wrong while applying credit.");
    } finally {
      setCreditSaving(false);
    }
  };

  const handleSubmit = async (values, { setSubmitting, resetForm }) => {
    const totalEffective = parseFloat(values.amount || 0) + (applyCredit ? creditAmount : 0);
    if (totalEffective <= 0) {
      notifyError("Please enter an amount or apply credit.");
      setSubmitting(false);
      return;
    }
    try {
      const supplierId = props.supplier?.supplier_id || props.supplier?.id;
      const payload = {
        id:             props.id,
        supplier_id:    supplierId,
        supplier:       props.supplier,
        payment_method: values.payment_method,
        amount:         parseFloat(values.amount || 0),
        note:           values.note || '',
        creditAmount:   applyCredit ? creditAmount : 0,
      };
      const response = await axios.post("/data_entry/purchase_entry/invoice_payment/create", payload);
      if (response.data.success === true) {
        notifySuccess("Payment saved successfully!");
        resetForm({ values: { payment_method: '2', amount: '', note: '' } });
        setApplyCredit(false);
        setCreditAmount(0);
        fetchPendingAmount();
        fetchCreditBalance();
        if (props.onFormChange) props.onFormChange();
      } else if (response.data.success === false) {
        notifyError(response.data.payload);
      }
    } catch (error) {
      notifyError("Something went wrong while saving.");
    } finally {
      setSubmitting(false);
    }
  };

  const cur = props.currency || '£';

  const summaryBadge = (label, value, color, bg, border, labelColor) => (
    <div style={{padding:'14px 16px',borderRadius:'12px',background:bg,border:`1px solid ${border}`}}>
      <div style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.7px',color:labelColor||'#0f1115',textTransform:'uppercase'}}>{label}</div>
      <div style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontSize:'22px',fontWeight:'800',color,marginTop:'6px',letterSpacing:'-0.5px'}}>{value === null ? '—' : `${cur} ${formatTwoDecimal(value)}`}</div>
    </div>
  );

  const isFullyPaid = invoiceSummary !== null && invoiceSummary.pending <= 0.01 && invoiceSummary.paid > 0;

  return (
    <div>
      {/* Summary Badges */}
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr',gap:'10px',marginBottom:'16px'}}>
        {summaryBadge('Total',   invoiceSummary?.total   ?? null, '#0f1115', '#fafafb', '#e8e8ec', '#0f1115')}
        {summaryBadge('Paid',    invoiceSummary?.paid    ?? null, '#15803d', '#e8f8ee', '#bde5c9', '#15803d')}
        {summaryBadge('Pending', invoiceSummary ? invoiceSummary.pending : null, '#b45309', '#fef7e5', '#f5d98c', '#b45309')}
      </div>

      {/* Fully Paid Banner */}
      {isFullyPaid && (
        <div style={{marginBottom:'16px',background:'#e8f8ee',border:'1px solid #bde5c9',borderRadius:'11px',padding:'12px 14px',display:'flex',alignItems:'center',gap:'10px'}}>
          <span style={{width:'22px',height:'22px',borderRadius:'50%',background:'#16a34a',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </span>
          <span style={{fontSize:'13.5px',fontWeight:'700',color:'#15803d'}}>Invoice is fully paid. No further payment required.</span>
        </div>
      )}

      {/* No Credit Banner */}
      {!isFullyPaid && creditLoaded && creditBalance === 0 && (
        <div style={{marginBottom:'16px',background:'#f8fafe',border:'1px solid #c7dbf5',borderRadius:'11px',padding:'10px 14px',display:'flex',alignItems:'center',gap:'10px',color:'#1d4ed8',fontSize:'12.5px',fontWeight:'600'}}>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          <span>You have no credit available. Take payment manually or use saved method.</span>
        </div>
      )}

      {/* Return Credit Banner */}
      {!isFullyPaid && creditBalance > 0 && invoiceSummary && invoiceSummary.pending > 0 && (
        <div className="sip-credit-banner" style={{marginBottom:'16px',background:'linear-gradient(135deg,#fff7ed,#ffedd5)',border:'1.5px solid #fdba74',borderRadius:'12px',padding:'12px 16px',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'12px',flexWrap:'wrap'}}>
          <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
            <div style={{width:'34px',height:'34px',borderRadius:'9px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 8px rgba(234,88,12,0.3)'}}>
              <i className="fa fa-gift" style={{color:'#fff',fontSize:'13px'}}></i>
            </div>
            <div>
              <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.6px'}}>Return Credit Available</div>
              <div style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)',lineHeight:'1.2'}}>{cur} {formatTwoDecimal(creditBalance)}</div>
            </div>
          </div>
          <div className="sip-credit-actions" style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
            {applyCredit ? (
              <>
                <div style={{display:'flex',alignItems:'center',gap:'5px'}}>
                  <span style={{fontSize:'11px',fontWeight:'600',color:'#9a3412'}}>Use:</span>
                  <input
                    type="number"
                    value={creditAmount}
                    onChange={(e) => handleCreditAmountChange(e.target.value)}
                    min="0"
                    max={creditBalance}
                    autoFocus
                    style={{width:'105px',height:'32px',borderRadius:'7px',border:'1.5px solid #fdba74',fontSize:'13px',fontWeight:'700',color:'rgb(234, 88, 12)',padding:'0 8px',outline:'none',background:'#fff',textAlign:'right',cursor:'text'}}
                  />
                </div>
                <button type="button" disabled={creditSaving || !creditAmount || creditAmount <= 0} onClick={handleApplyCreditNow} style={{
                  height:'32px',padding:'0 14px',borderRadius:'7px',
                  background: 'rgb(234, 88, 12)',color:'#fff',
                  border:'1.5px solid rgb(234, 88, 12)',
                  fontSize:'12px',fontWeight:'700',
                  cursor: (creditSaving || !creditAmount || creditAmount <= 0) ? 'not-allowed' : 'pointer',
                  opacity: (creditSaving || !creditAmount || creditAmount <= 0) ? 0.55 : 1,
                  whiteSpace:'nowrap',
                  display:'flex',alignItems:'center',gap:'5px',transition:'all 0.15s',
                }}>
                  <i className={`fa ${creditSaving ? 'fa-spinner fa-spin' : 'fa-check'}`}></i>
                  {creditSaving ? 'Applying...' : 'Apply'}
                </button>
                <button type="button" disabled={creditSaving} onClick={handleApplyCreditToggle} style={{
                  height:'32px',padding:'0 14px',borderRadius:'7px',
                  background:'#fff',color:'#64748b',
                  border:'1.5px solid #cbd5e1',
                  fontSize:'12px',fontWeight:'700',cursor: creditSaving ? 'not-allowed' : 'pointer',whiteSpace:'nowrap',
                  display:'flex',alignItems:'center',gap:'5px',transition:'all 0.15s',
                }}>
                  <i className="fa fa-times"></i>Cancel
                </button>
              </>
            ) : (
              <button type="button" onClick={handleApplyCreditToggle} style={{
                height:'32px',padding:'0 14px',borderRadius:'7px',
                background: '#fff',color:'rgb(234, 88, 12)',
                border:'1.5px solid rgb(234, 88, 12)',
                fontSize:'12px',fontWeight:'700',cursor:'pointer',whiteSpace:'nowrap',
                display:'flex',alignItems:'center',gap:'5px',transition:'all 0.15s',
              }}>
                <i className="fa fa-plus-circle"></i>Use Credit
              </button>
            )}
          </div>
        </div>
      )}

      {/* Credit usage hint */}
      {applyCredit && creditAmount > 0 && invoiceSummary && (
        <div style={{marginBottom:'14px',background:'#fff7ed',border:'1px dashed #fdba74',borderRadius:'8px',padding:'8px 14px',display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
          <i className="fa fa-info-circle" style={{color:'rgb(234, 88, 12)',fontSize:'13px'}}></i>
          <span style={{fontSize:'12px',fontWeight:'600',color:'#9a3412'}}>
            Click <strong>Apply</strong> to use {cur} {formatTwoDecimal(creditAmount)} credit. Pending will become <strong>{cur} {formatTwoDecimal(Math.max(0, invoiceSummary.pending - creditAmount))}</strong>.
          </span>
        </div>
      )}

      {!isFullyPaid && (
        <Formik
          innerRef={formikRef}
          initialValues={{ payment_method: '2', amount: '', note: '' }}
          validationSchema={PaymentSchema}
          onSubmit={handleSubmit}
        >
          {({ isSubmitting, values, setFieldValue }) => (
            <Form>
              {/* Payment Method Cards — spec UI */}
              <div style={{marginBottom:'16px'}}>
                <div style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase',marginBottom:'8px'}}>Payment Method</div>
                <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr 1fr',gap:'10px'}}>
                  {PAYMENT_METHODS.map(method => {
                    const selected = values.payment_method === method.value;
                    return (
                      <button type="button" key={method.value} onClick={() => setFieldValue('payment_method', method.value)} style={{
                        padding:'14px 12px',borderRadius:'12px',cursor:'pointer',
                        background:  selected ? '#fff7ed' : '#fff',
                        border:      selected ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e8e8ec',
                        boxShadow:   selected ? '0 0 0 4px rgba(234,88,12,0.18)' : '0 1px 2px rgba(15,17,21,0.04)',
                        display:'flex',flexDirection:'column',alignItems:'center',gap:'6px',
                        transition:'0.12s',outline:'none',
                      }}>
                        <i className={`fa ${method.icon}`} style={{fontSize:'18px',color:selected?'rgb(234, 88, 12)':'#6b7280'}}></i>
                        <span style={{fontSize:'12.5px',fontWeight:'800',color:selected?'rgb(234, 88, 12)':'#0f1115'}}>{method.label}</span>
                      </button>
                    );
                  })}
                </div>
                <ErrorMessage name="payment_method" component="div" className="text-danger small" style={{marginTop:'4px'}} />
              </div>

              {/* Amount + Note — spec UI */}
              <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px'}}>
                <div style={{display:'flex',flexDirection:'column',gap:'6px',minWidth:0}}>
                  <label style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase'}}>
                    Amount to Pay {applyCredit && creditAmount > 0 && <span style={{color:'#16a34a'}}>(after credit)</span>}
                  </label>
                  <div style={{height:'48px',borderRadius:'10px',border:'1px solid #e8e8ec',background:'#fff',display:'flex',alignItems:'center',gap:'8px',padding:'0 12px'}}>
                    <span style={{color:'#6b7280',fontSize:'13px',fontWeight:'600'}}>{cur}</span>
                    <Field type="number" name="amount" placeholder="0.00" style={{flex:'1 1 0%',border:'none',outline:'none',background:'transparent',fontSize:'14.5px',color:'#0f1115',minWidth:0,padding:0,fontFamily:'inherit'}} />
                  </div>
                  <ErrorMessage name="amount" component="div" className="text-danger small" />
                </div>
                <div style={{display:'flex',flexDirection:'column',gap:'6px',minWidth:0}}>
                  <label style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase'}}>Note (optional)</label>
                  <div style={{height:'48px',borderRadius:'10px',border:'1px solid #e8e8ec',background:'#fff',display:'flex',alignItems:'center',gap:'8px',padding:'0 12px'}}>
                    <Field type="text" name="note" placeholder="Enter a note" style={{flex:'1 1 0%',border:'none',outline:'none',background:'transparent',fontSize:'14.5px',color:'#0f1115',minWidth:0,padding:0,fontFamily:'inherit'}} />
                  </div>
                  <ErrorMessage name="note" component="div" className="text-danger small" />
                </div>
              </div>

              {/* Buttons — spec UI */}
              <div style={{display:'flex',justifyContent:'flex-end',gap:'10px',marginTop:'16px'}}>
                <button type="button" onClick={props.onClose} style={{height:'42px',padding:'0 16px',borderRadius:'10px',background:'#fff',color:'#0f1115',border:'1px solid #e8e8ec',fontWeight:'700',fontSize:'13.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',cursor:'pointer'}}>
                  Cancel
                </button>
                <button type="submit" disabled={isSubmitting} style={{height:'42px',padding:'0 16px',borderRadius:'10px',background:'rgb(234, 88, 12)',color:'#fff',border:'1px solid transparent',fontWeight:'700',fontSize:'13.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45)',cursor:'pointer',opacity:isSubmitting?0.7:1}}>
                  <svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                  {isSubmitting ? 'Saving...' : 'Save Payment'}
                </button>
              </div>
            </Form>
          )}
        </Formik>
      )}
    </div>
  );
});

// ✅ Payment Grid
const PaymentGrid = forwardRef((props, ref) => {
  const [payments,     setPayments]     = useState([]);
  const [details,      setDetails]      = useState([]);
  const [loading,      setLoading]      = useState(false);
  const [activeFilter, setActiveFilter] = useState('all');

  const notifyError   = (msg) => toast.error(msg,   { position:"top-right", autoClose: 3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });
  const notifySuccess = (msg) => toast.success(msg, { position:"top-right", autoClose:3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });

  const fetchPaymentData = async (id) => {
    try {
      setLoading(true);
      const response = await axios.get(`/data_entry/purchase_entry/invoice_payment/view/${id}`);
      setPayments(response.data.payload.list    || []);
      setDetails(response.data.payload.details  || []);
    } catch (error) {
      console.error("Error fetching payment data:", error);
    } finally {
      setLoading(false);
    }
  };

  const removePaymentData = async (id, supplier_id, supplier_invoice_id) => {
    try {
      setLoading(true);
      const response = await axios.post(`/data_entry/sales_entry/invoice_payment/delete`, { id, supplier_id, supplier_invoice_id });
      if (response.data.success === true) {
        notifySuccess("Payment removed successfully!");
        fetchPaymentData(props.id);
        if (props.onPaymentDelete) props.onPaymentDelete();
      } else if (response.data.success === false) {
        notifyError(response.data.payload);
      }
    } catch (error) {
      console.error("Error removing payment:", error);
    } finally {
      setLoading(false);
    }
  };

  useImperativeHandle(ref, () => ({
    reloadData: () => fetchPaymentData(props.id),
  }));

  useEffect(() => { fetchPaymentData(props.id); }, [props.id]);

  const methodLabel = (id) => PAYMENT_METHODS.find(m => m.value === String(id))?.label || id;

  const hasCashPart   = (p) => (parseFloat(p.amount) - parseFloat(p.credit_used || 0)) > 0;
  const hasCreditPart = (p) => parseFloat(p.credit_used || 0) > 0;

  const filteredPayments = (() => {
    if (activeFilter === 'all')    return payments;
    if (activeFilter === 'credit') return payments.filter(hasCreditPart);
    return payments.filter(p => String(p.payment_id) === activeFilter && hasCashPart(p));
  })();

  const filterOptions = [
    { value: 'all', label: 'All' },
    ...PAYMENT_METHODS.filter(m => payments.some(p =>
      String(p.payment_id) === m.value && hasCashPart(p)
    )),
    ...(payments.some(hasCreditPart) ? [{ value: 'credit', label: 'Credit' }] : []),
  ];

  return (
    <div>
      <div style={{display:'flex',alignItems:'center',gap:'10px',marginBottom:'10px'}}>
        <span style={{fontSize:'14px',fontWeight:'800',color:'#0f1115'}}>Payment History</span>
        <span style={{minWidth:'22px',height:'22px',padding:'0 6px',borderRadius:'11px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'11.5px',fontWeight:'800',display:'inline-flex',alignItems:'center',justifyContent:'center'}}>
          {payments.length}
        </span>
        <div style={{flex:'1 1 0%'}}></div>
        {details.type && (
          <span style={{display:'inline-flex',alignItems:'center',gap:'5px',padding:'3px 9px',borderRadius:'999px',background:'#fff7ed',color:'rgb(234, 88, 12)',border:'1px solid #fed7aa',fontSize:'11px',fontWeight:'700',letterSpacing:'0.1px',lineHeight:'1.4'}}>
            {details.type}: {props.currency} {details.paid}
          </span>
        )}
      </div>

      {payments.length > 0 && (
        <div style={{display:'flex',gap:'6px',flexWrap:'wrap',marginBottom:'12px'}}>
          {filterOptions.map(opt => {
            const active = activeFilter === opt.value;
            return (
              <button key={opt.value} onClick={() => setActiveFilter(opt.value)} style={{
                height:'30px',padding:'0 12px',fontSize:'12px',fontWeight:'700',
                borderRadius:'99px',
                border:      active ? '1px solid rgb(234, 88, 12)' : '1px solid #e8e8ec',
                background:  active ? 'rgb(234, 88, 12)' : '#fff',
                color:       active ? '#fff'    : '#0f1115',
                cursor:'pointer',transition:'all 0.15s',outline:'none',
              }}>
                {opt.label}
              </button>
            );
          })}
        </div>
      )}

      {loading && <div style={{textAlign:'center',padding:'20px',color:'#aaa',fontSize:'13px'}}>Loading...</div>}

      {!loading && payments.length === 0 && (
        <div style={{textAlign:'center',padding:'24px',color:'#bbb',fontSize:'13px',background:'#fafafa',borderRadius:'8px',border:'1px dashed #e5e7eb'}}>
          No payments recorded yet.
        </div>
      )}

      {!loading && payments.length > 0 && filteredPayments.length === 0 && (
        <div style={{textAlign:'center',padding:'24px',color:'#bbb',fontSize:'13px',background:'#fafafa',borderRadius:'8px',border:'1px dashed #e5e7eb'}}>
          No payments for this method.
        </div>
      )}

      {!loading && filteredPayments.length > 0 && (
        <div style={{display:'flex',flexDirection:'column',gap:'10px',maxHeight:'320px',overflowY:'auto',paddingRight:'6px'}}>
          {filteredPayments.map((row) => (
            <div key={row.id} style={{border:'1px solid #e8e8ec',borderRadius:'11px',padding:'12px 14px',display:'flex',alignItems:'center',gap:'14px'}}>
              <span style={{width:'34px',height:'34px',borderRadius:'9px',background:'#e8f8ee',color:'#15803d',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg>
              </span>
              <div style={{flex:'1 1 0%',minWidth:0}}>
              {(() => {
                const cashPart   = parseFloat(row.amount) - parseFloat(row.credit_used || 0);
                const creditPart = parseFloat(row.credit_used || 0);
                return (
                  <div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
                    {cashPart > 0 && (
                      <>
                        <span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontWeight:'800',fontSize:'15px',color:'#0f1115'}}>
                          {props.currency} {formatTwoDecimal(cashPart)}
                        </span>
                        <span style={{display:'inline-flex',alignItems:'center',gap:'5px',padding:'3px 9px',borderRadius:'999px',background:'#fff7f0',color:'rgb(234, 88, 12)',border:'1px solid #f6c9a8',fontSize:'11px',fontWeight:'700'}}>
                          {methodLabel(row.payment_id)}
                        </span>
                      </>
                    )}
                    {creditPart > 0 && (
                      <>
                        <span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontWeight:'800',fontSize:'15px',color:'#0f1115'}}>
                          {props.currency} {formatTwoDecimal(creditPart)}
                        </span>
                        <span style={{display:'inline-flex',alignItems:'center',gap:'4px',padding:'3px 9px',borderRadius:'999px',background:'#e8f8ee',color:'#15803d',border:'1px solid #bde5c9',fontSize:'11px',fontWeight:'700'}}>
                          <i className="fa fa-gift"></i>Credit
                        </span>
                      </>
                    )}
                    {cashPart <= 0 && creditPart <= 0 && (
                      <span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontWeight:'800',fontSize:'15px',color:'#0f1115'}}>
                        {props.currency} {formatTwoDecimal(row.amount)}
                      </span>
                    )}
                  </div>
                );
              })()}
              <div style={{fontSize:'11.5px',color:'#6b7280',marginTop:'2px',display:'inline-flex',alignItems:'center',gap:'6px'}}>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {row.created_at || '-'}
              </div>
              {row.note && (
                <div style={{fontSize:'12px',color:'#666',marginTop:'4px'}}>
                  <i className="fa fa-comment-o" style={{marginRight:'5px',color:'#bbb'}}></i>{row.note}
                </div>
              )}
              </div>
              <button onClick={() => removePaymentData(row.id, row.supplier_id, row.supplier_invoice_id)} style={{height:'34px',padding:'0 12px',borderRadius:'10px',background:'#fff',color:'#b91c1c',border:'1px solid #f8d2d2',fontWeight:'700',fontSize:'12.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',cursor:'pointer',flexShrink:0,whiteSpace:'nowrap'}}>
                <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                Delete
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
});

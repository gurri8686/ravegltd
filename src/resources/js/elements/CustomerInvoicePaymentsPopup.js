import React, {
    useEffect,
    useRef,
    useState,
    useImperativeHandle,
    forwardRef,
  } from "react";
  import Select from "react-select";
  import Button from "react-bootstrap/Button";
  import { ToastContainer, toast } from "react-toastify";
  import { Modal } from "react-bootstrap";
  import { Formik, Form, Field, ErrorMessage } from "formik";
  import * as Yup from "yup";
  import axios from "axios";

  import { formatTwoDecimal } from "./../hooks/utils";
  import { useWindowSize } from "./../hooks/useWindowSize";

  const PAYMENT_METHODS = [
    { value: '2', label: 'Cash',          icon: 'fa-money' },
    { value: '4', label: 'Card',          icon: 'fa-credit-card' },
    { value: '5', label: 'Bank Transfer', icon: 'fa-university' },
    { value: '3', label: 'Cheque',        icon: 'fa-file-text-o' },
  ];

  // ✅ Validation schema — amount can be 0 when fully covered by credit
  const PaymentSchema = Yup.object().shape({
    payment_method: Yup.string().required("Please select a payment method"),
    amount: Yup.number()
      .typeError("Amount must be a number")
      .min(0, "Amount cannot be negative")
      .required("Amount is required"),
    note: Yup.string().max(200, "Note cannot exceed 200 characters"),
  });

  // ✅ Main Component
  export default function CustomerInvoicePaymentsPopup(props) {
    const [showModal, setShowModal] = useState(false);
    const gridRef = useRef();
    const formRef = useRef();

    const handleClose = () => setShowModal(false);
    const handleShow = () => setShowModal(true);

    const handleFormChange = () => {
      if (gridRef.current) gridRef.current.reloadData();
      if (formRef.current) formRef.current.refreshSummary();
      if (props.onFormChange) props.onFormChange();
    };

    return (
      <>
        <button onClick={handleShow} className="pay-invoice-btn" style={{background:'#FF6B00',color:'#fff',border:'none',borderRadius:'10px',padding:'8px 16px',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'8px',outline:'none',boxShadow:'0 2px 8px rgba(255,107,0,0.3)',transition:'all 0.2s ease',letterSpacing:'0.3px',whiteSpace:'nowrap',flexShrink:0}}>
          <i className="fa fa-credit-card" style={{fontSize:'13px'}}></i> Pay Invoice
        </button>

        <Modal show={showModal} onHide={handleClose} dialogClassName="cust-payment-modal-wide">
          <style>{`
            .cust-payment-modal-wide { max-width: min(560px, 94vw) !important; margin-top: 80px !important; margin-bottom: 24px !important; }
            .cust-payment-modal-wide .modal-content{border-radius:18px;border:none;overflow:hidden;box-shadow:0 24px 60px -12px rgba(15,17,21,0.28),0 8px 20px -8px rgba(15,17,21,0.16);max-height:calc(100vh - 110px);}
            .cust-payment-modal-wide .modal-body{overflow-y:auto;}
            @media (max-width: 767px){ .cust-payment-modal-wide { margin-top: 64px !important; } .cust-payment-modal-wide .modal-content{ max-height:calc(100vh - 90px);} }
          `}</style>
          <Modal.Header style={{borderBottom:'1px solid #eeeeef',padding:'18px 22px',display:'flex',alignItems:'center',gap:'10px'}}>
            <h2 style={{margin:0,fontSize:'17px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'-0.2px'}}>
              Invoice Payments
            </h2>
            {props.id && <span style={{padding:'2px 7px',borderRadius:'6px',background:'#fff7ed',color:'rgb(234, 88, 12)',fontSize:'11px',fontWeight:'800'}}>#{props.id}</span>}
            <div style={{flex:'1 1 0%'}}></div>
            <button
              type="button"
              onClick={handleClose}
              aria-label="Close"
              style={{width:'32px',height:'32px',borderRadius:'9px',background:'#f4f4f6',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',justifyContent:'center',color:'#6b7280',cursor:'pointer',flexShrink:0,padding:0}}
            >
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

  // ✅ Payment Form Component
  const PaymentForm = forwardRef((props, ref) => {
    const [invoiceSummary, setInvoiceSummary] = useState(null);
    const [creditBalance, setCreditBalance] = useState(0);
    const [creditLoaded, setCreditLoaded] = useState(false);
    const [applyCredit, setApplyCredit] = useState(false);
    const [creditAmount, setCreditAmount] = useState(0);
    const formikRef = useRef();
    const { width } = useWindowSize();
    const isMobile = width < 768;

    useImperativeHandle(ref, () => ({
      refreshSummary: () => fetchPendingAmount(),
    }));

    const fetchPendingAmount = async () => {
      try {
        const response = await axios.get(`/data_entry/sales_entry/invoice_payment/view/${props.id}`);
        const details = response.data.payload.details;
        if (details) {
          const total = parseFloat(details.total) || 0;
          const totalPaid = parseFloat(details.total_paid) || 0;
          const pending = total - totalPaid;
          setInvoiceSummary({
            total,
            paid: totalPaid,
            pending: Math.max(0, pending),
            credit: pending < 0 ? Math.abs(pending) : 0,
          });
          if (formikRef.current) {
            formikRef.current.setFieldValue('amount', pending > 0 ? formatTwoDecimal(pending) : '');
          }
          // Reset credit on refresh
          setApplyCredit(false);
          setCreditAmount(0);
        }
      } catch (err) {
        // silently fail
      }
    };

    // Fetch credit balance for this customer
    const fetchCreditBalance = async () => {
      const customerId = props.customer?.customer_id || props.customer;
      if (!customerId) return;
      try {
        const r = await axios.get('/customer_return/view/credit-balance/' + customerId);
        if (r.data.success) setCreditBalance(parseFloat(r.data.payload.available) || 0);
      } catch (err) {}
      setCreditLoaded(true);
    };

    useEffect(() => { fetchPendingAmount(); fetchCreditBalance(); }, [props.id, props.customer]);

    const handleApplyCreditToggle = () => {
      const next = !applyCredit;
      setApplyCredit(next);
      if (next && invoiceSummary) {
        const auto = Math.min(creditBalance, invoiceSummary.pending);
        const rounded = parseFloat(auto.toFixed(2));
        setCreditAmount(rounded);
        // Reduce cash amount by credit
        const cashLeft = Math.max(0, invoiceSummary.pending - rounded);
        if (formikRef.current) {
          formikRef.current.setFieldValue('amount', formatTwoDecimal(cashLeft));
        }
      } else {
        setCreditAmount(0);
        if (formikRef.current && invoiceSummary) {
          formikRef.current.setFieldValue('amount', formatTwoDecimal(invoiceSummary.pending));
        }
      }
    };

    const handleCreditAmountChange = (val) => {
      const maxCredit = invoiceSummary ? Math.min(creditBalance, invoiceSummary.pending) : creditBalance;
      const v = Math.min(Math.max(0, parseFloat(val) || 0), maxCredit);
      setCreditAmount(v);
      if (formikRef.current && invoiceSummary) {
        const cashLeft = Math.max(0, invoiceSummary.pending - v);
        formikRef.current.setFieldValue('amount', formatTwoDecimal(cashLeft));
      }
    };

    const notifyError = (error) =>
      toast.error(error, { position:"top-right", autoClose: 3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });

    const notifySuccess = (success) =>
      toast.success(success, { position:"top-right", autoClose:3000, theme:"light", pauseOnHover:false, pauseOnFocusLoss:false });

    const handleSubmit = async (values, { setSubmitting, resetForm }) => {
      // Block payment if invoice has no products (total is 0)
      if (!invoiceSummary || invoiceSummary.total <= 0) {
        notifyError("Cannot process payment. This invoice has no products.");
        setSubmitting(false);
        return;
      }
      // Validate: cash + credit must cover > 0
      const totalEffective = parseFloat(values.amount || 0) + (applyCredit ? creditAmount : 0);
      if (totalEffective <= 0) {
        notifyError("Please enter an amount or apply credit.");
        setSubmitting(false);
        return;
      }
      // Validate: payment cannot exceed pending balance
      if (invoiceSummary && totalEffective > invoiceSummary.pending + 0.01) {
        notifyError(`Payment amount (${cur} ${formatTwoDecimal(totalEffective)}) exceeds the pending balance (${cur} ${formatTwoDecimal(invoiceSummary.pending)}).`);
        setSubmitting(false);
        return;
      }
      try {
        const payload = {
          id: props.id,
          customer: props.customer?.customer_id || props.customer,
          payment_method: values.payment_method,
          amount: parseFloat(values.amount || 0),
          note: values.note || '',
          creditAmount: applyCredit ? creditAmount : 0,
        };
        const response = await axios.post("/data_entry/sales_entry/invoice_payment/create", payload);
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
        console.error("Error saving payment:", error);
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

    const isFullyPaid = invoiceSummary !== null && invoiceSummary.pending === 0 && invoiceSummary.paid > 0;
    const hasCredit = invoiceSummary !== null && invoiceSummary.credit > 0;

    return (
      <div>
        {/* Summary Badges */}
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr',gap:'10px',marginBottom:'16px'}}>
          {summaryBadge('Total', invoiceSummary?.total ?? null, '#0f1115', '#fafafb', '#e8e8ec', '#0f1115')}
          {summaryBadge('Paid', invoiceSummary?.paid ?? null, '#15803d', '#e8f8ee', '#bde5c9', '#15803d')}
          {hasCredit
            ? summaryBadge('Credit', invoiceSummary.credit, '#b91c1c', '#fef2f2', '#f8d2d2', '#b91c1c')
            : summaryBadge('Pending', invoiceSummary ? Math.max(0, invoiceSummary.pending - (applyCredit ? creditAmount : 0)) : null, '#b45309', '#fef7e5', '#f5d98c', '#b45309')
          }
        </div>

        {/* Fully Paid / Credit Banner */}
        {hasCredit && (
          <div style={{marginBottom:'16px',background:'#fef2f2',border:'1.5px solid #fca5a5',borderRadius:'10px',padding:'12px 16px',display:'flex',alignItems:'center',gap:'10px'}}>
            <i className="fa fa-exclamation-circle" style={{color:'#dc2626',fontSize:'18px'}}></i>
            <span style={{fontSize:'13px',fontWeight:'700',color:'#dc2626'}}>Customer has overpaid by {cur} {formatTwoDecimal(invoiceSummary.credit)}. This amount is available as credit.</span>
          </div>
        )}
        {isFullyPaid && !hasCredit && (
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
          <div style={{marginBottom:'16px',background:'linear-gradient(135deg,#fff7ed,#ffedd5)',border:'1.5px solid #fdba74',borderRadius:'12px',padding:'12px 16px',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'12px',flexWrap:'wrap'}}>
            <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
              <div style={{width:'34px',height:'34px',borderRadius:'9px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 8px rgba(234,88,12,0.3)'}}>
                <i className="fa fa-gift" style={{color:'#fff',fontSize:'13px'}}></i>
              </div>
              <div>
                <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.6px'}}>Return Credit Available</div>
                <div style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)',lineHeight:'1.2'}}>{cur} {formatTwoDecimal(creditBalance)}</div>
              </div>
            </div>
            <div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
              {applyCredit && (
                <div style={{display:'flex',alignItems:'center',gap:'5px'}}>
                  <span style={{fontSize:'11px',fontWeight:'600',color:'#9a3412'}}>Apply:</span>
                  <input
                    type="number"
                    value={creditAmount}
                    onChange={(e) => handleCreditAmountChange(e.target.value)}
                    min="0"
                    max={creditBalance}
                    style={{width:'85px',height:'30px',borderRadius:'7px',border:'1.5px solid #fdba74',fontSize:'13px',fontWeight:'700',color:'rgb(234, 88, 12)',padding:'0 8px',outline:'none',background:'#fff',textAlign:'right',cursor:'text'}}
                  />
                </div>
              )}
              <button type="button" onClick={handleApplyCreditToggle} style={{
                height:'32px',padding:'0 14px',borderRadius:'7px',
                background: applyCredit ? 'rgb(234, 88, 12)' : '#fff',
                color: applyCredit ? '#fff' : 'rgb(234, 88, 12)',
                border: '1.5px solid rgb(234, 88, 12)',
                fontSize:'12px',fontWeight:'700',cursor:'pointer',whiteSpace:'nowrap',
                display:'flex',alignItems:'center',gap:'5px',transition:'all 0.15s',
              }}>
                <i className={`fa ${applyCredit ? 'fa-check-circle' : 'fa-plus-circle'}`}></i>
                {applyCredit ? 'Credit Applied' : 'Apply Credit'}
              </button>
            </div>
          </div>
        )}

        {/* Credit breakdown info */}
        {applyCredit && creditAmount > 0 && invoiceSummary && (
          <div style={{marginBottom:'14px',background:'#f0fdf4',border:'1px solid #86efac',borderRadius:'8px',padding:'8px 14px',display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
            <i className="fa fa-info-circle" style={{color:'#16a34a',fontSize:'13px'}}></i>
            <span style={{fontSize:'12px',fontWeight:'600',color:'#15803d'}}>
              {cur} {formatTwoDecimal(creditAmount)} credit applied →
              You only pay: <strong>{cur} {formatTwoDecimal(Math.max(0, invoiceSummary.pending - creditAmount))}</strong>
            </span>
          </div>
        )}

        {!isFullyPaid && <Formik
          innerRef={formikRef}
          initialValues={{ payment_method: '2', amount: '', note: '' }}
          validationSchema={PaymentSchema}
          onSubmit={handleSubmit}
        >
          {({ isSubmitting, values, setFieldValue }) => (
            <Form>
              {/* Payment Method Cards — spec UI */}
              <div style={{marginBottom:'16px'}}>
                <div style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase',marginBottom:'8px'}}>
                  Payment Method
                </div>
                <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr 1fr',gap:'10px'}}>
                  {PAYMENT_METHODS.map(method => {
                    const selected = values.payment_method === method.value;
                    return (
                      <button
                        type="button"
                        key={method.value}
                        onClick={() => setFieldValue('payment_method', method.value)}
                        style={{
                          padding:'14px 12px',
                          borderRadius:'12px',
                          cursor:'pointer',
                          background: selected ? '#fff7ed' : '#fff',
                          border: selected ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e8e8ec',
                          boxShadow: selected ? '0 0 0 4px rgba(234,88,12,0.18)' : '0 1px 2px rgba(15,17,21,0.04)',
                          display:'flex',flexDirection:'column',alignItems:'center',gap:'6px',
                          transition:'0.12s',outline:'none',
                        }}
                      >
                        <i className={`fa ${method.icon}`} style={{fontSize:'18px',color:selected?'rgb(234, 88, 12)':'#6b7280'}}></i>
                        <span style={{fontSize:'12.5px',fontWeight:'800',color:selected?'rgb(234, 88, 12)':'#0f1115'}}>{isMobile && method.value === '5' ? 'Bank' : method.label}</span>
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
                    <Field
                      type="number"
                      name="amount"
                      placeholder="0.00"
                      style={{flex:'1 1 0%',border:'none',outline:'none',background:'transparent',fontSize:'14.5px',color:'#0f1115',minWidth:0,padding:0,fontFamily:'inherit'}}
                    />
                  </div>
                  <ErrorMessage name="amount" component="div" className="text-danger small" />
                </div>
                <div style={{display:'flex',flexDirection:'column',gap:'6px',minWidth:0}}>
                  <label style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase'}}>Note (optional)</label>
                  <div style={{height:'48px',borderRadius:'10px',border:'1px solid #e8e8ec',background:'#fff',display:'flex',alignItems:'center',gap:'8px',padding:'0 12px'}}>
                    <Field
                      type="text"
                      name="note"
                      placeholder="Enter a note"
                      style={{flex:'1 1 0%',border:'none',outline:'none',background:'transparent',fontSize:'14.5px',color:'#0f1115',minWidth:0,padding:0,fontFamily:'inherit'}}
                    />
                  </div>
                  <ErrorMessage name="note" component="div" className="text-danger small" />
                </div>
              </div>

              {/* Buttons — mobile: full-width Cancel + Save Payment; desktop: original right-aligned spec */}
              <div style={isMobile
                ? {display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',marginTop:'18px'}
                : {display:'flex',justifyContent:'flex-end',gap:'10px',marginTop:'16px'}}>
                <button
                  type="button"
                  onClick={props.onClose}
                  style={isMobile
                    ? {height:'50px',borderRadius:'12px',background:'#fff',color:'#0f1115',border:'1px solid #e8e8ec',fontWeight:'700',fontSize:'15px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',cursor:'pointer'}
                    : {height:'42px',padding:'0 16px',borderRadius:'10px',background:'#fff',color:'#0f1115',border:'1px solid #e8e8ec',fontWeight:'700',fontSize:'13.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'0 1px 2px rgba(15,17,21,0.04)',cursor:'pointer'}}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isSubmitting}
                  style={isMobile
                    ? {height:'50px',borderRadius:'12px',background:'rgb(234, 88, 12)',color:'#fff',border:'1px solid transparent',fontWeight:'800',fontSize:'15px',letterSpacing:'0.2px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.3),0 6px 16px -4px rgba(234,88,12,0.45)',cursor:'pointer',opacity:isSubmitting?0.7:1}
                    : {height:'42px',padding:'0 16px',borderRadius:'10px',background:'rgb(234, 88, 12)',color:'#fff',border:'1px solid transparent',fontWeight:'700',fontSize:'13.5px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45)',cursor:'pointer',opacity:isSubmitting?0.7:1}}
                >
                  <svg width={isMobile ? "16" : "15.5"} height={isMobile ? "16" : "15.5"} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={isMobile ? "2.4" : "2"} strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                  {isSubmitting ? 'Saving...' : 'Save Payment'}
                </button>
              </div>
            </Form>
          )}
        </Formik>}
      </div>
    );
  });

  // ✅ Payment Grid Component
  const PaymentGrid = forwardRef((props, ref) => {
    const [payments, setPayments] = useState([]);
    const [details, setDetails] = useState([]);
    const [loading, setLoading] = useState(false);
    const [activeFilter, setActiveFilter] = useState('all');
    const { width } = useWindowSize();
    const isMobile = width < 768;

    const notifyError = (error) =>
      toast.error(error, { position:"top-right", autoClose: 3000, theme:"light" });
    const notifySuccess = (success) =>
      toast.success(success, { position:"top-right", autoClose:3000, theme:"light" });

    const fetchPaymentData = async (id) => {
      try {
        setLoading(true);
        const response = await axios.get(`/data_entry/sales_entry/invoice_payment/view/${id}`);
        setPayments(response.data.payload.list || []);
        setDetails(response.data.payload.details || []);
      } catch (error) {
        console.error("Error fetching payment data:", error);
      } finally {
        setLoading(false);
      }
    };

    const removePaymentData = async (id, customer_id, customer_invoice_id) => {
      try {
        setLoading(true);
        const response = await axios.post(`/data_entry/sales_entry/invoice_payment/delete`, {id, customer_id, customer_invoice_id});
        if (response.data.success === true) {
          notifySuccess("Payment removed successfully!");
          fetchPaymentData(props.id);
          if (props.onPaymentDelete) props.onPaymentDelete();
        } else if (response.data.success === false) {
          notifyError(response.data.payload);
        }
      } catch (error) {
        console.error("Error fetching payment data:", error);
      } finally {
        setLoading(false);
      }
    };

    useImperativeHandle(ref, () => ({
      reloadData: () => fetchPaymentData(props.id),
    }));

    useEffect(() => {
      fetchPaymentData(props.id);
    }, [props.id]);

    const methodLabel = (id) => PAYMENT_METHODS.find(m => m.value === String(id))?.label || id;

    const filteredPayments = activeFilter === 'all'
      ? payments
      : payments.filter(p => String(p.payment_id) === activeFilter);

    const filterOptions = [
      { value: 'all', label: 'All' },
      ...PAYMENT_METHODS,
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
                <button
                  key={opt.value}
                  onClick={() => setActiveFilter(opt.value)}
                  style={{
                    height:'30px',
                    padding:'0 12px',
                    fontSize:'12px',
                    fontWeight:'700',
                    borderRadius:'99px',
                    border: active ? '1px solid rgb(234, 88, 12)' : '1px solid #e8e8ec',
                    background: active ? 'rgb(234, 88, 12)' : '#fff',
                    color: active ? '#fff' : '#0f1115',
                    cursor:'pointer',
                    transition:'all 0.15s',
                    outline:'none',
                    boxShadow:'none',
                  }}
                >
                  {opt.label}
                </button>
              );
            })}
          </div>
        )}

        {loading && (
          <div style={{textAlign:'center',padding:'20px',color:'#aaa',fontSize:'13px'}}>Loading...</div>
        )}

        {!loading && payments.length === 0 && (
          isMobile ? (
          <div style={{textAlign:'center',padding:'28px 24px',background:'#f6f7f9',borderRadius:'14px'}}>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" style={{marginBottom:'10px'}}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
            <div style={{fontSize:'13px',fontWeight:'600',color:'#94a3b8'}}>No payments recorded yet.</div>
          </div>
          ) : (
          <div style={{textAlign:'center',padding:'24px',color:'#bbb',fontSize:'13px',background:'#fafafa',borderRadius:'8px',border:'1px dashed #e5e7eb'}}>
            No payments recorded yet.
          </div>
          )
        )}

        {!loading && payments.length > 0 && filteredPayments.length === 0 && (
          isMobile ? (
          <div style={{textAlign:'center',padding:'28px 24px',background:'#f6f7f9',borderRadius:'14px'}}>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" style={{marginBottom:'10px'}}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
            <div style={{fontSize:'13px',fontWeight:'600',color:'#94a3b8'}}>No payments for this method.</div>
          </div>
          ) : (
          <div style={{textAlign:'center',padding:'24px',color:'#bbb',fontSize:'13px',background:'#fafafa',borderRadius:'8px',border:'1px dashed #e5e7eb'}}>
            No payments for this method.
          </div>
          )
        )}

        {!loading && filteredPayments.length > 0 && (
          <div className="payment-history-scroll" style={{display:'flex',flexDirection:'column',gap:'10px',maxHeight:'320px',overflowY:'auto',paddingRight:'6px'}}>
            {filteredPayments.map((row) => {
              const isCreditRow = (row.credit_used || 0) > 0;
              const displayAmount = isCreditRow ? row.credit_used : row.amount;
              const methodId = row.payment_id;

              // Icon per payment type
              const renderIcon = () => {
                if (isCreditRow) {
                  // Return arrows (refresh/cycle)
                  return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>);
                }
                if (methodId == 2) {
                  // Cash — money/wallet
                  return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg>);
                }
                if (methodId == 4) {
                  // Card
                  return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>);
                }
                if (methodId == 5) {
                  // Bank Transfer — bank/building columns
                  return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 21h18"/><path d="M5 21V10l7-5 7 5v11"/><path d="M9 21v-7M15 21v-7M12 14v7"/></svg>);
                }
                if (methodId == 3) {
                  // Cheque — document/file
                  return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>);
                }
                // Fallback — generic dollar/money
                return (<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>);
              };

              return (
              <div key={row.id} style={{border:'1px solid #e8e8ec',borderRadius:'11px',padding:'14px 16px',display:'flex',alignItems:'center',gap:'14px',background:'#fff'}}>
                {/* Icon */}
                <span style={{width:'40px',height:'40px',borderRadius:'10px',background:'#e8f8ee',color:'#15803d',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                  {renderIcon()}
                </span>

                {/* Main content */}
                <div style={{flex:'1 1 0%',minWidth:0,display:'flex',flexDirection:'column',gap:'6px'}}>
                  {/* Amount + Method pill */}
                  <div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap'}}>
                    <span style={{fontFamily:'ui-monospace,SFMono-Regular,Menlo,monospace',fontWeight:'800',fontSize:'17px',color:'#0f1115',letterSpacing:'-0.3px'}}>
                      {props.currency} {formatTwoDecimal(displayAmount)}
                    </span>
                    <span style={{display:'inline-flex',alignItems:'center',gap:'5px',padding:'4px 11px',borderRadius:'999px',background:'#fff7ed',color:'rgb(234, 88, 12)',border:'1px solid #fed7aa',fontSize:'11.5px',fontWeight:'700',letterSpacing:'0.1px'}}>
                      {isCreditRow ? 'Return Credit' : methodLabel(row.payment_id)}
                    </span>
                  </div>

                  {/* Date + Note */}
                  <div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap',fontSize:'12px',color:'#6b7280'}}>
                    <span style={{display:'inline-flex',alignItems:'center',gap:'5px'}}>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                      {row.created_at || '-'}
                    </span>
                    {isCreditRow && (
                      <>
                        <span style={{color:'#cbd5e1'}}>·</span>
                        <span style={{fontStyle:'italic',color:'#6b7280'}}>"Return credit applied"</span>
                      </>
                    )}
                    {row.note && (
                      <>
                        <span style={{color:'#cbd5e1'}}>·</span>
                        <span style={{fontStyle:'italic',color:'#6b7280'}}>"{row.note}"</span>
                      </>
                    )}
                  </div>

                  {(row.is_discounted > 0 || row.is_refunded > 0) && (
                    <div style={{display:'flex',gap:'8px'}}>
                      {row.is_discounted > 0 && (
                        <span style={{fontSize:'11px',background:'#fefce8',color:'#ca8a04',border:'1px solid #fde047',borderRadius:'4px',padding:'1px 7px'}}>Discounted</span>
                      )}
                      {row.is_refunded > 0 && (
                        <span style={{fontSize:'11px',background:'#fef2f2',color:'#dc2626',border:'1px solid #fca5a5',borderRadius:'4px',padding:'1px 7px'}}>Refunded</span>
                      )}
                    </div>
                  )}
                </div>

                {/* Delete button */}
                <button
                  onClick={() => removePaymentData(row.id, row.customer_id, row.customer_invoice_id)}
                  style={{height:'36px',padding:'0 14px',borderRadius:'10px',background:'#fff',color:'rgb(234, 88, 12)',border:'1.5px solid #fed7aa',fontWeight:'700',fontSize:'13px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',cursor:'pointer',flexShrink:0,whiteSpace:'nowrap',transition:'all 0.15s'}}
                  onMouseEnter={e=>{e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='rgb(234, 88, 12)';}}
                  onMouseLeave={e=>{e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#fed7aa';}}
                >
                  <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  Delete
                </button>
              </div>
              );
            })}
          </div>
        )}
      </div>
    );
  });

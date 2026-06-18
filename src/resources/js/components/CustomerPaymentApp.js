import React, { useEffect, useState,useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import axios from 'axios';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import { useToast } from "./../hooks/useToast";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import _ from 'lodash';

// ----------------- Slice + Store -----------------
const customersSlice = createSlice({
    name: 'customers',
    initialState: { 
		customers: [],
		currentCustomer:"",
		loading: false,
		refreshPayments: 0 ,
		paymentMode:"2",
		amount:0,
		date:"",
		note:"",
		invoices:[],
		invoicesData:[],
		totalChecked:0,
		paymentDoable : 0,
		pendingInvoiceCount:1,
		accountPayments:[],
		onAccount:"",
		summaryTotals: { totalAmount: 0, totalPaid: 0, totalPending: 0, invoiceCount: 0 },
		saveTrigger: 0,
		creditBalance: 0,
		creditAmount: 0,
		applyCredit: false,
	},
    reducers: {
        setCustomers: (state, action) => { state.customers = action.payload },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
		setCustomersLoading: (state, action) => { state.loading = action.payload; },

		setPaymentMode: (state, action) => { state.paymentMode = action.payload; },
		setAmount: (state, action) => { state.amount = action.payload; },
		setDate: (state, action) => { state.date = action.payload; },
		setNote: (state, action) => { state.note = action.payload; },
		setInvoices: (state, action) => { state.invoices = action.payload; },
		setTotalChecked: (state, action) => { state.totalChecked = action.payload; },
		setPaymentDoable: (state, action) => { state.paymentDoable = action.payload; },
		setAccountPayments: (state, action) => { state.accountPayments = action.payload; },
		setOnAccount: (state, action) => { state.onAccount = action.payload; },
		setSummaryTotals: (state, action) => { state.summaryTotals = action.payload; },
		triggerSave: (state) => { state.saveTrigger = Date.now(); },
		setCreditBalance: (state, action) => { state.creditBalance = action.payload; },
		setCreditAmount: (state, action) => { state.creditAmount = action.payload; },
		setApplyCredit: (state, action) => { state.applyCredit = action.payload; },
		
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now(); // unique timestamp every trigger
        },
		toggleInvoice: (state, action) => {
		  const id = action.payload;
		  if (state.invoices.includes(id)) {
			state.invoices = state.invoices.filter(x => x !== id);
		  } else {
			state.invoices.push(id);
		  }
		  
		},
		resetPaymentForm: (state) => {
			state.amount = 0;
			state.note = "";
			state.date = "";
			state.paymentMode = "2";
			state.paymentDoable = 0;
			state.invoices = [];
			state.creditAmount = 0;
			state.applyCredit = false;
        },
		resetAccountPayments: (state) => {
			state.accountPayments = [];
        },
		resetInvoices: (state) => {
			state.invoices = [];
        },
		resetOnAccount: (state) => {
			state.onAccount = "";
        },
		// ✅ Reset all state to initial values
		resetCustomersState: (state) => {
            state.currentCustomer = "";
            state.loading = false;
            state.refreshPayments = 0;
            state.paymentMode = "2";
            state.amount = 0;
            state.date = "";
            state.note = "";
            state.invoices = [];
			state.invoicesData = [];
			state.totalChecked = 0;
			state.paymentDoable = 0;
			state.accountPayments=[];
			state.onAccount=""
        }
    },
});

const {setCustomers, setCurrentCustomer, setCustomersLoading, triggerPaymentRefresh,
	setPaymentMode, setAmount, setDate, setNote, setInvoices, resetCustomersState, setTotalChecked,
	setPaymentDoable, toggleInvoice, resetAccountPayments, resetPaymentForm, resetInvoices, setAccountPayments, setOnAccount, setSummaryTotals, triggerSave,
	setCreditBalance, setCreditAmount, setApplyCredit} = customersSlice.actions;

const store = configureStore({
    reducer: { customers: customersSlice.reducer},
});

// ----------------- Component -----------------

// customer selection.
function CustomerSelect({ apiUrl, onSubmit }) {
    const dispatch = useDispatch();
    const customers = useSelector(state => state.customers.customers);
    const loading = useSelector(state => state.customers.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetchCustomers = async () => {
            try {
                //dispatch(setLoading(true));
                const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setCustomers(response.data.payload)); // store customers in Redux
				}
            } catch (err) {
                console.error('Failed to load customers', err);
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
		})),
	];
	
	const handleChange = (selected) => {
		dispatch(resetCustomersState());
        dispatch(setCurrentCustomer(selected ? selected.value : null));
    };

    const formik = useFormik({
        initialValues: {
            customer_id: { label: '', value: '' },
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

// form .
function CreateForm({ apiUrl, onSubmit, initialCustomerId, currency }) {
	const dispatch = useDispatch();
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const customers = useSelector(state => state.customers.customers);
	const customersLoading = useSelector(state => state.customers.loading);
	const {paymentMode,amount,date,note,accountPayments,onAccount,creditBalance,creditAmount,applyCredit,summaryTotals: reduxSummary} = useSelector(state => state.customers);

	useEffect(() => {
		if (Number(amount) > 0) {
			formik.setFieldValue('amount', amount);
		}
	}, [amount]);

	useEffect(() => {
		const fetchCustomers = async () => {
			try {
				const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setCustomers(response.data.payload));
					// Set today's date by default
					const today = new Date();
					const todayStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
					dispatch(setDate(todayStr));
					formik.setFieldValue('date', todayStr);

					if(initialCustomerId){
						const cust = response.data.payload.find(c => c.id == initialCustomerId);
						if(cust){
							dispatch(setCurrentCustomer(cust.id));
						}
					} else if(response.data.payload.length > 0){
						dispatch(setCurrentCustomer(response.data.payload[0].id));
					}
				}
			} catch (err) {
				console.error('Failed to load customers', err);
			}
		};
		fetchCustomers();
	}, [apiUrl, dispatch]);

	const customerOptions = customers.map(c => ({
		value: c.id,
		label: c.name,
	}));

	const handleCustomerChange = (selected) => {
		dispatch(resetCustomersState());
		dispatch(setCurrentCustomer(selected ? selected.value : null));
		dispatch(setCreditBalance(0));
		dispatch(setCreditAmount(0));
		dispatch(setApplyCredit(false));
	};

	// Fetch credit balance when customer changes
	useEffect(() => {
		if (!currentCustomer) { dispatch(setCreditBalance(0)); return; }
		axios.get('/customer_return/view/credit-balance/' + currentCustomer)
			.then(r => { if (r.data.success) dispatch(setCreditBalance(r.data.payload.available)); })
			.catch(() => {});
	}, [currentCustomer]);
	const [loading, setLoading] = useState(0);
	
	const notifyError = (error) => toast.error(error, {
		position: "bottom-right",
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
		position: "bottom-right",
		autoClose: 3000,
		hideProgressBar: false,
		closeOnClick: false,
		pauseOnHover: true,
		draggable: true,
		progress: undefined,
		theme: "light",
		//transition: Bounce,
	});
	
	const paymentModes = [
		{ value: '2', label: 'Cash' },
		{ value: '3', label: 'Cheque' },
		{ value: '4', label: 'Card' },
		{ value: '5', label: 'Bank Transfer' },
		{ value: 'other', label: 'Other' },
		{ value: 'on-account', label: 'On Account' },
	];

    const formik = useFormik({
        initialValues: {
            payment_mode: '2',
            amount: '',
			note: '',
            date: '',
        },
        validationSchema: Yup.object({
            payment_mode: Yup.string().required('Payment mode is required'),
            amount: Yup.number()
                .typeError('Amount must be a number')
                .positive('Amount must be positive')
                .required('Amount is required'),
            date: Yup.date().required('Date is required'),
        }),
        onSubmit: async values => {
			values.customer_id = currentCustomer;
			setLoading(1)
			try {
				const response = await axios.post('/management/on_account_payment/create/store', values);
				if (response.data.success === true) {
					dispatch(triggerPaymentRefresh());
					formik.resetForm();
					notifySuccess("Success");
				}else{
					notifySuccess(response.data.payload);
				}
			} catch (err) {
				console.error('Failed to save payment', err);
				notifySuccess(err);
			}finally{
				setLoading(0)
			}
		},
    });
	
	const loadOnAccountpayments = async() => {
		try {
			const response = await axios.get('/payments/customer_payment/create/on-account-payments/'+currentCustomer);
			if (response.data.success === true) {
				dispatch(setAmount(0))
				dispatch(setAccountPayments(response.data.payload))
			}else{
				
			}
		} catch (err) {
			console.error('Failed to save payment', err);
		}finally{

		}
	}
	
	const options = [
		{ value: '', label: '-- Select --' }, // 👈 fake empty option
		...accountPayments.map(c => ({
			value: {id:c.payment_id,amount:c.remaining_amount},
			label: c.created_at_full+' | '+c.remaining_amount,
		})),
	];
	
	const handleChange = (e) => {
		console.log('-----')
		//console.log(e)
		console.log(e.value)
		dispatch(setOnAccount(e))
		if(typeof e.value.amount != "undefined"){
			dispatch(setAmount(Number(e.value.amount)))
			//console.log(e.value.amount)
		}else{
			dispatch(setAmount(0))
		}
	}

    const changePaymentMode = (e) => {
		if(e.target.value == 'on-account'){
			loadOnAccountpayments();
		}else{
			dispatch(setAmount(0))
			dispatch(setOnAccount(""))
		}
		dispatch(setPaymentMode(e.target.value))
	}
	
	const h = '44px';
	const lblStyle = {fontSize:'10.5px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'8px',display:'block'};
	const selectCtrl = {
		...orangeSelectStyles,
		control: (base, state) => ({
			...orangeSelectStyles.control(base, state),
			minHeight:h,height:h,borderRadius:'12px',
			border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
			background: state.isFocused ? '#fff' : '#f8fafc',
			boxShadow: state.isFocused ? '0 0 0 4px rgba(234,88,12,0.08)' : 'none',
			transition: 'all 0.2s ease',
			'&:hover': { borderColor: '#cbd5e1' },
		}),
		valueContainer: (base) => ({...base,height:h,padding:'0 14px'}),
		indicatorsContainer: (base) => ({...base,height:h}),
		placeholder: (base) => ({...base,fontSize:'13px',color:'#94a3b8',fontWeight:'500'}),
		singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
		menu: (base) => ({...base,borderRadius:'12px',border:'1px solid #e8ecf2',boxShadow:'0 12px 36px rgba(0,0,0,0.1)',overflow:'hidden',marginTop:'4px'}),
		option: (base, state) => ({
			...base,
			backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : state.isFocused ? 'rgb(234, 88, 12)' : '#334155',
			fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',
			transition:'all 0.1s',
		}),
	};
	const errSelectCtrl = {
		...selectCtrl,
		control: (base, state) => ({
			...selectCtrl.control(base, state),
			border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #ef4444',
		}),
	};
	const inputStyle = {
		height:h,borderRadius:'12px',border:'1.5px solid #e2e8f0',fontSize:'13px',
		background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',
		transition:'all 0.2s ease',fontWeight:'500',color:'#1e293b',
	};
	const inputFocusStyle = {borderColor:'rgb(234, 88, 12)',background:'#fff',boxShadow:'0 0 0 4px rgba(234,88,12,0.08)'};
	const errStyle = {color:'#ef4444',fontSize:'11px',marginTop:'5px',fontWeight:'500'};
	const dateBox = {
		display:'inline-flex',alignItems:'center',background:'#f8fafc',
		border:'1.5px solid #e2e8f0',borderRadius:'12px',overflow:'hidden',height:h,
		transition:'all 0.2s ease',
	};

	const cardStyle = {
		borderRadius:'20px',border:'1px solid #e8ecf2',
		boxShadow:'0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04)',
		background:'#fff',padding:'28px 28px 24px',
	};

	const paymentModeButtons = [
		{ value: '2', label: 'Cash', icon: 'fa fa-money', color: '#16a34a', bg: '#f0fdf4', border: '#86efac' },
		{ value: '3', label: 'Cheque', icon: 'fa fa-file-text-o', color: '#7c3aed', bg: '#f5f3ff', border: '#c4b5fd' },
		{ value: '4', label: 'Card', icon: 'fa fa-credit-card', color: '#2563eb', bg: '#eff6ff', border: '#93c5fd' },
		{ value: '5', label: 'Bank', icon: 'fa fa-university', color: '#0891b2', bg: '#ecfeff', border: '#67e8f9' },
		{ value: 'on-account', label: 'On Account', icon: 'fa fa-bookmark-o', color: '#d97706', bg: '#fffbeb', border: '#fde68a' },
	];

	const { summaryTotals } = useSelector(state => state.customers);
	const cur = currency || '£';
	const fmtS = v => cur + ' ' + Number(v || 0).toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2});
	const isMobile = window.innerWidth <= 767;
	const isTablet = false; // iPad/tablets (768-1024) now render the desktop UI
	const [summaryOpen, setSummaryOpen] = useState(false);
	const collRate = Number(summaryTotals.totalAmount) > 0 ? Math.round((Number(summaryTotals.totalPaid) / Number(summaryTotals.totalAmount)) * 100) : 0;

	// credit helpers
	const effectiveAmount = Number(amount) + Number(creditAmount);
	const canSave = paymentMode && date && (Number(amount) > 0 || (applyCredit && Number(creditAmount) > 0));

	const handleApplyCreditToggle = () => {
		const next = !applyCredit;
		dispatch(setApplyCredit(next));
		if (next) {
			const pending = summaryTotals.totalPending || 0;
			const auto = Math.min(creditBalance, pending);
			dispatch(setCreditAmount(parseFloat(auto.toFixed(2))));
		} else {
			dispatch(setCreditAmount(0));
		}
	};

	return (<>
		{/* Payment summary — collapsible bar on mobile (matches Stock Management / History), card grid on desktop */}
		{summaryTotals.invoiceCount > 0 && (isMobile ? (
				<div style={{marginBottom:'14px',borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'14px'}}>
					<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',gap:'10px'}}>
						<div style={{display:'flex',alignItems:'center',gap:'8px',minWidth:0}}>
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
							<span style={{fontSize:'11px',fontWeight:'800',color:'#6b7280',letterSpacing:'0.8px',textTransform:'uppercase',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>Financial Summary</span>
						</div>
						<button type="button" onClick={()=>setSummaryOpen(o=>!o)} style={{display:'flex',alignItems:'center',gap:'8px',background:'none',border:'none',padding:0,cursor:'pointer',outline:'none',flexShrink:0}}>
							<span style={{fontSize:'19px',fontWeight:'800',color:'#0f172a',letterSpacing:'-0.5px'}}>{fmtS(summaryTotals.totalAmount)}</span>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{transform: summaryOpen?'none':'rotate(-90deg)',transition:'transform 0.15s',flexShrink:0}}><path d="M6 9l6 6 6-6"/></svg>
						</button>
					</div>
					{summaryOpen && (
					<>
					<div style={{display:'flex',border:'1px solid #eef0f3',borderRadius:'12px',overflow:'hidden',marginTop:'12px'}}>
						{[{label:'Paid',value:fmtS(summaryTotals.totalPaid),color:'#16a34a'},{label:'Pending',value:fmtS(summaryTotals.totalPending),color:'#ea580c'},{label:'Invoices',value:String(summaryTotals.invoiceCount),color:'#0f172a'}].map((s,i)=>(
							<div key={s.label} style={{flex:1,minWidth:0,padding:'12px 8px',textAlign:'center',borderLeft: i===0?'none':'1px solid #eef0f3'}}>
								<div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'6px'}}>{s.label}</div>
								<div style={{fontSize:'16px',fontWeight:'800',color:s.color,whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{s.value}</div>
							</div>
						))}
					</div>
					<div style={{marginTop:'14px'}}>
						<div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'7px'}}>
							<span style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase'}}>Collection Rate</span>
							<span style={{fontSize:'11px',fontWeight:'700',color:'#374151'}}>{collRate}%</span>
						</div>
						<div style={{height:'6px',borderRadius:'99px',background:'#eef0f3',overflow:'hidden'}}>
							<div style={{height:'100%',width: collRate + '%',borderRadius:'99px',background:'#F27420',minWidth: collRate>0?'8px':'0'}}></div>
						</div>
					</div>
					</>
					)}
				</div>
		) : (
			<div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'10px',background:'#fff',borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 3px rgba(0,0,0,0.04)',padding:'14px 18px',marginBottom:'16px'}}>
				{[
					{label:'Open Invoices', value: String(summaryTotals.invoiceCount), icon:'fa-file-text-o', color:'#3b82f6', light:'#eff6ff'},
					{label:'Already Paid', value: fmtS(summaryTotals.totalPaid), icon:'fa-check-circle', color:'#16a34a', light:'#f0fdf4'},
					{label:'Total Pending', value: fmtS(summaryTotals.totalPending), icon:'fa-clock-o', color:'rgb(234, 88, 12)', light:'#fff7ed'},
				].map(c => (
					<div key={c.label} style={{display:'flex',alignItems:'center',gap:'13px',padding:'16px 18px',borderRadius:'12px',background:'#fff',border:'1px solid #edf2f7',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
						<div style={{width:'42px',height:'42px',borderRadius:'50%',background:c.light,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
							<i className={'fa '+c.icon} style={{color:c.color,fontSize:'16px'}}/>
						</div>
						<div>
							<div style={{fontSize:'22px',fontWeight:'800',color:'#1a2332',lineHeight:1,fontVariantNumeric:'tabular-nums'}}>{c.value}</div>
							<div style={{fontSize:'10px',fontWeight:'600',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',marginTop:'4px'}}>{c.label}</div>
						</div>
					</div>
				))}
			</div>
		))}

		{/* Form Card */}
        <div style={cardStyle}>
			<form onSubmit={formik.handleSubmit}>
				{/* Customer */}
				<div style={{marginBottom:'18px'}}>
					<label style={isMobile ? {fontSize:'10px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'10px',display:'block'} : lblStyle}>Customer</label>
					{initialCustomerId ? (
						isMobile ? (
							<div style={{display:'flex',alignItems:'center',gap:'12px',background:'#F3F4F6',border:'none',borderRadius:'14px',padding:'0 14px',height:'52px',cursor:'not-allowed'}}>
								<i className="fa fa-user-o" style={{fontSize:'20px',color:'#9CA3AF',flexShrink:0}}></i>
								<span style={{fontWeight:'600',fontSize:'15px',color:'#1e293b',flex:1}}>{(customerOptions.find(o => o.value === currentCustomer) || {}).label || 'Loading...'}</span>
								<i className="fa fa-lock" style={{fontSize:'15px',color:'#9CA3AF'}}></i>
							</div>
						) : (
							<div style={{display:'flex',alignItems:'center',gap:'10px',background:'#f1f5f9',border:'1.5px solid #e2e8f0',borderRadius:'10px',padding:'0 16px',height:h,cursor:'not-allowed',opacity:0.85,maxWidth:'350px'}}>
								<i className="fa fa-user" style={{fontSize:'12px',color:'#94a3b8'}}></i>
								<span style={{fontWeight:'700',fontSize:'14px',color:'#1e293b'}}>{(customerOptions.find(o => o.value === currentCustomer) || {}).label || 'Loading...'}</span>
								<i className="fa fa-lock" style={{fontSize:'11px',color:'#cbd5e1',marginLeft:'auto'}}></i>
							</div>
						)
					) : (
						<div style={{maxWidth: isMobile ? '100%' : '350px'}}>
							<Select styles={selectCtrl} options={customerOptions} value={customerOptions.find(o => o.value === currentCustomer) || null}
								isLoading={customersLoading} isClearable isSearchable onChange={handleCustomerChange} classNamePrefix="react-select" placeholder="Select Customer" />
						</div>
					)}
				</div>

				{/* Return Credit Banner */}
			{currentCustomer && creditBalance > 0 && paymentMode !== 'on-account' && (
				<div style={{marginBottom:'18px',background:'linear-gradient(135deg,#fff7ed,#ffedd5)',border:'1.5px solid #fdba74',borderRadius:'14px',padding:'14px 18px',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'14px',flexWrap:'wrap'}}>
					<div style={{display:'flex',alignItems:'center',gap:'12px'}}>
						<div style={{width:'38px',height:'38px',borderRadius:'10px',background:'linear-gradient(135deg,#f97316,#ea580c)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 8px rgba(249,115,22,0.3)'}}>
							<i className="fa fa-gift" style={{color:'#fff',fontSize:'14px'}}></i>
						</div>
						<div>
							<div style={{fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.6px'}}>Return Credit Available</div>
							<div style={{fontSize:'18px',fontWeight:'800',color:'#ea580c',lineHeight:'1.2'}}>{cur} {Number(creditBalance).toFixed(2)}</div>
						</div>
					</div>
					<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap'}}>
						{applyCredit && (
							<div style={{display:'flex',alignItems:'center',gap:'6px'}}>
								<span style={{fontSize:'11px',fontWeight:'600',color:'#9a3412'}}>Apply:</span>
								<input type="number" min="0.01" max={creditBalance} step="0.01" value={creditAmount}
									onChange={e => { const v = Math.min(Number(e.target.value), creditBalance); dispatch(setCreditAmount(v)); }}
									style={{width:'90px',height:'32px',borderRadius:'8px',border:'1.5px solid #fdba74',fontSize:'13px',fontWeight:'700',color:'#ea580c',padding:'0 8px',outline:'none',background:'#fff',textAlign:'right'}}
								/>
							</div>
						)}
						<button type="button" onClick={handleApplyCreditToggle} style={{height:'34px',padding:'0 16px',borderRadius:'8px',background:applyCredit?'#ea580c':'#fff',color:applyCredit?'#fff':'#ea580c',border:'1.5px solid #ea580c',fontSize:'12px',fontWeight:'700',cursor:'pointer',whiteSpace:'nowrap',display:'flex',alignItems:'center',gap:'6px',transition:'all 0.15s'}}>
							<i className={`fa ${applyCredit ? 'fa-check-circle' : 'fa-plus-circle'}`}></i>
							{applyCredit ? 'Credit Applied' : 'Apply Credit'}
						</button>
					</div>
				</div>
			)}

			{/* Payment Method */}
				<div style={{marginBottom:'18px'}}>
					<label style={lblStyle}>Payment Method</label>
					<div style={isMobile ? {display:'flex',flexWrap:'wrap',gap:'8px'} : {display:'flex',gap:'6px',flexWrap:'wrap'}}>
						{paymentModeButtons.map(m => {
							const isActive = paymentMode === m.value;
							const mobStyle = {display:'flex',alignItems:'center',gap:'7px',padding:'9px 13px',borderRadius:'10px',cursor:'pointer',fontFamily:'inherit',fontSize:'13px',border: isActive ? '1.5px solid #FF6B1A' : '1.5px solid #ECECEC',background: isActive ? '#FFF1E6' : '#fff',color: isActive ? '#E55A0E' : '#3D3D3D',fontWeight: isActive ? '700' : '600'};
							const deskStyle = {display:'flex',alignItems:'center',gap:'8px',cursor:'pointer',fontSize:'13px',fontWeight: isActive?'700':'500',color: isActive?'rgb(234, 88, 12)':'#64748b',padding:'8px 14px',borderRadius:'8px',background: isActive?'#FFF7ED':'transparent',border: isActive?'1.5px solid rgb(234, 88, 12)':'1.5px solid transparent',transition:'all 0.15s'};
							return (
								<label key={m.value} onClick={() => {
									formik.setFieldValue('payment_mode', m.value);
									if(m.value === 'on-account'){ loadOnAccountpayments(); } else { dispatch(setAmount(0)); dispatch(setOnAccount("")); }
									dispatch(setPaymentMode(m.value));
								}} style={isMobile ? mobStyle : deskStyle}>
									{isMobile ? (
										<span style={{width:'16px',height:'16px',borderRadius:'50%',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,border: isActive ? '2px solid #FF6B1A' : '2px solid #BCBCBC'}}>
											{isActive && <span style={{width:'7px',height:'7px',borderRadius:'50%',background:'#FF6B1A'}}></span>}
										</span>
									) : (
										<span style={{width:'16px',height:'16px',borderRadius:'50%',border: isActive ? '5px solid rgb(234, 88, 12)' : '2px solid #cbd5e1',background:'#fff',transition:'all 0.15s',flexShrink:0}}></span>
									)}
									{m.label}
								</label>
							);
						})}
					</div>
					{formik.touched.payment_mode && formik.errors.payment_mode && <div style={errStyle}>{formik.errors.payment_mode}</div>}
				</div>

				{/* Amount + Date + Note + Save */}
				{isMobile ? (
					<div style={{display:'flex',flexDirection:'column',gap:'14px'}}>
						<style>{`.odp-mobile-wrap .react-datepicker-wrapper,.odp-mobile-wrap .react-datepicker__input-container{display:block;width:100%}.odp-mobile-input{width:100%;height:52px!important;border-radius:12px!important;border:1px solid #E5E7EB!important;font-size:14px!important;font-weight:600!important;color:#1e293b!important;background:#fff!important;padding:0 14px 0 42px!important;outline:none!important;box-sizing:border-box!important;}`}</style>
						{/* Row 1: Amount + Date side by side */}
						<div style={{display:'flex',gap:'10px',flexWrap:'wrap'}}>
							{paymentMode === 'on-account'
								? <div style={{flex:1}}>
									<label style={{...lblStyle,fontSize:'10px',letterSpacing:'0.7px'}}>On Account<span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
									<Select styles={selectCtrl} options={options} isLoading={loading} isClearable isSearchable onChange={handleChange} classNamePrefix="react-select" />
								</div>
								: <div style={{flex:1}}>
									<label style={{...lblStyle,fontSize:'10px',letterSpacing:'0.7px'}}>Amount<span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
									<div style={{position:'relative'}}>
										<span style={{position:'absolute',left:'14px',top:'50%',transform:'translateY(-50%)',fontSize:'18px',fontWeight:'600',color:'#9CA3AF',pointerEvents:'none',zIndex:1}}>{cur}</span>
										<input type="number" min="0" max={summaryTotals.totalPending || undefined} step="0.01" name="amount" placeholder="0.00" value={amount || ''}
											onChange={(e) => { const raw = e.target.value; if (raw === '') { formik.setFieldValue('amount', 0); dispatch(setAmount(0)); return; } let val = Number(raw); if (summaryTotals.totalPending > 0 && val > summaryTotals.totalPending) val = summaryTotals.totalPending; if (val < 0) val = 0; formik.setFieldValue('amount', val); dispatch(setAmount(val)); }}
											onBlur={(e) => {formik.handleBlur(e);e.target.style.borderColor=formik.touched.amount&&formik.errors.amount?'#ef4444':'#E5E7EB';e.target.style.boxShadow='none'}}
											style={{height:'52px',borderRadius:'12px',border: formik.touched.amount && formik.errors.amount ? '1px solid #ef4444' : '1px solid #E5E7EB',fontSize:'22px',fontWeight:'800',color:'#1e293b',background:'#fff',padding:'0 14px 0 38px',outline:'none',width:'100%',boxSizing:'border-box',letterSpacing:'-0.5px'}}
											onFocus={(e) => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.boxShadow='0 0 0 3px rgba(234,88,12,0.08)'}}
										/>
									</div>
									{formik.touched.amount && formik.errors.amount && <div style={errStyle}>{formik.errors.amount}</div>}
								</div>
							}
							<div style={{flex:1}}>
								<label style={{...lblStyle,fontSize:'10px',letterSpacing:'0.7px'}}>Date<span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
								<OrangeDatePicker value={date} onChange={(val) => { formik.setFieldValue('date', val); dispatch(setDate(val)); }} standalone style={{width:'100%',height:'52px',borderRadius:'12px',border:'1px solid #E5E7EB',padding:'0 14px',gap:'10px'}} />
								{formik.touched.date && formik.errors.date && <div style={errStyle}>{formik.errors.date}</div>}
							</div>
						</div>
						{/* Row 2: Note */}
						<div>
							<label style={{...lblStyle,fontSize:'10px',letterSpacing:'0.7px'}}>Note</label>
							<input type="text" name="note" placeholder="Optional" value={note}
								onChange={(e) => {formik.handleChange(e); dispatch(setNote(e.target.value))}}
								onBlur={formik.handleBlur} style={{...inputStyle,height:'48px',background:'#fff',border:'1px solid #E5E7EB',fontSize:'14px'}}
								onFocus={(e) => {Object.assign(e.target.style,inputFocusStyle)}}
							/>
						</div>
						{/* Row 3: Save Button full width */}
						<button type="button" disabled={!canSave}
							onClick={() => dispatch(triggerSave())}
							style={{
								width:'100%',height:'52px',borderRadius:'50px',border:'none',
								background:'rgb(234, 88, 12)',
								color:'#fff',
								fontSize:'16px',fontWeight:'700',
								cursor: canSave ? 'pointer' : 'not-allowed',
								transition:'all 0.15s',
								boxShadow:'0 4px 16px rgba(234,88,12,0.35)',
								opacity: canSave ? 1 : 0.6,
								display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',
								letterSpacing:'0.3px',
							}}
						>
							<i className="fa fa-check-circle-o" style={{fontSize:'18px'}}></i>
							Save Payment
						</button>
					</div>
				) : (
				<div style={{display:'flex',gap:'12px',flexWrap:'wrap',alignItems:'flex-end'}}>
					{paymentMode === 'on-account'
						? <div style={{flex:1,minWidth:'180px'}}>
							<label style={lblStyle}>On Account</label>
							<Select styles={selectCtrl} options={options} isLoading={loading} isClearable isSearchable onChange={handleChange} classNamePrefix="react-select" />
						</div>
						: <div style={{flex:'0 0 160px'}}>
							<label style={lblStyle}>Amount<span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
							<input type="number" min="0" max={summaryTotals.totalPending || undefined} step="0.01" name="amount" placeholder="0.00" value={amount || ''}
								onChange={(e) => { const raw = e.target.value; if (raw === '') { formik.setFieldValue('amount', 0); dispatch(setAmount(0)); return; } let val = Number(raw); if (summaryTotals.totalPending > 0 && val > summaryTotals.totalPending) val = summaryTotals.totalPending; if (val < 0) val = 0; formik.setFieldValue('amount', val); dispatch(setAmount(val)); }}
								onBlur={formik.handleBlur}
								style={{...inputStyle, border: formik.touched.amount && formik.errors.amount ? '1.5px solid #ef4444' : '1.5px solid #e2e8f0'}}
								onFocus={(e) => {Object.assign(e.target.style,inputFocusStyle)}}
							/>
							{formik.touched.amount && formik.errors.amount && <div style={errStyle}>{formik.errors.amount}</div>}
						</div>
					}
					<div style={{flex:'0 0 160px'}}>
						<label style={lblStyle}>Date<span style={{color:'rgb(234, 88, 12)',marginLeft:'2px'}}>*</span></label>
						<div className="odp-tablet-wrap">
							<OrangeDatePicker value={date} onChange={(val) => { formik.setFieldValue('date', val); dispatch(setDate(val)); }} className="orange-datepicker-input odp-tablet-inp" />
						</div>
						{formik.touched.date && formik.errors.date && <div style={errStyle}>{formik.errors.date}</div>}
					</div>
					<div style={{flex:1,minWidth:'120px'}}>
						<label style={lblStyle}>Note</label>
						<input type="text" name="note" placeholder="Optional" value={note}
							onChange={(e) => {formik.handleChange(e); dispatch(setNote(e.target.value))}}
							onBlur={formik.handleBlur} style={inputStyle}
							onFocus={(e) => {Object.assign(e.target.style,inputFocusStyle)}}
						/>
					</div>
					{/* Save Button inline */}
					<button type="button" disabled={!canSave}
						onClick={() => dispatch(triggerSave())}
						style={{
							height:h,padding:'0 28px',borderRadius:'12px',border:'none',
							background: canSave ? 'rgb(234, 88, 12)' : '#e5e7eb',
							color: canSave ? '#fff' : '#9ca3af',
							fontSize:'14px',fontWeight:'700',
							cursor: canSave ? 'pointer' : 'not-allowed',
							transition:'all 0.15s',
							boxShadow: canSave ? '0 3px 12px rgba(234,88,12,0.3)' : 'none',
							display:'flex',alignItems:'center',gap:'8px',whiteSpace:'nowrap',flexShrink:0,
						}}
					>
						<i className="fa fa-check-circle"></i>
						Save Payment
					</button>
				</div>
				)}
			</form>
        <ToastContainer autoClose={3000} />
        </div>
    </>);

}

// Save Payment Button (used in List)
// list.
function List(props) {
	const {customers, totalChecked, invoices,amount,paymentMode,date,note,paymentDoable,onAccount,saveTrigger,creditAmount,applyCredit} = useSelector(state => state.customers);
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const refreshPayments = useSelector(state => state.customers.refreshPayments);
	const [data, setData] = useState([]);
	const [saving, setSaving] = useState(0);
	const [balance, setBalance] = useState(0);
	const [invoicesAmount, setInvoicesAmount] = useState(0);
	const [selectedRows, setSelectedRows] = useState([]);
	const [mobilePage, setMobilePage] = useState(1);
	const [invView, setInvView] = useState('card');
	const [mobilePerPage, setMobilePerPage] = useState(15);

	const { notifySuccess, notifyError } = useToast();

	const dispatch = useDispatch();
	
    const columns = [
		{
		  name: "",
		  cell: (row) => (
			<div style={{display:'flex',alignItems:'center',justifyContent:'center'}}>
				<input
				  type="checkbox"
				  checked={invoices.includes(row.id)}
				  onChange={() => handleCheckboxChange(row.id)}
				  style={{
					  width:'18px',height:'18px',cursor:'pointer',accentColor:'rgb(234, 88, 12)',
					  borderRadius:'6px',border:'1.5px solid #e2e8f0',
				  }}
				/>
			</div>
		  ),
		  width: "50px",
		  ignoreRowClick: true,
		  allowOverflow: true,
		  button: true,
		},
        { name: 'Invoice', selector: row => row.id, sortable: true, cell: row => <span style={{fontWeight:'700',color:'#1e293b'}}>#{row.id}</span> },
        { name: 'Amount', selector: row => row.total_products, sortable: false, cell: row => <span style={{fontVariantNumeric:'tabular-nums',fontWeight:'500'}}>{Number(row.total_products).toFixed(2)}</span> },
        { name: 'Paid', selector: row => row.total_payments, cell: row => <span style={{fontVariantNumeric:'tabular-nums',color:'#16A34A',fontWeight:'600'}}>{Number(row.total_payments).toFixed(2)}</span> },
		{ name: 'Balance', selector: row => row.balance_due, cell: row => <span style={{fontVariantNumeric:'tabular-nums',color: Number(row.balance_due) > 0 ? '#DC2626' : '#16A34A',fontWeight:'700'}}>{Number(row.balance_due).toFixed(2)}</span> },
		{ name: 'Running Bal.', selector: row => row.running_balance, cell: row => <span style={{fontVariantNumeric:'tabular-nums',fontWeight:'700',color: Number(row.running_balance) > 0 ? '#DC2626' : '#16A34A'}}>{row.running_balance}</span> },
        { name: 'Date', selector: row => row.created_at, sortable: true, cell: row => <span style={{color:'#64748b',fontSize:'12.5px',fontWeight:'500'}}>{row.created_at}</span> },
    ];
	
	const dataWithRunningBalance = useMemo(() => {
		let running = 0;
		return data.map(row => {
		  running += Number(row.balance_due); // add current row's balance_due
		  return { ...row, running_balance: running.toFixed(2) };
		});
	  }, [data]);
	
	// Handle single checkbox toggle
	const handleCheckboxChange = (id) => {
		dispatch(toggleInvoice(id));
	};
	
	const setCalc = () => {
		let total = [];
		data.forEach(d => {
			if(invoices.includes(d.id)){
				total.push(Number(d.balance_due))
			}
		  
		});
		
		
		// order covering.
		let total_invoices = invoices.length;
		let covered_invoices = 0;
		let calc = Number(amount);
		let negative_counts = [];
		
		data.forEach((d,i) => {
			if(invoices.includes(d.id)){
				calc -= d.balance_due
				//console.log('--calc--')
				//console.log(calc)
				if(calc < 0){
				  negative_counts.push(d.id)
				}
			}
		});
		
		dispatch(setTotalChecked(_.sum(total)))
		
		console.log('--Total--')
		console.log(_.sum(total))
		console.log('--Amount--')
		console.log(amount)
		console.log('--negatives--')
		console.log(negative_counts)
		
		if(amount - _.sum(total) > 0){
			dispatch(setPaymentDoable(0))
		
		}else if(negative_counts.length <= 1){
			//console.log('yes')
			dispatch(setPaymentDoable(1))
		
		}else{
			dispatch(setPaymentDoable(0))
		}
	}
	

	// Handle "Pay Selected" or similar bulk action
	const handleBulkAction = () => {
		alert(`Selected invoice IDs: ${selectedRows.join(", ")}`);
	};
	
	// (customStyles removed — using custom table)
	
	const loadList = async() => {
		try {
			const response = await axios.post('/payments/customer_payment/create/unpaid-invoices/'+currentCustomer);
			if (response.data.success === true) {
				setData(response.data.payload.data)
			}else{
			
			}
		} catch (err) {
			console.error('Failed to save payment', err);
		}finally{

		}
	}
	
	const save = async() => {
		// Frontend validation: amount must not exceed selected invoices total
		if (invoices.length > 0) {
			const selectedTotal = data
				.filter(d => invoices.includes(d.id))
				.reduce((sum, d) => sum + Number(d.balance_due || 0), 0);
			const effective = Number(amount) + (applyCredit ? Number(creditAmount) : 0);
			if (effective > selectedTotal) {
				notifyError('Amount (' + effective.toFixed(2) + ') exceeds the selected invoices total (' + selectedTotal.toFixed(2) + ')');
				return;
			}
		}
		try {
			let params = {
				date : date,
				note : note,
				invoices : invoices,
				paymentMode : paymentMode,
				amount : amount,
				customer_id : currentCustomer,
				onAccount : onAccount,
				creditAmount : applyCredit ? Number(creditAmount) : 0,
			}
			setSaving(1)
			const response = await axios.post('/payments/customer_payment/create/pay/'+currentCustomer, params);
			if (response.data.success === true) {
				notifySuccess("Saved successfully!")
				//setData(response.data.payload.data)
				// reset invoices values.
				dispatch(resetPaymentForm())
				loadList();
				// load list again.
			}else{
				const errorMessages = {
					'messages.amount_is_not_covering_invoices': 'Amount exceeds the selected invoices total',
				};
				if (response.data.form_errors) {
					const allErrors = Object.values(response.data.form_errors).flat();
					const msg = allErrors.map(e => errorMessages[e] || e).join(', ');
					notifyError(msg);
				} else {
					notifyError(response.data.payload || "Something went wrong!");
				}
			}
		} catch (err) {
			//console.error('Failed to save payment', err);
			notifyError(err)
		}finally{
			setSaving(0)
		}
	}
	
	const savePayment = () => {
		save()
	}
	
	useEffect(() => {
		setCalc();
	}, [invoices, amount]);
	
	useEffect(() => {
		/*console.log('-----')
		console.log(currentCustomer)*/
		if(currentCustomer != ""){
			loadList();
		}
    },[currentCustomer, refreshPayments])

	// Listen for save trigger from form
	useEffect(() => {
		const hasPayment = Number(amount) > 0 || (applyCredit && Number(creditAmount) > 0);
		if (saveTrigger > 0 && currentCustomer && hasPayment && paymentMode && date) {
			savePayment();
		}
	}, [saveTrigger]);

	const thStyle = {
		padding:'13px 18px',fontSize:'10.5px',fontWeight:'700',color:'#64748b',
		textTransform:'uppercase',letterSpacing:'0.7px',whiteSpace:'nowrap',
		borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',
	};
	const tdStyle = {
		padding:'14px 18px',borderBottom:'1px solid #f3f4f8',
		fontSize:'13.5px',fontWeight:'500',color:'#334155',
		fontVariantNumeric:'tabular-nums',
	};

	const cur2 = props.currency || '£';
	const fmt = v => cur2 + ' ' + Number(v || 0).toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2});
	const totalPending = data.reduce((a, b) => a + Number(b.balance_due || 0), 0);
	const totalAmount = data.reduce((a, b) => a + Number(b.total_products || 0), 0);
	const totalPaid = data.reduce((a, b) => a + Number(b.total_payments || 0), 0);

	useEffect(() => {
		dispatch(setSummaryTotals({ totalAmount, totalPaid, totalPending, invoiceCount: data.length }));
	}, [data]);

	// Auto-fill amount with pending total and select all invoices
	useEffect(() => {
		if (data.length > 0) {
			dispatch(setAmount(totalPending));
			dispatch(setInvoices(data.map(r => r.id)));
			dispatch(setPaymentDoable(1));
		}
	}, [data]);

    return (
	<>{currentCustomer != ""
		?
		<>


		{/* Unpaid Invoices List */}
		{(() => {
			const isMobile = window.innerWidth <= 767;
			const thS = {fontSize:'10px',fontWeight:'600',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.7px'};
			if (isMobile) {
				const mpp = mobilePerPage;
				const totalPages = Math.max(1, Math.ceil(data.length / mpp));
				const safePage = Math.min(Math.max(1, mobilePage), totalPages);
				const pageData = data.slice((safePage-1)*mpp, safePage*mpp);
				const from = data.length === 0 ? 0 : (safePage-1)*mpp + 1;
				const to = Math.min(safePage*mpp, data.length);
				const chip = {display:'inline-flex',alignItems:'center',gap:'4px',fontSize:'11px',fontWeight:'600',color:'#475569',background:'#f8fafc',border:'1px solid #eaecf2',borderRadius:'7px',padding:'4px 9px',whiteSpace:'nowrap'};
				const navBtn = (dis) => ({width:'30px',height:'30px',borderRadius:'8px',border:'1px solid #e8e8ec',background: dis?'#f9fafb':'#fff',color: dis?'#d1d5db':'#374151',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor: dis?'not-allowed':'pointer',outline:'none'});
				const pager = (
					<>
						<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600'}}>Rows:</span>
						<select value={mpp} onChange={e=>{setMobilePerPage(Number(e.target.value));setMobilePage(1);}} style={{height:'32px',padding:'0 10px',borderRadius:'8px',border:'1px solid #e8e8ec',background:'#fff',fontSize:'12px',color:'#0f1115',fontFamily:'inherit',cursor:'pointer'}}>
							{[15,30,50,100,200].map(n=><option key={n} value={n}>{n}</option>)}
						</select>
						<span style={{fontSize:'11.5px',color:'#6b7280',fontWeight:'600',marginLeft:'auto'}}>{from}&ndash;{to} of {data.length}</span>
						<div style={{display:'flex',gap:'4px'}}>
							<button type="button" disabled={safePage<=1} onClick={()=>setMobilePage(1)} style={navBtn(safePage<=1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg></button>
							<button type="button" disabled={safePage<=1} onClick={()=>setMobilePage(p=>Math.max(1,p-1))} style={navBtn(safePage<=1)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
							<button type="button" disabled={safePage>=totalPages} onClick={()=>setMobilePage(p=>Math.min(totalPages,p+1))} style={navBtn(safePage>=totalPages)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
							<button type="button" disabled={safePage>=totalPages} onClick={()=>setMobilePage(totalPages)} style={navBtn(safePage>=totalPages)}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg></button>
						</div>
					</>
				);
				return (
				<div>
					{/* Unpaid-invoices badge + view toggle — grouped on the right; wraps on narrow phones */}
					<div style={{display:'flex',alignItems:'center',justifyContent:'flex-end',marginBottom:'12px',gap:'10px',flexWrap:'wrap'}}>
						<span style={{display:'inline-flex',alignItems:'center',gap:'7px',fontSize:'13.5px',fontWeight:'700',color:'rgb(234,88,12)',flexShrink:0,whiteSpace:'nowrap'}}>
							<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="rgb(234,88,12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
							{data.length} unpaid {data.length===1?'invoice':'invoices'}
						</span>
						<div style={{display:'flex',gap:'8px',flexShrink:0}}>
							{[{k:'card',l:'Card View',i:'fa-th-large'},{k:'list',l:'Table View',i:'fa-table'}].map(v=>{const on=invView===v.k;return (
								<button key={v.k} type="button" onClick={()=>setInvView(v.k)} style={{display:'inline-flex',alignItems:'center',gap:'7px',padding:'8px 13px',borderRadius:'10px',border:on?'1px solid rgb(234,88,12)':'1px solid #eaecf2',cursor:'pointer',fontSize:'12.5px',fontWeight:'700',background:on?'rgb(234,88,12)':'#fff',color:on?'#fff':'#374151',boxShadow:on?'0 2px 6px rgba(234,88,12,0.25)':'0 1px 2px rgba(16,24,40,0.04)',whiteSpace:'nowrap'}}>
									<i className={'fa '+v.i} style={{fontSize:'12px'}}></i>{v.l}
								</button>
							);})}
						</div>
					</div>
					{data.length === 0 ? (
						<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'40px 24px',textAlign:'center'}}>
							<div style={{width:'60px',height:'60px',margin:'0 auto 14px',borderRadius:'14px',background:'#f0fdf4',border:'1px solid #bbf7d0',display:'flex',alignItems:'center',justifyContent:'center',color:'#22c55e'}}>
								<i className="fa fa-check-circle" style={{fontSize:'26px'}}></i>
							</div>
							<div style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>No unpaid invoices</div>
							<div style={{fontSize:'13px',color:'#6b7280',marginTop:'6px'}}>Everything here is settled up.</div>
						</div>
					) : invView === 'card' ? (
						<div style={{display:'flex',flexDirection:'column',gap:'12px'}}>
							{pageData.map((row)=>(
								<div key={row.id} style={{position:'relative',background:'#fff',border:'1px solid #eaecf2',borderRadius:'14px',boxShadow:'0 1px 4px rgba(0,0,0,0.05)',padding:'14px 16px 13px',overflow:'hidden'}}>
									<div style={{position:'absolute',left:0,top:0,bottom:0,width:'4px',background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}></div>
									<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'10px'}}>
										<div style={{minWidth:0}}>
											<div style={{fontSize:'15px',fontWeight:'800',color:'#0f172a',lineHeight:'1.25'}}>#{row.id}</div>
											<div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px'}}>{row.created_at || '—'}</div>
										</div>
										<span style={{flexShrink:0,display:'inline-flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',padding:'3px 9px',borderRadius:'999px',background:'#fff7ed',color:'rgb(234,88,12)'}}>
											<span style={{width:'6px',height:'6px',borderRadius:'50%',background:'#f97316'}}></span>Pending
										</span>
									</div>
									<div style={{display:'flex',flexWrap:'wrap',gap:'7px',marginTop:'12px'}}>
										<span style={chip}>Amount <b style={{color:'#0f172a',marginLeft:'3px'}}>{fmt(row.total_products)}</b></span>
										<span style={chip}>Paid <b style={{color:Number(row.total_payments)>0?'#16a34a':'#64748b',marginLeft:'3px'}}>{fmt(row.total_payments)}</b></span>
									</div>
									<div style={{marginTop:'12px'}}>
										<div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase'}}>Due</div>
										<div style={{fontSize:'22px',fontWeight:'800',color:'#dc2626',lineHeight:'1.1',marginTop:'2px'}}>{fmt(row.balance_due)}</div>
									</div>
								</div>
							))}
							{totalPages > 1 && <div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'16px 2px 4px'}}>{pager}</div>}
						</div>
					) : (
						<div style={{borderRadius:'14px',border:'1px solid #eaecf2',boxShadow:'0 1px 6px rgba(0,0,0,0.05)',background:'#fff',overflow:'hidden'}}>
							<div style={{overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
								<table style={{width:'100%',minWidth:'420px',borderCollapse:'collapse',fontSize:'12.5px'}}>
									<thead><tr style={{background:'#fafbfc'}}>
										{['INVOICE','DATE','AMOUNT','PAID','DUE'].map((h,i)=>(<th key={i} style={{padding:'11px 12px',fontSize:'10.5px',fontWeight:'800',color:'#1f2937',letterSpacing:'0.7px',borderBottom:'1px solid #eef2f7',textAlign:['AMOUNT','PAID','DUE'].includes(h)?'right':'left',whiteSpace:'nowrap'}}>{h}</th>))}
									</tr></thead>
									<tbody>
										{pageData.map((row)=>(
											<tr key={row.id} style={{background:'#fff',borderBottom:'1px solid #f0f0f2'}}>
												<td style={{padding:'12px',fontWeight:'800',color:'rgb(234, 88, 12)',whiteSpace:'nowrap'}}>#{row.id}</td>
												<td style={{padding:'12px',color:'#64748b',whiteSpace:'nowrap'}}>{row.created_at || '—'}</td>
												<td style={{padding:'12px',textAlign:'right',fontWeight:'700',color:'#111827',whiteSpace:'nowrap'}}>{fmt(row.total_products)}</td>
												<td style={{padding:'12px',textAlign:'right',fontWeight:'600',color:Number(row.total_payments)>0?'#16a34a':'#9ca3af',whiteSpace:'nowrap'}}>{fmt(row.total_payments)}</td>
												<td style={{padding:'12px',textAlign:'right',fontWeight:'800',color:'#dc2626',whiteSpace:'nowrap'}}>{fmt(row.balance_due)}</td>
											</tr>
										))}
									</tbody>
								</table>
							</div>
							<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap',padding:'10px 14px',borderTop:'1px solid #f1f5f9'}}>{pager}</div>
						</div>
					)}
				</div>
				);
			}
			return (
        <div style={{
				borderRadius:'16px',border:'1px solid #eaecf2',
				background:'#fff',overflow:'hidden',
				boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
			}}>
            <div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'10px',flexWrap:'wrap'}}>
				<div style={{display:'flex',alignItems:'center',gap:'10px'}}>
					<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Unpaid Invoices</span>
					{data.length > 0 && <span style={{fontSize:'11px',fontWeight:'700',color:'#64748b',background:'#f1f5f9',borderRadius:'10px',padding:'3px 10px'}}>{data.length}</span>}
				</div>
				<div style={{fontSize:'12px',color:'#64748b'}}>
					Tip: Click an invoice to view its details
				</div>
            </div>
            <div style={{overflowX:'auto'}}>
				{data.length === 0
					? <div style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>No unpaid invoices</div>
					: <table style={{width:'100%',minWidth:'520px',borderCollapse:'collapse'}}>
						<thead>
							<tr style={{background:'#fafbfc'}}>
								<th style={{padding:'12px 22px',fontSize:'11px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.6px',textAlign:'left',borderBottom:'2px solid #eef2f7'}}>Invoice</th>
								<th style={{padding:'12px 18px',fontSize:'11px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.6px',textAlign:'left',borderBottom:'2px solid #eef2f7'}}>Date</th>
								<th style={{padding:'12px 18px',fontSize:'11px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.6px',textAlign:'right',borderBottom:'2px solid #eef2f7'}}>Invoice Amount</th>
								<th style={{padding:'12px 18px',fontSize:'11px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.6px',textAlign:'right',borderBottom:'2px solid #eef2f7'}}>Paid</th>
								<th style={{padding:'12px 22px',fontSize:'11px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.6px',textAlign:'right',borderBottom:'2px solid #eef2f7'}}>Pending</th>
							</tr>
						</thead>
						<tbody>
							{data.map((row) => (
								<tr key={row.id} style={{borderBottom:'1px solid #f3f4f6'}}>
									<td style={{padding:'14px 22px'}}>
										<a href={`/data_entry/sales_entry/invoice/${row.id}`} style={{textDecoration:'none'}}>
											<span style={{background:'#FFF7ED',border:'1px solid #fed7aa',borderRadius:'6px',padding:'3px 10px',fontWeight:'700',fontSize:'12px',color:'rgb(234, 88, 12)'}}>#{row.id}</span>
										</a>
									</td>
									<td style={{padding:'14px 18px',fontSize:'12px',color:'#64748b',fontWeight:'500'}}>{row.created_at}</td>
									<td style={{padding:'14px 18px',textAlign:'right',fontSize:'13px',fontWeight:'700',color:'#1e293b',fontVariantNumeric:'tabular-nums'}}>{fmt(row.total_products)}</td>
									<td style={{padding:'14px 18px',textAlign:'right',fontSize:'13px',fontWeight:'600',color: Number(row.total_payments) > 0 ? '#16a34a' : '#d1d5db',fontVariantNumeric:'tabular-nums'}}>{Number(row.total_payments) > 0 ? fmt(row.total_payments) : '—'}</td>
									<td style={{padding:'14px 22px',textAlign:'right',fontSize:'14px',fontWeight:'800',color:'rgb(234, 88, 12)',fontVariantNumeric:'tabular-nums'}}>{fmt(row.balance_due)}</td>
								</tr>
							))}
						</tbody>
						<tfoot>
							<tr style={{background:'#fafbfc',borderTop:'2px solid #eef2f7'}}>
								<td colSpan={4} style={{padding:'14px 22px',textAlign:'right',fontSize:'12px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.6px'}}>Total Pending</td>
								<td style={{padding:'14px 22px',textAlign:'right',fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)',fontVariantNumeric:'tabular-nums'}}>{fmt(totalPending)}</td>
							</tr>
						</tfoot>
					</table>
				}
            </div>
        </div>
			);
		})()}


		</>
		:<></>
    }</> );
}

export default function CustomerPaymentApp(props) {
	const dispatch = useDispatch();
    const customers = useSelector(state => state.customers.customers);
	
    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
		<div style={{marginBottom:'20px'}}>
			<CreateForm apiUrl={'/management/customers/view/list'} initialCustomerId={props.customerId} currency={props.currency} />
		</div>
		<div style={{ marginBottom: '80px' }}>
			<List currency={props.currency} />
		</div>
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('customer-payment-app')) {
    const id = "customer-payment-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<CustomerPaymentApp {...props} />
		</Provider>
    );
}

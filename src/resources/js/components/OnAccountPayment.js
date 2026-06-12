import React, { useEffect, useState } from 'react';
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
import OrangeDatePicker from "./../hooks/OrangeDatePicker";

// ----------------- Slice + Store -----------------
const customersSlice = createSlice({
    name: 'customers',
    initialState: { customers: [], currentCustomer:"", loading: false, refreshPayments: 0 },
    reducers: {
        setCustomers: (state, action) => { state.customers = action.payload },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
		setCustomersLoading: (state, action) => { state.loading = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now();
        },
    },
});

const { setCustomers, setCurrentCustomer, setCustomersLoading, triggerPaymentRefresh } = customersSlice.actions;

const store = configureStore({
    reducer: { customers: customersSlice.reducer},
});

// Helper: today's date as YYYY-MM-DD
function getTodayDate() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

const labelStyle = {fontSize:'11px',fontWeight:'700',color:'#374151',letterSpacing:'0.4px',textTransform:'uppercase',marginBottom:'6px',display:'block'};

// ----------------- Unified Form -----------------
function UnifiedForm({ apiUrl }) {
	const dispatch = useDispatch();
	const customers = useSelector(state => state.customers.customers);
	const customersLoading = useSelector(state => state.customers.loading);
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const [customerValue, setCustomerValue] = useState(null);
	const [loading, setLoading] = useState(0);

	useEffect(() => {
		const fetchCustomers = async () => {
			try {
				const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setCustomers(response.data.payload));
					if (response.data.payload.length > 0) {
						const first = response.data.payload[0];
						dispatch(setCurrentCustomer(first.id));
						setCustomerValue({ value: first.id, label: first.name });
					}
				}
			} catch (err) {
				console.error('Failed to load customers', err);
			}
		};
		fetchCustomers();
	}, [apiUrl, dispatch]);

	const customerOptions = customers.map(c => ({ value: c.id, label: c.name }));

	const notifyError = (error) => toast.error(error, { position: "top-right", autoClose: 3000, theme: "light" });
	const notifySuccess = (success) => toast.success(success, { position: "top-right", autoClose: 3000, theme: "light" });

	const paymentModes = [
		{ value: '2', label: 'Cash' },
		{ value: '3', label: 'Cheque' },
		{ value: '4', label: 'Card' },
		{ value: '5', label: 'Bank Transfer' },
	];

    const formik = useFormik({
        initialValues: {
            payment_mode: '',
            amount: '',
			note: '',
            date: getTodayDate(),
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
			if(!currentCustomer){ notifyError("Please select a customer"); return; }
			values.customer_id = currentCustomer;
			setLoading(1)
			try {
				const response = await axios.post('/management/customers/on_account_payment/create/store', values);
				if (response.data.success === true) {
					dispatch(triggerPaymentRefresh());
					formik.resetForm({ values: { payment_mode: '', amount: '', note: '', date: getTodayDate() } });
					notifySuccess("Success");
				}else{
					notifyError(response.data.payload);
				}
			} catch (err) {
				console.error('Failed to save payment', err);
				notifyError("Failed to save payment");
			}finally{
				setLoading(0)
			}
		},
    });

	const selectControlStyle = (base, state, hasError) => ({
		...orangeSelectStyles.control(base, state),
		minHeight:'42px',height:'42px',borderRadius:'10px',
		border: state.isFocused ? '1.5px solid #F27420' : hasError ? '1.5px solid #ef4444' : '1.5px solid #e5e7eb',
		background:'#f9fafb',
	});

    return (
        <div style={{borderRadius:'16px',border:'1px solid #f0f0f0',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',background:'#fff',padding:'20px 24px'}}>
            <form onSubmit={formik.handleSubmit}>
                <div style={{display:'flex',alignItems:'flex-start',gap:'14px',flexWrap:'wrap'}}>

                    {/* Customer */}
                    <div style={{minWidth:'200px',flex:1}}>
                        <label style={labelStyle}>Customer<span style={{color:'#ef4444'}}>*</span></label>
                        <Select
                            styles={{
                                ...orangeSelectStyles,
                                control: (base, state) => selectControlStyle(base, state, false),
                                valueContainer: (base) => ({...base,height:'42px',padding:'0 12px'}),
                                indicatorsContainer: (base) => ({...base,height:'42px'}),
                                placeholder: (base) => ({...base,fontSize:'12px',color:'#9ca3af'}),
                                singleValue: (base) => ({...base,fontSize:'12px',fontWeight:'600',color:'#111827'}),
                            }}
                            options={customerOptions}
                            isLoading={customersLoading}
                            isClearable
                            isSearchable
                            value={customerValue}
                            onChange={(selected) => {
                                setCustomerValue(selected);
                                dispatch(setCurrentCustomer(selected ? selected.value : null));
                            }}
                            placeholder="Select Customer"
                            classNamePrefix="react-select"
                        />
                    </div>

                    {/* Date */}
                    <div style={{minWidth:'140px'}}>
                        <label style={labelStyle}>Date<span style={{color:'#ef4444'}}>*</span></label>
                        <div style={{display:'inline-flex',alignItems:'center',background:'#f9fafb',border: formik.touched.date && formik.errors.date ? '1.5px solid #ef4444' : '1.5px solid #e5e7eb',borderRadius:'10px',overflow:'hidden',height:'42px'}}>
                            <div style={{padding:'0 14px',display:'flex',alignItems:'center',gap:'10px',height:'100%'}}>
                                <OrangeDatePicker value={formik.values.date} onChange={(val) => formik.setFieldValue('date', val)} />
                            </div>
                        </div>
                        {formik.touched.date && formik.errors.date && <div style={{color:'#ef4444',fontSize:'11px',marginTop:'4px'}}>{formik.errors.date}</div>}
                    </div>

                    {/* Payment Mode */}
                    <div style={{minWidth:'160px'}}>
                        <label style={labelStyle}>Payment Mode<span style={{color:'#ef4444'}}>*</span></label>
                        <Select
                            styles={{
                                ...orangeSelectStyles,
                                control: (base, state) => ({
                                    ...selectControlStyle(base, state, formik.touched.payment_mode && formik.errors.payment_mode),
                                    minWidth:'160px',
                                }),
                                valueContainer: (base) => ({...base,height:'42px',padding:'0 12px'}),
                                indicatorsContainer: (base) => ({...base,height:'42px'}),
                                placeholder: (base) => ({...base,fontSize:'12px',color:'#9ca3af'}),
                                singleValue: (base) => ({...base,fontSize:'12px',fontWeight:'600',color:'#111827'}),
                            }}
                            options={paymentModes}
                            value={paymentModes.find(m => m.value === formik.values.payment_mode) || null}
                            onChange={(opt) => formik.setFieldValue('payment_mode', opt ? opt.value : '')}
                            onBlur={() => formik.setFieldTouched('payment_mode', true)}
                            placeholder="Select Mode"
                            isClearable
                            classNamePrefix="react-select"
                        />
                        {formik.touched.payment_mode && formik.errors.payment_mode && <div style={{color:'#ef4444',fontSize:'11px',marginTop:'4px'}}>{formik.errors.payment_mode}</div>}
                    </div>

                    {/* Amount */}
                    <div style={{minWidth:'110px',maxWidth:'140px'}}>
                        <label style={labelStyle}>Amount<span style={{color:'#ef4444'}}>*</span></label>
                        <input
                            type="number"
                            min="0"
                            className="form-control"
                            name="amount"
                            placeholder="0.00"
                            value={formik.values.amount}
                            onChange={formik.handleChange}
                            onBlur={formik.handleBlur}
                            style={{height:'42px',borderRadius:'10px',border: formik.touched.amount && formik.errors.amount ? '1.5px solid #ef4444' : '1.5px solid #e5e7eb',fontSize:'13px',background:'#f9fafb'}}
                        />
                        {formik.touched.amount && formik.errors.amount && <div style={{color:'#ef4444',fontSize:'11px',marginTop:'4px'}}>{formik.errors.amount}</div>}
                    </div>

                    {/* Note */}
                    <div style={{flex:1,minWidth:'140px'}}>
                        <label style={labelStyle}>Note</label>
                        <input
                            type="text"
                            className="form-control"
                            name="note"
                            placeholder="Optional"
                            value={formik.values.note}
                            onChange={formik.handleChange}
                            onBlur={formik.handleBlur}
                            style={{height:'42px',borderRadius:'10px',border:'1.5px solid #e5e7eb',fontSize:'13px',background:'#f9fafb'}}
                        />
                    </div>

                    {/* Submit */}
                    <div style={{display:'flex',alignItems:'flex-end',paddingTop:'22px'}}>
                        <button type="submit" disabled={loading} className="btn" style={{background:'#F27420',color:'#fff',border:'none',borderRadius:'10px',height:'42px',padding:'0 24px',fontSize:'13px',fontWeight:'700',boxShadow:'0 2px 8px rgba(242,116,32,0.3)',opacity:loading?0.7:1,whiteSpace:'nowrap'}}>
                            {loading ? 'Saving...' : 'Submit'}
                        </button>
                    </div>
                </div>
            </form>
            <ToastContainer autoClose={3000} />
        </div>
    );
}


// list.
function List() {
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const refreshPayments = useSelector(state => state.customers.refreshPayments);
	const [data, setData] = useState([]);

    const columns = [
        { name: 'ID', selector: row => row.id, sortable: true },
        { name: 'Amount', selector: row => row.amount, sortable: false },
        { name: 'Payment Method', selector: row => row.payment_id, right: false },
		{ name: 'Note', selector: row => row.note, right: false },
        { name: 'Date', selector: row => row.created_at_full, sortable: true },
    ];

	const loadList = async() => {
		try {
			const response = await axios.post('/management/customers/on_account_payment/view/list', {customer_id:currentCustomer});
			if (response.data.success === true) {
				setData(response.data.payload)
			}
		} catch (err) {
			console.error('Failed to load payments', err);
		}
	}

	useEffect(() => {
		if(currentCustomer != ""){
			loadList();
		}
    },[currentCustomer, refreshPayments])

    return (
	<>{currentCustomer != ""
		?
        <div className="card">
            <div className="card-header pb-0">
                <h5 className="card-title mb-0 pb-0">Past Payments <a href="/data_entry/sales_entry/statements/view" target="_blank">(View Statement)</a></h5>
            </div>
            <div className="card-body mt-0">
                <DataTable
                    columns={columns}
                    data={data}
                    pagination
                    highlightOnHover
                    striped
                    responsive
                />
            </div>
        </div>
		:<></>
    }</>);
}

export default function OnAccountPayment(props) {
    return (
	<div className="row">
		<div className="col-12 mb-2">
			<UnifiedForm apiUrl={'/management/customers/view/list'} />
		</div>
		<div className="col-12 mt-1">
			<List />
		</div>
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('on-account-payment-app')) {
    const id = "on-account-payment-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<OnAccountPayment {...props} />
		</Provider>
    );
}

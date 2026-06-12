import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import logger from 'redux-logger';
import axios from 'axios';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import OrangeDatePicker from "./../hooks/OrangeDatePicker";

// ----------------- Slice + Store -----------------
const suppliersSlice = createSlice({
    name: 'suppliers',
    initialState: { suppliers: [], currentSupplier:"", loading: false, refreshPayments: 0 },
    reducers: {
        setSuppliers: (state, action) => { state.suppliers = action.payload },
        setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },
		setSuppliersLoading: (state, action) => { state.loading = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now(); // unique timestamp every trigger
        },
    },
});

const { setSuppliers, setCurrentSupplier, setSuppliersLoading, triggerPaymentRefresh } = suppliersSlice.actions;

const store = configureStore({
    reducer: { suppliers: suppliersSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

// ----------------- Component -----------------

// customer selection.
function CustomerSelect({ apiUrl, onSubmit }) {
    const dispatch = useDispatch();
    const suppliers = useSelector(state => state.suppliers.suppliers);
    const loading = useSelector(state => state.suppliers.loading);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetchCustomers = async () => {
            try {
                //dispatch(setLoading(true));
                const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setSuppliers(response.data.payload));
					if(response.data.payload.length > 0){
						dispatch(setCurrentSupplier(response.data.payload[0].id));
					}
				}
            } catch (err) {
                console.error('Failed to load suppliers', err);
            } finally {
                //dispatch(setLoading(false));
            }
        };

        fetchCustomers();
    }, [apiUrl, dispatch]);
	
	const options = suppliers.map(c => ({
		value: c.id,
		label: c.name,
	}));
	
	const handleChange = (selected) => {
        dispatch(setCurrentSupplier(selected ? selected.value : null));
    };

    const formik = useFormik({
        initialValues: {
            supplier_id: { label: '', value: '' },
        },
        validationSchema: Yup.object({
            supplier_id: Yup.object({
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
								value={options.find(o => o.value === currentSupplier) || null}
								isLoading={loading}
								isClearable
								isSearchable
								onChange={handleChange}
								classNamePrefix="react-select"
								placeholder="Select Supplier"
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

// form .
function CreateForm({ apiUrl, onSubmit }) {
	const dispatch = useDispatch();
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const suppliers = useSelector(state => state.suppliers.suppliers);
	const suppliersLoading = useSelector(state => state.suppliers.loading);
	const [loading, setLoading] = useState(0);

	useEffect(() => {
		(async () => {
			try {
				const res = await axios.get(apiUrl);
				if (res.data.success) {
					dispatch(setSuppliers(res.data.payload));
					if (res.data.payload.length > 0) dispatch(setCurrentSupplier(res.data.payload[0].id));
				}
			} catch (err) { console.error('Failed to load suppliers', err); }
		})();
	}, [apiUrl, dispatch]);

	const supplierOptions = suppliers.map(c => ({ value: c.id, label: c.name }));

	const handleSupplierChange = (sel) => {
		dispatch(setCurrentSupplier(sel ? sel.value : ""));
	};
	
	const notifyError = (error) => toast.error(error, {
		position: "top-right",
		autoClose: 3000,
		hideProgressBar: false,
		closeOnClick: true,
		pauseOnHover: true,
		draggable: true,
		theme: "light",
	});

	const notifySuccess = (success) => toast.success(success, {
		position: "top-right",
		autoClose: 3000,
		hideProgressBar: false,
		closeOnClick: true,
		pauseOnHover: true,
		draggable: true,
		theme: "light",
	});
	
	const paymentModes = [
		{ value: '2', label: 'Cash' },
		{ value: '3', label: 'Cheque' },
		{ value: '4', label: 'Card' },
		{ value: '5', label: 'Bank Transfer	' },
	];

    const formik = useFormik({
        initialValues: {
            payment_mode: '',
            amount: '',
			note: '',
            date: '',
        },
        validationSchema: Yup.object({
            payment_mode: Yup.string().required('Payment mode is required'),
            amount: Yup.number()
                .typeError('Amount must be a number')
                .min(1, 'Amount must be at least 1')
                .required('Amount is required'),
            date: Yup.date().required('Date is required'),
        }),
        onSubmit: async values => {
			values.supplier_id = currentSupplier;
			setLoading(1)
			try {
				const response = await axios.post('/management/suppliers/on_account_payment/create/store', values);
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

	const h = '44px';
	const lblStyle = {fontSize:'10.5px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'8px',display:'block'};
	const selectCtrl = {
		...orangeSelectStyles,
		control: (base, state) => ({
			...orangeSelectStyles.control(base, state),
			minHeight:h,height:h,borderRadius:'12px',
			border: state.isFocused ? '1.5px solid #F27420' : '1.5px solid #e2e8f0',
			background: state.isFocused ? '#fff' : '#f8fafc',
			boxShadow: state.isFocused ? '0 0 0 4px rgba(242,116,32,0.08)' : 'none',
			transition:'all 0.2s ease','&:hover':{borderColor:'#cbd5e1'},
		}),
		valueContainer: (base) => ({...base,height:h,padding:'0 14px'}),
		indicatorsContainer: (base) => ({...base,height:h}),
		placeholder: (base) => ({...base,fontSize:'13px',color:'#94a3b8',fontWeight:'500'}),
		singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
		menu: (base) => ({...base,borderRadius:'12px',border:'1px solid #e8ecf2',boxShadow:'0 12px 36px rgba(0,0,0,0.1)',overflow:'hidden',marginTop:'4px',zIndex:10}),
		menuPortal: (base) => ({...base,zIndex:9999}),
		option: (base, state) => ({
			...base,
			backgroundColor: state.isSelected ? '#F27420' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : state.isFocused ? '#F27420' : '#334155',
			fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',transition:'all 0.1s',
		}),
	};
	const inputStyle = {
		height:h,borderRadius:'12px',border:'1.5px solid #e2e8f0',fontSize:'13px',
		background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',
		transition:'all 0.2s ease',fontWeight:'500',color:'#1e293b',
	};
	const inputFocus = {borderColor:'#F27420',background:'#fff',boxShadow:'0 0 0 4px rgba(242,116,32,0.08)'};
	const errStyle = {color:'#ef4444',fontSize:'11px',marginTop:'5px',fontWeight:'500'};
	const dateBoxStyle = {
		display:'inline-flex',alignItems:'center',background:'#f8fafc',
		border:'1.5px solid #e2e8f0',borderRadius:'12px',overflow:'hidden',height:h,
		transition:'all 0.2s ease',
	};

    return (
        <div style={{
			borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',
			boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',padding:'24px 28px',
		}}>
                <form onSubmit={formik.handleSubmit}>
                    <div style={{display:'flex',alignItems:'flex-start',gap:'18px',flexWrap:'wrap'}}>
                        {/* Supplier */}
                        <div style={{minWidth:'220px',flex:1}}>
                            <label style={lblStyle}>Supplier<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
                            <Select
                                styles={selectCtrl}
                                options={supplierOptions}
                                value={supplierOptions.find(o => o.value === currentSupplier) || null}
                                isLoading={suppliersLoading}
                                isClearable isSearchable
                                onChange={handleSupplierChange}
                                classNamePrefix="react-select"
                                placeholder="Select Supplier"
                                menuPortalTarget={document.body}
                            />
                        </div>

                        {/* Payment Mode */}
                        <div style={{minWidth:'180px'}}>
                            <label style={lblStyle}>Payment Mode<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
                            <Select
                                styles={selectCtrl}
                                options={paymentModes}
                                value={paymentModes.find(m => m.value === formik.values.payment_mode) || null}
                                onChange={(opt) => formik.setFieldValue('payment_mode', opt ? opt.value : '')}
                                placeholder="Select Mode"
                                isClearable
                                classNamePrefix="react-select"
                                menuPortalTarget={document.body}
                            />
                            {formik.touched.payment_mode && formik.errors.payment_mode && <div style={errStyle}>{formik.errors.payment_mode}</div>}
                        </div>

                        {/* Amount */}
                        <div style={{minWidth:'140px'}}>
                            <label style={lblStyle}>Amount<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
                            <input
                                type="number"
                                name="amount"
                                min="1"
                                placeholder="1.00"
                                value={formik.values.amount}
                                onChange={(e) => { const v = e.target.value; if(v === '' || Number(v) >= 0) formik.handleChange(e); }}
                                onBlur={formik.handleBlur}
                                onKeyDown={(e) => { if(e.key === '-' || e.key === 'e') e.preventDefault(); }}
                                style={{...inputStyle, border: formik.touched.amount && formik.errors.amount ? '1.5px solid #ef4444' : '1.5px solid #e2e8f0'}}
                                onFocus={(e) => Object.assign(e.target.style, inputFocus)}
                            />
                            {formik.touched.amount && formik.errors.amount && <div style={errStyle}>{formik.errors.amount}</div>}
                        </div>

                        {/* Date */}
                        <div style={{minWidth:'150px'}}>
                            <label style={lblStyle}>Date<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
                            <div style={{...dateBoxStyle, border: formik.touched.date && formik.errors.date ? '1.5px solid #ef4444' : '1.5px solid #e2e8f0'}}>
                                <div style={{padding:'0 14px',display:'flex',alignItems:'center',height:'100%'}}>
                                    <OrangeDatePicker value={formik.values.date} onChange={(val) => formik.setFieldValue('date', val)} />
                                </div>
                            </div>
                            {formik.touched.date && formik.errors.date && <div style={errStyle}>{formik.errors.date}</div>}
                        </div>

                        {/* Note */}
                        <div style={{minWidth:'160px',flex:1}}>
                            <label style={lblStyle}>Note</label>
                            <input
                                type="text"
                                name="note"
                                placeholder="Optional"
                                value={formik.values.note}
                                onChange={formik.handleChange}
                                onBlur={formik.handleBlur}
                                style={inputStyle}
                                onFocus={(e) => Object.assign(e.target.style, inputFocus)}
                            />
                        </div>

                        {/* Submit */}
                        <div style={{paddingTop:'26px'}}>
                            <button type="submit" disabled={loading} style={{
                                height:h,padding:'0 28px',borderRadius:'12px',border:'none',
                                background:'#F27420',color:'#fff',fontSize:'13.5px',fontWeight:'700',
                                cursor:'pointer',transition:'all 0.15s',whiteSpace:'nowrap',
                                boxShadow:'0 2px 8px rgba(242,116,32,0.3)',
                            }}
                            onMouseOver={(e) => e.target.style.background='#e0600e'}
                            onMouseOut={(e) => e.target.style.background='#F27420'}
                            >{loading ? 'Saving...' : 'Submit'}</button>
                        </div>
                    </div>
                </form>
		<ToastContainer autoClose={3000} />
        </div>
    );
}


// list.
function List() {
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const refreshPayments = useSelector(state => state.suppliers.refreshPayments);
	const [data, setData] = useState([]);
	
    // Sample columns
    const columns = [
        { name: 'ID', selector: row => row.id, sortable: true },
        { name: 'Amount', selector: row => row.amount, sortable: false },
        { name: 'Payment Method', selector: row => row.payment_id, right: false },
		{ name: 'Note', selector: row => row.note, right: false },
        { name: 'Date', selector: row => row.created_at_full, sortable: true },
    ];

    // Sample rows
    /*const data = [
        { id: 1, name: 'John Doe', email: 'john@example.com', amount: 100, date: '2025-10-22' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', amount: 250, date: '2025-10-21' },
        { id: 3, name: 'Bob Johnson', email: 'bob@example.com', amount: 75, date: '2025-10-20' },
    ];*/
	
	const loadList = async() => {
		try {
			const response = await axios.post('/management/suppliers/on_account_payment/view/list', {supplier_id:currentSupplier});
			if (response.data.success === true) {
				setData(response.data.payload)
			}else{
			
			}
		} catch (err) {
			console.error('Failed to save payment', err);
		}finally{

		}
	}
	
	useEffect(() => {
		/*console.log('-----')
		console.log(currentSupplier)*/
		if(currentSupplier != ""){
			loadList();
		}
    },[currentSupplier, refreshPayments])

	const thStyle = {
		padding:'13px 18px',fontSize:'10.5px',fontWeight:'700',color:'#64748b',
		textTransform:'uppercase',letterSpacing:'0.7px',whiteSpace:'nowrap',
		borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',
	};
	const tdStyle = {
		padding:'14px 18px',borderBottom:'1px solid #f3f4f8',
		fontSize:'13.5px',fontWeight:'500',color:'#334155',
		fontVariantNumeric:'tabular-nums',verticalAlign:'middle',
	};

    return (
	<>{currentSupplier != ""
		?
        <div style={{
			borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',
			boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
		}}>
            <div style={{
				padding:'16px 22px',borderBottom:'1px solid #eef2f7',
				display:'flex',alignItems:'center',justifyContent:'space-between',
			}}>
                <span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Past Payments</span>
				<a href="/data_entry/purchase_entry/statements/view" target="_blank"
					style={{fontSize:'12.5px',fontWeight:'600',color:'#F27420',textDecoration:'none',
					padding:'6px 14px',borderRadius:'8px',border:'1px solid #fcd6b5',background:'#fffaf6',
					transition:'all 0.15s'}}>
					View Statement &rarr;
				</a>
            </div>
            <div style={{overflowX:'auto'}}>
				<table style={{width:'100%',borderCollapse:'collapse',tableLayout:'fixed'}}>
					<thead>
						<tr>
							<th style={{...thStyle,width:'60px'}}>#</th>
							<th style={{...thStyle,textAlign:'left',width:'15%'}}>Amount</th>
							<th style={{...thStyle,width:'20%'}}>Payment Method</th>
							<th style={{...thStyle,width:'30%'}}>Note</th>
							<th style={{...thStyle,width:'25%'}}>Date</th>
						</tr>
					</thead>
					<tbody>
						{data.length === 0 ? (
							<tr><td colSpan="5" style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>No payments found</td></tr>
						) : data.map((row, idx) => (
							<tr key={row.id}
								style={{background: idx % 2 === 0 ? '#fff' : '#fcfcfd',transition:'background 0.12s'}}
								onMouseEnter={(e) => e.currentTarget.style.background='#fafbfc'}
								onMouseLeave={(e) => e.currentTarget.style.background = idx % 2 === 0 ? '#fff' : '#fcfcfd'}
							>
								<td style={{...tdStyle,width:'50px'}}>
									<span style={{
										display:'inline-flex',alignItems:'center',justifyContent:'center',
										width:'26px',height:'26px',borderRadius:'7px',background:'#f1f5f9',
										fontSize:'11px',fontWeight:'700',color:'#64748b',
									}}>{idx + 1}</span>
								</td>
								<td style={{...tdStyle,textAlign:'left',fontWeight:'700',color:'#1e293b'}}>{Number(row.amount).toFixed(2)}</td>
								<td style={tdStyle}>
									<span style={{padding:'3px 10px',borderRadius:'6px',fontSize:'11.5px',fontWeight:'600',background:'#f1f5f9',color:'#475569'}}>{row.payment_id || '—'}</span>
								</td>
								<td style={{...tdStyle,color:'#64748b',maxWidth:'200px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{row.note || '—'}</td>
								<td style={{...tdStyle,color:'#64748b',fontSize:'12.5px',whiteSpace:'nowrap'}}>{row.created_at_full}</td>
							</tr>
						))}
					</tbody>
				</table>
            </div>
        </div>
		:<></>
    }</>);
}

export default function SupplierOnAccountPayment(props) {
	const dispatch = useDispatch();
    const suppliers = useSelector(state => state.suppliers.suppliers);
	
    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
		<div style={{marginBottom:'20px'}}>
			<CreateForm apiUrl={'/payments/supplier_payment/create/suppliers/list'} />
		</div>
		<div style={{marginBottom:'20px'}}>
			<List />
		</div>
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('supplier-on-account-payment-app')) {
    const id = "supplier-on-account-payment-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<SupplierOnAccountPayment {...props} />
		</Provider>
    );
}
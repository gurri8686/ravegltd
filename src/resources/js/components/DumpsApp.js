import React, { useEffect, useState,useMemo,useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import axios from 'axios';
import logger from 'redux-logger';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';

import $ from 'jquery';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import 'datatables.net-buttons-dt/css/buttons.dataTables.css';
import 'datatables.net-dt';
import 'datatables.net-buttons/js/dataTables.buttons';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';
import jszip from 'jszip';

//import pdfMake from 'pdfmake/build/pdfmake';
//import pdfFonts from 'pdfmake/build/vfs_fonts';

//pdfMake.vfs = pdfFonts.pdfMake.vfs;
//window.JSZip = jszip;

import { useToast } from "./../hooks/useToast";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DateRangePicker from "./../hooks/DateRangePicker";
import { ReturnHistoryApp } from "./ReturnHistoryApp";
import _ from 'lodash';
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecTableEmpty from "./../elements/SpecTableEmpty";

// ----------------- Slice + Store -----------------
const formattedToday = new Date().toISOString().split('T')[0];
const formattedYesterday = (() => { const d = new Date(); d.setMonth(d.getMonth() - 1); return d.toISOString().split('T')[0]; })();

const suppliersSlice = createSlice({
    name: 'suppliers',
    initialState: { 
		suppliers: [],
		products:[],
		currentSupplier:"", 
		currentSupplierInfo:{},
		loading: false, 
		refreshPayments: 0 ,
		paymentMode:"",
		amount:0,
		date:formattedToday,
		end_date:formattedToday,
		note:"",
		invoices:[],
		invoicesData:[],
		totalChecked:0,
		paymentDoable : 0,
		pendingInvoiceCount:1,
		accountPayments:[],
		onAccount:"",
		refreshCount:0
	},
    reducers: {
        setSuppliers: (state, action) => { state.suppliers = action.payload },
		setRefreshCount: (state, action) => { state.refreshCount = action.payload },
		setProducts: (state, action) => { state.products = action.payload },
        setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },
		setCurrentSupplierInfo: (state, action) => { state.currentSupplierInfo = action.payload; },
		setSuppliersLoading: (state, action) => { state.loading = action.payload; },
		
		setPaymentMode: (state, action) => { state.paymentMode = action.payload; },
		setAmount: (state, action) => { state.amount = action.payload; },
		setDate: (state, action) => { state.date = action.payload; },
		setEndDate: (state, action) => { state.end_date = action.payload; },
		setNote: (state, action) => { state.note = action.payload; },
		setInvoices: (state, action) => { state.invoices = action.payload; },
		setTotalChecked: (state, action) => { state.totalChecked = action.payload; },
		setPaymentDoable: (state, action) => { state.paymentDoable = action.payload; },
		setAccountPayments: (state, action) => { state.accountPayments = action.payload; },
		setOnAccount: (state, action) => { state.onAccount = action.payload; },
		
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
			state.paymentMode = "";
			state.paymentDoable = 0;
			state.invoices = [];
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
		resetSuppliersState: (state) => {
            state.currentSupplier = "";
            state.loading = false;
            state.refreshPayments = 0;
            //state.paymentMode = "";
            //state.amount = 0;
            /*state.date = "";
            state.note = "";
            state.invoices = [];
			state.invoicesData = [];
			state.totalChecked = 0;
			state.paymentDoable = 0;
			state.accountPayments=[];
			state.onAccount=""*/
        }
    },
});

const {setSuppliers, setCurrentSupplier,setRefreshCount,setEndDate, setSuppliersLoading, triggerPaymentRefresh, setCurrentSupplierInfo,setProducts,
	setPaymentMode, setAmount, setDate, setNote, setInvoices, resetSuppliersState,setTotalChecked, 
	setPaymentDoable, toggleInvoice, resetAccountPayments,resetPaymentForm, resetInvoices, setAccountPayments, setOnAccount} = suppliersSlice.actions;

const store = configureStore({
    reducer: { suppliers: suppliersSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

// Clear-filters handler for the empty state — clears supplier + date so ALL
// records (the full date range present in the DB) are fetched and shown.
const clearDumpFilters = () => {
	store.dispatch(setCurrentSupplier(null));
	store.dispatch(setCurrentSupplierInfo(null));
	store.dispatch(setDate(''));
	store.dispatch(setEndDate(''));
};

// ----------------- Component -----------------

// customer selection.
function SupplierSelect({ apiUrl, onSubmit }) {
    const dispatch = useDispatch();
    const suppliers = useSelector(state => state.suppliers.suppliers);
    const loading = useSelector(state => state.suppliers.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetchSuppliers = async () => {
            try {
                //dispatch(setLoading(true));
                const response = await axios.get(apiUrl);
				if(response.data.success === true){
					dispatch(setSuppliers(response.data.payload)); // store suppliers in Redux
				}
            } catch (err) {
                console.error('Failed to load suppliers', err);
            } finally {
                //dispatch(setLoading(false));
            }
        };

        fetchSuppliers();
    }, [apiUrl, dispatch]);
	
	const options = [
		{ value: '', label: '-- Select Supplier --' }, // 👈 fake empty option
		...suppliers.map(c => ({
			value: c.id,
			label: c.name,
		})),
	];
	
	const handleChange = (selected) => {
		const selectedSupplier = suppliers.find(
		  (c) => c.id === selected.value
		);
		dispatch(resetSuppliersState());
        dispatch(setCurrentSupplierInfo(selectedSupplier));
		dispatch(setCurrentSupplier(selected ? selected.value : null));
    };

    const formik = useFormik({
        initialValues: {
            supplier_id: { label: '', value: '' },
        },
        validationSchema: Yup.object({
            supplier_id: Yup.object({
                label: Yup.string().required(),
                value: Yup.string().required('Supplier is required'),
            }).required('Supplier is required'),
        }),
        onSubmit: values => {
            onSubmit(values);
        },
    });

    return null; // Replaced by combined FilterBar
}

// form .
function CreateForm({ onSubmit }) {
	const dispatch = useDispatch();
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const {paymentMode,amount,date,end_date,note,accountPayments,onAccount} = useSelector(state => state.suppliers);
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
            /*payment_mode: '',
            amount: '',
			note: '',*/
            date: date,
			//end_date: date,
        },
        validationSchema: Yup.object({
            /*payment_mode: Yup.string().required('Payment mode is required'),
            amount: Yup.number()
                .typeError('Amount must be a number')
                .positive('Amount must be positive')
                .required('Amount is required'),*/
            date: Yup.date().required('Date is required'),
			//end_date: Yup.date().required('Date is required'),
        }),
        onSubmit: async values => {
			values.supplier_id = currentSupplier;
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
			const response = await axios.get('/payments/supplier_payment/create/on-account-payments/'+currentSupplier);
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
	  { value: '', label: '-- Select --' }, // 👈 default empty option
	  ...accountPayments
		.filter(c => parseFloat(c.remaining_amount) > 0) // ✅ include only if numeric > 0.00
		.map(c => ({
		  value: { id: c.payment_id, amount: parseFloat(c.remaining_amount) }, // ✅ numeric type
		  label: `${c.created_at_full} | ${parseFloat(c.remaining_amount).toFixed(2)}`, // ✅ formatted
		})),
	];

	
	const handleChange = (e) => {
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
	
    return null; // Replaced by combined FilterBar
}


// Actions cell extracted to a top-level component so hooks are not called inside a per-row cell callback.
function ActionsCell({ row, index, saveSingleRow, updateSingleRow, removeRow }) {
        const [open, setOpen] = useState(false);
        const [pos, setPos] = useState({top:0,left:0});
        const btnRef = useRef(null);
        const canSave = row.supplier_id && row.product_id && row.invoice_id && row.quantity && row.price;
        const handleOpen = () => {
          if(btnRef.current){
            const rect = btnRef.current.getBoundingClientRect();
            setPos({top: rect.bottom + 4, left: rect.right - 140});
          }
          setOpen(!open);
        };
        return (
          <div style={{position:'relative'}}>
            <button ref={btnRef} type="button" onClick={handleOpen}
              style={{border:'1.5px solid #e2e8f0',borderRadius:'8px',background:'#fff',color:'#475569',
                fontSize:'12px',fontWeight:'600',padding:'6px 10px',cursor:'pointer',outline:'none',
                display:'inline-flex',alignItems:'center',gap:'4px'}}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
            {open && (
              <>
              <div style={{position:'fixed',top:0,left:0,width:'100%',height:'100%',zIndex:9998}} onClick={() => setOpen(false)}></div>
              <div style={{position:'fixed',top:pos.top,left:pos.left,background:'#fff',
                border:'1px solid #e8ecf2',borderRadius:'12px',boxShadow:'0 12px 36px rgba(0,0,0,0.12)',
                zIndex:9999,minWidth:'140px',overflow:'hidden',padding:'4px 0'}}>
                {row.id === "" ? (
                  <div style={{padding:'8px 16px',fontSize:'13px',fontWeight:'600',color: canSave ? '#1e293b' : '#cbd5e1',
                    cursor: canSave ? 'pointer' : 'default',display:'flex',alignItems:'center',gap:'8px'}}
                    onClick={() => { if(canSave){ saveSingleRow(row, index); setOpen(false); } }}
                    onMouseOver={(e) => { if(canSave) e.currentTarget.style.background='#FFF5ED'; }}
                    onMouseOut={(e) => e.currentTarget.style.background='transparent'}>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save
                  </div>
                ) : (
                  <>
                    <div style={{padding:'8px 16px',fontSize:'13px',fontWeight:'600',color: canSave ? '#1e293b' : '#cbd5e1',
                      cursor: canSave ? 'pointer' : 'default',display:'flex',alignItems:'center',gap:'8px'}}
                      onClick={() => { if(canSave){ updateSingleRow(row, index); setOpen(false); } }}
                      onMouseOver={(e) => { if(canSave) e.currentTarget.style.background='#FFF5ED'; }}
                      onMouseOut={(e) => e.currentTarget.style.background='transparent'}>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      Update
                    </div>
                    <div style={{padding:'8px 16px',fontSize:'13px',fontWeight:'600',color:'#1e293b',
                      cursor:'pointer',display:'flex',alignItems:'center',gap:'8px'}}
                      onClick={() => { removeRow(row, index); setOpen(false); }}
                      onMouseOver={(e) => e.currentTarget.style.background='#FEF2F2'}
                      onMouseOut={(e) => e.currentTarget.style.background='transparent'}>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      Delete
                    </div>
                  </>
                )}
              </div>
              </>
            )}
          </div>
        );
}

// list.
function List(props) {
	const {currentSupplierInfo,products: allProducts,end_date, refreshCount, suppliers, totalChecked, invoices,amount,paymentMode,date,note,paymentDoable,onAccount} = useSelector(state => state.suppliers);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const refreshPayments = useSelector(state => state.suppliers.refreshPayments);
	const [supplierProducts, setSupplierProducts] = useState([]);
	const products = supplierProducts.length > 0 ? supplierProducts : allProducts;
	const [data, setData] = useState([]);
	const [refreshGrid, setRefreshGrid] = useState(0);
	const [allSet, setAllSet] = useState(false);
	const [saving, setSaving] = useState(0);
	const [balance, setBalance] = useState(0);
	const [invoicesAmount, setInvoicesAmount] = useState(0);
	const [selectedRows, setSelectedRows] = useState([]);
	
	const { notifySuccess, notifyError } = useToast();
	
	const tableRef = useRef(null);
	const dataTableRef = useRef(null);

	const dispatch = useDispatch();

	// Load supplier-specific products
	useEffect(() => {
		if (!currentSupplier || !props.supplierProductsApi) { setSupplierProducts([]); return; }
		(async () => {
			try {
				const res = await axios.post(props.supplierProductsApi, { supplier_id: currentSupplier });
				if (res.data.success) setSupplierProducts(res.data.payload || []);
				else setSupplierProducts([]);
			} catch (e) { setSupplierProducts([]); }
		})();
	}, [currentSupplier]);
	
	const formik = useFormik({
		initialValues: {
		  rows: [
			
		  ],
		},
		validationSchema: Yup.object({
		  rows: Yup.array().of(
			Yup.object({
			  product_id: Yup.string().required('Product required'),
			  quantity: Yup.number().required('Qty required').positive(),
			  price: Yup.number().required('Price required').positive(),
			  invoice_id: Yup.string().required('Invoice ID required'),
			})
		  ),
		}),
		onSubmit: async (values) => {
		  // Save all rows at once if needed
		  try {
			await axios.post('/api/stock-products/bulk-save', values.rows);
			toast.success('All rows saved successfully!');
		  } catch (err) {
			toast.error('Bulk save failed');
		  }
		},
	});
	
	const updateFormikRows = (index, jsonData) => {
		const updatedRows = [...formik.values.rows];
		updatedRows[index] = { ...updatedRows[index], ...jsonData };
		formik.setFieldValue('rows', updatedRows);
	}
	
	// Function to load API data and prepend
	const fetchAndPrependRows = async () => {
		try {
			const response = await axios.post(props.invoicesReturnsApi, {supplier_id:currentSupplier, date:date, end_date:end_date}); // your endpoint
			const apiRows = response.data; // assuming this returns an array of rows
			
			if(apiRows.success === true){
				return apiRows.payload;
			}else{
				return [];
			}
			
		} catch (err) {
		  console.error('Failed to load rows:', err);
		}
	};
	
	/*
	// datable testing.
	useEffect(() => {
		console.log('refreshGrid')
		console.log(refreshGrid)
		if(refreshGrid > 0){
			// ✅ Initialize DataTable
			if (tableRef.current) {
			  dataTableRef.current = $(tableRef.current).DataTable({
				destroy: true, // Important for re-init
				paging: true,
				searching: true,
				ordering: true,
			  });
			}

			// ✅ Cleanup on unmount
			return () => {
			  if (dataTableRef.current) {
				dataTableRef.current.destroy(true);
				dataTableRef.current = null;
			  }
			};
		}
	}, [refreshGrid]);*/
	
	useEffect(() => {
		//formik.setFieldValue('rows', []);
		const load = async () => {
			try {
				// fetchAndPrependRows should return an array (or [] if none)
				const apiRows = await fetchAndPrependRows(); // -> e.g. [{...}, {...}]
				
				let blankRow = null;
				if (currentSupplier) {
					blankRow = {
						id:"",
						product_id: '',
						quantity: '',
						price: '',
						invoice_id: '',
						note: '',
						supplier_id: currentSupplier,
						date: date, // ensure `date` is in scope
						invoices: [],
						total:"",
						supplier: currentSupplierInfo?.name || '',
					};
				}
				const finalRows = blankRow ? [...apiRows, blankRow] : apiRows;
				formik.setFieldValue('rows', finalRows);
			} catch (err) {
				console.error('load rows error', err);
			}finally{
				console.log(1)
			}
		};
		load();
	}, [currentSupplier, date, refreshCount]);
	
	const handleProductChange = async(index, e) => {
		const updatedRows = [...formik.values.rows];
		updatedRows[index] = { ...updatedRows[index], ...{product_id:e} };
		
		// call api to get the invoices.
		try {
			const response = await axios.post(props.invoicesListApi, {supplier_id:currentSupplier, product_id:e.value, date:date, end_date:end_date}); // your endpoint
			const apiRows = response.data; // assuming this returns an array of rows
			
			if(apiRows.success === true){
				updatedRows[index] = { ...updatedRows[index], ...{invoices:apiRows.payload} };
			}else{
				updatedRows[index] = { ...updatedRows[index], ...{invoices:[]} };
			}
			updatedRows[index] = { ...updatedRows[index], ...{invoice_id:""} };
		  formik.setFieldValue('rows', updatedRows);
		} catch (err) {
		  console.error('Failed to load rows:', err);
		}
	}
	
	const handleInvoiceChange = async(index, e) => {
		const updatedRows = [...formik.values.rows];
		updatedRows[index] = { ...updatedRows[index], ...{invoice_id:e} };
		try {
			const response = await axios.post(props.invoicesProductApi, 
				{
				supplier_id:formik.values.rows[index].supplier_id,
				date:formik.values.rows[index].date,
				invoice_id:e,
				product_id:formik.values.rows[index].product_id
			});
				
			const apiRows = response.data; // assuming this returns an array of rows
			
			if(apiRows.success === true){
				if(typeof apiRows.payload.id != "undefined"){
					updatedRows[index] = { ...updatedRows[index], ...{quantity:apiRows.payload.quantity} };
					updatedRows[index] = { ...updatedRows[index], ...{price:apiRows.payload.unit_price} };
					updatedRows[index] = { ...updatedRows[index], ...{total:apiRows.payload.unit_price * apiRows.payload.quantity} };
				}
			}
			formik.setFieldValue('rows', updatedRows);
		  
		} catch (err) {
		  console.error('Failed to load rows:', err);
		}
		formik.setFieldValue('rows', updatedRows);
	}
	
	// --- Utility handlers ---
	const addRow = () => {
		formik.setFieldValue('rows', [
		  ...formik.values.rows,
		  { product_id: '', quantity: '', price: '', invoice_id: '' },
		]);
	};

	const removeRow = async(row, index) => {
		try {
			updateFormikRows(index, {deleting:true})
			const response = await axios.post(props.invoicesReturnDeleteApi, row);
			if(response.data.success === true){
				notifySuccess("Deleted Successfully!");
				setTimeout(function(){
					const updated = formik.values.rows.filter((_, i) => i !== index);
					formik.setFieldValue('rows', updated);
				}, 500);
			}else{
				notifyError(response.data.payload);
			}
		} catch {
			
		}finally{
			updateFormikRows(index, {deleting:false})
		}
		/*const updated = formik.values.rows.filter((_, i) => i !== index);
		formik.setFieldValue('rows', updated);*/
	};

	const saveSingleRow = async (row, index) => {
		try {
			updateFormikRows(index, {creating:true})
			const response = await axios.post(props.invoicesReturnCreateApi, row);
			if(response.data.success === true){
				notifySuccess("Returned Successfully!");
				dispatch(setRefreshCount(Date.now()))
			}else{
				notifyError(response.data.payload);
			}
		} catch {
			
		}finally{
			updateFormikRows(index, {creating:false})
		}
	};
	
	const updateSingleRow = async (row, index) => {
		try {
			updateFormikRows(index, {updating:true})
			const response = await axios.post(props.invoicesReturnUpdateApi, row);
			if(response.data.success === true){
				notifySuccess("Returned Successfully!");
				//dispatch(setRefreshCount(Date.now()))
			}else{
				notifyError(response.data.payload);
			}
		} catch {
			
		}finally{
			updateFormikRows(index, {updating:false})
		}
	};
	
	const options = [
		{ value: '', label: '-- Select Product --' }, // 👈 fake empty option
		...products.map(c => ({
			value: c.id,
			label: c.name,
		})),
	];
	
	const columns = [
    {
      name: "#",
      selector: (row, index) => index + 1,
      width: "50px",
      cell: (row, index) => <span style={{display:'inline-flex',alignItems:'center',justifyContent:'center',width:'24px',height:'24px',borderRadius:'6px',background:'#f1f5f9',fontSize:'11px',fontWeight:'700',color:'#64748b'}}>{index + 1}</span>,
    },
    {
      name: "Date",
      selector: (row) => row.date,
      sortable: true,
      width: "120px",
      cell: (row) => <span style={{whiteSpace:'nowrap',fontSize:'12.5px',color:'#64748b'}}>{row.date}</span>,
    },
    {
      name: "Supplier",
      selector: (row) => row.supplier,
      sortable: true,
      cell: (row) => <span style={{fontWeight:'600',color:'#1e293b'}}>{row.supplier}</span>,
    },
    {
	  name: "Product ID",
	  cell: (row, index) =>
		row.id === "" ? (
		  <div style={{ width: "100%" }}>
			<Select
			  options={options}
			  isClearable
			  isSearchable
			  value={row.product_id}
			  onChange={(e) => handleProductChange(index, e)}
			  classNamePrefix="react-select"
			  menuPortalTarget={document.body}
			  styles={{
				container: base => ({
				  ...base,
				  width: "100%",   // Full width container
				}),
				control: base => ({
				  ...base,
				  width: "100%",   // Full width control
				  minHeight: "38px",
				}),
				menuPortal: base => ({ ...base, zIndex: 9999 }),
			  }}
			/>
		  </div>
		) : (
		  row.product_id
		),
	  grow: 3,
	},
    {
	  name: "Invoice ID",
	  cell: (row, index) =>
		row.id === "" ? (
		  <div style={{ width: "100%" }}>
			<Select
			  options={row.invoices}
			  isClearable
			  isSearchable
			  value={row.invoice_id}
			  onChange={(e) => handleInvoiceChange(index, e)}
			  classNamePrefix="react-select"
			  menuPortalTarget={document.body}
			  styles={{
				container: base => ({
				  ...base,
				  width: "100%",   // Full width container
				}),
				control: base => ({
				  ...base,
				  width: "100%",   // Full width control
				  minHeight: "38px",
				}),
				menuPortal: base => ({ ...base, zIndex: 9999 }),
			  }}
			/>

			{row.product_id && row.product_id.value && (
				(row.invoices?.length ?? 0) <= 0 ? (
					<span style={{fontSize:'10px',color:'#dc2626',fontWeight:'600',marginTop:'2px',display:'block'}}>No Invoice</span>
				) : null
			)}
		  </div>
		) : (
		  row.invoice_id
		),
	  grow: 3,
	},
    {
      name: "Note",
      cell: (row, index) =>
        row.id === "" ? (
          <input
            type="text"
            className="form-control"
            name={`rows[${index}].note`}
            defaultValue={row.note}
            onChange={formik.handleChange}
          />
        ) : (
          row.note
        ),
      grow: 2,
    },
    {
      name: "Quantity",
      cell: (row, index) => (
        <input
          type="number"
          min="1"
          className="form-control"
          name={`rows[${index}].quantity`}
          value={row.quantity}
          onChange={formik.handleChange}
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
        />
      ),
      width: "120px",
	  grow: 1,
    },
    {
      name: "Price",
      cell: (row, index) => (
        <input
          type="number"
          min="0"
          className="form-control"
          name={`rows[${index}].price`}
          value={row.price}
          onChange={formik.handleChange}
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
        />
      ),
      width: "120px",
	  grow: 1,
    },
    {
      name: "Total",
      selector: (row) => Number(row.quantity) * Number(row.price) || 0,
      sortable: true,
      width: "120px",
	  grow: 1,
	  center: true,
	  cell: (row) => <span style={{fontWeight:'700',color:'#1e293b'}}>{(Number(row.quantity) * Number(row.price) || 0).toFixed(2)}</span>,
    },
    {
      name: "Actions",
      center: true,
      cell: (row, index) => (
        <ActionsCell
          row={row}
          index={index}
          saveSingleRow={saveSingleRow}
          updateSingleRow={updateSingleRow}
          removeRow={removeRow}
        />
      ),
      width: "80px",
    },
  ];
	
	//return (<>{console.log('----')}{console.log(formik.values.rows)}</>)
	return (
	<>{date != ""
		?
			<>
                <div style={{display:'flex',alignItems:'center',gap:'12px',marginBottom:'16px',flexWrap:'wrap'}}>
					{currentSupplierInfo?.name && <span style={{fontSize:'14px',fontWeight:'700',color:'#1e293b'}}>{currentSupplierInfo.name}</span>}
					<span style={{fontSize:'12.5px',color:'#94a3b8',fontWeight:'500'}}>{date} — {end_date}</span>
				</div>
				<form onSubmit={formik.handleSubmit}>
				{
					<>
					{currentSupplier ? 
					<div className="card">
						<div className="card-header pb-0">
						<h5><b>Add Return</b></h5>
					<table ref={tableRef} className="table c-table table-hover dataTable mt-2">
						<thead>
						  <tr>
							<th>Product ID</th>
							<th>Invoice ID</th>
							<th>Note</th>
							<th>Quantity</th>
							<th>Price</th>
							<th>Total</th>
							<th className="text-right">Actions</th>
						  </tr>
						</thead>
						<tbody>
						  {formik.values.rows.map((row, index) => (
						  row.id === "" ? (
							<tr key={`empty_${index}`}>
							  <td>
								<Select styles={orangeSelectStyles}
								  options={options}
								  isClearable
								  isSearchable
								  value={row.product_id}
								  onChange={(e) => handleProductChange(index, e)}
								  classNamePrefix="react-select"
								/>
							  </td>

							  <td>
								<Select styles={orangeSelectStyles}
								  options={row.invoices}
								  isClearable
								  isSearchable
								  value={row.invoice_id}
								  onChange={(e) => handleInvoiceChange(index, e)}
								  classNamePrefix="react-select"
								/>
								{row.product_id && row.product_id.value && (row.invoices?.length ?? 0) <= 0
								  ? <span className="text-sm text-white badge badge-danger">No Invoice</span>
								  : null}
							  </td>

							  <td>
								<input
								  type="text"
								  className="form-control"
								  name={`rows[${index}].note`}
								  defaultValue={row.note}
								  onChange={formik.handleChange}
								/>
							  </td>

							  <td>
								<input
								  type="number"
								  min="1"
								  className="form-control"
								  name={`rows[${index}].quantity`}
								  value={row.quantity}
								  onChange={formik.handleChange}
								  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
								/>
							  </td>

							  <td>
								<input
								  type="number"
								  min="0"
								  className="form-control"
								  name={`rows[${index}].price`}
								  value={row.price}
								  onChange={formik.handleChange}
								  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
								/>
							  </td>

							  <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>

							  <td className="text-right">
							  {
								row.creating === false || !('creating' in row)
							  ?
								<button
								  type="button"
								  disabled={
									!(
									  row.supplier_id &&
									  row.product_id &&
									  row.invoice_id &&
									  row.quantity &&
									  row.price
									)
								  }
								  className="btn btn-sm btn-success"
								  onClick={() => saveSingleRow(row, index)}
								>
								  Save
								</button>
								:
								<></>
								}
								&nbsp;

								{row.id !== "" && (
								  <button
									type="button"
									className="btn btn-danger"
									onClick={() => removeRow(index)}
								  >
									Delete
								  </button>
								)}
							  </td>
							</tr>
						  ) : (
							<React.Fragment key={`skip_${index}`} />
						  )
						))}
						</tbody>
					</table>
					</div></div>
					:
					<></>}
					
					<div style={{
						borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',
						boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
					}}>
						<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
							<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Returns List</span>
							{formik.values.rows.filter(r => r.id !== "").length > 0 && (
								<span style={{fontSize:'11.5px',fontWeight:'700',color:'rgb(234, 88, 12)',background:'#FFF5ED',padding:'3px 10px',borderRadius:'6px'}}>
									{formik.values.rows.filter(r => r.id !== "").length} records
								</span>
							)}
						</div>
						<div style={{ width: "100%", overflow: "visible" }}>
						  <DataTable
							columns={columns}
							data={formik.values.rows.filter(r => r.id !== "")}
							highlightOnHover
							pagination
							paginationPerPage={10}
							paginationRowsPerPageOptions={[5, 10, 20, 50]}
							responsive
							customStyles={{
								headCells: { style: {
									fontWeight:'700',fontSize:'11px',color:'#64748b',
									textTransform:'uppercase',letterSpacing:'0.5px',
									padding:'12px 12px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',
									whiteSpace:'nowrap',
								}},
								rows: { style: {
									fontSize:'13px',fontWeight:'500',color:'#334155',
									minHeight:'50px',borderBottom:'1px solid #f3f4f8',
									'&:hover': { backgroundColor:'#FEFAF6' },
								}},
								cells: { style: { padding:'8px 12px' }},
								pagination: { style: { borderTop:'1px solid #eef2f7',fontSize:'13px' }},
							}}
							noDataComponent={<SpecTableEmpty onClear={clearDumpFilters} />}
						  />
						</div>
					</div>
					</>
				/*
				 <table ref={tableRef} className="table c-table table-hover dataTable mt-2">
					<thead>
					  <tr>
						<th>#</th>
						<th>Date</th>
						<th>Customer</th>
						<th>Product ID</th>
						<th>Invoice ID</th>
						<th>Note</th>
						<th>Quantity</th>
						<th>Price</th>
						<th>Total</th>
						<th className="text-right">Actions</th>
					  </tr>
					</thead>
					<tbody>
					  {formik.values.rows.map((row, index) => (
					  row.id === "" ? (
						<tr key={index}>
						  <td>{index + 1}</td>
						  <td>{row.date}</td>
						  <td>{row.customer}</td>

						  <td>
							<Select styles={orangeSelectStyles}
							  options={options}
							  isClearable
							  isSearchable
							  value={row.product_id}
							  onChange={(e) => handleProductChange(index, e)}
							  classNamePrefix="react-select"
							/>
						  </td>

						  <td>
							<Select styles={orangeSelectStyles}
							  options={row.invoices}
							  isClearable
							  isSearchable
							  value={row.invoice_id}
							  onChange={(e) => handleInvoiceChange(index, e)}
							  classNamePrefix="react-select"
							/>
							{row.product_id && row.product_id.value && (row.invoices?.length ?? 0) <= 0
							  ? <span className="text-sm text-white badge badge-danger">No Invoice</span>
							  : null}
						  </td>

						  <td>
							<input
							  type="text"
							  className="form-control"
							  name={`rows[${index}].note`}
							  defaultValue={row.note}
							  onChange={formik.handleChange}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="1"
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="0"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>

						  <td className="text-right">
						  {
							row.creating === false || !('creating' in row)
						  ?
							<button
							  type="button"
							  disabled={
								!(
								  row.customer_id &&
								  row.product_id &&
								  row.invoice_id &&
								  row.quantity &&
								  row.price
								)
							  }
							  className="btn btn-sm btn-success"
							  onClick={() => saveSingleRow(row, index)}
							>
							  Save
							</button>
							:
							<></>
							}
							&nbsp;

							{row.id !== "" && (
							  <button
								type="button"
								className="btn btn-danger"
								onClick={() => removeRow(index)}
							  >
								Delete
							  </button>
							)}
						  </td>
						</tr>
					  ) : (
						<tr key={index}>
						  <td>{index + 1}</td>
						  <td colSpan="" className="">{row.date}</td>
						  <td colSpan="" className="">{row.customer}</td>
						  <td colSpan="" className="">{row.product_id}</td>
						  <td colSpan="" className="">{row.invoice_id}</td>
						  <td colSpan="" className="">{row.note}</td>
						 <td>
							<input
							  type="number"
							  min="1"
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="0"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>
						  <td colSpan="" className="text-right">
						  
						  {row.updating === false || !('updating' in row)
						  ?
						  <button
							  type="button"
							  disabled={
								!(
								  row.customer_id &&
								  row.product_id &&
								  row.invoice_id &&
								  row.quantity &&
								  row.price
								)
							  }
							  className="btn btn-sm btn-success"
							  onClick={() => updateSingleRow(row, index)}
							>
							  Update
							</button>
							:
							<></>
						  }
						  &nbsp;
						  <button
							type="button"
							className="btn btn-sm btn-danger"
							onClick={() => removeRow(row, index)}
						  >
							Delete
						  </button>
						  </td>
						</tr>
					  )
					))}
					  {formik.values.rows.length <= 0
					  ?
					  <></>
					  :
					  <></>
					  }
					  
					</tbody>
				</table>*/}
				{/*
				  <br />
				  <button type="submit">💾 Save All</button>
				*/}
				</form>
			</>
            
		:
		<></>
    }</>);

    dep_return (
	<>{date != ""
		?
        <div className="card">{console.log(formik.values.rows)}
            <div className="card-header pb-0">
                <h5 className="card-title mb-0 pb-0">Supplier: {currentSupplierInfo?.name ? currentSupplierInfo?.name : 'None'}, {date}</h5>
				<form onSubmit={formik.handleSubmit}>{console.log(2)}
				  <table ref={tableRef} className="table c-table table-hover dataTable mt-2">
					<thead>
					  <tr>
						<th>#</th>
						<th>Date</th>
						<th>Supplier</th>
						<th>Product ID</th>
						<th>Invoice ID</th>
						<th>Note</th>
						<th>Quantity</th>
						<th>Price</th>
						<th>Total</th>
						<th className="text-right">Actions</th>
					  </tr>
					</thead>
					<tbody>
					  {formik.values.rows.map((row, index) => (
					  row.id === "" ? (
						<tr key={index}>
						  <td>{index + 1}</td>
						  <td>{row.date}</td>
						  <td>{row.supplier}</td>

						  <td>
							<Select styles={orangeSelectStyles}
							  options={options}
							  isClearable
							  isSearchable
							  value={row.product_id}
							  onChange={(e) => handleProductChange(index, e)}
							  classNamePrefix="react-select"
							/>
						  </td>

						  <td>
							<Select styles={orangeSelectStyles}
							  options={row.invoices}
							  isClearable
							  isSearchable
							  value={row.invoice_id}
							  onChange={(e) => handleInvoiceChange(index, e)}
							  classNamePrefix="react-select"
							/>
							{row.product_id && row.product_id.value && (row.invoices?.length ?? 0) <= 0
							  ? <span className="text-sm text-white badge badge-danger">No Invoice</span>
							  : null}
						  </td>

						  <td>
							<input
							  type="text"
							  className="form-control"
							  name={`rows[${index}].note`}
							  defaultValue={row.note}
							  onChange={formik.handleChange}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="1"
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="0"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>

						  <td className="text-right">
						  {
							row.creating === false || !('creating' in row)
						  ?
							<button
							  type="button"
							  disabled={
								!(
								  row.supplier_id &&
								  row.product_id &&
								  row.invoice_id &&
								  row.quantity &&
								  row.price
								)
							  }
							  className="btn btn-sm btn-success"
							  onClick={() => saveSingleRow(row, index)}
							>
							  Save
							</button>
							:
							<></>
							}
							&nbsp;

							{row.id !== "" && (
							  <button
								type="button"
								className="btn btn-danger"
								onClick={() => removeRow(index)}
							  >
								Delete
							  </button>
							)}
						  </td>
						</tr>
					  ) : (
						<tr key={index}>
						  <td>{index + 1}</td>
						  <td colSpan="" className="">{row.date}</td>
						  <td colSpan="" className="">{row.supplier}</td>
						  <td colSpan="" className="">{row.product_id}</td>
						  <td colSpan="" className="">{row.invoice_id}</td>
						  <td colSpan="" className="">{row.note}</td>
						 <td>
							<input
							  type="number"
							  min="1"
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  min="0"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
							  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
							/>
						  </td>

						  <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>
						  <td colSpan="" className="text-right">
						  
						  {row.updating === false || !('updating' in row)
						  ?
						  <button
							  type="button"
							  disabled={
								!(
								  row.supplier_id &&
								  row.product_id &&
								  row.invoice_id &&
								  row.quantity &&
								  row.price
								)
							  }
							  className="btn btn-sm btn-success"
							  onClick={() => updateSingleRow(row, index)}
							>
							  Update
							</button>
							:
							<></>
						  }
						  &nbsp;
						  <button
							type="button"
							className="btn btn-sm btn-danger"
							onClick={() => removeRow(row, index)}
						  >
							Delete
						  </button>
						  </td>
						</tr>
					  )
					))}


					  {/*<tr>
						<td colSpan={10} style={{ textAlign: 'center' }}>
						  <button type="button" onClick={addRow}>
							+ Add Row
						  </button>
						</td>
					  </tr>*/}
					  {formik.values.rows.length <= 0
					  ?
					  <></>
					  :
					  <></>
					  }
					  
					</tbody>
				  </table>
				  {/*
				  <br />
				  <button type="submit">💾 Save All</button>
				  */}
				</form>
            </div>
		</div>
		:
		<></>
    }</>);
}


function List2(props) {
  const {
    currentSupplierInfo,
    products,
    date,
  } = useSelector((state) => state.suppliers);
  const currentSupplier = useSelector(
    (state) => state.suppliers.currentSupplier
  );
  const dispatch = useDispatch();

  const [loading, setLoading] = useState(false);

  const formik = useFormik({
    initialValues: { rows: [] },
    validationSchema: Yup.object({
      rows: Yup.array().of(
        Yup.object({
          product_id: Yup.mixed().required("Product required"),
          quantity: Yup.number().required("Qty required").positive(),
          price: Yup.number().required("Price required").positive(),
          invoice_id: Yup.mixed().required("Invoice ID required"),
        })
      ),
    }),
    onSubmit: async (values) => {
      try {
        await axios.post("/api/stock-products/bulk-save", values.rows);
        toast.success("All rows saved successfully!");
      } catch (err) {
        toast.error("Bulk save failed");
      }
    },
  });

  // Fetch data from API
  const fetchRows = async () => {
    try {
      setLoading(true);
      const response = await axios.post(props.invoicesReturnsApi, {
        supplier_id: currentSupplier,
        date: date,
        end_date: end_date,
      });
      const apiRows = response.data;
      if (apiRows.success) {
        let blankRow = {
          id: "",
          product_id: "",
          quantity: "",
          price: "",
          invoice_id: "",
          note: "",
          supplier_id: currentSupplier,
          date: date,
          invoices: [],
          total: "",
          customer: currentSupplierInfo?.name || "",
        };
        formik.setFieldValue("rows", [...apiRows.payload, blankRow]);
      } else {
        formik.setFieldValue("rows", []);
      }
    } catch (err) {
      console.error("Failed to load rows:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (currentSupplier && date) fetchRows();
  }, [currentSupplier, date]);

  // Handlers
  const handleProductChange = async (index, e) => {
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], product_id: e };
    try {
      const response = await axios.post(props.invoicesListApi, {
        supplier_id: currentSupplier,
        product_id: e.value,
        date: date,
      });
      const apiRows = response.data;
      updatedRows[index].invoices = apiRows.success ? apiRows.payload : [];
      updatedRows[index].invoice_id = "";
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoices:", err);
    }
  };

  const handleInvoiceChange = async (index, e) => {
    const updatedRows = [...formik.values.rows];
    updatedRows[index].invoice_id = e;
    try {
      const response = await axios.post(props.invoicesProductApi, {
        supplier_id: updatedRows[index].supplier_id,
        date: updatedRows[index].date,
        invoice_id: e,
        product_id: updatedRows[index].product_id,
      });
      const apiRows = response.data;
      if (apiRows.success && apiRows.payload?.id) {
        updatedRows[index].quantity = apiRows.payload.quantity;
        updatedRows[index].price = apiRows.payload.unit_price;
        updatedRows[index].total =
          apiRows.payload.unit_price * apiRows.payload.quantity;
      }
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoice data:", err);
    }
  };

  const saveSingleRow = async (row, index) => {
    try {
      await axios.post(props.invoicesReturnCreateApi, row);
      toast.success("Row saved successfully!");
    } catch {
      toast.error("Failed to save row");
    }
  };

  const removeRow = (row, index) => {
    const updated = formik.values.rows.filter((_, i) => i !== index);
    formik.setFieldValue("rows", updated);
  };

  const productOptions = [
    { value: "", label: "-- Select Product --" },
    ...products.map((c) => ({ value: c.id, label: c.name + (c.stock ? ' (Stock: '+c.stock+')' : '') })),
  ];

  // Define columns for DataTable
  const columns = [
    {
      name: "#",
      cell: (row, index) => index + 1,
      width: "60px",
    },
    { name: "Date", selector: (row) => row.date, sortable: true },
    { name: "Supplier", selector: (row) => row.customer, sortable: true },
    {
      name: "Product",
      cell: (row, index) =>
        row.id === "" ? (
          <Select styles={orangeSelectStyles}
            options={productOptions}
            value={row.product_id}
            onChange={(e) => handleProductChange(index, e)}
            classNamePrefix="react-select"
          />
        ) : (
          row.product_id?.label || row.product_id
        ),
    },
    {
      name: "Invoice",
      cell: (row, index) =>
        row.id === "" ? (
          <>
            <Select styles={orangeSelectStyles}
              options={row.invoices}
              value={row.invoice_id}
              onChange={(e) => handleInvoiceChange(index, e)}
              classNamePrefix="react-select"
            />
            {row.product_id && row.product_id.value && (row.invoices?.length ?? 0) <= 0 ? (
              <span className="text-danger small">No Invoice</span>
            ) : null}
          </>
        ) : (
          row.invoice_id?.label || row.invoice_id
        ),
    },
    {
      name: "Note",
      cell: (row, index) => (
        <input
          type="text"
          className="form-control"
          name={`rows[${index}].note`}
          value={row.note || ""}
          onChange={formik.handleChange}
        />
      ),
    },
    {
      name: "Qty",
      cell: (row, index) => (
        <input
          type="number"
          min="1"
          className="form-control"
          name={`rows[${index}].quantity`}
          value={row.quantity || ""}
          onChange={formik.handleChange}
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
        />
      ),
    },
    {
      name: "Price",
      cell: (row, index) => (
        <input
          type="number"
          min="0"
          className="form-control"
          name={`rows[${index}].price`}
          value={row.price || ""}
          onChange={formik.handleChange}
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
        />
      ),
    },
    {
      name: "Total",
      selector: (row) => Number(row.quantity) * Number(row.price) || 0,
    },
    {
      name: "Actions",
      right: true,
      cell: (row, index) => (
        <>
          <button
            type="button"
            className="btn btn-sm btn-success me-1"
            disabled={
              !(
                row.supplier_id &&
                row.product_id &&
                row.invoice_id &&
                row.quantity &&
                row.price
              )
            }
            onClick={() => saveSingleRow(row, index)}
          >
            {row.id ? "Update" : "Save"}
          </button>
          <button
            type="button"
            className="btn btn-sm btn-danger"
            onClick={() => removeRow(index)}
          >
            Delete
          </button>
        </>
      ),
    },
  ];

  return (
    <>
      {date ? (
        <div className="card">
          <div className="card-header">
            <h5 className="card-title">
              Supplier: {currentSupplierInfo?.name || "None"} — {date}
            </h5>
          </div>

          <div className="card-body">
            <DataTable
              columns={columns}
              data={formik.values.rows}
              progressPending={loading}
              progressComponent={<SpecTableLoading />}
              pagination
              highlightOnHover
              dense
              striped
              responsive
            />
          </div>
        </div>
      ) : null}
    </>
  );
}

function ListGrid(props) {
  const { currentSupplierInfo, products, date } = useSelector(state => state.suppliers);
  const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
  const dispatch = useDispatch();

  const tableRef = useRef(null);
  const dataTableRef = useRef(null);
  const [tableReady, setTableReady] = useState(false); // ✅ Flag to trigger datatable init only after load

  const formik = useFormik({
    initialValues: { rows: [] },
    validationSchema: Yup.object({
      rows: Yup.array().of(
        Yup.object({
          product_id: Yup.string().required("Product required"),
          quantity: Yup.number().required("Qty required").positive(),
          price: Yup.number().required("Price required").positive(),
          invoice_id: Yup.string().required("Invoice ID required"),
        })
      ),
    }),
    onSubmit: async (values) => {
      try {
        await axios.post("/api/stock-products/bulk-save", values.rows);
        alert("All rows saved successfully!");
      } catch {
        alert("Bulk save failed");
      }
    },
  });

  const fetchAndPrependRows = async () => {
    try {
      const response = await axios.post(props.invoicesReturnsApi, {
        supplier_id: currentSupplier,
        date: date,
        end_date: end_date,
      });
      const apiRows = response.data;
      if (apiRows.success === true) return apiRows.payload;
      return [];
    } catch (err) {
      console.error("Failed to load rows:", err);
      return [];
    }
  };

  // 🟩 Step 1: Fetch data and set rows
  useEffect(() => {
    let isMounted = true;
    const load = async () => {
      setTableReady(false); // reset flag while loading

      try {
        const apiRows = await fetchAndPrependRows();
        let blankRow = null;

        if (currentSupplier) {
          blankRow = {
            id: "",
            product_id: "",
            quantity: "",
            price: "",
            invoice_id: "",
            note: "",
            supplier_id: currentSupplier,
            date: date,
            invoices: [],
            total: "",
            customer: currentSupplierInfo?.name || "",
          };
        }

        const finalRows = blankRow ? [...apiRows, blankRow] : apiRows;

        if (isMounted) {
          await formik.setFieldValue("rows", finalRows, false);
          setTableReady(true); // ✅ Only set to true after data is ready
        }
      } catch (err) {
        console.error("load rows error", err);
        setTableReady(false);
      }
    };

    if (currentSupplier && date) {
      load();
    }

    return () => {
      isMounted = false;
    };
  }, [currentSupplier, date]);

  // 🟩 Step 2: Initialize DataTable *after* data is loaded and ready
  useEffect(() => {
    if (tableReady && formik.values.rows.length > 0 && tableRef.current) {
      // destroy existing instance
      if (dataTableRef.current) {
        dataTableRef.current.destroy(true);
        dataTableRef.current = null;
      }

      dataTableRef.current = $(tableRef.current).DataTable({
        destroy: true,
        dom:
          "<'row'<'col-sm-6'B><'col-sm-6'f>>" + // buttons left, search right
          "<'row'<'col-sm-12'tr>>" +
          "<'row'<'col-sm-6'i><'col-sm-6'p>>",
        buttons: [
          { extend: "copy", text: "📋 Copy" },
          { extend: "excel", text: "📊 Excel" },
          { extend: "print", text: "🖨️ Print" },
        ],
        pageLength: 10,
      });
    }

    return () => {
      if (dataTableRef.current) {
        dataTableRef.current.destroy(true);
        dataTableRef.current = null;
      }
    };
  }, [tableReady, formik.values.rows]);

  // 🟩 Rest of your handleProductChange, handleInvoiceChange, etc. remain unchanged

  return (
    <>
      {date ? (
        <div className="card">
          <div className="card-header pb-0">
            <h5 className="card-title mb-0 pb-0">
              Supplier: {currentSupplierInfo?.name || "None"}, {date}
            </h5>
            <form onSubmit={formik.handleSubmit}>
              <table
                ref={tableRef}
                className="table table-striped table-bordered"
              >
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Product</th>
                    <th>Invoice</th>
                    <th>Note</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {formik.values.rows.map((row, index) => (
                    <tr key={index}>
                      <td>{index + 1}</td>
                      <td>{row.date}</td>
                      <td>{row.customer}</td>
                      <td>{row.product_id}</td>
                      <td>{row.invoice_id}</td>
                      <td>{row.note}</td>
                      <td>{row.quantity}</td>
                      <td>{row.price}</td>
                      <td>{(Number(row.quantity) * Number(row.price)) || 0}</td>
                      <td>
                        <button
                          type="button"
                          disabled={
                            !(
                              row.supplier_id &&
                              row.product_id &&
                              row.invoice_id &&
                              row.quantity &&
                              row.price
                            )
                          }
                          className="btn btn-sm btn-success"
                          onClick={() => saveSingleRow(row, index)}
                        >
                          Save
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </form>
          </div>
        </div>
      ) : null}
    </>
  );
}

function DumpFilterBar({ suppliersListApi, noCard = false }) {
	const dispatch = useDispatch();
	const suppliers = useSelector(state => state.suppliers.suppliers);
	const loading = useSelector(state => state.suppliers.loading);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const date = useSelector(state => state.suppliers.date);
	const end_date = useSelector(state => state.suppliers.end_date);

	useEffect(() => {
		(async () => {
			try {
				const res = await axios.get(suppliersListApi);
				if (res.data.success) dispatch(setSuppliers(res.data.payload));
			} catch (err) { console.error('Failed to load suppliers', err); }
		})();
	}, [suppliersListApi, dispatch]);

	const options = suppliers.map(c => ({ value: c.id, label: c.name }));
	const handleChange = (selected) => {
		if (selected) {
			const info = suppliers.find(c => c.id === selected.value);
			dispatch(resetSuppliersState());
			dispatch(setCurrentSupplierInfo(info));
			dispatch(setCurrentSupplier(selected.value));
		} else {
			dispatch(resetSuppliersState());
			dispatch(setCurrentSupplier(""));
		}
	};

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
			backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : state.isFocused ? 'rgb(234, 88, 12)' : '#334155',
			fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',
		}),
	};
	const dateBoxStyle = {display:'inline-flex',alignItems:'center',background:'#f8fafc',border:'1.5px solid #e2e8f0',borderRadius:'12px',overflow:'hidden',height:h};

	if (noCard) return (
		<div style={{padding:'16px 20px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'14px',alignItems:'end'}}>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Supplier</label>
					<Select styles={{control:(b,s)=>({...b,minHeight:'40px',height:'40px',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e2e8f0',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.1)':'0 1px 3px rgba(0,0,0,0.05)','&:hover':{borderColor:'rgb(234, 88, 12)'},background:'#fff',cursor:'pointer'}),valueContainer:(b)=>({...b,height:'40px',padding:'0 14px'}),indicatorsContainer:(b)=>({...b,height:'40px'}),clearIndicator:(b)=>({...b,padding:'0 4px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),dropdownIndicator:(b)=>({...b,padding:'0 10px 0 2px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),indicatorSeparator:()=>({display:'none'}),menuPortal:(b)=>({...b,zIndex:9999}),option:(b,s)=>({...b,fontSize:'13px',fontWeight:'500',padding:'9px 14px',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#FFF5ED':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#374151',cursor:'pointer'}),singleValue:(b)=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),placeholder:(b)=>({...b,fontSize:'13px',color:'#94a3b8'})}} options={options} value={options.find(o => o.value === currentSupplier) || null} isLoading={loading} isClearable isSearchable onChange={handleChange} classNamePrefix="react-select" placeholder="Select Supplier" menuPortalTarget={document.body} />
				</div>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Date Range</label>
					<DateRangePicker fromDate={date} toDate={end_date} onFromChange={val => dispatch(setDate(val))} onToChange={val => dispatch(setEndDate(val))} />
				</div>
			</div>
	);
	return (
		<div style={{borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',marginBottom:'16px'}}>
			<div style={{padding:'16px 20px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'14px',alignItems:'end',background:'linear-gradient(to bottom,#fafbfc,#fff)',borderRadius:'12px'}}>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Supplier</label>
					<Select styles={{control:(b,s)=>({...b,minHeight:'40px',height:'40px',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e2e8f0',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.1)':'0 1px 3px rgba(0,0,0,0.05)','&:hover':{borderColor:'rgb(234, 88, 12)'},background:'#fff',cursor:'pointer'}),valueContainer:(b)=>({...b,height:'40px',padding:'0 14px'}),indicatorsContainer:(b)=>({...b,height:'40px'}),clearIndicator:(b)=>({...b,padding:'0 4px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),dropdownIndicator:(b)=>({...b,padding:'0 10px 0 2px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),indicatorSeparator:()=>({display:'none'}),menuPortal:(b)=>({...b,zIndex:9999}),option:(b,s)=>({...b,fontSize:'13px',fontWeight:'500',padding:'9px 14px',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#FFF5ED':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#374151',cursor:'pointer'}),singleValue:(b)=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),placeholder:(b)=>({...b,fontSize:'13px',color:'#94a3b8'})}} options={options} value={options.find(o => o.value === currentSupplier) || null} isLoading={loading} isClearable isSearchable onChange={handleChange} classNamePrefix="react-select" placeholder="Select Supplier" menuPortalTarget={document.body} />
				</div>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Date Range</label>
					<DateRangePicker fromDate={date} toDate={end_date} onFromChange={val => dispatch(setDate(val))} onToChange={val => dispatch(setEndDate(val))} />
				</div>
			</div>
		</div>
	);
}

// ─── Supplier Stock Table (Dump Flow) ───
function SupplierStockTable(props) {
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const currentSupplierInfo = useSelector(state => state.suppliers.currentSupplierInfo);
	const { date, end_date, refreshCount } = useSelector(state => state.suppliers);
	const dispatch = useDispatch();
	const { notifySuccess, notifyError } = useToast();
	const [products, setLocalProducts] = useState([]);
	const [loading, setLoading] = useState(false);
	const [dumpModal, setDumpModal] = useState({ show: false, item: null });
	const [dumpNote, setDumpNote] = useState('');
	const [dumpQty, setDumpQty] = useState('');
	const [submitting, setSubmitting] = useState(false);
	const currency = props.currency || '£';

	useEffect(() => {
		if (!props.supplierProductsApi) { setLocalProducts([]); return; }
		const fetch = async () => {
			setLoading(true);
			try {
				const res = await axios.post(props.supplierProductsApi, { ...(currentSupplier ? { supplier_id: currentSupplier } : {}), from_date: date, to_date: end_date });
				const data = res.data.payload || [];
				if (res.data.success) { setLocalProducts(data); }
			} catch (e) { console.error(e); }
			finally { setLoading(false); }
		};
		fetch();
	}, [currentSupplier, refreshCount, date, end_date]);

	// Mirror product count to parent in a separate effect so we never call the
	// parent's setState during this component's render or another setState's updater.
	useEffect(() => {
		if (props.onCount) props.onCount(products.length);
	}, [products.length]);

	const openDumpModal = (item) => { setDumpModal({ show: true, item }); setDumpNote(''); setDumpQty(1); };
	const closeDumpModal = () => { setDumpModal({ show: false, item: null }); setDumpNote(''); setDumpQty(''); };

	const handleDump = async () => {
		if (!dumpNote.trim()) { notifyError('Note is required — please explain why dumping'); return; }
		if (!dumpQty || Number(dumpQty) <= 0) { notifyError('Enter valid quantity'); return; }
		const item = dumpModal.item;
		if (Number(dumpQty) > item.stock) { notifyError(`Max ${item.stock} available`); return; }
		setSubmitting(true);
		try {
			const res = await axios.post(props.invoicesReturnCreateApi, {
				supplier_id: item.supplier_id || currentSupplier,
				product_id: { value: item.product_id || item.id },
				invoice_id: { invoice_id: item.invoice_id || 0, id: item.id, ref_id: item.id },
				quantity: Number(dumpQty),
				price: item.unit_price || 0,
				note: dumpNote,
				date: date || '2000-01-01',
			});
			if (res.data.success !== false) {
				notifySuccess(`Dumped ${dumpQty} × ${item.product_name || item.name}`);
				closeDumpModal();
				window.dispatchEvent(new CustomEvent('stock-updated'));
				if (props.onSuccess) props.onSuccess();
				// Update local state — parent count is mirrored via the dedicated
				// useEffect on products.length, so no manual onCount call here.
				const dumped = Number(dumpQty);
				setLocalProducts(prev => prev
					.map(p => p.id === item.id ? { ...p, stock: p.stock - dumped } : p)
					.filter(p => p.stock > 0)
				);
			} else { notifyError(res.data.payload || res.data.message || 'Failed'); }
		} catch (e) { notifyError(e.response?.data?.payload || e.response?.data?.message || 'Failed to dump'); }
		finally { setSubmitting(false); }
	};

	const qtyExceeds = dumpModal.item && Number(dumpQty) > dumpModal.item.stock;
	const canSubmit = !submitting && !qtyExceeds && Number(dumpQty) > 0 && dumpNote.trim();

	const { noCard } = props;
	const isMobileView = window.innerWidth <= 767;
		return (
		<div style={noCard ? {overflow:'visible'} : {background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden',marginBottom:'16px'}}>

			{/* Table — Desktop */}
			{!isMobileView && (
			<div style={{overflowX:'auto'}}>
				<table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
					<thead><tr style={{background:'#fafbfc'}}>
						{['Invoice','Date','Supplier','Product','Remark','Purchased','Available','Price','Action'].map(h => (
							<th key={h} style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'2px solid #f1f5f9',textAlign:['Purchased','Available','Price'].includes(h)?'center':h==='Action'?'center':'left'}}>{h}</th>
						))}
					</tr></thead>
					<tbody>
						{loading ? (
							<tr><td colSpan={9} style={{padding:0}}><SpecTableLoading label="Loading dumps…" /></td></tr>
						) : products.length === 0 ? (
							<tr><td colSpan={9} style={{padding:0}}><SpecTableEmpty onClear={clearDumpFilters} /></td></tr>
						) : products.map(item => (
							<tr key={item.id} style={{borderBottom:'1px solid #f8fafc'}}>
								<td style={{padding:'12px 14px'}}><span style={{color:'rgb(234, 88, 12)',fontWeight:'700',fontSize:'12px'}}>#{item.invoice_id}</span></td>
								<td style={{padding:'12px 14px',color:'#64748b',fontSize:'12px',whiteSpace:'nowrap'}}>{item.date || '—'}</td>
								<td style={{padding:'12px 14px',color:'#1e293b',fontSize:'12px'}}>{item.supplier_name || '—'}</td>
								<td style={{padding:'12px 14px',fontWeight:'600',color:'#1e293b'}}>{item.product_name || item.name}</td>
								<td style={{padding:'12px 14px',color:'#94a3b8',fontStyle:'italic',fontSize:'12px'}}>{item.remarks || '—'}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'600'}}>{item.quantity}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'700',color:'#16a34a',fontSize:'15px'}}>{item.stock}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'600',whiteSpace:'nowrap'}}>{currency} {Number(item.unit_price||0).toFixed(2)}</td>
								<td style={{padding:'12px 14px',textAlign:'center'}}>
									<button onClick={() => openDumpModal(item)} style={{height:'32px',padding:'0 16px',borderRadius:'8px',border:'none',background:'#dc2626',color:'#fff',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'5px'}}>
										<i className="fa fa-trash" style={{fontSize:'11px'}}></i> Dump
									</button>
								</td>
							</tr>
						))}
					</tbody>
				</table>
			</div>
			)}

			{/* Mobile cards */}
			{isMobileView && (
			<div style={{padding:'12px 0 4px'}}>
				{/* Tab bar — always visible on this tab. It previously lived only inside the
				    empty-state card, so it vanished while loading or when dumpable products
				    existed. Render it once at the top so the tabs never disappear. */}
				{props.mobileTabsBar}
				{loading ? (
					<SpecTableLoading label="Loading dumps…" />
				) : products.length === 0 ? (
					<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
						<SpecTableEmpty onClear={clearDumpFilters} />
					</div>
				) : products.map(item => (
					<div key={item.id} style={{display:'flex',marginBottom:'10px',borderRadius:'14px',border:'1px solid #eaecf2',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
						<div style={{width:'4px',flexShrink:0,background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}/>
						<div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
							<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px',marginBottom:'8px'}}>
								<div style={{minWidth:0}}>
									<div style={{fontSize:'11px',color:'rgb(234, 88, 12)',fontWeight:'700',marginBottom:'2px'}}>#{item.invoice_id}{item.date ? ' · '+item.date : ''}</div>
									<div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',lineHeight:1.3,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{item.product_name || item.name}</div>
									{item.supplier_name && <div style={{fontSize:'11px',color:'#64748b',fontWeight:'600',marginTop:'1px'}}>{item.supplier_name}</div>}
								</div>
								<button onClick={() => openDumpModal(item)} style={{flexShrink:0,height:'32px',padding:'0 12px',borderRadius:'8px',border:'none',background:'#dc2626',color:'#fff',fontSize:'11px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'4px',whiteSpace:'nowrap'}}>
									<i className="fa fa-trash" style={{fontSize:'10px'}}></i> Dump
								</button>
							</div>
							<div style={{display:'flex',gap:'6px',flexWrap:'wrap'}}>
								<span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>Bought: {item.quantity}</span>
								<span style={{fontSize:'11px',fontWeight:'700',color:'#16a34a',background:'#f0fdf4',border:'1px solid #86efac',borderRadius:'6px',padding:'2px 8px'}}>Stock: {item.stock}</span>
								<span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>{currency} {Number(item.unit_price||0).toFixed(2)}</span>
							</div>
							{item.remarks && <div style={{marginTop:'6px',fontSize:'11px',color:'#94a3b8',fontStyle:'italic'}}>{item.remarks}</div>}
						</div>
					</div>
				))}
			</div>
			)}

			{/* Dump Modal */}
			{dumpModal.show && dumpModal.item && (<>
				<div style={{position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.4)',zIndex:99998}} onClick={closeDumpModal}></div>
				<div style={{position:'fixed',top:'50%',left:'50%',transform:'translate(-50%,-50%)',background:'#fff',borderRadius:'16px',width:'420px',maxWidth:'90vw',zIndex:99999,boxShadow:'0 20px 60px rgba(0,0,0,0.2)',overflow:'hidden'}}>
					<div style={{padding:'18px 22px',borderBottom:'1px solid #f1f5f9',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
						<div><div style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Dump Product</div><div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px'}}>{dumpModal.item.name}</div></div>
						<button onClick={closeDumpModal} style={{width:'32px',height:'32px',borderRadius:'8px',border:'1px solid #e2e8f0',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',color:'#64748b',fontSize:'16px'}}>×</button>
					</div>
					<div style={{padding:'18px 22px'}}>
						<div style={{display:'flex',gap:'10px',marginBottom:'16px',...(isMobileView ? {flexWrap:'wrap'} : {})}}>
							<div style={{flex:1,...(isMobileView ? {minWidth:0} : {}),background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
								<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Invoice</div>
								<div style={{fontSize:'15px',fontWeight:'800',color:'rgb(234, 88, 12)',marginTop:'2px'}}>#{dumpModal.item.invoice_id}</div>
							</div>
							<div style={{flex:1,...(isMobileView ? {minWidth:0} : {}),background:'#f0fdf4',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
								<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Available</div>
								<div style={{fontSize:'15px',fontWeight:'800',color:'#16a34a',marginTop:'2px'}}>{dumpModal.item.stock}</div>
							</div>
							<div style={{flex:1,...(isMobileView ? {minWidth:0} : {}),background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
								<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Unit Price</div>
								<div style={{fontSize:'15px',fontWeight:'800',color:'#1e293b',marginTop:'2px'}}>{currency} {Number(dumpModal.item.unit_price||0).toFixed(2)}</div>
							</div>
						</div>
						<div style={{marginBottom:'14px'}}>
							<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Quantity to Dump <span style={{color:'#dc2626'}}>*</span> <span style={{fontSize:'11px',color:'#dc2626',fontWeight:'700',textTransform:'none',letterSpacing:'0',marginLeft:'4px'}}>max {dumpModal.item.stock}</span></label>
							<input type="number" min="1" max={dumpModal.item.stock} value={dumpQty} onChange={e => setDumpQty(e.target.value)}
								style={{width:'100%',height:'42px',borderRadius:'10px',border: qtyExceeds ? '2px solid #dc2626' : '1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'700',textAlign:'center',outline:'none',color:'#1e293b',boxSizing:'border-box',background: qtyExceeds ? '#fef2f2' : '#fff'}}
								onFocus={e => { if(!qtyExceeds) e.target.style.borderColor='rgb(234, 88, 12)'; }} onBlur={e => { if(!qtyExceeds) e.target.style.borderColor='#e2e8f0'; }}
							/>
							<div style={{marginTop:'6px',fontSize:'12px',color: qtyExceeds ? '#dc2626' : '#64748b',fontWeight:'600',display:'flex',alignItems:'center',gap:'5px'}}>
								{qtyExceeds
									? <><i className="fa fa-exclamation-triangle" style={{fontSize:'11px'}}></i>You purchased {dumpModal.item.quantity} and have {dumpModal.item.stock} available — cannot dump {dumpQty}</>
									: <><i className="fa fa-info-circle" style={{fontSize:'11px',color:'#94a3b8'}}></i>You purchased {dumpModal.item.quantity} and have {dumpModal.item.stock} available</>
								}
							</div>
						</div>
						<div style={{marginBottom:'14px'}}>
							<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Reason for Dump <span style={{color:'#dc2626'}}>*</span></label>
							<textarea value={dumpNote} onChange={e => setDumpNote(e.target.value)} placeholder="e.g. Expired, damaged, rotten..." rows="3"
								style={{width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',padding:'10px 14px',outline:'none',color:'#1e293b',boxSizing:'border-box',resize:'none'}}
								onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
						</div>
					</div>
					<div style={{padding:'14px 22px',borderTop:'1px solid #f1f5f9',display:'flex',gap:'10px'}}>
						<button onClick={closeDumpModal} style={{flex:1,height:'42px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer'}}>Cancel</button>
						<button onClick={handleDump} disabled={!canSubmit} style={{flex:1,height:'42px',borderRadius:'10px',border:'none',background: canSubmit ? '#dc2626' : '#e2e8f0',color: canSubmit ? '#fff' : '#94a3b8',fontSize:'13px',fontWeight:'700',cursor: canSubmit ? 'pointer' : 'not-allowed',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}>
							{submitting ? <><i className="fa fa-spinner fa-spin"></i> Processing...</> : <><i className="fa fa-trash"></i> Confirm Dump</>}
						</button>
					</div>
				</div>
			</>)}
		</div>
	);
}

export default function DumpsApp(props) {
	const dispatch = useDispatch();
	const [showHistory, setShowHistory] = useState(true);
	const [returnTotal, setReturnTotal] = useState(0);
	const [productCount, setProductCount] = useState(0);
	const [totals, setTotals] = useState({ all: 0, paid: 0, pending: 0, count: 0 });
	const [historyRefreshKey, setHistoryRefreshKey] = useState(0);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);

	useEffect(() => {
		(async () => {
			try {
				const res = await axios.get(props.productsListApi);
				if (res.data.success) dispatch(setProducts(res.data.payload));
			} catch (err) {}
		})();
	}, []);

	useEffect(() => {
		const handleTabActivated = (e) => {
			if (e.detail && e.detail.tab === 'dump') {
				dispatch(setRefreshCount(Date.now()));
			}
		};
		window.addEventListener('sc-tab-activated', handleTabActivated);
		return () => window.removeEventListener('sc-tab-activated', handleTabActivated);
	}, [dispatch]);

    const tabsMarkup = (
        <div style={{ padding: '8px 20px 0', display: 'flex', gap: '6px', borderBottom: '1px solid #f1f5f9', alignItems: 'flex-end', overflow: 'hidden' }}>
            <button type="button" onClick={() => setShowHistory(true)} style={{ padding: '6px 12px 8px', border: 'none', outline: 'none', background: 'transparent', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '6px', borderBottom: showHistory ? '2px solid rgb(234, 88, 12)' : '2px solid transparent',  transition: 'all 0.15s', boxShadow: 'none', whiteSpace: 'nowrap', flexShrink: 0 }}>
                <i className="fa fa-history" style={{ fontSize: '12px', color: showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}></i>
                <span style={{ fontSize: '12px', fontWeight: '600', color: showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}>Return History</span>
                <span style={{ fontSize: '10px', fontWeight: '700', color: showHistory ? '#fff' : '#94a3b8', background: showHistory ? 'rgb(234, 88, 12)' : '#f1f5f9', padding: '2px 7px', borderRadius: '10px', minWidth: '16px', textAlign: 'center' }}>{totals ? totals.count || 0 : 0}</span>
            </button>
            <button type="button" onClick={() => setShowHistory(false)} style={{ padding: '6px 12px 8px', border: 'none', outline: 'none', background: 'transparent', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '6px', borderBottom: !showHistory ? '2px solid rgb(234, 88, 12)' : '2px solid transparent',  transition: 'all 0.15s', boxShadow: 'none', whiteSpace: 'nowrap', flexShrink: 0 }}>
                <i className="fa fa-plus-circle" style={{ fontSize: '12px', color: !showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}></i>
                <span style={{ fontSize: '12px', fontWeight: '600', color: !showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}>Add Dump</span>
                <span style={{ fontSize: '10px', fontWeight: '700', color: !showHistory ? '#fff' : '#94a3b8', background: !showHistory ? 'rgb(234, 88, 12)' : '#f1f5f9', padding: '2px 7px', borderRadius: '10px', minWidth: '16px', textAlign: 'center' }}>{productCount}</span>
            </button>
        </div>
    );

    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
	<div style={props.noHeader ? {} : { background: '#fff', borderRadius: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', border: '1px solid #f1f5f9', overflow: 'visible', marginBottom: '16px' }}>
		{/* Header */}
		{props.noHeader ? null : (
			<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 24px 14px' }}>
				<div style={{ width: '44px', height: '41px', borderRadius: '14px', background: 'rgb(234, 88, 12)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 3px 12px rgba(234,88,12,0.25)', flexShrink: 0 }}>
					<i className="fa fa-trash" style={{ color: '#fff', fontSize: '20px' }}></i>
				</div>
				<div>
					<h1 style={{ fontSize: '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Dump</h1>
					<p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Record product dump entries</p>
				</div>
			</div>
		)}
		{/* Content — filters always visible, tabs below */}
		<ReturnHistoryApp noCard hideTable={!showHistory} type="dump" returnsApi={props.invoicesReturnsApi} entitiesApi={props.suppliersListApi} currency={props.currency} onBack={() => setShowHistory(false)} onTotal={setReturnTotal} onTotals={setTotals} refreshKey={historyRefreshKey} printUrl={showHistory ? props.printUrl : props.dumpablePrintUrl} excelUrl={showHistory ? props.excelUrl : props.dumpableExcelUrl}
			onEntityChange={v => { dispatch(setCurrentSupplier(v?.value || '')); dispatch(setCurrentSupplierInfo(v ? { id: v.value, name: v.label } : {})); }}
			onDateChange={(from,to) => { dispatch(setDate(from)); dispatch(setEndDate(to)); }}
			tabsBar={tabsMarkup} />
		<div style={{ display: showHistory ? 'none' : 'block' }}>
			<SupplierStockTable noCard {...props} mobileTabsBar={tabsMarkup} dumpableExcelUrl={props.dumpableExcelUrl} onSuccess={() => { setShowHistory(true); setHistoryRefreshKey(k => k + 1); }} onCount={setProductCount} />
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('dumps-return-app')) {
    const id = "dumps-return-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<DumpsApp {...props} />
		</Provider>
    );
}
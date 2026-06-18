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

const {setSuppliers, setCurrentSupplier,setRefreshCount, setSuppliersLoading, triggerPaymentRefresh, setCurrentSupplierInfo,setProducts,
	setPaymentMode, setAmount, setDate, setEndDate, setNote, setInvoices, resetSuppliersState,setTotalChecked,
	setPaymentDoable, toggleInvoice, resetAccountPayments,resetPaymentForm, resetInvoices, setAccountPayments, setOnAccount} = suppliersSlice.actions;

const store = configureStore({
    reducer: { suppliers: suppliersSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

// Clear-filters handler for the empty state — clears supplier + date so ALL
// records (the full date range present in the DB) are fetched and shown.
const clearReturnFilters = () => {
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
	const {paymentMode,amount,date,note,accountPayments,onAccount} = useSelector(state => state.suppliers);
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
        },
        validationSchema: Yup.object({
            /*payment_mode: Yup.string().required('Payment mode is required'),
            amount: Yup.number()
                .typeError('Amount must be a number')
                .positive('Amount must be positive')
                .required('Amount is required'),*/
            date: Yup.date().required('Date is required'),
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


// list.
function List(props) {
	const {currentSupplierInfo,products, refreshCount, suppliers, totalChecked, invoices,amount,paymentMode,date,end_date,note,paymentDoable,onAccount} = useSelector(state => state.suppliers);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const refreshPayments = useSelector(state => state.suppliers.refreshPayments);
	const [data, setData] = useState([]);
	const [refreshGrid, setRefreshGrid] = useState(0);
	const [allSet, setAllSet] = useState(false);
	const [saving, setSaving] = useState(0);
	const [balance, setBalance] = useState(0);
	const [invoicesAmount, setInvoicesAmount] = useState(0);
	const [selectedRows, setSelectedRows] = useState([]);
	const isMobile = window.innerWidth <= 767;

	const { notifySuccess, notifyError } = useToast();
	
	const tableRef = useRef(null);
	const dataTableRef = useRef(null);
	
	const dispatch = useDispatch();
	
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

			{false ? (null) : (null
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
      cell: (row, index) => {
        const [open, setOpen] = useState(false);
        const [pos, setPos] = useState({top:0,left:0});
        const btnRef = useRef(null);
        const canSave = row.supplier_id && row.product_id && row.invoice_id && row.quantity && row.price;
        const handleOpen = () => {
          if(btnRef.current){
            const rect = btnRef.current.getBoundingClientRect();
            setPos({top: rect.bottom + 4, left: rect.right - 130});
          }
          setOpen(!open);
        };
        return (
          <div style={{position:'relative'}}>
            <button ref={btnRef} type="button" onClick={handleOpen}
              style={{border:'1.5px solid #e2e8f0',borderRadius:'8px',background:'#fff',color:'#475569',
                fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s',
                display:'inline-flex',alignItems:'center',gap:'5px',outline:'none'}}>
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
                    cursor: canSave ? 'pointer' : 'default',transition:'all 0.12s',display:'flex',alignItems:'center',gap:'8px'}}
                    onClick={() => { if(canSave){ saveSingleRow(row, index); setOpen(false); } }}
                    onMouseOver={(e) => { if(canSave) e.currentTarget.style.background='#FFF5ED'; }}
                    onMouseOut={(e) => e.currentTarget.style.background='transparent'}>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save
                  </div>
                ) : (
                  <>
                    <div style={{padding:'8px 16px',fontSize:'13px',fontWeight:'600',color: canSave ? '#1e293b' : '#cbd5e1',
                      cursor: canSave ? 'pointer' : 'default',transition:'all 0.12s',display:'flex',alignItems:'center',gap:'8px'}}
                      onClick={() => { if(canSave){ updateSingleRow(row, index); setOpen(false); } }}
                      onMouseOver={(e) => { if(canSave) e.currentTarget.style.background='#FFF5ED'; }}
                      onMouseOut={(e) => e.currentTarget.style.background='transparent'}>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      Update
                    </div>
                    <div style={{padding:'8px 16px',fontSize:'13px',fontWeight:'600',color:'#1e293b',
                      cursor:'pointer',transition:'all 0.12s',display:'flex',alignItems:'center',gap:'8px'}}
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
      },
      width: "130px",
    },
  ];
	
	//return (<>{console.log('----')}{console.log(formik.values.rows)}</>)
	return (
	<>{date != ""
		?
			<>
				<form onSubmit={formik.handleSubmit} style={{display:'none'}}>
				{
					<>
					{false && currentSupplier ? (
					isMobile ? (
					<div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',background:'#fff',overflow:'visible',marginBottom:'16px',position:'relative',zIndex:10}}>
						<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7'}}>
							<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Add Return</span>
						</div>
						<div style={{padding:'20px 22px'}}>
						{formik.values.rows.map((row, index) => {
						return row.id === "" ? (
						<div key={`empty_${index}`}>
							{/* PRODUCT */}
							<div style={{marginBottom:'16px'}}>
								<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-cube" style={{color:'rgb(234, 88, 12)',fontSize:'12px'}}></i> Product</label>
								<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'48px',height:'48px',borderRadius:'12px',border:state.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #E5E7EB',background:'#fff'}),valueContainer:(base)=>({...base,height:'48px',padding:'0 14px'}),indicatorsContainer:(base)=>({...base,display:'none'}),placeholder:(base)=>({...base,fontSize:'14px',color:'#9CA3AF'}),menuPortal:(base)=>({...base,zIndex:9999})}} options={options} isClearable isSearchable value={row.product_id} onChange={(e) => handleProductChange(index, e)} classNamePrefix="react-select" menuPortalTarget={document.body} placeholder="Select product..." />
							</div>
							{/* INVOICE */}
							<div style={{marginBottom:'16px'}}>
								<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}>
									<i className="fa fa-file-text-o" style={{color:'rgb(234, 88, 12)',fontSize:'12px'}}></i> Invoice
																	</label>
								<div style={{position:'relative'}}>
									<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'48px',height:'48px',borderRadius:'12px',border:state.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #E5E7EB',background:'#fff'}),valueContainer:(base)=>({...base,height:'48px',padding:'0 14px'}),indicatorsContainer:(base)=>({...base,display:'none'}),placeholder:(base)=>({...base,fontSize:'14px',color:'#9CA3AF'}),menuPortal:(base)=>({...base,zIndex:9999})}} options={row.invoices} isClearable isSearchable value={row.invoice_id} onChange={(e) => handleInvoiceChange(index, e)} classNamePrefix="react-select" menuPortalTarget={document.body} placeholder="Select invoice..." />
									<span style={{position:'absolute',right:'14px',top:'50%',transform:'translateY(-50%)',pointerEvents:'none',color:'#9CA3AF'}}><i className="fa fa-file-o" style={{fontSize:'15px'}}></i></span>
								</div>
							</div>
							{/* QUANTITY + PRICE */}
							<div style={{display:'flex',gap:'10px',marginBottom:'16px'}}>
								<div style={{flex:1}}>
									<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-hashtag" style={{color:'rgb(234, 88, 12)',fontSize:'12px'}}></i> Quantity</label>
									<input type="number" name={`rows[${index}].quantity`} value={row.quantity} min="0" placeholder="0" onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }} onChange={formik.handleChange} style={{width:'100%',height:'56px',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'22px',fontWeight:'700',color:'#111827',padding:'0 14px',outline:'none',background:'#fff',textAlign:'center',boxSizing:'border-box'}} onFocus={e=>e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
								</div>
								<div style={{flex:1}}>
									<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-usd" style={{color:'rgb(234, 88, 12)',fontSize:'12px'}}></i> Price</label>
									<input type="number" name={`rows[${index}].price`} value={row.price} min="0" placeholder="0.00" onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }} onChange={formik.handleChange} style={{width:'100%',height:'56px',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'22px',fontWeight:'700',color:'#111827',padding:'0 14px',outline:'none',background:'#fff',textAlign:'center',boxSizing:'border-box'}} onFocus={e=>e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
								</div>
							</div>
							{/* NOTE */}
							<div style={{marginBottom:'16px'}}>
								<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-sticky-note-o" style={{color:'rgb(234, 88, 12)',fontSize:'11px'}}></i> Note</label>
								<textarea name={`rows[${index}].note`} defaultValue={row.note} placeholder="Optional additional details" onChange={formik.handleChange} style={{width:'100%',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'14px',padding:'12px 14px',outline:'none',background:'#fff',resize:'none',height:'80px',boxSizing:'border-box',lineHeight:'1.5'}} onFocus={e=>e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
							</div>
							{/* TOTAL + SAVE */}
							<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',paddingTop:'16px',borderTop:'1px solid #F0F4F8'}}>
								<div>
									<div style={{fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'4px'}}>Total Amount</div>
									<div style={{fontSize:'24px',fontWeight:'800',color:'#111827'}}>{((Number(row.quantity)*Number(row.price))||0).toLocaleString('en-GB',{minimumFractionDigits:2})}</div>
								</div>
								{(row.creating===false||!('creating' in row)) && (
								<button type="button" disabled={!(row.supplier_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)} onClick={() => saveSingleRow(row, index)} style={{height:'50px',padding:'0 28px',borderRadius:'50px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'15px',fontWeight:'700',cursor:(row.supplier_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'pointer':'not-allowed',display:'flex',alignItems:'center',gap:'8px',opacity:(row.supplier_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?1:0.55,boxShadow:(row.supplier_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'0 4px 16px rgba(234,88,12,0.4)':'none'}}>
									<i className="fa fa-check-circle" style={{fontSize:'18px'}}></i> Save Return
								</button>
								)}
							</div>
						</div>
						) : (
						<React.Fragment key={`skip_${index}`} />
						);
						})}
						</div>
					</div>
					) : (
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
								{(row.invoices?.length ?? 0) <= 0
								  ? null
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
								  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
									style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
					)
					) : <></>}
					
					<div style={{
						borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',
						boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
					}}>
						<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
							<div>
								<div style={{fontSize:isMobile?'18px':'15px',fontWeight:'700',color:isMobile?'#111827':'#1e293b',letterSpacing:isMobile?'-0.3px':'normal'}}>Returns List</div>
								{isMobile && <div style={{fontSize:'12px',color:'#9CA3AF',fontWeight:'400',marginTop:'2px'}}>Recent transaction history</div>}
							</div>
							{formik.values.rows.filter(r => r.id !== "").length > 0 && (
								<span style={{fontSize:'11.5px',fontWeight:'700',color:'rgb(234, 88, 12)',background:'#FFF5ED',padding:'3px 10px',borderRadius:'6px'}}>
									{formik.values.rows.filter(r => r.id !== "").length} records
								</span>
							)}
						</div>
						<style>{`.cr-scroll-area{scrollbar-width:none;}.cr-scroll-area::-webkit-scrollbar{display:none;}.cr-range-scroll{-webkit-appearance:none;width:100%;height:6px;border-radius:10px;background:#f0f0f0;outline:none;cursor:pointer;}.cr-range-scroll::-webkit-slider-thumb{-webkit-appearance:none;width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;}.cr-range-scroll::-moz-range-thumb{width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;border:none;}`}</style>
						<div className={isMobile ? "cr-scroll-area" : ""} style={{width:"100%",overflowX:"auto",WebkitOverflowScrolling:"touch"}}>
						  <div style={{minWidth: isMobile ? "1450px" : "100%"}}>
						  <DataTable
							columns={columns}
							data={formik.values.rows.filter(r => r.id !== "")}
							highlightOnHover
							pagination
							paginationPerPage={10}
							paginationRowsPerPageOptions={[5, 10, 20, 50]}
							responsive
							expandableRows
							expandableRowsComponent={({ data }) => (
								<div style={{padding:'12px 16px',background:'#F9FAFB',borderTop:'1px solid #F0F0F0'}}>
								  {[['SUPPLIER', data.supplier, false],['INVOICE', data.invoice_id, false],['PRICE', data.price, true]].map(([label,val,isPrice])=>(
									<div key={label} style={{display:'flex',alignItems:'center',gap:'8px',marginBottom:'6px'}}>
									  <span style={{fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.6px',textTransform:'uppercase',minWidth:'70px'}}>{label}:</span>
									  <span style={{fontSize:'13px',fontWeight:'700',color:isPrice?'rgb(234, 88, 12)':'#111827'}}>{val}</span>
									</div>
								  ))}
								</div>
							)}
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
									transition:'all 0.12s',
									'&:hover': { backgroundColor:'#FEFAF6' },
								}},
								cells: { style: { padding:'8px 12px' }},
								pagination: { style: { borderTop:'1px solid #eef2f7',fontSize:'13px',minHeight:'48px' }},
							}}
							noDataComponent={
								<SpecTableEmpty onClear={clearReturnFilters} />
							}
						  />
						  </div>
						</div>
						{isMobile && (
							<div style={{padding:'0 12px 10px'}}>
								<input type="range" min="0" max="100" defaultValue="0" className="cr-range-scroll"
									onChange={(e) => {
										const el = document.querySelector('.cr-scroll-area');
										if (!el) return;
										const maxScroll = el.scrollWidth - el.clientWidth;
										el.scrollLeft = (e.target.value / 100) * maxScroll;
									}}
								/>
							</div>
						)}
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
							{(row.invoices?.length ?? 0) <= 0
							  ? null
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
							  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
								style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
							  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
							style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
						<tr key={"empty_"+index}>
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
							{(row.invoices?.length ?? 0) <= 0
							  ? null
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
							  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
								style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
							  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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
							style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
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

function ListGrid(props) {
  const dispatch = useDispatch();
  const { currentSupplierInfo, products, refreshCount } = useSelector(
    (state) => state.suppliers
  );
  const currentSupplier = useSelector((state) => state.suppliers.currentSupplier);
  const date = useSelector((state) => state.suppliers.date);
  const end_date = useSelector((state) => state.suppliers.end_date);
  const { notifySuccess, notifyError } = useToast();

  const [filterText, setFilterText] = useState(""); // 🔍 search text

  const formik = useFormik({
    initialValues: { rows: [] },
    validationSchema: Yup.object({
      rows: Yup.array().of(
        Yup.object({
          product_id: Yup.mixed().required("Product required"),
          quantity: Yup.number().required("Qty required").positive(),
          price: Yup.number().required("Price required").positive(),
          invoice_id: Yup.mixed().required("Invoice required"),
        })
      ),
    }),
    onSubmit: async (values) => {
      try {
        await axios.post("/api/stock-products/bulk-save", values.rows);
        notifySuccess("All rows saved successfully!");
      } catch (err) {
        notifyError("Bulk save failed");
      }
    },
  });

  const updateFormikRows = (index, jsonData) => {
    const updated = [...formik.values.rows];
    updated[index] = { ...updated[index], ...jsonData };
    formik.setFieldValue("rows", updated);
  };

  const fetchAndPrependRows = async () => {
    try {
      const response = await axios.post(props.invoicesReturnsApi, {
        supplier_id: currentSupplier,
        date,
        end_date,
      });
      if (response.data?.success === true) return response.data.payload || [];
      return [];
    } catch (err) {
      console.error("Failed to load rows:", err);
      return [];
    }
  };

  const handleProductChange = async (index, option) => {
    const productValue = option ? option.value : "";
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], product_id: productValue };

    try {
      const response = await axios.post(props.invoicesListApi, {
        supplier_id: currentSupplier,
        product_id: productValue,
        date: updatedRows[index].date ?? date,
      });

      if (response.data?.success === true) {
        updatedRows[index] = { ...updatedRows[index], invoices: response.data.payload };
      } else {
        updatedRows[index] = { ...updatedRows[index], invoices: [] };
      }
      updatedRows[index] = { ...updatedRows[index], invoice_id: "" };
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoices for product", err);
      formik.setFieldValue("rows", updatedRows);
    }
  };

  const handleInvoiceChange = async (index, option) => {
    const invoiceId = option ? option.value : "";
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], invoice_id: invoiceId };

    try {
      const response = await axios.post(props.invoicesProductApi, {
        supplier_id: updatedRows[index].supplier_id,
        date: updatedRows[index].date,
        invoice_id: invoiceId,
        product_id: updatedRows[index].product_id,
      });

      if (response.data?.success === true && response.data.payload) {
        const payload = response.data.payload;
        if (typeof payload.id !== "undefined") {
          updatedRows[index] = {
            ...updatedRows[index],
            quantity: payload.quantity,
            price: payload.unit_price,
            total: payload.unit_price * payload.quantity,
          };
        }
      }
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoice product", err);
      formik.setFieldValue("rows", updatedRows);
    }
  };

  const addRow = () => {
    formik.setFieldValue("rows", [
      ...formik.values.rows,
      {
        id: "",
        product_id: "",
        quantity: "",
        price: "",
        invoice_id: "",
        note: "",
        supplier_id: currentSupplier,
        date,
        invoices: [],
        total: "",
      },
    ]);
  };

  const removeRow = async (row, index) => {
    try {
      updateFormikRows(index, { deleting: true });
      const response = await axios.post(props.invoicesReturnDeleteApi, row);
      if (response.data?.success === true) {
        notifySuccess("Deleted Successfully!");
        setTimeout(() => {
          const updated = formik.values.rows.filter((_, i) => i !== index);
          formik.setFieldValue("rows", updated);
        }, 500);
      } else {
        notifyError(response.data?.payload || "Delete failed");
      }
    } catch (err) {
      console.error(err);
      notifyError("Delete failed");
    } finally {
      updateFormikRows(index, { deleting: false });
    }
  };

  const saveSingleRow = async (row, index) => {
    try {
      updateFormikRows(index, { creating: true });
      const response = await axios.post(props.invoicesReturnCreateApi, row);
      if (response.data?.success === true) {
        notifySuccess("Returned Successfully!");
        dispatch(setRefreshCount(Date.now()));
      } else {
        notifyError(response.data?.payload || "Save failed");
      }
    } catch (err) {
      console.error(err);
      notifyError("Save failed");
    } finally {
      updateFormikRows(index, { creating: false });
    }
  };

  const updateSingleRow = async (row, index) => {
    try {
      updateFormikRows(index, { updating: true });
      const response = await axios.post(props.invoicesReturnUpdateApi, row);
      if (response.data?.success === true) {
        notifySuccess("Returned Successfully!");
      } else {
        notifyError(response.data?.payload || "Update failed");
      }
    } catch (err) {
      console.error(err);
      notifyError("Update failed");
    } finally {
      updateFormikRows(index, { updating: false });
    }
  };

  useEffect(() => {
    let mounted = true;
    const load = async () => {
      try {
        const apiRows = await fetchAndPrependRows();
        const blankRow = currentSupplier
          ? {
              id: "",
              product_id: "",
              quantity: "",
              price: "",
              invoice_id: "",
              note: "",
              supplier_id: currentSupplier,
              date,
              invoices: [],
              total: "",
              supplier: currentSupplierInfo?.name || "",
            }
          : null;
        const finalRows = blankRow ? [...apiRows, blankRow] : apiRows;
        if (mounted) formik.setFieldValue("rows", finalRows);
      } catch (err) {
        console.error("load rows error", err);
      }
    };
    load();
    return () => {
      mounted = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentSupplier, date, refreshCount]);

  const productOptions = useMemo(
    () => [
      { value: "", label: "-- Select Product --" },
      ...products.map((p) => ({ value: p.id, label: p.name })),
    ],
    [products]
  );

  const columns = useMemo(
    () => [
      { name: "#", selector: (row, i) => i + 1, width: "60px" },
      { name: "Date", selector: (row) => row.date || "", sortable: true, width: "120px" },
      {
        name: "Supplier",
        selector: (row) => row.supplier || (currentSupplierInfo?.name || ""),
        sortable: true,
        width: "180px",
      },
      {
        name: "Product",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const value = productOptions.find((o) => o.value === row.product_id) || null;
          return (
            <Select styles={orangeSelectStyles}
              options={productOptions}
              value={value}
              onChange={(opt) => handleProductChange(idx, opt)}
              isClearable
              placeholder="Select Product"
              classNamePrefix="react-select"
              menuPortalTarget={document.body}
              menuPosition="fixed"
            />
          );
        },
        grow: 2,
      },
      {
        name: "Invoice",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const invoiceOptions = (row.invoices || []).map((inv) => {
            const value = inv.payment_id ?? inv.id ?? inv.value ?? inv;
            const label = inv.created_at_full ?? inv.label ?? inv.name ?? String(value);
            return { value, label };
          });
          const value = invoiceOptions.find((o) => o.value === row.invoice_id) || null;
          return (
            <Select styles={orangeSelectStyles}
              options={[{ value: "", label: "-- Select Invoice --" }, ...invoiceOptions]}
              value={value}
              onChange={(opt) => handleInvoiceChange(idx, opt)}
              isClearable
              menuPortalTarget={document.body}
              menuPosition="fixed"
              placeholder="Select Invoice"
              classNamePrefix="react-select"
            />
          );
        },
        grow: 2,
      },
      {
        name: "Note",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          return (
            <input
              type="text"
              className="form-control"
              value={row.note || ""}
              onChange={(e) => updateFormikRows(idx, { note: e.target.value })}
            />
          );
        },
        grow: 2,
      },
      {
        name: "Qty",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          return (
            <input
              type="number"
              min="1"
              className="form-control"
              value={row.quantity || ""}
              onChange={(e) =>
                updateFormikRows(idx, {
                  quantity: e.target.value,
                  total: Number(e.target.value) * Number(row.price || 0),
                })
              }
              onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
            />
          );
        },
        width: "100px",
      },
      {
        name: "Price",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          return (
            <input
              type="number"
              min="0"
              className="form-control"
              value={row.price || ""}
              onChange={(e) =>
                updateFormikRows(idx, {
                  price: e.target.value,
                  total: Number(row.quantity || 0) * Number(e.target.value),
                })
              }
              onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
            />
          );
        },
        width: "120px",
      },
      {
        name: "Total",
        selector: (row) =>
          (Number(row.quantity || 0) * Number(row.price || 0)).toFixed(2),
        sortable: true,
        width: "120px",
      },
      {
        name: "Actions",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const canDo =
            row.supplier_id &&
            row.product_id &&
            row.invoice_id &&
            row.quantity &&
            row.price;

          return (
            <div className="d-flex gap-2 justify-content-end">
              {row.id === "" ? (
                <button
                  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                  disabled={!canDo}
                  onClick={() => saveSingleRow(row, idx)}
                >
                  Save
                </button>
              ) : (
                <button
                  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                  disabled={!canDo}
                  onClick={() => updateSingleRow(row, idx)}
                >
                  Update
                </button>
              )}
              <button
                style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                onClick={() => removeRow(row, idx)}
              >
                Delete
              </button>
            </div>
          );
        },
        right: true,
        width: "220px",
      },
    ],
    [productOptions, formik.values.rows, currentSupplierInfo]
  );

  // 🔍 Filter rows by search term
  const filteredRows = formik.values.rows.filter((row) => {
    const search = filterText.toLowerCase();
    return (
      row.note?.toLowerCase().includes(search) ||
      row.date?.toLowerCase().includes(search) ||
      (row.supplier || "").toLowerCase().includes(search)
    );
  });

  return (
    <div className="card">
      <div className="card-header pb-0 d-flex justify-content-between align-items-center">
        <h5 className="card-title mb-0">
          Supplier: {currentSupplierInfo?.name || "None"}, {date}
        </h5>
        <input
          type="text"
          className="form-control w-auto"
          placeholder="🔍 Search..."
          value={filterText}
          onChange={(e) => setFilterText(e.target.value)}
        />
{filterText && <button type="button" onClick={() => {setFilterText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
      </div>

      <div className="card-body">
		{/*<div className="mb-2 d-flex justify-content-between">
          <button type="button" className="btn btn-sm btn-primary" onClick={addRow}>
            + Add Row
          </button>
          <button
            type="button"
            className="btn btn-sm btn-secondary"
            onClick={() => formik.handleSubmit()}
          >
            Save All
          </button>
        </div>*/}

        <DataTable
          columns={columns}
          data={filteredRows}
          pagination
          highlightOnHover
          persistTableHead
          defaultSortFieldId={1}
          responsive
          noHeader
        />
      </div>
    </div>
  );
}


function ListServerGrid(props) {
  const dispatch = useDispatch();
  const { currentSupplierInfo, products, refreshCount } = useSelector(
    (state) => state.suppliers
  );
  const currentSupplier = useSelector((state) => state.suppliers.currentSupplier);
  const date = useSelector((state) => state.suppliers.date);
  const end_date = useSelector((state) => state.suppliers.end_date);
  const { notifySuccess, notifyError } = useToast();

  // 🔹 Pagination + Search state
  const [filterText, setFilterText] = useState("");
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [totalRows, setTotalRows] = useState(0);
  const [loading, setLoading] = useState(false);

  const formik = useFormik({
    initialValues: { rows: [] },
    validationSchema: Yup.object({
      rows: Yup.array().of(
        Yup.object({
          product_id: Yup.mixed().required("Product required"),
          quantity: Yup.number().required("Qty required").positive(),
          price: Yup.number().required("Price required").positive(),
          invoice_id: Yup.mixed().required("Invoice required"),
        })
      ),
    }),
    onSubmit: async (values) => {
      try {
        await axios.post("/api/stock-products/bulk-save", values.rows);
        notifySuccess("All rows saved successfully!");
      } catch (err) {
        notifyError("Bulk save failed");
      }
    },
  });

  const updateFormikRows = (index, jsonData) => {
    const updated = [...formik.values.rows];
    updated[index] = { ...updated[index], ...jsonData };
    formik.setFieldValue("rows", updated);
  };

  // 🔹 Fetch rows from server with pagination + search
  const fetchRows = async (currentPage = page, currentSearch = filterText) => {
    if (!currentSupplier) return;
    setLoading(true);
    try {
      const response = await axios.post(props.invoicesReturnsApi, {
        supplier_id: currentSupplier,
        date,
        end_date,
        page: currentPage,
        per_page: perPage,
        search: currentSearch,
      });

      if (response.data?.success) {
        const payload = response.data.payload || [];
        const total = response.data.total || payload.length;
        setTotalRows(total);

        // Append a blank row at bottom for new entries
        const blankRow = {
          id: "",
          product_id: "",
          quantity: "",
          price: "",
          invoice_id: "",
          note: "",
          supplier_id: currentSupplier,
          date,
          invoices: [],
          total: "",
          supplier: currentSupplierInfo?.name || "",
        };

        formik.setFieldValue("rows", [...payload, blankRow]);
      } else {
        formik.setFieldValue("rows", []);
      }
    } catch (err) {
      console.error("fetchRows failed:", err);
      notifyError("Failed to fetch rows");
      formik.setFieldValue("rows", []);
    } finally {
      setLoading(false);
    }
  };

  // 🔹 Trigger fetch when supplier/date/refreshCount/page/search changes
  useEffect(() => {
    fetchRows();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentSupplier, date, refreshCount, page, perPage, filterText]);

  // 🔹 Column config same as before
  const productOptions = useMemo(
    () => [
      { value: "", label: "-- Select Product --" },
      ...products.map((p) => ({ value: p.id, label: p.name })),
    ],
    [products]
  );

  const handleProductChange = async (index, option) => {
    const productValue = option ? option.value : "";
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], product_id: productValue };
    formik.setFieldValue("rows", updatedRows);

    try {
      const response = await axios.post(props.invoicesListApi, {
        supplier_id: currentSupplier,
        product_id: productValue,
        date,
      });
      if (response.data?.success) {
        updatedRows[index] = {
          ...updatedRows[index],
          invoices: response.data.payload,
        };
      } else {
        updatedRows[index] = { ...updatedRows[index], invoices: [] };
      }
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoices", err);
    }
  };

  const handleInvoiceChange = async (index, option) => {
    const invoiceId = option ? option.value : "";
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], invoice_id: invoiceId };

    try {
      const response = await axios.post(props.invoicesProductApi, {
        supplier_id: updatedRows[index].supplier_id,
        date: updatedRows[index].date,
        invoice_id: invoiceId,
        product_id: updatedRows[index].product_id,
      });

      if (response.data?.success && response.data.payload) {
        const payload = response.data.payload;
        updatedRows[index] = {
          ...updatedRows[index],
          quantity: payload.quantity,
          price: payload.unit_price,
          total: payload.unit_price * payload.quantity,
        };
      }
      formik.setFieldValue("rows", updatedRows);
    } catch (err) {
      console.error("Failed to load invoice product", err);
    }
  };

  const addRow = () => {
    formik.setFieldValue("rows", [
      ...formik.values.rows,
      {
        id: "",
        product_id: "",
        quantity: "",
        price: "",
        invoice_id: "",
        note: "",
        supplier_id: currentSupplier,
        date,
        invoices: [],
        total: "",
      },
    ]);
  };

  const saveSingleRow = async (row, index) => {
    try {
      updateFormikRows(index, { creating: true });
      const response = await axios.post(props.invoicesReturnCreateApi, row);
      if (response.data?.success) {
        notifySuccess("Returned Successfully!");
        dispatch(setRefreshCount(Date.now()));
      } else notifyError("Save failed");
    } catch (err) {
      console.error(err);
      notifyError("Save failed");
    } finally {
      updateFormikRows(index, { creating: false });
    }
  };

  const updateSingleRow = async (row, index) => {
    try {
      updateFormikRows(index, { updating: true });
      const response = await axios.post(props.invoicesReturnUpdateApi, row);
      if (response.data?.success) notifySuccess("Updated Successfully!");
      else notifyError("Update failed");
    } catch (err) {
      console.error(err);
      notifyError("Update failed");
    } finally {
      updateFormikRows(index, { updating: false });
    }
  };

  const removeRow = async (row, index) => {
    try {
      updateFormikRows(index, { deleting: true });
      const response = await axios.post(props.invoicesReturnDeleteApi, row);
      if (response.data?.success) {
        notifySuccess("Deleted Successfully!");
        fetchRows(page, filterText);
      } else notifyError("Delete failed");
    } catch (err) {
      console.error(err);
      notifyError("Delete failed");
    } finally {
      updateFormikRows(index, { deleting: false });
    }
  };

  const columns = useMemo(
    () => [
      { name: "#", selector: (row, i) => i + 1, width: "60px" },
      { name: "Date", selector: (row) => row.date || "", sortable: true, width: "120px" },
      {
        name: "Supplier",
        selector: (row) => row.supplier || (currentSupplierInfo?.name || ""),
        sortable: true,
        width: "180px",
      },
      {
        name: "Product",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const value = productOptions.find((o) => o.value === row.product_id) || null;
          return (
            <Select styles={orangeSelectStyles}
              options={productOptions}
              value={value}
              onChange={(opt) => handleProductChange(idx, opt)}
              isClearable
              placeholder="Select Product"
              classNamePrefix="react-select"
              menuPortalTarget={document.body}
              menuPosition="fixed"
            />
          );
        },
        grow: 2,
      },
      {
        name: "Invoice",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const invoiceOptions = (row.invoices || []).map((inv) => ({
            value: inv.id,
            label: inv.created_at_full || inv.name || inv.id,
          }));
          const value = invoiceOptions.find((o) => o.value === row.invoice_id) || null;
          return (
            <Select styles={orangeSelectStyles}
              options={[{ value: "", label: "-- Select Invoice --" }, ...invoiceOptions]}
              value={value}
              onChange={(opt) => handleInvoiceChange(idx, opt)}
              isClearable
              menuPortalTarget={document.body}
              menuPosition="fixed"
              placeholder="Select Invoice"
              classNamePrefix="react-select"
            />
          );
        },
        grow: 2,
      },
      {
        name: "Qty",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          return (
            <input
              type="number"
              min="1"
              className="form-control"
              value={row.quantity || ""}
              onChange={(e) =>
                updateFormikRows(idx, {
                  quantity: e.target.value,
                  total: Number(e.target.value) * Number(row.price || 0),
                })
              }
              onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
            />
          );
        },
        width: "100px",
      },
      {
        name: "Price",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          return (
            <input
              type="number"
              min="0"
              className="form-control"
              value={row.price || ""}
              onChange={(e) =>
                updateFormikRows(idx, {
                  price: e.target.value,
                  total: Number(row.quantity || 0) * Number(e.target.value),
                })
              }
              onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
            />
          );
        },
        width: "120px",
      },
      {
        name: "Total",
        selector: (row) =>
          (Number(row.quantity || 0) * Number(row.price || 0)).toFixed(2),
        sortable: true,
        width: "120px",
      },
      {
        name: "Actions",
        cell: (row) => {
          const idx = formik.values.rows.indexOf(row);
          const canDo =
            row.supplier_id &&
            row.product_id &&
            row.invoice_id &&
            row.quantity &&
            row.price;

          return (
            <div className="d-flex gap-2 justify-content-end">
              {row.id === "" ? (
                <button
                  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                  disabled={!canDo}
                  onClick={() => saveSingleRow(row, idx)}
                >
                  Save
                </button>
              ) : (
                <button
                  style={{border:'none',borderRadius:'8px',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                  disabled={!canDo}
                  onClick={() => updateSingleRow(row, idx)}
                >
                  Update
                </button>
              )}
              <button
                style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'rgb(234, 88, 12)',fontSize:'12px',fontWeight:'600',padding:'6px 14px',cursor:'pointer',transition:'all 0.15s'}}
                onClick={() => removeRow(row, idx)}
              >
                Delete
              </button>
            </div>
          );
        },
        right: true,
        width: "220px",
      },
    ],
    [productOptions, formik.values.rows, currentSupplierInfo]
  );

  return (
    <div className="card">
      <div className="card-header pb-0 d-flex justify-content-between align-items-center">
        <h5 className="card-title mb-0">
          Supplier: {currentSupplierInfo?.name || "None"}, {date}
        </h5>
        <input
          type="text"
          className="form-control w-auto"
          placeholder="🔍 Search..."
          value={filterText}
          onChange={(e) => {
            setFilterText(e.target.value);
            setPage(1);
          }}
        />
{filterText && <button type="button" onClick={() => {setFilterText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
      </div>

      <div className="card-body">
        <div className="mb-2 d-flex justify-content-between">
          <button type="button" className="btn btn-sm btn-primary" onClick={addRow}>
            + Add Row
          </button>
          <button
            type="button"
            className="btn btn-sm btn-secondary"
            onClick={() => formik.handleSubmit()}
          >
            Save All
          </button>
        </div>

        <DataTable
          columns={columns}
          data={formik.values.rows}
          pagination
          paginationServer
          paginationTotalRows={totalRows}
          onChangePage={setPage}
          onChangeRowsPerPage={setPerPage}
          highlightOnHover
          persistTableHead
          progressPending={loading}
          progressComponent={<SpecTableLoading />}
          responsive
          noHeader
        />
      </div>
    </div>
  );
}

function FilterBar({ suppliersListApi, noCard = false }) {
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
				if (res.data.success) {
					dispatch(setSuppliers(res.data.payload));
					// Auto-select from URL ?supplier=ID
					const urlParams = new URLSearchParams(window.location.search);
					const urlSupplier = urlParams.get('supplier');
					if (urlSupplier) {
						const info = res.data.payload.find(s => s.id == urlSupplier);
						if (info) {
							dispatch(setCurrentSupplierInfo(info));
							dispatch(setCurrentSupplier(parseInt(urlSupplier)));
						}
					}
				}
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
	const dateBoxStyle = {
		display:'flex',alignItems:'center',gap:'8px',background:'#f8fafc',
		border:'1.5px solid #e2e8f0',borderRadius:'12px',height:h,padding:'0 14px',
		minWidth:'190px',cursor:'pointer',boxSizing:'border-box',
	};

	const _filterInner = (
			<div style={{padding:'16px 20px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'14px',alignItems:'end', ...(noCard ? {} : {background:'linear-gradient(to bottom,#fafbfc,#fff)',borderRadius:'12px'})}}>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Supplier</label>
					<Select styles={{control:(b,s)=>({...b,minHeight:'40px',height:'40px',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e2e8f0',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.1)':'0 1px 3px rgba(0,0,0,0.05)','&:hover':{borderColor:'rgb(234, 88, 12)'},background:'#fff',cursor:'pointer'}),valueContainer:(b)=>({...b,height:'40px',padding:'0 14px'}),indicatorsContainer:(b)=>({...b,height:'40px'}),clearIndicator:(b)=>({...b,padding:'0 4px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),dropdownIndicator:(b)=>({...b,padding:'0 10px 0 2px',color:'#c0c8d4','&:hover':{color:'rgb(234, 88, 12)'}}),indicatorSeparator:()=>({display:'none'}),menuPortal:(b)=>({...b,zIndex:9999}),option:(b,s)=>({...b,fontSize:'13px',fontWeight:'500',padding:'9px 14px',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#FFF5ED':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#374151',cursor:'pointer'}),singleValue:(b)=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),placeholder:(b)=>({...b,fontSize:'13px',color:'#94a3b8'})}} options={options} value={options.find(o => o.value === currentSupplier) || null} isLoading={loading} isClearable isSearchable onChange={handleChange} placeholder="Select Supplier" classNamePrefix="react-select" menuPortalTarget={document.body} />
				</div>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Date Range</label>
					<DateRangePicker fromDate={date} toDate={end_date} onFromChange={val => dispatch(setDate(val))} onToChange={val => dispatch(setEndDate(val))} />
				</div>
			</div>
		);
		if (noCard) return _filterInner;
		return <div style={{borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',marginBottom:'16px'}}>{_filterInner}</div>;
}

// ─── Supplier Products Table (New Return Flow) ───
function SupplierProductsTable(props) {
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);
	const currentSupplierInfo = useSelector(state => state.suppliers.currentSupplierInfo);
	const { date, end_date, refreshCount } = useSelector(state => state.suppliers);
	const dispatch = useDispatch();
	const { notifySuccess, notifyError } = useToast();
	const [products, setLocalProducts] = useState([]);
	const [loading, setLoading] = useState(false);
	const [returnModal, setReturnModal] = useState({ show: false, item: null });
	const [returnNote, setReturnNote] = useState('');
	const [returnQty, setReturnQty] = useState('');
	const [submitting, setSubmitting] = useState(false);
	const [customPrice, setCustomPrice] = useState('');
	const [showReturns, setShowReturns] = useState(false);
	const [returns, setReturns] = useState([]);
	const [returnsLoading, setReturnsLoading] = useState(false);
	const isMobile = window.innerWidth <= 767;
	const currency = props.currency || '£';

	const loadReturns = async () => {
		setReturnsLoading(true);
		try {
			const res = await axios.post(props.invoicesReturnsApi, { supplier_id: currentSupplier, date: date || '2000-01-01', end_date: end_date || '' });
			if (res.data.success) setReturns(res.data.payload || []);
		} catch(e) {}
		finally { setReturnsLoading(false); }
	};

	useEffect(() => {
		const fetch = async () => {
			setLoading(true);
			try {
				const res = await axios.post(props.supplierProductsApi, {
					...(currentSupplier ? { supplier_id: currentSupplier } : {}),
					from_date: date || '',
					to_date: end_date || '',
				});
				const data = res.data.payload || [];
				if (res.data.success) { setLocalProducts(data); props.onCount?.(data.length); }
			} catch (e) { console.error(e); }
			finally { setLoading(false); }
		};
		fetch();
	}, [currentSupplier, date, end_date, refreshCount]);

	const openReturnModal = (item) => { setReturnModal({ show: true, item }); setReturnNote(''); setReturnQty(1); };
	const closeReturnModal = () => { setReturnModal({ show: false, item: null }); setReturnNote(''); setReturnQty(''); setCustomPrice(''); };

	const handleReturn = async () => {
		if (!returnNote.trim()) { notifyError('Note is required'); return; }
		if (!returnQty || Number(returnQty) <= 0) { notifyError('Enter valid quantity'); return; }
		const item = returnModal.item;
		if (Number(returnQty) > item.available) { notifyError(`Max ${item.available} allowed`); return; }
		setSubmitting(true);
		try {
			const res = await axios.post(props.invoicesReturnCreateApi, {
				supplier_id: item.supplier_id || currentSupplier,
				product_id: { value: item.product_id },
				invoice_id: { invoice_id: item.invoice_id, id: item.id, ref_id: item.id },
				quantity: Number(returnQty),
				price: Number(returnQty) > 0 ? totalPrice / Number(returnQty) : item.unit_price,
				note: returnNote,
				date: date || '2000-01-01',
			});
			if (res.data.success !== false) {
				notifySuccess(`Returned ${returnQty} × ${item.product_name}`);
				closeReturnModal();
				window.dispatchEvent(new CustomEvent('stock-updated'));
				if (props.onSuccess) props.onSuccess();
			} else { notifyError(res.data.payload || res.data.message || 'Failed'); }
		} catch (e) { notifyError(e.response?.data?.message || e.response?.data?.payload || 'Failed'); }
		finally { setSubmitting(false); }
	};

	const autoPrice = Number(returnQty || 0) * (returnModal.item?.unit_price || 0);
	const totalPrice = customPrice !== '' ? Number(customPrice) : autoPrice;
	const filteredProducts = props.searchText
		? products.filter(p => (p.product_name || '').toLowerCase().includes(props.searchText.toLowerCase()) || (p.supplier_name || '').toLowerCase().includes(props.searchText.toLowerCase()))
		: products;
	const qtyExceeds = returnModal.item && Number(returnQty) > returnModal.item.available;
	const canSubmit = !submitting && !qtyExceeds && Number(returnQty) > 0 && returnNote.trim();

	const { noCard } = props;
	const isMobileView = window.innerWidth <= 767;
		return (
		<div style={noCard ? {overflow:'visible'} : {background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden',marginBottom:'16px'}}>

			{/* Table — Desktop */}
			{!isMobileView && (
			<div style={{overflowX:'auto',overflowY:'hidden'}}>
				<table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
					<thead>
						<tr style={{background:'#fafbfc'}}>
							{['Invoice','Date','Supplier','Product','Remark','Purchased','Returned','Available','Price','Action'].map(h => (
								<th key={h} style={{padding:'10px 14px',fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'2px solid #f1f5f9',textAlign:h==='Price'?'right':['Purchased','Returned','Available'].includes(h)?'center':'left',whiteSpace:'nowrap'}}>{h}</th>
							))}
						</tr>
					</thead>
					<tbody>
						{loading ? (
							<tr><td colSpan={9} style={{padding:0}}><SpecTableLoading label="Loading returns…" /></td></tr>
						) : products.length === 0 ? (
							<tr><td colSpan={9} style={{padding:0}}><SpecTableEmpty onClear={clearReturnFilters} /></td></tr>
						) : filteredProducts.map((item) => (
							<tr key={item.id} style={{borderBottom:'1px solid #f8fafc'}}>
								<td style={{padding:'12px 14px'}}><span style={{color:'rgb(234, 88, 12)',fontWeight:'700',fontSize:'12px'}}>#{item.invoice_id}</span></td>
								<td style={{padding:'12px 14px',color:'#64748b',fontSize:'12px',whiteSpace:'nowrap'}}>{item.date || '—'}</td>
								<td style={{padding:'12px 14px',color:'#1e293b',fontSize:'12px'}}>{item.supplier_name || '—'}</td>
								<td style={{padding:'12px 14px',fontWeight:'600',color:'#1e293b'}}>{item.product_name}</td>
								<td style={{padding:'12px 14px',color:'#94a3b8',fontStyle:'italic',fontSize:'12px'}}>{item.remarks || '—'}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'600'}}>{item.quantity}</td>
								<td style={{padding:'12px 14px',textAlign:'center',color: item.returned > 0 ? '#dc2626' : '#d1d5db',fontWeight:'600'}}>{item.returned}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'700',color:'#16a34a'}}>{item.available}</td>
								<td style={{padding:'12px 14px',textAlign:'right',fontWeight:'600',whiteSpace:'nowrap'}}>{currency} {Number(item.unit_price).toFixed(2)}</td>
								<td style={{padding:'12px 14px'}}>
									<button onClick={() => openReturnModal(item)} style={{height:'30px',padding:'0 14px',borderRadius:'8px',border:'none',background:'#dc2626',color:'#fff',fontSize:'11px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'4px',whiteSpace:'nowrap'}}>
										<i className="fa fa-undo" style={{fontSize:'10px'}}></i> Return
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
				    empty-state card, so it vanished while loading or when returnable products
				    existed. Render it once at the top so the tabs never disappear. */}
				{props.mobileTabsBar}
				{loading ? (
					<SpecTableLoading label="Loading returns…" />
				) : products.length === 0 ? (
					<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
						<SpecTableEmpty onClear={clearReturnFilters} />
					</div>
				) : filteredProducts.map((item) => (
					<div key={item.id} style={{display:'flex',marginBottom:'10px',borderRadius:'14px',border:'1px solid #eaecf2',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
						<div style={{width:'4px',flexShrink:0,background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}/>
						<div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
							<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px',marginBottom:'8px'}}>
								<div style={{minWidth:0}}>
									<div style={{fontSize:'11px',color:'rgb(234, 88, 12)',fontWeight:'700',marginBottom:'2px'}}>
										{item.invoice_id ? `#${item.invoice_id}` : ''}
										{item.invoice_id && item.date ? ' · ' : ''}
										{item.date || ''}
									</div>
									<div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{item.product_name}</div>
									{item.supplier_name && <div style={{fontSize:'11px',color:'#64748b',fontWeight:'600',marginTop:'1px'}}>{item.supplier_name}</div>}
								</div>
								<button onClick={() => openReturnModal(item)} style={{flexShrink:0,height:'32px',padding:'0 12px',borderRadius:'8px',border:'none',background:'#dc2626',color:'#fff',fontSize:'11px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'4px',whiteSpace:'nowrap'}}>
									<i className="fa fa-undo" style={{fontSize:'10px'}}></i> Return
								</button>
							</div>
							<div style={{display:'flex',gap:'6px',flexWrap:'wrap'}}>
								<span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>Bought: {item.quantity}</span>
								<span style={{fontSize:'11px',fontWeight:'600',color: item.returned > 0 ? '#dc2626' : '#9ca3af',background: item.returned > 0 ? '#fef2f2' : '#f8fafc',border:'1px solid '+(item.returned > 0 ? '#fecaca' : '#e5e7eb'),borderRadius:'6px',padding:'2px 8px'}}>Returned: {item.returned}</span>
								<span style={{fontSize:'11px',fontWeight:'700',color:'#16a34a',background:'#f0fdf4',border:'1px solid #86efac',borderRadius:'6px',padding:'2px 8px'}}>Avail: {item.available}</span>
								<span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>{currency} {Number(item.unit_price).toFixed(2)}</span>
							</div>
							{item.remarks && <div style={{marginTop:'6px',fontSize:'11px',color:'#94a3b8',fontStyle:'italic'}}>{item.remarks}</div>}
						</div>
					</div>
				))}
			</div>
			)}
			{showReturns && (
				<div style={{borderTop:'1px solid #f1f5f9'}}>
					<div style={{padding:'14px 22px',background:'#fafbfc',borderBottom:'1px solid #f1f5f9',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
						<span style={{fontSize:'14px',fontWeight:'700',color:'#1e293b'}}>Returns History</span>
						{returns.length > 0 && <span style={{fontSize:'11px',fontWeight:'600',color:'#64748b',background:'#f1f5f9',padding:'3px 10px',borderRadius:'6px'}}>{returns.length} records</span>}
					</div>
					{returnsLoading ? (
						<div style={{padding:'30px',textAlign:'center',color:'#94a3b8'}}><i className="fa fa-spinner fa-spin" style={{color:'rgb(234, 88, 12)'}}></i> Loading...</div>
					) : returns.length === 0 ? (
						<SpecTableEmpty onClear={clearReturnFilters} />
					) : (
						<div style={{overflowX:'auto'}}>
							<table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
								<thead><tr style={{background:'#fafbfc'}}>
									{['#','Product','Qty','Price','Total','Date'].map(h=>(
										<th key={h} style={{padding:'8px 14px',fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'1px solid #f1f5f9',textAlign:['Qty','Price','Total'].includes(h)?'right':'left'}}>{h}</th>
									))}
								</tr></thead>
								<tbody>
									{returns.map((r,i)=>(
										<tr key={r.id} style={{borderBottom:'1px solid #f8fafc'}}>
											<td style={{padding:'10px 14px',color:'#94a3b8',fontSize:'12px'}}>{i+1}</td>
											<td style={{padding:'10px 14px',fontWeight:'600',color:'#1e293b'}}>{r.product_id}</td>
											<td style={{padding:'10px 14px',textAlign:'right',fontWeight:'600'}}>{r.quantity}</td>
											<td style={{padding:'10px 14px',textAlign:'right'}}>{currency} {Number(r.price).toFixed(2)}</td>
											<td style={{padding:'10px 14px',textAlign:'right',fontWeight:'700',color:'#dc2626'}}>{currency} {Number(r.total).toFixed(2)}</td>
											<td style={{padding:'10px 14px',color:'#64748b',fontSize:'12px'}}>{r.date ? new Date(String(r.date).replace(' ','T')).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—'}</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}
				</div>
			)}

			{/* Return Modal */}
			{returnModal.show && returnModal.item && (<>
				<div style={{position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.4)',zIndex:99998}} onClick={closeReturnModal}></div>
				<div style={{position:'fixed',top:'50%',left:'50%',transform:'translate(-50%,-50%)',background:'#fff',borderRadius:'16px',width:'440px',maxWidth:'90vw',zIndex:99999,boxShadow:'0 20px 60px rgba(0,0,0,0.2)',overflow:'hidden'}}>
					<div style={{padding:'18px 22px',borderBottom:'1px solid #f1f5f9',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
						<div><div style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Return to Supplier</div><div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px'}}>{returnModal.item.product_name}</div></div>
						<button onClick={closeReturnModal} style={{width:'32px',height:'32px',borderRadius:'8px',border:'1px solid #e2e8f0',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',color:'#64748b',fontSize:'16px'}}>×</button>
					</div>
					<div style={{padding:'18px 22px'}}>
						<div style={{display:'flex',gap:'10px',marginBottom:'16px'}}>
							<div style={{flex:1,background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}><div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Invoice</div><div style={{fontSize:'15px',fontWeight:'800',color:'rgb(234, 88, 12)',marginTop:'2px'}}>#{returnModal.item.invoice_id}</div></div>
							<div style={{flex:1,background:'#f0fdf4',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}><div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Available</div><div style={{fontSize:'15px',fontWeight:'800',color:'#16a34a',marginTop:'2px'}}>{returnModal.item.available}</div></div>
							<div style={{flex:1,background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}><div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Unit Price</div><div style={{fontSize:'15px',fontWeight:'800',color:'#1e293b',marginTop:'2px'}}>{currency} {Number(returnModal.item.unit_price).toFixed(2)}</div></div>
						</div>
						<div style={{marginBottom:'14px'}}>
							<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Quantity to Return <span style={{color:'#dc2626'}}>*</span> <span style={{fontSize:'11px',color:'#dc2626',fontWeight:'700',textTransform:'none',letterSpacing:'0',marginLeft:'4px'}}>max {returnModal.item.available}</span></label>
							<input type="number" min="1" max={returnModal.item.available} value={returnQty} onChange={(e) => setReturnQty(e.target.value)}
								style={{width:'100%',height:'42px',borderRadius:'10px',border: qtyExceeds ? '2px solid #dc2626' : '1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'700',textAlign:'center',outline:'none',color:'#1e293b',boxSizing:'border-box',background: qtyExceeds ? '#fef2f2' : '#fff'}}
								onFocus={e => { if(!qtyExceeds) e.target.style.borderColor='rgb(234, 88, 12)'; }} onBlur={e => { if(!qtyExceeds) e.target.style.borderColor='#e2e8f0'; }}
							/>
							{qtyExceeds && <div style={{marginTop:'6px',fontSize:'12px',color:'#dc2626',fontWeight:'600',display:'flex',alignItems:'center',gap:'5px'}}><i className="fa fa-exclamation-triangle" style={{fontSize:'11px'}}></i>You purchased {returnModal.item.quantity} and have {returnModal.item.available} available — cannot return {returnQty}</div>}
						</div>
						<div style={{marginBottom:'14px'}}>
							<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Reason for Return <span style={{color:'#dc2626'}}>*</span></label>
							<textarea value={returnNote} onChange={(e) => setReturnNote(e.target.value)} placeholder="e.g. Damaged, wrong product, quality issue..." rows="3"
								style={{width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',padding:'10px 14px',outline:'none',color:'#1e293b',boxSizing:'border-box',resize:'none'}}
								onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
						</div>
						<div style={{background:'#fef2f2',borderRadius:'10px',padding:'14px 16px',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
							<span style={{fontSize:'13px',fontWeight:'600',color:'#64748b'}}>Refund Amount</span>
							<div style={{display:'flex',alignItems:'center',gap:'6px'}}><span style={{fontSize:'14px',fontWeight:'700',color:'#dc2626'}}>{currency}</span><input type="number" min="0" step="0.01" value={customPrice !== '' ? customPrice : autoPrice.toFixed(2)} onChange={e => setCustomPrice(e.target.value)} style={{width:'100px',height:'36px',borderRadius:'8px',border:'1.5px solid #fca5a5',fontSize:'16px',fontWeight:'800',color:'#dc2626',textAlign:'right',padding:'0 8px',outline:'none',background:'#fef2f2'}} onFocus={e=>e.target.style.borderColor='#dc2626'} onBlur={e=>e.target.style.borderColor='#fca5a5'} /></div>
						</div>
					</div>
					<div style={{padding:'14px 22px',borderTop:'1px solid #f1f5f9',display:'flex',gap:'10px'}}>
						<button onClick={closeReturnModal} style={{flex:1,height:'42px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer'}}>Cancel</button>
						<button onClick={handleReturn} disabled={!canSubmit} style={{flex:1,height:'42px',borderRadius:'10px',border:'none',background: canSubmit ? '#dc2626' : '#e2e8f0',color: canSubmit ? '#fff' : '#94a3b8',fontSize:'13px',fontWeight:'700',cursor: canSubmit ? 'pointer' : 'not-allowed',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}>
							{submitting ? <><i className="fa fa-spinner fa-spin"></i> Processing...</> : <><i className="fa fa-undo"></i> Confirm Return</>}
						</button>
					</div>
				</div>
			</>)}
		</div>
	);
}

export default function SuppliersReturnApp(props) {
	const dispatch = useDispatch();
	const [showHistory, setShowHistory] = useState(true);
	const [totals, setTotals] = useState({ all: 0, paid: 0, pending: 0 });
	const [returnTotal, setReturnTotal] = useState(0);
	const [productCount, setProductCount] = useState(0);
	const [historyRefreshKey, setHistoryRefreshKey] = useState(0);
	const [productSearch, setProductSearch] = useState('');
	const suppliers = useSelector(state => state.suppliers.suppliers);
	const currentSupplier = useSelector(state => state.suppliers.currentSupplier);

	const loadList = async() => {
		try {
			const response = await axios.get(props.productsListApi);
			if (response.data.success === true) {
				dispatch(setProducts(response.data.payload));
			}
		} catch (err) {}
	}

	useEffect(() => {
		loadList()
	},[])

	useEffect(() => {
		const handleRefresh = (e) => {
			if (!e.detail || e.detail.tab === 'supplier-return') {
				dispatch(setRefreshCount(Date.now()));
			}
		};
		window.addEventListener('sc-tab-activated', handleRefresh);
		window.addEventListener('stock-updated', handleRefresh);
		return () => {
			window.removeEventListener('sc-tab-activated', handleRefresh);
			window.removeEventListener('stock-updated', handleRefresh);
		};
	}, [dispatch]);

    const tabsMarkup = (
        <div style={{ padding: '8px 20px 0', display: 'flex', gap: '6px', borderBottom: '1px solid #f1f5f9', alignItems: 'flex-end', overflow: 'hidden' }}>
        <button type="button" onClick={() => setShowHistory(true)} style={{ padding: '6px 12px 8px', border: 'none', outline: 'none', background: 'transparent', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '6px', borderBottom: showHistory ? '2px solid rgb(234, 88, 12)' : '2px solid transparent', transition: 'all 0.15s', boxShadow: 'none', whiteSpace: 'nowrap', flexShrink: 0 }}>
            <i className="fa fa-history" style={{ fontSize: '11px', color: showHistory ? 'rgb(234, 88, 12)' : '#94a3b8', flexShrink: 0 }}></i>
            <span style={{ fontSize: '11px', fontWeight: '600', color: showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}>Credit History</span>
            <span style={{ fontSize: '10px', fontWeight: '700', color: showHistory ? '#fff' : '#94a3b8', background: showHistory ? 'rgb(234, 88, 12)' : '#f1f5f9', padding: '1px 6px', borderRadius: '10px', minWidth: '16px', textAlign: 'center', flexShrink: 0 }}>{totals.count || 0}</span>
        </button>
        <button type="button" onClick={() => setShowHistory(false)} style={{ padding: '6px 12px 8px', border: 'none', outline: 'none', background: 'transparent', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '6px', borderBottom: !showHistory ? '2px solid rgb(234, 88, 12)' : '2px solid transparent', transition: 'all 0.15s', boxShadow: 'none', whiteSpace: 'nowrap', flexShrink: 0 }}>
            <i className="fa fa-plus-circle" style={{ fontSize: '11px', color: !showHistory ? 'rgb(234, 88, 12)' : '#94a3b8', flexShrink: 0 }}></i>
            <span style={{ fontSize: '11px', fontWeight: '600', color: !showHistory ? 'rgb(234, 88, 12)' : '#94a3b8' }}>Add Return Credit</span>
            <span style={{ fontSize: '10px', fontWeight: '700', color: !showHistory ? '#fff' : '#94a3b8', background: !showHistory ? 'rgb(234, 88, 12)' : '#f1f5f9', padding: '1px 6px', borderRadius: '10px', minWidth: '16px', textAlign: 'center', flexShrink: 0 }}>{productCount}</span>
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
					<i className="fa fa-truck" style={{ color: '#fff', fontSize: '20px' }}></i>
				</div>
				<div>
					<h1 style={{ fontSize: '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Supplier Return</h1>
					<p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Manage supplier return records</p>
				</div>
			</div>
		)}
		{/* Content — filters always visible, tabs below, form when !showHistory */}
		<ReturnHistoryApp noCard hideTable={!showHistory} type="supplier" returnsApi={props.invoicesReturnsApi} entitiesApi={props.suppliersListApi} currency={props.currency} onBack={() => setShowHistory(false)} onTotals={setTotals} creditBalanceApi="/supplier_return/view/credit-balance/" creditBalanceAllApi="/supplier_return/view/credit-balance-all" refreshKey={historyRefreshKey} printUrl={showHistory ? props.printUrl : props.returnablePrintUrl} excelUrl={showHistory ? props.excelUrl : props.returnableExcelUrl} onEntityChange={v => { dispatch(setCurrentSupplier(v?.value || '')); dispatch(setCurrentSupplierInfo(v ? { id: v.value, name: v.label } : {})); }} onDateChange={(from,to) => { dispatch(setDate(from)); dispatch(setEndDate(to)); }} onSearchChange={setProductSearch} tabsBar={tabsMarkup} />
		<div style={{ display: showHistory ? 'none' : 'block' }}>
			<SupplierProductsTable noCard {...props} mobileTabsBar={tabsMarkup} returnableExcelUrl={props.returnableExcelUrl} onSuccess={() => { setShowHistory(true); setHistoryRefreshKey(k => k + 1); }} onCount={setProductCount} searchText={productSearch} />
			{currentSupplier && <div style={{ marginBottom: '70px' }}>
				<List {...props} />
			</div>}
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('suppliers-return-app')) {
    const id = "suppliers-return-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<SuppliersReturnApp {...props} />
		</Provider>
    );
}
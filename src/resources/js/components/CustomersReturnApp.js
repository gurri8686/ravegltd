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
import DateRangePicker from "./../hooks/DateRangePicker";
import { ReturnHistoryApp } from "./ReturnHistoryApp";

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
import _ from 'lodash';
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecTableEmpty from "./../elements/SpecTableEmpty";

// ----------------- Slice + Store -----------------
const formattedToday = new Date().toISOString().split('T')[0];
const formattedYesterday = (() => { const d = new Date(); d.setMonth(d.getMonth() - 1); return d.toISOString().split('T')[0]; })();

const customersSlice = createSlice({
    name: 'customers',
    initialState: { 
		customers: [],
		products:[],
		currentCustomer:"", 
		currentCustomerInfo:{},
		loading: false, 
		refreshPayments: 0 ,
		paymentMode:"",
		amount:0,
		date:formattedToday,
		toDate:formattedToday,
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
        setCustomers: (state, action) => { state.customers = action.payload },
		setRefreshCount: (state, action) => { state.refreshCount = action.payload },
		setProducts: (state, action) => { state.products = action.payload },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
		setCurrentCustomerInfo: (state, action) => { state.currentCustomerInfo = action.payload; },
		setCustomersLoading: (state, action) => { state.loading = action.payload; },
		
		setPaymentMode: (state, action) => { state.paymentMode = action.payload; },
		setAmount: (state, action) => { state.amount = action.payload; },
		setDate: (state, action) => { state.date = action.payload; },
		setToDate: (state, action) => { state.toDate = action.payload; },
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
		resetCustomersState: (state) => {
            state.currentCustomer = "";
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

const {setCustomers, setCurrentCustomer,setRefreshCount, setCustomersLoading, triggerPaymentRefresh, setCurrentCustomerInfo,setProducts,
	setPaymentMode, setAmount, setDate, setToDate, setNote, setInvoices, resetCustomersState,setTotalChecked,
	setPaymentDoable, toggleInvoice, resetAccountPayments,resetPaymentForm, resetInvoices, setAccountPayments, setOnAccount} = customersSlice.actions;

const store = configureStore({
    reducer: { customers: customersSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

// Clear-filters handler for the empty state — clears customer + date so ALL
// records (the full date range present in the DB) are fetched and shown.
const clearReturnFilters = () => {
	store.dispatch(setCurrentCustomer(null));
	store.dispatch(setCurrentCustomerInfo(null));
	store.dispatch(setDate(''));
	store.dispatch(setToDate(''));
};

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
	
	const options = customers.map(c => ({
		value: c.id,
		label: c.name,
	}));
	
	const handleChange = (selected) => {
		if (!selected || !selected.value) {
			dispatch(resetCustomersState());
			dispatch(setCurrentCustomerInfo(null));
			dispatch(setCurrentCustomer(null));
			return;
		}
		const selectedCustomer = customers.find(
		  (c) => c.id === selected.value
		);
		dispatch(resetCustomersState());
        dispatch(setCurrentCustomerInfo(selectedCustomer));
		dispatch(setCurrentCustomer(selected.value));
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

    return { options, loading, error, handleChange };
}

// Unified filter bar
function FilterBar({ apiUrl, initialCustomerId, noCard = false }) {
	const dispatch = useDispatch();
	const { date, toDate, customers } = useSelector(state => state.customers);
	const { options, loading, error, handleChange } = CustomerSelect({ apiUrl });

	// Auto-select customer from URL only
	useEffect(() => {
		if (initialCustomerId && customers.length > 0) {
			const cust = customers.find(c => c.id == initialCustomerId);
			if (cust) {
				dispatch(setCurrentCustomerInfo(cust));
				dispatch(setCurrentCustomer(cust.id));
			}
		}
	}, [initialCustomerId, customers]);

	// Default dates: today
	useEffect(() => {
		const today = new Date().toISOString().split('T')[0];
		dispatch(setDate(today));
		dispatch(setToDate(today));
	}, []);

	const h = '42px';
	const selectStyles = {
		...orangeSelectStyles,
		control: (base, state) => ({
			...orangeSelectStyles.control(base, state),
			minHeight:h,height:h,borderRadius:'10px',
			border: state.isFocused ? '1.5px solid #F27420' : '1.5px solid #e5e7eb',
			background:'#fff',boxShadow: state.isFocused ? '0 0 0 3px rgba(242,116,32,0.1)' : 'none',
		}),
		valueContainer: (base) => ({...base,height:h,padding:'0 12px'}),
		indicatorsContainer: (base) => ({...base,height:h}),
		placeholder: (base) => ({...base,fontSize:'13px',color:'#9ca3af'}),
		singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#111827'}),
	};

	const currentCustomerInfo = useSelector(state => state.customers.currentCustomerInfo);
	const currentCustomer = useSelector(state => state.customers.currentCustomer);

	const filterContent = (
		<div style={{padding:'16px 20px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'14px',alignItems:'end', ...(noCard ? {} : {background:'linear-gradient(to bottom,#fafbfc,#fff)',borderRadius:'12px'})}}>
			<div>
				<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Customer</label>
					{initialCustomerId && currentCustomerInfo?.name ? (
						<div style={{display:'flex',alignItems:'center',gap:'10px',background:'#f1f5f9',border:'1.5px solid #e2e8f0',borderRadius:'10px',padding:'0 14px',height:'40px',cursor:'not-allowed',opacity:0.85}}>
							<i className="fa fa-user" style={{fontSize:'12px',color:'#94a3b8'}}></i>
							<span style={{fontWeight:'700',fontSize:'13px',color:'#1e293b',flex:1,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{currentCustomerInfo.name}</span>
							<i className="fa fa-lock" style={{fontSize:'11px',color:'#cbd5e1'}}></i>
						</div>
					) : (
						<Select styles={{control:(base, state) => ({...base,minHeight:'40px',height:'40px',borderRadius:'10px',border:state.isFocused?'1.5px solid #F27420':'1.5px solid #e2e8f0',boxShadow:state.isFocused?'0 0 0 3px rgba(242,116,32,0.1)':'0 1px 3px rgba(0,0,0,0.05)','&:hover':{borderColor:'#F27420'},background:'#fff',cursor:'pointer'}),valueContainer:(b)=>({...b,height:'40px',padding:'0 14px'}),indicatorsContainer:(b)=>({...b,height:'40px'}),clearIndicator:(b)=>({...b,padding:'0 4px',color:'#c0c8d4','&:hover':{color:'#F27420'}}),dropdownIndicator:(b)=>({...b,padding:'0 10px 0 2px',color:'#c0c8d4','&:hover':{color:'#F27420'}}),indicatorSeparator:()=>({display:'none'}),menuPortal:(b)=>({...b,zIndex:9999}),option:(b,s)=>({...b,fontSize:'13px',fontWeight:'500',padding:'9px 14px',backgroundColor:s.isSelected?'#F27420':s.isFocused?'#FFF5ED':'#fff',color:s.isSelected?'#fff':s.isFocused?'#F27420':'#374151',cursor:'pointer'}),singleValue:(b)=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),placeholder:(b)=>({...b,fontSize:'13px',color:'#94a3b8'})}} options={options} isLoading={loading} isClearable isSearchable onChange={handleChange} placeholder="Select Customer" classNamePrefix="react-select" menuPortalTarget={document.body} />
					)}
				</div>
				<div>
					<label style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Date Range</label>
					<DateRangePicker fromDate={date} toDate={toDate} onFromChange={val => dispatch(setDate(val))} onToChange={val => dispatch(setToDate(val))} />
				</div>
			</div>
	);
	if (noCard) return filterContent;
	return <div style={{borderRadius:"12px",border:"1px solid #eaecf2",background:"#fff",overflow:"visible",boxShadow:"0 1px 4px rgba(0,0,0,0.04)",marginBottom:"16px"}}>{filterContent}</div>;
}


// list.
function List(props) {
	const {currentCustomerInfo,products, refreshCount, customers, totalChecked, invoices,amount,paymentMode,date,toDate,note,paymentDoable,onAccount} = useSelector(state => state.customers);
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const refreshPayments = useSelector(state => state.customers.refreshPayments);
	const [data, setData] = useState([]);
	const [refreshGrid, setRefreshGrid] = useState(0);
	const [allSet, setAllSet] = useState(true);
	const [saving, setSaving] = useState(0);
	const [tableLoading, setTableLoading] = useState(false);
	const [balance, setBalance] = useState(0);
	const [invoicesAmount, setInvoicesAmount] = useState(0);
	const [selectedRows, setSelectedRows] = useState([]);
	const isMobile = window.innerWidth <= 767;
	const isTablet = window.innerWidth >= 768 && window.innerWidth <= 1024;

	const { notifySuccess, notifyError } = useToast();
	
	const tableRef = useRef(null);
	const dataTableRef = useRef(null);
	
	const dispatch = useDispatch();
	
	const formik = useFormik({
		initialValues: {
		  rows: [
			/*{ product_id: '', quantity: '', price: '', invoice_id: '', 'note':'', customer_id:'' },*/
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
			const response = await axios.post(props.invoicesReturnsApi, {customer_id:currentCustomer, date:date, to_date:toDate}); // your endpoint
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
			setTableLoading(true);
			const start = Date.now();
			try {
				// fetchAndPrependRows should return an array (or [] if none)
				const apiRows = await fetchAndPrependRows(); // -> e.g. [{...}, {...}]

				let blankRow = null;
				if (currentCustomer) {
					blankRow = {
						id:"",
						product_id: '',
						quantity: '',
						price: '',
						invoice_id: '',
						note: '',
						customer_id: currentCustomer,
						date: date, // ensure `date` is in scope
						invoices: [],
						total:"",
						customer: currentCustomerInfo?.name || '',
					};
				}
				const finalRows = blankRow ? [...apiRows, blankRow] : apiRows;
				//const finalRows = null ? [...apiRows, blankRow] : apiRows;
				formik.setFieldValue('rows', finalRows);
			} catch (err) {
				console.error('load rows error', err);
			}finally{
				const elapsed = Date.now() - start;
				const remaining = 1000 - elapsed;
				if (remaining > 0) { setTimeout(() => setTableLoading(false), remaining); } else { setTableLoading(false); }
			}
		};
		load();
	}, [currentCustomer, date, toDate, refreshCount]);
	
	const handleProductChange = async(index, e) => {
		const updatedRows = [...formik.values.rows];
		updatedRows[index] = { ...updatedRows[index], ...{product_id:e} };
		
		// call api to get the invoices.
		try {
			const response = await axios.post(props.invoicesListApi, {customer_id:currentCustomer, product_id:e.value, date:date}); // your endpoint
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
				customer_id:formik.values.rows[index].customer_id,
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
	
	const options = products.map(c => ({
		value: c.id,
		label: c.name,
	}));
	
	const columns = [
    {
      name: "ID",
      selector: (row) => row.id,
      width: "70px",
      sortable: true,
    },
    {
      name: "#",
      selector: (row, index) => index + 1,
      width: "60px",
    },
    {
      name: "Date",
      selector: (row) => row.date,
      sortable: true,
      minWidth: '110px',
    },
    {
      name: "Customer",
      selector: (row) => row.customer,
      sortable: true,
      minWidth: '120px',
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
		  row.product_id?.label || row.product_id || '-'
		),
	  grow: 3,
	  minWidth: '160px',
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

		  </div>
		) : (
		  row.invoice_id?.label || row.invoice_id || '-'
		),
	  grow: 3,
	  minWidth: '160px',
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
      minWidth: '120px',
    },
    {
      name: "Quantity",
      cell: (row, index) => (
        <input
          type="number"
          className="form-control"
          name={`rows[${index}].quantity`}
          value={row.quantity}
          min="0"
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
          onChange={(e) => { const v=e.target.value; if(v===''||Number(v)>=0) formik.handleChange(e); }}
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
          className="form-control"
          name={`rows[${index}].price`}
          value={row.price}
          min="0"
          onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
          onChange={(e) => { const v=e.target.value; if(v===''||Number(v)>=0) formik.handleChange(e); }}
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
    },
    {
      name: "Actions",
      right: true,
      cell: (row, index) =>
        row.id === "" ? (
          <>
            {(row.creating === false || !("creating" in row)) && (
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
            )}

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
          </>
        ) : (
          <>
            {(row.updating === false || !("updating" in row)) && (
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
            )}

            &nbsp;

            <button
              type="button"
              className="btn btn-sm btn-danger"
              onClick={() => removeRow(row, index)}
            >
              Delete
            </button>
          </>
        ),
      width: "200px",
    },
  ];
	
	//return (<>{console.log('----')}{console.log(formik.values.rows)}</>)

    return (
	<>{true
		?
			<>
				<form onSubmit={formik.handleSubmit} style={{display:'none'}}>
				{
					<>
					{false && currentCustomer ?
					<div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',background:'#fff',overflow:'visible',marginBottom:'16px',position:'relative',zIndex:10}}>
						<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7'}}>
							<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Add Return</span>
						</div>
						<div style={{padding:'20px 22px'}}>
						  {formik.values.rows.map((row, index) => {
						  const hasProduct = !!row.product_id;
						  const hasInvoice = !!row.invoice_id;
						  return row.id === "" ? (
							isMobile ? (
							<div key={`empty_${index}`}>
								{/* PRODUCT */}
											<div style={{marginBottom:'16px'}}>
												<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-cube" style={{color:'#F27420',fontSize:'12px'}}></i> Product</label>
												<div style={{position:'relative'}}>
													<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'48px',height:'48px',borderRadius:'12px',border:state.isFocused?'1.5px solid #F27420':'1px solid #E5E7EB',background:'#fff'}),valueContainer:(base)=>({...base,height:'48px',padding:'0 14px'}),indicatorsContainer:(base)=>({...base,display:'none'}),placeholder:(base)=>({...base,fontSize:'14px',color:'#9CA3AF'}),menuPortal:(base)=>({...base,zIndex:9999})}} options={options} isClearable isSearchable value={row.product_id} onChange={(e) => handleProductChange(index, e)} classNamePrefix="react-select" menuPortalTarget={document.body} placeholder="Select product..." />
													<button type="button" style={{position:'absolute',right:'12px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',fontSize:'22px',fontWeight:'300',color:'#6B7280',cursor:'pointer',lineHeight:1,padding:0,display:'flex',alignItems:'center',justifyContent:'center'}} onClick={() => {}}>+</button>
												</div>
											</div>
											{/* INVOICE */}
														<div style={{marginBottom:'16px'}}>
															<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}>
																<i className="fa fa-file-text-o" style={{color:'#F27420',fontSize:'12px'}}></i> Invoice
																{row.product_id && <span style={{marginLeft:'6px',fontSize:'10px',fontWeight:'600',color:(row.invoices?.length??0)>0?'#16a34a':'#ef4444',background:(row.invoices?.length??0)>0?'#f0fdf4':'#fef2f2',border:(row.invoices?.length??0)>0?'1px solid #86efac':'1px solid #fecaca',borderRadius:'10px',padding:'1px 8px',textTransform:'none',letterSpacing:'0'}}>{(row.invoices?.length??0)>0?`${row.invoices.length} found`:'No invoice'}</span>}
															</label>
															<div style={{position:'relative'}}>
																<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'48px',height:'48px',borderRadius:'12px',border:state.isFocused?'1.5px solid #F27420':'1px solid #E5E7EB',background:'#fff'}),valueContainer:(base)=>({...base,height:'48px',padding:'0 14px'}),indicatorsContainer:(base)=>({...base,display:'none'}),placeholder:(base)=>({...base,fontSize:'14px',color:'#9CA3AF'}),menuPortal:(base)=>({...base,zIndex:9999})}} options={row.invoices} isClearable isSearchable value={row.invoice_id} onChange={(e) => handleInvoiceChange(index, e)} classNamePrefix="react-select" menuPortalTarget={document.body} placeholder="Select invoice..." />
																<span style={{position:'absolute',right:'14px',top:'50%',transform:'translateY(-50%)',pointerEvents:'none',color:'#9CA3AF'}}><i className="fa fa-file-o" style={{fontSize:'15px'}}></i></span>
															</div>
														</div>
														{/* QUANTITY + PRICE */}
																	<div style={{display:'flex',gap:'10px',marginBottom:'16px'}}>
																		<div style={{flex:1}}>
																			<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-hashtag" style={{color:'#F27420',fontSize:'12px'}}></i> Quantity</label>
																			<input type="number" name={`rows[${index}].quantity`} value={row.quantity} min="0" placeholder="0" onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }} onChange={(e) => { const v=e.target.value; if(v===''||Number(v)>=0) formik.handleChange(e); }} style={{width:'100%',height:'56px',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'22px',fontWeight:'700',color:'#111827',padding:'0 14px',outline:'none',background:'#fff',textAlign:'center',boxSizing:'border-box'}} onFocus={e=>e.target.style.borderColor='#F27420'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
																		</div>
																		<div style={{flex:1}}>
																			<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'11px',fontWeight:'700',color:'#111827',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-usd" style={{color:'#F27420',fontSize:'12px'}}></i> Price</label>
																			<input type="number" name={`rows[${index}].price`} value={row.price} min="0" placeholder="0.00" onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }} onChange={formik.handleChange} style={{width:'100%',height:'56px',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'22px',fontWeight:'700',color:'#111827',padding:'0 14px',outline:'none',background:'#fff',textAlign:'center',boxSizing:'border-box'}} onFocus={e=>e.target.style.borderColor='#F27420'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
																		</div>
																	</div>
																	{/* NOTE */}
								<div style={{marginBottom:'16px'}}>
									<label style={{display:'flex',alignItems:'center',gap:'5px',fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'8px'}}><i className="fa fa-sticky-note-o" style={{color:'#F27420',fontSize:'11px'}}></i> Note</label>
									<textarea name={`rows[${index}].note`} defaultValue={row.note} placeholder="Optional additional details" onChange={formik.handleChange} style={{width:'100%',borderRadius:'12px',border:'1px solid #E5E7EB',fontSize:'14px',padding:'12px 14px',outline:'none',background:'#fff',resize:'none',height:'80px',boxSizing:'border-box',lineHeight:'1.5'}} onFocus={e=>e.target.style.borderColor='#F27420'} onBlur={e=>e.target.style.borderColor='#E5E7EB'} />
								</div>
								{/* TOTAL + SAVE */}
								<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',paddingTop:'16px',borderTop:'1px solid #F0F4F8'}}>
									<div>
										<div style={{fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'4px'}}>Total Amount</div>
										<div style={{fontSize:'24px',fontWeight:'800',color:'#111827'}}>{((Number(row.quantity)*Number(row.price))||0).toLocaleString('en-GB',{minimumFractionDigits:2})}</div>
									</div>
									{(row.creating===false||!('creating' in row)) && (
									<button type="button" disabled={!(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)} onClick={() => saveSingleRow(row, index)} style={{height:'50px',padding:'0 28px',borderRadius:'50px',border:'none',background:'#F27420',color:'#fff',fontSize:'15px',fontWeight:'700',cursor:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'pointer':'not-allowed',display:'flex',alignItems:'center',gap:'8px',opacity:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?1:0.55,boxShadow:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'0 4px 16px rgba(242,116,32,0.4)':'none'}}>
										<i className="fa fa-check-circle" style={{fontSize:'18px'}}></i> Save Return
									</button>
									)}
								</div>
							</div>
							) : (
							<div key={`empty_${index}`}>
								{/* Row 1: Product + Invoice */}
								<div style={{display:'flex',gap:'14px',flexWrap:'wrap'}}>
									<div style={{flex:1,minWidth:'200px'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Product</label>
										<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'42px',height:'42px',borderRadius:'10px',border:state.isFocused?'1.5px solid #F27420':'1.5px solid #e2e8f0',background:state.isFocused?'#fff':'#f8fafc'}),valueContainer:(base)=>({...base,height:'42px',padding:'0 12px'}),indicatorsContainer:(base)=>({...base,height:'42px'}),placeholder:(base)=>({...base,fontSize:'13px',color:'#9ca3af'}),menuPortal:(base)=>({...base,zIndex:9999})}}
										  options={options}
										  isClearable isSearchable
										  value={row.product_id}
										  onChange={(e) => handleProductChange(index, e)}
										  classNamePrefix="react-select"
										  menuPortalTarget={document.body}
										  placeholder="Select product..."
										/>
									</div>
									<div style={{flex:1,minWidth:'200px'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>
											Invoice
											{row.product_id && <span style={{marginLeft:'8px',fontSize:'10px',fontWeight:'600',color:(row.invoices?.length??0)>0?'#16a34a':'#ef4444',background:(row.invoices?.length??0)>0?'#f0fdf4':'#fef2f2',border:(row.invoices?.length??0)>0?'1px solid #86efac':'1px solid #fecaca',borderRadius:'10px',padding:'1px 8px',textTransform:'none',letterSpacing:'0'}}>
												{(row.invoices?.length??0)>0 ? `${row.invoices.length} found` : 'No invoice'}
											</span>}
										</label>
										<Select styles={{...orangeSelectStyles,control:(base,state)=>({...orangeSelectStyles.control(base,state),minHeight:'42px',height:'42px',borderRadius:'10px',border:state.isFocused?'1.5px solid #F27420':'1.5px solid #e2e8f0',background:state.isFocused?'#fff':'#f8fafc'}),valueContainer:(base)=>({...base,height:'42px',padding:'0 12px'}),indicatorsContainer:(base)=>({...base,height:'42px'}),placeholder:(base)=>({...base,fontSize:'13px',color:'#9ca3af'}),menuPortal:(base)=>({...base,zIndex:9999})}}
										  options={row.invoices}
										  isClearable isSearchable
										  value={row.invoice_id}
										  onChange={(e) => handleInvoiceChange(index, e)}
										  classNamePrefix="react-select"
										  menuPortalTarget={document.body}
										  placeholder="Select invoice..."
										/>
									</div>
								</div>

								{/* Row 2: Qty + Price + Note + Total + Save */}
								<div style={{display:'flex',gap:'14px',alignItems:'flex-end',flexWrap:'wrap',marginTop:'14px'}}>
									<div style={{flex:'0 0 110px'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Quantity</label>
										<input type="number" name={`rows[${index}].quantity`} value={row.quantity} min="0" placeholder="0"
										  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
										  onChange={(e) => { const v=e.target.value; if(v===''||Number(v)>=0) formik.handleChange(e); }}
										  style={{height:'42px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'600',padding:'0 12px',outline:'none',background:'#f8fafc',textAlign:'center'}}
										  onFocus={e=>e.target.style.borderColor='#F27420'}
										  onBlur={e=>e.target.style.borderColor='#e2e8f0'}
										/>
									</div>
									<div style={{flex:'0 0 120px'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Price</label>
										<input type="number" name={`rows[${index}].price`} value={row.price} min="0" placeholder="0.00"
										  onKeyDown={(e) => { if(e.key==='-'||e.key==='e') e.preventDefault(); }}
										  onChange={formik.handleChange}
										  style={{height:'42px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'600',padding:'0 12px',outline:'none',background:'#f8fafc',textAlign:'right'}}
										  onFocus={e=>e.target.style.borderColor='#F27420'}
										  onBlur={e=>e.target.style.borderColor='#e2e8f0'}
										/>
									</div>
									<div style={{flex:1,minWidth:'120px'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Note</label>
										<input type="text" name={`rows[${index}].note`} defaultValue={row.note} placeholder="Optional"
										  onChange={formik.handleChange}
										  style={{height:'42px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',padding:'0 12px',outline:'none',background:'#f8fafc'}}
										  onFocus={e=>e.target.style.borderColor='#F27420'}
										  onBlur={e=>e.target.style.borderColor='#e2e8f0'}
										/>
									</div>

									{/* Total */}
									<div style={{flex:'0 0 100px',textAlign:'center'}}>
										<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px',display:'block'}}>Total</label>
										<div style={{height:'42px',display:'flex',alignItems:'center',justifyContent:'center',fontSize:'16px',fontWeight:'800',color:'#1e293b'}}>
											{((Number(row.quantity)*Number(row.price))||0).toLocaleString('en-GB',{minimumFractionDigits:2})}
										</div>
									</div>

									{/* Save */}
									{(row.creating===false||!('creating' in row)) && (
									<button type="button"
									  disabled={!(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)}
									  onClick={() => saveSingleRow(row, index)}
									  style={{
										height:'42px',padding:'0 24px',borderRadius:'10px',border:'none',
										background:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'linear-gradient(135deg,#F27420,#ea580c)':'#e5e7eb',
										color:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'#fff':'#9ca3af',
										fontSize:'14px',fontWeight:'700',cursor:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'pointer':'not-allowed',
										display:'flex',alignItems:'center',gap:'6px',whiteSpace:'nowrap',flexShrink:0,
										boxShadow:(row.customer_id&&row.product_id&&row.invoice_id&&row.quantity&&row.price)?'0 2px 8px rgba(242,116,32,0.3)':'none',
									  }}
									>
									  <i className="fa fa-check-circle"></i> Save Return
									</button>
									)}
								</div>
							</div>
							)
						  ) : (
							<React.Fragment key={`skip_${index}`} />
						  );
						})}
						</div>
					</div>
					:
					<></>}

					<div style={{borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',background:'#fff',overflow:'visible'}}>
						<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7'}}>
						<div>
						<div style={{fontSize:(isMobile||isTablet)?'18px':'15px',fontWeight:'700',color:(isMobile||isTablet)?'#111827':'#1e293b',letterSpacing:(isMobile||isTablet)?'-0.3px':'normal'}}>Returns List</div>
						{(isMobile||isTablet) && <div style={{fontSize:'12px',color:'#9CA3AF',fontWeight:'400',marginTop:'2px'}}>Recent transaction history</div>}
					</div>
						<style>{`@keyframes cr-spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}.cr-scroll-area{scrollbar-width:none;}.cr-scroll-area::-webkit-scrollbar{display:none;}.cr-range-scroll{-webkit-appearance:none;width:100%;height:6px;border-radius:10px;background:#f0f0f0;outline:none;cursor:pointer;}.cr-range-scroll::-webkit-slider-thumb{-webkit-appearance:none;width:50px;height:6px;border-radius:10px;background:#F27420;cursor:pointer;}.cr-range-scroll::-moz-range-thumb{width:50px;height:6px;border-radius:10px;background:#F27420;cursor:pointer;border:none;}`}</style>
						<div className={(isMobile||isTablet) ? "cr-scroll-area" : ""} style={{width:"100%",overflowX:"auto",WebkitOverflowScrolling:"touch"}}>
						  <div style={{minWidth: (isMobile||isTablet) ? "1450px" : "100%"}}>
						  <DataTable
							columns={columns}
							data={formik.values.rows.filter(r => r.id !== "")}
							dense
							highlightOnHover
							pagination
							paginationRowsPerPageOptions={[5, 10, 20, 50]}
							paginationPerPage={10}
							className="table table-hover mt-2"
							progressPending={tableLoading}
							progressComponent={<SpecTableLoading />}
							expandableRows
							  expandableRowsComponent={({ data }) => (
								<div style={{padding:'12px 16px',background:'#F9FAFB',borderTop:'1px solid #F0F0F0'}}>
								  {[['CUSTOMER', data.customer, false],['INVOICE', data.invoice, false],['PRICE', data.price, true]].map(([label,val,isPrice])=>(
									<div key={label} style={{display:'flex',alignItems:'center',gap:'8px',marginBottom:'6px'}}>
									  <span style={{fontSize:'10px',fontWeight:'700',color:'#9CA3AF',letterSpacing:'0.6px',textTransform:'uppercase',minWidth:'70px'}}>{label}:</span>
									  <span style={{fontSize:'13px',fontWeight:'700',color:isPrice?'#F27420':'#111827'}}>{val}</span>
									</div>
								  ))}
								</div>
							  )}
						  />
						  </div>
						</div>
						{(isMobile||isTablet) && (
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
						</div></div>
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
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
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
							  className="form-control"
							  name={`rows[${index}].quantity`}
							  value={row.quantity}
							  onChange={formik.handleChange}
							/>
						  </td>

						  <td>
							<input
							  type="number"
							  className="form-control"
							  name={`rows[${index}].price`}
							  value={row.price}
							  onChange={formik.handleChange}
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
}


function List2(props) {
  const {
    currentCustomerInfo,
    products,
    date,
  } = useSelector((state) => state.customers);
  const currentCustomer = useSelector(
    (state) => state.customers.currentCustomer
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
        customer_id: currentCustomer,
        date: date,
        to_date: toDate,
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
          customer_id: currentCustomer,
          date: date,
          invoices: [],
          total: "",
          customer: currentCustomerInfo?.name || "",
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
    if (currentCustomer && date) fetchRows();
  }, [currentCustomer, date]);

  // Handlers
  const handleProductChange = async (index, e) => {
    const updatedRows = [...formik.values.rows];
    updatedRows[index] = { ...updatedRows[index], product_id: e };
    try {
      const response = await axios.post(props.invoicesListApi, {
        customer_id: currentCustomer,
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
        customer_id: updatedRows[index].customer_id,
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
    ...products.map((c) => ({ value: c.id, label: c.name })),
  ];

  // Define columns for DataTable
  const columns = [
    {
      name: "#",
      cell: (row, index) => index + 1,
      width: "60px",
    },
    { name: "Date", selector: (row) => row.date, sortable: true },
    { name: "Customer", selector: (row) => row.customer, sortable: true },
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
            {(row.invoices?.length ?? 0) <= 0 ? (
              null
            ) : (
              null
            )}
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
          className="form-control"
          name={`rows[${index}].quantity`}
          value={row.quantity || ""}
          onChange={formik.handleChange}
        />
      ),
    },
    {
      name: "Price",
      cell: (row, index) => (
        <input
          type="number"
          className="form-control"
          name={`rows[${index}].price`}
          value={row.price || ""}
          onChange={formik.handleChange}
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
                row.customer_id &&
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
              Customer: {currentCustomerInfo?.name || "None"} — {date}
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
  const { currentCustomerInfo, products, date, toDate } = useSelector(state => state.customers);
  const currentCustomer = useSelector(state => state.customers.currentCustomer);
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
        customer_id: currentCustomer,
        date: date,
        to_date: toDate,
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

        if (currentCustomer) {
          blankRow = {
            id: "",
            product_id: "",
            quantity: "",
            price: "",
            invoice_id: "",
            note: "",
            customer_id: currentCustomer,
            date: date,
            invoices: [],
            total: "",
            customer: currentCustomerInfo?.name || "",
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

    if (currentCustomer && date) {
      load();
    }

    return () => {
      isMounted = false;
    };
  }, [currentCustomer, date]);

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
              Customer: {currentCustomerInfo?.name || "None"}, {date}
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
                    <th>Customer</th>
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

// ─── Customer Products Table (New Return Flow) ───
function CustomerProductsTable(props) {
	const { noCard } = props;
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	const currentCustomerInfo = useSelector(state => state.customers.currentCustomerInfo);
	const { date, toDate, refreshCount } = useSelector(state => state.customers);
	const dispatch = useDispatch();
	const { notifySuccess, notifyError } = useToast();
	const [products, setLocalProducts] = useState([]);
	const [loading, setLoading] = useState(false);
	const [arcPage, setArcPage] = useState(0);
	const [arcPerPage, setArcPerPage] = useState(50);
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
			const res = await axios.post(props.invoicesReturnsApi, { customer_id: currentCustomer, date: date || '', to_date: toDate || '' });
			if (res.data.success) setReturns(res.data.payload || []);
		} catch(e) {}
		finally { setReturnsLoading(false); }
	};

	useEffect(() => {
		const fetch = async () => {
			setLoading(true);
			const start = Date.now();
			try {
				const res = await axios.post(props.customerProductsApi, {
					...(currentCustomer ? { customer_id: currentCustomer } : {}),
					from_date: date || '',
					to_date: toDate || '',
				});
				const data = res.data.payload || [];
				if (res.data.success) { setLocalProducts(data); }
			} catch (e) { console.error(e); }
			finally { const elapsed = Date.now() - start; const remaining = 1000 - elapsed; if (remaining > 0) { setTimeout(() => setLoading(false), remaining); } else { setLoading(false); } }
		};
		fetch();
	}, [currentCustomer, date, toDate, refreshCount]);

	// Report the product count to the parent in its own effect — keeps the parent's
	// setState out of this component's render pass (avoids the cross-component
	// "Cannot update a component while rendering a different component" warning).
	useEffect(() => {
		props.onCount?.(products.length);
		setArcPage(0);
	}, [products]);

	const openReturnModal = (item) => {
		setReturnModal({ show: true, item });
		setReturnNote('');
		setReturnQty(1);
	};

	const closeReturnModal = () => {
		setReturnModal({ show: false, item: null });
		setReturnNote('');
		setReturnQty('');
		setCustomPrice('');
	};

	const handleReturn = async () => {
		if (!returnNote.trim()) { notifyError('Note is required — please explain why returning'); return; }
		if (!returnQty || Number(returnQty) <= 0) { notifyError('Enter valid quantity'); return; }
		const item = returnModal.item;
		if (Number(returnQty) > item.available) { notifyError(`Max ${item.available} allowed`); return; }

		setSubmitting(true);
		try {
			const res = await axios.post(props.invoicesReturnCreateApi, {
				customer_id: currentCustomer,
				product_id: { value: item.product_id },
				invoice_id: {
					invoice_id: item.invoice_id,
					id: item.id,
					ref_id: item.id,
				},
				quantity: Number(returnQty),
				price: Number(returnQty) > 0 ? totalPrice / Number(returnQty) : item.unit_price,
				note: returnNote,
				date: date || '2000-01-01',
			});
			if (res.data.success !== false) {
				notifySuccess(`Returned ${returnQty} × ${item.product_name}`);
				closeReturnModal();
				window.dispatchEvent(new CustomEvent('stock-updated'));
				setLocalProducts(prev => {
					const qty = Number(returnQty);
					return prev.map(p => p.id === item.id
						? { ...p, returned: (p.returned || 0) + qty, available: p.available - qty }
						: p
					).filter(p => p.available > 0);
				});
				if (props.onSuccess) props.onSuccess();
			} else {
				notifyError(res.data.message || 'Failed');
			}
		} catch (e) {
			notifyError(e.response?.data?.message || 'Failed to return');
		} finally { setSubmitting(false); }
	};

	const autoPrice = Number(returnQty || 0) * (returnModal.item?.unit_price || 0);
	const totalPrice = customPrice !== '' ? Number(customPrice) : autoPrice;

	const isMobileView = window.innerWidth <= 767;

	return (
		<div style={noCard ? {overflow:'visible'} : {background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden',marginBottom:'16px'}}>
			<style>{`@keyframes cr-spin2{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}`}</style>

			{/* Desktop-only top loader; on mobile the loader lives inside the mobile block
			    below the tab bar so the tabs stay visible while loading. */}
			{loading && !isMobileView && (
				<SpecTableLoading label="Loading…" />
			)}

			{/* Table — Desktop */}
			{!loading && !isMobileView && (
			<div style={{overflowX:'auto',overflowY:'hidden'}}>
				<table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
					<thead>
						<tr style={{background:'#fafbfc'}}>
							{['Invoice','Date','Customer','Product','Remark','Purchased','Returned','Available','Price','Action'].map(h => (
								<th key={h} style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'2px solid #f1f5f9',textAlign:['Price','Purchased','Returned','Available'].includes(h)?'center':'left',whiteSpace:'nowrap',width:h==='Date'?'95px':h==='Invoice'?'70px':undefined}}>{h}</th>
							))}
						</tr>
					</thead>
					<tbody>
						{products.length === 0 ? (
							<tr><td colSpan={10} style={{padding:0}}><SpecTableEmpty onClear={clearReturnFilters} /></td></tr>
						) : products.slice(arcPage * arcPerPage, (arcPage + 1) * arcPerPage).map((item) => (
							<tr key={item.id} style={{borderBottom:'1px solid #f8fafc'}}>
								<td style={{padding:'12px 14px'}}><a href={'/data_entry/sales_entry/invoice/'+item.invoice_id} target="_blank" style={{color:'#f97316',fontWeight:'700',textDecoration:'none',fontSize:'12px'}}>#{item.invoice_id}</a></td>
								<td style={{padding:'12px 14px',color:'#64748b',fontSize:'12px',whiteSpace:'nowrap',width:'95px'}}>{item.date || '—'}</td>
								<td style={{padding:'12px 14px',color:'#374151',fontSize:'12px',fontWeight:'500'}}>{item.customer_name || '—'}</td>
								<td style={{padding:'12px 14px',fontWeight:'600',color:'#1e293b'}}>{item.product_name}</td>
								<td style={{padding:'12px 14px',color:'#94a3b8',fontStyle:'italic',fontSize:'12px'}}>{item.remarks || '—'}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'600'}}>{item.quantity}</td>
								<td style={{padding:'12px 14px',textAlign:'center',color: item.returned > 0 ? '#dc2626' : '#d1d5db',fontWeight:'600'}}>{item.returned}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'700',color:'#16a34a'}}>{item.available}</td>
								<td style={{padding:'12px 14px',textAlign:'center',fontWeight:'600'}}>{currency} {Number(item.unit_price).toFixed(2)}</td>
								<td style={{padding:'12px 14px'}}>
									<button onClick={() => openReturnModal(item)} style={{height:'30px',padding:'0 14px',borderRadius:'8px',border:'none',background:'#dc2626',color:'#fff',fontSize:'11px',fontWeight:'700',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'4px',whiteSpace:'nowrap'}}>
										<i className="fa fa-undo" style={{fontSize:'10px'}}></i> Return
									</button>
								</td>
							</tr>
						))}
					</tbody>
				</table>
				{products.length > 0 && (() => {
					const total = products.length;
					const totalPages = Math.max(1, Math.ceil(total / arcPerPage));
					const start = arcPage * arcPerPage + 1;
					const end = Math.min((arcPage + 1) * arcPerPage, total);
					return (
						<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'10px 16px',borderTop:'1px solid #f1f5f9',background:'#fafbfc',fontSize:'12px',color:'#64748b'}}>
							<div style={{display:'flex',alignItems:'center',gap:'8px'}}>
								<span>Rows per page:</span>
								<select value={arcPerPage} onChange={(e) => { setArcPerPage(Number(e.target.value)); setArcPage(0); }}
									style={{height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',padding:'0 6px',fontSize:'12px',color:'#374151',cursor:'pointer',outline:'none'}}>
									{[50,100,200,500].map(n => <option key={n} value={n}>{n}</option>)}
								</select>
							</div>
							<div style={{display:'flex',alignItems:'center',gap:'12px'}}>
								<span>{start}–{end} of {total}</span>
								<div style={{display:'flex',gap:'4px'}}>
									<button type="button" onClick={() => setArcPage(0)} disabled={arcPage === 0}
										style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:arcPage===0?'#cbd5e1':'#64748b',cursor:arcPage===0?'default':'pointer',outline:'none',fontSize:'10px'}}>«</button>
									<button type="button" onClick={() => setArcPage(p => Math.max(0, p - 1))} disabled={arcPage === 0}
										style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:arcPage===0?'#cbd5e1':'#64748b',cursor:arcPage===0?'default':'pointer',outline:'none',fontSize:'10px'}}>‹</button>
									<button type="button" onClick={() => setArcPage(p => Math.min(totalPages - 1, p + 1))} disabled={arcPage >= totalPages - 1}
										style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:arcPage>=totalPages-1?'#cbd5e1':'#64748b',cursor:arcPage>=totalPages-1?'default':'pointer',outline:'none',fontSize:'10px'}}>›</button>
									<button type="button" onClick={() => setArcPage(totalPages - 1)} disabled={arcPage >= totalPages - 1}
										style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:arcPage>=totalPages-1?'#cbd5e1':'#64748b',cursor:arcPage>=totalPages-1?'default':'pointer',outline:'none',fontSize:'10px'}}>»</button>
								</div>
							</div>
						</div>
					);
				})()}
			</div>
			)}

			{/* Mobile cards */}
			{isMobileView && (
			<div style={{padding:'12px 0 4px'}}>
				{/* Tab bar — always visible on this tab. It previously lived only inside the
				    empty-state card (and the whole block was gated on !loading), so it vanished
				    while loading or when returnable products existed. Render it once at the top. */}
				{props.mobileTabsBar}
				{loading ? (
					<SpecTableLoading label="Loading…" />
				) : products.length === 0 ? (
					<div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
						<SpecTableEmpty onClear={clearReturnFilters} />
					</div>
				) : products.map((item) => (
					<div key={item.id} style={{display:'flex',marginBottom:'10px',borderRadius:'14px',border:'1px solid #eaecf2',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
						<div style={{width:'4px',flexShrink:0,background:'linear-gradient(180deg,#f97316,#ea580c)'}}/>
						<div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
							<div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px',marginBottom:'8px'}}>
								<div style={{minWidth:0}}>
									<div style={{fontSize:'11px',color:'#f97316',fontWeight:'700',marginBottom:'2px'}}>
										{item.invoice_id ? `#${item.invoice_id}` : ''}
										{item.invoice_id && item.date ? ' · ' : ''}
										{item.date || ''}

									</div>
									<div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{item.product_name}</div>
									{item.customer_name ? <div style={{fontSize:'11px',color:'#64748b',fontWeight:'600',marginTop:'1px'}}>{item.customer_name}</div> : null}
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
						<SpecTableLoading label="Loading…" />
					) : returns.length === 0 ? (
						<SpecTableEmpty onClear={clearReturnFilters} />
					) : (
						<div style={{overflowX:'auto'}}>
							<table style={{width:'100%',borderCollapse:'collapse',fontSize:'13px'}}>
								<thead><tr style={{background:'#fafbfc'}}>
									{['#','Product','Qty','Price','Total','Note','Date'].map(h=>(
										<th key={h} style={{padding:'8px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'1px solid #f1f5f9',textAlign:['Qty','Price','Total'].includes(h)?'right':'left'}}>{h}</th>
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
											<td style={{padding:'10px 14px',color:'#64748b',fontStyle:'italic',fontSize:'12px'}}>{r.note || '—'}</td>
											<td style={{padding:'10px 14px',color:'#64748b',fontSize:'12px'}}>{r.date ? new Date(r.date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '—'}</td>
										</tr>
									))}
								</tbody>
							</table>
						</div>
					)}
				</div>
			)}

			{/* Return Modal */}
			{returnModal.show && returnModal.item && (
				<>
					<div style={{position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.4)',zIndex:99998}} onClick={closeReturnModal}></div>
					<div style={{position:'fixed',top:'50%',left:'50%',transform:'translate(-50%,-50%)',background:'#fff',borderRadius:'16px',width:'440px',maxWidth:'90vw',zIndex:99999,boxShadow:'0 20px 60px rgba(0,0,0,0.2)',overflow:'hidden'}}>
						{/* Modal Header */}
						<div style={{padding:'18px 22px',borderBottom:'1px solid #f1f5f9',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
							<div>
								<div style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Return Product</div>
								<div style={{fontSize:'12px',color:'#94a3b8',marginTop:'2px'}}>{returnModal.item.product_name}</div>
							</div>
							<button onClick={closeReturnModal} style={{width:'32px',height:'32px',borderRadius:'8px',border:'1px solid #e2e8f0',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',color:'#64748b',fontSize:'16px'}}>×</button>
						</div>
						{/* Modal Body */}
						<div style={{padding:'18px 22px'}}>
							{/* Info */}
							<div style={{display:'flex',gap:'10px',marginBottom:'16px'}}>
								<div style={{flex:1,background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
									<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Invoice</div>
									<div style={{fontSize:'15px',fontWeight:'800',color:'#f97316',marginTop:'2px'}}>#{returnModal.item.invoice_id}</div>
								</div>
								<div style={{flex:1,background:'#f0fdf4',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
									<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Available</div>
									<div style={{fontSize:'15px',fontWeight:'800',color:'#16a34a',marginTop:'2px'}}>{returnModal.item.available}</div>
								</div>
								<div style={{flex:1,background:'#f8fafc',borderRadius:'10px',padding:'10px 14px',textAlign:'center'}}>
									<div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.4px'}}>Unit Price</div>
									<div style={{fontSize:'15px',fontWeight:'800',color:'#1e293b',marginTop:'2px'}}>{currency} {Number(returnModal.item.unit_price).toFixed(2)}</div>
								</div>
							</div>
							{/* Quantity */}
							<div style={{marginBottom:'14px'}}>
								<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Quantity to Return <span style={{color:'#dc2626'}}>*</span> <span style={{fontSize:'11px',color:'#dc2626',fontWeight:'700',textTransform:'none',letterSpacing:'0',marginLeft:'4px'}}>max {returnModal.item.available}</span></label>
								<input type="number" min="1" max={returnModal.item.available} value={returnQty}
									onChange={(e) => { setReturnQty(e.target.value); setCustomPrice(''); }}
									style={{width:'100%',height:'42px',borderRadius:'10px',border: Number(returnQty) > returnModal.item.available ? '2px solid #dc2626' : '1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'700',textAlign:'center',outline:'none',color:'#1e293b',boxSizing:'border-box',background: Number(returnQty) > returnModal.item.available ? '#fef2f2' : '#fff'}}
									onFocus={e => { if(Number(returnQty) <= returnModal.item.available) e.target.style.borderColor='#f97316'; }}
									onBlur={e => { if(Number(returnQty) <= returnModal.item.available) e.target.style.borderColor='#e2e8f0'; }}
								/>
								{Number(returnQty) > returnModal.item.available && (
									<div style={{marginTop:'6px',fontSize:'12px',color:'#dc2626',fontWeight:'600',display:'flex',alignItems:'center',gap:'5px'}}>
										<i className="fa fa-exclamation-triangle" style={{fontSize:'11px'}}></i>
										You purchased {returnModal.item.quantity} and have {returnModal.item.available} available — cannot return {returnQty}
									</div>
								)}
							</div>
							{/* Note */}
							<div style={{marginBottom:'14px'}}>
								<label style={{fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'6px',display:'block'}}>Reason for Return <span style={{color:'#dc2626'}}>*</span></label>
								<textarea value={returnNote} onChange={(e) => setReturnNote(e.target.value)}
									placeholder="e.g. Damaged, expired, wrong product..."
									rows="3"
									style={{width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',padding:'10px 14px',outline:'none',color:'#1e293b',boxSizing:'border-box',resize:'none'}}
									onFocus={e => e.target.style.borderColor='#f97316'}
									onBlur={e => e.target.style.borderColor='#e2e8f0'}
								/>
							</div>
							{/* Refund Amount */}
							<div style={{background:'#fef2f2',borderRadius:'10px',padding:'14px 16px',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
								<span style={{fontSize:'13px',fontWeight:'600',color:'#64748b'}}>Refund Amount</span>
								<div style={{display:'flex',alignItems:'center',gap:'6px'}}><span style={{fontSize:'14px',fontWeight:'700',color:'#dc2626'}}>{currency}</span><input type="number" min="0" step="0.01" value={customPrice !== '' ? customPrice : autoPrice.toFixed(2)} onChange={e => setCustomPrice(e.target.value)} style={{width:'100px',height:'36px',borderRadius:'8px',border:'1.5px solid #fca5a5',fontSize:'16px',fontWeight:'800',color:'#dc2626',textAlign:'right',padding:'0 8px',outline:'none',background:'#fef2f2'}} onFocus={e=>e.target.style.borderColor='#dc2626'} onBlur={e=>e.target.style.borderColor='#fca5a5'} /></div>
							</div>
						</div>
						{/* Modal Footer */}
						<div style={{padding:'14px 22px',borderTop:'1px solid #f1f5f9',display:'flex',gap:'10px'}}>
							<button onClick={closeReturnModal} style={{flex:1,height:'42px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer'}}>Cancel</button>
							<button onClick={handleReturn} disabled={submitting || Number(returnQty) > returnModal.item.available || Number(returnQty) <= 0 || !returnNote.trim()} style={{flex:1,height:'42px',borderRadius:'10px',border:'none',background: (submitting || Number(returnQty) > returnModal.item.available || Number(returnQty) <= 0 || !returnNote.trim()) ? '#e2e8f0' : '#dc2626',color: (submitting || Number(returnQty) > returnModal.item.available || Number(returnQty) <= 0 || !returnNote.trim()) ? '#94a3b8' : '#fff',fontSize:'13px',fontWeight:'700',cursor: (submitting || Number(returnQty) > returnModal.item.available || Number(returnQty) <= 0 || !returnNote.trim()) ? 'not-allowed' : 'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px'}}>
								{submitting ? <><i className="fa fa-spinner fa-spin"></i> Processing...</> : <><i className="fa fa-undo"></i> Confirm Return</>}
							</button>
						</div>
					</div>
				</>
			)}
		</div>
	);
}

export default function CustomersReturnApp(props) {
	const dispatch = useDispatch();
	const [showHistory, setShowHistory] = useState(true);
	const [totals, setTotals] = useState({ all: 0, paid: 0, pending: 0 });
	const [returnTotal, setReturnTotal] = useState(0);
	const [productCount, setProductCount] = useState(0);
	const [historyRefreshKey, setHistoryRefreshKey] = useState(0);
    const customers = useSelector(state => state.customers.customers);
	const currentCustomer = useSelector(state => state.customers.currentCustomer);
	
	const loadList = async() => {
		try {
			const response = await axios.get(props.productsListApi);
			if (response.data.success === true) {
				dispatch(setProducts(response.data.payload));
			}else{
			
			}
		} catch (err) {
			
		}finally{

		}
	}
	
	useEffect(() => {
		loadList()
    },[])
	
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
	<>
	<div style={props.noHeader ? {} : { background: '#fff', borderRadius: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', border: '1px solid #f1f5f9', overflow: 'visible', marginBottom: '16px' }}>
		{/* Header */}
		{props.noHeader ? null : (
			<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 24px 14px' }}>
				<div style={{ width: '44px', height: '41px', borderRadius: '14px', background: 'rgb(234, 88, 12)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 3px 12px rgba(234,88,12,0.25)', flexShrink: 0 }}>
					<i className="fa fa-undo" style={{ color: '#fff', fontSize: '20px' }}></i>
				</div>
				<div>
					<h1 style={{ fontSize: '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Customer Return</h1>
					<p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Manage customer return records</p>
				</div>
			</div>
		)}
		{/* Content — filters always visible, tabs below, form when !showHistory */}
		<ReturnHistoryApp noCard hideTable={!showHistory} type="customer" returnsApi={props.invoicesReturnsApi} entitiesApi={props.customersListApi} currency={props.currency} onBack={() => setShowHistory(false)} onTotals={setTotals} creditBalanceApi="/customer_return/view/credit-balance/" creditBalanceAllApi="/customer_return/view/credit-balance-all" refreshKey={historyRefreshKey} printUrl={showHistory ? props.printUrl : props.returnablePrintUrl} excelUrl={showHistory ? props.excelUrl : props.returnableExcelUrl} onEntityChange={v => { dispatch(setCurrentCustomer(v?.value || '')); dispatch(setCurrentCustomerInfo(v ? { id: v.value, name: v.label } : {})); }} onDateChange={(from,to) => { dispatch(setDate(from)); dispatch(setToDate(to)); }} tabsBar={tabsMarkup} />
		<div style={{ display: showHistory ? 'none' : 'block' }}>
			<CustomerProductsTable noCard {...props} mobileTabsBar={tabsMarkup} returnableExcelUrl={props.returnableExcelUrl} onSuccess={() => { setShowHistory(true); setHistoryRefreshKey(k => k + 1); }} onCount={setProductCount} />
			{currentCustomer && <List {...props} />}
		</div>
	</div>
	<ToastContainer autoClose={3000} />
	</>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('customers-return-app')) {
    const id = "customers-return-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<CustomersReturnApp {...props} />
		</Provider>
    );
}

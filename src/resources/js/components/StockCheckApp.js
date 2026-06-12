import React, { useEffect, useState,useMemo,useRef } from 'react';
import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray,Formik, Form, Field } from 'formik';
import DataTable from 'react-data-table-component';
import { Modal, Button, Spinner } from "react-bootstrap";
import * as Yup from 'yup';
import axios from 'axios';
import logger from 'redux-logger';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import _ from "lodash";
import { ToastContainer, toast } from 'react-toastify';
import SpecPagination from "./../elements/SpecPagination";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecTableEmpty from "./../elements/SpecTableEmpty";
import { useToast } from "./../hooks/useToast";
import useDataTableStyles from "../hooks/useDataTableStyles";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";


const productsSlice = createSlice({
    name: 'products',
    initialState: {
		//products:[],
		date:new Date(Date.now()).toISOString().slice(0, 10),
		to_date:new Date(Date.now()).toISOString().slice(0, 10),
		modes:[{label:'All',value:'show-all'},
			{label:'Short (\u2212)',value:'short'},
			{label:'Excess (+)',value:'excess'},
			{label:'Mismatch Only',value:'both'}
		],
		mode:{label:'All',value:'show-all'},
		loading: false,
		refreshCount:0
	},
    reducers: {
        //setProducts: (state, action) => { state.products = action.payload },
		setDate: (state, action) => { state.date = action.payload },
		setMode: (state, action) => { state.mode = action.payload },
		setToDate: (state, action) => { state.to_date = action.payload },
		setLoading: (state, action) => { state.loading = action.payload },
		setRefreshCount: (state, action) => { state.refreshCount = action.payload },
    }
});

const {
	//setProducts, 
	setRefreshCount,setDate,setLoading,setToDate,setMode} = productsSlice.actions;

const store = configureStore({
    reducer: { products: productsSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});


function CreateForm(props) {
	
	const dispatch = useDispatch();
	const {products,date,to_date, modes, mode} = useSelector(state => state.products);
	
	const handleChange = (e) => {
		dispatch(setDate(e.target.value))
	}
	const handleChangeToDate = (e) => {
		dispatch(setToDate(e.target.value))
	}
	
	const handleModeChange = (e) => {
		dispatch(setMode(e));
	}
	
	const matchingSelectStyles = {
		control: (base, state) => ({
			...base,
			border: '1.5px solid #e5e7eb',
			borderRadius: '12px',
			boxShadow: state.isFocused ? '0 0 0 0.2rem rgba(234,88,12,0.25)' : '0 1px 4px rgba(0,0,0,0.06)',
			minHeight: '44px',
			'&:hover': { borderColor: 'rgb(234, 88, 12)' },
			borderColor: state.isFocused ? 'rgb(234, 88, 12)' : '#e5e7eb',
		}),
		option: (base, state) => ({
			...base,
			backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : '#333',
			cursor: 'pointer',
			fontSize: '13px',
		}),
		placeholder: (base) => ({...base,fontSize:'13px',color:'#9ca3af'}),
		singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'500',color:'#111827'}),
		input: (base) => ({...base,fontSize:'13px'}),
	};

	return (
		<div style={{borderRadius:'16px',border:'1px solid #f0f0f0',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',background:'#fff',padding:'14px 20px'}}>
			<div style={{display:'flex',alignItems:'center',gap:'14px'}}>
				{/* Date Range - compact */}
				<div style={{display:'inline-flex',alignItems:'center',background:'#fff',border:'1.5px solid #e5e7eb',borderRadius:'10px',overflow:'visible',height:'42px',flexShrink:0}}>
					<div style={{padding:'0 10px 0 12px',borderRight:'1px solid #e5e7eb',display:'flex',alignItems:'center',gap:'10px',height:'100%'}}>
						<span style={{fontSize:'9px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase',flexShrink:0}}>
							<i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',marginRight:'2px',fontSize:'9px'}}></i>From
						</span>
						<OrangeDatePicker value={date} onChange={(val) => dispatch(setDate(val))} />
					</div>
					<div style={{display:'flex',alignItems:'center',padding:'0 6px',color:'rgb(234, 88, 12)',fontSize:'10px',opacity:0.5}}>
						<i className="fa fa-long-arrow-right"></i>
					</div>
					<div style={{padding:'0 12px 0 10px',display:'flex',alignItems:'center',gap:'10px',height:'100%'}}>
						<span style={{fontSize:'9px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase',flexShrink:0}}>
							<i className="fa fa-calendar" style={{color:'rgb(234, 88, 12)',marginRight:'2px',fontSize:'9px'}}></i>To
						</span>
						<OrangeDatePicker value={to_date} onChange={(val) => dispatch(setToDate(val))} />
					</div>
				</div>
				{/* Mode Select */}
				<div style={{width:'200px',flexShrink:0}}>
					<Select styles={{
						...matchingSelectStyles,
						control: (base, state) => ({
							...matchingSelectStyles.control(base, state),
							minHeight:'42px',height:'42px',borderRadius:'10px',
						}),
						valueContainer: (base) => ({...base,height:'42px',padding:'0 12px'}),
						indicatorsContainer: (base) => ({...base,height:'42px'}),
					}}
						options={modes}
						isSearchable
						value={mode}
						onChange={(e) => handleModeChange(e)}
						classNamePrefix="react-select"
					/>
				</div>
			</div>
		</div>
	)
}

function List(props) {
  const { noCard } = props;
  const [products, setProducts] = useState([]);
  const [searchText, setSearchText] = useState("");
  const [mobileView, setMobileView] = useState('card');
  const [stockFilter, setStockFilter] = useState({label:'In Stock',value:'in-stock'});
  const stockFilterOptions = [{label:'All Products',value:'all'},{label:'In Stock',value:'in-stock'},{label:'No Stock',value:'no-stock'}];
  const isMobile = typeof window !== 'undefined' && window.innerWidth < 768;
  const [filterPopupOpen, setFilterPopupOpen] = useState(false);
  // Pending filter state (before Apply)
  const [pendingMode, setPendingMode] = useState(null);
  const [pendingFrom, setPendingFrom] = useState(null);
  const [pendingTo, setPendingTo] = useState(null);
  const [activeDateField, setActiveDateField] = useState(null); // 'from' | 'to' | null
  const [expandedCard, setExpandedCard] = useState(null);
  const [summaryOpen, setSummaryOpen] = useState(false);
  const [downloadingExcel, setDownloadingExcel] = useState(false);
  // Tooltip portal node — lifecycle tied to mount/unmount via useEffect so React
  // never tries to remove a stale node ("removeChild not a child of this node").
  const tooltipPortalRef = useRef(null);
  const [tooltipPortalReady, setTooltipPortalReady] = useState(false);
  useEffect(() => {
    const d = document.createElement("div");
    document.body.appendChild(d);
    tooltipPortalRef.current = d;
    setTooltipPortalReady(true);
    return () => {
      try { if (d.parentNode) d.parentNode.removeChild(d); } catch (_) { /* already detached */ }
      tooltipPortalRef.current = null;
    };
  }, []);

  const fmtDisplay = (v) => {
    if (!v) return '';
    try { const d = new Date(v+'T00:00:00'); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); } catch { return v; }
  };
  const handleDateSelect = (selectedDate) => {
    if (!selectedDate) return;
    const y = selectedDate.getFullYear();
    const m = String(selectedDate.getMonth()+1).padStart(2,'0');
    const d = String(selectedDate.getDate()).padStart(2,'0');
    const str = `${y}-${m}-${d}`;
    if (activeDateField === 'from') { setPendingFrom(str); setActiveDateField('to'); }
    else if (activeDateField === 'to') { setPendingTo(str); setActiveDateField(null); }
  };
  const calendarDate = activeDateField === 'from'
    ? (pendingFrom ? new Date(pendingFrom+'T00:00:00') : new Date())
    : (pendingTo ? new Date(pendingTo+'T00:00:00') : new Date());

  const { notifySuccess, notifyError } = useToast();
  const dispatch = useDispatch();
  const { date, to_date, mode, modes } = useSelector((state) => state.products);
  const clearFilters = () => {
    const today = new Date(Date.now()).toISOString().slice(0, 10);
    setSearchText('');
    setStockFilter({ label: 'All Products', value: 'all' });
    // Show ALL stock-check data: widen the range to all-time (far-past → today)
    // instead of today→today, so every product with any stock history appears.
    dispatch(setDate('2000-01-01'));
    dispatch(setToDate(today));
    if (modes && modes[0]) dispatch(setMode(modes[0]));
  };
  const isActiveTab = useRef(true); // stock-check is the default active tab
  const [fetching, setFetching] = useState(false);
  const hasFetchedOnce = useRef(false);

  const [popupData, setPopupData] = useState({ show: false });

  // ✅ Fetch products
  const fetchProducts = async (silent = false) => {
    // Show skeleton only on the very first fetch (no data yet, never fetched)
    const showSkeleton = !hasFetchedOnce.current;
    try {
      if (showSkeleton) setFetching(true);
      const response = await axios.post(props.listApi, { date:date, to_date:to_date, mode:mode.value });
      setProducts(Array.isArray(response.data.payload) ? response.data.payload : []);
      hasFetchedOnce.current = true;
    } catch (error) {
      console.error("Error fetching products:", error);
      if (!silent && isActiveTab.current) notifyError("Failed to fetch products");
    } finally {
      setFetching(false);
    }
  };

  useEffect(() => {
    if (date && to_date) fetchProducts();
  }, [date,to_date,mode]);

  useEffect(() => {
    const handleStockUpdated = () => fetchProducts(true); // silent — triggered by other tabs
    const handleTabActivated = (e) => {
      if (e.detail?.tab === 'stock-check') { isActiveTab.current = true; fetchProducts(); }
      else { isActiveTab.current = false; }
    };
    window.addEventListener('stock-updated', handleStockUpdated);
    window.addEventListener('sc-tab-activated', handleTabActivated);
    return () => {
      window.removeEventListener('stock-updated', handleStockUpdated);
      window.removeEventListener('sc-tab-activated', handleTabActivated);
    };
  }, [date, to_date, mode]);
  
	const handleOpenPopup = (params) => {
	  setPopupData(params);
	};

  // ✅ Search handler
  const handleSearch = (value) => {
    setSearchText(value);
  };

  // 🔹 Find product by ID (safely)
  const findProductIndexById = (products, id) =>
    products.findIndex((p) => p.id === id);

	// 🔹 Save
	const handleSave = async (productId, values, setFieldValue) => {
		const index = findProductIndexById(values.products, productId);
		if (index === -1) return;

		const product = values.products[index];
		try {
		  await axios.post(props.saveOneApi, {
			product_id: product.id,
			stock: parseInt(product.stock),
			date,
		  });
		  notifySuccess(`Saved ${product.name}`);
		  setFieldValue(`products[${index}].isSaved`, true);
		} catch (err) {
		  console.error(err);
		  notifyError("Error saving product");
		}
	};

	// 🔹 Update
	const handleUpdate = async (productId, values, setFieldValue) => {
		const index = findProductIndexById(values.products, productId);
		if (index === -1) return;

		const product = values.products[index];
		try {
		  await axios.post(props.editApi, {
			product_id: product.id,
			stock: product.stock,
			date,
		  });
		  notifySuccess(`Updated ${product.name}`);
		  setFieldValue(`products[${index}].isSaved`, true);
		} catch (err) {
		  console.error(err);
		  notifyError("Error updating product");
		}
	};
  
	const handleSaveAll = async () => {
		console.log(values)
	}

	const baseStyles = useDataTableStyles();
	const customStyles = useMemo(() => ({
		...baseStyles,
		headRow: { style: { ...baseStyles.headRow?.style, backgroundColor:'#fafbfc', borderBottom:'2px solid #eef2f7', minHeight:'44px' } },
		headCells: { style: { ...baseStyles.headCells?.style, padding:'10px 12px', color:'#64748b', fontSize:'11px', fontWeight:'700', letterSpacing:'0.7px' } },
		cells: { style: { ...baseStyles.cells?.style, padding:'14px 12px' } },
		rows: {
			style: { ...baseStyles.rows?.style, borderBottomColor:'#f5f5f5', minHeight:'52px' },
			highlightOnHoverStyle: { backgroundColor:'#fff7ed', borderBottomColor:'#fed7aa', outlineColor:'#fed7aa' },
			stripedStyle: { backgroundColor:'#fafbfc' },
		},
	}), []);
	const hs = {fontSize:'11px',fontWeight:'700',color:'#64748b',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace:'nowrap',cursor:'default'};
	const [tipState, setTipState] = useState({show:false, text:'', x:0, y:0, align:'center'});
	const showTip = (e, text) => {
		const rect = e.currentTarget.getBoundingClientRect();
		const x = rect.left + rect.width/2;
		const vw = window.innerWidth;
		const align = x > vw - 150 ? 'right' : x < 150 ? 'left' : 'center';
		setTipState({show:true, text, x, y: rect.bottom + 6, align});
	};
	const hideTip = () => setTipState(s => ({...s, show:false}));
	const hsTip = (label, tip) => (
		<span style={{...hs, cursor:'default'}}
			onMouseEnter={(e) => showTip(e, tip)}
			onMouseLeave={hideTip}>
			{label}
		</span>
	);
	const numStyle = {fontSize:'13px',color:'#2563eb',fontWeight:'700',cursor:'pointer'};
	const zeroStyle = {fontSize:'13px',color:'#d1d5db',fontWeight:'500',cursor:'pointer'};
	const numW = '75px';
	const numCell = (val, onClick) => {
		const n = Number(val);
		if (n === 0) return <span style={zeroStyle} onClick={onClick}>0</span>;
		return <span style={numStyle} onClick={onClick}>{n}</span>;
	};

	const computeTotals = (data) => {
		if (!data || data.length === 0) return [];
		const total = {
			product_id: "", // ignore first column
			product_name: "Total", // label in 2nd column
			os: _.sumBy(data, (row) => Number(row.os) || 0),
			ns: _.sumBy(data, (row) => _.sum(row.ns) || 0),
			sales: _.sumBy(data, (row) => _.sum(row.sales) || 0),
			crtn: _.sumBy(data, (row) => _.sum(row.crtn) || 0),
			dmps: _.sumBy(data, (row) => _.sum(row.dmps) || 0),
			srtn: _.sumBy(data, (row) => _.sum(row.srtn) || 0),
			cl_stock: _.sumBy(data, (row) => Number(row.cl_stock) || 0),
		};
		// you can compute Result total if needed
		total.result =
		total.os +
		total.ns -
		total.sales +
		total.crtn -
		total.srtn -
		total.dmps -
		total.cl_stock;

		return [...data, total];
	};
	//const updatedData = computeTotals(data);

	// ✅ Table columns
	const calcExpected = (row) => parseInt(row.os) + _.sum(row.ns) - _.sum(row.sales) + _.sum(row.crtn) - _.sum(row.srtn) - _.sum(row.dmps);
	const hasClosing = (row) => row.cl_stock !== null && row.cl_stock !== undefined && row.cl_stock !== '' && row.cl_stock !== 0 && row.cl_stock !== '0';
	const calcDiff = (row) => hasClosing(row) ? Number(row.cl_stock) - calcExpected(row) : 0;

	const printStockReport = () => {
		const stockVal = stockFilter?.value || 'all';
		const url = props.printUrl + '?date=' + date + '&stock=' + stockVal;
		window.open(url, '_blank');
	};

	const downloadExcel = async () => {
		if (downloadingExcel) return;
		setDownloadingExcel(true);
		const stockVal = stockFilter?.value || 'all';
		const base = props.excelUrl || '/excel/stock_check';
		const url = base + '?date=' + date + '&to_date=' + (to_date || date) + '&stock=' + stockVal + '&mode=' + (mode?.value || 'show-all');
		try {
			// Fetch the file as a blob so the loading spinner stays on until the
			// file is actually ready and the browser download is triggered.
			const response = await axios.get(url, { responseType: 'blob' });
			const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
			const link = document.createElement('a');
			link.href = blobUrl;
			link.setAttribute('download', 'stock_check-' + (date || 'export') + '.xlsx');
			document.body.appendChild(link);
			link.click();
			link.remove();
			window.URL.revokeObjectURL(blobUrl);
		} catch (err) {
			console.error('Stock check download failed', err);
		} finally {
			setDownloadingExcel(false);
		}
	};

	const getColumns = (values, setFieldValue) => [
	{
	name: hsTip('Sl.No', 'Serial Number'),
	cell: (row, index) => <span style={{fontSize:'12px',color:'#94a3b8'}}>{index + 1}</span>,
	width: '65px', grow: 0,
	},
	{
	name: hsTip('Product', 'Product Name'),
	selector: (row) => row.product_name,
	cell: (row) => <span style={{fontSize:'13px',color:'#111827',fontWeight:'600'}}>{row.product_name}</span>,
	sortable: true, grow: 2, minWidth: '140px',
	},
	{
	name: hsTip('O.S', 'Opening Stock — Stock carried from previous day'),
	selector: (row) => Number(row.os),
	cell: (row) => numCell(row.os, () => handleOpenPopup({ type:"opening_stock", show:true, title:"Opening Stock", productName:row.product_name, apiUrl:props.openingStockApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('N.S', 'New Stock — Purchased today'),
	selector: (row) => _.sum(row.ns),
	cell: (row) => numCell(_.sum(row.ns), () => handleOpenPopup({ type:"new_stock", show:true, title:"Purchases", productName:row.product_name, apiUrl:props.newStockApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('Sales', 'Total units sold today'),
	selector: (row) => _.sum(row.sales),
	cell: (row) => numCell(_.sum(row.sales), () => handleOpenPopup({ type:"sales", show:true, title:"Sales", productName:row.product_name, apiUrl:props.salesApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('C.RTN', 'Customer Returns — Stock returned by customers'),
	selector: (row) => _.sum(row.crtn),
	cell: (row) => numCell(_.sum(row.crtn), () => handleOpenPopup({ type:"customer_return", show:true, title:"Customer Return", productName:row.product_name, apiUrl:props.customerReturnApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('Dumps', 'Dumped / Wasted stock'),
	selector: (row) => _.sum(row.dmps),
	cell: (row) => numCell(_.sum(row.dmps), () => handleOpenPopup({ type:"dump", show:true, title:"Dumps", productName:row.product_name, apiUrl:props.dumpsApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('S.RTN', 'Supplier Returns — Stock returned to supplier'),
	selector: (row) => _.sum(row.srtn),
	cell: (row) => numCell(_.sum(row.srtn), () => handleOpenPopup({ type:"supplier_return", show:true, title:"Supplier Return", productName:row.product_name, apiUrl:props.supplierReturnApi, payload:{product_id:row.product_id,supplier_invoice_id:row.supplier_invoice_id,date,to_date,mode} })),
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('Stock', 'Expected Stock — O.S + N.S - Sales + C.RTN - S.RTN - Dumps'),
	selector: (row) => calcExpected(row),
	cell: (row) => {
	  const val = calcExpected(row);
	  const noClick = {fontSize:'13px',color:'#111827',fontWeight:'700',cursor:'default'};
	  return val === 0 ? <span style={{...zeroStyle,cursor:'default'}}>0</span> : <span style={noClick}>{val}</span>;
	},
	sortable: true, width: numW, grow: 0,
	},
	{
	name: hsTip('CL.STOCK', 'Closing Stock — Physical stock count saved from Stock Closing page'),
	selector: (row) => Number(row.cl_stock) || 0,
	cell: (row) => {
		if (row.cl_stock === null || row.cl_stock === undefined || row.cl_stock === 0) return <span style={{fontSize:'12px',color:'#d1d5db'}}>—</span>;
		return <span style={{fontSize:'13px',fontWeight:'700',color:'#1e293b'}}>{Number(row.cl_stock)}</span>;
	},
	sortable: true, width: '100px', grow: 0,
	},
	{
	name: hsTip('Result', 'Difference between Expected Stock and Closing Stock'),
	selector: (row) => calcDiff(row) ?? 0,
	cell: (row) => {
		if (!hasClosing(row)) return <span style={{fontSize:'12px',color:'#d1d5db'}}>—</span>;
		const val = calcDiff(row);
		if (val === 0) return <span style={{background:'#f0fdf4',color:'#16a34a',border:'1px solid #bbf7d0',borderRadius:'20px',padding:'3px 10px',fontSize:'11px',fontWeight:'700',whiteSpace:'nowrap',display:'inline-flex',alignItems:'center',gap:'4px'}}><i className="fa fa-check-circle" style={{fontSize:'10px'}}></i> OK</span>;
		if (val > 0) return <span style={{background:'#fefce8',color:'#ca8a04',border:'1px solid #fef08a',borderRadius:'20px',padding:'3px 10px',fontSize:'11px',fontWeight:'700',whiteSpace:'nowrap',display:'inline-flex',alignItems:'center',gap:'4px'}}><i className="fa fa-arrow-up" style={{fontSize:'9px'}}></i> Excess {val}</span>;
		return <span style={{background:'#fef2f2',color:'#dc2626',border:'1px solid #fecaca',borderRadius:'20px',padding:'3px 10px',fontSize:'11px',fontWeight:'700',whiteSpace:'nowrap',display:'inline-flex',alignItems:'center',gap:'4px'}}><i className="fa fa-arrow-down" style={{fontSize:'9px'}}></i> Short {Math.abs(val)}</span>;
	},
	sortable: true, width: '110px', grow: 0,
	},
	{
	name: hsTip('Note', 'Explanation of the result'),
	cell: (row) => {
		if (!hasClosing(row)) return <span style={{fontSize:'11px',color:'#d1d5db'}}>—</span>;
		const val = calcDiff(row);
		if (val === 0) return <span style={{fontSize:'11px',color:'#94a3b8'}}>Matched</span>;
		if (val > 0) return <span style={{fontSize:'11px',color:'#ca8a04'}}>{val} extra in physical count</span>;
		return <span style={{fontSize:'11px',color:'#dc2626'}}>{Math.abs(val)} missing from physical count</span>;
	},
	width: '160px', grow: 0,
	},
	];

  // ✅ Filtered data
  const filteredProducts = (Array.isArray(products) ? products : []).filter((item) => {
    if (searchText) {
      const q = searchText.toLowerCase();
      const matchesProduct  = (item.product_name || '').toLowerCase().includes(q);
      const matchesSupplier = (item.supplier_name || '').toLowerCase().includes(q);
      if (!matchesProduct && !matchesSupplier) return false;
    }
    // Only show products with Opening Stock > 0 OR any activity in range
    if (parseInt(item.os) <= 0 && _.sum(item.ns) <= 0 && _.sum(item.sales) <= 0 && _.sum(item.crtn) <= 0 && _.sum(item.srtn) <= 0 && _.sum(item.dmps) <= 0) return false;
    if (mode && mode.value && mode.value !== 'show-all') {
      const diff = calcDiff(item);
      if (mode.value === 'short' && diff >= 0) return false;
      if (mode.value === 'excess' && diff <= 0) return false;
      if (mode.value === 'both' && diff === 0) return false;
    }
    return true;
  }).sort((a, b) => {
    const da = a.last_activity_date || '';
    const db = b.last_activity_date || '';
    return da.localeCompare(db);
  });

  // Summary stats
  const stats = useMemo(() => {
    let ok = 0, excess = 0, short = 0;
    filteredProducts.forEach(row => {
      const expected = calcExpected(row);
      // Count negative expected stock as short even without closing stock
      if (!hasClosing(row)) {
        if (expected < 0) short++;
        return;
      }
      const diff = calcDiff(row);
      if (diff === 0) ok++;
      else if (diff > 0) excess++;
      else short++;
    });
    return { total: filteredProducts.length, ok, excess, short };
  }, [filteredProducts]);

  // Conditional row styles - red bg for negative stock
  const conditionalRowStyles = [
    { when: row => (parseInt(row.os) + _.sum(row.ns) - _.sum(row.sales) + _.sum(row.crtn) - _.sum(row.srtn) - _.sum(row.dmps)) < 0, style: { backgroundColor: '#fef2f2' } },
  ];

  return (
    <div style={noCard ? {overflow:'visible'} : {borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)'}}>
	  <Formik
		initialValues={{ products }}
		enableReinitialize
		onSubmit={async (values, { setSubmitting, resetForm }) => {
		  try {
			// Make API call
			await axios.post(props.saveAllApi, {date:date, products:values.products});

			// Success notification
			notifySuccess("All products updated successfully!");

			// Optionally reset form or re-fetch data
			// resetForm();
		  } catch (err) {
			console.error(err);
			notifyError("Error updating products");
		  } finally {
			setSubmitting(false); // ✅ Always end submission
		  }
		}}
	  >
    {({ values, setFieldValue, isSubmitting }) => (<>
      <style>{`.inv-date-picker-wrap:hover{border-color:rgb(234, 88, 12) !important;background:#fff !important;}.inv-date-picker-wrap:focus-within{border-color:rgb(234, 88, 12) !important;box-shadow:0 0 0 3px rgba(234,88,12,0.08) !important;background:#fff !important;}.inv-date-picker{padding:0;font-size:13px;font-weight:600;border:none;height:100%;color:#1e293b;outline:none;cursor:pointer;background:transparent;width:110px;letter-spacing:0.2px;-webkit-appearance:none;appearance:none;}@media(max-width:767px){.sc-filter-grid{display:flex !important;flex-direction:row !important;flex-wrap:wrap !important;padding:14px 16px !important;gap:10px !important;border-bottom:1.5px solid #f0f0f0 !important;background:#fff !important;}.sc-filter-search{width:100%;order:-1;}.sc-filter-search label,.sc-filter-grid>div>label{font-size:9px !important;margin-bottom:4px !important;letter-spacing:0.5px !important;color:#94a3b8 !important;}.sc-filter-grid>div:not(.sc-filter-search){flex:1;min-width:0;overflow:hidden;}.sc-filter-grid input{height:38px !important;font-size:12px !important;}.sc-filter-grid .css-b62m3t-container,.sc-filter-grid [class*="-container"]{width:100% !important;}.sc-filter-grid [class*="-control"]{width:100% !important;min-width:0 !important;}.sc-info-bar{margin-top:0 !important;padding:10px 16px !important;background:#f8fafc !important;border-radius:0 !important;border-bottom:1.5px solid #f0f0f0 !important;flex-direction:row !important;justify-content:space-between !important;align-items:center !important;}.sc-info-bar span{font-size:10px !important;}.sc-info-bar button{height:28px !important;font-size:11px !important;padding:0 10px !important;}}@keyframes scSlideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}.sc-filter-date-wrap .orange-datepicker-input,.sc-filter-date-wrap .inv-date-picker{border:none !important;outline:none !important;box-shadow:none !important;background:transparent !important;width:100% !important;font-size:13px !important;font-weight:600 !important;color:#1e293b !important;caret-color:transparent !important;cursor:pointer !important;}.sc-filter-date-wrap .react-datepicker__input-container{width:100% !important;}.sc-filter-date-wrap .react-datepicker__input-container input{border:none !important;outline:none !important;box-shadow:none !important;background:transparent !important;width:100% !important;font-size:13px !important;font-weight:600 !important;color:#1e293b !important;}.sc-filter-sheet *:focus{outline:none !important;box-shadow:none !important;}.sc-filter-sheet button:focus{outline:none !important;border-color:rgb(234, 88, 12) !important;}`}</style>
      <Form>
        {/* Summary Cards + Actions */}
        {isMobile ? (
          <div style={{padding:'0'}}>
            {/* Collapsed summary bar — always visible */}
            <div onClick={()=>setSummaryOpen(v=>!v)} style={{borderRadius: summaryOpen ? '16px 16px 0 0' : '16px',border:'1px solid #eaecf2',borderBottom: summaryOpen ? '1px solid #f0f0f0' : '1px solid #eaecf2',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',padding:'12px 14px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer',marginBottom: summaryOpen ? 0 : '14px'}}>
              <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'rgb(234, 88, 12)'}}/>
                <span style={{fontSize:'10px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Stock Summary</span>
              </div>
              <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                <div style={{display:'flex',gap:'8px'}}>
                  {[{v:stats.total,c:'#3b82f6'},{v:stats.ok,c:'#16a34a'},{v:stats.excess,c:'#d97706'},{v:stats.short,c:'#dc2626'}].map((s,i)=>(
                    <span key={i} style={{fontSize:'12px',fontWeight:'700',color:s.c}}>{s.v}</span>
                  ))}
                </div>
                <i className={'fa fa-chevron-'+(summaryOpen?'up':'down')} style={{fontSize:'9px',color:'#9ca3af'}}/>
              </div>
            </div>
            {/* Expanded summary */}
            {summaryOpen && (
              <div style={{borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',background:'#fff',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',marginBottom:'14px'}}>
                <div style={{display:'flex',padding:'10px 16px 12px'}}>
                  {[
                    {label:'Total',value:stats.total},
                    {label:'Matched',value:stats.ok},
                    {label:'Excess',value:stats.excess},
                    {label:'Short',value:stats.short},
                  ].map((c,i,arr)=>(
                    <React.Fragment key={c.label}>
                      <div style={{flex:1}}>
                        <div style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'4px'}}>{c.label}</div>
                        <div style={{fontSize:'24px',fontWeight:'700',color:'#111827',lineHeight:1,letterSpacing:'-1px'}}>{c.value}</div>
                      </div>
                      {i < arr.length-1 && <div style={{width:'1px',background:'#e5e7eb',margin:'0 8px',alignSelf:'stretch'}}/>}
                    </React.Fragment>
                  ))}
                </div>
                <div style={{height:'1px',background:'#e5e7eb',margin:'0 16px'}}/>
                <div style={{padding:'8px 16px'}}>
                  <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'4px'}}>
                    <span style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase'}}>Match Rate</span>
                    <span style={{fontSize:'10px',color:'#9ca3af',fontWeight:'600'}}>{stats.total>0?Math.round(stats.ok/stats.total*100):0}%</span>
                  </div>
                  <div style={{height:'3px',borderRadius:'99px',background:'#e5e7eb',overflow:'hidden'}}>
                    <div style={{height:'100%',width:(stats.total>0?stats.ok/stats.total*100:0)+'%',borderRadius:'99px',background:'rgb(234, 88, 12)'}}/>
                  </div>
                </div>
              </div>
            )}
          </div>
        ) : (
          <div style={{padding:'18px 20px 16px',background:'#fff',borderBottom:'1px solid #f1f5f9'}}>

            {/* Summary Cards */}
            <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:'10px',marginBottom:'14px'}}>
              {[
                {label:'Total Products',value:stats.total,icon:'fa-cubes',color:'#3b82f6',light:'#eff6ff'},
                {label:'Matched',value:stats.ok,icon:'fa-check-circle',color:'#16a34a',light:'#f0fdf4'},
                {label:'Excess Stock',value:stats.excess,icon:'fa-arrow-up',color:'#d97706',light:'#fffbeb'},
                {label:'Short Stock',value:stats.short,icon:'fa-arrow-down',color:'#dc2626',light:'#fef2f2'},
              ].map(c => (
                <div key={c.label} style={{display:'flex',alignItems:'center',gap:'13px',padding:'16px 18px',borderRadius:'12px',background:'#fff',border:'1px solid #edf2f7',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                  <div style={{width:'42px',height:'42px',borderRadius:'50%',background:c.light,display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                    <i className={'fa '+c.icon} style={{color:c.color,fontSize:'16px'}}/>
                  </div>
                  <div>
                    <div style={{fontSize:'22px',fontWeight:'800',color:'#1a2332',lineHeight:1}}>{c.value}</div>
                    <div style={{fontSize:'10px',fontWeight:'600',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',marginTop:'4px'}}>{c.label}</div>
                  </div>
                </div>
              ))}
            </div>

            {/* Filter bar - separate pill sections */}
            <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
              {/* Search pill */}
              <div style={{flex:'1 1 0',display:'flex',alignItems:'center',gap:'9px',padding:'0 14px',height:'40px',border:'1.5px solid #e8edf2',borderRadius:'10px',background:'#fff',minWidth:0}}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" placeholder="Search product..."
                  value={searchText} onChange={(e) => handleSearch(e.target.value)}
                  style={{flex:1,height:'100%',border:'none',outline:'none',fontSize:'13px',color:'#374151',background:'transparent',minWidth:0}}
                />
                {searchText && (
                  <button type="button" onClick={() => handleSearch('')} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0}}>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  </button>
                )}
              </div>
              {/* Date Picker */}
              <OrangeDatePicker value={date} onChange={(val) => { dispatch(setDate(val)); dispatch(setToDate(val)); }} placeholder="Select date" standalone style={{flexShrink:0,width:'220px'}} />
              {/* Mode select pill */}
              <div style={{height:'40px',border:'1.5px solid #e8edf2',borderRadius:'10px',background:'#fff',flexShrink:0,overflow:'hidden'}}>
                <Select styles={{
                  control:(base)=>({...base,border:'none',borderRadius:'10px',minHeight:'38px',height:'38px',width:'148px',boxShadow:'none',background:'transparent',cursor:'pointer'}),
                  valueContainer:(base)=>({...base,height:'38px',padding:'0 10px'}),
                  indicatorsContainer:(base)=>({...base,height:'38px'}),
                  clearIndicator:(base)=>({...base,padding:'0 2px',color:'#cbd5e1','&:hover':{color:'rgb(234, 88, 12)'}}),
                  dropdownIndicator:(base)=>({...base,padding:'0 8px 0 0',color:'#cbd5e1','&:hover':{color:'rgb(234, 88, 12)'}}),
                  indicatorSeparator:()=>({display:'none'}),
                  menuPortal:(base)=>({...base,zIndex:9999}),
                  option:(base,state)=>({...base,fontSize:'13px',fontWeight:'500',padding:'9px 14px',backgroundColor:state.isSelected?'rgb(234, 88, 12)':state.isFocused?'#FFF5ED':'#fff',color:state.isSelected?'#fff':state.isFocused?'rgb(234, 88, 12)':'#374151',cursor:'pointer'}),
                  singleValue:(base)=>({...base,fontSize:'13px',fontWeight:'600',color:'#374151'}),
                  placeholder:(base)=>({...base,fontSize:'13px',color:'#9ca3af'}),
                }}
                  options={modes} isSearchable value={mode}
                  onChange={(e) => dispatch(setMode(e))} menuPortalTarget={typeof document !== "undefined" ? document.body : null} menuShouldBlockScroll={false} placeholder="All"
                />
              </div>
              {/* Print button */}
              <button type="button" className="icon-tip" data-tip="Print" onClick={() => printStockReport()}
                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
                onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
              >
                <i className="fa fa-print" style={{fontSize:'14px'}}></i>
              </button>
              {/* Download Excel button */}
              <button type="button" className="icon-tip" data-tip="Download Excel" onClick={() => downloadExcel()} disabled={downloadingExcel}
                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid '+(downloadingExcel?'rgb(234, 88, 12)':'#e8edf2'),background:downloadingExcel?'#fff7ed':'#fff',color:downloadingExcel?'rgb(234, 88, 12)':'#64748b',cursor:downloadingExcel?'default':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                onMouseEnter={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}}
                onMouseLeave={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}}
              >
                <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'14px'}}></i>
              </button>
            </div>

          </div>
        )}

        {/* Mobile Search + Filter bar — no background card; the search input and filter button
            carry their own borders. */}
        {isMobile && (
          <div style={{margin:'0 0 14px',display:'flex',alignItems:'center',gap:'8px'}}>
            {/* Search input */}
            <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'44px',border:'1.5px solid #e8edf2',borderRadius:'12px',background:'#fff',padding:'0 12px',minWidth:0}}>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search product..."
                value={searchText} onChange={(e) => handleSearch(e.target.value)}
                style={{flex:1,border:'none',outline:'none',fontSize:'12px',color:'#374151',background:'transparent',minWidth:0}}
              />
              {searchText && (
                <button type="button" onClick={() => handleSearch('')} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0}}>
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              )}
            </div>
            {/* Filter button — same look as Sales page (solid orange + white icon) */}
            <button type="button" onClick={() => {
              setPendingMode(mode);
              setPendingFrom(date);
              setPendingTo(to_date);
              setFilterPopupOpen(true);
            }} style={{
              flexShrink:0,height:'44px',width:'44px',borderRadius:'12px',
              border:'none',
              background:'rgb(234, 88, 12)',
              boxShadow:'0 2px 6px rgba(234,88,12,0.3)',
              cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',position:'relative',outline:'none',
            }}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
              {(filterPopupOpen || (mode && mode.value !== 'show-all') || date) && (
                <span style={{position:'absolute',top:'4px',right:'4px',width:'7px',height:'7px',borderRadius:'50%',background:'#fff',border:'1.5px solid rgb(234, 88, 12)'}}/>
              )}
            </button>
          </div>
        )}

        {/* Mobile action buttons — Print / Excel (below the search bar) */}
        {isMobile && (
          <div style={{margin:'0 0 14px',display:'flex',gap:'10px'}}>
            <button type="button" onClick={() => printStockReport()} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
              <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
            </button>
            <button type="button" onClick={() => downloadExcel()} disabled={downloadingExcel} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:downloadingExcel?'default':'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
              <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{downloadingExcel ? 'Preparing…' : 'Excel'}
            </button>
          </div>
        )}

        {/* Mobile Filter Bottom Sheet */}
        {isMobile && filterPopupOpen && (
          <>
            {/* Backdrop */}
            <div onMouseDown={() => setFilterPopupOpen(false)} onTouchStart={() => setFilterPopupOpen(false)}
              style={{position:'fixed',inset:0,background:'rgba(0,0,0,0.35)',zIndex:998}}/>
            {/* Sheet */}
            <div className="sc-filter-sheet" onMouseDown={e=>e.stopPropagation()} onTouchStart={e=>e.stopPropagation()}
              style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',animation:'scSlideUp 0.25s ease',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'92vh',overflowY:'auto'}}>
              {/* Drag handle */}
              <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
              </div>
              {/* Header */}
              <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'8px 18px 12px'}}>
                <div style={{display:'flex',alignItems:'center',gap:'7px'}}>
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Filters</span>
                </div>
                <button type="button" onClick={() => setFilterPopupOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'8px',width:'28px',height:'28px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>

              <div style={{padding:'0 18px 18px',display:'flex',flexDirection:'column',gap:'16px'}}>
                {/* Single Date */}
                <div>
                  <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Select Date</div>
                  <button type="button" onClick={()=>setActiveDateField(activeDateField==='date'?null:'date')}
                    style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid '+(activeDateField==='date'?'rgb(234, 88, 12)':'#e5e7eb'),background:activeDateField==='date'?'#fff7f0':pendingFrom?'#f9fafb':'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'8px',cursor:'pointer',outline:'none',transition:'all 0.15s'}}>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke={activeDateField==='date'?'rgb(234, 88, 12)':'#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span style={{fontSize:'12px',fontWeight:'600',color:pendingFrom?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{pendingFrom?fmtDisplay(pendingFrom):'Select date'}</span>
                  </button>
                  {activeDateField === 'date' && (
                    <div style={{marginTop:'10px',borderRadius:'14px',border:'1.5px solid rgb(234, 88, 12)',overflow:'hidden',background:'#fff'}}>
                      <div style={{padding:'8px 14px 6px',background:'#fff7f0',borderBottom:'1px solid #fed7aa',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                        <span style={{fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase'}}>Select Date</span>
                        <button type="button" onClick={()=>setActiveDateField(null)} style={{background:'none',border:'none',outline:'none',cursor:'pointer',color:'#94a3b8',padding:'2px'}}>
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                      </div>
                      <style>{`.sc-inline-cal .react-datepicker{width:100%;border:none;font-family:inherit}.sc-inline-cal .react-datepicker__month-container{width:100%;float:none}.sc-inline-cal .react-datepicker__header{background:#fff;border-bottom:1px solid #f1f5f9;padding:8px 0 4px}.sc-inline-cal .react-datepicker__day-names,.sc-inline-cal .react-datepicker__week{display:flex;justify-content:space-around}.sc-inline-cal .react-datepicker__day-name,.sc-inline-cal .react-datepicker__day{width:36px;height:36px;line-height:36px;border-radius:50%;font-size:13px;font-weight:500;margin:1px}.sc-inline-cal .react-datepicker__day-name{font-size:11px;font-weight:700;color:#94a3b8}.sc-inline-cal .react-datepicker__day:hover{background:#fff7f0;color:rgb(234, 88, 12)}.sc-inline-cal .react-datepicker__day--selected{background:rgb(234, 88, 12) !important;color:#fff !important;font-weight:700}.sc-inline-cal .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12)}.sc-inline-cal .react-datepicker__day--outside-month{color:#d1d5db}.sc-inline-cal .react-datepicker__day--disabled{color:#e5e7eb;cursor:default}.sc-inline-cal .react-datepicker__navigation{top:10px}.sc-inline-cal .react-datepicker__navigation--previous{left:10px}.sc-inline-cal .react-datepicker__navigation--next{right:10px}.sc-inline-cal .react-datepicker__current-month{font-size:14px;font-weight:700;color:#111827;padding:4px 0}`}</style>
                      <div className="sc-inline-cal">
                        <DatePicker inline selected={pendingFrom?new Date(pendingFrom+'T00:00:00'):new Date()} onChange={(d)=>{ if(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); setPendingFrom(`${y}-${m}-${dd}`);} setActiveDateField(null); }} maxDate={new Date()} />
                      </div>
                    </div>
                  )}
                </div>

                {/* Mode filter */}
                <div>
                  <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Stock Filter</div>
                  <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'8px'}}>
                    {modes.map(opt => {
                      const isActive = pendingMode && pendingMode.value === opt.value;
                      return (
                        <button key={opt.value} type="button" onClick={() => setPendingMode(opt)}
                          style={{border:'1.5px solid '+(isActive?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'10px',padding:'10px 12px',background:isActive?'#fff7f0':'#fff',cursor:'pointer',display:'flex',alignItems:'center',gap:'8px',textAlign:'left',outline:'none'}}>
                          <span style={{width:'8px',height:'8px',borderRadius:'50%',background:'rgb(234, 88, 12)',opacity:isActive?1:0.3,flexShrink:0}}/>
                          <span style={{fontSize:'12px',fontWeight:'600',color:isActive?'rgb(234, 88, 12)':'#374151'}}>{opt.label}</span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Action buttons */}
                <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px',paddingTop:'4px'}}>
                  <button type="button" onClick={() => {
                    setPendingMode({label:'All',value:'show-all'});
                    setPendingFrom(new Date().toISOString().slice(0,10));
                    setActiveDateField(null);
                  }} style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                    Clear
                  </button>
                  <button type="button" onClick={() => {
                    if (pendingMode) dispatch(setMode(pendingMode));
                    if (pendingFrom) { dispatch(setDate(pendingFrom)); dispatch(setToDate(pendingFrom)); }
                    setFilterPopupOpen(false);
                  }} style={{height:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Apply Filters
                  </button>
                </div>
              </div>
            </div>
          </>
        )}

        {/* Mobile: count bar + list/empty wrapped in one content card. On desktop `display:contents`
            makes this wrapper layout-neutral so nothing changes there. */}
        <div className="sc-mob-content">
        {/* Mobile View Toggle — products count badge + Card View / Table View segmented control */}
        {isMobile && (
          <div style={{padding:'8px 0 4px',display:'flex',alignItems:'center',justifyContent:'space-between',gap:'10px'}}>
            <span style={{display:'inline-flex',alignItems:'center',gap:'5px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'20px',padding:'4px 11px',fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',flexShrink:0}}><i className="fa fa-cubes" style={{fontSize:'9px'}}></i>{filteredProducts.length} products</span>
            <div style={{display:'inline-flex',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e2e8f0',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'}}>
              <button type="button" onClick={() => setMobileView('card')} style={{display:'inline-flex',alignItems:'center',gap:'6px',height:'34px',padding:'0 16px',border:'none',background:mobileView==='card'?'rgb(234, 88, 12)':'#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                <i className="fa fa-th-large" style={{fontSize:'11px',color:mobileView==='card'?'#fff':'#64748b'}}></i>
                <span style={{fontSize:'12px',fontWeight:'700',color:mobileView==='card'?'#fff':'#64748b'}}>Card View</span>
              </button>
              <button type="button" onClick={() => setMobileView('table')} style={{display:'inline-flex',alignItems:'center',gap:'6px',height:'34px',padding:'0 16px',border:'none',borderLeft:'1.5px solid #e2e8f0',background:mobileView==='table'?'rgb(234, 88, 12)':'#fff',cursor:'pointer',outline:'none',transition:'all 0.2s'}}>
                <i className="fa fa-table" style={{fontSize:'11px',color:mobileView==='table'?'#fff':'#64748b'}}></i>
                <span style={{fontSize:'12px',fontWeight:'700',color:mobileView==='table'?'#fff':'#64748b'}}>Table View</span>
              </button>
            </div>
          </div>
        )}

        {/* DataTable / Card View */}
        {fetching ? (
          <SpecTableLoading label="Loading stock…" />
        ) : isMobile && mobileView === 'card' ? (
          <div style={{padding:'10px 0',display:'flex',flexDirection:'column',gap:'10px'}}>
            {filteredProducts.length === 0 ? (
              <div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
                <SpecTableEmpty onClear={clearFilters} />
              </div>
            ) : filteredProducts.map((row, idx) => {
              const expected = calcExpected(row);
              const diff = calcDiff(row);
              const isOk = diff === 0;
              const isExcess = diff > 0;
              const resultLabel = isOk ? 'OK' : isExcess ? '+'+diff : ''+diff;
              const resultColor = isOk ? '#16a34a' : isExcess ? '#ca8a04' : '#dc2626';
              const resultBg   = isOk ? '#f0fdf4'  : isExcess ? '#fefce8'  : '#fef2f2';
              const resultBdr  = isOk ? '#bbf7d0'  : isExcess ? '#fde68a'  : '#fecaca';
              const isExpanded = expandedCard === ((row.product_id||'')+'_'+(row.date||idx));
              // label = short code, full = full name shown below value
              const statCell = (label, full, val, color, isLast=false) => (
                <div style={{flex:1,textAlign:'center',borderRight:isLast?'none':'1px solid #eef2f7',padding:'12px 6px 10px'}}>
                  <div style={{fontSize:'17px',fontWeight:'800',lineHeight:1,color:color||(val===0?'#d1d5db':'#1e293b'),marginBottom:'6px'}}>{val}</div>
                  <div style={{fontSize:'10px',fontWeight:'700',color:color||'#475569',letterSpacing:'0.2px',marginBottom:'2px'}}>{label}</div>
                  <div style={{fontSize:'8px',fontWeight:'500',color:'#94a3b8',letterSpacing:'0.1px',lineHeight:1.2}}>{full}</div>
                </div>
              );
              const cardKey = (row.product_id||'')+'_'+(row.date||idx);
              return (
                <div key={cardKey} style={{display:'flex',borderRadius:'14px',border:'1px solid #f1f5f9',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                  <div style={{width:'4px',flexShrink:0,background:'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)'}}/>
                  <div style={{flex:1,minWidth:0}}>
                    {/* Clickable top section */}
                    <div onClick={()=>setExpandedCard(isExpanded?null:cardKey)} style={{padding:'12px 12px 10px',cursor:'pointer'}}>
                      {/* Product name + result badge */}
                      <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px',marginBottom:'4px'}}>
                        <div style={{minWidth:0}}>
                          <div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',lineHeight:1.3,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{row.product_name}</div>
                          {/* Date */}
                          {(row.last_activity_date||date) && (
                            <div style={{fontSize:'11px',color:'#64748b',fontWeight:'600',marginTop:'2px'}}>
                              {(v=>{const[y,m,d]=v.split('-');return d+'/'+m+'/'+y;})(row.last_activity_date||date)}
                            </div>
                          )}
                        </div>
                        <div style={{display:'flex',alignItems:'center',gap:'6px',flexShrink:0}}>
                          <span style={{background:resultBg,color:resultColor,border:'1px solid '+resultBdr,borderRadius:'8px',padding:'3px 10px',fontSize:'11px',fontWeight:'700',whiteSpace:'nowrap'}}>{resultLabel}</span>
                          <i className={'fa fa-chevron-'+(isExpanded?'up':'down')} style={{fontSize:'10px',color:isExpanded?'rgb(234, 88, 12)':'#d1d5db'}}/>
                        </div>
                      </div>
                      {/* Badges: Opening, Sales, Expected */}
                      <div style={{display:'flex',gap:'6px',flexWrap:'wrap',marginTop:'6px'}}>
                        <span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>Opening: {Number(row.os)||0}</span>
                        <span style={{fontSize:'11px',fontWeight:'600',color:'#374151',background:'#f8fafc',border:'1px solid #e5e7eb',borderRadius:'6px',padding:'2px 8px'}}>Sales: {_.sum(row.sales)||0}</span>
                        <span style={{fontSize:'11px',fontWeight:'700',color:Number(expected)<0?'#dc2626':'#3b82f6',background:Number(expected)<0?'#fef2f2':'#eff6ff',border:'1px solid '+(Number(expected)<0?'#fecaca':'#bfdbfe'),borderRadius:'6px',padding:'2px 8px'}}>Expected: {expected}</span>
                      </div>
                    </div>
                    {/* Expanded detail */}
                    {isExpanded && (
                      <div style={{borderTop:'1px solid #f1f5f9',background:'#f8fafc',padding:'10px 12px'}}>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr 1fr',gap:'6px',marginBottom:'6px'}}>
                          {[['O.S','Opening',Number(row.os)||0,null],['N.S','New Stock',_.sum(row.ns)||0,null],['Sales','Sales',_.sum(row.sales)||0,null],['C.RTN','Cust.Rtn',_.sum(row.crtn)||0,null]].map(([lbl,full,val,col])=>(
                            <div key={lbl} style={{background:'#fff',borderRadius:'8px',padding:'6px 4px',textAlign:'center',border:'1px solid #f1f5f9'}}>
                              <div style={{fontSize:'14px',fontWeight:'800',color:col||(val===0?'#d1d5db':'#1e293b'),lineHeight:1,marginBottom:'3px'}}>{val}</div>
                              <div style={{fontSize:'8px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.3px'}}>{lbl}</div>
                            </div>
                          ))}
                        </div>
                        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr 1fr',gap:'6px'}}>
                          {[['Dumps','Dumps',_.sum(row.dmps)||0,null],['S.RTN','Supp.Rtn',_.sum(row.srtn)||0,null],['Stock','Exp.Stock',expected,Number(expected)<0?'#dc2626':'#3b82f6'],['Cl.Stk','Closing',(!row.cl_stock||row.cl_stock===0)?'—':row.cl_stock,row.cl_stock?'#ca8a04':null]].map(([lbl,full,val,col])=>(
                            <div key={lbl} style={{background:'#fff',borderRadius:'8px',padding:'6px 4px',textAlign:'center',border:'1px solid #f1f5f9'}}>
                              <div style={{fontSize:'14px',fontWeight:'800',color:col||(val===0||val==='—'?'#d1d5db':'#1e293b'),lineHeight:1,marginBottom:'3px'}}>{val}</div>
                              <div style={{fontSize:'8px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.3px'}}>{lbl}</div>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        ) : isMobile && mobileView === 'table' && filteredProducts.length === 0 ? (
          /* Empty state outside the scrollable area, wrapped in a card — same UI as Card View */
          <div style={{padding:'10px 0'}}>
            <div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
              <SpecTableEmpty onClear={clearFilters} />
            </div>
          </div>
        ) : isMobile && mobileView === 'table' ? (
          /* Mobile List View — scrollable table */
          <div style={{overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
            <div style={{minWidth:'720px'}}>
              {/* Header row */}
              <div style={{display:'grid',gridTemplateColumns:'120px 52px 52px 52px 56px 56px 52px 60px 86px 76px 1fr',padding:'8px 12px',background:'#f8fafc',borderBottom:'2px solid #f1f5f9',position:'sticky',top:0,zIndex:1}}>
                {['Product','O.S','N.S','Sales','C.RTN','Dumps','S.RTN','Stock','CL.STOCK','Result','Note'].map((h,i)=>(
                  <span key={h} style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',textAlign:i===0||i===10?'left':'center',whiteSpace:'nowrap'}}>{h}</span>
                ))}
              </div>
              {filteredProducts.map((row, idx) => {
                const expected = calcExpected(row);
                const diff = calcDiff(row);
                const resultColor = diff === 0 ? '#16a34a' : diff > 0 ? '#ca8a04' : '#dc2626';
                const resultBg = diff === 0 ? '#f0fdf4' : diff > 0 ? '#fefce8' : '#fef2f2';
                const resultLabel = diff === 0 ? 'OK' : diff > 0 ? '+'+diff : diff;
                return (
                  <div key={row.product_id||idx} style={{display:'grid',gridTemplateColumns:'120px 52px 52px 52px 56px 56px 52px 60px 86px 76px 1fr',padding:'10px 12px',borderBottom:'1px solid #f1f5f9',alignItems:'center',background:idx%2===0?'#fff':'#fafbfc'}}>
                    <div style={{fontSize:'12px',fontWeight:'600',color:'#1e293b',paddingRight:'8px',lineHeight:1.3}}>{row.product_name}</div>
                    {[Number(row.os)||0, _.sum(row.ns)||0, _.sum(row.sales)||0, _.sum(row.crtn)||0, _.sum(row.dmps)||0, _.sum(row.srtn)||0, expected].map((v,i)=>(
                      <div key={i} style={{textAlign:'center',fontSize:'12px',fontWeight:'700',color:v===0?'#d1d5db':'#374151'}}>{v}</div>
                    ))}
                    <div style={{textAlign:'center',fontSize:'12px',fontWeight:'700',color:!hasClosing(row)?'#d1d5db':'#ca8a04'}}>{hasClosing(row)?Number(row.cl_stock):'—'}</div>
                    <div style={{textAlign:'center'}}>
                      {!hasClosing(row) ? <span style={{fontSize:'12px',color:'#d1d5db'}}>—</span> : <span style={{background:resultBg,color:resultColor,borderRadius:'12px',padding:'2px 8px',fontSize:'10px',fontWeight:'700',display:'inline-block',whiteSpace:'nowrap'}}>{resultLabel}</span>}
                    </div>
                    <div style={{fontSize:'10px',color:diff===0?'#94a3b8':diff>0?'#ca8a04':'#dc2626',paddingLeft:'6px',lineHeight:1.3,whiteSpace:'nowrap'}}>
                      {!hasClosing(row)?'':diff===0?'Matched':diff>0?diff+' extra':Math.abs(diff)+' missing'}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        ) : (
        <DataTable
          columns={getColumns(values, setFieldValue)}
          data={filteredProducts}
          pagination
          highlightOnHover
          persistTableHead={filteredProducts.length > 0}
          striped
          conditionalRowStyles={conditionalRowStyles}
          paginationPerPage={50}
          paginationRowsPerPageOptions={[10, 20, 50, 100]}
          paginationComponent={SpecPagination}
          customStyles={customStyles}
          noDataComponent={<SpecTableEmpty onClear={clearFilters} />}
        />
        )}
        </div>{/* /.sc-mob-content */}
		<CommonPopup
			type={popupData.type}
			show={popupData.show}
			title={popupData.title}
			apiUrl={popupData.apiUrl}
			payload={popupData.payload}
			productName={popupData.productName}
			renderContent={popupData.renderContent}
			onSuccess={(res) => console.log("Success:", res)}
			onClose={() => setPopupData({ show: false })}
		  />
		  {/* Floating Tooltip */}
		  {tipState.show && tooltipPortalReady && tooltipPortalRef.current && ReactDOM.createPortal(
			<div style={{position:'fixed',left:tipState.x,top:tipState.y,
				transform: tipState.align==='right' ? 'translateX(-90%)' : tipState.align==='left' ? 'translateX(-10%)' : 'translateX(-50%)',
				background:'#1e293b',color:'#fff',fontSize:'11px',fontWeight:'500',padding:'7px 12px',borderRadius:'8px',whiteSpace:'normal',zIndex:99999,boxShadow:'0 4px 16px rgba(0,0,0,0.2)',pointerEvents:'none',lineHeight:'1.4',maxWidth:'300px'}}>
				{tipState.text}
				<div style={{position:'absolute',bottom:'100%',
					left: tipState.align==='right' ? '85%' : tipState.align==='left' ? '15%' : '50%',
					transform:'translateX(-50%)',border:'5px solid transparent',borderBottomColor:'#1e293b'}}></div>
			</div>,
			tooltipPortalRef.current
		  )}
			  </Form>
			</>)}
		  </Formik>
    </div>
  );
}

function CommonPopup({
  type,
  show,
  onClose,
  title,
  productName,
  apiUrl,
  payload,
  onSuccess,
}) {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState(null);

  // ✅ Fetch data when popup opens
  useEffect(() => {
    if (show && apiUrl) {
      fetchData();
    }
  }, [show, apiUrl]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const response = await axios.post(apiUrl, payload);
      setData(response.data?.payload || response.data);
    } catch (err) {
      console.error("API Fetch Error:", err);
      alert("Error fetching popup data");
    } finally {
      setLoading(false);
    }
  };

  // ✅ Optional: Reuse submit if you want a Save/Update inside popup
  const handleSubmit = async () => {
    try {
      setLoading(true);
      const response = await axios.post(apiUrl, payload);
      onSuccess?.(response.data);
      onClose();
    } catch (err) {
      console.error("API Error:", err);
      alert("Error occurred while submitting");
    } finally {
      setLoading(false);
    }
  };

  // ✅ Render content based on popup type
  const renderContent = () => {
    if (loading) {
      return (
        <div className="text-center py-4" style={{color:'rgb(234, 88, 12)',fontWeight:'600'}}>
          <Spinner animation="border" size="sm" style={{color:'rgb(234, 88, 12)'}} /> Loading...
        </div>
      );
    }

    const fmtDate = (val) => {
      if (!val) return '—';
      try {
        const d = new Date(val);
        if (isNaN(d)) return val; // already formatted string (e.g. "27 Apr 2026")
        return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
      } catch { return val; }
    };

    const TH = ({children, right}) => (
      <th style={{padding:'10px 14px',fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',borderBottom:'2px solid #f1f5f9',textAlign:right?'right':'left',whiteSpace:'nowrap'}}>{children}</th>
    );
    const TD = ({children, right, bold, color, italic, small}) => (
      <td style={{padding:'10px 14px',textAlign:right?'right':'left',fontWeight:bold?'700':'400',color:color||'#374151',fontStyle:italic?'italic':'normal',fontSize:small?'12px':'13px',whiteSpace:'nowrap'}}>{children}</td>
    );
    const tblWrap = {borderRadius:'12px',border:'1px solid #eaecf2',overflow:'hidden'};
    const tbl = {width:'100%',borderCollapse:'collapse',fontSize:'13px'};
    const rowStyle = (i) => ({borderBottom:'1px solid #f1f5f9',background: i%2===0?'#fff':'#fafbfc'});
    const noData = (cols) => <tr><td colSpan={cols} style={{textAlign:'center',padding:'32px',color:'#94a3b8',fontSize:'13px'}}>No data available</td></tr>;
    const Wrap = ({children, summary}) => (
      <div style={tblWrap}>
        <div style={{overflowX:'hidden'}}>{children}</div>
        {summary}
      </div>
    );

    const summaryBar = (items, fields) => {
      if (!items || items.length === 0) return null;
      const totals = {};
      fields.forEach(f => { totals[f] = items.reduce((s,i) => s + (parseFloat(i[f])||0), 0); });
      return (
        <div style={{display:'flex',gap:'16px',padding:'10px 14px',background:'#fff7ed',borderTop:'2px solid #fed7aa',fontSize:'12px',fontWeight:'700',color:'rgb(234, 88, 12)',flexWrap:'wrap'}}>
          {fields.map(f => (
            <span key={f}>{f==='stock'||f==='qty'?'Total Qty':'Total Value'}: {f==='stock'||f==='qty' ? totals[f] : '£ '+totals[f].toFixed(2)}</span>
          ))}
        </div>
      );
    };

    const summaryRow = (qty, val) => (
      <div style={{display:'flex',gap:'24px',padding:'10px 14px',background:'#fff7ed',borderTop:'2px solid #fed7aa',fontSize:'12px',fontWeight:'700',color:'rgb(234, 88, 12)'}}>
        <span>Total Qty: {qty}</span><span>Total Value: £ {val.toFixed(2)}</span>
      </div>
    );

    switch (type) {
      case "opening_stock": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        const grouped = data ? data.reduce((acc, item) => {
          const pname = item.product?.name || '-';
          if (!acc[pname]) acc[pname] = [];
          acc[pname].push(item);
          return acc;
        }, {}) : {};
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>#</TH><TH>Invoice</TH><TH>Date</TH><TH>Supplier</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? Object.entries(grouped).map(([pname, items]) => (
              <React.Fragment key={pname}>
                {items.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
                  <TD color="#94a3b8">{i+1}</TD>
                  <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
                  <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                  <TD color="#64748b">{item.supplier?.name||'-'}</TD>
                  <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
                  <TD right bold color="#1e293b">{Math.abs(item.stock)}</TD>
                  <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
                  <TD right bold color="#1e293b">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
                </tr>)}
              </React.Fragment>
            )) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "new_stock": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>Invoice</TH><TH>Date</TH><TH>Supplier</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
              <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
              <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                            <TD color="#64748b">{item.supplier?.name||'-'}</TD>
              <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
              <TD right bold color="#1e293b">{Math.abs(item.stock)}</TD>
              <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
              <TD right bold color="#1e293b">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
            </tr>) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "sales": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>Invoice</TH><TH>Date</TH><TH>Customer</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
              <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
              <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                            <TD color="#64748b">{item.customer?.name||'-'}</TD>
              <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
              <TD right bold color="#1e293b">{Math.abs(item.stock)}</TD>
              <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
              <TD right bold color="#1e293b">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
            </tr>) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "customer_return": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>Invoice</TH><TH>Date</TH><TH>Customer</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
              <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
              <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                            <TD color="#64748b">{item.customer?.name||'-'}</TD>
              <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
              <TD right bold color="#1e293b">{Math.abs(item.stock)}</TD>
              <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
              <TD right bold color="#1e293b">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
            </tr>) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "supplier_return": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>Invoice</TH><TH>Date</TH><TH>Supplier</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
              <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
              <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                            <TD color="#64748b">{item.supplier?.name||'-'}</TD>
              <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
              <TD right bold color="#1e293b">{Math.abs(item.stock)}</TD>
              <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
              <TD right bold color="#1e293b">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
            </tr>) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "dump": {
        const qty = data?.reduce((s,i)=>s+Math.abs(parseFloat(i.stock)||0),0)||0;
        const val = data?.reduce((s,i)=>s+(parseFloat(i.price||0)*Math.abs(parseFloat(i.stock)||0)),0)||0;
        return (<Wrap summary={data?.length>0 ? summaryRow(qty,val) : null}>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>Invoice</TH><TH>Date</TH><TH>Supplier</TH><TH>Note</TH><TH right>Qty</TH><TH right>Price</TH><TH right>Total</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=><tr key={item.id} style={rowStyle(i)}>
              <TD bold color="rgb(234, 88, 12)">#{item.invoice_id}</TD>
              <TD small color="#64748b">{fmtDate(item.updated_at)}</TD>
                            <TD color="#64748b">{item.supplier?.name||'-'}</TD>
              <TD italic small color="#94a3b8">{item.remarks||'—'}</TD>
              <TD right bold color="#dc2626">{Math.abs(item.stock)}</TD>
              <TD right>{"£ "}{Number(item.price||0).toFixed(2)}</TD>
              <TD right bold color="#dc2626">{"£ "}{(Number(item.price||0)*Math.abs(item.stock||0)).toFixed(2)}</TD>
            </tr>) : noData(8)}
          </tbody></table>
        </Wrap>);
      }

      case "closing_stock":
        return (<Wrap>
          <table style={tbl}><thead><tr style={{background:'#f8fafc'}}>
            <TH>#</TH><TH right>System Stock</TH><TH right>Recorded Stock</TH><TH right>Variance</TH><TH right>Date</TH>
          </tr></thead><tbody>
            {data && data.length > 0 ? data.map((item,i)=>{
              const variance = Number(item.variance||0);
              const varColor = variance > 0 ? '#16a34a' : variance < 0 ? '#dc2626' : '#64748b';
              const varLabel = variance > 0 ? `+${variance}` : `${variance}`;
              return (<tr key={item.product_id||i} style={rowStyle(i)}>
                <TD color="#94a3b8">{i+1}</TD>
                <TD bold color="#1e293b">{item.product_name||'-'}</TD>
                <TD right color="#64748b">{item.system_stock ?? '—'}</TD>
                <TD right bold color="#1e293b">{item.recorded_stock ?? '—'}</TD>
                <td style={{padding:'10px 14px',textAlign:'right',fontWeight:'700',color:varColor,whiteSpace:'nowrap'}}>
                  {variance === 0 ? <span style={{color:'#64748b'}}>—</span> : varLabel}
                  {variance !== 0 && <span style={{fontSize:'10px',marginLeft:'4px',fontWeight:'600',background: variance>0?'#dcfce7':'#fee2e2',color:varColor,padding:'1px 5px',borderRadius:'4px'}}>{variance>0?'Excess':'Short'}</span>}
                </td>
                <TD right small color="#64748b">{item.date||'—'}</TD>
              </tr>);
            }) : noData(6)}
          </tbody></table>
        </Wrap>);

      default:
        return <p>{type} No content available for this type.</p>;
    }
  };

  return (
     <>
      <style>{`.stock-detail-modal-wide { max-width: min(640px, 94vw) !important; } .stock-detail-modal-wide .modal-content{border-radius:18px;border:none;overflow:hidden;box-shadow:0 24px 60px -12px rgba(15,17,21,0.28),0 8px 20px -8px rgba(15,17,21,0.16);} .stock-detail-modal-wide .modal-body { overflow-x: auto; max-height: 72vh; overflow-y: auto; }`}</style>
     <Modal show={show} onHide={onClose} centered backdrop="static" dialogClassName="stock-detail-modal-wide">
      <Modal.Header style={{borderBottom:'2px solid rgb(234, 88, 12)',paddingBottom:'12px'}}>
        <Modal.Title style={{fontSize:'16px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.3px',display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>{title || "Popup"}{productName && <span style={{fontSize:'14px',fontWeight:'600',color:'#1e293b'}}>— {productName}</span>}</Modal.Title>
        <button type="button" className="close" onClick={onClose} aria-label="Close" style={{fontSize:'1.5rem',fontWeight:'700',lineHeight:'1',opacity:'0.5'}}>
          <span aria-hidden="true">&times;</span>
        </button>
      </Modal.Header>

      <Modal.Body style={{padding:'20px 24px',overflowY:'auto',maxHeight:'75vh'}}>
	  {renderContent()}</Modal.Body>
    </Modal>
    </>
  );
}

export default function StockCheckApp(props) {
	const dispatch = useDispatch();
	const {products} = useSelector(state => state.products);
	
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
		//loadList()
    },[])
	
    if (props.noHeader) {
		return (
		<>
				<List noCard {...props} />
		<ToastContainer position="top-right" autoClose={3000} />
		</>
		);
	}
    return (
	<>
	{/* Single merged card */}
	<div style={{ background: '#fff', borderRadius: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', border: '1px solid #f1f5f9', overflow: 'visible', marginBottom: '16px' }}>
		<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 24px 14px' }}>
			<div style={{ width: '44px', height: '41px', borderRadius: '14px', background: 'rgb(234, 88, 12)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 3px 12px rgba(234,88,12,0.25)', flexShrink: 0 }}>
				<i className="fa fa-calendar-check-o" style={{ color: '#fff', fontSize: '20px' }}></i>
			</div>
			<div>
				<h1 style={{ fontSize: '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Stock Check</h1>
				<p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Review and verify stock levels</p>
			</div>
		</div>
		<List noCard {...props} />
	</div>
	<ToastContainer position="top-right" autoClose={3000} />
	</>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('stock-check-app')) {
    const id = "stock-check-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<StockCheckApp {...props} />
		</Provider>
    );
}
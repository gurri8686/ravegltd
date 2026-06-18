import React, { useEffect, useState,useMemo,useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray,Formik, Form, Field } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import axios from 'axios';
import logger from 'redux-logger';
import Select from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import { useToast } from "./../hooks/useToast";
import { useQueryData } from "./../hooks/useQueryData";
import useDataTableStyles from "../hooks/useDataTableStyles";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecTableEmpty from "./../elements/SpecTableEmpty";
import OrangeDatePicker from "../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";


const productsSlice = createSlice({
    name: 'products',
    initialState: {
		//products:[],
		//date:new Date(Date.now() - 86400000).toISOString().slice(0, 10),
		date:"",
		loading: false,
		refreshCount:0
	},
    reducers: {
        //setProducts: (state, action) => { state.products = action.payload },
		setDate: (state, action) => { state.date = action.payload },
		setLoading: (state, action) => { state.loading = action.payload },
		setRefreshCount: (state, action) => { state.refreshCount = action.payload },
    }
});

const {
	//setProducts, 
	setRefreshCount,setDate,setLoading} = productsSlice.actions;

const store = configureStore({
    reducer: { products: productsSlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

// Clear-filters handler for the empty state — resets the date filter to default.
const clearStockClosingFilters = () => { store.dispatch(setDate("")); };


function CreateForm(props) {
	
	const dispatch = useDispatch();
	const {products,date} = useSelector(state => state.products);
	
	const handleChange = (e) => {
		dispatch(setDate(e.target.value))
	}
	
	return (
		<div className="card">
			<div className="card-body mb-0 pb-0">
				<div className="row">
					<div className="col-12">
					<label>Select Date<span className="text-danger">*</span></label>
					<input
					defaultValue={date}
					onChange={(e) => handleChange(e)}
					type="date"
					className="form-control"
					/>
					</div>
				</div>
			</div>
		</div>
	)
}

function CBox({ checked, onChange }) {
  return (
    <div onClick={onChange} style={{
      width:'18px', height:'18px', borderRadius:'5px', cursor:'pointer', flexShrink:0,
      border: checked ? '2px solid rgb(234, 88, 12)' : '2px solid #d1d5db',
      background: checked ? 'rgb(234, 88, 12)' : '#fff',
      display:'inline-flex', alignItems:'center', justifyContent:'center',
      transition:'all 0.15s',
    }}>
      {checked && (
        <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
          <path d="M1 4L3.5 6.5L9 1" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      )}
    </div>
  );
}

function ProductList(props) {
  const [products, setProducts] = useState([]);
  const [searchText, setSearchText] = useState("");
  const [stockFilter] = useState({label:'In Stock',value:'in-stock'});
  const [reviewedIds, setReviewedIds] = useState(new Set());
  const [editingIds, setEditingIds] = useState(new Set());
  const [editBackup, setEditBackup] = useState({});
  const [selectedIds, setSelectedIds] = useState(new Set());
  const [showReviewed, setShowReviewed] = useState(false);
  const [summaryOpen, setSummaryOpen] = useState(false);
  const stockFilterOptions = [
    {label:'All',value:'all'},
    {label:'In Stock',value:'in-stock'},
    {label:'Out of Stock',value:'out-of-stock'},
  ];

  const { notifySuccess, notifyError } = useToast();
  const dispatch = useDispatch();
  const { date, loading } = useSelector((state) => state.products);

  // Toasts must not linger or leak onto another view. Clear them when the user
  // switches the reviewed/unreviewed tab...
  useEffect(() => { toast.dismiss(); }, [showReviewed]);
  // ...and when the page is hidden / another browser tab is opened / this view
  // unmounts (navigates away). They only reappear on a fresh action.
  useEffect(() => {
    const dismiss = () => toast.dismiss();
    document.addEventListener('visibilitychange', dismiss);
    window.addEventListener('pagehide', dismiss);
    return () => {
      toast.dismiss();
      document.removeEventListener('visibilitychange', dismiss);
      window.removeEventListener('pagehide', dismiss);
    };
  }, []);

  // ✅ Fetch products
  useEffect(() => {
    const fetchProducts = async () => {
      try {
        dispatch(setLoading(true));
        const response = await axios.post(props.listApi, { date });
        const urlProductId = new URLSearchParams(window.location.search).get('product');
        const seenUids = {};
        let mapped = response.data.payload.map((item, idx) => {
		  const hasSaved = item.stock_closing && item.stock_closing.id;
		  const systemStock = Number(item.system_stock ?? 0);
		  const savedStock = hasSaved ? Number(item.stock_closing.stock ?? systemStock) : systemStock;
		  const purchased = Number(item.total_in ?? 0);
		  const sold = Number(item.total_out ?? 0);
		  const savedAt = hasSaved ? item.stock_closing.updated_at : null;
		  const remark = hasSaved ? (item.stock_closing.remark || '') : '';
		  const isReviewed = hasSaved && item.stock_closing.is_reviewed;
		  const supplierInvoiceId = Number(item.supplier_invoice_id ?? 0);
		  const supplierId = Number(item.supplier_id ?? 0);
		  // Composite UID: product | supplier | supplier_invoice. If still duplicate (rare —
		  // same product/supplier with no supplier_invoice_id), suffix with the array index.
		  let baseUid = item.id + '|' + supplierId + '|' + supplierInvoiceId;
		  let uid = baseUid;
		  if (seenUids[baseUid]) {
		    uid = baseUid + '|#' + idx;
		  }
		  seenUids[baseUid] = true;
		  return {
		    id: item.id,
		    _uid: uid,
		    supplier_invoice_id: supplierInvoiceId,
		    supplier_id: supplierId,
		    supplier_name: item.supplier_name ?? '',
		    name: item.name,
		    stock: savedStock,
		    originalStock: savedStock,
		    systemStock,
		    purchased,
		    sold,
		    remark,
		    isSaved: !!hasSaved,
		    hasSaved: !!hasSaved,
		    savedAt,
		    isReviewed: !!isReviewed,
		  };
		});
        if (urlProductId) {
            mapped = mapped.filter(p => String(p.id) === String(urlProductId));
        }
        setProducts(mapped);
        // Pre-populate reviewed UIDs from DB (per product+supplier)
        const reviewedFromDb = new Set(mapped.filter(p => p.isReviewed).map(p => p._uid));
        setReviewedIds(reviewedFromDb);
      } catch (error) {
        console.error("Error fetching products:", error);
        notifyError("Failed to fetch products");
      } finally {
        dispatch(setLoading(false));
      }
    };
	if(date != ""){
		fetchProducts();
	}
  }, [date]);

  // ✅ Search handler
  const handleSearch = (value) => {
    setSearchText(value);
  };


  // 🔹 Find product by composite uid (id + supplier_invoice_id) so per-supplier rows stay distinct.
  // Falls back to plain id match for backward compatibility.
  const findProductIndexById = (products, id) => {
    const idStr = String(id);
    const directIdx = products.findIndex((p) => String(p._uid) === idStr);
    if (directIdx !== -1) return directIdx;
    return products.findIndex((p) => p.id === id);
  };

	// 🔹 Save
	const handleSave = async (productId, values, setFieldValue) => {
		const index = findProductIndexById(values.products, productId);
		if (index === -1) return;

		const product = values.products[index];
		try {
		  await axios.post(props.saveOneApi, {
			product_id: product.id,
			supplier_invoice_id: product.supplier_invoice_id || 0,
			stock: parseInt(product.stock),
			remark: product.remark || '',
			date,
		  });
		  const savedStockVal = parseInt(product.stock);
		  notifySuccess(`Saved ${product.name}`);
		  setFieldValue(`products[${index}].isSaved`, true);
		  setFieldValue(`products[${index}].hasSaved`, true);
		  setFieldValue(`products[${index}].savedAt`, new Date().toLocaleString('en-GB'));
		  setFieldValue(`products[${index}].stock`, savedStockVal);
		  setFieldValue(`products[${index}].originalStock`, savedStockVal);
		  setReviewedIds(prev => new Set([...prev, product._uid]));
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
			supplier_invoice_id: product.supplier_invoice_id || 0,
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
  
  const [savingSelected, setSavingSelected] = useState(false);

  const handleSaveSelected = async (values, setFieldValue) => {
    if (selectedIds.size === 0) return;
    setSavingSelected(true);
    let saved = 0;
    for (const uid of selectedIds) {
      const idx = findProductIndexById(values.products, uid);
      if (idx === -1) continue;
      const product = values.products[idx];
      try {
        await axios.post(props.saveOneApi, {
          product_id: product.id,
          supplier_invoice_id: product.supplier_invoice_id || 0,
          stock: parseInt(product.stock),
          remark: product.remark || '',
          date,
        });
        setFieldValue(`products[${idx}].isSaved`, true);
        setFieldValue(`products[${idx}].hasSaved`, true);
        setFieldValue(`products[${idx}].savedAt`, new Date().toLocaleString('en-GB'));
        setReviewedIds(prev => new Set([...prev, product._uid]));
        saved++;
      } catch (err) {
        console.error(`Failed to save product uid=${uid}`, err);
      }
    }
    setSelectedIds(new Set());
    setSavingSelected(false);
    notifySuccess(`Saved ${saved} of ${selectedIds.size} products`);
  };

	const [saveAllProgress, setSaveAllProgress] = useState({saving: false, current: 0, total: 0, done: false});

	const handleSaveAll = async (values, setFieldValue) => {
		const unsaved = values.products.filter(p => !p.isSaved && p.stock !== '' && p.stock !== undefined);
		if (unsaved.length === 0) {
			notifyError("No changes to save");
			return;
		}
		setSaveAllProgress({saving: true, current: 0, total: unsaved.length, done: false});
		let saved = 0;
		for (const product of unsaved) {
			try {
				await axios.post(props.saveOneApi, {
					product_id: product.id,
					supplier_invoice_id: product.supplier_invoice_id || 0,
					stock: parseInt(product.stock),
					remark: product.remark || '',
					date,
				});
				const idx = findProductIndexById(values.products, product._uid);
				if (idx !== -1) {
					setFieldValue(`products[${idx}].isSaved`, true);
					setFieldValue(`products[${idx}].hasSaved`, true);
					setFieldValue(`products[${idx}].savedAt`, new Date().toLocaleString('en-GB'));
				}
				saved++;
				setSaveAllProgress(p => ({...p, current: saved}));
			} catch (err) {
				console.error(`Failed to save ${product.name}`, err);
			}
		}
		setSaveAllProgress({saving: false, current: saved, total: unsaved.length, done: true});
		notifySuccess(`Saved ${saved} of ${unsaved.length} products`);
		setTimeout(() => setSaveAllProgress(p => ({...p, done: false})), 3000);
	}

	const baseStyles = useDataTableStyles();
	const customStyles = useMemo(() => ({
		...baseStyles,
		headCells: { style: { ...baseStyles.headCells?.style, padding: '10px 10px', overflow: 'hidden' } },
		cells:     { style: { ...baseStyles.cells?.style,     padding: '10px 10px' } },
	}), []);

	const hs = {fontSize:'11px',fontWeight:'700',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13px',color:'#374151',fontWeight:'500'};

  // Table columns
  const getColumns = (values, setFieldValue, visibleData, showCheckbox = true) => [
    ...(showCheckbox ? [{
      name: (() => {
        const allChecked = visibleData && visibleData.length > 0 && visibleData.every(r => selectedIds.has(r._uid));
        return (
          <div style={{display:'flex',alignItems:'center',justifyContent:'center',width:'100%'}}>
            <CBox checked={!!allChecked} onChange={() => {
              if (allChecked) setSelectedIds(new Set());
              else setSelectedIds(new Set(visibleData.map(r => r._uid)));
            }} />
          </div>
        );
      })(),
      cell: (row) => (
        <div style={{display:'flex',alignItems:'center',justifyContent:'center',width:'100%'}}>
          <CBox checked={selectedIds.has(row._uid)} onChange={() => {
            setSelectedIds(prev => {
              const next = new Set(prev);
              if (next.has(row._uid)) next.delete(row._uid); else next.add(row._uid);
              return next;
            });
          }} />
        </div>
      ),
      width: '52px', grow: 0, compact: true,
    }] : []),
    {
      name: <span style={hs}>Product</span>,
      selector: (row) => row.name,
	  cell: (row) => (
		<div style={{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'}}>
			<span style={{fontWeight:'700',color:'#1e293b',fontSize:'13px'}}>{row.name}</span>
			<span style={{fontSize:'10px',color:'#16a34a',fontWeight:'600'}}>In:{row.purchased}</span>
			<span style={{fontSize:'10px',color:'#ef4444',fontWeight:'600'}}>Out:{row.sold}</span>
		</div>
	  ),
      sortable: true,
      grow: 1, minWidth: '180px',
    },
    {
      name: <span style={hs}>Remark</span>,
      selector: (row) => row.remark || '',
      cell: (row) => {
        const index = findProductIndexById(values.products, row._uid);
        const isRev = reviewedIds.has(row._uid) && !editingIds.has(row._uid);
        if (isRev) return <span style={{fontSize:'12px',color:'#64748b',fontStyle:'italic'}}>{values.products[index]?.remark || '—'}</span>;
        return (
          <input type="text" placeholder="Add remark..."
            value={values.products[index]?.remark || ''}
            onChange={(e) => {
              setFieldValue(`products[${index}].remark`, e.target.value);
              setFieldValue(`products[${index}].isSaved`, false);
            }}
            style={{width:'100%',height:'34px',borderRadius:'8px',border:'1.5px solid #e2e8f0',fontSize:'12px',fontWeight:'500',padding:'0 12px',outline:'none',color:'#1e293b',background:'#fff',transition:'all 0.15s'}}
            onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.boxShadow='0 0 0 3px rgba(234,88,12,0.08)';}}
            onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.boxShadow='none';}}
          />
        );
      },
      grow: 1, minWidth: '160px',
    },
	{
      name: <span style={{...hs,width:'100%',textAlign:'center',display:'block'}}>System Stock</span>,
      selector: (row) => row.systemStock,
	  cell: (row) => {
		const s = row.systemStock;
		return <div style={{width:'100%',textAlign:'center'}}>
			<span style={{fontSize:'15px',fontWeight:'800',color: s > 0 ? '#1e293b' : s < 0 ? '#ef4444' : '#94a3b8',fontVariantNumeric:'tabular-nums'}}>{s}</span>
		</div>;
	  },
      sortable: true,
      width: '130px',
    },
    {
	  name: <span style={{...hs,width:'100%',textAlign:'center',display:'block'}}>Closing Stock</span>,
	  sortable: false,
	  width: '160px',
	  cell: (row) => {
		const index = findProductIndexById(values.products, row._uid);
		const product = values.products[index];
		const stockValue = product?.stock ?? '';
		const systemStock = row.systemStock;
		const diff = Number(stockValue || 0) - systemStock;
		const hasDiff = diff !== 0;
		const isRev = reviewedIds.has(row._uid) && !editingIds.has(row._uid);

		if (isRev) return (
		  <div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:'2px',width:'100%',textAlign:'center'}}>
			<span style={{fontSize:'14px',fontWeight:'700',color:'#1e293b'}}>{stockValue}</span>
			{hasDiff && <span style={{fontSize:'10px',fontWeight:'700',color: diff > 0 ? '#16a34a' : '#ef4444'}}>{diff > 0 ? '+' : ''}{diff}</span>}
		  </div>
		);

		return (
		  <div style={{display:'flex',flexDirection:'column',alignItems:'center',gap:'2px'}}>
		    <Field name={`products[${index}].stock`}>
			  {({ field }) => (
			    <input
				  {...field}
				  type="number"
				  min="0"
				  style={{width:'100px',height:'36px',borderRadius:'8px',border: hasDiff ? '2px solid #f59e0b' : '1.5px solid #e2e8f0',fontSize:'14px',fontWeight:'700',textAlign:'center',outline:'none',background: hasDiff ? '#fffbeb' : '#fff',color:'#1e293b'}}
				  placeholder="0"
				  value={stockValue}
				  onChange={(e) => {
				    setFieldValue(`products[${index}].stock`, e.target.value);
				    setFieldValue(`products[${index}].isSaved`, false);
				  }}
				  onFocus={e => e.target.style.borderColor='rgb(234, 88, 12)'}
				  onBlur={e => e.target.style.borderColor = hasDiff ? '#f59e0b' : '#e2e8f0'}
			    />
			  )}
		    </Field>
			{hasDiff && <span style={{fontSize:'10px',fontWeight:'700',color: diff > 0 ? '#16a34a' : '#ef4444'}}>{diff > 0 ? '+' : ''}{diff}</span>}
		  </div>
		);
	  },
	},
    {
      name: <span style={{...hs,display:'block',textAlign:'center'}}>Action</span>,
      width: '80px', grow: 0,
      cell: (row) => {
        const index = findProductIndexById(values.products, row._uid);
        const isReviewed = reviewedIds.has(row._uid);
        const isEditing = editingIds.has(row._uid);
		if (isReviewed && !isEditing) return (
			<div style={{display:'flex',justifyContent:'center',width:'100%'}}>
				<button type="button" title="Edit" onClick={() => { setEditBackup(prev => ({...prev, [row._uid]: { remark: values.products[index]?.remark || '', stock: values.products[index]?.stock }})); setEditingIds(prev => new Set([...prev, row._uid])); setFieldValue(`products[${index}].isSaved`, false); }}
				  style={{width:'32px',height:'32px',borderRadius:'8px',border:'1.5px solid #e2e8f0',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',transition:'all 0.15s',color:'#64748b',background:'#fff'}}
				  onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
				  onMouseLeave={e=>{e.currentTarget.style.borderColor='#e2e8f0';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}>
				  <i className="fa fa-pencil" style={{fontSize:'12px'}}></i>
				</button>
			</div>
		);
		if (isReviewed && isEditing) return (
			<div style={{display:'flex',justifyContent:'center',width:'100%',gap:'4px'}}>
				<button type="button" title="Save" onClick={() => { handleSave(row._uid, values, setFieldValue); setEditingIds(prev => { const n = new Set(prev); n.delete(row._uid); return n; }); }}
				  style={{width:'28px',height:'28px',borderRadius:'6px',border:'1.5px solid #16a34a',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',transition:'all 0.15s',color:'#16a34a',background:'#f0fdf4'}}
				  onMouseEnter={e=>{e.currentTarget.style.background='#16a34a';e.currentTarget.style.color='#fff';}}
				  onMouseLeave={e=>{e.currentTarget.style.background='#f0fdf4';e.currentTarget.style.color='#16a34a';}}>
				  <i className="fa fa-check" style={{fontSize:'11px'}}></i>
				</button>
				<button type="button" title="Cancel" onClick={() => { const backup = editBackup[row._uid]; if(backup){ setFieldValue(`products[${index}].remark`, backup.remark); setFieldValue(`products[${index}].stock`, backup.stock); setFieldValue(`products[${index}].isSaved`, true); } setEditingIds(prev => { const n = new Set(prev); n.delete(row._uid); return n; }); }}
				  style={{width:'28px',height:'28px',borderRadius:'6px',border:'1.5px solid #e2e8f0',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',transition:'all 0.15s',color:'#94a3b8',background:'#fff'}}
				  onMouseEnter={e=>{e.currentTarget.style.borderColor='#dc2626';e.currentTarget.style.color='#dc2626';e.currentTarget.style.background='#fef2f2';}}
				  onMouseLeave={e=>{e.currentTarget.style.borderColor='#e2e8f0';e.currentTarget.style.color='#94a3b8';e.currentTarget.style.background='#fff';}}>
				  <i className="fa fa-times" style={{fontSize:'11px'}}></i>
				</button>
			</div>
		);
		return (
			<div style={{display:'flex',justifyContent:'center',width:'100%'}}>
				<button
				  type="button"
				  title="Save & Review"
				  onClick={() => handleSave(row._uid, values, setFieldValue)}
				  style={{width:'32px',height:'32px',borderRadius:'8px',border:'1.5px solid rgb(234, 88, 12)',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',transition:'all 0.15s',
				    color:'rgb(234, 88, 12)',
				    background:'#fff7ed',
				  }}
				  onMouseEnter={e=>{e.currentTarget.style.background='rgb(234, 88, 12)';e.currentTarget.style.color='#fff';}}
				  onMouseLeave={e=>{e.currentTarget.style.background='#fff7ed';e.currentTarget.style.color='rgb(234, 88, 12)';}}
				>
				  <i className="fa fa-check" style={{fontSize:'13px'}}></i>
				</button>
			</div>
		)
        /*return (
          <div className="dropdown">
            <button
              className="btn btn-secondary btn-sm dropdown-toggle"
              type="button"
              id={`dropdownMenuButton-${row.id}`}
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              Actions
            </button>
            <ul
              className="dropdown-menu"
              aria-labelledby={`dropdownMenuButton-${row.id}`}
            >
              <li>
                <button
                  className="dropdown-item text-success w-100"
                  type="button"
                  onClick={() => handleSave(row.id, values, setFieldValue)}
                  disabled={isSaved}
                >
                  Save
                </button>
              </li>
              <li>
                <button
                  className="dropdown-item text-primary w-100"
                  type="button"
                  onClick={() => handleUpdate(row.id, values, setFieldValue)}
                >
                  Update
                </button>
              </li>
            </ul>
          </div>
        );*/
      },
      ignoreRowClick: true,
      allowOverflow: true,
      button: true,
    },
  ];

  // ✅ Filtered data (search + stock status)
  const filteredProducts = products.filter((item) => {
    // Search filter
    if (searchText && !item.name.toLowerCase().includes(searchText.toLowerCase())) return false;
    // Stock status filter (use systemStock = actual stock level, not closing value)
    const stock = parseInt(item.systemStock) || 0;
    const filter = stockFilter?.value || 'all';
    if (filter === 'out-of-stock' && stock > 0) return false;
    if (filter === 'in-stock' && stock <= 0) return false;
    return true;
  });

  const isMobile = window.innerWidth <= 767;
  const [mobilePage, setMobilePage] = useState(0);
  const [mobilePerPage, setMobilePerPage] = useState(10);
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const [filterPopupOpen, setFilterPopupOpen] = useState(false);
  const [activeDateField, setActiveDateField] = useState(null);
  const [pendingDate, setPendingDate] = useState(null);
  const mobileSearchRef = useRef(null);
  const datePickerRef = useRef(null);

  const fmtDisplaySC = (v) => {
    if (!v) return '';
    const [y,m,d] = v.split('-');
    return d+'/'+m+'/'+y;
  };
  const calendarDateSC = pendingDate ? new Date(pendingDate+'T00:00:00') : null;

  const { noCard } = props;
  return (
    <div className="sc-outer-card" style={noCard ? {overflow:'visible'} : {borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)'}}>
      {false ? null : (
		<>
  <Formik
    initialValues={{ products }}
    enableReinitialize
    onSubmit={async (values, { setSubmitting, resetForm }) => {
      try {
		// 🔹 Simple confirm dialog
		const isConfirmed = window.confirm(
			"Are you sure you want to save all products?"
		);

		if (!isConfirmed) {
			setSubmitting(false);
			return; // ❌ Stop if user clicks Cancel
		}
		
	  
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
    {({ values, setFieldValue, isSubmitting }) => (
      <Form>
        {/* Cards + Filter bar */}
        <div style={{padding: isMobile ? '0' : '18px 20px 16px', background:'#fff', borderBottom: isMobile ? 'none' : '1px solid #f1f5f9'}}>
          {/* Summary Cards */}
          {products.length > 0 && (isMobile ? (
            (() => {
              const reviewed = filteredProducts.filter(p=>reviewedIds.has(p._uid)).length;
              const total = filteredProducts.length;
              const pct = total > 0 ? Math.round(reviewed/total*100) : 0;
              const unreviewed = total - reviewed;
              return (<>
                <div onClick={()=>setSummaryOpen(v=>!v)} style={{borderRadius: summaryOpen?'16px 16px 0 0':'16px',border:'1px solid #eaecf2',borderBottom: summaryOpen?'1px solid #f0f0f0':'1px solid #eaecf2',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',padding:'12px 14px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer',marginBottom: summaryOpen?0:'14px'}}>
                  <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                    <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'rgb(234, 88, 12)'}}/>
                    <span style={{fontSize:'10px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Stock Summary</span>
                  </div>
                  <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                    <div style={{display:'flex',gap:'8px'}}>
                      {[{v:total,c:'#3b82f6'},{v:reviewed,c:'#16a34a'},{v:unreviewed,c:'#d97706'}].map((s,i)=>(
                        <span key={i} style={{fontSize:'12px',fontWeight:'700',color:s.c}}>{s.v}</span>
                      ))}
                    </div>
                    <i className={'fa fa-chevron-'+(summaryOpen?'up':'down')} style={{fontSize:'9px',color:'#9ca3af'}}/>
                  </div>
                </div>
                {summaryOpen && (
                  <div style={{borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',background:'#fff',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',marginBottom:'14px'}}>
                    <div style={{display:'flex',padding:'10px 16px 12px'}}>
                      {[{label:'Total',value:total},{label:'Reviewed',value:reviewed},{label:'Pending',value:unreviewed}].map((c,i,arr)=>(
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
                        <span style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase'}}>Review Rate</span>
                        <span style={{fontSize:'10px',color:'#9ca3af',fontWeight:'600'}}>{pct}%</span>
                      </div>
                      <div style={{height:'3px',borderRadius:'99px',background:'#e5e7eb',overflow:'hidden'}}>
                        <div style={{height:'100%',width:pct+'%',borderRadius:'99px',background:'rgb(234, 88, 12)'}}/>
                      </div>
                    </div>
                  </div>
                )}
              </>);
            })()
          ) : (
            /* Desktop: full cards */
            <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'10px',marginBottom:'14px'}}>
              {[
                {label:'Total Products',value:filteredProducts.length,icon:'fa-cubes',color:'#3b82f6',light:'#eff6ff'},
                {label:'Reviewed',value:filteredProducts.filter(p=>reviewedIds.has(p._uid)).length,icon:'fa-check-circle',color:'#16a34a',light:'#f0fdf4'},
                {label:'Unreviewed',value:filteredProducts.filter(p=>!reviewedIds.has(p._uid)).length,icon:'fa-clock-o',color:'#d97706',light:'#fffbeb'},
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
          ))}
          {/* Desktop filter bar — inside the padded section */}
          {!isMobile && (
            <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
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
              <OrangeDatePicker value={date} onChange={(val) => dispatch(setDate(val))} placeholder="Select date" standalone style={{flexShrink:0,width:'200px'}} />
              {/* Print button */}
              {props.printUrl && (
              <button type="button" className="icon-tip" data-tip="Print" onClick={() => {
                const tab = showReviewed ? 'reviewed' : 'unreviewed';
                const url = props.printUrl + '?date=' + (date || new Date().toISOString().slice(0,10)) + '&tab=' + tab;
                window.open(url, '_blank');
              }}
                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
                onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
              >
                <i className="fa fa-print" style={{fontSize:'14px'}}></i>
              </button>
              )}
              {/* Download Excel button */}
              <button type="button" className="icon-tip" data-tip="Download Excel" onClick={() => {
                const base = props.excelUrl || '/excel/stock_closing';
                const tab = showReviewed ? 'reviewed' : 'unreviewed';
                const url = base + '?date=' + (date || new Date().toISOString().slice(0,10)) + '&tab=' + tab;
                window.location.href = url;
              }}
                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
                onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
              >
                <i className="fa fa-download" style={{fontSize:'14px'}}></i>
              </button>
            </div>
          )}
        </div>

        {/* Mobile search + filter bar — same as StockCheck (zero horizontal inset, 44px height) */}
        {isMobile && (
          <div style={{margin:'0 0 14px',display:'flex',alignItems:'center',gap:'8px'}}>
            {/* Search input */}
            <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'44px',border:'1.5px solid #e8edf2',borderRadius:'12px',background:'#fff',padding:'0 12px',minWidth:0}}>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" placeholder="Search product..."
                value={searchText} onChange={(e) => { handleSearch(e.target.value); setMobilePage(0); }}
                style={{flex:1,border:'none',outline:'none',fontSize:'12px',color:'#374151',background:'transparent',minWidth:0}}
              />
              {searchText && (
                <button type="button" onClick={() => { handleSearch(''); setMobilePage(0); }} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0}}>
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              )}
            </div>
            {/* Filter button — Sales-style solid orange */}
            <button type="button" onClick={() => { setPendingDate(date||null); setActiveDateField(null); setFilterPopupOpen(v => !v); }}
              style={{flexShrink:0,height:'44px',width:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',boxShadow:'0 2px 6px rgba(234,88,12,0.3)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none'}}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
              {date && <span style={{position:'absolute',top:'4px',right:'4px',width:'7px',height:'7px',borderRadius:'50%',background:'#fff',border:'1.5px solid rgb(234, 88, 12)'}}/>}
            </button>
          </div>
        )}

        {/* Mobile action buttons — Print / Excel (below the search bar) — same as Stock Check */}
        {isMobile && (
          <div style={{margin:'0 0 14px',display:'flex',gap:'10px'}}>
            {props.printUrl && (
            <button type="button" onClick={() => {
              const tab = showReviewed ? 'reviewed' : 'unreviewed';
              const url = props.printUrl + '?date=' + (date || new Date().toISOString().slice(0,10)) + '&tab=' + tab;
              window.open(url, '_blank');
            }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
              <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
            </button>
            )}
            <button type="button" onClick={() => {
              const base = props.excelUrl || '/excel/stock_closing';
              const tab = showReviewed ? 'reviewed' : 'unreviewed';
              const url = base + '?date=' + (date || new Date().toISOString().slice(0,10)) + '&tab=' + tab;
              window.location.href = url;
            }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
              <i className="fa fa-file-excel-o" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Excel
            </button>
          </div>
        )}

        {/* Mobile filter bottom sheet */}
        {isMobile && filterPopupOpen && (
          <>
            <div onMouseDown={()=>setFilterPopupOpen(false)} onTouchStart={()=>setFilterPopupOpen(false)}
              style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
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
                <button type="button" onClick={()=>setFilterPopupOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'8px',width:'28px',height:'28px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
              <div style={{padding:'0 18px 18px',display:'flex',flexDirection:'column',gap:'16px'}}>
                {/* Single Date — tap target + inline calendar */}
                <div>
                  <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Select Date</div>
                  <div style={{display:'grid',gridTemplateColumns:'1fr',gap:'10px',marginBottom: activeDateField ? '12px' : '0'}}>
                    <div style={{display:'flex',flexDirection:'column',gap:'4px'}}>
                      <span style={{fontSize:'9px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase'}}>Date</span>
                      <button type="button" onClick={()=>setActiveDateField(activeDateField==='date'?null:'date')}
                        style={{height:'44px',borderRadius:'10px',border:'1.5px solid '+(activeDateField==='date'?'rgb(234, 88, 12)':'#e5e7eb'),background:activeDateField==='date'?'#fff7f0':pendingDate?'#f9fafb':'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'8px',cursor:'pointer',outline:'none',transition:'all 0.15s'}}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke={activeDateField==='date'?'rgb(234, 88, 12)':'#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span style={{fontSize:'12px',fontWeight:'600',color:pendingDate?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{pendingDate?fmtDisplaySC(pendingDate):'Select'}</span>
                      </button>
                    </div>
                  </div>
                  {activeDateField && (
                    <div style={{borderRadius:'14px',border:'1.5px solid rgb(234, 88, 12)',overflow:'hidden',background:'#fff'}}>
                      <div style={{padding:'8px 14px 6px',background:'#fff7f0',borderBottom:'1px solid #fed7aa',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
                        <span style={{fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.4px',textTransform:'uppercase'}}>Select Date</span>
                        <button type="button" onClick={()=>setActiveDateField(null)} style={{background:'none',border:'none',outline:'none',cursor:'pointer',color:'#94a3b8',padding:'2px'}}>
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                      </div>
                      <style>{`.sc-inline-cal .react-datepicker{width:100%;border:none;font-family:inherit}.sc-inline-cal .react-datepicker__month-container{width:100%;float:none}.sc-inline-cal .react-datepicker__header{background:#fff;border-bottom:1px solid #f1f5f9;padding:8px 0 4px}.sc-inline-cal .react-datepicker__day-names,.sc-inline-cal .react-datepicker__week{display:flex;justify-content:space-around}.sc-inline-cal .react-datepicker__day-name,.sc-inline-cal .react-datepicker__day{width:36px;height:36px;line-height:36px;border-radius:50%;font-size:13px;font-weight:500;margin:1px}.sc-inline-cal .react-datepicker__day-name{font-size:11px;font-weight:700;color:#94a3b8}.sc-inline-cal .react-datepicker__day:hover{background:#fff7f0;color:rgb(234, 88, 12)}.sc-inline-cal .react-datepicker__day--selected{background:rgb(234, 88, 12) !important;color:#fff !important;font-weight:700}.sc-inline-cal .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12)}.sc-inline-cal .react-datepicker__day--outside-month{color:#d1d5db}.sc-inline-cal .react-datepicker__day--disabled{color:#e5e7eb;cursor:default}.sc-inline-cal .react-datepicker__navigation{top:10px}.sc-inline-cal .react-datepicker__navigation--previous{left:10px}.sc-inline-cal .react-datepicker__navigation--next{right:10px}.sc-inline-cal .react-datepicker__current-month{font-size:14px;font-weight:700;color:#111827;padding:4px 0}`}</style>
                      <div className="sc-inline-cal">
                        <DatePicker inline selected={calendarDateSC} onChange={(d)=>{ if(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); setPendingDate(y+'-'+m+'-'+dd);} setActiveDateField(null); }} maxDate={new Date()} />
                      </div>
                    </div>
                  )}
                </div>
                {/* Action buttons */}
                <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px',paddingTop:'4px'}}>
                  <button type="button" onClick={()=>{setPendingDate(null);setActiveDateField(null);}}
                    style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                    Clear
                  </button>
                  <button type="button" onClick={()=>{ if(pendingDate) dispatch(setDate(pendingDate)); else dispatch(setDate('')); setFilterPopupOpen(false); setActiveDateField(null); }}
                    style={{height:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Apply Filters
                  </button>
                </div>
              </div>
            </div>
          </>
        )}


        {/* Stock list card */}
        <div className="sc-list-card">
        {/* Mobile custom list / Desktop DataTable */}
        {isMobile ? (() => {
          const unreviewedList = filteredProducts.filter(p => !reviewedIds.has(p._uid));
          const reviewedList   = filteredProducts.filter(p =>  reviewedIds.has(p._uid));
          const activeList = showReviewed ? reviewedList : unreviewedList;
          const totalItems = activeList.length;
          const totalPages = Math.ceil(totalItems / mobilePerPage) || 1;
          const safePage = Math.min(mobilePage, totalPages - 1);
          const pageData = activeList.slice(safePage * mobilePerPage, (safePage + 1) * mobilePerPage);
          const startNum = totalItems === 0 ? 0 : safePage * mobilePerPage + 1;
          const endNum = Math.min((safePage + 1) * mobilePerPage, totalItems);
          return (
          <div>
            {/* Tabs */}
            <div style={{display:'flex',alignItems:'center',padding:'0 14px',borderBottom:'1px solid #f1f5f9',background:'#fff'}}>
              <button type="button" onClick={() => { setShowReviewed(false); setMobilePage(0); }}
                style={{padding:'10px 14px 8px',border:'none',outline:'none',background:'transparent',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'5px',borderBottom: !showReviewed ? '2px solid rgb(234, 88, 12)' : '2px solid transparent',marginBottom:'-1px'}}>
                <span style={{fontSize:'12px',fontWeight:'600',color: !showReviewed ? 'rgb(234, 88, 12)' : '#94a3b8'}}>Unreviewed</span>
                <span style={{fontSize:'10px',fontWeight:'700',color: !showReviewed?'#fff':'#94a3b8',background: !showReviewed?'rgb(234, 88, 12)':'#f1f5f9',padding:'1px 6px',borderRadius:'10px'}}>{unreviewedList.length}</span>
              </button>
              <button type="button" onClick={() => { setShowReviewed(true); setMobilePage(0); }}
                style={{padding:'10px 14px 8px',border:'none',outline:'none',background:'transparent',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'5px',borderBottom: showReviewed ? '2px solid #16a34a' : '2px solid transparent',marginBottom:'-1px'}}>
                <span style={{fontSize:'12px',fontWeight:'600',color: showReviewed ? '#16a34a' : '#94a3b8'}}>Reviewed</span>
                <span style={{fontSize:'10px',fontWeight:'700',color: showReviewed?'#fff':'#94a3b8',background: showReviewed?'#16a34a':'#f1f5f9',padding:'1px 6px',borderRadius:'10px'}}>{reviewedList.length}</span>
              </button>
            </div>
            {/* Cards */}
            {totalItems === 0 ? (
              <SpecTableEmpty onClear={false} />
            ) : (
              <div style={{padding:'10px 12px 4px'}}>
              {pageData.map((row) => {
              const index = findProductIndexById(values.products, row._uid);
              const stockValue = values.products[index]?.stock ?? '';
              const diff = stockValue !== '' ? Number(stockValue) - row.systemStock : null;
              const hasDiff = diff !== null && diff !== 0;
              const isReviewed = reviewedIds.has(row._uid);
              const isEditing = editingIds.has(row._uid);
              const isReadOnly = isReviewed && !isEditing;

              const accentColor = isReviewed ? '#16a34a' : 'rgb(234, 88, 12)';
              const accentBottom = isReviewed ? '#15803d' : '#c2410c';
              const btn = {height:'36px',padding:'0 14px',borderRadius:'8px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'5px',cursor:'pointer',outline:'none',flexShrink:0,fontSize:'12px',fontWeight:'700',border:'none'};

              const doSave   = () => { handleSave(row._uid,values,setFieldValue); setEditingIds(prev=>{ const n=new Set(prev); n.delete(row._uid); return n; }); };
              const doCancel = () => { const b=editBackup[row._uid]; if(b){ setFieldValue(`products[${index}].remark`,b.remark); setFieldValue(`products[${index}].stock`,b.stock); setFieldValue(`products[${index}].isSaved`,true); } setEditingIds(prev=>{ const n=new Set(prev); n.delete(row._uid); return n; }); };
              const doPencil = () => { setEditBackup(prev=>({...prev,[row._uid]:{remark:values.products[index]?.remark||'',stock:values.products[index]?.stock}})); setEditingIds(prev=>{ const n=new Set(prev); n.add(row._uid); return n; }); };

              return (
                <div key={row._uid} style={{display:'flex',marginBottom:'10px',borderRadius:'14px',border:'1px solid #f1f5f9',overflow:'hidden',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.05)'}}>
                  <div style={{width:'4px',flexShrink:0,background:`linear-gradient(180deg,${accentColor},${accentBottom})`}}/>
                  <div style={{flex:1,padding:'12px 12px 10px',minWidth:0}}>
                    {/* Top: date + action button */}
                    <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',gap:'8px',marginBottom:'4px'}}>
                      <div style={{minWidth:0}}>
                        {row.savedAt && (
                          <div style={{fontSize:'11px',color:accentColor,fontWeight:'700',marginBottom:'2px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>
                            {(()=>{ try { return new Date(row.savedAt).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); } catch(e){ return row.savedAt; } })()}
                          </div>
                        )}
                        {/* Product name */}
                        <div style={{fontWeight:'700',color:'#1e293b',fontSize:'13px',lineHeight:1.3,overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap',marginBottom:'6px'}}>{row.name}</div>
                        {/* In/Out badges */}
                        <div style={{display:'flex',gap:'5px',flexWrap:'wrap',alignItems:'center',marginBottom:'8px'}}>
                          <span style={{color:'#16a34a',fontWeight:'700',background:'#f0fdf4',border:'1px solid #bbf7d0',borderRadius:'6px',padding:'2px 8px',fontSize:'11px'}}>In {row.purchased}</span>
                          <span style={{color:'#ef4444',fontWeight:'700',background:'#fef2f2',border:'1px solid #fecaca',borderRadius:'6px',padding:'2px 8px',fontSize:'11px'}}>Out {row.sold}</span>
                        </div>
                      </div>
                      {/* Action button */}
                      <div style={{flexShrink:0}}>
                        {isReadOnly ? (
                          <button type="button" onClick={doPencil} style={{...btn,background:'#f8fafc',color:'#64748b',border:'1.5px solid #e2e8f0'}}>
                            <i className="fa fa-pencil" style={{fontSize:'11px'}}/> Edit
                          </button>
                        ) : isReviewed ? (
                          <div style={{display:'flex',gap:'6px'}}>
                            <button type="button" onClick={doSave} style={{...btn,background:'#f0fdf4',color:'#16a34a',border:'1.5px solid #86efac'}}>
                              <i className="fa fa-check" style={{fontSize:'11px'}}/> Save
                            </button>
                            <button type="button" onClick={doCancel} style={{...btn,padding:'0',width:'36px',background:'#f8fafc',color:'#94a3b8',border:'1.5px solid #e2e8f0'}}>
                              <i className="fa fa-times" style={{fontSize:'11px'}}/>
                            </button>
                          </div>
                        ) : (
                          <button type="button" onClick={doSave} style={{...btn,background:'#fff7ed',color:'rgb(234, 88, 12)',border:'1.5px solid #fed7aa'}}>
                            <i className="fa fa-check" style={{fontSize:'11px'}}/> Save
                          </button>
                        )}
                      </div>
                    </div>
                    {/* System + Closing inline row */}
                    <div style={{display:'flex',flexWrap:'wrap',gap:'8px',alignItems:'center',marginBottom:'8px',borderTop:'1px solid #f8fafc',paddingTop:'8px'}}>
                      {/* System badge */}
                      <div style={{display:'flex',alignItems:'center',gap:'6px',background:'#f8fafc',border:'1px solid #e2e8f0',borderRadius:'8px',padding:'6px 12px',flex:1,minWidth:0}}>
                        <span style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',flexShrink:0}}>System</span>
                        <span style={{fontSize:'15px',fontWeight:'800',color:row.systemStock>0?'#1e293b':row.systemStock<0?'#ef4444':'#94a3b8',marginLeft:'auto'}}>{row.systemStock}</span>
                      </div>
                      <i className="fa fa-arrow-right" style={{fontSize:'10px',color:'#d1d5db',flexShrink:0}}/>
                      {/* Closing badge/input */}
                      <div style={{display:'flex',alignItems:'center',gap:'6px',background:hasDiff?'#fffbeb':'#f8fafc',border:hasDiff?'1.5px solid #fbbf24':'1px solid #e2e8f0',borderRadius:'8px',padding:'6px 12px',flex:1,minWidth:0}}>
                        <span style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',textTransform:'uppercase',letterSpacing:'0.5px',flexShrink:0}}>Closing</span>
                        {isReadOnly ? (
                          <span style={{fontSize:'15px',fontWeight:'800',color:'#1e293b',marginLeft:'auto'}}>{stockValue !== '' ? stockValue : '—'}</span>
                        ) : (
                          <Field name={`products[${index}].stock`}>
                            {({ field }) => (
                              <input {...field} type="number" min="0"
                                style={{flex:1,minWidth:0,height:'24px',border:'none',borderRadius:'4px',fontSize:'15px',fontWeight:'800',textAlign:'right',outline:'none',background:'transparent',color:'#1e293b',padding:0,marginLeft:'auto'}}
                                placeholder="0" value={stockValue}
                                onChange={(e)=>{setFieldValue(`products[${index}].stock`,e.target.value);setFieldValue(`products[${index}].isSaved`,false);}}
                              />
                            )}
                          </Field>
                        )}
                      </div>
                      {/* Diff badge */}
                      {hasDiff && (
                        <span style={{fontSize:'11px',fontWeight:'700',color:diff>0?'#16a34a':'#ef4444',background:diff>0?'#f0fdf4':'#fef2f2',border:`1px solid ${diff>0?'#bbf7d0':'#fecaca'}`,borderRadius:'6px',padding:'2px 7px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis',flexShrink:0}}>{diff>0?'+':''}{diff}</span>
                      )}
                    </div>
                    {/* Remark */}
                    {isReadOnly ? (
                      values.products[index]?.remark ? <div style={{fontSize:'11px',color:'#94a3b8',fontStyle:'italic'}}>{values.products[index].remark}</div> : null
                    ) : (
                      <input type="text" placeholder="Add remark..."
                        value={values.products[index]?.remark||''}
                        onChange={(e)=>{setFieldValue(`products[${index}].remark`,e.target.value);setFieldValue(`products[${index}].isSaved`,false);}}
                        style={{width:'100%',height:'34px',borderRadius:'8px',border:'1.5px solid #e2e8f0',fontSize:'12px',padding:'0 12px',outline:'none',color:'#1e293b',background:'#fff',boxSizing:'border-box'}}
                        onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';}}
                        onBlur={e=>{e.target.style.borderColor='#e2e8f0';}}
                      />
                    )}
                  </div>
                </div>
              );
            })}
            </div>
            )}
            {/* Pagination */}
            <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'10px 14px',borderTop:'1px solid #f1f5f9',background:'#fafbfc'}}>
              <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                <span style={{fontSize:'11px',color:'#64748b',fontWeight:'500'}}>Rows:</span>
                <select value={mobilePerPage} onChange={e=>{setMobilePerPage(Number(e.target.value));setMobilePage(0);}}
                  style={{height:'26px',border:'1px solid #e2e8f0',borderRadius:'6px',fontSize:'11px',fontWeight:'600',color:'#374151',background:'#fff',padding:'0 4px',outline:'none'}}>
                  {[10,25,50].map(n=><option key={n} value={n}>{n}</option>)}
                </select>
              </div>
              <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                <span style={{fontSize:'11px',color:'#64748b',fontWeight:'500'}}>{startNum}–{endNum} of {totalItems}</span>
                <button type="button" disabled={safePage===0} onClick={()=>setMobilePage(p=>p-1)}
                  style={{width:'24px',height:'24px',borderRadius:'6px',border:'1px solid #e2e8f0',background:safePage===0?'#f8fafc':'#fff',color:safePage===0?'#cbd5e1':'#374151',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:safePage===0?'default':'pointer',outline:'none'}}>
                  <i className="fa fa-chevron-left" style={{fontSize:'9px'}}/>
                </button>
                <button type="button" disabled={safePage>=totalPages-1} onClick={()=>setMobilePage(p=>p+1)}
                  style={{width:'24px',height:'24px',borderRadius:'6px',border:'1px solid #e2e8f0',background:safePage>=totalPages-1?'#f8fafc':'#fff',color:safePage>=totalPages-1?'#cbd5e1':'#374151',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:safePage>=totalPages-1?'default':'pointer',outline:'none'}}>
                  <i className="fa fa-chevron-right" style={{fontSize:'9px'}}/>
                </button>
              </div>
            </div>
          </div>
          );
        })() : (
          <div style={{overflow:'hidden'}}>
          {/* Section Toggle Tabs */}
          {(() => {
            const unreviewedCount = filteredProducts.filter(p => !reviewedIds.has(p._uid)).length;
            const reviewedCount = filteredProducts.filter(p => reviewedIds.has(p._uid)).length;
            return (<>
              <div style={{padding:'8px 20px',display:'flex',gap:'6px',borderBottom:'1px solid #f1f5f9',alignItems:'center'}}>
                <button type="button" onClick={() => setShowReviewed(false)}
                  style={{padding:'6px 16px',border:'none',outline:'none',background:'transparent',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'6px',borderBottom: !showReviewed ? '2px solid rgb(234, 88, 12)' : '2px solid transparent',marginBottom:'-9px',transition:'all 0.15s',boxShadow:'none'}}>
                  <span style={{fontSize:'12px',fontWeight:'600',color: !showReviewed ? 'rgb(234, 88, 12)' : '#94a3b8'}}>Unreviewed</span>
                  <span style={{fontSize:'10px',fontWeight:'700',color: !showReviewed ? '#fff' : '#94a3b8',background: !showReviewed ? 'rgb(234, 88, 12)' : '#f1f5f9',padding:'2px 7px',borderRadius:'10px',minWidth:'16px',textAlign:'center'}}>{unreviewedCount}</span>
                </button>
                <button type="button" onClick={() => setShowReviewed(true)}
                  style={{padding:'6px 16px',border:'none',outline:'none',background:'transparent',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'6px',borderBottom: showReviewed ? '2px solid #16a34a' : '2px solid transparent',marginBottom:'-9px',transition:'all 0.15s',boxShadow:'none'}}>
                  <span style={{fontSize:'12px',fontWeight:'600',color: showReviewed ? '#16a34a' : '#94a3b8'}}>Reviewed</span>
                  <span style={{fontSize:'10px',fontWeight:'700',color: showReviewed ? '#fff' : '#94a3b8',background: showReviewed ? '#16a34a' : '#f1f5f9',padding:'2px 7px',borderRadius:'10px',minWidth:'16px',textAlign:'center'}}>{reviewedCount}</span>
                </button>
                {/* Save All Selected Button — only on Unreviewed tab */}
                {!showReviewed && (
                <div style={{marginLeft:'auto',paddingRight:'4px'}}>
                  <button type="button"
                    disabled={selectedIds.size === 0 || savingSelected}
                    onClick={() => handleSaveSelected(values, setFieldValue)}
                    style={{
                      height:'40px', padding:'0 20px', borderRadius:'10px', display:'inline-flex', alignItems:'center', gap:'6px',
                      border: selectedIds.size > 0 ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
                      background: selectedIds.size > 0 ? 'rgb(234, 88, 12)' : '#f8fafc',
                      color: selectedIds.size > 0 ? '#fff' : '#cbd5e1',
                      fontSize:'13px', fontWeight:'700', cursor: selectedIds.size > 0 ? 'pointer' : 'default',
                      transition:'all 0.15s', outline:'none', opacity: savingSelected ? 0.7 : 1,
                    }}
                  >
                    {savingSelected
                      ? <><i className="fa fa-spinner fa-spin" style={{fontSize:'11px'}}></i> Saving...</>
                      : <><i className="fa fa-check" style={{fontSize:'11px'}}></i> Save All</>
                    }
                  </button>
                </div>
                )}
              </div>

              {/* Content based on tab */}
              {loading ? (
                <SpecTableLoading label="Loading stock closing…" />
              ) : !showReviewed ? (
                unreviewedCount > 0 ? (
                  <DataTable
                    keyField="_uid"
                    columns={getColumns(values, setFieldValue, filteredProducts.filter(p => !reviewedIds.has(p._uid)))}
                    data={filteredProducts.filter(p => !reviewedIds.has(p._uid))}
                    pagination highlightOnHover persistTableHead
                    paginationPerPage={50} paginationRowsPerPageOptions={[10, 20, 50, 100]}
                    customStyles={customStyles}
                  />
                ) : (
                  <SpecTableEmpty onClear={false} />
                )
              ) : (
                reviewedCount > 0 ? (
                  <DataTable
                    keyField="_uid"
                    columns={getColumns(values, setFieldValue, filteredProducts.filter(p => reviewedIds.has(p._uid)), false)}
                    data={filteredProducts.filter(p => reviewedIds.has(p._uid))}
                    paginationPerPage={50} paginationRowsPerPageOptions={[10, 20, 50, 100]}
                    customStyles={customStyles}
                  />
                ) : (
                  <SpecTableEmpty onClear={false} />
                )
              )}
            </>);
          })()}
        </div>)}
        </div>{/* end sc-list-card */}

      </Form>
    )}
  </Formik>
</>
        )}
    </div>
  );
}

export default function StockClosingApp(props) {
	const dispatch = useDispatch();
	const {products} = useSelector(state => state.products);
	
	const query = useQueryData(props.query);
	
	useEffect(() => {
		 dispatch(setDate(query?.date || new Date(Date.now()).toISOString().slice(0, 10)))		
	}, []);
	
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
	
    return (
	<>
	<style>{`
		.inv-date-picker-wrap:hover{border-color:rgb(234, 88, 12) !important;background:#fff !important;}
		.inv-date-picker-wrap:focus-within{border-color:rgb(234, 88, 12) !important;box-shadow:0 0 0 3px rgba(234,88,12,0.08) !important;background:#fff !important;}
		.inv-date-picker{padding:0;font-size:13px;font-weight:600;border:none;height:100%;color:#1e293b;outline:none;cursor:pointer;background:transparent;width:110px;letter-spacing:0.2px;-webkit-appearance:none;appearance:none;}
		.rdt_Pagination select {
			appearance: none !important;
			-webkit-appearance: none !important;
			background-color: #fff !important;
			border: 1.5px solid #e2e8f0 !important;
			border-radius: 8px !important;
			padding: 0 28px 0 10px !important;
			height: 32px !important;
			font-size: 12px !important;
			font-weight: 600 !important;
			color: #374151 !important;
			cursor: pointer !important;
			outline: none !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23F27420' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important;
			background-position: right 8px center !important;
			transition: border-color 0.15s !important;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
		}
		.rdt_Pagination select:focus {
			border-color: rgb(234, 88, 12) !important;
			box-shadow: 0 0 0 3px rgba(234,88,12,0.1) !important;
		}
		.rdt_Pagination select option {
			font-size: 13px !important;
			font-weight: 500 !important;
			color: #374151 !important;
			padding: 8px !important;
		}
		@media (min-width: 768px) and (max-width: 1024px) {
			.sc-filter-row { display: grid !important; grid-template-columns: 1fr 1fr 1fr !important; gap: 16px !important; align-items: end !important; }
			.sc-date-col { min-width: 0 !important; }
			.sc-date-inner { width: 100% !important; min-width: unset !important; box-sizing: border-box !important; }
			.sc-date-inner .react-datepicker-wrapper { display: block !important; width: 100% !important; }
			.sc-date-inner .react-datepicker__input-container { width: 100% !important; }
			.sc-date-inner .react-datepicker__input-container input { width: 100% !important; min-width: 0 !important; flex: 1 !important; }
			.sc-stock-col { min-width: 0 !important; width: 100% !important; display: block !important; }
			.sc-stock-col > div { width: 100% !important; min-width: unset !important; box-sizing: border-box !important; }
			.sc-stock-col .react-select__container,
			.sc-stock-col [class$="-container"] { width: 100% !important; }
			.sc-stock-col .react-select__control,
			.sc-stock-col [class$="-control"] { width: 100% !important; box-sizing: border-box !important; }
			.sc-search-col { min-width: 0 !important; width: 100% !important; }
			.sc-search-col > div { width: 100% !important; box-sizing: border-box !important; }
			.sc-search-col input { width: 100% !important; box-sizing: border-box !important; }
		}
		@media (min-width: 1025px) {
			.sc-search-col { flex: 1 1 auto !important; width: auto !important; min-width: 0 !important; }
			.sc-search-col input { width: 100% !important; box-sizing: border-box !important; }
		}
		@media (max-width: 767px) {
			.sc-filter-row {
				flex-direction: row !important;
				align-items: flex-start !important;
				flex-wrap: nowrap !important;
				padding: 12px 12px 14px !important;
				gap: 8px !important;
				border-bottom: none !important;
				background: #fff !important;
			}
			.sc-date-col { flex: 1 1 0 !important; min-width: 0 !important; width: 0 !important; }
			.sc-date-inner { min-width: unset !important; width: 100% !important; height: 40px !important; padding: 0 10px !important; gap: 8px !important; border-radius: 10px !important; box-sizing: border-box !important; }
			.sc-date-inner input { font-size: 13px !important; min-width: 0 !important; flex: 1 !important; }
			.sc-stock-col { flex: 1 1 0 !important; min-width: 0 !important; width: 0 !important; }
			.sc-stock-col > div { min-width: unset !important; width: 100% !important; }
			.sc-stock-col .react-select__control { min-height: 40px !important; height: 40px !important; }
			.sc-stock-col .react-select__value-container { height: 40px !important; padding: 0 10px !important; }
			.sc-stock-col .react-select__indicators { height: 40px !important; }
			.sc-search-col { display: none !important; }
			.sc-outer-card { border-radius: 0 !important; border: none !important; box-shadow: none !important; background: transparent !important; }
			.sc-filter-card {
				background: #fff;
				border-radius: 14px !important;
				border: 1px solid #eaecf2 !important;
				box-shadow: 0 1px 4px rgba(0,0,0,0.05) !important;
				margin-bottom: 12px !important;
				overflow: hidden !important;
			}
			.sc-list-card {
				background: #fff;
				border-radius: 14px !important;
				border: 1px solid #eaecf2 !important;
				box-shadow: 0 1px 4px rgba(0,0,0,0.05) !important;
				overflow: hidden !important;
				margin-bottom: 16px !important;
			}
		}
	`}</style>
	{props.noHeader ? (<>
	<ProductList noCard {...props} />
	</>) : (
	<div style={{ background: '#fff', borderRadius: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', border: '1px solid #f1f5f9', overflow: 'hidden', marginBottom: '16px' }}>
		{/* Single merged card */}
		<div style={{ display: 'flex', alignItems: 'center', gap: '12px', padding: '18px 24px 14px' }}>
			<div style={{ width: '44px', height: '41px', borderRadius: '14px', background: 'rgb(234, 88, 12)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 3px 12px rgba(234,88,12,0.25)', flexShrink: 0 }}>
				<i className="fa fa-lock" style={{ color: '#fff', fontSize: '20px' }}></i>
			</div>
			<div>
				<h1 style={{ fontSize: '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Stock Closing</h1>
				<p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Close and finalize stock periods</p>
			</div>
		</div>
		<ProductList noCard {...props} />
	</div>
	)}
	<ToastContainer position="top-right" autoClose={5000} closeOnClick closeButton pauseOnHover={false} pauseOnFocusLoss={false} />
	</>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('stock-closing-app')) {
    const id = "stock-closing-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<StockClosingApp {...props} />
		</Provider>
    );
}
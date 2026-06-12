import React, { useEffect, useState,useCallback } from 'react';
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
import _ from 'lodash';
import logger from 'redux-logger';
import { useToast } from "./../hooks/useToast";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";

const today = new Date();
const sevenDaysAgo = new Date();
sevenDaysAgo.setDate(today.getDate() - 7);
const formatDate = (date) => date.toISOString().split("T")[0]; // YYYY-MM-DD

// ----------------- Slice + Store -----------------
const paymentHistorySlice = createSlice({
    name: 'payments',
    initialState: { payments: [], suppliers:[], start_date:formatDate(sevenDaysAgo), isListable:false, end_date:formatDate(today), currentSupplier:"", loading: false, refreshPayments: 0 },
    reducers: {
        setPayments: (state, action) => { state.payments = action.payload },
		setSuppliers: (state, action) => { state.suppliers = action.payload },
		setStartDate: (state, action) => { state.start_date = action.payload },
		setEndDate: (state, action) => { state.end_date = action.payload },
		setIsListable: (state, action) => { state.isListable = action.payload },
		setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },
		setSuppliersLoading: (state, action) => { state.loading = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now(); // unique timestamp every trigger
        },
    },
});

const { setPayments, setSuppliers,setStartDate,setEndDate,setIsListable, setCurrentSupplier, setSuppliersLoading, triggerPaymentRefresh } = paymentHistorySlice.actions;

const store = configureStore({
    reducer: { paymentHistory: paymentHistorySlice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

function SupplierSelect({ listSuppliersApi, onSubmit }) {
	const dispatch = useDispatch();
	const suppliers = useSelector((state) => state.paymentHistory.suppliers);
	const loading = useSelector((state) => state.paymentHistory.loading);
	const [error, setError] = useState(null);

  useEffect(() => {
    const fetchCustomers = async () => {
      try {
        const response = await axios.get(listSuppliersApi);
        if (response.data.success === true) {
          dispatch(setSuppliers(response.data.payload));
        }
      } catch (err) {
        console.error("Failed to load suppliers", err);
        setError("Unable to load suppliers");
      }
    };

    fetchCustomers();
  }, [listSuppliersApi, dispatch]);

  const options = [
    { value: "", label: "-- Select Supplier --" },
    ...suppliers.map((c) => ({
      value: c.id,
      label: c.name,
    })),
  ];

  const formik = useFormik({
    initialValues: {
      supplier_id: null,
      start_date: formatDate(sevenDaysAgo),
      end_date: formatDate(today),
    },
    validationSchema: Yup.object({
      supplier_id: Yup.object()
        .shape({
          label: Yup.string().required(),
          value: Yup.string().required("Customer is required"),
        })
        .nullable()
        .required("Customer is required"),
      start_date: Yup.string().required("Start Date is required"),
      end_date: Yup.string().required("End Date is required"),
    }),
    onSubmit: (values, { setSubmitting }) => {
      //onSubmit(values);
	  //console.log(values)
      //setSubmitting(false);
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
      fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',
    }),
  };
  const dateBoxStyle = {
    display:'inline-flex',alignItems:'center',background:'#f8fafc',
    border:'1.5px solid #e2e8f0',borderRadius:'12px',overflow:'hidden',height:h,
  };

  return (
    <div style={{
      borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',
      boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',padding:'24px 28px',
    }}>
        <form onSubmit={formik.handleSubmit}>
          <div style={{display:'flex',alignItems:'flex-end',gap:'18px',flexWrap:'wrap'}}>
            {/* Supplier */}
            <div style={{minWidth:'240px',flex:1}}>
              <label style={lblStyle}>Supplier<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
              <Select styles={selectCtrl}
                options={options}
                isLoading={loading}
                isClearable isSearchable
                name="supplier_id"
                value={formik.values.supplier_id}
                onChange={(selected) => {
                  formik.setFieldValue("supplier_id", selected);
                  dispatch(setCurrentSupplier(selected?.value || null));
                }}
                classNamePrefix="react-select"
                placeholder="Select Supplier"
                menuPortalTarget={document.body}
              />
            </div>

            {/* From Date */}
            <div style={{minWidth:'150px'}}>
              <label style={lblStyle}>From Date<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
              <div style={dateBoxStyle}>
                <div style={{padding:'0 14px',display:'flex',alignItems:'center',height:'100%'}}>
                  <OrangeDatePicker value={formik.values.start_date} onChange={(val) => { formik.setFieldValue('start_date', val); dispatch(setStartDate(val)); }} />
                </div>
              </div>
            </div>

            {/* To Date */}
            <div style={{minWidth:'150px'}}>
              <label style={lblStyle}>To Date<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
              <div style={dateBoxStyle}>
                <div style={{padding:'0 14px',display:'flex',alignItems:'center',height:'100%'}}>
                  <OrangeDatePicker value={formik.values.end_date} onChange={(val) => { formik.setFieldValue('end_date', val); dispatch(setEndDate(val)); }} />
                </div>
              </div>
            </div>
          </div>
        </form>
    </div>
  );
}

function List({ listPaymentsApi, deletePaymentApi }) {
  const { suppliers, start_date, end_date, payments } = useSelector(state => state.paymentHistory);
  const currentSupplier = useSelector(state => state.paymentHistory.currentSupplier);
  const refreshPayments = useSelector(state => state.paymentHistory.refreshPayments);
  const { notifySuccess, notifyError } = useToast();

  const dispatch = useDispatch();
  const [isLoading, setIsLoading] = useState(false);

  const loadList = async () => {
    setIsLoading(true);
    try {
      const response = await axios.post(listPaymentsApi + '/' + currentSupplier, { start_date: start_date, end_date: end_date });
      if (response.data.success === true) {
        dispatch(setPayments(response.data.payload))
      }
    } catch (err) {
      console.error('Failed to save payment', err);
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    if (new Date(start_date) > new Date(end_date)) {
      notifyError('Start Date can\'t be greater than End Date');
	  dispatch(setPayments([]))
      return;
    }
    if (currentSupplier != "") {
      loadList();
    }else{
		dispatch(setPayments([]))
	}
  }, [currentSupplier, start_date, end_date, refreshPayments])
  
  const handleDelete = async(row) => {
	const response = await axios.post(deletePaymentApi+'/'+row);
	if (response.data.success === true) {
		notifySuccess("Deleted Successfully!");
		loadList()
	}else{
		notifySuccess(response.data.payload);
	}
  }

	const rows = payments?.data || [];
	const [deleting, setDeleting] = useState(null);

	const handleDeleteClick = async (id) => {
		if (!confirm('Are you sure you want to delete this payment?')) return;
		setDeleting(id);
		try {
			const res = await axios.post(deletePaymentApi + '/' + id);
			if (res.data.success) { notifySuccess("Deleted successfully"); loadList(); }
			else notifyError(res.data.payload || "Delete failed");
		} catch (err) { notifyError("Delete failed"); }
		finally { setDeleting(null); }
	};

	const thStyle = {
		padding:'12px 16px',fontSize:'10.5px',fontWeight:'700',color:'#64748b',
		textTransform:'uppercase',letterSpacing:'0.7px',whiteSpace:'nowrap',
		borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',
	};
	const tdStyle = {
		padding:'12px 16px',borderBottom:'1px solid #f3f4f8',
		fontSize:'13px',fontWeight:'500',color:'#334155',
		fontVariantNumeric:'tabular-nums',verticalAlign:'middle',whiteSpace:'nowrap',
	};
	const childTd = { ...tdStyle, background:'#fafbfc', fontSize:'12.5px', color:'#64748b' };

  return (
	<div style={{
		borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'hidden',
		boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',
	}}>
		<div style={{padding:'16px 22px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
			<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>Payment History</span>
			{rows.length > 0 && <span style={{fontSize:'11.5px',fontWeight:'700',color:'#F27420',background:'#FFF5ED',padding:'3px 10px',borderRadius:'6px'}}>{rows.length} payments</span>}
		</div>
		<div style={{overflowX:'auto',position:'relative'}}>
			<table style={{width:'100%',borderCollapse:'collapse'}}>
				<thead>
					<tr>
						<th style={{...thStyle,width:'50px'}}>#</th>
						<th style={thStyle}>Payment Date</th>
						<th style={thStyle}>Mode</th>
						<th style={thStyle}>Payment ID</th>
						<th style={{...thStyle,textAlign:'right'}}>Recd Amt</th>
						<th style={thStyle}>Invoice</th>
						<th style={thStyle}>Invoice Date</th>
						<th style={thStyle}>Supplier</th>
						<th style={{...thStyle,textAlign:'right'}}>Invoice Amt</th>
						<th style={{...thStyle,textAlign:'right'}}>Allocated</th>
						<th style={{...thStyle,textAlign:'center',width:'80px'}}>Actions</th>
					</tr>
				</thead>
				<tbody>
					{rows.length === 0 ? (
						<tr><td colSpan="11" style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>
							{isLoading ? (
								<span style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#ea580c',fontSize:'13px',fontWeight:'600'}}>
									<i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
									Loading payments…
								</span>
							) : (currentSupplier ? 'No payments found for this period' : 'Select a supplier to view payment history')}
						</td></tr>
					) : rows.map((parent, idx) => (
						<React.Fragment key={parent.id}>
							<tr onMouseEnter={(e) => e.currentTarget.style.background='#fefaf6'} onMouseLeave={(e) => e.currentTarget.style.background='#fff'}>
								<td style={{...tdStyle,width:'50px'}}>
									<span style={{display:'inline-flex',alignItems:'center',justifyContent:'center',width:'26px',height:'26px',borderRadius:'7px',background:'#f1f5f9',fontSize:'11px',fontWeight:'700',color:'#64748b'}}>{idx + 1}</span>
								</td>
								<td style={{...tdStyle,fontWeight:'700',color:'#F27420'}}>{parent.created_at}</td>
								<td style={tdStyle}>
									{parent.payment_mode?.type
										? <span style={{padding:'3px 10px',borderRadius:'6px',fontSize:'11.5px',fontWeight:'600',background:'#f1f5f9',color:'#475569'}}>{parent.payment_mode.type}</span>
										: <span style={{color:'#cbd5e1'}}>—</span>
									}
								</td>
								<td style={{...tdStyle,fontWeight:'700',color:'#1e293b'}}>{parent.id}</td>
								<td style={{...tdStyle,textAlign:'right',fontWeight:'700',color:'#1e293b'}}>{Number(parent.amount).toFixed(2)}</td>
								<td style={tdStyle}></td>
								<td style={{...tdStyle,color:'#64748b',fontSize:'12.5px'}}>{parent.created_at}</td>
								<td style={{...tdStyle,fontWeight:'600'}}>{parent.supplier?.name || ''}</td>
								<td style={{...tdStyle,textAlign:'right'}}></td>
								<td style={{...tdStyle,textAlign:'right',fontWeight:'700',color:'#1e293b'}}>{Number(parent.amount).toFixed(2)}</td>
								<td style={{...tdStyle,textAlign:'center'}}>
									<button onClick={() => handleDeleteClick(parent.id)} disabled={deleting === parent.id}
										style={{border:'1.5px solid #fed7aa',borderRadius:'8px',background:'#fff',color:'#F27420',fontSize:'12px',fontWeight:'600',padding:'5px 12px',cursor:'pointer',transition:'all 0.15s'}}
										onMouseOver={(e) => {e.target.style.background='#FFF5ED';}}
										onMouseOut={(e) => {e.target.style.background='#fff';}}>
										{deleting === parent.id ? '...' : 'Delete'}
									</button>
								</td>
							</tr>
							{parent.child_payments?.length > 0 && parent.child_payments.map((child) => (
								<tr key={child.id}>
									<td style={{...childTd,paddingLeft:'24px'}}><span style={{color:'#cbd5e1',fontSize:'14px'}}>↳</span></td>
									<td style={childTd}>{child.created_at}</td>
									<td style={childTd}>
										{child.payment_mode?.type
											? <span style={{fontSize:'11px',color:'#94a3b8'}}>{child.payment_mode.type}</span>
											: <span style={{color:'#e2e8f0'}}>—</span>
										}
									</td>
									<td style={{...childTd,color:'#94a3b8'}}>{child.supplier_payment_id}</td>
									<td style={{...childTd,textAlign:'right'}}>{Number(child.amount).toFixed(2)}</td>
									<td style={{...childTd,fontWeight:'600',color:'#475569'}}>{child.id}</td>
									<td style={{...childTd,fontSize:'12px'}}>{parent.created_at_full}</td>
									<td style={childTd}>{child.supplier?.name || ''}</td>
									<td style={{...childTd,textAlign:'right',fontWeight:'600'}}>{child.order_start_sum_sub_total || ''}</td>
									<td style={{...childTd,textAlign:'right'}}>{Number(parent.amount).toFixed(2)}</td>
									<td style={childTd}></td>
								</tr>
							))}
						</React.Fragment>
					))}
				</tbody>
			</table>
			{isLoading && rows.length > 0 && (
				<div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
					<div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#ea580c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
						<i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
						<span>Loading…</span>
					</div>
				</div>
			)}
		</div>
	</div>
  );
}

export default function SupplierPaymentHistory(props) {
    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
		<div style={{marginBottom:'20px'}}>
			<SupplierSelect {...props} />
		</div>
		<List {...props} />
		<ToastContainer autoClose={3000} />
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('supplier-payment-history-app')) {
    const id = "supplier-payment-history-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<SupplierPaymentHistory {...props} />
		</Provider>
    );
}
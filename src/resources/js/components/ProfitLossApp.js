import React, { useEffect, useState,useMemo,useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import { useFormik,FieldArray,Formik,Form,Field,useFormikContext  } from 'formik';
import DataTable from 'react-data-table-component';
import * as Yup from 'yup';
import axios from 'axios';
import logger from 'redux-logger';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

import { faChevronRight, faChevronDown } from "@fortawesome/free-solid-svg-icons";
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer, toast } from 'react-toastify';
import Tab from 'react-bootstrap/Tab';
import Tabs from 'react-bootstrap/Tabs';

import { useToast } from "./../hooks/useToast";
import useOpenInNewTab from "./../hooks/useOpenInNewTab";
import useCopyTableData from "./../hooks/useCopyTableData";
import Icon from "./../hooks/Icons";
import useDataTableStyles from "./../hooks/useDataTableStyles";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";

const useFetchJson = (url, limit) => {
    const [data, setData] = useState();
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        const fetchData = async () => {
            setLoading(true);

            // Note error handling is omitted here for brevity
            const response = await fetch(url);
            const json = await response.json();
            const data = limit ? json.slice(0, limit) : json;
            setData(data);
            setLoading(false);
        };
        fetchData();
    }, [url, limit]);
    return { data, loading };
};

// ----------------- Slice + Store -----------------
const today = new Date();
const priorDate = new Date();
priorDate.setDate(today.getDate() - 30);
const formatDate = (date) => date.toISOString().slice(0, 10);

const slice = createSlice({
    name: 'products',
    initialState: {
		timeSlotMonths:6,
		suppliers: [],
		customers: [],
		products: [],
		selectedInvoices: [],

		currentSupplier:"",
		currentCustomer:"",
		currentProduct:[],

		currentSupplierInfo:"",
		currentCustomerInfo:"",
		currentProductInfo:[],


		loading: false,
		refreshPayments: 0,

		toDate: formatDate(today),
		fromDate: formatDate(priorDate),

		option:{label:"All",value:"all"}
	},
    reducers: {
        setSuppliers: (state, action) => { state.suppliers = action.payload },
        setCustomers: (state, action) => { state.customers = action.payload },
        setProducts: (state, action) => { state.products = action.payload },

		setToDate: (state, action) => { state.toDate = action.payload },
		setFromDate: (state, action) => { state.fromDate = action.payload },
		setSelectedInvoices: (state, action) => { state.selectedInvoices = action.payload },
		setOption: (state, action) => { state.option = action.payload },

        setCurrentSupplier: (state, action) => { state.currentSupplier = action.payload; },
        setCurrentCustomer: (state, action) => { state.currentCustomer = action.payload; },
        setCurrentProduct: (state, action) => { state.currentProduct = action.payload; },


		setCurrentSupplierInfo: (state, action) => { state.currentSupplierInfo = action.payload; },
		setCurrentCustomerInfo: (state, action) => { state.currentCustomerInfo = action.payload; },
		setCurrentProductInfo: (state, action) => { state.currentProductInfo = action.payload; },


		setSuppliersLoading: (state, action) => { state.loading = action.payload; },
		triggerPaymentRefresh: (state) => {
            state.refreshPayments = Date.now(); // unique timestamp every trigger
        },
    },
});

const { setSelectedInvoices, setToDate, setFromDate, setOption, setSuppliers, setCustomers, setProducts,
	setCurrentProductInfo, setCurrentCustomerInfo,
setCurrentSupplier, setCurrentProduct,setCurrentCustomer, setCurrentSupplierInfo, setSuppliersLoading, triggerPaymentRefresh } = slice.actions;

const store = configureStore({
    reducer: { products: slice.reducer},
	middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(logger), // ✅ add logger middleware
	devTools: process.env.NODE_ENV !== 'production',
});

function Filters(props) {
    const dispatch = useDispatch();
    const {
		currentSupplier, currentCustomer, currentProduct, currentSupplierInfo, currentCustomerInfo, currentProductInfo,
		suppliers, fromDate, toDate, customers, products
	} = useSelector(state => state.products);

    const loading = useSelector(state => state.products.loading);
	const [error, setError] = useState(null);

    useEffect(() => {
        const fetch = async () => {
            try {
                const response1 = await axios.get(props.productListApi);
				if(response1.data.success === true){
					dispatch(setProducts(response1.data.payload));
					const urlProductId = new URLSearchParams(window.location.search).get('product');
					if (urlProductId) {
						const found = response1.data.payload.find(p => String(p.id) === String(urlProductId));
						if (found) {
							const selected = [{ value: found.id, label: found.name }];
							dispatch(setCurrentProduct(selected));
							dispatch(setCurrentProductInfo([found]));
						}
					}
				}
            } catch (err) {
                console.error('Failed to load ', err);
            } finally {
            }
        };
        fetch();
    }, []);

	const product_options = [
		...products.map(c => ({
			value: c.id,
			label: c.name,
		})),
	];

	const formik = useFormik({
		initialValues: {
			supplier_id: { label: '', value: '' },
			product_id: [],
			customer_id: { label: '', value: '' },
			start_date: fromDate,
			end_date: toDate,
		},
		validationSchema: Yup.object({
			product_id: Yup.array()
				.min(1, "Please select at least one product")
				.of(
					Yup.object({
						label: Yup.string().required(),
						value: Yup.string().required(),
					})
				)
				.required(),
		}),
		onSubmit: values => {
			onSubmit(values);
		},
	});

	const handleProductChange = (selected) => {
		formik.setFieldValue("product_id", selected);
		dispatch(setCurrentProduct(selected));
		const selectedInfo = products.filter(p =>
			selected.some(s => s.value === p.id)
		);
		dispatch(setCurrentProductInfo(selectedInfo));
	};

	const matchingSelectStyles = {
		control: (base, state) => ({
			...base,
			border: '1.5px solid #e5e7eb',
			borderRadius: '12px',
			boxShadow: state.isFocused ? '0 0 0 0.2rem rgba(249,115,22,0.25)' : '0 1px 4px rgba(0,0,0,0.06)',
			minHeight: '44px',
			'&:hover': { borderColor: '#F27420' },
			borderColor: state.isFocused ? '#F27420' : '#e5e7eb',
		}),
		option: (base, state) => ({
			...base,
			backgroundColor: state.isSelected ? '#F27420' : state.isFocused ? '#FFF5ED' : '#fff',
			color: state.isSelected ? '#fff' : '#333',
			cursor: 'pointer',
			fontSize: '13px',
		}),
		placeholder: (base) => ({
			...base,
			fontSize: '13px',
			color: '#9ca3af',
		}),
		singleValue: (base) => ({
			...base,
			fontSize: '13px',
			fontWeight: '500',
			color: '#111827',
		}),
		multiValue: (base) => ({
			...base,
			borderRadius: '8px',
			backgroundColor: '#FFF5ED',
			border: '1px solid #fed7aa',
		}),
		multiValueLabel: (base) => ({
			...base,
			color: '#F27420',
			fontWeight: '600',
			fontSize: '12px',
		}),
		multiValueRemove: (base) => ({
			...base,
			color: '#F27420',
			'&:hover': { backgroundColor: '#F27420', color: '#fff', borderRadius: '0 8px 8px 0' },
		}),
		input: (base) => ({
			...base,
			fontSize: '13px',
		}),
		menu: (base) => ({
			...base,
			zIndex: 10,
		}),
	};

	const h = '44px';
	const lblStyle = {fontSize:'10.5px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'8px',display:'block'};
	const dateBoxStyle = {display:'inline-flex',alignItems:'center',background:'#f8fafc',border:'1.5px solid #e2e8f0',borderRadius:'12px',overflow:'hidden',height:h};

    return (
        <div style={{
			borderRadius:'20px',border:'1px solid #e8ecf2',background:'#fff',
			boxShadow:'0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04)',
			overflow:'hidden',
		}}>
			{/* Orange accent bar */}
			<div style={{height:'3px',background:'linear-gradient(90deg, #F27420 0%, #fed7aa 100%)'}}></div>

			<div style={{padding:'24px 28px'}}>
				<div style={{display:'flex',alignItems:'flex-end',gap:'16px',flexWrap:'wrap'}}>
					{/* Product */}
					<div style={{minWidth:'200px',flex:1,maxWidth:'340px'}}>
						<label style={lblStyle}>Products<span style={{color:'#F27420',marginLeft:'2px'}}>*</span></label>
						<Select styles={{
							...matchingSelectStyles,
							control: (base, state) => ({
								...base, minHeight:h, borderRadius:'12px',
								border: state.isFocused ? '1.5px solid #F27420' : '1.5px solid #e2e8f0',
								background: state.isFocused ? '#fff' : '#f8fafc',
								boxShadow: state.isFocused ? '0 0 0 4px rgba(242,116,32,0.08)' : 'none',
								'&:hover':{borderColor:'#cbd5e1'}, transition:'all 0.2s',
							}),
							menuPortal: (base) => ({...base, zIndex:9999}),
						}}
							options={product_options}
							isLoading={loading}
							isMulti
							value={currentProduct}
							placeholder="Select Products..."
							isClearable isSearchable
							onChange={handleProductChange}
							classNamePrefix="react-select"
							menuPortalTarget={document.body}
						/>
					</div>

					{/* From Date */}
					<div style={{minWidth:'140px'}}>
						<label style={lblStyle}>From</label>
						<div style={dateBoxStyle}>
							<div style={{padding:'0 14px',display:'flex',alignItems:'center',height:'100%'}}>
								<OrangeDatePicker value={fromDate} onChange={(val) => dispatch(setFromDate(val))} />
							</div>
						</div>
					</div>

					<span style={{color:'#cbd5e1',fontSize:'16px',fontWeight:'600',paddingBottom:'12px'}}>&rarr;</span>

					{/* To Date */}
					<div style={{minWidth:'140px'}}>
						<label style={lblStyle}>To</label>
						<div style={dateBoxStyle}>
							<div style={{padding:'0 14px',display:'flex',alignItems:'center',height:'100%'}}>
								<OrangeDatePicker value={toDate} onChange={(val) => dispatch(setToDate(val))} />
							</div>
						</div>
					</div>

					{/* Divider */}
					<div style={{width:'1px',height:'44px',background:'linear-gradient(to bottom, transparent, #e2e8f0, transparent)',alignSelf:'flex-end'}}></div>

					{/* Action Buttons */}
					<div style={{paddingTop:'26px'}}><ActionButtons {...props} /></div>
				</div>
			</div>
        </div>
    );
}

function Grid(props) {
    const dispatch = useDispatch();
    const { suppliers, currentSupplier, currentCustomer, currentProduct, fromDate, toDate, customers, products } = useSelector(state => state.products);

    const [data, setData] = useState([]);
    const [searchText, setSearchText] = useState("");

    useEffect(() => {
        const fetch = async () => {
            try {
                let api = props.profitLossApi;

                const response = await axios.post(api, {
                    product_id: currentProduct.map(p => p.value),
                    start_date: fromDate,
                    end_date: toDate
                });

                if (response.data.success === true) {
                    setData(response.data.payload);
                } else {
                    setData([]);
                }
            } catch (err) {
                console.error('Failed to load ', err);
            }
        };
        if (fromDate !== "" && toDate !== "") {
            fetch();
        }
    }, [currentSupplier, currentCustomer, currentProduct, fromDate, toDate]);

    // Filter rows based on search
    const filteredData = data.filter(row =>
        Object.values(row)
            .join(" ")
            .toLowerCase()
            .includes(searchText.toLowerCase())
    );

	const hs = {fontSize:'11px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13px',color:'#374151',fontWeight:'500'};

    const numStyle = {fontSize:'13px',color:'#111827',fontWeight:'700'};

    const columns = [
        {
            name: <span style={hs}>Sl.No</span>,
            selector: (row, index) => index + 1,
			cell: (row, index) => <span style={{...cellStyle,color:'#6b7280'}}>{index + 1}</span>,
            width:'60px', grow: 0
        },
        {
            name: <span style={hs}>Product</span>,
            selector: row => row.product,
			cell: row => <span style={{...cellStyle,fontWeight:'600',color:'#111827'}}>{row.product}</span>,
            sortable: true,
        },
        {
            name: <span style={hs}>Purchased Qty</span>,
            selector: row => row.purchased_qty,
			cell: row => <span style={numStyle}>{row.purchased_qty ?? 0}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Sold Qty</span>,
            selector: row => row.sold_qty,
			cell: row => <span style={numStyle}>{row.sold_qty ?? 0}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Purchase Cost</span>,
            selector: row => row.purchase_cost,
			cell: row => <span style={cellStyle}>{Number(row.purchase_cost ?? 0).toLocaleString()}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Sales Revenue</span>,
            selector: row => row.sales_revenue,
			cell: row => <span style={cellStyle}>{Number(row.sales_revenue ?? 0).toLocaleString()}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Cus. Returns</span>,
            selector: row => row.customer_returns,
			cell: row => <span style={cellStyle}>{Number(row.customer_returns ?? 0).toLocaleString()}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Sup. Returns</span>,
            selector: row => row.supplier_returns,
			cell: row => <span style={cellStyle}>{Number(row.supplier_returns ?? 0).toLocaleString()}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Dumps</span>,
            selector: row => row.dumps,
			cell: row => <span style={cellStyle}>{Number(row.dumps ?? 0).toLocaleString()}</span>,
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Net Profit / Loss</span>,
            selector: row => {
                const revenue = Number(row.sales_revenue ?? 0);
                const cost = Number(row.purchase_cost ?? 0);
                const cusRet = Number(row.customer_returns ?? 0);
                const supRet = Number(row.supplier_returns ?? 0);
                const dumps = Number(row.dumps ?? 0);
                return revenue - cost - cusRet + supRet - dumps;
            },
			cell: row => {
                const revenue = Number(row.sales_revenue ?? 0);
                const cost = Number(row.purchase_cost ?? 0);
                const cusRet = Number(row.customer_returns ?? 0);
                const supRet = Number(row.supplier_returns ?? 0);
                const dumps = Number(row.dumps ?? 0);
                const net = revenue - cost - cusRet + supRet - dumps;
                return <span style={{fontWeight:'700',fontSize:'13px',color: net >= 0 ? '#15803d' : '#dc2626'}}>{net >= 0 ? '+' : ''}{net.toLocaleString()}</span>;
            },
            sortable: true, right: true,
        },
        {
            name: <span style={hs}>Margin %</span>,
            selector: row => {
                const revenue = Number(row.sales_revenue ?? 0);
                const cost = Number(row.purchase_cost ?? 0);
                const cusRet = Number(row.customer_returns ?? 0);
                const supRet = Number(row.supplier_returns ?? 0);
                const dumps = Number(row.dumps ?? 0);
                const net = revenue - cost - cusRet + supRet - dumps;
                return revenue > 0 ? ((net / revenue) * 100) : 0;
            },
			cell: row => {
                const revenue = Number(row.sales_revenue ?? 0);
                const cost = Number(row.purchase_cost ?? 0);
                const cusRet = Number(row.customer_returns ?? 0);
                const supRet = Number(row.supplier_returns ?? 0);
                const dumps = Number(row.dumps ?? 0);
                const net = revenue - cost - cusRet + supRet - dumps;
                const margin = revenue > 0 ? ((net / revenue) * 100) : 0;
                return <span style={{fontWeight:'700',fontSize:'12px',color: margin >= 0 ? '#15803d' : '#dc2626',background: margin >= 0 ? '#dcfce7' : '#fee2e2',borderRadius:'6px',padding:'3px 8px'}}>{margin.toFixed(1)}%</span>;
            },
            sortable: true, right: true, width:'90px',
        },
    ];

	const { handleCopy, copied } = useCopyTableData(columns, filteredData);

	const customStyles = {
		table: {
			style: {
				fontSize: '13px',
				overflow: 'visible',
				minHeight: '250px',
			},
		},
		headRow: {
			style: {
				backgroundColor: '#fafbfc',
				borderBottom: '2px solid #eef2f7',
				minHeight: '44px',
			},
		},
		headCells: {
			style: {
				fontSize: '10.5px',
				fontWeight: '700',
				color: '#64748b',
				letterSpacing: '0.6px',
				textTransform: 'uppercase',
				padding: '12px 16px',
				whiteSpace: 'nowrap',
			},
		},
		rows: {
			style: {
				borderBottom: '1px solid #f3f4f8',
				fontSize: '13px',
				minHeight: '50px',
				transition: 'all 0.12s',
				'&:hover': { backgroundColor: '#FEFAF6' },
			},
		},
		cells: {
			style: {
				fontSize: '13px',
				padding: '12px 16px',
				display: 'flex',
				alignItems: 'center',
			},
		},
		pagination: {
			style: { borderTop: '1px solid #eef2f7', fontSize: '13px' },
		},
	};

    return (
        <div style={{
			borderRadius:'20px',border:'1px solid #e8ecf2',background:'#fff',overflow:'hidden',
			boxShadow:'0 1px 3px rgba(0,0,0,0.02), 0 8px 24px rgba(0,0,0,0.04)',
		}}>
			{/* Header */}
			<div style={{padding:'18px 24px',borderBottom:'1px solid #eef2f7',display:'flex',alignItems:'center',justifyContent:'space-between'}}>
				<div style={{display:'flex',alignItems:'center',gap:'10px'}}>
					<span style={{fontSize:'15px',fontWeight:'700',color:'#1e293b'}}>List</span>
					<span onClick={handleCopy} title="Copy" style={{cursor:'pointer',padding:'4px 8px',borderRadius:'6px',border:'1px solid #e2e8f0',display:'inline-flex',alignItems:'center',transition:'all 0.15s'}}>
						<Icon name="copy" className="text-warning" />
					</span>
					{copied && <span style={{fontSize:'12px',color:'#F27420',fontWeight:'600'}}>Copied!</span>}
				</div>
				<div style={{position:'relative'}}>
					<svg style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',pointerEvents:'none'}} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
					<input type="text" placeholder="Search by name..." value={searchText}
						onChange={(e) => setSearchText(e.target.value)}
						style={{paddingRight:'32px',height:'40px',width:'260px',borderRadius:'10px',border:'1.5px solid #e2e8f0',
							fontSize:'13px',fontWeight:'500',background:'#f8fafc',padding:'0 14px 0 36px',
							outline:'none',transition:'all 0.2s',color:'#1e293b'}}
						onFocus={(e) => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';e.target.style.boxShadow='0 0 0 4px rgba(242,116,32,0.08)';}}
						onBlur={(e) => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';e.target.style.boxShadow='none';}}
					/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
				</div>
			</div>

                {/* DataTable */}
                <DataTable
                    columns={columns}
                    data={filteredData}
                    pagination
                    highlightOnHover
                    customStyles={customStyles}
                    paginationPerPage={10}
                    paginationRowsPerPageOptions={[5, 10, 20, 50]}
                />
        </div>
    );
}


function ActionButtons(props) {
	const {currentSupplier,
		currentProduct,currentCustomer,
		selectedInvoices, currentSupplierInfo, suppliers, toDate, fromDate
		} = useSelector(state => state.products)

	const openInNewTab = useOpenInNewTab();

	const statementInvoice = (e) => {
		alert('statementInvoice')
	}

	const printInvoice = (e) => {
		let url = props.printApi;
			openInNewTab(props.printApi, {
			product_id: currentProduct?.value ?? "",
			supplier_id: currentSupplier?.value ?? "",
			customer_id: currentCustomer?.value ?? "",
			start_date: fromDate,
			end_date: toDate,
			type:e
		});
	}

	const emailInvoice = (e) => {
		alert('emailInvoice')
	}

	const btnStyle = {
		height:'44px',padding:'0 18px',borderRadius:'12px',border:'1.5px solid #e2e8f0',
		background:'#fff',color:'#475569',fontSize:'12.5px',fontWeight:'600',cursor:'pointer',
		transition:'all 0.15s',display:'inline-flex',alignItems:'center',gap:'6px',outline:'none',
		whiteSpace:'nowrap',
	};
	const hoverOn = (e) => {e.currentTarget.style.borderColor='#F27420';e.currentTarget.style.color='#F27420';e.currentTarget.style.background='#FFF8F3';};
	const hoverOff = (e) => {e.currentTarget.style.borderColor='#e2e8f0';e.currentTarget.style.color='#475569';e.currentTarget.style.background='#fff';};

	return (
		<div style={{display:'flex',alignItems:'center',gap:'10px',flexWrap:'wrap'}}>
			<button style={btnStyle} type="button" onClick={() => emailInvoice('all')} onMouseOver={hoverOn} onMouseOut={hoverOff}>
				<i className="fa fa-envelope" style={{fontSize:'12px'}}></i> Email
			</button>
			<button style={btnStyle} type="button" onClick={() => printInvoice('all')} onMouseOver={hoverOn} onMouseOut={hoverOff}>
				<i className="fa fa-print" style={{fontSize:'12px'}}></i> Print
			</button>
			<button style={btnStyle} type="button" onClick={() => statementInvoice('1')} onMouseOver={hoverOn} onMouseOut={hoverOff}>
				<i className="fa fa-file-text-o" style={{fontSize:'12px'}}></i> Statement
			</button>
		</div>
	);
}

export default function ProfitLossApp(props) {
	const dispatch = useDispatch();

	useEffect(() => {

    },[])

    return (
	<div style={{maxWidth:'1440px',margin:'0 auto'}}>
		<div style={{marginBottom:'16px'}}>
			<Filters {...props} />
		</div>
		<div style={{marginBottom:'20px'}}>
			<Grid {...props} />
		</div>
		<ToastContainer autoClose={3000} />
	</div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('profit-loss-app')) {
    const id = "profit-loss-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<Provider store={store}>
			<ProfitLossApp {...props} />
		</Provider>
    );
}

import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import Form from 'react-bootstrap/Form';
import { useSelector, useDispatch } from 'react-redux'
import Select, { components as rsComponents } from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import PurchasesService from "./../services/PurchasesService";
import { getIn, Formik, Field, useField, useFormik }
    from 'formik';
import Button from 'react-bootstrap/Button';
import * as Yup from "yup";
import axios from "axios";
import { compareAsc, format } from 'date-fns';
import dateFormat from 'dateformat';
import {AlertProvider, useAlert } from "./../hooks/AlertContext";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import { formatTwoDecimal } from './../hooks/utils';
import { useWindowSize } from "./../hooks/useWindowSize";
import SupplierInvoicePayment from "./../elements/SupplierInvoicePayment"
import SpecTableLoading from "./../elements/SpecTableLoading";
import { fixedSelectStyles } from "./../utils/selectStyles";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import EmailInvoiceModal from "./../elements/EmailInvoiceModal";

export default function SupplierInvoiceApp(props) {
    // const [rowsData, setRowsData] = useState([{
    //     product: '',
    //     quantity: '',
    //     price: '',
    //     totalPrice: '',
    //     fieldToggle: '',
    //     invoiceproductid: 0,
    // }]);
	const { showAlert } = useAlert();
	const { width } = useWindowSize();
    const [rowsData, setRowsData] = useState([]);
    const [isInvoiceLoaded, setIsInvoiceLoaded] = useState(false);
    const [isSubmitted, setIsSubmitted] = useState(0);
    const [isChecked, setIsChecked] = useState(false);
	const [otherInvoiceId, setOtherInvoiceId] = useState('-');
    const [paymentId, setPaymentId] = useState(0);
    const [downloading, setDownloading] = useState(false);
    const [printing, setPrinting] = useState(false);
    const [productsList, setProductsList] = useState([]);
    const [paymentsList, setPaymentsList] = useState([]);
    const [invoiceDetail, setinvoiceDetail] = useState({});
    const [emailModalOpen, setEmailModalOpen] = useState(false);
    const [isShowpdf, setisShowpdf] = useState(false);
    const [errorData, setErrorData] = useState(false);
	const [isSavingNew, setIsSavingNew] = useState(false);
	const [editingRowIdx, setEditingRowIdx] = useState(null);
	const [pendingDeleteIdx, setPendingDeleteIdx] = useState(null);
	const [editQty, setEditQty] = useState('');
	const [editPrice, setEditPrice] = useState('');
	const [editSalePrice, setEditSalePrice] = useState('');
	const [editRemarks, setEditRemarks] = useState('');
	const [isSavingEdit, setIsSavingEdit] = useState(false);
	const [mobileSlideOpen, setMobileSlideOpen] = useState(false);
	const [mobileEditIndex, setMobileEditIndex] = useState(null);
	const [mobileSummaryOpen, setMobileSummaryOpen] = useState(false);
	// Card/List toggle for the mobile rows section (mirrors Sales/CustomerInvoiceApp behavior)
	const [purchaseForceList, setPurchaseForceList] = useState(() => localStorage.getItem('ts_purchase_invoice_view') === 'list');
	const [mobileSearch, setMobileSearch] = useState('');
	const [mobileFilterOpen, setMobileFilterOpen] = useState(false);
	const [activeDateField, setActiveDateField] = useState(null);
	const [pendingDate, setPendingDate] = useState(null);
	const [expandedCardIndex, setExpandedCardIndex] = useState(null);
	const [editingDate, setEditingDate] = useState(false);
	const [purchaseDate, setPurchaseDate] = useState('');
	const [dateSaved, setDateSaved] = useState(false);
	const [paidAmount, setPaidAmount] = useState(0);
	const [pendingAmount, setPendingAmount] = useState(0);
	const [editingSupplier, setEditingSupplier] = useState(false);
	const [editingRemark, setEditingRemark] = useState(false);
	const [tempDate, setTempDate] = useState('');
	const [tempSupplier, setTempSupplier] = useState('');
	const [suppliersList, setSuppliersList] = useState([]);
	const loadSuppliers = async () => {
		try {
			const res = await axios.get('/payments/supplier_payment/create/suppliers/list');
			if (res.data.success) {
				setSuppliersList(res.data.payload.map(s => ({ value: s.id, label: s.name }))
					.sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' })));
			}
		} catch(err) { console.error('Failed to load suppliers', err); }
	};
	// Load suppliers on mount
	useEffect(() => { loadSuppliers(); }, []);
	
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

	const handleDownload = async (e) => {
		if (e) e.preventDefault();
		setDownloading(true);
		try {
			const response = await axios.get("/data_entry/purchase_entry/invoice/invoiceexcel/"+props.id, {responseType: 'blob'});
			const url = window.URL.createObjectURL(new Blob([response.data], {type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}));
			const link = document.createElement('a');
			link.href = url;
			link.setAttribute('download', 'purchase_invoice_'+props.id+'.xlsx');
			document.body.appendChild(link);
			link.click();
			link.remove();
			window.URL.revokeObjectURL(url);
		} catch(err) {
			notifyError('Download failed');
		} finally { setDownloading(false); }
	};

	const handlePrint = (e) => {
		if (e) e.preventDefault();
		setPrinting(true);
		window.open("/data_entry/purchase_entry/invoice/invoiceview/"+props.id, '_blank');
		setTimeout(() => setPrinting(false), 2000);
	};

    var subTotal = 0;
    useEffect(() => {
        PurchasesService.allInvoiceDetail(props.id).then(response => {
            if(response.data){
                const productsData = response.data;
                if(productsData.length >=1){
                    setisShowpdf(true);
                }
                productsData.forEach(element => {
                    subTotal += element.totalPrice;
                });
                productsData.push({
                    product: '',
                    quantity: '',
					remarks:'',
                    price: '',
					selected: 0,
                    totalPrice: '',
                    fieldToggle: '',
                    invoiceproductid: 0,
                });
                setRowsData(productsData);
            }
        }).finally(() => {
            // Mark fetch as resolved so the empty-state placeholder stops showing during initial load.
            setIsInvoiceLoaded(true);
        });
    },[])

    useEffect(()=>{
        setIsChecked(true);
        rowsData.forEach(row => {
            if(row.fieldToggle!='checked')
            {
              setIsChecked(false)
            }
            });
    },[rowsData])
    // const [subTotal, setSubTotal] = useState();
    const refetchInvoicePayments = () => {
        const getdatainvoiceid = {  getInvoiceId: props.id };
        PurchasesService.invoiceDetail(getdatainvoiceid).then(response => {
            const d = response.data;
            if (!d || !d.id) return;
            setPaidAmount(d.paid_amount || 0);
            setPendingAmount(d.pending_amount || 0);
            if (d.invoice_payment) setPaymentId(d.invoice_payment.payment_id);
        }).catch(err => console.error('Invoice payments refresh error:', err));
    };

    // sessionStorage helpers — products list rarely changes within a session.
    // Serve cached copy instantly then refresh in background so subsequent loads are near-instant.
    const _cacheGet = (key, ttlMs) => {
        try {
            const raw = sessionStorage.getItem(key);
            if (!raw) return null;
            const { t, v } = JSON.parse(raw);
            if (Date.now() - t > ttlMs) return null;
            return v;
        } catch (e) { return null; }
    };
    const _cacheSet = (key, v) => {
        try { sessionStorage.setItem(key, JSON.stringify({ t: Date.now(), v })); } catch (e) {}
    };

    useEffect(() => {
        const CACHE_TTL = 5 * 60 * 1000; // 5 min
        const cachedProducts = _cacheGet('si_productsList', CACHE_TTL);
        if (cachedProducts) setProductsList(cachedProducts);

        // Always re-fetch in the background to freshen cache, but UI already has data.
        PurchasesService.productsList().then(response => {
            if (response.data) { setProductsList(response.data); _cacheSet('si_productsList', response.data); }
        });
        const getdatainvoiceid = {  getInvoiceId: props.id };
        PurchasesService.invoiceDetail(getdatainvoiceid).then(response => {
            const d = response.data;
            if (!d || !d.id) return;
            setPaymentId("");
            if(d.invoice_payment){
                setPaymentId(d.invoice_payment.payment_id);
            }
           const dateStr = d.formatted_date || '';
           const supplierName = d.supplier ? d.supplier.name : '';
           let displayDate = '';
           if (dateStr) {
               try { displayDate = format(new Date(dateStr + 'T12:00:00'), 'dd MMM yyyy'); } catch(e) { displayDate = dateStr; }
           }
           setinvoiceDetail({id:d.id, other_invoice_id:d.other_invoice_id, supplier:supplierName, supplier_id: d.supplier ? d.supplier.id : null, supplier_email: d.supplier ? d.supplier.email : '', created_date:displayDate});
           if (dateStr) setPurchaseDate(dateStr);
           setPaidAmount(d.paid_amount || 0);
           setPendingAmount(d.pending_amount || 0);
        }).catch(err => console.error('Invoice detail error:', err));



    }, []);
    var porterageVal = 0;
    var vatVal = 0;
    var subTotal = 0;
    var invoiceTotal = 0;
    if (rowsData.length > 0) {
        rowsData.forEach(element => {
            subTotal += element.totalPrice;
        });
    }
    if (subTotal == 0) {
        invoiceTotal = 0;
    } else {
        invoiceTotal = (parseFloat(porterageVal) + parseFloat(vatVal) + parseFloat(subTotal));
    }
	
    const [addRowKey, setAddRowKey] = useState(0);
    const addTableRows = () => {
        const rowsInput = {
            product: '',
            quantity: '',
            price: '',
            sale_price: '',
            remarks: '',
            totalPrice: '',
            fieldToggle: '',
            invoiceproductid: 0,
        }
        setRowsData([...rowsData, rowsInput]);
        setAddRowKey(k => k + 1);
    }
    const confirmDeleteProduct = () => {
        if (pendingDeleteIdx === null) return;
        const index = pendingDeleteIdx;
        const invoiceproductid = rowsData[index]?.invoiceproductid;
        setPendingDeleteIdx(null);
        deleteTableRowsConfirmed(index, invoiceproductid);
    };

    const deleteTableRows = (index,invoiceproductid) => {
        if (invoiceproductid != 0) { setPendingDeleteIdx(index); return; }
        deleteTableRowsConfirmed(index, invoiceproductid);
    };

    const deleteTableRowsConfirmed = (index,invoiceproductid) => {
        // Prevent deleting the last saved product
        const savedCount = rowsData.filter(r => r.invoiceproductid && r.invoiceproductid != 0).length;
        if(invoiceproductid != 0 && savedCount <= 1){
            notifyError('Cannot delete the last product. Invoice must have at least one product.');
            return;
        }

        const rows = [...rowsData];

        if(invoiceproductid != 0){

           const deleteData = {  invoiceproductid: invoiceproductid, invoiceId: props.id };

           PurchasesService.deleteSingleInvoice(deleteData)
                .then(response => {
                    if (response.data.success === true) {
                        rows.splice(index, 1);
                        setRowsData(rows);
                        if(response.data.payload.pdfshow === 0){
                            setisShowpdf(false);
                        }
                    }else if(response.data.success === false){
						//showAlert(response.data.payload, "danger")
						notifyError(response.data.payload)
					}else{
                        alert('There is Some Error!')
                    }
                });

        }else{
            rows.splice(index, 1);
            setRowsData(rows);

        }


        // rows.splice(index, 1);
        // setRowsData(rows);
    }
    /*const handleProductChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['product'] = evnt.target.value;
        setRowsData(rowsInput);
        setErrorData(false);
    }*/
	
	const isAnySelected = (rowsData) => {
	  return rowsData.some(row => row.selected === 1);
	};
	
	const deleteSelected = (e) => {
		let rows = rowsData
		  .map((row, index) => ({ row: index, selected: row.selected, invoiceproductid: row.invoiceproductid })) // attach index
		  .filter(row => row.selected === 1) // only selected rows
		  .map(row => ({ row: row.row, invoiceproductid: row.invoiceproductid })); // final structure
		
		PurchasesService.deleteInvoiceProducts(rows)
			.then(response => {
				if (response.data.success === true) {
					
				}else if(response.data.success === false){
					//showAlert(response.data.payload, "danger")
					//notifyError(response.data.payload)
				}else{
					alert('There is Some Error!')
				}
			});
	}
	
	const checkUncheckAll = (e) => {
		const updatedRows = rowsData.map(row => ({
		  ...row,
		  selected: e.target.checked === true ? 1 : 0
		}));

		setRowsData(updatedRows);
		setErrorData(false);
	}
	
	const handleSelection = (index,e) => {
		const rowsInput = [...rowsData];
        rowsInput[index]['selected'] = e.target.checked === true ? 1 : 0;
        setRowsData(rowsInput);
        setErrorData(false);
	}
	
	const handleProductChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['product'] = evnt;
        setRowsData(rowsInput);
        setErrorData(false);
    }
    const handlePriceChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['price'] = +(evnt.target.value);
        rowsInput[index]['totalPrice'] = (+(evnt.target.value)) * (rowsInput[index]['quantity']);
        setRowsData(rowsInput);
        setErrorData(false);
    }
    const handleSalePriceChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['sale_price'] = evnt.target.value ? +(evnt.target.value) : '';
        setRowsData(rowsInput);
    }
    const handleQtyChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        rowsInput[index]['quantity'] = +(evnt.target.value);
        rowsInput[index]['totalPrice'] = (+(evnt.target.value)) * (rowsInput[index]['price']);
        setRowsData(rowsInput);
        setErrorData(false);
    }
	
	const handleRemarksChange = (index, evnt) => {
	  const rowsInput = [...rowsData];
        rowsInput[index]['remarks'] = evnt.target.value;
        // setRowsData(rowsInput);
		// uncought error.
        //[...rowsData[index], rowsInput[index]];
        setErrorData(false);
	};
	
    const handleToogleChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        let fieldData = rowsInput[index];

        // Validation on fieldData
        const { product, quantity, remarks, selected,price, totalPrice, fieldToggle } = fieldData;
        if(!fieldData.product || fieldData.quantity === "" || fieldData.quantity === undefined){
            setErrorData(true);
        } else {
            /*setErrorData(false);
            rowsInput[index]['fieldToggle']='checked';
            setRowsData(rowsInput);*/
            fieldData = { ...fieldData, invoiceId: props.id, indexvalue: index };
            // Submit form
			setIsSavingNew(true)
            PurchasesService.addSingleInvoice(fieldData)
                    .then(response => {
                        if (response.data.success === true) {
							setTimeout(() => {
								setErrorData(false);
								rowsInput[index]['fieldToggle']='checked';
							}, 50);
							
							setTimeout(() => {
								const rowsInput = [...rowsData];
								rowsInput[response.data.payload.indexvalue]['invoiceproductid'] = response.data.payload.invoiceproductid;
								setRowsData(rowsInput);
								addTableRows();
								setisShowpdf(true);	
							}, 100);
							//showAlert("Product Added Successfully!", "success");
							setIsSavingNew(false)
							
                        }else if(response.data.success === false){
							//showAlert(response.data.payload, "danger");
							notifyError(response.data.payload)
							setIsSavingNew(false)
						}else{
                            if(response.data.status === '208'){
                                alert('Invoice Already Created .')
                                window.location.href = '/data_entry/sales_entry/create';
                            }else{
								alert('There is Some Error!')
                            }
							setIsSavingNew(false)
                        }
                    });
        }


    }
    const handleEditChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        let fieldData = rowsInput[index];
        rowsInput[index]['fieldToggle']='';
        setRowsData(rowsInput);

        // Validation on fieldData
        const { product, quantity, price, totalPrice, fieldToggle } = fieldData;
        if(!product || !quantity || fieldToggle) {
            // Show alert
            return;
        };

        fieldData = { ...fieldData, invoiceId: props.id, indexvalue: index };
    }
    const handleUpdateChange = (index, evnt) => {
        const rowsInput = [...rowsData];
        let fieldData = rowsInput[index];
        rowsInput[index]['fieldToggle']='';
        setRowsData(rowsInput);

        // Validation on fieldData
        const { product, quantity, price, totalPrice, fieldToggle } = fieldData;
        fieldData = { ...fieldData, invoiceId: props.id, indexvalue: index };
        // Submit form
        PurchasesService.editSingleInvoice(fieldData)
                .then(response => {
                    if (response.data.success === true) {
                        const rowsInput = [...rowsData];
                        rowsInput[response.data.payload.indexvalue]['invoiceproductid'] = response.data.payload.invoiceproductid;
                        rowsInput[response.data.payload.indexvalue]['fieldToggle'] ="checked";
                        setRowsData(rowsInput);
                        setisShowpdf(true);
                    }else if(response.data.success === false){
						//showAlert(response.data.payload, "danger");
						notifyError(response.data.payload)
					}else{
                        if(response.data.status === '208'){
                            alert('Invoice Already Created .')
                            window.location.href = '/data_entry/sales_entry/create';

                        }else{
							alert('There is Some Error!')

                        }
                    }
                });
    }

    const startEditRow = (origIdx, data) => {
        setEditingRowIdx(origIdx);
        setEditQty(data.quantity);
        setEditPrice(data.price);
        setEditSalePrice(data.sale_price || '');
        setEditRemarks(data.remarks || '');
    };

    const cancelEditRow = () => {
        setEditingRowIdx(null);
        setEditQty('');
        setEditPrice('');
        setEditSalePrice('');
        setEditRemarks('');
    };

    const saveEditRow = (origIdx) => {
        if (!editQty || Number(editQty) <= 0) { notifyError('Enter valid quantity'); return; }
        if (!editPrice || Number(editPrice) <= 0) { notifyError('Enter valid price'); return; }
        setIsSavingEdit(true);
        const rowsInput = [...rowsData];
        rowsInput[origIdx]['quantity'] = Number(editQty);
        rowsInput[origIdx]['price'] = Number(editPrice);
        rowsInput[origIdx]['sale_price'] = editSalePrice ? Number(editSalePrice) : '';
        rowsInput[origIdx]['totalPrice'] = Number(editQty) * Number(editPrice);
        rowsInput[origIdx]['remarks'] = editRemarks;
        rowsInput[origIdx]['fieldToggle'] = '';
        setRowsData(rowsInput);

        const fieldData = { ...rowsInput[origIdx], invoiceId: props.id, indexvalue: origIdx };
        PurchasesService.editSingleInvoice(fieldData)
            .then(response => {
                if (response.data.success === true) {
                    const updated = [...rowsData];
                    updated[response.data.payload.indexvalue]['fieldToggle'] = 'checked';
                    setRowsData(updated);
                    setEditingRowIdx(null);
                    notifySuccess('Product updated');
                } else {
                    notifyError(response.data.payload || 'Update failed');
                }
            })
            .catch(() => notifyError('Update failed'))
            .finally(() => setIsSavingEdit(false));
    };

    const formik = useFormik({
        initialValues: {
            rowsdata: rowsData,
            invoiceId: props.id,
            status:''
        },
        enableReinitialize: true,
        onSubmit: (values, { resetForm }) => {

            console.log('values'+ values)

            setIsSubmitted(1)
            PurchasesService.addInvoice(values)
                .then(response => {
                    console.log(response);
                    if (response.data.success === true) {
                        window.location.href = '/data_entry/sales_entry/invoice/invoiceview/'+response.data.payload.id;
                    }else{

                        if(response.data.status === '208'){
                            alert('Invoice Already Created .')
                            window.location.href = '/data_entry/sales_entry/create';

                        }else{
                        alert('There is Some Error!')

                        }
                    }

                });
        }
    });
    const { handleSubmit, values, setFieldValue, errors } = formik;

    const handleonchange = (event) => {
        setFieldValue('status', event.target.value);
    }
	
	const handleOtherInvoiceIdChange = (e) => {
		setOtherInvoiceId(e.target.value)
	}
	
	const handleOtherInvoiceIdChangeSubmit = async	(id) => {
		const response = await axios.post(
			"/data_entry/purchase_entry/change_other_invoice/edit",
			{other_invoice_id:otherInvoiceId,id:id}
			);
		if(response.data.success === true){
			notifySuccess("Updated Successfully")
		}else{
			notifyError(response.data.payload)
		}
	} 

    /*let allProducts = (productsList).map((product) => {
        return <option id={product.id} value={product.id}>{product.name}</option>;
    });*/
	let allProducts = productsList.map((product) => {
		return {
			label: product.name,
			value: product.id
		};
	}).sort((a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' }));

    return (
        <>
            <style>{`
                .si-download-btn {
                    display: inline-flex; align-items: center; gap: 6px;
                    height: 36px; background: #fff; border: 1.5px solid #F27420;
                    color: #F27420; border-radius: 6px; padding: 0 40px;
                    font-size: 13px; font-weight: 600; text-decoration: none;
                    cursor: pointer; white-space: nowrap;
                    box-shadow: 0 1px 4px rgba(242,116,32,0.12);
                    transition: background 0.2s, color 0.2s;
                }
                .si-download-btn:hover {
                    background: #F27420 !important;
                    color: #fff !important;
                    text-decoration: none;
                }
                .si-print-btn {
                    display: inline-flex; align-items: center; gap: 6px;
                    height: 36px; background: #F27420; border: 1.5px solid #F27420;
                    color: #fff; border-radius: 6px; padding: 0 40px;
                    font-size: 13px; font-weight: 600; text-decoration: none;
                    cursor: pointer; white-space: nowrap;
                    box-shadow: 0 1px 4px rgba(242,116,32,0.12);
                    transition: background 0.2s, color 0.2s;
                }
                .si-print-btn:hover {
                    background: #fff !important;
                    color: #F27420 !important;
                    text-decoration: none;
                }
                @media (max-width: 767px) {
                    .mt-2, .my-2 { margin-top: 0 !important; }
                }
            `}</style>
            {console.log(invoiceDetail)}
            <div className="row">
                {width < 768 ? (
                    <div className="col-12">
                        {/* Header card — standalone (Back + Invoice # + Pay Invoice) */}
                        <div style={{borderRadius:'16px',border:'1px solid #eaecf2',background:'#fff',overflow:'visible',boxShadow:'0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)',marginBottom:'14px'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'12px',padding:'12px 16px'}}>
                                <a href="/daily_report/daily_book_purchase/view/index" style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',textDecoration:'none',flexShrink:0,boxShadow:'0 2px 6px rgba(234,88,12,0.3)'}}>
                                    <i className="fa fa-chevron-left" style={{fontSize:'14px',color:'#fff'}}></i>
                                </a>
                                <div style={{flex:1}}>
                                    <div style={{fontSize:'17px',fontWeight:'800',color:'#1e293b'}}>Invoice #{invoiceDetail.other_invoice_id || invoiceDetail.id || '—'}</div>
                                </div>
                                <div style={{flexShrink:0}}>
                                    <SupplierInvoicePayment mobile={true} label="Pay Invoice" currency={props.currency} total={formatTwoDecimal(invoiceTotal)} supplier={invoiceDetail} {...props} onFormChange={refetchInvoicePayments}/>
                                </div>
                            </div>
                        </div>

                        {/* ── SUMMARY / Search / Actions — OUTSIDE the card (standalone) ── */}
                        <div style={{marginBottom:'14px'}}>
                            {/* Collapsible Summary — standalone card */}
                            <div style={{marginBottom:'10px'}}>
                                <div onClick={()=>setMobileSummaryOpen(v=>!v)} style={{borderRadius: mobileSummaryOpen ? '14px 14px 0 0' : '14px',border:'1px solid #ecedf1',borderBottom: mobileSummaryOpen ? '1px solid #f0f0f0' : '1px solid #ecedf1',background:'#fff',boxShadow:'0 1px 3px rgba(15,23,42,0.06)',padding:'10px 12px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                                        <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'rgb(234, 88, 12)'}}/>
                                        <span style={{fontSize:'10px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Summary</span>
                                    </div>
                                    <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                                        <div style={{display:'flex',gap:'8px'}}>
                                            <span style={{fontSize:'12px',fontWeight:'700',color:'#374151'}}>{formatTwoDecimal(invoiceTotal)}</span>
                                            <span style={{fontSize:'12px',fontWeight:'700',color:'#16a34a'}}>{formatTwoDecimal(paidAmount)}</span>
                                            <span style={{fontSize:'12px',fontWeight:'700',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(pendingAmount)}</span>
                                        </div>
                                        <i className={'fa fa-chevron-'+(mobileSummaryOpen?'up':'down')} style={{fontSize:'9px',color:'#9ca3af'}}/>
                                    </div>
                                </div>
                                {mobileSummaryOpen && (
                                    <div style={{borderRadius:'0 0 14px 14px',border:'1px solid #ecedf1',borderTop:'none',background:'#fff',overflow:'hidden',boxShadow:'0 1px 3px rgba(15,23,42,0.06)'}}>
                                        <div style={{display:'flex',padding:'10px 16px 12px'}}>
                                            {[
                                                {label:'Total',value:invoiceTotal,color:'#374151'},
                                                {label:'Paid',value:paidAmount,color:'#16a34a'},
                                                {label:'Pending',value:pendingAmount,color:'rgb(234, 88, 12)'},
                                            ].map(({label,value,color},i,arr)=>(
                                                <React.Fragment key={label}>
                                                    <div style={{flex:1}}>
                                                        <div style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'4px'}}>{label}</div>
                                                        <div style={{fontSize:'16px',fontWeight:'600',color,lineHeight:1}}>{props.currency} {formatTwoDecimal(value)}</div>
                                                    </div>
                                                    {i < arr.length-1 && <div style={{width:'1px',background:'#e5e7eb',margin:'0 8px',alignSelf:'stretch'}}/>}
                                                </React.Fragment>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Search + Filter */}
                            <div style={{display:'flex',alignItems:'center',gap:'8px',padding:'0 0 10px'}}>
                                <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'44px',border:'1.5px solid #e5e7eb',borderRadius:'12px',background:'#fff',padding:'0 12px',minWidth:0}}>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <input type="text" placeholder="Search product..." value={mobileSearch} onChange={e=>setMobileSearch(e.target.value)}
                                        style={{flex:1,height:'100%',border:'none',outline:'none',fontSize:'13px',color:'#374151',background:'transparent',minWidth:0}} />
                                    {mobileSearch && (
                                        <button type="button" onClick={()=>setMobileSearch('')} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',outline:'none',flexShrink:0}}>
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    )}
                                </div>
                                <button type="button" onClick={()=>setMobileFilterOpen(v=>!v)}
                                    style={{flexShrink:0,height:'44px',width:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none',boxShadow:'0 3px 10px rgba(234,88,12,0.3)'}}>
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                </button>
                            </div>

                            {/* Action buttons — Email / Print / Download cards */}
                            <div style={{display:'flex',gap:'10px',padding:'0'}}>
                                {[
                                    {label:'Email', icon:'fa-envelope-o', onClick:() => setEmailModalOpen(true)},
                                    {label:'Print', icon:'fa-print', href:"/data_entry/purchase_entry/invoice/invoiceview/"+props.id, target:'_blank'},
                                    {label:'Download', icon:'fa-download', href:"/data_entry/purchase_entry/invoice/invoicedownload/"+props.id},
                                ].map(({label,icon,href,target,onClick}) => {
                                    const st = {flex:1,height:'46px',borderRadius:'12px',border:'1px solid #eef0f3',background:'#fff',color:'#374151',fontSize:'12px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px',textDecoration:'none',outline:'none',boxShadow:'0 1px 3px rgba(0,0,0,0.05)'};
                                    const inner = <><i className={"fa "+icon} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{label}</>;
                                    return onClick ? <button key={label} type="button" onClick={onClick} style={st}>{inner}</button> : <a key={label} href={href} target={target} style={st}>{inner}</a>;
                                })}
                            </div>
                        </div>

                        {/* Filter bottom sheet — matching Sales page */}
                        {mobileFilterOpen && (<>
                                <div onMouseDown={()=>setMobileFilterOpen(false)} onTouchStart={()=>setMobileFilterOpen(false)}
                                    style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
                                <div onMouseDown={e=>e.stopPropagation()} onTouchStart={e=>e.stopPropagation()}
                                    style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'80vh',overflowY:'auto'}}>
                                    <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                                        <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
                                    </div>
                                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'8px 18px 12px'}}>
                                        <div style={{display:'flex',alignItems:'center',gap:'7px'}}>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                            <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Filters</span>
                                        </div>
                                        <button type="button" onClick={()=>setMobileFilterOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'8px',width:'28px',height:'28px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                    <div style={{padding:'0 18px 18px',display:'flex',flexDirection:'column',gap:'16px'}}>
                                        {/* Supplier Select */}
                                        <div>
                                            <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Supplier</div>
                                            <Select styles={{
                                                control: (b,s) => ({...b,minHeight:'44px',height:'44px',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:'none',background:'#fff',cursor:'pointer',paddingLeft:'8px'}),
                                                valueContainer: b => ({...b,height:'44px',padding:'0 12px'}),
                                                indicatorsContainer: b => ({...b,height:'44px'}),
                                                indicatorSeparator: () => ({display:'none'}),
                                                dropdownIndicator: b => ({...b,padding:'0 8px 0 0',color:'#94a3b8'}),
                                                singleValue: b => ({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
                                                placeholder: b => ({...b,fontSize:'13px',color:'#94a3b8'}),
                                                menu: b => ({...b,borderRadius:'12px',border:'1px solid #eaecf2',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',zIndex:9999}),
                                                menuPortal: b => ({...b,zIndex:9999}),
                                                option: (b,s) => ({...b,fontSize:'13px',fontWeight:'500',padding:'10px 14px',cursor:'pointer',backgroundColor:s.isSelected?'rgb(234, 88, 12)':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':s.isFocused?'rgb(234, 88, 12)':'#334155'}),
                                            }}
                                            components={{
                                                Control: ({children, ...cprops}) => {
                                                    const active = cprops.isFocused || cprops.hasValue;
                                                    return (
                                                        <rsComponents.Control {...cprops}>
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke={active ? 'rgb(234, 88, 12)' : '#94a3b8'} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{marginLeft:'12px',flexShrink:0,transition:'stroke 0.15s'}}><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                                            {children}
                                                        </rsComponents.Control>
                                                    );
                                                },
                                            }}
                                            options={suppliersList}
                                            value={suppliersList.find(s => s.label === invoiceDetail.supplier) || (invoiceDetail.supplier ? {label: invoiceDetail.supplier, value: invoiceDetail.supplier_id} : null)}
                                            onChange={async (opt) => {
                                                if (!opt) return;
                                                try {
                                                    const res = await axios.post('/data_entry/purchase_entry/change_supplier', { invoice_id: invoiceDetail.id, supplier_id: opt.value });
                                                    if (res.data.success) { setinvoiceDetail({...invoiceDetail, supplier: res.data.payload.supplier_name}); toast.success('Supplier updated'); }
                                                } catch(err) { toast.error('Failed'); }
                                            }}
                                            isSearchable placeholder="Select Supplier" menuPortalTarget={document.body} menuShouldScrollIntoView={false}
                                            onMenuOpen={() => loadSuppliers()} />
                                        </div>
                                        {/* Date — styled to match Sales filter date picker */}
                                        <div>
                                            <div style={{fontSize:'10px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date</div>
                                            <button type="button" onClick={()=>setActiveDateField(activeDateField==='date'?null:'date')}
                                                style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid '+(activeDateField==='date'?'rgb(234, 88, 12)':'#e2e8f0'),background:'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'10px',cursor:'pointer',outline:'none',transition:'all 0.15s'}}>
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <span style={{fontSize:'13px',fontWeight:'600',color:(pendingDate||purchaseDate)?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{pendingDate ? format(new Date(pendingDate), 'dd MMM yyyy') : (invoiceDetail.created_date || 'Select date')}</span>
                                                <i className="fa fa-chevron-right" style={{fontSize:'10px',color:'#d1d5db'}}></i>
                                            </button>
                                            {activeDateField === 'date' && (
                                                <div style={{marginTop:'10px',padding:'14px',borderRadius:'14px',border:'1px solid #fed7aa',background:'#fffcf7'}}>
                                                    <style>{`.si-inline-cal .react-datepicker{width:100%;border:none;background:transparent !important;box-shadow:none !important}.si-inline-cal .react-datepicker__month-container{width:100%;float:none;background:transparent !important}.si-inline-cal .react-datepicker__month{background:transparent !important;margin:0 !important}.si-inline-cal .react-datepicker__header{background:transparent !important;border-bottom:none;padding:0}.si-inline-cal .react-datepicker__header--custom{background:transparent !important;border-bottom:none !important;padding:0 !important}.si-inline-cal .react-datepicker__day-names,.si-inline-cal .react-datepicker__week{display:flex;justify-content:space-around}.si-inline-cal .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.4px;margin:0}.si-inline-cal .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:40px;font-size:13px;font-weight:600;color:#334155;margin:0;border-radius:50%;position:relative}.si-inline-cal .react-datepicker__day:hover:not(.react-datepicker__day--selected){background:#fff;color:rgb(234, 88, 12)}.si-inline-cal .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.si-inline-cal .react-datepicker__day--selected,.si-inline-cal .react-datepicker__day--today.react-datepicker__day--selected{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;z-index:1}.si-inline-cal .react-datepicker__day--selected::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.si-inline-cal .react-datepicker__day--outside-month{color:#cbd5e1}.si-inline-cal .react-datepicker__day--disabled{color:#e5e7eb !important;cursor:default}.si-inline-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#334155}.si-inline-cal .react-datepicker__navigation{display:none !important}.si-inline-cal .react-datepicker__current-month{display:none !important}`}</style>
                                                    {/* Header: Select date label + close */}
                                                    <div style={{display:'flex',alignItems:'center',gap:'6px',marginBottom:'10px'}}>
                                                        <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.7px',textTransform:'uppercase'}}>Select date</span>
                                                        <div style={{flex:1}}/>
                                                        <button type="button" onClick={()=>setActiveDateField(null)} style={{width:'24px',height:'24px',borderRadius:'7px',background:'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',color:'#94a3b8',border:'none',outline:'none',cursor:'pointer'}}>
                                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                    <div className="si-inline-cal">
                                                        <DatePicker inline selected={pendingDate?new Date(pendingDate+'T00:00:00'):(purchaseDate?new Date(purchaseDate+'T00:00:00'):new Date())} maxDate={new Date()}
                                                            onChange={(d)=>{ if(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); setPendingDate(y+'-'+m+'-'+dd); } setActiveDateField(null); }}
                                                            renderCustomHeader={({date,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                                                const mnthsFull=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                                                return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'10px'}}>
                                                                    <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{width:'26px',height:'26px',borderRadius:'7px',background:'#fff',border:'1px solid #fed7aa',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',color:'rgb(234, 88, 12)',opacity:prevMonthButtonDisabled?0.4:1}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                                                                    <span style={{fontSize:'13.5px',fontWeight:'800',color:'#1e293b'}}>{mnthsFull[date.getMonth()]} {date.getFullYear()}</span>
                                                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{width:'26px',height:'26px',borderRadius:'7px',background:'#fff',border:'1px solid #e5e7eb',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',outline:'none',color:'#cbd5e1',opacity:nextMonthButtonDisabled?0.4:1}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                                                </div>);
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        {/* Actions */}
                                        <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px',paddingTop:'4px'}}>
                                            <button type="button" onClick={()=>{ setPendingDate(null); setMobileFilterOpen(false); }}
                                                style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                                                Clear
                                            </button>
                                            <button type="button" onClick={()=>{
                                                if (pendingDate) {
                                                    setPurchaseDate(pendingDate);
                                                    axios.post('/data_entry/purchase_entry/change_other_invoice/edit', { id: invoiceDetail.id, date: pendingDate })
                                                        .then(() => { setinvoiceDetail({...invoiceDetail, created_date: format(new Date(pendingDate), 'dd MMM yyyy')}); toast.success('Date updated'); })
                                                        .catch(() => toast.error('Failed'));
                                                    setPendingDate(null);
                                                }
                                                setMobileFilterOpen(false);
                                            }}
                                                style={{height:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </>)}
                    </div>
                ) : (
                <>
                {/* Desktop: Full-width header bar */}
                <div className="col-12 mb-0">
                    <div style={{display:'flex',alignItems:'flex-start',justifyContent:'space-between',width:'100%'}}>
                        {/* No overflow:hidden so Pay Invoice button never gets clipped at narrow widths / browser zoom */}
                        <div style={{background:'#fff',borderRadius:'14px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',width:'100%',display:'flex',minWidth:0}}>
                            {/* Invoice badge — spans full height */}
                            <div style={{background:'rgb(234, 88, 12)',padding:'0 24px',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,minWidth:'80px',borderTopLeftRadius:'14px',borderBottomLeftRadius:'14px'}}>
                                <div style={{display:'flex',flexDirection:'column',alignItems:'center',lineHeight:1.2}}>
                                    <span style={{fontSize:'10px',fontWeight:'700',color:'rgba(255,255,255,0.8)',letterSpacing:'1.5px',textTransform:'uppercase'}}>Invoice</span>
                                    <span style={{fontSize:'22px',fontWeight:'900',color:'#fff',whiteSpace:'nowrap'}}>#{invoiceDetail.other_invoice_id || invoiceDetail.id || '—'}</span>
                                </div>
                            </div>
                            {/* Right side */}
                            <div style={{flex:1,minWidth:0}}>
                            {/* Always render single inline row at desktop widths. Supplier column shrinks freely (minWidth:0)
                                so the right-side metrics + Pay Invoice always stay inside the card boundary. */}
                            {true ? (
                            /* ── Desktop single row ── */
                            <div style={{display:'flex',alignItems:'stretch'}}>
                                <div style={{flex:1,padding:'10px 16px',borderRight:'1px solid #f0f0f0',minWidth:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'4px',marginBottom:'3px'}}>
                                        <i className="fa fa-truck" style={{fontSize:'10px',color:'#f97316'}}></i>
                                        <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase'}}>Supplier</span>
                                    </div>
                                    <Select options={suppliersList}
                                        value={suppliersList.find(s => s.label === invoiceDetail.supplier) || (invoiceDetail.supplier ? {label: invoiceDetail.supplier, value: ''} : null)}
                                        onChange={async (opt) => { if (!opt || !opt.value) return; try { const res = await axios.post('/data_entry/purchase_entry/change_supplier', { invoice_id: invoiceDetail.id, supplier_id: opt.value }); if (res.data.success) { setinvoiceDetail({...invoiceDetail, supplier: res.data.payload.supplier_name}); toast.success('Supplier updated'); } } catch(err) { toast.error('Failed'); } }}
                                        isSearchable placeholder="Select Supplier..." menuPortalTarget={document.body}
                                        styles={{ control:(b,s)=>({...b,minHeight:'34px',height:'34px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border:s.isFocused?'1.5px solid #f97316':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(249,115,22,0.08)':'none',background:'#fafafa'}), valueContainer:b=>({...b,padding:'0 10px',height:'34px'}), indicatorsContainer:b=>({...b,height:'34px'}), singleValue:b=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}), menuPortal:b=>({...b,zIndex:9999}), option:(b,s)=>({...b,fontSize:'13px',background:s.isSelected?'#f97316':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':'#334155'}) }}
                                    />
                                </div>
                                <div style={{padding:'10px 16px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'3px'}}>Date</div>
                                    <style>{`.si-date-picker-wrap{display:flex;align-items:center;gap:8px;background:#fafafa;border:1.5px solid #e5e7eb;border-radius:8px;height:34px;padding:0 10px;cursor:pointer;transition:border-color 0.15s;}.si-date-picker-wrap:hover{border-color:#f97316;background:#fff;}.si-date-picker-wrap:focus-within{border-color:#f97316;box-shadow:0 0 0 3px rgba(249,115,22,0.08);background:#fff;}.si-date-picker{padding:0;font-size:13px;font-weight:600;border:none;height:100%;color:#374151;outline:none;cursor:pointer;background:transparent;width:110px;}`}</style>
                                    <div className="si-date-picker-wrap">
                                        <OrangeDatePicker value={purchaseDate} onChange={val => { setPurchaseDate(val); axios.post('/data_entry/purchase_entry/change_other_invoice/edit', { id: invoiceDetail.id, date: val }).then(() => { setinvoiceDetail({...invoiceDetail, created_date: format(new Date(val), 'dd MMM yyyy')}); setDateSaved(true); setTimeout(() => setDateSaved(false), 2000); }).catch(() => toast.error('Failed')); }} className="si-date-picker" popperPlacement="bottom-end" />
                                    </div>
                                    {dateSaved && <i className="fa fa-check-circle" style={{fontSize:'10px',color:'#22c55e',marginLeft:'4px'}}></i>}
                                </div>
                                <div style={{display:'flex',alignItems:'center',gap:'4px',padding:'0 10px',flexShrink:0,borderRight:'1px solid #f0f0f0'}}>
                                    <button onClick={() => setEmailModalOpen(true)} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color:'#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Email"
                                        onMouseEnter={e=>{e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}
                                        onMouseLeave={e=>{e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}>
                                        <i className="fa fa-envelope-o" style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handlePrint} disabled={printing} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: printing ? '#f97316' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Print"
                                        onMouseEnter={e=>{if(!printing){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}}
                                        onMouseLeave={e=>{if(!printing){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={printing ? "fa fa-spinner fa-spin" : "fa fa-print"} style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handleDownload} disabled={downloading} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: downloading ? '#f97316' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Download"
                                        onMouseEnter={e=>{if(!downloading){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}}
                                        onMouseLeave={e=>{if(!downloading){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={downloading ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'13px'}}></i></button>
                                </div>
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>Total</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color:'#1e293b'}}>{props.currency} {formatTwoDecimal(subTotal)}</span>
                                </div>
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px',background:'#f0fdf4'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#22c55e',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>Paid</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color:'#22c55e'}}>{props.currency} {formatTwoDecimal(paidAmount)}</span>
                                </div>
                                <div style={{padding:'10px 14px',borderRight:'1px solid #f0f0f0',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center',alignItems:'center',minWidth:'75px',background:'#fff7ed'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'rgb(234, 88, 12)',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'2px'}}>Pending</span>
                                    <span style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{props.currency} {formatTwoDecimal(pendingAmount)}</span>
                                </div>
                                <div style={{padding:'8px 14px',flexShrink:0,display:'flex',alignItems:'center'}}>
                                    <SupplierInvoicePayment mobile={true} label="Pay Invoice" currency={props.currency} total={formatTwoDecimal(subTotal)} supplier={invoiceDetail} {...props} onFormChange={refetchInvoicePayments}/>
                                </div>
                            </div>
                            ) : (
                            /* ── Tablet two rows ── */
                            <>
                            <div style={{display:'flex',alignItems:'stretch'}}>
                                <div style={{flex:1,padding:'10px 16px',borderRight:'1px solid #f0f0f0',minWidth:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'4px',marginBottom:'3px'}}>
                                        <i className="fa fa-truck" style={{fontSize:'10px',color:'#f97316'}}></i>
                                        <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase'}}>Supplier</span>
                                    </div>
                                    <Select options={suppliersList}
                                        value={suppliersList.find(s => s.label === invoiceDetail.supplier) || (invoiceDetail.supplier ? {label: invoiceDetail.supplier, value: ''} : null)}
                                        onChange={async (opt) => { if (!opt || !opt.value) return; try { const res = await axios.post('/data_entry/purchase_entry/change_supplier', { invoice_id: invoiceDetail.id, supplier_id: opt.value }); if (res.data.success) { setinvoiceDetail({...invoiceDetail, supplier: res.data.payload.supplier_name}); toast.success('Supplier updated'); } } catch(err) { toast.error('Failed'); } }}
                                        isSearchable placeholder="Select Supplier..." menuPortalTarget={document.body}
                                        styles={{ control:(b,s)=>({...b,minHeight:'34px',height:'34px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border:s.isFocused?'1.5px solid #f97316':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(249,115,22,0.08)':'none',background:'#fafafa'}), valueContainer:b=>({...b,padding:'0 10px',height:'34px'}), indicatorsContainer:b=>({...b,height:'34px'}), singleValue:b=>({...b,fontSize:'13px',fontWeight:'600',color:'#1e293b'}), menuPortal:b=>({...b,zIndex:9999}), option:(b,s)=>({...b,fontSize:'13px',background:s.isSelected?'#f97316':s.isFocused?'#fff7ed':'#fff',color:s.isSelected?'#fff':'#334155'}) }}
                                    />
                                </div>
                                <div style={{padding:'10px 16px',flexShrink:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'3px'}}>Date</div>
                                    <div className="si-date-picker-wrap">
                                        <OrangeDatePicker value={purchaseDate} onChange={val => { setPurchaseDate(val); axios.post('/data_entry/purchase_entry/change_other_invoice/edit', { id: invoiceDetail.id, date: val }).then(() => { setinvoiceDetail({...invoiceDetail, created_date: format(new Date(val), 'dd MMM yyyy')}); setDateSaved(true); setTimeout(() => setDateSaved(false), 2000); }).catch(() => toast.error('Failed')); }} className="si-date-picker" popperPlacement="bottom-end" />
                                    </div>
                                    {dateSaved && <i className="fa fa-check-circle" style={{fontSize:'10px',color:'#22c55e',marginLeft:'4px'}}></i>}
                                </div>
                            </div>
                            <div style={{display:'flex',alignItems:'center',borderTop:'1px solid #f0f0f0'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'4px',padding:'0 10px',flexShrink:0}}>
                                    <button onClick={() => setEmailModalOpen(true)} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color:'#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Email"
                                        onMouseEnter={e=>{e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}
                                        onMouseLeave={e=>{e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}>
                                        <i className="fa fa-envelope-o" style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handlePrint} disabled={printing} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: printing ? '#f97316' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Print"
                                        onMouseEnter={e=>{if(!printing){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}}
                                        onMouseLeave={e=>{if(!printing){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={printing ? "fa fa-spinner fa-spin" : "fa fa-print"} style={{fontSize:'13px'}}></i></button>
                                    <button onClick={handleDownload} disabled={downloading} style={{width:'30px',height:'30px',borderRadius:'7px',border:'1px solid #e5e7eb',background:'#fff',color: downloading ? '#f97316' : '#9ca3af',display:'inline-flex',alignItems:'center',justifyContent:'center',cursor:'pointer',transition:'all 0.15s'}} title="Download"
                                        onMouseEnter={e=>{if(!downloading){e.currentTarget.style.background='#fff7ed';e.currentTarget.style.borderColor='#f97316';e.currentTarget.style.color='#f97316';}}}
                                        onMouseLeave={e=>{if(!downloading){e.currentTarget.style.background='#fff';e.currentTarget.style.borderColor='#e5e7eb';e.currentTarget.style.color='#6b7280';}}}><i className={downloading ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'13px'}}></i></button>
                                </div>
                                {/* Spacer pushes metrics + Pay to the right; metrics shrink to content so Pay stays inside the card */}
                                <div style={{flex:1}}></div>
                                <div style={{flex:'0 0 auto',padding:'6px 12px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',borderLeft:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#9ca3af',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>Total</span>
                                    <span style={{fontSize:'15px',fontWeight:'800',color:'#1e293b',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(subTotal)}</span>
                                </div>
                                <div style={{flex:'0 0 auto',padding:'6px 12px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',background:'#f0fdf4',borderLeft:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#22c55e',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>Paid</span>
                                    <span style={{fontSize:'15px',fontWeight:'800',color:'#22c55e',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(paidAmount)}</span>
                                </div>
                                <div style={{flex:'0 0 auto',padding:'6px 12px',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',background:'#fff7ed',borderLeft:'1px solid #f0f0f0',borderRight:'1px solid #f0f0f0'}}>
                                    <span style={{fontSize:'9px',fontWeight:'700',color:'#f97316',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'1px'}}>Pending</span>
                                    <span style={{fontSize:'15px',fontWeight:'800',color:'#f97316',whiteSpace:'nowrap'}}>{props.currency} {formatTwoDecimal(pendingAmount)}</span>
                                </div>
                                <div style={{padding:'6px 14px',flexShrink:0,display:'flex',alignItems:'center'}}>
                                    <SupplierInvoicePayment mobile={true} label="Pay Invoice" currency={props.currency} total={formatTwoDecimal(subTotal)} supplier={invoiceDetail} {...props} onFormChange={refetchInvoicePayments}/>
                                </div>
                            </div>
                            </>
                            )}
                            </div>{/* end right side */}
                        </div>
                    </div>
                </div>
                </>
                )}

                {/*
                <div className="col-xl-4 col-lg-6  col-md-6 mb-1">
                    <div className="card stretchclassName bg-warning">
                        <div className="card-content">
                            <div className="media align-items-stretch">
                                <div className="p-2 text-center bg-warning bg-darken-2">
                                    <i className="icon-basket-loaded font-large-2 white"></i>
                                </div>
                                <div className="p-2 white media-body">
                                    <h5>Products</h5>
                                    <h2 className="text-bold-400 mb-0">
									<i className="feather icon-arrow-up"></i> {props.productscount}</h2>
                                </div>
								<div className="p-2 white media-body">
                                    <h5>Suppliers</h5>
                                    <h2 className="text-bold-400 mb-0">
									<i className="feather icon-arrow-up"></i> {props.supplierscount}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				
				<div className="col-xl-4 col-lg-12 mb-1 col-md-12">
                    <div className="card stretchclassName bg-success" >
                        <div className="card-content">
                            <div className="media align-items-stretch ">
                                <div className="p-2 pb-1 text-center bg-success bg-darken-2">
                                    <i className="fa fa-print font-large-2 text-white"></i>
                                </div>
                                <div className="p-2 white media-body">
                                    <div className="row">
										<div className="col-6 text-center">
											<h4 className="mt-0">
												<i className="fa fa-print font-large-1 text-white"></i>
											</h4>
											<h6>
											<a className="text-white" href={"/data_entry/purchase_entry/invoice/invoiceview/"+props.id} target="_blank">
											Print Invoice
											</a>
											</h6>
											
										</div>
										<div className="col-6 text-center">
											<h4 className="mt-0">
												<i className="fa fa-file-pdf-o font-large-1 text-white"></i>
											</h4>
											<h6>
											<a className="text-white" href={"/data_entry/purchase_entry/invoice/invoicedownload/"+props.id} target="_blank">
											Download Invoice
											</a>
											</h6>
											
										</div>
									</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>*/}
				
				{/*<div className="col-12 mb-1">
					<SupplierInvoicePayment 
						currency={props.currency} 
						total={formatTwoDecimal(invoiceTotal)}
						supplier={invoiceDetail}
						{...props}/>
				</div>*/}
				
                {/* <div className="col-xl-3 col-lg-6 col-12">
                    <div className="card stretchclassName bg-danger">
                        <div className="card-content">
                            <div className="media align-items-stretch">
                                <div className="p-2 text-center bg-danger bg-darken-2">
                                    <i className="icon-user font-large-2 white"></i>
                                </div>
                                <div className="p-2 white media-body">
                                    <h5>Supplier Payment</h5>
                                    <h5 className="text-bold-400 mb-0"><i className="feather icon-arrow-down"></i> 1,238</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> */}
            </div>

            <div className="row mt-0">
				{
					isAnySelected(rowsData) === true
					?
					<>
						<div className="col-12 mb-1">
							<button onClick={(e) => deleteSelected(e)} className="btn btn-danger btn-sm"><i className="fa fa-trash"></i> Remove</button>
						</div>
					</>
					:
					<></>
				}
                {width < 768 ? (
                    <div className='col-lg-12 mt-2'>
                        {/* Outer card — overflow:hidden so the header stays put and only the inner table viewport scrolls horizontally in List view */}
                        <div style={{backgroundColor:'white',borderRadius:'12px',boxShadow:'0 2px 8px rgba(0,0,0,0.07)',overflow:'hidden',marginBottom:'12px'}}>
                            {/* Header — product count pill (left) + Card/List toggle (right). Mirrors Sales/CustomerInvoiceApp parity. */}
                            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'8px 16px',borderBottom:'1.5px solid #f0f0f0',background:'#fff',position:'relative',zIndex:2}}>
                                <span style={{display:'inline-flex',alignItems:'center',gap:'4px',background:'#fff7ed',border:'1px solid #fed7aa',borderRadius:'20px',padding:'2px 10px',fontSize:'11px',fontWeight:'700',color:'#f97316'}}>
                                    <i className='fa fa-cubes' style={{fontSize:'9px'}}></i>
                                    {rowsData.filter(r => r.fieldToggle === 'checked').length} products
                                </span>
                                <div style={{display:'inline-flex',borderRadius:'8px',overflow:'hidden',padding:'3px',gap:'3px',background:'#f1f5f9'}}>
                                    <button onClick={() => { localStorage.setItem('ts_purchase_invoice_view','card'); setPurchaseForceList(false); }}
                                        style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background: !purchaseForceList?'#fff':'transparent',cursor:'pointer',boxShadow: !purchaseForceList?'0 1px 3px rgba(0,0,0,0.1)':'none',outline:'none'}}>
                                        <i className='fa fa-th-large' style={{fontSize:'10px',color: !purchaseForceList?'#f97316':'#94a3b8'}}></i>
                                        <span style={{fontSize:'11px',fontWeight:'600',color: !purchaseForceList?'#f97316':'#94a3b8'}}>Card</span>
                                    </button>
                                    <button onClick={() => { localStorage.setItem('ts_purchase_invoice_view','list'); setPurchaseForceList(true); }}
                                        style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'26px',padding:'0 12px',border:'none',borderRadius:'6px',background: purchaseForceList?'#fff':'transparent',cursor:'pointer',boxShadow: purchaseForceList?'0 1px 3px rgba(0,0,0,0.1)':'none',outline:'none'}}>
                                        <i className='fa fa-list' style={{fontSize:'10px',color: purchaseForceList?'#f97316':'#94a3b8'}}></i>
                                        <span style={{fontSize:'11px',fontWeight:'600',color: purchaseForceList?'#f97316':'#94a3b8'}}>List</span>
                                    </button>
                                </div>
                            </div>

                            {/* Body — Card view (default) or List view (when toggled) */}
                            {!purchaseForceList ? (
                            <div style={{padding:'8px 12px 4px'}}>
                            {(() => {
                                const mobileSaved = rowsData.filter(r => r.fieldToggle === 'checked').filter(r => !mobileSearch || (r.product && typeof r.product === 'object' && r.product.label && r.product.label.toLowerCase().includes(mobileSearch.toLowerCase())));
                                const mobileIsLast = mobileSaved.length <= 1;
                                return mobileSaved.map((data) => {
                                const realIndex = rowsData.indexOf(data);
                                const isExpanded = expandedCardIndex === realIndex;
                                return (
                                    <div key={realIndex} style={{backgroundColor:'white',borderRadius:'10px',marginBottom:'8px',border:isExpanded ? '2px solid #F27420' : '1px solid #e8e8e8',overflow:'hidden'}}>
                                        <div onClick={() => setExpandedCardIndex(isExpanded ? null : realIndex)} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 12px',cursor:'pointer'}}>
                                            <div style={{flex:1,minWidth:0}}>
                                                <div style={{fontWeight:'700',fontSize:'14px',color:'#1a1a2e',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{data.product ? data.product.label : ''}</div>
                                                <div style={{fontSize:'12px',color:'#bbb',marginTop:'2px',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>{invoiceDetail.supplier}</div>
                                                <div style={{marginTop:'5px'}}>
                                                    <span style={{display:'inline-flex',alignItems:'center',gap:'4px',backgroundColor:'#f0f3f8',borderRadius:'6px',padding:'2px 8px',fontSize:'11px',color:'#666'}}>
                                                        <i className='fa fa-cube' style={{fontSize:'9px',color:'#999'}}></i>
                                                        <span>{data.quantity}</span>
                                                        <span style={{color:'#bbb'}}>·</span>
                                                        <span>{props.currency} {formatTwoDecimal(data.price)}/unit</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div style={{display:'flex',alignItems:'center',gap:'6px',marginLeft:'10px',flexShrink:0}}>
                                                <span style={{backgroundColor:isExpanded ? '#F27420' : '#FFF5ED',borderRadius:'20px',padding:'4px 10px',fontWeight:'700',fontSize:'13px',color:isExpanded ? 'white' : '#F27420'}}>{props.currency} {formatTwoDecimal(data.totalPrice)}</span>
                                                <i className={isExpanded ? 'fa fa-chevron-up' : 'fa fa-chevron-right'} style={{color:isExpanded ? '#F27420' : '#bbb',fontSize:'12px'}}></i>
                                            </div>
                                        </div>
                                        {isExpanded && (
                                            <div style={{display:'flex',gap:'8px',padding:'0 12px 10px'}}>
                                                <button style={{flex:1,borderRadius:'8px',fontWeight:'600',fontSize:'13px',backgroundColor:'#F27420',color:'white',border:'none',padding:'7px 0',cursor:'pointer'}} onClick={() => { setMobileEditIndex(realIndex); setMobileSlideOpen(true); setExpandedCardIndex(null); }}>
                                                    <i className="fa fa-edit" style={{marginRight:'4px'}}></i> Edit
                                                </button>
                                                {!mobileIsLast && (
                                                <button style={{flex:1,borderRadius:'8px',fontWeight:'600',fontSize:'13px',backgroundColor:'white',color:'#F27420',border:'2px solid #F27420',padding:'7px 0',cursor:'pointer'}} onClick={() => { deleteTableRows(realIndex, data.invoiceproductid); setExpandedCardIndex(null); }}>
                                                    <i className="fa fa-trash" style={{marginRight:'4px'}}></i> Delete
                                                </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            });
                            })()}
                            <button style={{width:'100%',marginBottom:'12px',borderRadius:'10px',border:'2px dashed #F27420',padding:'11px',fontWeight:'700',fontSize:'14px',color:'#F27420',backgroundColor:'white',cursor:'pointer'}} onClick={() => { setMobileEditIndex(rowsData.length - 1); setMobileSlideOpen(true); }}>
                                <i className="fa fa-plus-circle" style={{marginRight:'6px'}}></i> Add Product
                            </button>
                            </div>
                            ) : (
                            /* ── List view — horizontally scrollable compact table. Header is OUTSIDE the scroll viewport (see outer card overflow:hidden), so only the table scrolls. ── */
                            <>
                            <div style={{overflowX:'auto',overflowY:'hidden',WebkitOverflowScrolling:'touch'}}>
                                <div style={{minWidth:'760px'}}>
                                    {(() => {
                                        const listSaved = rowsData.filter(r => r.fieldToggle === 'checked').filter(r => !mobileSearch || (r.product && typeof r.product === 'object' && r.product.label && r.product.label.toLowerCase().includes(mobileSearch.toLowerCase())));
                                        const listIsLast = listSaved.length <= 1;
                                        const thStyle = {padding:'10px 12px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',whiteSpace:'nowrap'};
                                        const tdStyle = {padding:'10px 12px',fontSize:'13px',color:'#1e293b',borderBottom:'1px solid #f1f5f9',whiteSpace:'nowrap',verticalAlign:'middle'};
                                        return (
                                            <table style={{width:'100%',borderCollapse:'collapse'}}>
                                                <thead>
                                                    <tr>
                                                        <th style={{...thStyle,width:'40px'}}>#</th>
                                                        <th style={thStyle}>Product</th>
                                                        <th style={{...thStyle,textAlign:'right',width:'70px'}}>Qty</th>
                                                        <th style={{...thStyle,textAlign:'right',width:'100px'}}>Unit Price</th>
                                                        <th style={{...thStyle,textAlign:'right',width:'100px'}}>Sell Price</th>
                                                        <th style={thStyle}>Remarks</th>
                                                        <th style={{...thStyle,textAlign:'right',width:'100px'}}>Total</th>
                                                        <th style={{...thStyle,width:'80px',textAlign:'center'}}>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {listSaved.map((data, i) => {
                                                        const realIndex = rowsData.indexOf(data);
                                                        return (
                                                            <tr key={'lst_'+realIndex}>
                                                                <td style={{...tdStyle,color:'#94a3b8',fontWeight:'700'}}>{i + 1}</td>
                                                                <td style={{...tdStyle,fontWeight:'600',maxWidth:'180px',overflow:'hidden',textOverflow:'ellipsis'}} title={data.product ? data.product.label : ''}>{data.product ? data.product.label : ''}</td>
                                                                <td style={{...tdStyle,textAlign:'right',fontVariantNumeric:'tabular-nums'}}>{data.quantity}</td>
                                                                <td style={{...tdStyle,textAlign:'right',fontVariantNumeric:'tabular-nums'}}>{props.currency} {formatTwoDecimal(data.price)}</td>
                                                                <td style={{...tdStyle,textAlign:'right',fontVariantNumeric:'tabular-nums',color: data.sale_price ? '#1e293b' : '#cbd5e1'}}>{data.sale_price ? (props.currency + ' ' + formatTwoDecimal(data.sale_price)) : '—'}</td>
                                                                <td style={{...tdStyle,color:'#64748b',maxWidth:'160px',overflow:'hidden',textOverflow:'ellipsis'}} title={data.remarks || ''}>{data.remarks || '—'}</td>
                                                                <td style={{...tdStyle,textAlign:'right',fontWeight:'700',color:'#F27420',fontVariantNumeric:'tabular-nums'}}>{props.currency} {formatTwoDecimal(data.totalPrice)}</td>
                                                                <td style={{...tdStyle,textAlign:'center'}}>
                                                                    <div style={{display:'inline-flex',gap:'6px'}}>
                                                                        <button onClick={() => { setMobileEditIndex(realIndex); setMobileSlideOpen(true); }} title="Edit" style={{width:'30px',height:'30px',borderRadius:'7px',border:'1.5px solid #fed7aa',background:'#fff7ed',color:'#F27420',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',padding:0,outline:'none'}}>
                                                                            <i className='fa fa-pencil' style={{fontSize:'11px'}}></i>
                                                                        </button>
                                                                        {!listIsLast && (
                                                                            <button onClick={() => deleteTableRows(realIndex, data.invoiceproductid)} title="Delete" style={{width:'30px',height:'30px',borderRadius:'7px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',padding:0,outline:'none'}}>
                                                                                <i className='fa fa-trash-o' style={{fontSize:'11px'}}></i>
                                                                            </button>
                                                                        )}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        );
                                    })()}
                                </div>
                            </div>
                            <div style={{padding:'10px 12px 12px'}}>
                                <button style={{width:'100%',borderRadius:'10px',border:'2px dashed #F27420',padding:'11px',fontWeight:'700',fontSize:'14px',color:'#F27420',backgroundColor:'white',cursor:'pointer'}} onClick={() => { setMobileEditIndex(rowsData.length - 1); setMobileSlideOpen(true); }}>
                                    <i className="fa fa-plus-circle" style={{marginRight:'6px'}}></i> Add Product
                                </button>
                            </div>
                            </>
                            )}
                        </div>
                        {errorData == true ? <p className="node-error">Please fill the fields</p> : ""}
                        {mobileSlideOpen && mobileEditIndex !== null && (
                            <MobileProductSlide index={mobileEditIndex} allProducts={allProducts} />
                        )}
                    </div>
                ) : !isInvoiceLoaded ? (
                /* Loader while the invoice products API is in flight — uses the shared SpecTableLoading pill
                   (same orange pill style used on the purchase listing page) for visual consistency.
                   minHeight on the wrapper reserves enough vertical space so the page is tall enough to need a
                   scrollbar even while loading. Without this, the page starts short → no scrollbar →
                   window.innerWidth is wider; after rows render the scrollbar appears → window.innerWidth shrinks
                   by ~15px → header card visibly shifts horizontally on load. */
                <div className='col-lg-12 col-md-12 mt-2'>
                    <div style={{minHeight:'500px',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <SpecTableLoading label="Loading invoice…" inline={true} />
                    </div>
                </div>
                ) : (
                <div className='col-lg-12 col-md-12 mt-2'>
                    {/* flex-direction: column-reverse so the saved-products table renders ABOVE the add-product form — matches Sales (CustomerInvoice) page behavior. */}
                    <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden',display:'flex',flexDirection:'column-reverse'}}>
                        {/* Add product form */}
                        <div key={'addrow_'+addRowKey} style={{padding:'16px 20px',background:'#fff',borderBottom:'1px solid #eef2f7'}}>
                            {width >= 1200 ? (
                            /* ── Desktop: single row ── */
                            <div style={{display:'flex',alignItems:'flex-end',gap:'12px'}}>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Product</div>
                                    <Select options={allProducts}
                                        menuPortalTarget={document.body}
                                        styles={{
                                            control:(base,state) => ({...base,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',
                                                border: state.isFocused ? '1.5px solid #F27420' : '1.5px solid #e2e8f0',
                                                boxShadow: state.isFocused ? '0 0 0 3px rgba(242,116,32,0.08)' : 'none',background: state.isFocused ? '#fff' : '#f8fafc'}),
                                            menuPortal:(base)=>({...base,zIndex:9999}),
                                            option:(base,state)=>({...base,fontSize:'13px',backgroundColor: state.isSelected ? '#F27420' : state.isFocused ? '#FFF5ED' : '#fff',color: state.isSelected ? '#fff' : '#334155'}),
                                        }}
                                        value={rowsData.length > 0 ? rowsData[rowsData.length-1].product : null}
                                        onChange={(evnt) => handleProductChange(rowsData.length-1, evnt)}
                                        isDisabled={rowsData.length > 0 && rowsData[rowsData.length-1].invoiceproductid !== 0}
                                        placeholder="Select..."
                                    />
                                </div>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Remarks</div>
                                    <input type="text" placeholder="Remarks..."
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].remarks : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleRemarksChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'100px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Quantity</div>
                                    <input type="number" min="0.01" step="any" placeholder="Qty"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].quantity : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleQtyChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'100px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Unit Price</div>
                                    <input type="number" min="0.01" step="any" placeholder="Price"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].price : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handlePriceChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'100px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Sell Price</div>
                                    <input type="number" min="0" step="any" placeholder="Sell Price"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].sale_price : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleSalePriceChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'80px',textAlign:'right'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Price</div>
                                    <div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px'}}>
                                        {props.currency} {rowsData.length > 0 ? formatTwoDecimal(rowsData[rowsData.length-1].totalPrice || 0) : '0.00'}
                                    </div>
                                </div>
                                <button onClick={(evnt) => handleToogleChange(rowsData.length-1, evnt)} disabled={isSavingNew}
                                    style={{height:'40px',padding:'0 20px',borderRadius:'10px',border:'none',
                                        background:'rgb(234, 88, 12)',color:'#fff',
                                        fontSize:'13px',fontWeight:'700',cursor: isSavingNew ? 'not-allowed' : 'pointer',
                                        display:'flex',alignItems:'center',gap:'6px',
                                        boxShadow:'0 2px 8px rgba(234,88,12,0.3)',opacity: isSavingNew ? 0.7 : 1,flexShrink:0,
                                    }}>
                                    <i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-plus"}></i> Add
                                </button>
                            </div>
                            ) : (
                            /* ── Tablet: 2 rows ── */
                            <>
                            <div style={{display:'flex',gap:'12px',marginBottom:'12px'}}>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Product</div>
                                    <Select options={allProducts}
                                        menuPortalTarget={document.body}
                                        styles={{
                                            control:(base,state) => ({...base,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',
                                                border: state.isFocused ? '1.5px solid #F27420' : '1.5px solid #e2e8f0',
                                                boxShadow: state.isFocused ? '0 0 0 3px rgba(242,116,32,0.08)' : 'none',background: state.isFocused ? '#fff' : '#f8fafc'}),
                                            menuPortal:(base)=>({...base,zIndex:9999}),
                                            option:(base,state)=>({...base,fontSize:'13px',backgroundColor: state.isSelected ? '#F27420' : state.isFocused ? '#FFF5ED' : '#fff',color: state.isSelected ? '#fff' : '#334155'}),
                                        }}
                                        value={rowsData.length > 0 ? rowsData[rowsData.length-1].product : null}
                                        onChange={(evnt) => handleProductChange(rowsData.length-1, evnt)}
                                        isDisabled={rowsData.length > 0 && rowsData[rowsData.length-1].invoiceproductid !== 0}
                                        placeholder="Select..."
                                    />
                                </div>
                            </div>
                            <div style={{display:'flex',alignItems:'flex-end',gap:'12px'}}>
                                <div style={{flex:1,minWidth:0}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Remarks</div>
                                    <input type="text" placeholder="Remarks..."
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].remarks : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleRemarksChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'80px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Quantity</div>
                                    <input type="number" min="0.01" step="any" placeholder="Qty"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].quantity : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleQtyChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'80px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Unit Price</div>
                                    <input type="number" min="0.01" step="any" placeholder="Price"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].price : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handlePriceChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'80px'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Sell Price</div>
                                    <input type="number" min="0" step="any" placeholder="Sell Price"
                                        defaultValue={rowsData.length > 0 ? rowsData[rowsData.length-1].sale_price : ''}
                                        disabled={rowsData.length > 0 && rowsData[rowsData.length-1].fieldToggle === 'checked'}
                                        onChange={e => handleSalePriceChange(rowsData.length-1, e)}
                                        style={{height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b'}}
                                        onFocus={e => {e.target.style.borderColor='#F27420';e.target.style.background='#fff';}}
                                        onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
                                    />
                                </div>
                                <div style={{width:'70px',textAlign:'right'}}>
                                    <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px'}}>Price</div>
                                    <div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px'}}>
                                        {props.currency} {rowsData.length > 0 ? formatTwoDecimal(rowsData[rowsData.length-1].totalPrice || 0) : '0.00'}
                                    </div>
                                </div>
                                <button onClick={(evnt) => handleToogleChange(rowsData.length-1, evnt)} disabled={isSavingNew}
                                    style={{height:'40px',padding:'0 20px',borderRadius:'10px',border:'none',
                                        background:'linear-gradient(135deg,#F27420,#e0600e)',color:'#fff',
                                        fontSize:'13px',fontWeight:'700',cursor: isSavingNew ? 'not-allowed' : 'pointer',
                                        display:'flex',alignItems:'center',gap:'6px',
                                        boxShadow:'0 2px 8px rgba(242,116,32,0.3)',opacity: isSavingNew ? 0.7 : 1,flexShrink:0,
                                    }}>
                                    <i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-plus"}></i> Add
                                </button>
                            </div>
                            </>
                            )}
                            {errorData && <p style={{color:'#ef4444',fontSize:'12px',marginTop:'8px',fontWeight:'500'}}>Please fill all required fields</p>}
                        </div>

                        {/* Saved products table */}
                        {rowsData.filter(r => r.fieldToggle === 'checked').length > 0 && (
                            <div style={{overflowX:'auto'}}>
                                <table style={{width:'100%',borderCollapse:'collapse'}}>
                                    <thead>
                                        <tr>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left',width:'40px'}}>#</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'}}>Product</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'}}>Remarks</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Qty</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Price</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Sell Price</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'right'}}>Total</th>
                                            <th style={{padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',width:'50px'}}></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(() => {
                                            const savedRows = rowsData.filter(r => r.fieldToggle === 'checked');
                                            const isLastProduct = savedRows.length <= 1;
                                            const editInput = {height:'30px',borderRadius:'6px',border:'1px solid #e2e8f0',fontSize:'13px',padding:'0 8px',outline:'none',fontWeight:'600',color:'#1e293b',background:'#fff',textAlign:'right',transition:'border 0.2s'};
                                            return savedRows.map((data, idx) => {
                                                const origIdx = rowsData.indexOf(data);
                                                const isEditing = editingRowIdx === origIdx;
                                                const td = {padding:'10px 14px',borderBottom:'1px solid #f3f4f8',fontSize:'13px'};
                                                return (
                                                    <tr key={'saved_'+idx} style={{background: idx%2===0 ? '#fff' : '#fcfcfd'}}>
                                                        <td style={{...td,color:'#94a3b8'}}>{idx+1}</td>
                                                        <td style={{...td,fontWeight:'600',color:'#1e293b'}}>{data.product?.label || data.product}</td>
                                                        <td style={{...td,color:'#64748b'}}>
                                                            {isEditing
                                                                ? <input type="text" value={editRemarks} onChange={e => setEditRemarks(e.target.value)} placeholder="—" style={{...editInput,width:'100%',textAlign:'left'}}
                                                                    onFocus={e => e.target.style.borderColor='#F27420'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
                                                                : (data.remarks || '—')
                                                            }
                                                        </td>
                                                        <td style={{...td,fontWeight:'500',textAlign:'right'}}>
                                                            {isEditing
                                                                ? <input type="number" min="0.01" step="any" value={editQty} onChange={e => setEditQty(e.target.value)} style={{...editInput,width:'70px'}}
                                                                    onFocus={e => e.target.style.borderColor='#F27420'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
                                                                : data.quantity
                                                            }
                                                        </td>
                                                        <td style={{...td,fontWeight:'500',textAlign:'right'}}>
                                                            {isEditing
                                                                ? <input type="number" min="0.01" step="any" value={editPrice} onChange={e => setEditPrice(e.target.value)} style={{...editInput,width:'80px'}}
                                                                    onFocus={e => e.target.style.borderColor='#F27420'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
                                                                : <>{props.currency} {formatTwoDecimal(data.price)}</>
                                                            }
                                                        </td>
                                                        <td style={{...td,textAlign:'right',color: data.sale_price ? '#16a34a' : '#d1d5db'}}>
                                                            {isEditing
                                                                ? <input type="number" min="0" step="any" value={editSalePrice} onChange={e => setEditSalePrice(e.target.value)} placeholder="—" style={{...editInput,width:'80px'}}
                                                                    onFocus={e => e.target.style.borderColor='#F27420'} onBlur={e => e.target.style.borderColor='#e2e8f0'} />
                                                                : (data.sale_price ? <>{props.currency} {formatTwoDecimal(data.sale_price)}</> : '—')
                                                            }
                                                        </td>
                                                        <td style={{...td,fontWeight:'700',color:'#1e293b',textAlign:'right'}}>
                                                            {isEditing
                                                                ? <span style={{color:'#F27420',fontWeight:'700'}}>{props.currency} {formatTwoDecimal((Number(editQty) || 0) * (Number(editPrice) || 0))}</span>
                                                                : <>{props.currency} {formatTwoDecimal(data.totalPrice)}</>
                                                            }
                                                        </td>
                                                        <td style={{...td,textAlign:'center',width:'90px'}}>
                                                            {isEditing ? (
                                                                <div style={{display:'flex',gap:'4px',justifyContent:'center'}}>
                                                                    <button onClick={() => saveEditRow(origIdx)} disabled={isSavingEdit}
                                                                        style={{height:'28px',padding:'0 12px',borderRadius:'6px',border:'none',background:'#F27420',color:'#fff',cursor:'pointer',fontSize:'11px',fontWeight:'700',display:'flex',alignItems:'center',justifyContent:'center',gap:'4px'}} title="Save">
                                                                        <i className={isSavingEdit ? "fa fa-spinner fa-spin" : "fa fa-check"} style={{fontSize:'10px'}}></i> Save
                                                                    </button>
                                                                    <button onClick={cancelEditRow}
                                                                        style={{height:'28px',padding:'0 10px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:'#94a3b8',cursor:'pointer',fontSize:'11px',fontWeight:'600',display:'flex',alignItems:'center',justifyContent:'center'}} title="Cancel">
                                                                        Cancel
                                                                    </button>
                                                                </div>
                                                            ) : (
                                                                <div style={{display:'flex',gap:'4px',justifyContent:'center'}}>
                                                                    <button onClick={() => startEditRow(origIdx, data)}
                                                                        style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:'#F27420',cursor:'pointer',fontSize:'12px',display:'flex',alignItems:'center',justifyContent:'center'}} title="Edit">
                                                                        <i className="fa fa-pencil"></i>
                                                                    </button>
                                                                    {!isLastProduct && (
                                                                        <button onClick={() => deleteTableRows(origIdx, data.invoiceproductid)}
                                                                            style={{width:'28px',height:'28px',borderRadius:'6px',border:'1px solid #e2e8f0',background:'#fff',color:'#ef4444',cursor:'pointer',fontSize:'12px',display:'flex',alignItems:'center',justifyContent:'center'}} title="Delete">
                                                                            <i className="fa fa-trash-o"></i>
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            });
                                        })()}
                                    </tbody>
                                </table>
                            </div>
                        )}

                    </div>
                    <input type="hidden" rowsData={rowsData} {...formik.getFieldProps("rowsdata")} />
                </div>
                )}

            {(width < 768 || rowsData.length > 0) ?
                <div className={width < 768 ? undefined : "invoice-total col-lg-12 col-md-12 mb-4 mt-2 d-flex flex-column align-items-end"} style={width < 768 ? {marginRight:'0',marginTop:'12px',width:'100%',padding:'0 15px',boxSizing:'border-box'} : {marginRight:'0'}}>
                    {width < 768 ? (
                        // ── Mobile Invoice Summary — matches Sales (CustomerInvoiceApp) styling for parity ──
                        <div style={{borderRadius:'14px',overflow:'hidden',border:'1px solid #e5e7eb',background:'#fff',marginBottom:'24px',width:'100%'}}>
                            {[
                                {label:'Sub Total', value: formatTwoDecimal(subTotal), icon:'fa-calculator', color:'#374151'},
                                {label:'Porterage', value: invoiceTotal > 0 ? formatTwoDecimal(porterageVal) : '0.00', icon:'fa-truck', color:'#6b7280'},
                                {label:'VAT',       value: invoiceTotal > 0 ? formatTwoDecimal(vatVal) : '0.00',       icon:'fa-percent', color:'#6b7280'}
                            ].map((item, i) => (
                                <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 14px',borderBottom:'1px solid #f1f5f9'}}>
                                    <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                                        <div style={{width:'28px',height:'28px',borderRadius:'8px',background:'#f8fafc',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                            <i className={"fa "+item.icon} style={{fontSize:'11px',color:'#94a3b8'}}></i>
                                        </div>
                                        <span style={{fontSize:'13px',color:'#64748b',fontWeight:'500'}}>{item.label}</span>
                                    </div>
                                    <span style={{fontSize:'13px',fontWeight:'600',color:item.color,fontVariantNumeric:'tabular-nums'}}>{props.currency} {item.value}</span>
                                </div>
                            ))}
                            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 14px',background:'linear-gradient(135deg,#fff7ed,#ffedd5)'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                                    <div style={{width:'28px',height:'28px',borderRadius:'8px',background:'#f97316',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                        <i className="fa fa-tag" style={{fontSize:'11px',color:'#fff'}}></i>
                                    </div>
                                    <span style={{fontSize:'14px',fontWeight:'800',color:'#1e293b'}}>Total</span>
                                </div>
                                <span className="invoice-price" style={{fontSize:'18px',fontWeight:'800',color:'#f97316',letterSpacing:'-0.5px'}}>{props.currency} {formatTwoDecimal(invoiceTotal)}</span>
                            </div>
                        </div>
                    ) : (
                        <div style={{borderRadius:'14px',overflow:'hidden',width:'100%',maxWidth:'550px',background:'#fff',boxShadow:'0 4px 20px rgba(0,0,0,0.08)',border:'1px solid #e5e7eb'}}>
                            <div style={{padding:'14px 20px',borderBottom:'1px solid #f0f0f0'}}>
                                <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                                    <span style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>Sub Total</span>
                                    <span style={{fontSize:'14px',fontWeight:'700',color:'#374151'}}>{props.currency} {formatTwoDecimal(subTotal)}</span>
                                </div>
                            </div>
                            <div style={{padding:'14px 20px',borderTop:'2px solid #f97316',background:'#fff',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
                                <span style={{fontSize:'15px',fontWeight:'800',color:'#111827'}}>Total</span>
                                <span style={{fontSize:'18px',fontWeight:'900',color:'#f97316'}}>{props.currency} {formatTwoDecimal(subTotal)}</span>
                            </div>
                        </div>
                    )}
                    <style>{`.si-pay-wrap button{background:linear-gradient(135deg,#f97316,#ea580c)!important;color:#fff!important;border:none!important;padding:10px 28px!important;border-radius:10px!important;font-size:14px!important;font-weight:700!important;box-shadow:0 3px 10px rgba(249,115,22,0.35)!important;transition:all 0.15s!important;}.si-pay-wrap button:hover{box-shadow:0 4px 14px rgba(249,115,22,0.45)!important;transform:translateY(-1px);}.si-pay-wrap button i{color:#fff!important;font-size:14px!important;}`}</style>
                </div>
                : <></>
            }
            </div>
		{pendingDeleteIdx !== null && (
			<>
			<div style={{position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.35)',zIndex:99998}} onClick={() => setPendingDeleteIdx(null)}></div>
			<div style={{position:'fixed',top:'50%',left:'50%',transform:'translate(-50%,-50%)',zIndex:99999,background:'#fff',borderRadius:'16px',boxShadow:'0 20px 60px rgba(0,0,0,0.2)',padding:'28px 32px',maxWidth:'400px',width:'90%',textAlign:'center'}}>
				<div style={{width:'48px',height:'48px',borderRadius:'50%',background:'#fef2f2',display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 16px'}}>
					<i className="fa fa-trash-o" style={{fontSize:'20px',color:'#ef4444'}}></i>
				</div>
				<h3 style={{margin:'0 0 8px',fontSize:'17px',fontWeight:'700',color:'#1e293b'}}>Delete Product?</h3>
				<p style={{margin:'0 0 24px',fontSize:'13px',color:'#64748b',lineHeight:'1.5'}}>
					{rowsData[pendingDeleteIdx]?.product?.label ? (
						<>Are you sure you want to delete <strong style={{color:'#1e293b'}}>{rowsData[pendingDeleteIdx].product.label}</strong>? This cannot be undone.</>
					) : 'Are you sure you want to delete this product? This cannot be undone.'}
				</p>
				<div style={{display:'flex',gap:'10px',justifyContent:'center'}}>
					<button onClick={() => setPendingDeleteIdx(null)} style={{flex:1,height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer'}}>Cancel</button>
					<button onClick={confirmDeleteProduct} style={{flex:1,height:'40px',borderRadius:'10px',border:'none',background:'#ef4444',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',boxShadow:'0 2px 8px rgba(239,68,68,0.3)'}}>
						<i className="fa fa-trash-o" style={{marginRight:'6px'}}></i>Delete
					</button>
				</div>
			</div>
			</>
		)}
		<ToastContainer autoClose={3000} />
		<EmailInvoiceModal
			open={emailModalOpen}
			onClose={() => setEmailModalOpen(false)}
			apiUrl={`/data_entry/purchase_entry/invoice_email/send/${props.id}`}
			invoiceId={invoiceDetail.id}
			invoiceNumber={invoiceDetail.other_invoice_id || invoiceDetail.id}
			partyLabel="Supplier"
			partyName={invoiceDetail.supplier}
			partyEmail={invoiceDetail.supplier_email}
			invoiceDate={invoiceDetail.created_date}
		/>
        </>

    );

    function MobileProductSlide({ index, allProducts }) {
        const data = rowsData[index] || {};
        const isEditing = !!(data.invoiceproductid && data.invoiceproductid !== 0);
        const [localProduct, setLocalProduct] = useState(data.product || null);
        const [localQty, setLocalQty] = useState(data.quantity !== undefined && data.quantity !== '' ? String(data.quantity) : '');
        const [localPrice, setLocalPrice] = useState(data.price !== undefined && data.price !== '' ? String(data.price) : '');
        const [localSalePrice, setLocalSalePrice] = useState(data.sale_price !== undefined && data.sale_price !== '' ? String(data.sale_price) : '');
        const [localRemarks, setLocalRemarks] = useState(data.remarks || '');
        const [localError, setLocalError] = useState(false);
        const panelRef = useRef(null);

        // Mobile: render as a slide-up bottom sheet. `mounted` flips after first paint
        // so the transform transition animates the sheet in from the bottom.
        const [mounted, setMounted] = useState(false);
        useEffect(() => { const r = requestAnimationFrame(() => setMounted(true)); return () => cancelAnimationFrame(r); }, []);

        const handleSave = () => {
            if (!localProduct || localQty === '' || localPrice === '') {
                // Only name the fields the user actually left empty.
                const missing = [];
                if (!localProduct) missing.push('Product');
                if (localQty === '') missing.push('Quantity');
                if (localPrice === '') missing.push('Unit Price');
                setLocalError('Please fill ' + (missing.length === 1 ? missing[0] : missing.slice(0, -1).join(', ') + ' and ' + missing[missing.length - 1]));
                return;
            }
            rowsData[index]['product'] = localProduct;
            rowsData[index]['quantity'] = +localQty;
            rowsData[index]['price'] = +localPrice;
            rowsData[index]['sale_price'] = localSalePrice ? +localSalePrice : '';
            rowsData[index]['remarks'] = localRemarks;
            rowsData[index]['totalPrice'] = (+localQty) * (+localPrice);
            const fieldData = { ...rowsData[index], invoiceId: props.id, indexvalue: index };
            setIsSavingNew(true);
            const serviceCall = isEditing ? PurchasesService.editSingleInvoice : PurchasesService.addSingleInvoice;
            serviceCall(fieldData).then(response => {
                if (response.data.success === true) {
                    setMobileSlideOpen(false);
                    setTimeout(() => {
                        const ri = [...rowsData];
                        ri[index]['fieldToggle'] = 'checked';
                        ri[response.data.payload.indexvalue]['invoiceproductid'] = response.data.payload.invoiceproductid;
                        setRowsData(ri);
                        if (!isEditing) addTableRows();
                        setisShowpdf(true);
                    }, 100);
                    setIsSavingNew(false);
                } else if (response.data.success === false) {
                    notifyError(response.data.payload);
                    setIsSavingNew(false);
                } else {
                    alert('There is Some Error!');
                    setIsSavingNew(false);
                }
            });
        };

        // Shared label style — matches Sales (CustomerInvoiceApp) for visual parity
        const lbl = {fontSize:'11px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'5px',display:'block'};
        // Edit gets the orange outline + brighter shadow (Sales parity); Add gets the soft shadow
        const cardStyle = isEditing
            ? {background:'#fff',borderRadius:'12px',outline:'2px solid #f97316',outlineOffset:'-2px',boxShadow:'0 4px 16px rgba(249,115,22,0.10)',marginTop:'6px',overflow:'hidden'}
            : {background:'#fff',borderRadius:'12px',overflow:'hidden',boxShadow:'0 4px 18px rgba(0,0,0,0.12)',marginTop:'6px'};

        return (<>
            {/* Backdrop — tap to close */}
            <div onMouseDown={() => setMobileSlideOpen(false)} onTouchStart={() => setMobileSlideOpen(false)}
                 style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}} />
            {/* Bottom sheet */}
            <div ref={panelRef} onMouseDown={e => e.stopPropagation()} onTouchStart={e => e.stopPropagation()}
                 style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',
                         paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',
                         maxHeight:'88vh',overflowY:'auto',
                         transform: mounted ? 'translateY(0)' : 'translateY(100%)', transition:'transform .28s ease'}}>
                <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                    <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
                </div>
                {/* Header */}
                <div style={{padding:'10px 14px',display:'flex',justifyContent:'space-between',alignItems:'center',borderBottom:'1.5px solid #f0f0f0'}}>
                    <span style={{color:'#1e293b',fontSize:'14px',fontWeight:'700',display:'flex',alignItems:'center',gap:'6px',flex:1,minWidth:0,overflow:'hidden'}}>
                        <i className={isEditing ? "fa fa-pencil-square-o" : "fa fa-plus-circle"} style={{color:'#f97316'}}></i>
                        <span style={{whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{isEditing ? (localProduct ? localProduct.label : 'Edit Product') : 'Add New Product'}</span>
                    </span>
                    <button onClick={() => setMobileSlideOpen(false)} style={{background:'#f3f4f6',border:'none',color:'#666',fontSize:'13px',cursor:'pointer',padding:'4px 10px',borderRadius:'6px',flexShrink:0}}>✕</button>
                </div>
                {/* Body */}
                <div style={{padding:'14px 16px'}}>
                    {/* Product */}
                    <div style={{marginBottom:'14px'}}>
                        <label style={lbl}>Product</label>
                        <Select key={'mob_purchase_product_'+index} options={allProducts} menuPortalTarget={document.body}
                            styles={{...fixedSelectStyles({width:'100%',maxWidth:'100%'}),control:(b,s)=>({...b,minHeight:'42px',fontSize:'14px',fontWeight:'600',borderRadius:'10px',border:s.isFocused?'1.5px solid #f97316':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(249,115,22,0.08)':'none',background:'#fafbfc'}),menuPortal:(base)=>({...base,zIndex:9999})}}
                            defaultValue={localProduct}
                            onChange={(val) => setLocalProduct(val)}
                            placeholder="Search product..." />
                    </div>
                    {/* Qty + Unit Price + Sell Price — 3 columns on mobile, premium inputs */}
                    <div style={{display:'flex',gap:'8px',marginBottom:'14px',alignItems:'flex-end'}}>
                        <div style={{flex:1,minWidth:0}}>
                            <label style={lbl}>Quantity</label>
                            <input key={'mob_purchase_qty_'+index} type="number" min="1" step="1" defaultValue={localQty}
                                onChange={e => setLocalQty(e.target.value)}
                                onKeyDown={e => { if([".","-","+","e","E"].includes(e.key)) e.preventDefault(); }}
                                placeholder="0"
                                style={{width:'100%',height:'42px',padding:'0 10px',fontSize:'15px',fontWeight:'600',borderRadius:'10px',border:'1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}}
                                onFocus={e=>{e.target.style.borderColor='#f97316';e.target.style.background='#fff';}}
                                onBlur={e=>{e.target.style.borderColor='#e5e7eb';e.target.style.background='#fafbfc';}} />
                        </div>
                        <div style={{flex:1.2,minWidth:0}}>
                            <label style={lbl}>Unit Price</label>
                            <div style={{display:'flex',height:'42px',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e5e7eb',background:'#fafbfc'}}>
                                <span style={{padding:'0 8px',fontSize:'13px',background:'#fff7ed',color:'#f97316',fontWeight:'700',display:'flex',alignItems:'center',borderRight:'1.5px solid #e5e7eb'}}>{props.currency}</span>
                                <input key={'mob_purchase_price_'+index} type="number" min="0" defaultValue={localPrice}
                                    onChange={e => setLocalPrice(e.target.value)}
                                    placeholder="0.00"
                                    style={{flex:1,border:'none',outline:'none',padding:'0 10px',fontSize:'15px',fontWeight:'600',background:'#fafbfc',color:'#1e293b',minWidth:0,width:'100%'}}
                                    onFocus={e=>{e.target.parentElement.style.borderColor='#f97316';}}
                                    onBlur={e=>{e.target.parentElement.style.borderColor='#e5e7eb';}} />
                            </div>
                        </div>
                        <div style={{flex:1.2,minWidth:0}}>
                            <label style={lbl}>Sell Price</label>
                            <div style={{display:'flex',height:'42px',borderRadius:'10px',overflow:'hidden',border:'1.5px solid #e5e7eb',background:'#fafbfc'}}>
                                <span style={{padding:'0 8px',fontSize:'13px',background:'#fff7ed',color:'#f97316',fontWeight:'700',display:'flex',alignItems:'center',borderRight:'1.5px solid #e5e7eb'}}>{props.currency}</span>
                                <input key={'mob_purchase_sellprice_'+index} type="number" min="0" defaultValue={localSalePrice}
                                    onChange={e => setLocalSalePrice(e.target.value)}
                                    placeholder="0.00"
                                    style={{flex:1,border:'none',outline:'none',padding:'0 10px',fontSize:'15px',fontWeight:'600',background:'#fafbfc',color:'#1e293b',minWidth:0,width:'100%'}}
                                    onFocus={e=>{e.target.parentElement.style.borderColor='#f97316';}}
                                    onBlur={e=>{e.target.parentElement.style.borderColor='#e5e7eb';}} />
                            </div>
                        </div>
                    </div>
                    {/* Remarks */}
                    <div style={{marginBottom:'16px'}}>
                        <label style={lbl}>Remarks</label>
                        <input key={'mob_purchase_remarks_'+index} type="text" defaultValue={localRemarks}
                            onChange={e => setLocalRemarks(e.target.value)}
                            placeholder="Add a note..."
                            style={{width:'100%',height:'42px',padding:'0 12px',fontSize:'14px',borderRadius:'10px',border:'1.5px solid #e5e7eb',outline:'none',background:'#fafbfc',color:'#1e293b',boxSizing:'border-box'}}
                            onFocus={e=>{e.target.style.borderColor='#f97316';e.target.style.background='#fff';}}
                            onBlur={e=>{e.target.style.borderColor='#e5e7eb';e.target.style.background='#fafbfc';}} />
                    </div>
                    {/* Save Button — same gradient style as Sales for Add; flat orange for Edit (Sales parity) */}
                    {isEditing ? (
                        <button onClick={handleSave} disabled={isSavingNew} style={{width:'100%',height:'44px',fontSize:'14px',fontWeight:'700',borderRadius:'8px',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',background:'#f97316',border:'none',color:'#fff',cursor:'pointer',opacity: isSavingNew ? 0.7 : 1}}>
                            <i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-check-circle"}></i> {isSavingNew ? 'Saving...' : 'Save Changes'}
                        </button>
                    ) : (
                        <button onClick={handleSave} disabled={isSavingNew} style={{width:'100%',height:'48px',fontSize:'15px',fontWeight:'700',borderRadius:'12px',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',background:'linear-gradient(135deg,#f97316,#ea580c)',border:'none',color:'#fff',cursor:'pointer',opacity: isSavingNew ? 0.7 : 1,boxShadow:'0 3px 12px rgba(249,115,22,0.3)'}}>
                            <i className={isSavingNew ? "fa fa-spinner fa-spin" : "fa fa-check-circle"}></i> {isSavingNew ? 'Saving...' : 'Save Product'}
                        </button>
                    )}
                    {localError && <p style={{color:'#dc2626',fontSize:'12px',marginTop:'8px',marginBottom:0,display:'flex',alignItems:'center',gap:'4px'}}>
                        <i className="fa fa-exclamation-circle"></i> {localError}
                    </p>}
                </div>
            </div>
        </>);
    }

    function TableRows({ allProducts, rowsData, deleteTableRows, handleChange }) {

        const [qty, setQty] = useState('');
        const [amount, setAmount] = useState('');
		const [remark, setRemark] = useState('');
		
        return (
            rowsData.map((data, index) => {
                const { product, quantity, price, totalPrice,selected, remarks, fieldToggle, invoiceproductid } = data;
                return (
                    <tr index={index} key={"row_"+index}>
						{/*<td><input type="checkbox" defaultChecked={selected === 1}
						onClick={(evnt) => (handleSelection(index, evnt))}
						className="form-control checkbox-20 m-8l" /></td>
						*/}
                        <td>
						{/*<select value={product} disabled={fieldToggle} onChange={(evnt) => (handleProductChange(index, evnt))} name="product" className="form-control">
                                <option>Select Product</option>
                                {allProducts}
						</select>*/}
						<Select options={allProducts} 
						menuPortalTarget={document.body}
						  styles={{
                            ...fixedSelectStyles({
                                width: "100%",
                                maxWidth: "100%",
                            }),
							menuPortal: (base) => ({ ...base, zIndex: 9999 }), // Keep dropdown on top
						  }}
						//isDisabled={fieldToggle}
						isDisabled={invoiceproductid == "" ? fieldToggle : true}  
						defaultValue={product} onChange={(evnt) => (handleProductChange(index, evnt))} name="product" />
                            <input type="hidden" key={'product_'+index} value={invoiceproductid} name="invoiceproductid" className="form-control" />
                        
						{width <= 1024
							?
							<>
                            {/*<input type="text" 
							className="form-control mt-1" 
							disabled={fieldToggle}
							defaultValue={remarks}
							onChange={e => {
								handleRemarksChange(index, e)
								setRemark(e.target.value);
							}}
							placeholder="Remarks..." />
							</>*/}
                            </>
                            :
							<></>
						}
						
						</td>
						
						<td>
							<textarea className="form-control" 
                            rows={1}
                            style={{height:"38px",resize:"none",padding:"6px 10px"}}
							disabled={fieldToggle}
							defaultValue={remarks}
							onChange={e => {
								handleRemarksChange(index, e)
								setRemark(e.target.value);
							}}
							placeholder="Add Remarks..."></textarea>
							</td>

                        {
                            width >= 768
                            ?
                            <>
                                <td>
                                    <input
                                        type="number"
                                        key={'quantity_'+index}
                                        pattern="[0-9]*"
                                        defaultValue={quantity || qty}
                                        disabled={fieldToggle}
                                        onChange={evnt => {
                                            handleQtyChange(index, evnt)
                                            setQty(evnt.target.value);
                                        }}
                                        name="qty"
                                        placeholder="Qty.."
                                        className="form-control product-qty w-100" style={{padding:"6px 8px"}} />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        key={'price_'+index}
                                        pattern="[0-9]*"
                                        defaultValue={amount || price}
                                        disabled={fieldToggle}
                                        onChange={evnt => {
                                            handlePriceChange(index, evnt)
                                            setAmount(evnt.target.value);
                                        }}
                                        name="amount"
                                        placeholder="Price.."
                                        className="form-control product-price" style={{padding:"6px 8px"}} />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        key={'sale_price_'+index}
                                        min="0" step="any"
                                        defaultValue={data.sale_price || ''}
                                        disabled={fieldToggle}
                                        onChange={evnt => handleSalePriceChange(index, evnt)}
                                        placeholder="Sell.."
                                        className="form-control" style={{padding:"6px 8px"}} />
                                </td>
                            </>
                            :
                            <>
                                <td>
                                    <input
                                        type="number"
                                        key={'quantity_'+index}
                                        pattern="[0-9]*"
                                        defaultValue={quantity || qty}
                                        disabled={fieldToggle}
                                        onChange={evnt => {
                                            handleQtyChange(index, evnt)
                                            setQty(evnt.target.value);
                                        }}
                                        name="qty"
                                        placeholder="Qty.."
                                        className="form-control product-qty w-100" style={{padding:"6px 8px"}} />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        key={'price_'+index}
                                        pattern="[0-9]*"
                                        defaultValue={amount || price}
                                        disabled={fieldToggle}
                                        onChange={evnt => {
                                            handlePriceChange(index, evnt)
                                            setAmount(evnt.target.value);
                                        }}
                                        name="amount"
                                        placeholder="Price.."
                                        className="form-control product-price w-100" style={{padding:"6px 8px"}} />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        key={'sale_price_'+index}
                                        min="0" step="any"
                                        defaultValue={data.sale_price || ''}
                                        disabled={fieldToggle}
                                        onChange={evnt => handleSalePriceChange(index, evnt)}
                                        placeholder="Sell.."
                                        className="form-control w-100" style={{padding:"6px 8px"}} />
                                </td>
                            </>
                        }


                        <th className='text-right'>
                            {props.currency} {formatTwoDecimal(totalPrice)}
                        </th>
                        <td className='text-center'>
                        {
                            width >= 768 
                            ?
                            <></>
                            :
                            <></>
                        }   
						{index == (rowsData.length - 1) ?<></>: <button key={'submit_'+index} className="btn btn-danger remove-row-product px-1" onClick={() => (deleteTableRows(index,invoiceproductid))}>{width > 1024 ? <i className="fa fa-trash c-pl3 c-pr3"></i> : <i data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" className="fa fa-trash fa-1x"></i>}</button>}
						<label className="switch">
                            {/* <input type="checkbox" onChange={(evnt) => (handleToogleChange(index, evnt))} name="fieldToggle" id="checkboxlength" checked={fieldToggle} /> */}
                            {fieldToggle== "checked" ? <button className="btn btn-warning save-product-detail px-1" value={invoiceproductid} onClick={(evnt) =>handleEditChange(index,evnt)}>{width > 1024 ? <i className="fa fa-edit fa-1x"></i> : <i className="fa fa-edit fa-1x"></i>}</button>
                            : ((fieldToggle== "") && (invoiceproductid !="")) ? <button className="btn btn-success save-product-detail px-1" onClick={(evnt) =>handleUpdateChange(index,evnt)}>{width >= 768 ? "Save" : <i className="fa fa-save fa-1x"></i>}</button>
                            : <button className="btn btn-success save-product-detail px-1" disabled={isSavingNew} onClick={(evnt) =>handleToogleChange(index,evnt)}>{width >= 768 ? <><i className="fa fa-save fa-1x"></i> Save</> : <i className="fa fa-save fa-1x"></i>}</button>}
                            {/* {fieldToggle== "checked"?
                                <button className="btn btn-outline-warning save-product-detail" value={invoiceproductid} onClick={(evnt) =>handleEditChange(index,evnt)}>Edit</button>
                            :
                                <button className="btn btn-outline-success save-product-detail" onClick={(evnt) =>handleToogleChange(index,evnt)}>Save</button>
                            } */}

                            {/* <button className="btn btn-outline-success save-product-detail" onClick={(evnt) =>handleToogleChange(index,evnt)}>{fieldToggle=="checked"?"Edit":"Save"}</button> */}

                        </label></td>
                    </tr>
                )
            })
        )
    }
}

if (document.getElementById('supplier-invoice-app')) {
    const id = "supplier-invoice-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		<AlertProvider>
			<SupplierInvoiceApp {...props} />
		</AlertProvider>
    );
}

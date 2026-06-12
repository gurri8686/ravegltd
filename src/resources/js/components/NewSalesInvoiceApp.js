import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import Select from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import { formatTwoDecimal } from './../hooks/utils';
import {AlertProvider, useAlert } from "./../hooks/AlertContext";
import AddStock from "./../elements/AddStock";

const selectStyles = {
    control: (base, state) => ({...base,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',
        border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
        boxShadow: state.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none',background: state.isFocused ? '#fff' : '#f8fafc',
    }),
    menuPortal: (base) => ({...base,zIndex:9999}),
    option: (base, state) => ({...base,fontSize:'13px',
        backgroundColor: state.isSelected ? '#FFF7ED' : state.isFocused ? '#FFF7ED' : '#fff',
        color: '#334155',
        borderLeft: state.isSelected ? '3px solid rgb(234, 88, 12)' : '3px solid transparent',
    }),
    singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
};

const formatSupplierOption = (option, { context }) => {
    if (context === 'value') {
        const parts = (option.label || '').split('|');
        const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
        return <span>{option.supplier_name || option.label} {qty && <span style={{fontSize:'11px',color:'#94a3b8',fontWeight:'500'}}>({qty} in stock)</span>}</span>;
    }
    if (!option.value) return <span style={{color:'#aaa'}}>-- Select Supplier --</span>;
    const parts = (option.label || '').split('|');
    const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
    const pr = (parts.find(p => p.startsWith('P:')) || '').replace('P:','').trim();
    return (
        <div>
            <div style={{fontWeight:'600',fontSize:'13px',color:'#1e293b'}}>{option.supplier_name || option.label}</div>
            {(qty || pr) && <div style={{fontSize:'11px',color:'#64748b',marginTop:'2px'}}>
                {qty && <span style={{color:'#16a34a',fontWeight:'600'}}>Stock: {qty}</span>}
                {qty && pr && <span style={{margin:'0 8px',color:'#d1d5db'}}>|</span>}
                {pr && <span>Price: £{pr}</span>}
            </div>}
        </div>
    );
};

export default function NewSalesInvoiceApp(props) {
    const [customers, setCustomers] = useState([]);
    const [products, setProducts] = useState([]);
    const [customer, setCustomer] = useState(null);
    const [date, setDate] = useState(new Date().toISOString().slice(0,10));
    const [items, setItems] = useState([]);
    const [generating, setGenerating] = useState(false);
    const [continued, setContinued] = useState(false);

    const handleContinue = async () => {
        if (!customer) { toast.error('Please select a customer'); return; }
        setGenerating(true);
        try {
            const res = await axios.post(props.generateApi, {
                customer_id: customer.value,
                date,
                products: [],
            });
            if (res.data.success) {
                toast.success('Invoice created!');
                window.location.href = '/data_entry/sales_entry/invoice/' + res.data.invoice_id;
            } else {
                toast.error(res.data.message || 'Failed to create invoice');
            }
        } catch(err) {
            toast.error(err.response?.data?.message || 'Failed to create invoice');
        } finally { setGenerating(false); }
    };
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
    const [dateOpen, setDateOpen] = useState(false);
    const [nsiMonthOpen, setNsiMonthOpen] = useState(false);
    const [nsiYearOpen, setNsiYearOpen] = useState(false);

    useEffect(() => {
        const handle = () => setIsMobile(window.innerWidth <= 767);
        window.addEventListener('resize', handle);
        return () => window.removeEventListener('resize', handle);
    }, []);

    // Current row
    const [curProduct, setCurProduct] = useState(null);
    const [curSupplierOptions, setCurSupplierOptions] = useState([]);
    const [curSupplier, setCurSupplier] = useState(null);
    const [curRemarks, setCurRemarks] = useState('');
    const [errors, setErrors] = useState({});
    const [curQty, setCurQty] = useState('');
    const [curPrice, setCurPrice] = useState('');
    const [loadingSuppliers, setLoadingSuppliers] = useState(false);

    useEffect(() => {
        axios.get('/data_entry/sales_entry/ajax/fetchuser').then(res => {
            if (res.data.success) {
                const list = res.data.payload || [];
                const opts = list.map(c => ({value: c.id, label: c.name}));
                setCustomers(opts);
                const guest = opts.find(o => o.label.toLowerCase() === 'guest');
                if (guest) setCustomer(guest);
            }
        }).catch(() => {});
        axios.get('/data_entry/sales_entry/ajax/product-list').then(res => {
            const list = Array.isArray(res.data) ? res.data : (res.data.payload || []);
            setProducts(list.map(p => ({value: p.id, label: p.name || p.product_name || ('Product #'+p.id)})));
        }).catch(() => {});
    }, []);

    const onProductChange = (selected) => {
        setCurProduct(selected);
        setErrors(prev => ({...prev, product: false}));
        setCurSupplier(null);
        setCurSupplierOptions([]);
        if (selected) {
            setLoadingSuppliers(true);
            axios.get('/data_entry/sales_entry/ajax/supplier-list/' + selected.value).then(res => {
                if (res.data.success) {
                    const opts = res.data.payload.flatMap(item =>
                        (item.supplier?.invoices || []).map(invoice => ({
                            label: invoice.invoice_title || 'Untitled',
                            supplier_name: item.supplier?.name || 'Unknown',
                            available_qty: invoice.quantity ?? invoice.available_qty ?? null,
                            value: {
                                supplier_invoice: invoice.supplier_invoice_id,
                                supplier: item.supplier_id,
                                product: selected.value,
                                supplier_invoice_product_id: invoice.id,
                            }
                        }))
                    );
                    setCurSupplierOptions(opts);
                    if (opts.length === 1) setCurSupplier(opts[0]);
                }
            }).catch(() => {}).finally(() => setLoadingSuppliers(false));
        }
    };

    const onSupplierChange = (selected) => {
        setCurSupplier(selected);
        setErrors(prev => ({...prev, supplier: false}));
        // Auto-fill price from supplier label
        if (selected && selected.label) {
            const parts = (selected.label || '').split('|');
            const prPart = (parts.find(p => p.startsWith('P:')) || '').replace('P:','').trim();
            if (prPart && Number(prPart) > 0) {
                setCurPrice(prPart);
            }
        }
    };

    const onSaveStock = (index, data) => {
        if (data && data !== "") {
            const stock_suppliers = data.suppliers;
            const current_supplier = data.current;
            const opts = stock_suppliers.flatMap(item =>
                (item.supplier?.invoices || []).map(invoice => ({
                    label: invoice.invoice_title || 'Untitled',
                    supplier_name: item.supplier?.name || 'Unknown',
                    available_qty: invoice.quantity ?? invoice.available_qty ?? null,
                    value: {
                        supplier_invoice: invoice.supplier_invoice_id,
                        supplier: invoice.supplier_id,
                        product: invoice.product_id,
                        supplier_invoice_product_id: invoice.id,
                    }
                }))
            );
            setCurSupplierOptions(opts);
            if (current_supplier) setCurSupplier(current_supplier);
        }
    };

    const addProduct = () => {
        const errs = {};
        if (!curProduct) errs.product = true;
        if (!curSupplier) errs.supplier = true;
        if (!curQty || Number(curQty) <= 0) errs.qty = true;
        if (!curPrice || Number(curPrice) <= 0) errs.price = true;
        if (Object.keys(errs).length > 0) {
            setErrors(prev => ({...prev, ...errs}));
            toast.error('Please fill in all required fields');
            return;
        }
        const maxQty = curSupplier?.available_qty;
        if (maxQty !== null && maxQty !== undefined && Number(curQty) > Number(maxQty)) { toast.error(`Only ${maxQty} available in stock`); return; }
        setItems([...items, {
            product_id: curProduct.value,
            product_name: curProduct.label,
            supplier_name: curSupplier.supplier_name || '',
            supplier_data: curSupplier.value,
            remarks: curRemarks,
            quantity: Number(curQty),
            price: Number(curPrice),
            total: Number(curQty) * Number(curPrice),
        }]);
        setCurProduct(null); setCurSupplier(null); setCurSupplierOptions([]);
        setCurRemarks(''); setCurQty(''); setCurPrice('');
    };

    const removeItem = (idx) => setItems(items.filter((_,i) => i !== idx));
    const grandTotal = items.reduce((sum,item) => sum + item.total, 0);

    const generateInvoice = async () => {
        if (items.length === 0) { toast.error('Add at least one product'); return; }
        setGenerating(true);
        try {
            const res = await axios.post(props.generateApi, {
                customer_id: customer ? customer.value : null,
                date,
                products: items.map(i => ({
                    product_id: i.product_id,
                    supplier_id: i.supplier_data.supplier,
                    supplier_invoice_id: i.supplier_data.supplier_invoice,
                    supplier_invoice_product_id: i.supplier_data.supplier_invoice_product_id,
                    quantity: i.quantity,
                    price: i.price,
                    remarks: i.remarks,
                })),
            });
            if (res.data.success) {
                toast.success('Invoice generated!');
                window.location.href = '/data_entry/sales_entry/invoice/' + res.data.invoice_id;
            } else {
                toast.error(res.data.message || 'Failed');
            }
        } catch(err) {
            toast.error(err.response?.data?.message || 'Failed to generate invoice');
        } finally { setGenerating(false); }
    };

    const lblStyle = {fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'};
    const inputStyle = {height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b',transition:'all 0.2s'};
    const thStyle = {padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'};
    const tdStyle = {padding:'12px 14px',borderBottom:'1px solid #f3f4f8',fontSize:'13px',fontWeight:'500',color:'#334155'};

    const porterage = 5.25;
    const vat = 2;
    const porterageVal = items.length > 0 ? porterage : 0;
    const vatVal = items.length > 0 ? vat : 0;
    const invoiceTotal = grandTotal + porterageVal + vatVal;

    return (
    <div style={{maxWidth:'1440px',margin:'0 auto'}}>
        <style>{`
            .nsi-scroll-inner{transition:transform 0.2s ease;}
            .nsi-range-scroll{-webkit-appearance:none;width:100%;height:6px;border-radius:10px;background:#f0f0f0;outline:none;}
            .nsi-range-scroll::-webkit-slider-thumb{-webkit-appearance:none;width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;}
            .nsi-range-scroll::-moz-range-thumb{width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;border:none;}
            .inv-date-picker-wrap{position:relative;display:flex;align-items:center;background:#fafafa;border:1.5px solid #e5e7eb;border-radius:8px;height:36px;padding:0 10px;cursor:pointer;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;gap:8px;}
            .inv-date-picker-wrap:hover{border-color:rgb(234, 88, 12);background:#fff;}
            .inv-date-picker-wrap:focus-within{border-color:rgb(234, 88, 12);box-shadow:0 0 0 3px rgba(234,88,12,0.08);background:#fff;}
            .inv-date-picker-wrap .inv-date-icon{color:rgb(234, 88, 12);font-size:13px;flex-shrink:0;pointer-events:none;}
            .inv-date-picker{padding:0;font-size:13px;font-weight:600;border:none;height:100%;color:#1e293b;outline:none;cursor:pointer;background:transparent;width:110px;letter-spacing:0.2px;-webkit-appearance:none;appearance:none;}
            .inv-date-picker::placeholder{color:#94a3b8;font-weight:500;}
        `}</style>
        {/* Header bar. On mobile we let the calendar overlay float without expanding the card,
            so overflow must stay visible there (desktop keeps overflow:hidden for its rounded corners). */}
        <div style={{background:'#fff',borderRadius:'14px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',overflow:isMobile?'visible':'hidden',marginBottom:'16px'}}>
            {isMobile ? (
                /* ── Mobile header: modern clean layout ── */
                <div>
                    {/* Top bar: Back + Title + Stats */}
                    <div style={{display:'flex',alignItems:'center',padding:'14px 16px',borderBottom:'1px solid #f0f0f0'}}>
                        <a href={props.backUrl || '/data_entry/sales_entry/view'} style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',textDecoration:'none',flexShrink:0,boxShadow:'0 2px 6px rgba(234,88,12,0.3)'}}>
                            <i className="fa fa-chevron-left" style={{fontSize:'14px',color:'#fff'}}></i>
                        </a>
                        <div style={{flex:1,marginLeft:'12px'}}>
                            <div style={{fontSize:'17px',fontWeight:'800',color:'#1e293b'}}>New Invoice</div>
                        </div>
                        {continued && (
                            <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                                <div style={{textAlign:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.5px',textTransform:'uppercase'}}>Items</div>
                                    <span style={{fontSize:'15px',fontWeight:'800',color:'#374151'}}>{items.length}</span>
                                </div>
                                <div style={{width:'1px',height:'24px',background:'#e5e7eb'}}></div>
                                <div style={{textAlign:'center'}}>
                                    <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.5px',textTransform:'uppercase'}}>Total</div>
                                    <span style={{fontSize:'15px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(invoiceTotal)}</span>
                                </div>
                            </div>
                        )}
                    </div>
                    {/* Customer + Date inline */}
                    <div style={{display:'flex',alignItems:'stretch',borderBottom:'1px solid #f0f0f0',position:'relative'}}>
                        <div style={{flex:'0 1 55%',padding:'12px 10px 12px 14px',borderRight:'1px solid #f0f0f0',minWidth:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px'}}><i className="fa fa-user" style={{fontSize:'9px',color:'rgb(234, 88, 12)',marginRight:'4px'}}></i>Customer</div>
                            <Select options={customers} value={customer} onChange={setCustomer}
                                isSearchable placeholder="Select..." menuPortalTarget={document.body}
                                styles={{...selectStyles,
                                    control:(b,s)=>({...b,minHeight:'36px',height:'36px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow:s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafafa'}),
                                    valueContainer:(b)=>({...b,padding:'0 10px',height:'36px'}),
                                    indicatorsContainer:(b)=>({...b,height:'36px'}),
                                }}
                            />
                        </div>
                        <div style={{flex:'0 1 45%',padding:'12px 14px 12px 10px',display:'flex',flexDirection:'column',justifyContent:'center'}}>
                            <div style={{fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.5px',textTransform:'uppercase',marginBottom:'6px'}}>Date</div>
                            {/* Mobile: tap to toggle the calendar overlay (floats below, doesn't expand the card). */}
                            <button type="button" onClick={()=>setDateOpen(o=>!o)}
                                style={{width:'100%',height:'36px',borderRadius:'8px',border:'1.5px solid '+(dateOpen?'rgb(234, 88, 12)':'#e5e7eb'),background:dateOpen?'#fff':'#fafafa',display:'flex',alignItems:'center',padding:'0 10px',gap:'8px',cursor:'pointer',outline:'none'}}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span style={{fontSize:'13px',fontWeight:'600',color:date?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{date ? new Date(date+'T00:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'2-digit',year:'numeric'}) : 'Select date'}</span>
                            </button>
                        </div>
                        {/* Calendar overlay — floats below the row (absolute) so the card does NOT
                            expand/push the Continue button. Same compact look as the original
                            OrangeDatePicker calendar; stays within the card width (left:0/right:0). */}
                        {dateOpen && (
                        <>
                        <div onClick={()=>{setDateOpen(false);setNsiMonthOpen(false);setNsiYearOpen(false);}} style={{position:'fixed',inset:0,zIndex:40}} />
                        <div style={{position:'absolute',top:'100%',left:0,right:0,marginTop:'6px',zIndex:50,display:'flex',justifyContent:'center'}}>
                            <div className="nsi-inline-cal" style={{border:'1px solid #fed7aa',borderRadius:'16px',background:'#fffcf7',padding:'10px',maxWidth:'100%',boxShadow:'0 12px 40px rgba(0,0,0,0.18)'}}>
                            <style>{`.nsi-inline-cal .react-datepicker{border:none;background:transparent !important;box-shadow:none !important;font-family:inherit}.nsi-inline-cal .react-datepicker__month-container{float:none;background:transparent !important}.nsi-inline-cal .react-datepicker__header{background:transparent !important;border-bottom:none;padding:0}.nsi-inline-cal .react-datepicker__month{margin:4px 8px 8px !important;background:transparent !important}.nsi-inline-cal .react-datepicker__day-names,.nsi-inline-cal .react-datepicker__week{display:flex;align-items:center}.nsi-inline-cal .react-datepicker__day-name,.nsi-inline-cal .react-datepicker__day{display:inline-flex !important;align-items:center !important;justify-content:center !important;line-height:1 !important;width:2rem !important;height:2rem !important;margin:0.1rem !important;font-size:13px !important}.nsi-inline-cal .react-datepicker__day-name{font-size:11px !important;font-weight:700 !important;color:#94a3b8 !important;text-transform:uppercase !important;letter-spacing:0.3px !important}.nsi-inline-cal .react-datepicker__day{font-weight:600;color:#334155;border-radius:50%}.nsi-inline-cal .react-datepicker__day:hover:not(.react-datepicker__day--selected){background:#fff;color:rgb(234, 88, 12)}.nsi-inline-cal .react-datepicker__day--selected{background:rgb(234, 88, 12) !important;color:#fff !important;font-weight:800}.nsi-inline-cal .react-datepicker__day--today{color:rgb(234, 88, 12);font-weight:700}.nsi-inline-cal .react-datepicker__day--outside-month{color:#cbd5e1}.nsi-inline-cal .react-datepicker__day--disabled{color:#e5e7eb !important}.nsi-inline-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#334155}`}</style>
                            <DatePicker inline selected={date?new Date(date+'T00:00:00'):new Date()} maxDate={new Date()}
                                onChange={(d)=>{ if(d){const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); setDate(y+'-'+m+'-'+dd);} setDateOpen(false); }}
                                renderCustomHeader={({date:hd,changeYear,changeMonth,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                    const mnths=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                    const cy=new Date().getFullYear();
                                    const years=Array.from({length:20},(_,i)=>cy-10+i);
                                    const hBtn={border:'1.5px solid #fed7aa',borderRadius:'8px',padding:'5px 12px',fontSize:'13px',fontWeight:'600',color:'#111827',cursor:'pointer',outline:'none',background:'#FFF7F2',display:'inline-flex',alignItems:'center',gap:'4px',lineHeight:'1.4'};
                                    const aBtn={background:'#FFF7F2',border:'1.5px solid #fed7aa',cursor:'pointer',fontSize:'14px',color:'#F27420',padding:'4px 8px',borderRadius:'8px',lineHeight:1,display:'flex',alignItems:'center',justifyContent:'center',fontWeight:'700'};
                                    return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'2px 4px 8px',gap:'6px'}}>
                                        <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{...aBtn,opacity:prevMonthButtonDisabled?0.3:1}}>&#8249;</button>
                                        <div style={{display:'flex',alignItems:'center',gap:'6px',position:'relative'}}>
                                            <button type="button" style={hBtn} onClick={()=>setNsiMonthOpen(o=>!o)}>{mnths[hd.getMonth()]}<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg></button>
                                            <button type="button" style={hBtn} onClick={()=>setNsiYearOpen(o=>!o)}>{hd.getFullYear()}<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg></button>
                                            {nsiMonthOpen && (
                                                <div style={{position:'absolute',top:'100%',left:'50%',transform:'translateX(-50%)',marginTop:'4px',background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'12px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',padding:'8px',zIndex:100,display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'4px',minWidth:'180px'}}>
                                                    {mnths.map((m,i)=>(<button key={m} type="button" onClick={()=>{changeMonth(i);setNsiMonthOpen(false);}} style={{border:'none',borderRadius:'8px',padding:'7px 4px',fontSize:'12px',fontWeight:'600',cursor:'pointer',textAlign:'center',background:i===hd.getMonth()?'#F27420':'transparent',color:i===hd.getMonth()?'#fff':'#374151'}}>{m}</button>))}
                                                </div>
                                            )}
                                            {nsiYearOpen && (
                                                <div style={{position:'absolute',top:'100%',left:'50%',transform:'translateX(-50%)',marginTop:'4px',background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'12px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',padding:'8px',zIndex:100,display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'4px',minWidth:'180px',maxHeight:'200px',overflowY:'auto'}}>
                                                    {years.map(y=>(<button key={y} type="button" onClick={()=>{changeYear(y);setNsiYearOpen(false);}} style={{border:'none',borderRadius:'8px',padding:'7px 4px',fontSize:'12px',fontWeight:'600',cursor:'pointer',textAlign:'center',background:y===hd.getFullYear()?'#F27420':'transparent',color:y===hd.getFullYear()?'#fff':'#374151'}}>{y}</button>))}
                                                </div>
                                            )}
                                        </div>
                                        <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...aBtn,opacity:nextMonthButtonDisabled?0.3:1}}>&#8250;</button>
                                    </div>);
                                }}
                            />
                            </div>
                        </div>
                        </>
                        )}
                    </div>
                    {/* Continue button — full width */}
                    <div style={{padding: continued ? 0 : '14px 16px'}}>
                        {!continued && (
                            <button disabled={generating} onClick={handleContinue} style={{
                                width:'100%',height:'44px',border:'none',borderRadius:'10px',background:'rgb(234, 88, 12)',color:'#fff',
                                fontSize:'15px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',
                                boxShadow:'0 3px 10px rgba(234,88,12,0.3)',
                            }}>
                                {generating ? <><i className="fa fa-spinner fa-spin"></i> Creating...</> : <>Continue <i className="fa fa-arrow-right"></i></>}
                            </button>
                        )}
                    </div>
                </div>
            ) : (
                /* ── Desktop header: single row — exact spec UI ── */
                <div style={{display:'flex',alignItems:'stretch'}}>
                    {/* Orange tile — Back + New Invoice (single line) */}
                    <a href={props.backUrl || '/data_entry/sales_entry/view'} style={{flexShrink:0,background:'rgb(234, 88, 12)',color:'#fff',padding:'14px 18px',display:'flex',alignItems:'center',gap:'10px',border:'none',cursor:'pointer',textDecoration:'none',whiteSpace:'nowrap',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.25),4px 0 14px -4px rgba(234,88,12,0.45)'}} title="Back to Sales">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        <span style={{fontSize:'15px',fontWeight:'800',letterSpacing:'0.3px',lineHeight:'1'}}>New Invoice</span>
                    </a>
                    <span style={{width:'1px',alignSelf:'stretch',background:'#eeeeef'}}></span>
                    {/* Customer */}
                    <div style={{flex:'1 1 0%',padding:'10px 16px',minWidth:'280px',display:'flex',flexDirection:'column',justifyContent:'center',gap:'6px'}}>
                        <span style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase'}}>Customer</span>
                        <Select options={customers} value={customer} onChange={setCustomer}
                            isSearchable placeholder="Select Customer..." menuPortalTarget={document.body}
                            styles={{...selectStyles,
                                control:(b,s)=>({...b,minHeight:'42px',height:'42px',fontSize:'13.5px',fontWeight:'500',borderRadius:'10px',border:s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e8e8ec',boxShadow:'none',background:'#fff','&:hover':{borderColor:'rgb(234, 88, 12)'}}),
                                valueContainer:(b)=>({...b,padding:'0 14px',height:'42px'}),
                                indicatorsContainer:(b)=>({...b,height:'42px'}),
                                placeholder:(b)=>({...b,fontSize:'13.5px',fontWeight:'500',color:'#9ca3af'}),
                                singleValue:(b)=>({...b,fontSize:'13.5px',fontWeight:'500',color:'#0f1115'}),
                            }}
                        />
                    </div>
                    <span style={{width:'1px',alignSelf:'stretch',background:'#eeeeef'}}></span>
                    {/* Date */}
                    <div style={{flexShrink:0,width:'200px',padding:'10px 16px',display:'flex',flexDirection:'column',justifyContent:'center',gap:'6px'}}>
                        <span style={{fontSize:'10.5px',fontWeight:'800',letterSpacing:'0.8px',color:'#6b7280',textTransform:'uppercase'}}>Date</span>
                        <div style={{height:'42px',padding:'0 14px',borderRadius:'10px',background:'#fff',border:'1px solid #e8e8ec',display:'flex',alignItems:'center',gap:'8px'}}>
                            <OrangeDatePicker value={date} onChange={(val) => setDate(val)} />
                        </div>
                    </div>
                    <span style={{width:'1px',alignSelf:'stretch',background:'#eeeeef'}}></span>
                    {/* Stats or Continue */}
                    {continued ? (
                    <div style={{display:'flex',alignItems:'stretch',flexShrink:0}}>
                        <div style={{padding:'10px 14px',textAlign:'center',display:'flex',flexDirection:'column',justifyContent:'center',minWidth:'75px',borderRight:'1px solid #f0f0f0'}}>
                            <div style={{fontSize:'9px',fontWeight:'700',color:'#b0b0b0',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'2px'}}>Items</div>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'#374151'}}>{items.length}</span>
                        </div>
                        <div style={{padding:'10px 14px',textAlign:'center',display:'flex',flexDirection:'column',justifyContent:'center',minWidth:'80px',background:'#fff7ed'}}>
                            <div style={{fontSize:'9px',fontWeight:'700',color:'#b0b0b0',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'2px'}}>Total</div>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(invoiceTotal)}</span>
                        </div>
                    </div>
                    ) : (
                    <div style={{padding:'14px 16px',display:'flex',alignItems:'center',flexShrink:0}}>
                        <button disabled={generating || !customer} onClick={handleContinue} style={{
                            height:'48px',padding:'0 20px',borderRadius:'10px',
                            background:(generating||!customer)?'#f1f1f4':'rgb(234, 88, 12)',
                            color:(generating||!customer)?'#9ca3af':'#fff',
                            border:'1px solid transparent',fontWeight:'700',fontSize:'14.5px',letterSpacing:'-0.1px',
                            display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',
                            boxShadow:(generating||!customer)?'none':'inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45)',
                            cursor:(generating||!customer)?'not-allowed':'pointer',
                        }}>
                            {generating ? <><i className="fa fa-spinner fa-spin"></i> Creating...</> : <>Continue <svg width="16.5" height="16.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg></>}
                        </button>
                    </div>
                    )}
                </div>
            )}
        </div>

        {/* Step indicator + helper (before continue) */}
        {!continued && (
        <div style={{marginTop: isMobile ? '12px' : '16px',minHeight: isMobile ? 'calc(100vh - 220px)' : 'calc(100vh - 200px)',display:'flex',flexDirection:'column'}}>
            {/* Stepper card — exact spec UI */}
            <div style={{background:'#fff',borderRadius:'12px',border:'1px solid #e8e8ec',boxShadow:'0 1px 2px rgba(15,17,21,0.04),0 6px 18px -8px rgba(15,17,21,0.12)',padding: isMobile ? '12px 16px' : '14px 22px',display:'flex',alignItems:'center',gap:'12px',marginBottom: isMobile ? '12px' : '16px',flexWrap:'wrap'}}>
                {[
                    {num:'1', label:'Customer & Date', active:true},
                    {num:'2', label:'Add Products', active:false},
                ].map((step, i) => (
                    <React.Fragment key={i}>
                        {i > 0 && <span style={{flex:'0 0 auto',width:'48px',height:'2px',borderRadius:'99px',background:'#eeeeef'}}></span>}
                        <div style={{display:'inline-flex',alignItems:'center',gap:'10px'}}>
                            <span style={{
                                width:'28px',height:'28px',borderRadius:'50%',flexShrink:0,
                                display:'inline-flex',alignItems:'center',justifyContent:'center',
                                fontSize:'12.5px',fontWeight:'800',
                                background: step.active ? 'rgb(234, 88, 12)' : '#f4f4f6',
                                color: step.active ? '#fff' : '#6b7280',
                                border: step.active ? 'none' : '1px solid #e8e8ec',
                                boxShadow: step.active ? 'inset 0 1px 0 rgba(255,255,255,0.25),0 4px 10px -3px rgba(234,88,12,0.45)' : 'none',
                            }}>{step.num}</span>
                            <span style={{fontSize:'14px',fontWeight: step.active ? '800' : '700',color: step.active ? 'rgb(234, 88, 12)' : '#6b7280',letterSpacing:'-0.1px',whiteSpace:'nowrap'}}>{step.label}</span>
                        </div>
                    </React.Fragment>
                ))}
            </div>
            {/* Main card */}
            <div style={{background:'#fff',borderRadius:'14px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',overflow:'hidden',flex:1,display:'flex',flexDirection:'column'}}>

                {/* Center content — fills remaining space */}
                <div style={{flex:1,display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',padding: isMobile ? '36px 20px 28px' : '48px 24px 36px',textAlign:'center'}}>
                    <div style={{width:'84px',height:'84px',margin:'0 auto 18px',borderRadius:'50%',background:'#fff7f0',color:'rgb(234, 88, 12)',border:'1px solid #f6c9a8',display:'flex',alignItems:'center',justifyContent:'center'}}>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6M9 9h2"/></svg>
                    </div>
                    <h2 style={{margin:0,fontSize: isMobile ? '20px' : '24px',fontWeight:'800',color:'#0f1115',letterSpacing:'-0.4px'}}>Create New Invoice</h2>
                    <p style={{margin:'10px auto 28px',fontSize:'13.5px',color:'#6b7280',lineHeight:'1.55',maxWidth:'480px'}}>Select a customer and date above, then click <b style={{color:'rgb(234, 88, 12)'}}>Continue</b> to start adding products.</p>

                    {/* Step cards */}
                    <div style={{display: isMobile ? 'flex' : 'grid',flexDirection: isMobile ? 'column' : undefined,gridTemplateColumns: isMobile ? undefined : 'repeat(3, minmax(0, 220px))',gap:'14px',justifyContent:'center',width: isMobile ? '100%' : 'auto'}}>
                        {[
                            {key:'user',title:'Select Customer',desc:'Choose from your customer list',active:true},
                            {key:'date',title:'Pick a Date',desc:'Set the invoice date',active:true},
                            {key:'arrow',title:'Continue',desc:'Add products to your invoice',active:false},
                        ].map(({key,title,desc,active}) => (
                            <div key={title} style={{padding:'18px 18px 16px',borderRadius:'14px',background: active ? '#fff7f0' : '#fff',border:'1px solid '+(active ? '#f6c9a8' : '#e8e8ec'),display:'flex',flexDirection:'column',alignItems:'flex-start',gap:'10px',textAlign:'left',boxShadow:'0 1px 2px rgba(15,17,21,0.04),0 6px 18px -8px rgba(15,17,21,0.12)'}}>
                                <span style={{width:'38px',height:'38px',borderRadius:'9px',flexShrink:0,
                                    background: active ? 'rgb(234, 88, 12)' : '#fff7f0',
                                    color: active ? '#fff' : 'rgb(234, 88, 12)',
                                    display:'inline-flex',alignItems:'center',justifyContent:'center',
                                    boxShadow: active ? 'inset 0 1px 0 rgba(255,255,255,0.25),0 4px 10px -3px rgba(234,88,12,0.45)' : 'none',
                                }}>
                                    {key==='user' && <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>}
                                    {key==='date' && <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M20 6L9 17l-5-5"/></svg>}
                                    {key==='arrow' && <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>}
                                </span>
                                <div>
                                    <div style={{fontSize:'14.5px',fontWeight:'800',color: active ? 'rgb(234, 88, 12)' : '#0f1115',letterSpacing:'-0.1px'}}>{title}</div>
                                    <div style={{fontSize:'12px',color:'#6b7280',marginTop:'3px',lineHeight:'1.4'}}>{desc}</div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
        )}

        {/* Products table */}
        {continued && <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden'}}>
            {/* Add product row */}
            <div style={{padding:'16px 20px',background:'#fff',borderBottom:'1px solid #eef2f7'}}>
                {/* Row 1: Product + Supplier */}
                <div style={{display:'flex',gap:'12px',marginBottom:'12px'}}>
                    <div style={{flex:1,minWidth:0}}>
                        <label style={lblStyle}>Product</label>
                        <Select options={products} value={curProduct} onChange={onProductChange}
                            isSearchable placeholder="Select..." menuPortalTarget={document.body}
                            styles={{...selectStyles, control:(b,s)=>({...b,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',border: errors.product ? '2px solid #ef4444' : s.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #e2e8f0',boxShadow: errors.product ? '0 0 0 3px rgba(239,68,68,0.08)' : s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:s.isFocused?'#fff':'#f8fafc'})}}
                        />
                    </div>
                    <div style={{flex:1,minWidth:0}}>
                        <label style={lblStyle}>Supplier</label>
                        <div style={{display:'flex',gap:'4px',alignItems:'center'}}>
                            <div style={{flex:1,minWidth:0}}>
                                <Select options={curSupplierOptions} value={curSupplier} onChange={onSupplierChange}
                                    isSearchable placeholder={loadingSuppliers ? "Loading..." : "Select..."}
                                    menuPortalTarget={document.body}
                                    styles={{...selectStyles, control:(b,s)=>({...b,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',border: errors.supplier ? '2px solid #ef4444' : s.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #e2e8f0',boxShadow: errors.supplier ? '0 0 0 3px rgba(239,68,68,0.08)' : s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:s.isFocused?'#fff':'#f8fafc'})}}
                                    formatOptionLabel={formatSupplierOption}
                                    isDisabled={!curProduct || loadingSuppliers}
                                    noOptionsMessage={() => curProduct ? "No suppliers found" : "Select product first"}
                                />
                            </div>
                            {curProduct && (
                                <div style={{flexShrink:0}}>
                                    <AddStock onSaveStock={onSaveStock} show={false} product={curProduct} index={0} apiKey="" invoiceId={0} />
                                </div>
                            )}
                        </div>
                    </div>
                </div>
                {/* Row 2: Remarks + Qty + Unit Price + Price + Add */}
                {isMobile ? (
                    <div>
                        {/* Mobile: Remarks full width */}
                        <div style={{marginBottom:'10px'}}>
                            <label style={lblStyle}>Remarks</label>
                            <input type="text" value={curRemarks} onChange={e => setCurRemarks(e.target.value)} placeholder="Remarks (optional)..." style={inputStyle}
                                onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';}} onBlur={e => {e.target.style.borderColor='#cbd5e1';}}
                            />
                        </div>
                        {/* Mobile: Qty + Unit Price + Price + Add in one row */}
                        <div style={{display:'flex',alignItems:'flex-end',gap:'8px'}}>
                            <div style={{flex:1,minWidth:0}}>
                                <label style={lblStyle}>Qty</label>
                                <input type="number" min="0.01" max={curSupplier?.available_qty || undefined} step="any" value={curQty}
                                    onChange={e => { let val=e.target.value; if(curSupplier?.available_qty!=null&&Number(val)>Number(curSupplier.available_qty))val=curSupplier.available_qty; setCurQty(val); setErrors(prev=>({...prev,qty:false})); }}
                                    placeholder={curSupplier?.available_qty!=null?`Max ${curSupplier.available_qty}`:'Qty'}
                                    style={{...inputStyle, border: errors.qty ? '2px solid #ef4444' : '1.5px solid #e2e8f0'}}
                                    onFocus={e=>{e.target.style.borderColor=errors.qty?'#ef4444':'rgb(234, 88, 12)';}} onBlur={e=>{e.target.style.borderColor=errors.qty?'#ef4444':'#e2e8f0';}}
                                />
                            </div>
                            <div style={{flex:1,minWidth:0}}>
                                <label style={lblStyle}>Unit Price</label>
                                <input type="text" inputMode="decimal" value={curPrice}
                                    onChange={e => { const v=e.target.value; if(v===''||/^[0-9]*[.]?[0-9]*$/.test(v)){setCurPrice(v); setErrors(prev=>({...prev,price:false}));}}}
                                    placeholder="Price" style={{...inputStyle, border: errors.price ? '2px solid #ef4444' : inputStyle.border}}
                                    onFocus={e=>{e.target.style.borderColor=errors.price?'#ef4444':'rgb(234, 88, 12)';}} onBlur={e=>{e.target.style.borderColor=errors.price?'#ef4444':'#e2e8f0';}}
                                />
                            </div>
                            <div style={{flexShrink:0,textAlign:'center'}}>
                                <label style={lblStyle}>Total</label>
                                <div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px',whiteSpace:'nowrap'}}>
                                    {curQty && curPrice ? formatTwoDecimal(Number(curQty)*Number(curPrice)) : '0.00'}
                                </div>
                            </div>
                            <button onClick={addProduct} style={{height:'40px',padding:'0 16px',borderRadius:'10px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',gap:'6px',boxShadow:'0 2px 8px rgba(234,88,12,0.3)',flexShrink:0}}>
                                <i className="fa fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                ) : (
                    <div style={{display:'flex',alignItems:'flex-end',gap:'12px'}}>
                        <div style={{flex:1,minWidth:0}}>
                            <label style={lblStyle}>Remarks</label>
                            <input type="text" value={curRemarks} onChange={e => setCurRemarks(e.target.value)} placeholder="Remarks..." style={inputStyle}
                                onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor='#cbd5e1';e.target.style.background='#f8fafc';}}
                            />
                        </div>
                        <div style={{width:'100px'}}>
                            <label style={lblStyle}>Quantity</label>
                            <input type="number" min="0.01" max={curSupplier?.available_qty || undefined} step="any" value={curQty}
                                title={curSupplier?.available_qty != null ? `Max: ${curSupplier.available_qty}` : ''}
                                onChange={e => {
                                    let val = e.target.value;
                                    if (curSupplier?.available_qty != null && Number(val) > Number(curSupplier.available_qty)) val = curSupplier.available_qty;
                                    setCurQty(val);
                                    setErrors(prev => ({...prev, qty: false}));
                                }}
                                placeholder={curSupplier?.available_qty != null ? `Max ${curSupplier.available_qty}` : 'Qty'}
                                style={{...inputStyle, border: errors.qty ? '2px solid #ef4444' : (curQty && curSupplier?.available_qty != null && Number(curQty) > Number(curSupplier.available_qty) ? '1.5px solid #ef4444' : '1.5px solid #e2e8f0')}}
                                onFocus={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : 'rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : '#e2e8f0';e.target.style.background='#f8fafc';}}
                            />
                        </div>
                        <div style={{width:'100px'}}>
                            <label style={lblStyle}>Unit Price</label>
                            <input type="text" inputMode="decimal" value={curPrice}
                                onChange={e => { const v=e.target.value; if(v===''||/^[0-9]*[.]?[0-9]*$/.test(v)){setCurPrice(v); setErrors(prev=>({...prev,price:false}))}}}
                                placeholder="Price" style={{...inputStyle, border: errors.price ? '2px solid #ef4444' : inputStyle.border}}
                                onFocus={e => {e.target.style.borderColor= errors.price ? '#ef4444' : 'rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor= errors.price ? '#ef4444' : '#e2e8f0';e.target.style.background='#f8fafc';}}
                            />
                        </div>
                        <div style={{width:'80px',textAlign:'right'}}>
                            <label style={lblStyle}>Price</label>
                            <div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b',lineHeight:'40px'}}>
                                {curQty && curPrice ? formatTwoDecimal(Number(curQty)*Number(curPrice)) : '0.00'}
                            </div>
                        </div>
                        <button onClick={addProduct} style={{
                            height:'40px',padding:'0 24px',borderRadius:'10px',border:'none',
                            background:'rgb(234, 88, 12)',color:'#fff',
                            fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',gap:'6px',
                            boxShadow:'0 2px 8px rgba(234,88,12,0.3)',flexShrink:0,
                        }}>
                            <i className="fa fa-plus"></i> Add
                        </button>
                    </div>
                )}
            </div>

            {/* Items table */}
            {items.length > 0 && (
                <>
                <div style={{overflow:'hidden',position:'relative'}}>
                <div className="nsi-scroll-inner" style={{minWidth: isMobile ? '650px' : 'auto'}}>
                    <table style={{width:'100%',borderCollapse:'collapse'}}>
                        <thead>
                            <tr>
                                <th style={{...thStyle,width:'40px'}}>#</th>
                                <th style={thStyle}>Product</th>
                                <th style={thStyle}>Supplier</th>
                                <th style={thStyle}>Remarks</th>
                                <th style={{...thStyle,textAlign:'right'}}>Qty</th>
                                <th style={{...thStyle,textAlign:'right'}}>Price</th>
                                <th style={{...thStyle,textAlign:'right'}}>Total</th>
                                <th style={{...thStyle,width:'50px'}}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, idx) => (
                                <tr key={idx} style={{background: idx%2===0 ? '#fff' : '#fcfcfd'}}>
                                    <td style={{...tdStyle,width:'40px',color:'#94a3b8'}}>{idx+1}</td>
                                    <td style={{...tdStyle,fontWeight:'600'}}>{item.product_name}</td>
                                    <td style={{...tdStyle,color:'#64748b',fontSize:'12px'}}>{item.supplier_name}</td>
                                    <td style={{...tdStyle,color:'#64748b',fontStyle:'italic'}}>{item.remarks || '—'}</td>
                                    <td style={{...tdStyle,textAlign:'right'}}>{item.quantity}</td>
                                    <td style={{...tdStyle,textAlign:'right'}}>{formatTwoDecimal(item.price)}</td>
                                    <td style={{...tdStyle,textAlign:'right',fontWeight:'700',color:'#1e293b'}}>{formatTwoDecimal(item.total)}</td>
                                    <td style={{...tdStyle,width:'50px',textAlign:'center'}}>
                                        <button onClick={() => removeItem(idx)} style={{background:'none',border:'none',color:'#ef4444',cursor:'pointer',fontSize:'14px'}} title="Remove">
                                            <i className="fa fa-trash-o"></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                </div>
                {isMobile && (
                    <div style={{padding:'4px 14px 8px'}}>
                        <input type="range" min="0" max="100" defaultValue="0"
                            className="nsi-range-scroll"
                            onChange={(e) => {
                                const inner = document.querySelector('.nsi-scroll-inner');
                                if (!inner) return;
                                const parent = inner.parentElement;
                                const maxMove = inner.scrollWidth - parent.clientWidth;
                                const pct = e.target.value / 100;
                                inner.style.transform = 'translateX(-' + (pct * maxMove) + 'px)';
                            }}
                        />
                    </div>
                )}
                </>
            )}

            {items.length === 0 && (
                <div style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>
                    <i className="fa fa-shopping-cart" style={{fontSize:'32px',marginBottom:'12px',display:'block',opacity:0.3}}></i>
                    Add products to create your invoice
                </div>
            )}
        </div>}

        {/* Invoice Summary + Generate */}
        {items.length > 0 && (
            <div style={{display:'flex',gap:'16px',marginTop:'16px',flexWrap:'wrap'}}>
                <div style={{flex:1}}></div>
                <div style={{minWidth:'320px',flex:1}}>
                    <div style={{border:'1px solid #e5e7eb',borderRadius:'8px',overflow:'hidden',background:'#fff'}}>
                        {[
                            {label:'Sub Total', value: formatTwoDecimal(grandTotal)},
                            {label:'Porterage', value: formatTwoDecimal(porterageVal)},
                            {label:'Vat', value: formatTwoDecimal(vatVal)},
                        ].map((item, i) => (
                            <div key={i} style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 16px',borderBottom:'1px solid #f3f4f6'}}>
                                <span style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>{item.label}</span>
                                <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{item.value}</span>
                            </div>
                        ))}
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 16px',background:'#fafafa'}}>
                            <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Total</span>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(invoiceTotal)}</span>
                        </div>
                    </div>
                    <div style={{marginTop:'14px',display:'flex',justifyContent:'flex-end'}}>
                        <button onClick={generateInvoice} disabled={generating} style={{
                            height:'44px',padding:'0 32px',borderRadius:'10px',border:'none',
                            background: generating ? '#cbd5e1' : 'rgb(234, 88, 12)',
                            color:'#fff',fontSize:'15px',fontWeight:'700',cursor: generating ? 'not-allowed' : 'pointer',
                            boxShadow:'0 4px 16px rgba(234,88,12,0.3)',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',
                        }}>
                            <i className={generating ? "fa fa-spinner fa-spin" : "fa fa-check-circle"} style={{fontSize:'18px'}}></i>
                            {generating ? 'Generating...' : 'Generate Invoice'}
                        </button>
                    </div>
                </div>
            </div>
        )}

        <ToastContainer position="top-right" style={{ zIndex: 999999 }} autoClose={3000} />
    </div>
    );
}

// Mount
if (document.getElementById('new-sales-invoice-app')) {
    const el = document.getElementById('new-sales-invoice-app');
    const root = createRoot(el);
    root.render(
        <AlertProvider>
            <NewSalesInvoiceApp {...el.dataset} />
        </AlertProvider>
    );
}

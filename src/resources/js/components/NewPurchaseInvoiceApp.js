import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import Select from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";
import { formatTwoDecimal } from './../hooks/utils';

const selectStyles = {
    control: (base, state) => ({...base,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',
        border: state.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0',
        boxShadow: state.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none',background: state.isFocused ? '#fff' : '#f8fafc',
    }),
    menuPortal: (base) => ({...base,zIndex:9999}),
    option: (base, state) => ({...base,fontSize:'13px',
        backgroundColor: state.isSelected ? 'rgb(234, 88, 12)' : state.isFocused ? '#FFF5ED' : '#fff',
        color: state.isSelected ? '#fff' : '#334155',
    }),
    singleValue: (base) => ({...base,fontSize:'13px',fontWeight:'600',color:'#1e293b'}),
};

export default function NewPurchaseInvoiceApp(props) {
    const [suppliers, setSuppliers] = useState([]);
    const [products, setProducts] = useState([]);
    const [supplier, setSupplier] = useState(null);
    const [date, setDate] = useState(new Date().toISOString().slice(0,10));
    const [remark, setRemark] = useState('');
    const [deliveryNo, setDeliveryNo] = useState('');
    const [supplierInvoiceNo, setSupplierInvoiceNo] = useState('');
    const [awb, setAwb] = useState('');
    const [remarks, setRemarks] = useState('');
    const [items, setItems] = useState([]);
    const [generating, setGenerating] = useState(false);
    const [continued, setContinued] = useState(false);

    const handleContinue = async () => {
        if (!supplier) { setErrors(prev => ({...prev, supplier: true})); toast.error('Please select a supplier'); return; }
        setGenerating(true);
        try {
            const res = await axios.post(props.generateApi, {
                supplier_id: supplier.value,
                date,
                other_invoice_id: remark,
                delivery_no: deliveryNo,
                supplier_invoice_no: supplierInvoiceNo,
                awb: awb,
                remarks: remarks,
                products: [],
            });
            if (res.data.success) {
                toast.success('Invoice created!');
                window.location.href = '/data_entry/purchase_entry/invoice/' + res.data.invoice_id;
            } else {
                toast.error(res.data.message || 'Failed to create invoice');
            }
        } catch(err) {
            toast.error(err.response?.data?.message || 'Failed to create invoice');
        } finally { setGenerating(false); }
    };

    const [curProduct, setCurProduct] = useState(null);
    const [curRemarks, setCurRemarks] = useState('');
    const [curQty, setCurQty] = useState('');
    const [curPrice, setCurPrice] = useState('');
    const [curSalePrice, setCurSalePrice] = useState('');
    const [errors, setErrors] = useState({});
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 767);
    // Mobile: the add-product form opens as a slide-up bottom sheet (desktop keeps it inline).
    const [addSheetOpen, setAddSheetOpen] = useState(false);
    const [dateOpen, setDateOpen] = useState(false);
    const [nsiMonthOpen, setNsiMonthOpen] = useState(false);
    const [nsiYearOpen, setNsiYearOpen] = useState(false);

    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth <= 767);
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    useEffect(() => {
        const byLabel = (a, b) => String(a.label).localeCompare(String(b.label), undefined, { sensitivity: 'base' });
        axios.get(props.suppliersApi).then(res => {
            if (res.data.success) setSuppliers(res.data.payload.map(s => ({value:s.id,label:s.name})).sort(byLabel));
        });
        axios.get(props.productsApi).then(res => {
            if (res.data.success !== false) {
                const list = Array.isArray(res.data) ? res.data : (res.data.payload || []);
                setProducts(list.map(p => ({value:p.id,label:p.name || p.product_name || ('Product #'+p.id)})).sort(byLabel));
            }
        }).catch(() => {});
    }, []);

    const addProduct = () => {
        const errs = {};
        if (!curProduct) errs.product = true;
        if (!curQty || Number(curQty) <= 0) errs.qty = true;
        if (Object.keys(errs).length > 0) {
            setErrors(prev => ({...prev, ...errs}));
            toast.error('Please fill in all required fields');
            return;
        }
        setItems([...items, {
            product_id: curProduct.value,
            product_name: curProduct.label,
            remarks: curRemarks,
            quantity: Number(curQty),
            price: Number(curPrice) || 0,
            sale_price: curSalePrice ? Number(curSalePrice) : null,
            total: Number(curQty) * (Number(curPrice) || 0),
        }]);
        setCurProduct(null); setCurRemarks(''); setCurQty(''); setCurPrice(''); setCurSalePrice('');
        setAddSheetOpen(false); // mobile: close the bottom sheet after a successful add
    };

    const removeItem = (idx) => setItems(items.filter((_,i) => i !== idx));
    const grandTotal = items.reduce((sum,item) => sum + item.total, 0);

    const generateInvoice = async () => {
        if (!supplier) { setErrors(prev => ({...prev, supplier: true})); toast.error('Select a supplier'); return; }
        if (items.length === 0) { toast.error('Add at least one product'); return; }
        setGenerating(true);
        try {
            const res = await axios.post(props.generateApi, {
                supplier_id: supplier.value,
                date,
                other_invoice_id: remark,
                delivery_no: deliveryNo,
                supplier_invoice_no: supplierInvoiceNo,
                awb: awb,
                remarks: remarks,
                products: items.map(i => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    price: i.price,
                    sale_price: i.sale_price || null,
                    remarks: i.remarks,
                })),
            });
            if (res.data.success) {
                toast.success('Invoice generated!');
                window.location.href = '/data_entry/purchase_entry/invoice/' + res.data.invoice_id;
            } else {
                toast.error(res.data.message || 'Failed');
            }
        } catch(err) {
            toast.error(err.response?.data?.message || 'Failed to generate invoice');
        } finally { setGenerating(false); }
    };

    const invoiceTotal = grandTotal;

    const lblStyle = {fontSize:'10px',fontWeight:'700',color:'#64748b',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'6px',display:'block'};
    const inputStyle = {height:'40px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',padding:'0 14px',outline:'none',width:'100%',fontWeight:'500',color:'#1e293b',transition:'all 0.2s'};
    const thStyle = {padding:'10px 14px',fontSize:'10px',fontWeight:'700',color:'#64748b',textTransform:'uppercase',letterSpacing:'0.7px',borderBottom:'2px solid #eef2f7',background:'#fafbfc',textAlign:'left'};
    const tdStyle = {padding:'12px 14px',borderBottom:'1px solid #f3f4f8',fontSize:'13px',fontWeight:'500',color:'#334155'};

    const supplierSelectStyles = {
        ...selectStyles,
        control:(b,s)=>({...b,minHeight:'34px',height:'34px',fontSize:'13px',fontWeight:'600',borderRadius:'8px',border: errors.supplier ? '2px solid #ef4444' : s.isFocused?'1.5px solid rgb(234, 88, 12)':'1.5px solid #e5e7eb',boxShadow: errors.supplier ? '0 0 0 3px rgba(239,68,68,0.08)' : s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:'#fafafa','&:hover':{borderColor: errors.supplier ? '#ef4444' : 'rgb(234, 88, 12)',background:'#fff'}}),
        valueContainer:(b)=>({...b,padding:'0 10px',height:'34px'}),
        indicatorsContainer:(b)=>({...b,height:'34px'}),
    };

    const dateCell = (
        <div style={{display:'flex',alignItems:'center',gap:'8px',background:'#f8fafc',border:'1.5px solid #e2e8f0',borderRadius:'8px',height:'34px',padding:'0 10px'}}>
            <OrangeDatePicker value={date} onChange={(val) => setDate(val)} />
        </div>
    );

    return (
    <div style={{maxWidth:'1440px',margin:'0 auto'}}>
        <style>{`
            @keyframes npiSheetUp{from{transform:translateY(100%);}to{transform:translateY(0);}}
            .npi-scroll-inner{transition:transform 0.2s ease;}
            .npi-range-scroll{-webkit-appearance:none;width:100%;height:6px;border-radius:10px;background:#f0f0f0;outline:none;}
            .npi-range-scroll::-webkit-slider-thumb{-webkit-appearance:none;width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;}
            .npi-range-scroll::-moz-range-thumb{width:50px;height:6px;border-radius:10px;background:rgb(234, 88, 12);cursor:pointer;border:none;}
        `}</style>
        {/* Form Card. On mobile the date calendar floats as an overlay without expanding the card,
            so overflow must stay visible there (desktop keeps overflow:hidden for rounded corners). */}
        <div style={{background:'#fff',borderRadius:'14px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.06)',overflow:isMobile?'visible':'hidden',marginBottom:'16px',marginTop:'8px'}}>
        {continued ? (
            /* ── After continue: compact header with stats ── */
            <div style={{display:'flex',alignItems:'stretch',flexWrap: isMobile ? 'wrap' : 'nowrap'}}>
                <div style={{background:'rgb(234, 88, 12)',padding:'0 18px',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,minWidth:'80px'}}>
                    <div style={{textAlign:'center'}}>
                        <span style={{fontSize:'9px',fontWeight:'700',color:'rgba(255,255,255,0.8)',letterSpacing:'1px',textTransform:'uppercase',display:'block'}}>Invoice</span>
                        <span style={{fontSize:'18px',fontWeight:'800',color:'#fff'}}>New</span>
                    </div>
                </div>
                <div style={{flex:1,padding:'10px 16px',borderRight:'1px solid #f0f0f0',minWidth:0,display:'flex',flexDirection:'column',justifyContent:'center'}}>
                    <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'2px'}}>Supplier</div>
                    <div style={{fontSize:'14px',fontWeight:'700',color:'#1e293b'}}>{supplier?.label || '—'}</div>
                </div>
                <div style={{padding:'10px 14px',textAlign:'center',display:'flex',flexDirection:'column',justifyContent:'center',minWidth:'75px',borderRight:'1px solid #f0f0f0'}}>
                    <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'2px'}}>Items</div>
                    <span style={{fontSize:'16px',fontWeight:'800',color:'#374151'}}>{items.length}</span>
                </div>
                <div style={{padding:'10px 14px',textAlign:'center',display:'flex',flexDirection:'column',justifyContent:'center',minWidth:'80px',background:'#fff7ed'}}>
                    <div style={{fontSize:'9px',fontWeight:'700',color:'#94a3b8',letterSpacing:'1px',textTransform:'uppercase',marginBottom:'2px'}}>Total</div>
                    <span style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(invoiceTotal)}</span>
                </div>
            </div>
        ) : (
            /* ── Form ── */
            <div style={{padding: isMobile ? '20px 16px' : '36px 40px'}}>
                {/* Header */}
                <div style={{display:'flex',alignItems:'center',gap:'14px',marginBottom:'32px'}}>
                    {isMobile ? (
                        /* Mobile: back button (same style as New Sales Invoice) → Daily Book Purchase list */
                        <a href="/daily_report/daily_book_purchase/view/index" style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',textDecoration:'none',flexShrink:0,boxShadow:'0 2px 6px rgba(234,88,12,0.3)'}}>
                            <i className="fa fa-chevron-left" style={{fontSize:'14px',color:'#fff'}}></i>
                        </a>
                    ) : (
                        <div style={{width:'44px',height:'44px',borderRadius:'12px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',boxShadow:'0 4px 12px rgba(234,88,12,0.25)',flexShrink:0}}>
                            <i className="fa fa-file-text-o" style={{fontSize:'18px',color:'#fff'}}></i>
                        </div>
                    )}
                    <div>
                        <div style={{fontSize:'20px',fontWeight:'800',color:'#0f172a',letterSpacing:'-0.3px'}}>New Purchase Invoice</div>
                        <div style={{fontSize:'12px',color:'#94a3b8',fontWeight:'500',marginTop:'2px'}}>Fill in the details below to create a new purchase invoice</div>
                    </div>
                </div>

                {/* Section 1: Supplier & Date */}
                <div style={{marginBottom:'24px'}}>
                    <div style={{fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'12px',display:'flex',alignItems:'center',gap:'6px'}}>
                        <span style={{width:'18px',height:'18px',borderRadius:'50%',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'10px',fontWeight:'700',display:'inline-flex',alignItems:'center',justifyContent:'center'}}>1</span>
                        Supplier & Date
                    </div>
                    <div style={{display:'grid',gridTemplateColumns: isMobile ? '1fr' : '1fr 220px',gap:'16px'}}>
                        <div>
                            <label style={{...lblStyle,color:'#475569'}}>Supplier <span style={{color:'#ef4444'}}>*</span></label>
                            <Select
                                options={suppliers}
                                value={supplier}
                                onChange={(v) => { setSupplier(v); setErrors(prev => ({...prev, supplier: false})); }}
                                isSearchable
                                placeholder="Select Supplier..."
                                menuPortalTarget={document.body}
                                components={{
                                    Control: ({ children, ...props }) => {
                                        const { selectProps } = props;
                                        return (
                                            <div ref={props.innerRef} {...props.innerProps} style={{
                                                width:'100%',
                                                height:'42px',
                                                padding:'0 14px',
                                                borderRadius:'10px',
                                                background:'#ffffff',
                                                border: errors.supplier ? '2px solid #ef4444' : (props.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0'),
                                                boxShadow: props.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none',
                                                display:'flex',
                                                alignItems:'center',
                                                gap:'8px',
                                                cursor:'pointer',
                                                fontSize:'13.5px',
                                                fontWeight:'500',
                                                color: supplier ? '#0f172a' : '#94a3b8',
                                                textAlign:'left',
                                                transition:'border-color 0.15s, box-shadow 0.15s'
                                            }}>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}>
                                                    <path d="M1 3h15v13H1z"></path>
                                                    <path d="M16 8h4l3 3v5h-7"></path>
                                                    <circle cx="6" cy="19" r="2"></circle>
                                                    <circle cx="18" cy="19" r="2"></circle>
                                                </svg>
                                                <div style={{flex:1,display:'flex',alignItems:'center',minWidth:0}}>
                                                    {children}
                                                </div>
                                            </div>
                                        );
                                    },
                                    IndicatorSeparator: () => null,
                                }}
                                styles={{
                                    ...selectStyles,
                                    container: (b) => ({ ...b }),
                                    valueContainer: (b) => ({ ...b, padding:'0', height:'42px', flex:1, minWidth:0 }),
                                    singleValue: (b) => ({ ...b, color:'#0f172a', fontWeight:500, fontSize:'13.5px', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis' }),
                                    placeholder: (b) => ({ ...b, color:'#94a3b8', fontWeight:500, fontSize:'13.5px' }),
                                    input: (b) => ({ ...b, color:'#0f172a', fontWeight:500, fontSize:'13.5px', margin:0, padding:0 }),
                                    indicatorsContainer: (b) => ({ ...b, height:'42px' }),
                                    dropdownIndicator: (b) => ({ ...b, color:'#94a3b8', padding:'0 0 0 4px' }),
                                    menuPortal: (b) => ({ ...b, zIndex: 9999 }),
                                }}
                            />
                        </div>
                        <div style={{position:'relative'}}>
                            <label style={{...lblStyle,color:'#475569'}}>Invoice Date</label>
                            {isMobile ? (
                            <>
                                {/* Mobile: tap to toggle a floating calendar overlay (doesn't expand the card,
                                    stays within card width). Same compact look as the OrangeDatePicker calendar. */}
                                <button type="button" onClick={()=>setDateOpen(o=>!o)}
                                    style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid '+(dateOpen?'rgb(234, 88, 12)':'#e2e8f0'),background:'#fff',display:'flex',alignItems:'center',padding:'0 14px',gap:'8px',cursor:'pointer',outline:'none'}}>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'13px',fontWeight:'600',color:date?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{date ? (() => { const [y,m,d]=String(date).split('-'); return `${d}/${m}/${y}`; })() : 'Select date'}</span>
                                </button>
                                {dateOpen && (
                                <>
                                <div onClick={()=>{setDateOpen(false);setNsiMonthOpen(false);setNsiYearOpen(false);}} style={{position:'fixed',inset:0,zIndex:40}} />
                                <div style={{position:'absolute',top:'100%',left:0,right:0,marginTop:'6px',zIndex:50,display:'flex',justifyContent:'center'}}>
                                    <div className="npi-inline-cal" style={{border:'1px solid #fed7aa',borderRadius:'16px',background:'#fffcf7',padding:'10px',maxWidth:'100%',boxShadow:'0 12px 40px rgba(0,0,0,0.18)'}}>
                                    <style>{`.npi-inline-cal .react-datepicker{border:none;background:transparent !important;box-shadow:none !important;font-family:inherit}.npi-inline-cal .react-datepicker__month-container{float:none;background:transparent !important}.npi-inline-cal .react-datepicker__header{background:transparent !important;border-bottom:none;padding:0}.npi-inline-cal .react-datepicker__month{margin:4px 8px 8px !important;background:transparent !important}.npi-inline-cal .react-datepicker__day-names,.npi-inline-cal .react-datepicker__week{display:flex;align-items:center}.npi-inline-cal .react-datepicker__day-name,.npi-inline-cal .react-datepicker__day{display:inline-flex !important;align-items:center !important;justify-content:center !important;line-height:1 !important;width:2rem !important;height:2rem !important;margin:0.1rem !important;font-size:13px !important}.npi-inline-cal .react-datepicker__day-name{font-size:11px !important;font-weight:700 !important;color:#94a3b8 !important;text-transform:uppercase !important;letter-spacing:0.3px !important}.npi-inline-cal .react-datepicker__day{font-weight:600;color:#334155;border-radius:50%}.npi-inline-cal .react-datepicker__day:hover:not(.react-datepicker__day--selected){background:#fff;color:rgb(234, 88, 12)}.npi-inline-cal .react-datepicker__day--selected{background:rgb(234, 88, 12) !important;color:#fff !important;font-weight:800}.npi-inline-cal .react-datepicker__day--today{color:rgb(234, 88, 12);font-weight:700}.npi-inline-cal .react-datepicker__day--outside-month{color:#cbd5e1}.npi-inline-cal .react-datepicker__day--disabled{color:#e5e7eb !important}.npi-inline-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#334155}`}</style>
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
                            </>
                            ) : (
                            <div style={{display:'flex',alignItems:'center',gap:'8px',background:'#fff',border:'1.5px solid #e2e8f0',borderRadius:'10px',height:'44px',padding:'0 14px',transition:'border-color 0.15s'}}>
                                <OrangeDatePicker value={date} onChange={(val) => setDate(val)} />
                            </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Divider */}
                <div style={{height:'1px',background:'#f1f5f9',margin:'0 0 24px'}}/>

                {/* Section 2: Additional Details */}
                <div style={{marginBottom:'32px'}}>
                    <div style={{fontSize:'11px',fontWeight:'700',color:'rgb(234, 88, 12)',letterSpacing:'0.8px',textTransform:'uppercase',marginBottom:'12px',display:'flex',alignItems:'center',gap:'6px'}}>
                        <span style={{width:'18px',height:'18px',borderRadius:'50%',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'10px',fontWeight:'700',display:'inline-flex',alignItems:'center',justifyContent:'center'}}>2</span>
                        Additional Details
                    </div>
                    <div style={{display:'grid',gridTemplateColumns: isMobile ? '1fr 1fr' : '1fr 1fr 1fr 1fr',gap:'16px'}}>
                        {[
                            {label:'Delivery No', value:deliveryNo, set:setDeliveryNo, placeholder:'e.g. DN-001', icon:'fa-truck'},
                            {label:'Supplier Invoice', value:supplierInvoiceNo, set:setSupplierInvoiceNo, placeholder:'e.g. INV-2026', icon:'fa-file-text-o'},
                            {label:'AWB', value:awb, set:setAwb, placeholder:'e.g. AWB123456', icon:'fa-plane'},
                        ].map(f => (
                            <div key={f.label}>
                                <label style={{...lblStyle,color:'#475569'}}>{f.label}</label>
                                <div style={{position:'relative'}}>
                                    <i className={"fa "+f.icon} style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',fontSize:'12px',color:'#cbd5e1'}}></i>
                                    <input type="text" value={f.value} onChange={e=>f.set(e.target.value)} placeholder={f.placeholder}
                                        style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#fff',padding:'0 12px 0 34px',outline:'none',fontWeight:'500',color:'#1e293b',boxSizing:'border-box',transition:'border-color 0.15s'}}
                                        onFocus={e=>{e.target.style.borderColor='rgb(234, 88, 12)';e.target.parentElement.querySelector('i').style.color='rgb(234, 88, 12)';}}
                                        onBlur={e=>{e.target.style.borderColor='#e2e8f0';e.target.parentElement.querySelector('i').style.color='#cbd5e1';}} />
                                </div>
                            </div>
                        ))}
                        {/* Remarks - new reference UI */}
                        <div>
                            <label style={{...lblStyle,color:'#475569'}}>Remarks</label>
                            <div style={{height:'42px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#ffffff',display:'flex',alignItems:'center',gap:'8px',padding:'0 12px',boxShadow:'none',transition:'border-color 0.15s'}}
                                onFocus={(e)=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';}}>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}>
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                                <input type="text" value={remarks} onChange={e=>setRemarks(e.target.value)} placeholder="Any notes..."
                                    style={{flex:1,border:'none',outline:'none',background:'transparent',fontSize:'13px',color:'#0f172a',fontFamily:'inherit',padding:0,minWidth:0,fontWeight:'500'}}
                                    onFocus={e=>{e.target.parentElement.style.borderColor='rgb(234, 88, 12)';e.target.parentElement.querySelector('svg').setAttribute('stroke','rgb(234, 88, 12)');}}
                                    onBlur={e=>{e.target.parentElement.style.borderColor='#e2e8f0';e.target.parentElement.querySelector('svg').setAttribute('stroke','#94a3b8');}}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Buttons */}
                <div style={{display:'flex',gap:'12px',justifyContent:'flex-end',paddingTop:'8px',borderTop:'1px solid #f1f5f9'}}>
                    <a href={props.backUrl || '/data_entry/purchase_entry/view'} style={{height:'44px',padding:'0 28px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',display:'flex',alignItems:'center',gap:'6px',textDecoration:'none',outline:'none',transition:'all 0.15s'}}>
                        <i className="fa fa-times" style={{fontSize:'11px'}}></i> Cancel
                    </a>
                    <button disabled={generating || !supplier} onClick={handleContinue} style={{
                        height:'44px',padding:'0 32px',borderRadius:'10px',border:'none',outline:'none',
                        background: (!supplier || generating) ? '#e5e7eb' : 'linear-gradient(135deg,rgb(234, 88, 12),#e0600e)',
                        color: (!supplier || generating) ? '#9ca3af' : '#fff',
                        fontSize:'14px',fontWeight:'700',cursor: (!supplier || generating) ? 'not-allowed' : 'pointer',display:'flex',alignItems:'center',gap:'8px',
                        boxShadow: (!supplier || generating) ? 'none' : '0 4px 14px rgba(234,88,12,0.3)',
                        opacity: generating ? 0.7 : 1,transition:'all 0.15s',
                    }}>
                        {generating ? <><i className="fa fa-spinner fa-spin"></i> Creating...</> : <>Continue <i className="fa fa-arrow-right"></i></>}
                    </button>
                </div>
            </div>
        )}
        </div>


        {/* Products table */}
        {continued && <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #eaecf2',boxShadow:'0 1px 4px rgba(0,0,0,0.04)',overflow:'hidden'}}>
            {/* Add product — mobile shows a trigger button; the form itself moves into a bottom sheet (see render below). */}
            {isMobile && (
                <div style={{padding:'14px 16px',background:'#fff',borderBottom:'1px solid #eef2f7'}}>
                    <button type="button" onClick={() => setAddSheetOpen(true)} style={{width:'100%',height:'46px',borderRadius:'12px',border:'1.5px dashed rgb(234, 88, 12)',background:'#fff7f2',color:'rgb(234, 88, 12)',fontSize:'14px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px'}}>
                        <i className="fa fa-plus"></i> Add Product
                    </button>
                </div>
            )}
            {/* Add product row (desktop inline; on mobile this whole block is wrapped into the bottom sheet) */}
            {(!isMobile || addSheetOpen) && (
            <div style={isMobile
                ? {position:'fixed',left:0,right:0,bottom:0,top:0,zIndex:6000,display:'flex',flexDirection:'column',justifyContent:'flex-end'}
                : {}}>
              {isMobile && <div onClick={() => setAddSheetOpen(false)} style={{position:'absolute',inset:0,background:'rgba(15,17,21,0.45)'}} />}
              <div style={isMobile
                ? {position:'relative',background:'#fff',borderTopLeftRadius:'18px',borderTopRightRadius:'18px',boxShadow:'0 -8px 30px rgba(0,0,0,0.18)',maxHeight:'90vh',overflowY:'auto',paddingBottom:'8px',animation:'npiSheetUp 0.22s ease'}
                : {}}>
                {isMobile && (
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'14px 16px 6px'}}>
                        <span style={{fontSize:'15px',fontWeight:'800',color:'#0f1115'}}>Add Product</span>
                        <button type="button" onClick={() => setAddSheetOpen(false)} style={{background:'#f1f5f9',border:'none',borderRadius:'8px',width:'30px',height:'30px',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',color:'#475569',fontSize:'15px'}} aria-label="Close"><i className="fa fa-times"></i></button>
                    </div>
                )}
            <div style={{padding: isMobile ? '12px 14px' : '16px 20px',background:'#fff',borderBottom: isMobile ? 'none' : '1px solid #eef2f7'}}>
                {/* Row 1: Product (full width) */}
                <div style={{marginBottom:'10px'}}>
                    <label style={lblStyle}>Product</label>
                    <Select options={products} value={curProduct} onChange={(v) => { setCurProduct(v); setErrors(prev => ({...prev, product: false})); }}
                        isSearchable placeholder="Select..." menuPortalTarget={document.body}
                        styles={{...selectStyles, control:(b,s)=>({...b,minHeight:'40px',borderRadius:'10px',fontSize:'13px',fontWeight:'600',border: errors.product ? '2px solid #ef4444' : s.isFocused?'1.5px solid rgb(234, 88, 12)':'1px solid #e2e8f0',boxShadow: errors.product ? '0 0 0 3px rgba(239,68,68,0.08)' : s.isFocused?'0 0 0 3px rgba(234,88,12,0.08)':'none',background:s.isFocused?'#fff':'#f8fafc'})}}
                    />
                </div>
                {isMobile ? (
                    /* ── Mobile Row 2: Remarks top, then Qty | Price | Total | Add ── */
                    <>
                    <div style={{marginBottom:'8px'}}>
                        <label style={lblStyle}>Remarks</label>
                        <input type="text" value={curRemarks} onChange={e => setCurRemarks(e.target.value)} placeholder="Remarks..." style={inputStyle}
                            onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                            onBlur={e => {e.target.style.borderColor='#cbd5e1';e.target.style.background='#f8fafc';}}
                        />
                    </div>
                    <div style={{display:'flex',alignItems:'flex-end',gap:'8px',flexWrap:'wrap'}}>
                        <div style={{flex:1,minWidth:0}}>
                            <label style={lblStyle}>Qty</label>
                            <input type="number" min="0.01" step="any" value={curQty}
                                onChange={e => { setCurQty(e.target.value); setErrors(prev => ({...prev, qty: false})); }}
                                placeholder="Qty" style={{...inputStyle, border: errors.qty ? '2px solid #ef4444' : inputStyle.border}}
                                onFocus={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : 'rgb(234, 88, 12)';}}
                                onBlur={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : '#e2e8f0';}}
                            />
                        </div>
                        <div style={{flex:1,minWidth:0}}>
                            <label style={lblStyle}>Unit Price</label>
                            <input type="number" min="0.01" step="any" value={curPrice}
                                onChange={e => { setCurPrice(e.target.value); setErrors(prev => ({...prev, price: false})); }}
                                placeholder="Price" style={{...inputStyle, border: errors.price ? '2px solid #ef4444' : inputStyle.border}}
                                onFocus={e => {e.target.style.borderColor= errors.price ? '#ef4444' : 'rgb(234, 88, 12)';}}
                                onBlur={e => {e.target.style.borderColor= errors.price ? '#ef4444' : '#e2e8f0';}}
                            />
                        </div>
                        <div style={{flex:1,minWidth:0}}>
                            <label style={lblStyle}>Sell Price</label>
                            <input type="number" min="0" step="any" value={curSalePrice}
                                onChange={e => setCurSalePrice(e.target.value)}
                                placeholder="Sell Price" style={inputStyle}
                                onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';}}
                                onBlur={e => {e.target.style.borderColor='#e2e8f0';}}
                            />
                        </div>
                        <div style={{flexShrink:0,textAlign:'right',lineHeight:'40px',fontSize:'13px',fontWeight:'700',color:'#1e293b',paddingBottom:'0',alignSelf:'flex-end',overflow:'hidden',textOverflow:'ellipsis',whiteSpace:'nowrap'}}>
                            {curQty && curPrice ? formatTwoDecimal(Number(curQty)*Number(curPrice)) : '0.00'}
                        </div>
                        <button onClick={addProduct} style={{
                            height:'40px',padding:'0 16px',borderRadius:'10px',border:'none',flexShrink:0,
                            background:'linear-gradient(135deg,rgb(234, 88, 12),#e0600e)',color:'#fff',
                            fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',gap:'5px',
                            boxShadow:'0 2px 8px rgba(234,88,12,0.3)',
                        }}>
                            <i className="fa fa-plus"></i> Add
                        </button>
                    </div>
                    </>
                ) : (
                    /* ── Desktop Row 2: Remarks + Qty + Price + Total + Add ── */
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
                            <input type="number" min="0.01" step="any" value={curQty}
                                onChange={e => { setCurQty(e.target.value); setErrors(prev => ({...prev, qty: false})); }}
                                placeholder="Qty" style={{...inputStyle, border: errors.qty ? '2px solid #ef4444' : inputStyle.border}}
                                onFocus={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : 'rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor= errors.qty ? '#ef4444' : '#e2e8f0';e.target.style.background='#f8fafc';}}
                            />
                        </div>
                        <div style={{width:'100px'}}>
                            <label style={lblStyle}>Unit Price</label>
                            <input type="number" min="0.01" step="any" value={curPrice}
                                onChange={e => { setCurPrice(e.target.value); setErrors(prev => ({...prev, price: false})); }}
                                placeholder="Price" style={{...inputStyle, border: errors.price ? '2px solid #ef4444' : inputStyle.border}}
                                onFocus={e => {e.target.style.borderColor= errors.price ? '#ef4444' : 'rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor= errors.price ? '#ef4444' : '#e2e8f0';e.target.style.background='#f8fafc';}}
                            />
                        </div>
                        <div style={{width:'100px'}}>
                            <label style={lblStyle}>Sell Price</label>
                            <input type="number" min="0" step="any" value={curSalePrice}
                                onChange={e => setCurSalePrice(e.target.value)}
                                placeholder="Sell Price" style={inputStyle}
                                onFocus={e => {e.target.style.borderColor='rgb(234, 88, 12)';e.target.style.background='#f8fafc';}}
                                onBlur={e => {e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}}
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
                            background:'linear-gradient(135deg,rgb(234, 88, 12),#e0600e)',color:'#fff',
                            fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',gap:'6px',
                            boxShadow:'0 2px 8px rgba(234,88,12,0.3)',flexShrink:0,
                        }}>
                            <i className="fa fa-plus"></i> Add
                        </button>
                    </div>
                )}
            </div>
              </div>
            </div>
            )}

            {/* Items table */}
            {items.length > 0 && (
                <>
                <div style={{overflow:'hidden',position:'relative'}}>
                <div className="npi-scroll-inner" style={{minWidth: isMobile ? '600px' : 'auto'}}>
                    <table style={{width:'100%',borderCollapse:'collapse'}}>
                        <thead>
                            <tr>
                                <th style={{...thStyle,width:'40px'}}>#</th>
                                <th style={thStyle}>Product</th>
                                <th style={thStyle}>Remarks</th>
                                <th style={{...thStyle,textAlign:'right'}}>Qty</th>
                                <th style={{...thStyle,textAlign:'right'}}>Price</th>
                                <th style={{...thStyle,textAlign:'right'}}>Sell Price</th>
                                <th style={{...thStyle,textAlign:'right'}}>Total</th>
                                <th style={{...thStyle,width:'50px'}}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((item, idx) => (
                                <tr key={idx} style={{background: idx%2===0 ? '#fff' : '#fcfcfd'}}>
                                    <td style={{...tdStyle,width:'40px',color:'#94a3b8'}}>{idx+1}</td>
                                    <td style={{...tdStyle,fontWeight:'600'}}>{item.product_name}</td>
                                    <td style={{...tdStyle,color:'#64748b',fontStyle:'italic'}}>{item.remarks || '—'}</td>
                                    <td style={{...tdStyle,textAlign:'right'}}>{item.quantity}</td>
                                    <td style={{...tdStyle,textAlign:'right'}}>{formatTwoDecimal(item.price)}</td>
                                    <td style={{...tdStyle,textAlign:'right',color: item.sale_price ? '#16a34a' : '#d1d5db'}}>{item.sale_price ? formatTwoDecimal(item.sale_price) : '—'}</td>
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
                            className="npi-range-scroll"
                            onChange={(e) => {
                                const inner = document.querySelector('.npi-scroll-inner');
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
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'10px 16px',borderBottom:'1px solid #f3f4f6'}}>
                            <span style={{fontSize:'13px',color:'#6b7280',fontWeight:'500'}}>Sub Total</span>
                            <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatTwoDecimal(grandTotal)}</span>
                        </div>
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',padding:'12px 16px',background:'#fafafa'}}>
                            <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Total</span>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'rgb(234, 88, 12)'}}>{formatTwoDecimal(invoiceTotal)}</span>
                        </div>
                    </div>
                    <div style={{marginTop:'14px',display:'flex',justifyContent:'flex-end'}}>
                        <button onClick={generateInvoice} disabled={generating} style={{
                            height:'44px',padding:'0 32px',borderRadius:'10px',border:'none',
                            background: generating ? '#cbd5e1' : 'linear-gradient(135deg,rgb(234, 88, 12),#e0600e)',
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

        <ToastContainer position="top-right" autoClose={3000} />
    </div>
    );
}

// Mount
if (document.getElementById('new-purchase-invoice-app')) {
    const el = document.getElementById('new-purchase-invoice-app');
    const root = createRoot(el);
    root.render(<NewPurchaseInvoiceApp {...el.dataset} />);
}

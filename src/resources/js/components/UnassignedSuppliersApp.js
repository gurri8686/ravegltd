import React, { useEffect, useState, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import Select from 'react-select';
import { ToastContainer, toast } from 'react-toastify';
import { useWindowSize } from "./../hooks/useWindowSize";
import {AlertProvider, useAlert } from "./../hooks/AlertContext";
import AddStock from "./../elements/AddStock";
import SpecTableEmpty from "./../elements/SpecTableEmpty";
import DateRangePicker from "./../hooks/DateRangePicker";
import OrangeDatePicker from "./../hooks/OrangeDatePicker";
import DatePicker from "react-datepicker";

function UnassignedSuppliersApp(props) {
    const [products, setProducts] = useState([]);
    const [totalLoaded, setTotalLoaded] = useState(0);
    const [hasFetched, setHasFetched] = useState(false);
    const totalLoadedRef = useRef(0);
    const [loading, setLoading] = useState(true);
    const [searchText, setSearchText] = useState('');
    const _today = new Date();
    const _todayStr = _today.getFullYear() + '-' + String(_today.getMonth()+1).padStart(2,'0') + '-' + String(_today.getDate()).padStart(2,'0');
    const _monthAgo = new Date(_today); _monthAgo.setMonth(_monthAgo.getMonth() - 1);
    const _monthAgoStr = _monthAgo.getFullYear() + '-' + String(_monthAgo.getMonth()+1).padStart(2,'0') + '-' + String(_monthAgo.getDate()).padStart(2,'0');
    const [fromDate, setFromDate] = useState(_monthAgoStr);
    const [toDate, setToDate] = useState(_todayStr);
    const [supplierOptions, setSupplierOptions] = useState({});
    const [supplierSelections, setSupplierSelections] = useState({});
    const [savingIds, setSavingIds] = useState({});
    const [page, setPage] = useState(0);
    const [perPage, setPerPage] = useState(10);
    const [filterPopupOpen, setFilterPopupOpen] = useState(false);
    const [summaryOpen, setSummaryOpen] = useState(false);
    const [pendingFrom, setPendingFrom] = useState(null);
    const [pendingTo, setPendingTo] = useState(null);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [rangeStart, setRangeStart] = useState(null);
    const [rangeEnd, setRangeEnd] = useState(null);
    const [monthDdOpen, setMonthDdOpen] = useState(false);
    const [yearDdOpen, setYearDdOpen] = useState(false);
    const { width } = useWindowSize();

    const toYMD = (d) => { const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); return y+'-'+m+'-'+dd; };
    const fmtDisplay = (v) => { if (!v) return ''; const d=new Date(v+'T00:00:00'); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); };
    const handleRangeChange = (dates) => {
      const [start, end] = dates;
      setRangeStart(start);
      setRangeEnd(end||null);
      if (start) setPendingFrom(toYMD(start));
      if (end) { setPendingTo(toYMD(end)); }
    };
    const openCalendar = () => {
      setPendingFrom(fromDate||null); setPendingTo(toDate||null);
      setRangeStart(fromDate?new Date(fromDate+'T00:00:00'):null);
      setRangeEnd(toDate?new Date(toDate+'T00:00:00'):null);
      setCalendarOpen(true);
    };
    const applyRange = () => {
      let f=pendingFrom, t=pendingTo;
      if(f&&t&&f>t){[f,t]=[t,f];}
      if(f) setFromDate(f); if(t) setToDate(t);
      setCalendarOpen(false);
    };
    const isMobile = width < 768;
    const clearFilters = () => { setSearchText(''); setFromDate(_monthAgoStr); setToDate(_todayStr); setPage(0); };

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const res = await axios.post(props.listApi, { search: searchText, from_date: fromDate, to_date: toDate });
            if (res.data.success) {
                const payload = res.data.payload || [];
                setProducts(payload);
                setHasFetched(true);
                const newTotal = res.data.total ?? payload.length;
                if (newTotal > 0) { totalLoadedRef.current = newTotal; setTotalLoaded(newTotal); }
                else if (totalLoadedRef.current === 0) { totalLoadedRef.current = payload.length; setTotalLoaded(payload.length); }
                // Fetch supplier options for each unique product_id
                const uniqueProductIds = [...new Set((res.data.payload || []).map(p => p.product_id))];
                uniqueProductIds.forEach(async (pid) => {
                    if (supplierOptions[pid]) return;
                    try {
                        const sRes = await axios.get('/stock_closing/view/unassigned-suppliers/suppliers-by-product/' + pid);
                        if (sRes.data.success) {
                            const opts = [
                                {label:'--Select Supplier--',value:'',supplier_name:'--Select Supplier--'},
                                ...sRes.data.payload.flatMap((item) =>
                                    (item.supplier?.invoices || []).map((invoice) => ({
                                        label: invoice.invoice_title || 'Untitled',
                                        supplier_name: item.supplier?.name || 'Unknown',
                                        available_qty: invoice.quantity ?? null,
                                        sell_price: invoice.sale_price ?? null,
                                        value: item.supplier_id,
                                        supplier_invoice_id: invoice.supplier_invoice_id,
                                        supplier_invoice_product_id: invoice.id,
                                        // Fuzzy-match metadata so the row can hint the user
                                        is_fuzzy: !!sRes.data.fuzzy_match,
                                        matched_product_id: invoice.matched_product_id || null,
                                        matched_product_name: invoice.matched_product_name || null,
                                    }))
                                )
                            ];
                            setSupplierOptions(prev => ({ ...prev, [pid]: opts }));
                        }
                    } catch (e) {}
                });
            }
        } catch (e) {
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchProducts(); }, [fromDate, toDate]);

    // Toasts must not linger or leak onto another view: clear them when the user
    // switches tab / hides the page, and when this component unmounts (navigates
    // away). They only reappear on a fresh action (e.g. a new assign).
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

    const handleAssign = async (item) => {
        const selected = supplierSelections[item.id];
        if (!selected) { toast.error('Please select a supplier'); return; }
        setSavingIds(prev => ({ ...prev, [item.id]: true }));
        try {
            const res = await axios.post(props.assignApi, {
                id: item.id,
                supplier_id: selected.value,
                supplier_invoice_id: selected.supplier_invoice_id || null,
                supplier_invoice_product_id: selected.supplier_invoice_product_id || null,
            });
            if (res.data.success) {
                toast.success('Supplier assigned!');
                setProducts(prev => prev.filter(p => p.id !== item.id));
            } else {
                toast.error(res.data.message || 'Failed');
            }
        } catch (e) {
            toast.error('Failed to assign supplier');
        } finally {
            setSavingIds(prev => ({ ...prev, [item.id]: false }));
        }
    };

    const refreshSupplierOptions = async (productId) => {
        try {
            const sRes = await axios.get('/stock_closing/view/unassigned-suppliers/suppliers-by-product/' + productId);
            if (sRes.data.success) {
                const opts = [
                    {label:'--Select Supplier--',value:'',supplier_name:'--Select Supplier--'},
                    ...sRes.data.payload.flatMap((item) =>
                        (item.supplier?.invoices || []).map((invoice) => ({
                            label: invoice.invoice_title || 'Untitled',
                            supplier_name: item.supplier?.name || 'Unknown',
                            available_qty: invoice.quantity ?? null,
                            sell_price: invoice.sale_price ?? null,
                            value: item.supplier_id,
                            supplier_invoice_id: invoice.supplier_invoice_id,
                            supplier_invoice_product_id: invoice.id,
                            is_fuzzy: !!sRes.data.fuzzy_match,
                            matched_product_id: invoice.matched_product_id || null,
                            matched_product_name: invoice.matched_product_name || null,
                        }))
                    )
                ];
                setSupplierOptions(prev => ({ ...prev, [productId]: opts }));
            }
        } catch (e) {}
    };

    const fmtSupplier = (option, { context }) => {
        if (context === 'value') return <span>{option.supplier_name || option.label}</span>;
        if (!option.value) return <span style={{color:'#aaa',fontSize:'13px'}}>-- Select Supplier --</span>;
        const parts = (option.label || '').split('|');
        const qty = (parts.find(p => p.startsWith('Qty:')) || '').replace('Qty:','').trim();
        const price = option.sell_price != null && option.sell_price !== ''
            ? Number(option.sell_price).toFixed(2)
            : (parts.find(p => p.startsWith('P:')) || '').replace('P:','').trim();
        return (
            <div>
                <div style={{fontWeight:'600',fontSize:'13px',color:'inherit'}}>
                    {option.supplier_name || option.label}
                </div>
                {(qty || price) && (
                    <div style={{fontSize:'11px',color:'inherit',marginTop:'2px'}}>
                        {qty ? 'Qty: ' + qty : ''}
                        {qty && price ? ' · ' : ''}
                        {price ? 'Price: ' + price : ''}
                    </div>
                )}
            </div>
        );
    };

    const onSaveStock = (productId) => {
        toast.success('Stock added! Suppliers refreshed.');
        refreshSupplierOptions(productId);
    };

    const filtered = (searchText ? products.filter(p => (p.product_name || '').toLowerCase().includes(searchText.toLowerCase()) || (p.customer_name || '').toLowerCase().includes(searchText.toLowerCase())) : products)
        .slice().sort((a, b) => {
            const parse = d => { if (!d) return 0; const [day,mon,yr] = d.split('/'); return new Date(yr,mon-1,day); };
            return parse(a.created_at) - parse(b.created_at);
        });

    return (
        <div style={props.noHeader ? {} : { background: '#fff', borderRadius: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.06)', border: '1px solid #f1f5f9', overflow: 'visible', marginBottom: '16px' }}>
            <ToastContainer position="top-right" autoClose={5000} closeOnClick closeButton pauseOnHover={false} pauseOnFocusLoss={false} />

            {/* Page Header */}
            {props.noHeader ? null : (
                <div style={{ padding: isMobile ? '14px 16px' : '18px 24px 14px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
                        <div style={{ width: '44px', height: '41px', borderRadius: '14px', background: 'rgb(234, 88, 12)', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: '0 3px 12px rgba(234,88,12,0.25)' }}>
                            <i className="fa fa-unlink" style={{ color: '#fff', fontSize: '20px' }}></i>
                        </div>
                        <div>
                            <h1 style={{ fontSize: isMobile ? '17px' : '19px', fontWeight: '600', color: '#0f172a', margin: 0 }}>Unassigned Suppliers</h1>
                            <p style={{ fontSize: '12.5px', color: '#94a3b8', fontWeight: '500', margin: '2px 0 0' }}>Products without supplier — assign suppliers here</p>
                        </div>
                    </div>
                </div>
            )}

            {/* Cards + Filter bar */}
            <div style={{padding: isMobile ? '0' : '18px 20px 16px',background:'#fff',borderBottom: isMobile ? 'none' : '1px solid #f1f5f9'}}>
              {/* Summary Cards */}
              {isMobile ? (() => {
                const total = totalLoaded;
                const pending = products.length;
                const assigned = total - pending;
                const pct = total > 0 ? Math.round(assigned/total*100) : 0;
                const statCards = [{label:'Total',value:total,color:'#111827'},{label:'Assigned',value:assigned,color:'#16a34a'},{label:'Pending',value:pending,color:'rgb(234, 88, 12)'}];
                return (<>
                  {/* Collapsed bar */}
                  <div onClick={()=>setSummaryOpen(v=>!v)} style={{borderRadius:summaryOpen?'16px 16px 0 0':'16px',border:'1px solid #eaecf2',borderBottom:summaryOpen?'1px solid #f0f0f0':'1px solid #eaecf2',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',padding:'12px 14px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer',marginBottom:summaryOpen?0:'14px'}}>
                    <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                      <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'rgb(234, 88, 12)'}}/>
                      <span style={{fontSize:'10px',fontWeight:'800',color:'#374151',letterSpacing:'0.6px',textTransform:'uppercase'}}>Summary</span>
                    </div>
                    <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                      <div style={{display:'flex',gap:'8px'}}>
                        {statCards.map((s,i)=>(<span key={i} style={{fontSize:'12px',fontWeight:'700',color:s.color}}>{s.value}</span>))}
                      </div>
                      <i className={'fa fa-chevron-'+(summaryOpen?'up':'down')} style={{fontSize:'9px',color:'#9ca3af'}}/>
                    </div>
                  </div>
                  {/* Expanded */}
                  {summaryOpen && (
                    <div style={{borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',background:'#fff',overflow:'hidden',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',marginBottom:'14px'}}>
                      <div style={{display:'flex',padding:'10px 16px 12px'}}>
                        {statCards.map(({label:lbl,value},i)=>(
                          <React.Fragment key={lbl}>
                            <div style={{flex:1,minWidth:0}}>
                              <div style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'4px'}}>{lbl}</div>
                              <div style={{fontSize:'20px',fontWeight:'700',color:'#111827',lineHeight:1.1,letterSpacing:'-0.5px'}}>{value}</div>
                            </div>
                            {i < statCards.length-1 && <div style={{width:'1px',background:'#e5e7eb',margin:'0 8px',alignSelf:'stretch',flexShrink:0}}/>}
                          </React.Fragment>
                        ))}
                      </div>
                      <div style={{height:'1px',background:'#e5e7eb',margin:'0 16px'}}/>
                      <div style={{padding:'8px 16px'}}>
                        <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:'4px'}}>
                          <span style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase'}}>Assign Rate</span>
                          <span style={{fontSize:'10px',color:'#9ca3af',fontWeight:'600'}}>{pct}%</span>
                        </div>
                        <div style={{height:'3px',borderRadius:'99px',background:'#e5e7eb',overflow:'hidden'}}>
                          <div style={{height:'100%',width:pct+'%',borderRadius:'99px',background:'rgb(234, 88, 12)'}}/>
                        </div>
                      </div>
                    </div>
                  )}
                </>);
              })() : (
                <div style={{display:'grid',gridTemplateColumns:'repeat(2,1fr)',gap:'10px',marginBottom:'14px'}}>
                  {[
                    {label:'Total Unassigned',value:products.length,icon:'fa-exclamation-triangle',color:'#d97706',light:'#fffbeb'},
                    {label:'Unique Products',value:[...new Set(products.map(p=>p.product_id))].length,icon:'fa-cubes',color:'#3b82f6',light:'#eff6ff'},
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
              )}
              {/* Desktop filter bar */}
              {!isMobile && (
                <div style={{display:'flex',alignItems:'center',gap:'8px'}}>
                  <div style={{flex:'1 1 0',display:'flex',alignItems:'center',gap:'9px',padding:'0 14px',height:'40px',border:'1.5px solid #e8edf2',borderRadius:'10px',background:'#fff',minWidth:0}}>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" placeholder="Search product..." value={searchText}
                      onChange={(e) => setSearchText(e.target.value)}
                      style={{flex:1,height:'100%',border:'none',outline:'none',fontSize:'13px',color:'#374151',background:'transparent',minWidth:0}}
                    />
                    {searchText && (
                      <button type="button" onClick={() => setSearchText('')} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',flexShrink:0,outline:'none'}}>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                      </button>
                    )}
                  </div>
                  <div style={{flex:'1 1 0',minWidth:0}}>
                    <DateRangePicker fromDate={fromDate} toDate={toDate} onFromChange={(val) => setFromDate(val)} onToChange={(val) => setToDate(val)} width={width} />
                  </div>
                  {/* Print */}
                  {props.printUrl && (
                    <button type="button" className="icon-tip" data-tip="Print" onClick={() => {
                      const params = new URLSearchParams({ search: searchText || '', from_date: fromDate || '', to_date: toDate || '' });
                      window.open(props.printUrl + '?' + params.toString(), '_blank');
                    }}
                      style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                      onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
                      onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
                    >
                      <i className="fa fa-print" style={{fontSize:'14px'}}></i>
                    </button>
                  )}
                  {/* Download Excel */}
                  {props.excelUrl && (
                    <button type="button" className="icon-tip" data-tip="Download Excel" onClick={() => {
                      const params = new URLSearchParams({ search: searchText || '', from_date: fromDate || '', to_date: toDate || '' });
                      window.location.href = props.excelUrl + '?' + params.toString();
                    }}
                      style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                      onMouseEnter={e=>{e.currentTarget.style.borderColor='rgb(234, 88, 12)';e.currentTarget.style.color='rgb(234, 88, 12)';e.currentTarget.style.background='#fff7ed';}}
                      onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
                    >
                      <i className="fa fa-download" style={{fontSize:'14px'}}></i>
                    </button>
                  )}
                </div>
              )}
            </div>

            {/* Mobile search + filter bar — same as StockCheck/Closing (zero inset, 44px, Sales-style filter) */}
            {isMobile && (
              <div style={{margin:'0 0 14px',display:'flex',alignItems:'center',gap:'8px'}}>
                {/* Search input */}
                <div style={{flex:1,display:'flex',alignItems:'center',gap:'8px',height:'44px',border:'1.5px solid #e8edf2',borderRadius:'12px',background:'#fff',padding:'0 12px',minWidth:0}}>
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                  <input type="text" placeholder="Search product..."
                    value={searchText} onChange={(e) => { setSearchText(e.target.value); setPage(0); }}
                    style={{flex:1,border:'none',outline:'none',fontSize:'12px',color:'#374151',background:'transparent',minWidth:0}}
                  />
                  {searchText && (
                    <button type="button" onClick={()=>{setSearchText('');setPage(0);}} style={{background:'none',border:'none',cursor:'pointer',padding:'2px',display:'flex',alignItems:'center',outline:'none'}}>
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  )}
                </div>
                {/* Filter button — Sales-style solid orange */}
                <button type="button" onClick={()=>setFilterPopupOpen(v=>!v)}
                  style={{flexShrink:0,height:'44px',width:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',boxShadow:'0 2px 6px rgba(234,88,12,0.3)',display:'flex',alignItems:'center',justifyContent:'center',cursor:'pointer',position:'relative',outline:'none'}}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                </button>
              </div>
            )}

            {/* Mobile action buttons — Print / Excel (below the search bar) — same as Stock Check */}
            {isMobile && (
              <div style={{margin:'0 0 14px',display:'flex',gap:'10px'}}>
                {props.printUrl && (
                <button type="button" onClick={() => {
                  const params = new URLSearchParams({ search: searchText || '', from_date: fromDate || '', to_date: toDate || '' });
                  window.open(props.printUrl + '?' + params.toString(), '_blank');
                }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
                  <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
                </button>
                )}
                {props.excelUrl && (
                <button type="button" onClick={() => {
                  const params = new URLSearchParams({ search: searchText || '', from_date: fromDate || '', to_date: toDate || '' });
                  window.location.href = props.excelUrl + '?' + params.toString();
                }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
                  <i className="fa fa-file-excel-o" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Excel
                </button>
                )}
              </div>
            )}

            {/* Mobile date range calendar bottom sheet */}
            {isMobile && calendarOpen && (<>
                <div onMouseDown={()=>setCalendarOpen(false)} style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.4)'}}/>
                <div onMouseDown={e=>e.stopPropagation()} style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'85vh',overflowY:'auto'}}>
                    <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}><div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/></div>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'4px 18px 14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{width:'30px',height:'30px',borderRadius:'9px',background:'#fff7ed',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Select Date Range</span>
                        </div>
                        <button type="button" onClick={()=>setCalendarOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'50%',width:'30px',height:'30px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div style={{padding:'0 18px 14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{flex:1,background:'#fff',border:'2px solid '+(pendingFrom?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingFrom?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>From</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingFrom?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingFrom?fmtDisplay(pendingFrom):'Select'}</div>
                            </div>
                            <div style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 10px rgba(234,88,12,0.35)'}}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </div>
                            <div style={{flex:1,background:'#fff',border:'2px solid '+(pendingTo?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingTo?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>To</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingTo?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingTo?fmtDisplay(pendingTo):'Select'}</div>
                            </div>
                        </div>
                    </div>
                    <div className="sp-presets" style={{display:'flex',gap:'8px',padding:'0 18px 14px',overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
                        {['Today','Yesterday','Last 7d','This month','Custom Range'].map(label => {
                            const pr=(()=>{const now=new Date();let f,t;if(label==='Today'){f=t=now;}else if(label==='Yesterday'){f=t=new Date(now.getTime()-86400000);}else if(label==='Last 7d'){f=new Date(now.getTime()-6*86400000);t=now;}else if(label==='This month'){f=new Date(now.getFullYear(),now.getMonth(),1);t=now;}else return {f:null,t:null};return {f,t};})();
                            const active = label!=='Custom Range' && pr.f && pendingFrom===toYMD(pr.f) && pendingTo===toYMD(pr.t);
                            return (<button key={label} type="button" onClick={()=>{ if(label==='Custom Range') return; setRangeStart(pr.f); setRangeEnd(pr.t); setPendingFrom(toYMD(pr.f)); setPendingTo(toYMD(pr.t)); }} style={{flexShrink:0,height:'34px',padding:'0 16px',borderRadius:'999px',border: active ? 'none' : '1.5px solid #e5e7eb',background: active ? '#111827' : '#fff',color: active ? '#fff' : '#475569',fontSize:'13px',fontWeight:'700',cursor:'pointer',outline:'none',whiteSpace:'nowrap'}}>{label}</button>);
                        })}
                    </div>
                    <style>{`.sp-range .react-datepicker{width:100%;border:none;font-family:inherit;background:#fff !important;box-shadow:none !important}.sp-range .react-datepicker__month-container{width:100%;float:none;background:#fff !important}.sp-range .react-datepicker__month{background:#fff !important;margin:0 !important}.sp-range .react-datepicker__week{background:#fff !important}.sp-range .react-datepicker__header{background:#fff !important;border-bottom:none;padding:0}.sp-range .react-datepicker__header--custom{background:#fff !important;border-bottom:none !important;padding:0 !important}.sp-range .react-datepicker__day-names,.sp-range .react-datepicker__week{display:flex;justify-content:space-around}.sp-range .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0}.sp-range .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:42px;font-size:14px;font-weight:500;color:#334155;margin:0;border-radius:50%;transition:background 0.12s,color 0.12s;position:relative}.sp-range .react-datepicker__day:hover:not(.react-datepicker__day--selected):not(.react-datepicker__day--range-start):not(.react-datepicker__day--range-end){background:#f1f5f9;color:#0f172a}.sp-range .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.sp-range .react-datepicker__day--in-range,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start){background:transparent !important;color:rgb(234, 88, 12) !important;font-weight:600;position:relative}.sp-range .react-datepicker__day--in-range::before,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start)::before{content:'';position:absolute;top:4px;bottom:4px;left:0;right:0;background:#fff7f0;z-index:-1}.sp-range .react-datepicker__day--selected,.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--selecting-range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--selected,.sp-range .react-datepicker__day--today.react-datepicker__day--range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--range-end{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;position:relative;z-index:1}
.sp-range .react-datepicker__day--range-start:not(.react-datepicker__day--range-end)::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;right:0;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--range-end:not(.react-datepicker__day--range-start)::after{content:'';position:absolute;top:4px;bottom:4px;left:0;right:50%;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--selected::before,.sp-range .react-datepicker__day--range-start::before,.sp-range .react-datepicker__day--range-end::before,.sp-range .react-datepicker__day--selecting-range-start::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50% !important}.sp-range .react-datepicker__day--outside-month{color:#d1d5db}.sp-range .react-datepicker__day--disabled{color:#e5e7eb !important;background:transparent !important}.sp-range .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b}.sp-range .react-datepicker__navigation{display:none !important}.sp-range .react-datepicker__current-month{display:none !important}.sp-dd{position:relative;display:inline-block}.sp-dd-btn{border:1.5px solid #e5e7eb;border-radius:9px;padding:7px 26px 7px 14px;font-size:13px;font-weight:700;color:#1e293b;cursor:pointer;outline:none;background:#f4f4f6;position:relative}.sp-dd-btn:focus,.sp-dd-btn:active{outline:none;border-color:rgb(234, 88, 12)}.sp-dd-btn::after{content:'';position:absolute;right:10px;top:50%;transform:translateY(-50%);border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #94a3b8}.sp-dd-list{position:absolute;top:calc(100% + 4px);left:50%;transform:translateX(-50%);background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:99;max-height:180px;overflow-y:auto;min-width:84px;padding:4px}.sp-dd-list::-webkit-scrollbar{width:3px}.sp-dd-list::-webkit-scrollbar-thumb{background:#fed7aa;border-radius:3px}.sp-dd-item{padding:6px 12px;font-size:12px;font-weight:600;border-radius:6px;cursor:pointer;text-align:center;color:#374151;transition:all 0.1s}.sp-dd-item:hover{background:#fff7ed;color:rgb(234, 88, 12)}.sp-dd-item.active{background:rgb(234, 88, 12);color:#fff;font-weight:700}.sp-presets{scrollbar-width:none;-ms-overflow-style:none}.sp-presets::-webkit-scrollbar{display:none;width:0;height:0}`}</style>
                    <div className="sp-range" style={{padding:'4px 16px 0'}}>
                        <DatePicker inline selected={rangeStart} onChange={handleRangeChange} startDate={rangeStart} endDate={rangeEnd} selectsRange maxDate={new Date()}
                            renderCustomHeader={({date,changeYear,changeMonth,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                const mnthsFull=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                const mnths=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                const cy=new Date().getFullYear(); const yrs=Array.from({length:10},(_,i)=>cy-5+i);
                                const nb={width:'34px',height:'34px',border:'1.5px solid #e5e7eb',borderRadius:'50%',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',outline:'none',flexShrink:0};
                                return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'12px',gap:'6px'}}>
                                    <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{...nb,opacity:prevMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                                    <div className="sp-dd" style={{flex:1,display:'flex',justifyContent:'center'}}>
                                        <button type="button" onClick={()=>setMonthDdOpen(v=>!v)} style={{display:'inline-flex',alignItems:'center',gap:'7px',background:'transparent',border:'none',outline:'none',cursor:'pointer',fontSize:'16px',fontWeight:'800',color:'#0f172a',padding:'4px 8px'}}>
                                            {mnthsFull[date.getMonth()]} {date.getFullYear()}
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        {monthDdOpen&&(<div className="sp-dd-list" style={{minWidth:'200px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'4px',padding:'8px'}}>
                                            <div style={{gridColumn:'1 / -1',display:'flex',alignItems:'center',justifyContent:'space-between',padding:'2px 4px 6px'}}>
                                                <span style={{fontSize:'11px',fontWeight:'700',color:'#94a3b8'}}>MONTH</span>
                                                <select value={date.getFullYear()} onChange={e=>changeYear(Number(e.target.value))} style={{border:'1.5px solid #e5e7eb',borderRadius:'7px',fontSize:'12px',fontWeight:'700',color:'#0f172a',padding:'3px 6px',outline:'none',cursor:'pointer'}}>{yrs.map(y=><option key={y} value={y}>{y}</option>)}</select>
                                            </div>
                                            {mnths.map((m,i)=><div key={m} className={'sp-dd-item'+(date.getMonth()===i?' active':'')} onClick={()=>{changeMonth(i);setMonthDdOpen(false);}}>{m}</div>)}
                                        </div>)}
                                    </div>
                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...nb,opacity:nextMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                </div>);
                            }}
                        />
                    </div>
                    <div style={{display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',padding:'8px 18px 16px'}}>
                        <button type="button" onClick={()=>{setCalendarOpen(false);setFilterPopupOpen(true);}} style={{height:'52px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'15px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px'}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel
                        </button>
                        <button type="button" onClick={applyRange} disabled={!pendingFrom||!pendingTo} style={{height:'52px',borderRadius:'14px',border:'none',background:(!pendingFrom||!pendingTo)?'#e2e8f0':'rgb(234, 88, 12)',color:(!pendingFrom||!pendingTo)?'#94a3b8':'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:(!pendingFrom||!pendingTo)?'default':'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:(!pendingFrom||!pendingTo)?'none':'0 6px 16px rgba(234,88,12,0.35)'}}>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply
                        </button>
                    </div>
                </div>
            </>)}

            {/* Mobile filter bottom sheet — only non-date filters */}
            {isMobile && filterPopupOpen && (
              <>
                <div onMouseDown={()=>setFilterPopupOpen(false)} style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
                <div onMouseDown={e=>e.stopPropagation()} style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)'}}>
                  <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                    <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
                  </div>
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
                    {/* Date Range */}
                    <div>
                      <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date Range</div>
                      <button type="button" onClick={()=>{setFilterPopupOpen(false);openCalendar();}}
                        style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'8px',cursor:'pointer',outline:'none'}}>
                        <i className="fa fa-calendar" style={{fontSize:'13px',color:'rgb(234, 88, 12)'}}></i>
                        <span style={{fontSize:'13px',fontWeight:'600',color:fromDate&&toDate?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{fromDate&&toDate ? fmtDisplay(fromDate)+' — '+fmtDisplay(toDate) : 'Select date range'}</span>
                        <i className="fa fa-chevron-right" style={{fontSize:'10px',color:'#d1d5db'}}></i>
                      </button>
                    </div>
                    {/* Actions */}
                    <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px'}}>
                      <button type="button" onClick={()=>{ setFromDate(''); setToDate(''); setFilterPopupOpen(false); }}
                        style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                        Clear
                      </button>
                      <button type="button" onClick={()=>setFilterPopupOpen(false)}
                        style={{height:'44px',borderRadius:'12px',border:'none',background:'rgb(234, 88, 12)',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Apply
                      </button>
                    </div>
                  </div>
                </div>
              </>
            )}

            {/* Products List */}
            <div style={{ overflow: 'hidden', borderTop: isMobile ? 'none' : '1px solid #f1f5f9', paddingTop: isMobile ? '10px' : 0 }}>
                {loading ? (
                    <div style={{ textAlign: 'center', padding: '60px 20px', color: '#94a3b8' }}>
                        <i className="fa fa-spinner fa-spin" style={{ fontSize: '28px', marginBottom: '12px', display: 'block' }}></i>
                        Loading products...
                    </div>
                ) : (
                    <>
                    {/* Table header — desktop (always visible) */}
                    {!isMobile && (
                        <div style={{ display: 'grid', gridTemplateColumns: '55px 80px 1fr 90px 45px 65px 2fr 95px', padding: '12px 20px', borderBottom: '2px solid #eef2f7', background: '#fafbfc', gap: '10px' }}>
                            {['Inv #', 'Date', 'Product', 'Customer', 'Qty', 'Price', 'Supplier', 'Action'].map(h => (
                                <div key={h} style={{ fontSize: '11px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.7px' }}>{h}</div>
                            ))}
                        </div>
                    )}
                    {filtered.length === 0 ? (
                        <SpecTableEmpty onClear={false} />
                    ) : (<>

                    {filtered.slice(page * perPage, (page + 1) * perPage).map((item, idx) => (
                        isMobile ? (
                            /* ── Mobile Card ── */
                            <div key={item.id} style={{ display: 'flex', margin: '0 0 10px', borderRadius: '14px', border: '1px solid #eaecf2', overflow: 'hidden', background: '#fff', boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
                                <div style={{ width: '4px', flexShrink: 0, background: 'linear-gradient(180deg,rgb(234, 88, 12),#ea580c)' }}/>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    {/* Top content */}
                                    <div style={{ padding: '0 12px 10px' }}>
                                        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '8px', marginBottom: '4px' }}>
                                            <div style={{ minWidth: 0 }}>
                                                {/* Invoice # · Date */}
                                                <div style={{ fontSize: '11px', color: 'rgb(234, 88, 12)', fontWeight: '700', marginBottom: '2px' }}>
                                                    <a href={`/data_entry/sales_entry/invoice/${item.invoice_id}`} style={{ color: 'rgb(234, 88, 12)', textDecoration: 'none' }}>#{item.invoice_id}</a>
                                                    {item.created_at ? ` · ${item.created_at}` : ''}
                                                </div>
                                                {/* Product name */}
                                                <div style={{ fontWeight: '700', color: '#1e293b', fontSize: '13px', lineHeight: 1.3, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{item.product_name}</div>
                                                {/* Customer */}
                                                {item.customer_name && <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '600', marginTop: '1px' }}>{item.customer_name}</div>}
                                            </div>
                                            <button onClick={() => handleAssign(item)} disabled={savingIds[item.id]} style={{ flexShrink: 0, height: '32px', padding: '0 12px', borderRadius: '8px', border: 'none', background: 'rgb(234, 88, 12)', color: '#fff', fontSize: '11px', fontWeight: '700', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', gap: '4px', opacity: savingIds[item.id] ? 0.7 : 1, outline: 'none', whiteSpace: 'nowrap' }}>
                                                {savingIds[item.id] ? <i className="fa fa-spinner fa-spin"></i> : <><i className="fa fa-check-circle"></i> Assign</>}
                                            </button>
                                        </div>
                                        {/* Badges */}
                                        <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginTop: '6px' }}>
                                            <span style={{ fontSize: '11px', fontWeight: '600', color: '#374151', background: '#f8fafc', border: '1px solid #e5e7eb', borderRadius: '6px', padding: '2px 8px' }}>Qty: {item.quantity}</span>
                                            <span style={{ fontSize: '11px', fontWeight: '600', color: '#374151', background: '#f8fafc', border: '1px solid #e5e7eb', borderRadius: '6px', padding: '2px 8px' }}>{props.currency} {Number(item.unit_price || 0).toFixed(2)}</span>
                                        </div>
                                    </div>
                                    {/* Supplier select + Add Stock */}
                                    <div style={{ padding: '7px 12px 10px', borderTop: '1px solid #f1f5f9' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                            <div style={{ flex: 1, minWidth: 0 }}>
                                                <Select
                                                    options={supplierOptions[item.product_id] || []}
                                                    value={supplierSelections[item.id] || null}
                                                    onChange={(val) => setSupplierSelections(prev => ({ ...prev, [item.id]: val }))}
                                                    placeholder={supplierOptions[item.product_id] ? "Select Supplier" : "Loading..."}
                                                    isLoading={!supplierOptions[item.product_id]}
                                                    noOptionsMessage={() => (<span style={{fontSize:'11px',color:'#94a3b8'}}>No supplier has stock for this product. Add a purchase first.</span>)}
                                                    formatOptionLabel={fmtSupplier}
                                                    menuPortalTarget={typeof document !== "undefined" ? document.body : null} menuShouldBlockScroll={false}
                                                    styles={{
                                                        control: (b, s) => ({ ...b, minHeight: '34px', fontSize: '12px', fontWeight: '600', borderRadius: '8px', border: s.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e5e7eb', boxShadow: s.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none', background: s.isFocused ? '#fff' : '#fafbfc' }),
                                                        placeholder: b => ({ ...b, color: '#9ca3af', fontSize: '12px' }),
                                                        menuPortal: b => ({ ...b, zIndex: 99999 }),
                                                        option: (b, s) => ({ ...b, fontSize: '12px', padding: '8px 12px', backgroundColor: !s.data.value ? '#fff' : s.isSelected ? '#c2410c' : s.isFocused ? 'rgb(234, 88, 12)' : '#fff', color: !s.data.value ? '#aaa' : (s.isSelected || s.isFocused) ? '#fff' : '#334155' }),
                                                        valueContainer: b => ({ ...b, padding: '0 8px' }),
                                                        indicatorsContainer: b => ({ ...b, '& > div': { padding: '0 6px' } }),
                                                    }}
                                                />
                                            </div>
                                            <AddStock product={{ label: item.product_name, value: item.product_id }} index={idx} onSaveStock={() => onSaveStock(item.product_id)} invoiceId={0} />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            /* ── Desktop Row ── */
                            <div key={item.id} style={{ display: 'grid', gridTemplateColumns: '55px 80px 1fr 90px 45px 65px 2fr 95px', padding: '14px 20px', borderBottom: '1px solid #f3f4f8', gap: '10px', alignItems: 'center', background: idx % 2 === 0 ? '#fff' : '#fcfcfd' }}>
                                <a href={`/data_entry/sales_entry/invoice/${item.invoice_id}`} style={{ fontWeight: '700', color: 'rgb(234, 88, 12)', textDecoration: 'none', fontSize: '13px' }}>#{item.invoice_id}</a>
                                <div style={{ fontSize: '13px', color: '#64748b', whiteSpace: 'nowrap' }}>{item.created_at || '—'}</div>
                                <div>
                                    <div style={{ fontWeight: '600', fontSize: '14px', color: '#1e293b' }}>{item.product_name}</div>
                                    {item.remarks && <div style={{ fontSize: '12px', color: '#94a3b8', fontStyle: 'italic' }}>{item.remarks}</div>}
                                </div>
                                <div style={{ fontSize: '13px', color: '#64748b' }}>{item.customer_name || '—'}</div>
                                <div style={{ fontSize: '14px', color: '#374151' }}>{item.quantity}</div>
                                <div style={{ fontSize: '13px', fontWeight: '600', color: '#1e293b' }}>{props.currency} {Number(item.unit_price || 0).toFixed(2)}</div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                    <div style={{ flex: 1, minWidth: 0 }}>
                                        <Select
                                            options={supplierOptions[item.product_id] || []}
                                            value={supplierSelections[item.id] || null}
                                            onChange={(val) => setSupplierSelections(prev => ({ ...prev, [item.id]: val }))}
                                            placeholder={supplierOptions[item.product_id] ? "Select Supplier" : "Loading..."}
                                            isLoading={!supplierOptions[item.product_id]}
                                            formatOptionLabel={fmtSupplier}
                                            menuPortalTarget={typeof document !== "undefined" ? document.body : null} menuShouldBlockScroll={false}
                                            styles={{
                                                control: (b, s) => ({ ...b, minHeight: '36px', fontSize: '13px', fontWeight: '600', borderRadius: '8px', border: s.isFocused ? '1.5px solid rgb(234, 88, 12)' : '1.5px solid #e2e8f0', boxShadow: s.isFocused ? '0 0 0 3px rgba(234,88,12,0.08)' : 'none', background: s.isFocused ? '#fff' : '#fafafa' }),
                                                placeholder: b => ({ ...b, color: '#1e293b' }),
                                                menuPortal: b => ({ ...b, zIndex: 99999 }),
                                                option: (b, s) => ({ ...b, fontSize: '13px', backgroundColor: !s.data.value ? '#fff' : s.isSelected ? '#c2410c' : s.isFocused ? 'rgb(234, 88, 12)' : '#fff', color: !s.data.value ? '#aaa' : (s.isSelected || s.isFocused) ? '#fff' : '#334155' }),
                                            }}
                                        />
                                    </div>
                                    <AddStock product={{ label: item.product_name, value: item.product_id }} index={idx} onSaveStock={() => onSaveStock(item.product_id)} invoiceId={0} />
                                </div>
                                <button onClick={() => handleAssign(item)} disabled={savingIds[item.id]} style={{ height: '36px', padding: '0 16px', borderRadius: '8px', border: 'none', background: 'rgb(234, 88, 12)', color: '#fff', fontSize: '13px', fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '5px', opacity: savingIds[item.id] ? 0.7 : 1 }}>
                                    {savingIds[item.id] ? <i className="fa fa-spinner fa-spin"></i> : <><i className="fa fa-check"></i> Assign</>}
                                </button>
                            </div>
                        )
                    ))}
                    {/* Pagination */}
                    {filtered.length > 0 && (
                        <div style={{ padding: '12px 20px', borderTop: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#fafbfc' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <span style={{ fontSize: '12px', color: '#64748b' }}>Rows per page:</span>
                                <select value={perPage} onChange={(e) => { setPerPage(Number(e.target.value)); setPage(0); }}
                                    style={{ height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', fontSize: '12px', fontWeight: '600', color: '#1e293b', padding: '0 6px', outline: 'none', cursor: 'pointer' }}>
                                    {[5,10,20,50].map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                                <span style={{ fontSize: '12px', color: '#64748b', marginRight: '8px' }}>{page * perPage + 1}-{Math.min((page + 1) * perPage, filtered.length)} of {filtered.length}</span>
                                <button onClick={() => setPage(0)} disabled={page === 0} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: page === 0 ? 'not-allowed' : 'pointer', color: page === 0 ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '10px' }}>
                                    <i className="fa fa-angle-double-left"></i>
                                </button>
                                <button onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: page === 0 ? 'not-allowed' : 'pointer', color: page === 0 ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px' }}>
                                    <i className="fa fa-angle-left"></i>
                                </button>
                                <button onClick={() => setPage(p => Math.min(Math.ceil(filtered.length / perPage) - 1, p + 1))} disabled={(page + 1) * perPage >= filtered.length} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: (page + 1) * perPage >= filtered.length ? 'not-allowed' : 'pointer', color: (page + 1) * perPage >= filtered.length ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px' }}>
                                    <i className="fa fa-angle-right"></i>
                                </button>
                                <button onClick={() => setPage(Math.ceil(filtered.length / perPage) - 1)} disabled={(page + 1) * perPage >= filtered.length} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: (page + 1) * perPage >= filtered.length ? 'not-allowed' : 'pointer', color: (page + 1) * perPage >= filtered.length ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '10px' }}>
                                    <i className="fa fa-angle-double-right"></i>
                                </button>
                            </div>
                        </div>
                    )}
                    </>
                    )}
                    </>
                )}
            </div>
        </div>
    );
}

// Mount
const el = document.getElementById('unassigned-suppliers-app');
if (el) {
    const root = createRoot(el);
    root.render(<AlertProvider><UnassignedSuppliersApp
        listApi={el.dataset.listApi}
        assignApi={el.dataset.assignApi}
        backUrl={el.dataset.backUrl}
        currency={el.dataset.currency}
        noHeader={el.dataset.noHeader}
        printUrl={el.dataset.printUrl}
        excelUrl={el.dataset.excelUrl}
    /></AlertProvider>);
}

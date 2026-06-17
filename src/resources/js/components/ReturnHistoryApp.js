import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';
import Select from 'react-select';
import DateRangePicker from './../hooks/DateRangePicker';
import { useWindowSize } from './../hooks/useWindowSize';
import DatePicker from 'react-datepicker';
import SpecTableLoading from './../elements/SpecTableLoading';
import SpecTableEmpty from './../elements/SpecTableEmpty';

export function ReturnHistoryApp({ type, returnsApi, entitiesApi, creditBalanceApi, creditBalanceAllApi, currency = '£', onBack, noCard = false, onTotal, onTotals, tabsBar, hideTable = false, onEntityChange, onDateChange, refreshKey = 0, onSearchChange, printUrl, excelUrl }) {
    const [data, setData] = useState([]);
    const [entities, setEntities] = useState([]);
    const [selectedEntity, setSelectedEntity] = useState(null);
    const _todayStr = new Date().toISOString().split('T')[0];
    const _monthAgoStr = (() => { const d = new Date(); d.setMonth(d.getMonth() - 1); return d.toISOString().split('T')[0]; })();
    const [fromDate, setFromDate] = useState(_todayStr);
    const [toDate, setToDate] = useState(_todayStr);
    const [summaryOpen, setSummaryOpen] = useState(false);
    const [searchText, setSearchText] = useState('');
    const clearFilters = () => {
        setSearchText('');
        setSelectedEntity(null);
        if (onEntityChange) onEntityChange(null);
        // Clear the date range (empty = fetch ALL records present in the DB)
        setFromDate(''); setToDate('');
        if (onDateChange) onDateChange('', '');
        setPage(0);
    };
    const [downloadingExcel, setDownloadingExcel] = useState(false);
    useEffect(() => { onSearchChange?.(searchText); }, [searchText]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(0);
    const [perPage, setPerPage] = useState(10);
    const [totals, setTotals] = useState({ all: 0, paid: 0, pending: 0 });
    const [creditBalance, setCreditBalance] = useState(null);
    const { width } = useWindowSize();
    const isCustomer = type === 'customer';
    const isDump = type === 'dump';
    const label = isCustomer ? 'Customer' : 'Supplier';
    const [filterOpen, setFilterOpen] = useState(false);
    const [pendingFrom, setPendingFrom] = useState(null);
    const [pendingTo, setPendingTo] = useState(null);
    const [pendingEntity, setPendingEntity] = useState(null);
    const [calendarOpen, setCalendarOpen] = useState(false);
    const [rangeStart, setRangeStart] = useState(null);
    const [rangeEnd, setRangeEnd] = useState(null);
    const [mMonthDd, setMMonthDd] = useState(false);
    const [mYearDd, setMYearDd] = useState(false);
    const toYMD = (d) => { const y=d.getFullYear(),m=String(d.getMonth()+1).padStart(2,'0'),dd=String(d.getDate()).padStart(2,'0'); return y+'-'+m+'-'+dd; };
    const fmtDisp = (v) => { if (!v) return ''; const MON=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; const [y,m,d]=String(v).split('-').map(Number); if(!y||!m||!d) return ''; return `${String(d).padStart(2,'0')} ${MON[m-1]} ${y}`; };
    const handleRangeChange = (dates) => { const [s,e]=dates; setRangeStart(s); setRangeEnd(e||null); if(s) setPendingFrom(toYMD(s)); if(e) setPendingTo(toYMD(e)); };
    const openCalendar = () => { setRangeStart(pendingFrom?new Date(pendingFrom+'T00:00:00'):null); setRangeEnd(pendingTo?new Date(pendingTo+'T00:00:00'):null); setCalendarOpen(true); setFilterOpen(false); };

    useEffect(() => {
        axios.get(entitiesApi).then(r => { if (r.data.success) setEntities(r.data.payload || []); });
    }, []);

    useEffect(() => { loadData(); setPage(0); }, [selectedEntity, fromDate, toDate, refreshKey]);

    useEffect(() => {
        if (selectedEntity && creditBalanceApi) {
            axios.get(creditBalanceApi + selectedEntity.value)
                .then(r => { if (r.data.success) setCreditBalance(r.data.payload); })
                .catch(() => setCreditBalance(null));
        } else if (!selectedEntity && creditBalanceAllApi) {
            axios.get(creditBalanceAllApi)
                .then(r => { if (r.data.success) setCreditBalance(r.data.payload); })
                .catch(() => setCreditBalance(null));
        } else {
            setCreditBalance(null);
        }
    }, [selectedEntity, creditBalanceApi, creditBalanceAllApi]);

    const loadData = async () => {
        setLoading(true);
        try {
            const params = isCustomer
                ? { customer_id: selectedEntity?.value || '', date: fromDate, to_date: toDate }
                : { supplier_id: selectedEntity?.value || '', date: fromDate || '2000-01-01', end_date: toDate };
            const r = await axios.post(returnsApi, params);
            setData(r.data.success ? (r.data.payload || []) : []);
        } catch (e) { setData([]); }
        finally { setLoading(false); }
    };

    const entityOptions = entities.map(e => ({ value: e.id, label: e.name }));
    const filtered = (searchText
        ? data.filter(r => (r.product_id || '').toLowerCase().includes(searchText.toLowerCase()) || (r[isCustomer ? 'customer' : 'supplier'] || '').toLowerCase().includes(searchText.toLowerCase()))
        : data).slice().sort((a, b) => new Date((b.date||'').replace(' ','T')) - new Date((a.date||'').replace(' ','T')));

    useEffect(() => {
        const allTotal = data.reduce((sum, r) => sum + Number(r.total || 0), 0);
        const paidTotal = data.filter(r => r.status === 'paid').reduce((sum, r) => sum + Number(r.total || 0), 0);
        const pendingTotal = data.filter(r => r.status !== 'paid').reduce((sum, r) => sum + Number(r.total || 0), 0);
        setTotals({ all: allTotal, paid: paidTotal, pending: pendingTotal });
        if (onTotal) onTotal(allTotal);
        if (onTotals) onTotals({ all: allTotal, paid: paidTotal, pending: pendingTotal, count: data.length });
    }, [data]);

    const selectStyles = {
        control: (b, s) => ({ ...b, minHeight: '38px', height: '38px', borderRadius: '9px', border: s.isFocused ? '1.5px solid #f97316' : '1.5px solid #e5e7eb', background: '#fff', boxShadow: s.isFocused ? '0 0 0 3px rgba(249,115,22,0.08)' : 'none', '&:hover': { borderColor: '#cbd5e1' }, cursor: 'pointer', fontSize: '13px', fontWeight: '600' }),
        valueContainer: b => ({ ...b, height: '38px', padding: '0 12px' }),
        indicatorsContainer: b => ({ ...b, height: '38px' }),
        indicatorSeparator: () => ({ display: 'none' }),
        singleValue: b => ({ ...b, fontSize: '13px', fontWeight: '600', color: '#1e293b' }),
        placeholder: b => ({ ...b, fontSize: '13px', color: '#94a3b8', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }),
        menu: b => ({ ...b, borderRadius: '10px', border: '1px solid #eaecf2', boxShadow: '0 8px 24px rgba(0,0,0,0.12)', marginTop: '4px', zIndex: 50 }),
        menuPortal: b => ({ ...b, zIndex: 9999 }),
        option: (b, s) => ({ ...b, fontSize: '13px', fontWeight: '500', padding: '9px 12px', cursor: 'pointer', backgroundColor: s.isSelected ? '#f97316' : s.isFocused ? '#fff7ed' : '#fff', color: s.isSelected ? '#fff' : s.isFocused ? '#f97316' : '#334155' }),
    };

    const isMobile = width <= 767;

    const matchPct = isDump
        ? (data.length > 0 ? Math.round(data.filter(r => r.status === 'paid').length / data.length * 100) : 0)
        : (() => { const total = creditBalance != null ? creditBalance.total_earned : totals.all; const paid = creditBalance != null ? creditBalance.total_used : totals.paid; return total > 0 ? Math.round(paid / total * 100) : 0; })();
    const statCards = isDump ? [
        { label: 'Total Credit', value: currency + ' ' + Number(totals.all).toFixed(2), icon: 'fa-credit-card', color: '#64748b', light: '#f8fafc' },
        { label: 'Total Items', value: data.length, icon: 'fa-cubes', color: '#3b82f6', light: '#eff6ff' },
    ] : [
        { label: 'Total Credit', value: currency + ' ' + Number(creditBalance != null ? creditBalance.total_earned : totals.all).toFixed(2), icon: 'fa-credit-card', color: '#64748b', light: '#f8fafc' },
        { label: 'Paid', value: currency + ' ' + Number(creditBalance != null ? creditBalance.total_used : totals.paid).toFixed(2), icon: 'fa-check-circle', color: '#16a34a', light: '#f0fdf4' },
        { label: 'Pending', value: currency + ' ' + Number(creditBalance != null ? creditBalance.available : totals.pending).toFixed(2), icon: 'fa-clock-o', color: 'rgb(234, 88, 12)', light: '#fff7ed' },
    ];

    return (
        <div>
        <div style={noCard ? {overflow:'visible'} : { background: '#fff', borderRadius: isMobile ? '14px' : '16px', border: '1px solid #eaecf2', boxShadow: '0 1px 4px rgba(0,0,0,0.04)', overflow: 'visible' }}>
            {/* Filters */}
            <div style={{ padding: isMobile ? '0' : '18px 20px 16px', background: '#fff', borderBottom: isMobile ? 'none' : '1px solid #f1f5f9', overflow: 'visible' }}>
                {/* Summary Cards */}
                {isMobile ? (<>
                    {/* Collapsed bar */}
                    <div onClick={()=>setSummaryOpen(v=>!v)} style={{borderRadius: summaryOpen?'16px 16px 0 0':'16px',border:'1px solid #eaecf2',borderBottom: summaryOpen?'1px solid #f0f0f0':'1px solid #eaecf2',background:'#fff',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',padding:'12px 14px',display:'flex',alignItems:'center',justifyContent:'space-between',cursor:'pointer',marginBottom: summaryOpen?0:'14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'6px'}}>
                            <i className="fa fa-bar-chart" style={{fontSize:'11px',color:'#F27420'}}/>
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
                                    <span style={{fontSize:'9px',color:'#9ca3af',fontWeight:'700',letterSpacing:'0.7px',textTransform:'uppercase'}}>{isDump?'Review Rate':'Match Rate'}</span>
                                    <span style={{fontSize:'10px',color:'#9ca3af',fontWeight:'600'}}>{matchPct}%</span>
                                </div>
                                <div style={{height:'3px',borderRadius:'99px',background:'#e5e7eb',overflow:'hidden'}}>
                                    <div style={{height:'100%',width:matchPct+'%',borderRadius:'99px',background:'#F27420'}}/>
                                </div>
                            </div>
                        </div>
                    )}
                </>) : (
                    /* Desktop: grid */
                    <div style={{ display: 'grid', gridTemplateColumns: isDump ? 'repeat(2,1fr)' : 'repeat(3,1fr)', gap: '10px', marginBottom: '14px' }}>
                        {statCards.map(({ label: lbl, value, icon, color, light }) => (
                            <div key={lbl} style={{ display: 'flex', alignItems: 'center', gap: '13px', padding: '16px 18px', borderRadius: '12px', background: '#fff', border: '1px solid #edf2f7', boxShadow: '0 1px 4px rgba(0,0,0,0.05)' }}>
                                <div style={{ width: '42px', height: '42px', borderRadius: '50%', background: light, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                    <i className={'fa ' + icon} style={{ color, fontSize: '16px' }} />
                                </div>
                                <div>
                                    <div style={{ fontSize: '22px', fontWeight: '800', color: '#1a2332', lineHeight: 1 }}>{value}</div>
                                    <div style={{ fontSize: '10px', fontWeight: '600', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.5px', marginTop: '4px' }}>{lbl}</div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                {/* Desktop filter bar */}
                {!isMobile && (
                    <div style={{ display: 'flex', gap: '14px', alignItems: 'center' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '9px', padding: '0 14px', height: '40px', border: '1.5px solid #e8edf2', borderRadius: '10px', background: '#fff', flex: '1 1 0', minWidth: 0 }}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" placeholder="Search product..." value={searchText} onChange={e => setSearchText(e.target.value)}
                                style={{ flex: 1, height: '100%', border: 'none', outline: 'none', fontSize: '13px', color: '#374151', background: 'transparent', minWidth: 0 }} />
                            {searchText && (
                                <button type="button" onClick={() => setSearchText('')} style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '2px', display: 'flex', alignItems: 'center', flexShrink: 0 }}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            )}
                        </div>
                        <div style={{ flex: '1 1 0', height: '40px', border: '1.5px solid #e8edf2', borderRadius: '10px', background: '#fff', overflow: 'hidden', minWidth: 0 }}>
                            <Select styles={{
                                control: b => ({ ...b, border: 'none', borderRadius: '10px', minHeight: '38px', height: '38px', boxShadow: 'none', background: 'transparent', cursor: 'pointer' }),
                                valueContainer: b => ({ ...b, height: '38px', padding: '0 10px' }),
                                indicatorsContainer: b => ({ ...b, height: '38px' }),
                                indicatorSeparator: () => ({ display: 'none' }),
                                dropdownIndicator: b => ({ ...b, padding: '0 8px 0 0', color: '#cbd5e1', '&:hover': { color: '#F27420' } }),
                                clearIndicator: b => ({ ...b, padding: '0 2px', color: '#cbd5e1', '&:hover': { color: '#F27420' } }),
                                singleValue: b => ({ ...b, fontSize: '13px', fontWeight: '600', color: '#1e293b' }),
                                placeholder: b => ({ ...b, fontSize: '13px', color: '#94a3b8' }),
                                menu: b => ({ ...b, borderRadius: '10px', border: '1px solid #eaecf2', boxShadow: '0 8px 24px rgba(0,0,0,0.12)', zIndex: 9999 }),
                                menuPortal: b => ({ ...b, zIndex: 9999 }),
                                option: (b, s) => ({ ...b, fontSize: '13px', fontWeight: '500', padding: '9px 12px', cursor: 'pointer', backgroundColor: s.isSelected ? '#F27420' : s.isFocused ? '#fff7ed' : '#fff', color: s.isSelected ? '#fff' : s.isFocused ? '#F27420' : '#334155' }),
                            }}
                                options={entityOptions} isClearable isSearchable value={selectedEntity} onChange={v => { setSelectedEntity(v); if (onEntityChange) onEntityChange(v); }}
                                placeholder={`Select ${label}`} menuPortalTarget={document.body} menuShouldScrollIntoView={false} />
                        </div>
                        <div style={{ flex: '1 1 0', minWidth: 0 }}>
                            <DateRangePicker fromDate={fromDate} toDate={toDate} onFromChange={v => { setFromDate(v); if(onDateChange) onDateChange(v, toDate); }} onToChange={v => { setToDate(v); if(onDateChange) onDateChange(fromDate, v); }} width={width} />
                        </div>
                        <div style={{display:'flex',gap:'10px',flexShrink:0,paddingLeft:'4px'}}>
                        {/* Print */}
                        {printUrl && (
                            <button type="button" className="icon-tip" data-tip="Print" onClick={() => {
                                const entityId = selectedEntity?.value || '';
                                const params = new URLSearchParams({ entity_id: entityId, date: fromDate || '', to_date: toDate || '', end_date: toDate || '' });
                                window.open(printUrl + '?' + params.toString(), '_blank');
                            }}
                                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid #e8edf2',background:'#fff',color:'#64748b',cursor:'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                                onMouseEnter={e=>{e.currentTarget.style.borderColor='#F27420';e.currentTarget.style.color='#F27420';e.currentTarget.style.background='#fff7ed';}}
                                onMouseLeave={e=>{e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}
                            >
                                <i className="fa fa-print" style={{fontSize:'14px'}}></i>
                            </button>
                        )}
                        {/* Download Excel */}
                        {excelUrl && (
                            <button type="button" className="icon-tip" data-tip="Download Excel" disabled={downloadingExcel} onClick={() => {
                                if (downloadingExcel) return;
                                setDownloadingExcel(true);
                                const entityId = selectedEntity?.value || '';
                                const isCust = type === 'customer';
                                const params = new URLSearchParams(isCust
                                    ? { customer_id: entityId, date: fromDate || '', to_date: toDate || '' }
                                    : { supplier_id: entityId, date: fromDate || '', end_date: toDate || '' });
                                window.location.href = excelUrl + '?' + params.toString();
                                setTimeout(() => setDownloadingExcel(false), 2500);
                            }}
                                style={{width:'40px',height:'40px',borderRadius:'10px',border:'1.5px solid '+(downloadingExcel?'#F27420':'#e8edf2'),background:downloadingExcel?'#fff7ed':'#fff',color:downloadingExcel?'#F27420':'#64748b',cursor:downloadingExcel?'default':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',transition:'all 0.15s',flexShrink:0}}
                                onMouseEnter={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='#F27420';e.currentTarget.style.color='#F27420';e.currentTarget.style.background='#fff7ed';}}}
                                onMouseLeave={e=>{if(!downloadingExcel){e.currentTarget.style.borderColor='#e8edf2';e.currentTarget.style.color='#64748b';e.currentTarget.style.background='#fff';}}}
                            >
                                <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-download"} style={{fontSize:'14px'}}></i>
                            </button>
                        )}
                        </div>
                    </div>
                )}
            </div>

            {/* Mobile search + filter bar — same as StockCheck/Closing/Unassigned */}
            {isMobile && (
                <div style={{ margin: '0 0 14px', display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: '8px', height: '44px', border: '1.5px solid #e8edf2', borderRadius: '12px', background: '#fff', padding: '0 12px', minWidth: 0 }}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" placeholder="Search product..." value={searchText} onChange={e => { setSearchText(e.target.value); setPage(0); }}
                            style={{ flex: 1, border: 'none', outline: 'none', fontSize: '12px', color: '#374151', background: 'transparent', minWidth: 0 }} />
                        {searchText && (
                            <button type="button" onClick={() => { setSearchText(''); setPage(0); }} style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '2px', display: 'flex', alignItems: 'center', flexShrink: 0 }}>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        )}
                    </div>
                    {/* Filter button — Sales-style solid orange */}
                    <button type="button" onClick={() => { setPendingFrom(fromDate||null); setPendingTo(toDate||null); setPendingEntity(selectedEntity||null); setFilterOpen(v => !v); }}
                        style={{ flexShrink:0, height:'44px', width:'44px', borderRadius:'12px', border:'none', background:'rgb(234, 88, 12)', boxShadow:'0 2px 6px rgba(234,88,12,0.3)', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', position:'relative', outline:'none' }}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        {(fromDate||toDate||selectedEntity) && <span style={{ position:'absolute', top:'4px', right:'4px', width:'7px', height:'7px', borderRadius:'50%', background:'#fff', border:'1.5px solid rgb(234, 88, 12)' }}/>}
                    </button>
                </div>
            )}

            {/* Mobile action buttons — Print / Excel (below the search bar) — same as Stock Check */}
            {isMobile && (
                <div style={{margin:'0 0 14px',display:'flex',gap:'10px'}}>
                    {printUrl && (
                    <button type="button" onClick={() => {
                        const entityId = selectedEntity?.value || '';
                        const params = new URLSearchParams({ entity_id: entityId, date: fromDate || '', to_date: toDate || '', end_date: toDate || '' });
                        window.open(printUrl + '?' + params.toString(), '_blank');
                    }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
                        <i className="fa fa-print" style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>Print
                    </button>
                    )}
                    {excelUrl && (
                    <button type="button" disabled={downloadingExcel} onClick={() => {
                        if (downloadingExcel) return;
                        setDownloadingExcel(true);
                        const entityId = selectedEntity?.value || '';
                        const isCust = type === 'customer';
                        const params = new URLSearchParams(isCust
                            ? { customer_id: entityId, date: fromDate || '', to_date: toDate || '' }
                            : { supplier_id: entityId, date: fromDate || '', end_date: toDate || '' });
                        window.location.href = excelUrl + '?' + params.toString();
                        setTimeout(() => setDownloadingExcel(false), 2500);
                    }} style={{flex:1,height:'44px',borderRadius:'12px',border:'1px solid #eaecf2',background:'#fff',color:'#374151',fontSize:'13px',fontWeight:'700',cursor:downloadingExcel?'default':'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',outline:'none',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}}>
                        <i className={downloadingExcel ? "fa fa-spinner fa-spin" : "fa fa-file-excel-o"} style={{fontSize:'14px',color:'rgb(234, 88, 12)'}}></i>{downloadingExcel ? 'Preparing…' : 'Excel'}
                    </button>
                    )}
                </div>
            )}

            {/* Mobile filter bottom sheet */}
            {isMobile && filterOpen && (
                <>
                    <div onMouseDown={()=>setFilterOpen(false)} onTouchStart={()=>setFilterOpen(false)}
                        style={{position:'fixed',inset:0,zIndex:998,background:'rgba(0,0,0,0.35)'}}/>
                    <div className="sc-filter-sheet" onMouseDown={e=>e.stopPropagation()} onTouchStart={e=>e.stopPropagation()}
                        style={{position:'fixed',bottom:0,left:0,right:0,zIndex:999,background:'#fff',borderRadius:'20px 20px 0 0',animation:'scSlideUp 0.25s ease',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'92vh',overflowY:'auto'}}>
                        <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}>
                            <div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/>
                        </div>
                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'8px 18px 12px'}}>
                            <div style={{display:'flex',alignItems:'center',gap:'7px'}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                <span style={{fontSize:'14px',fontWeight:'700',color:'#111827'}}>Filters</span>
                            </div>
                            <button type="button" onClick={()=>setFilterOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'8px',width:'28px',height:'28px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div style={{padding:'0 18px 18px',display:'flex',flexDirection:'column',gap:'16px'}}>
                            {/* Entity select */}
                            <div>
                                <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>{label}</div>
                                <Select styles={{
                                    control: (b,s) => ({ ...b, minHeight:'44px', height:'44px', borderRadius:'10px', border: s.isFocused?'1.5px solid #F27420':'1.5px solid #e5e7eb', boxShadow:'none', background:'#fff', cursor:'pointer' }),
                                    valueContainer: b => ({ ...b, height:'44px', padding:'0 12px' }),
                                    indicatorsContainer: b => ({ ...b, height:'44px' }),
                                    indicatorSeparator: () => ({ display:'none' }),
                                    dropdownIndicator: b => ({ ...b, padding:'0 8px 0 0', color:'#cbd5e1' }),
                                    clearIndicator: b => ({ ...b, padding:'0 4px', color:'#cbd5e1' }),
                                    singleValue: b => ({ ...b, fontSize:'13px', fontWeight:'600', color:'#1e293b' }),
                                    placeholder: b => ({ ...b, fontSize:'13px', color:'#94a3b8' }),
                                    menu: b => ({ ...b, borderRadius:'12px', border:'1px solid #eaecf2', boxShadow:'0 8px 24px rgba(0,0,0,0.12)', zIndex:9999 }),
                                    menuPortal: b => ({ ...b, zIndex:9999 }),
                                    option: (b,s) => ({ ...b, fontSize:'13px', fontWeight:'500', padding:'10px 14px', cursor:'pointer', backgroundColor: s.isSelected?'#F27420':s.isFocused?'#fff7ed':'#fff', color: s.isSelected?'#fff':s.isFocused?'#F27420':'#334155' }),
                                }} options={entityOptions} isClearable isSearchable value={pendingEntity} onChange={v => setPendingEntity(v)} placeholder={`Select ${label}`} menuPortalTarget={document.body} menuShouldScrollIntoView={false} />
                            </div>
                            {/* Date range — single button opens calendar */}
                            <div>
                                <div style={{fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.6px',textTransform:'uppercase',marginBottom:'8px'}}>Date Range</div>
                                <button type="button" onClick={openCalendar}
                                    style={{width:'100%',height:'44px',borderRadius:'10px',border:'1.5px solid #e2e8f0',background:'#fff',display:'flex',alignItems:'center',padding:'0 12px',gap:'8px',cursor:'pointer',outline:'none'}}>
                                    <i className="fa fa-calendar" style={{fontSize:'13px',color:'#f97316'}}></i>
                                    <span style={{fontSize:'13px',fontWeight:'600',color:pendingFrom&&pendingTo?'#1e293b':'#9ca3af',flex:1,textAlign:'left'}}>{pendingFrom&&pendingTo ? fmtDisp(pendingFrom)+' — '+fmtDisp(pendingTo) : 'Select date range'}</span>
                                    <i className="fa fa-chevron-right" style={{fontSize:'10px',color:'#d1d5db'}}></i>
                                </button>
                            </div>
                            {/* Action buttons */}
                            <div style={{display:'grid',gridTemplateColumns:'1fr 2fr',gap:'10px',paddingTop:'4px'}}>
                                <button type="button" onClick={()=>{ setPendingFrom(null); setPendingTo(null); setPendingEntity(null); }}
                                    style={{height:'44px',borderRadius:'12px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#64748b',fontSize:'13px',fontWeight:'600',cursor:'pointer',outline:'none'}}>
                                    Clear
                                </button>
                                <button type="button" onClick={()=>{ let f=pendingFrom||_monthAgoStr, t=pendingTo||_todayStr; if(f&&t&&f>t){[f,t]=[t,f];} setFromDate(f); setToDate(t); setSelectedEntity(pendingEntity); if(onEntityChange) onEntityChange(pendingEntity); if(onDateChange) onDateChange(f,t); setFilterOpen(false); }}
                                    style={{height:'44px',borderRadius:'12px',border:'none',background:'#F27420',color:'#fff',fontSize:'13px',fontWeight:'700',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',gap:'6px',outline:'none'}}>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </>
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
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingFrom?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingFrom?fmtDisp(pendingFrom):'Select'}</div>
                            </div>
                            <div style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 10px rgba(234,88,12,0.35)'}}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </div>
                            <div style={{flex:1,background:'#fff',border:'2px solid '+(pendingTo?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:pendingTo?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>To</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:pendingTo?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{pendingTo?fmtDisp(pendingTo):'Select'}</div>
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
/* range band behind start/end (half-inset like reference) */
.sp-range .react-datepicker__day--range-start:not(.react-datepicker__day--range-end)::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;right:0;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--range-end:not(.react-datepicker__day--range-start)::after{content:'';position:absolute;top:4px;bottom:4px;left:0;right:50%;background:#fff7f0;z-index:-2}
/* orange circle for selected/start/end */
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
                                        <button type="button" onClick={()=>setMMonthDd(v=>!v)} style={{display:'inline-flex',alignItems:'center',gap:'7px',background:'transparent',border:'none',outline:'none',cursor:'pointer',fontSize:'16px',fontWeight:'800',color:'#0f172a',padding:'4px 8px'}}>
                                            {mnthsFull[date.getMonth()]} {date.getFullYear()}
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        {mMonthDd&&(<div className="sp-dd-list" style={{minWidth:'200px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'4px',padding:'8px'}}>
                                            <div style={{gridColumn:'1 / -1',display:'flex',alignItems:'center',justifyContent:'space-between',padding:'2px 4px 6px'}}>
                                                <span style={{fontSize:'11px',fontWeight:'700',color:'#94a3b8'}}>MONTH</span>
                                                <select value={date.getFullYear()} onChange={e=>changeYear(Number(e.target.value))} style={{border:'1.5px solid #e5e7eb',borderRadius:'7px',fontSize:'12px',fontWeight:'700',color:'#0f172a',padding:'3px 6px',outline:'none',cursor:'pointer'}}>{yrs.map(y=><option key={y} value={y}>{y}</option>)}</select>
                                            </div>
                                            {mnths.map((m,i)=><div key={m} className={'sp-dd-item'+(date.getMonth()===i?' active':'')} onClick={()=>{changeMonth(i);setMMonthDd(false);}}>{m}</div>)}
                                        </div>)}
                                    </div>
                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...nb,opacity:nextMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                </div>);
                            }}
                        />
                    </div>
                    <div style={{display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',padding:'8px 18px 16px'}}>
                        <button type="button" onClick={()=>{setRangeStart(null);setRangeEnd(null);setPendingFrom(null);setPendingTo(null);setCalendarOpen(false);setFilterOpen(true);}} style={{height:'52px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'15px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px'}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel
                        </button>
                        <button type="button" onClick={()=>{setCalendarOpen(false);setFilterOpen(true);}} disabled={!pendingFrom||!pendingTo} style={{height:'52px',borderRadius:'14px',border:'none',background:(!pendingFrom||!pendingTo)?'#e2e8f0':'rgb(234, 88, 12)',color:(!pendingFrom||!pendingTo)?'#94a3b8':'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:(!pendingFrom||!pendingTo)?'default':'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:(!pendingFrom||!pendingTo)?'none':'0 6px 16px rgba(234,88,12,0.35)'}}>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply
                        </button>
                    </div>
                </div>
            </>)}

            {/* On mobile, when there are no rows, group the tabs and the empty/loading state into ONE card */}
            {isMobile && tabsBar && !hideTable && filtered.length === 0 ? (
                <div style={{padding:'10px 0'}}>
                    <div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
                        {tabsBar}
                        {loading ? <SpecTableLoading label="Loading…" /> : <SpecTableEmpty onClear={clearFilters} />}
                    </div>
                </div>
            ) : (<>
            {/* On mobile, when the table is hidden (i.e. the consumer renders its own content,
                like the Add Return Credit tab), let the consumer also render the tabs inside its
                own card. Otherwise the tabs float alone above the consumer's card. */}
            {tabsBar && !(isMobile && hideTable) && tabsBar}
            {!hideTable && <>
            {isMobile ? (
                /* Mobile: card list — edge-to-edge like Stock Check */
                <div style={{ padding: '10px 0' }}>
                    {loading ? (
                        <SpecTableLoading label="Loading…" />
                    ) : filtered.length === 0 ? (
                        <div style={{background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'}}>
                            <SpecTableEmpty onClear={clearFilters} />
                        </div>
                    ) : filtered.slice(page * perPage, (page + 1) * perPage).map((r, i) => (
                        <div key={r.id || i} style={{ display: 'flex', marginBottom: '10px', borderRadius: '14px', border: '1px solid #eaecf2', overflow: 'hidden', background: '#fff', boxShadow: '0 1px 4px rgba(0,0,0,0.06)' }}>
                            <div style={{ width: '4px', flexShrink: 0, background: 'linear-gradient(180deg,#f97316,#ea580c)' }} />
                            <div style={{ flex: 1, padding: '12px 12px 10px', minWidth: 0 }}>
                                {/* Top: product name + total */}
                                <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '8px', marginBottom: '4px' }}>
                                    <div style={{ minWidth: 0 }}>
                                        <div style={{ fontSize: '11px', color: '#f97316', fontWeight: '700', marginBottom: '2px' }}>
                                            {r.invoice_id ? `#${r.invoice_id}` : ''}
                                            {r.invoice_id && r.date ? ' · ' : ''}
                                            {r.date ? new Date((r.date||'').replace(' ','T')).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : ''}
                                        </div>
                                        <div style={{ fontWeight: '700', color: '#1e293b', fontSize: '13px', lineHeight: 1.3, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{r.product_id || '—'}</div>
                                        <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '600', marginTop: '1px' }}>{r[isCustomer ? 'customer' : 'supplier'] || ''}</div>
                                    </div>
                                    <div style={{ fontSize: '15px', fontWeight: '800', color: '#dc2626', flexShrink: 0 }}>{currency} {Number(r.total || 0).toFixed(2)}</div>
                                </div>
                                {/* Badges: qty, price each */}
                                <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginTop: '6px' }}>
                                    <span style={{ fontSize: '11px', fontWeight: '600', color: '#374151', background: '#f8fafc', border: '1px solid #e5e7eb', borderRadius: '6px', padding: '2px 8px' }}>Qty: {r.quantity}</span>
                                    <span style={{ fontSize: '11px', fontWeight: '600', color: '#374151', background: '#f8fafc', border: '1px solid #e5e7eb', borderRadius: '6px', padding: '2px 8px' }}>{currency} {Number(r.price || 0).toFixed(2)} each</span>
                                    {!isDump && r.status && (
                                        <span style={{ fontSize: '11px', fontWeight: '700', borderRadius: '6px', padding: '2px 8px',
                                            background: r.status === 'paid' ? '#f0fdf4' : '#fff7ed',
                                            color: r.status === 'paid' ? '#16a34a' : '#ea580c',
                                            border: '1px solid ' + (r.status === 'paid' ? '#86efac' : '#fed7aa'),
                                        }}>{r.status === 'paid' ? 'Paid' : 'Pending'}</span>
                                    )}
                                </div>
                                {/* Note / reason */}
                                {r.note && <div style={{ marginTop: '6px', fontSize: '11px', color: '#94a3b8', fontStyle: 'italic' }}>{r.note}</div>}
                            </div>
                        </div>
                    ))}
                    {/* Mobile pagination */}
                    {filtered.length > 0 && (
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '8px 0 4px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                <span style={{ fontSize: '11px', color: '#64748b' }}>Rows:</span>
                                <select value={perPage} onChange={e => { setPerPage(Number(e.target.value)); setPage(0); }}
                                    style={{ height: '26px', border: '1px solid #e2e8f0', borderRadius: '6px', fontSize: '11px', fontWeight: '600', color: '#374151', background: '#fff', padding: '0 4px', outline: 'none' }}>
                                    {[5,10,20,50].map(n => <option key={n} value={n}>{n}</option>)}
                                </select>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                                <span style={{ fontSize: '11px', color: '#64748b' }}>{page * perPage + 1}–{Math.min((page + 1) * perPage, filtered.length)} of {filtered.length}</span>
                                <button disabled={page === 0} onClick={() => setPage(p => p - 1)} style={{ width: '26px', height: '26px', borderRadius: '6px', border: '1px solid #e2e8f0', background: page === 0 ? '#f8fafc' : '#fff', color: page === 0 ? '#cbd5e1' : '#374151', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', cursor: page === 0 ? 'default' : 'pointer', outline: 'none' }}>
                                    <i className="fa fa-chevron-left" style={{ fontSize: '9px' }}></i>
                                </button>
                                <button disabled={page >= Math.ceil(filtered.length / perPage) - 1} onClick={() => setPage(p => p + 1)} style={{ width: '26px', height: '26px', borderRadius: '6px', border: '1px solid #e2e8f0', background: page >= Math.ceil(filtered.length / perPage) - 1 ? '#f8fafc' : '#fff', color: page >= Math.ceil(filtered.length / perPage) - 1 ? '#cbd5e1' : '#374151', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', cursor: page >= Math.ceil(filtered.length / perPage) - 1 ? 'default' : 'pointer', outline: 'none' }}>
                                    <i className="fa fa-chevron-right" style={{ fontSize: '9px' }}></i>
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            ) : (<>
            <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '13px' }}>
                    <thead>
                        <tr style={{ background: '#fafbfc' }}>
                            {['#', 'Date', 'Product', label, 'Note', 'Qty', 'Price', 'Total'].map(h => (
                                <th key={h} style={{ padding: '10px 14px', fontSize: '11px', fontWeight: '700', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px', borderBottom: '2px solid #f1f5f9', textAlign: ['Qty', 'Price', 'Total'].includes(h) ? 'right' : 'left' }}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={8} style={{ padding: 0 }}><SpecTableLoading label="Loading…" /></td></tr>
                        ) : filtered.length === 0 ? (
                            <tr><td colSpan={8} style={{padding:0}}><SpecTableEmpty onClear={clearFilters} /></td></tr>
                        ) : filtered.slice(page * perPage, (page + 1) * perPage).map((r, i) => (
                            <tr key={r.id || i} style={{ borderBottom: '1px solid #f8fafc' }} onMouseEnter={e => e.currentTarget.style.background = '#fafbfc'} onMouseLeave={e => e.currentTarget.style.background = ''}>
                                <td style={{ padding: '12px 14px', color: '#94a3b8', fontSize: '12px', fontWeight: '600' }}>{page * perPage + i + 1}</td>
                                <td style={{ padding: '12px 14px', color: '#64748b', fontSize: '12px', whiteSpace: 'nowrap' }}>{r.date ? new Date((r.date||'').replace(' ','T')).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'}</td>
                                <td style={{ padding: '12px 14px', fontSize: '13px', fontWeight: '600', color: '#1e293b' }}>{r.product_id || '—'}</td>
                                <td style={{ padding: '12px 14px', fontSize: '13px', color: '#64748b' }}>{r[isCustomer ? 'customer' : 'supplier'] || '—'}</td>
                                <td style={{ padding: '12px 14px', color: '#64748b', fontStyle: 'italic', fontSize: '12px' }}>{r.note || '—'}</td>
                                <td style={{ padding: '12px 14px', fontSize: '13px', textAlign: 'right', fontWeight: '700' }}>{r.quantity}</td>
                                <td style={{ padding: '12px 14px', fontSize: '13px', textAlign: 'right' }}>{currency} {Number(r.price || 0).toFixed(2)}</td>
                                <td style={{ padding: '12px 14px', fontSize: '13px', textAlign: 'right', fontWeight: '700', color: '#dc2626' }}>{currency} {Number(r.total || 0).toFixed(2)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
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
                        <span style={{ fontSize: '12px', color: '#64748b', marginRight: '8px' }}>{page * perPage + 1}–{Math.min((page + 1) * perPage, filtered.length)} of {filtered.length}</span>
                        <button onClick={() => setPage(0)} disabled={page === 0} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: page === 0 ? 'not-allowed' : 'pointer', color: page === 0 ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <i className="fa fa-angle-double-left"></i>
                        </button>
                        <button onClick={() => setPage(p => Math.max(0, p - 1))} disabled={page === 0} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: page === 0 ? 'not-allowed' : 'pointer', color: page === 0 ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <i className="fa fa-angle-left"></i>
                        </button>
                        <button onClick={() => setPage(p => Math.min(Math.ceil(filtered.length / perPage) - 1, p + 1))} disabled={(page + 1) * perPage >= filtered.length} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: (page + 1) * perPage >= filtered.length ? 'not-allowed' : 'pointer', color: (page + 1) * perPage >= filtered.length ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <i className="fa fa-angle-right"></i>
                        </button>
                        <button onClick={() => setPage(Math.ceil(filtered.length / perPage) - 1)} disabled={(page + 1) * perPage >= filtered.length} style={{ width: '28px', height: '28px', borderRadius: '6px', border: '1px solid #e2e8f0', background: '#fff', cursor: (page + 1) * perPage >= filtered.length ? 'not-allowed' : 'pointer', color: (page + 1) * perPage >= filtered.length ? '#d1d5db' : '#64748b', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                            <i className="fa fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            )}
            </>)}
            </>}
            </>)}
        </div>
        </div>
    );
}

// Mount Supplier Return History
const sEl = document.getElementById('supplier-return-history-app');
if (sEl) {
    createRoot(sEl).render(<ReturnHistoryApp type="supplier" returnsApi={sEl.dataset.returnsApi} entitiesApi={sEl.dataset.suppliersApi} currency={sEl.dataset.currency} creditBalanceApi={sEl.dataset.creditBalanceApi} creditBalanceAllApi={sEl.dataset.creditBalanceAllApi} />);
}

// Mount Customer Return History
const cEl = document.getElementById('customer-return-history-app');
if (cEl) {
    createRoot(cEl).render(<ReturnHistoryApp type="customer" returnsApi={cEl.dataset.returnsApi} entitiesApi={cEl.dataset.customersApi} currency={cEl.dataset.currency} creditBalanceApi={cEl.dataset.creditBalanceApi} creditBalanceAllApi={cEl.dataset.creditBalanceAllApi} />);
}

// Mount Dump History
const dEl = document.getElementById('dump-return-history-app');
if (dEl) {
    createRoot(dEl).render(<ReturnHistoryApp type="dump" returnsApi={dEl.dataset.returnsApi} entitiesApi={dEl.dataset.suppliersApi} currency={dEl.dataset.currency} />);
}

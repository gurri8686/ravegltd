import React, { useState, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

// Custom styled dropdown — replaces native <select> for month/year
function DrpDropdown({ value, options, onChange }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const listRef = useRef(null);
    const selectedLabel = (options.find(o => o.value === value) || {}).label || value;

    useEffect(() => {
        if (!open) return;
        const handle = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, [open]);

    // Scroll selected item into view when dropdown opens
    useEffect(() => {
        if (open && listRef.current) {
            const active = listRef.current.querySelector('[data-active="true"]');
            if (active) active.scrollIntoView({ block: 'center' });
        }
    }, [open]);

    return (
        <div ref={ref} style={{position:'relative',display:'inline-block'}}>
            <button type="button" onMouseDown={e=>{e.stopPropagation();setOpen(v=>!v);}} style={{
                border:'1.5px solid #fed7aa',borderRadius:'6px',background:'#fff7f0',
                color:'#1e293b',fontSize:'12px',fontWeight:'700',padding:'3px 20px 3px 8px',
                cursor:'pointer',outline:'none',display:'flex',alignItems:'center',gap:'4px',
                whiteSpace:'nowrap',position:'relative',lineHeight:'1.4',
            }}>
                {selectedLabel}
                <svg style={{position:'absolute',right:'5px',top:'50%',transform:'translateY(-50%)'}} width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            {open && (
                <div ref={listRef} style={{
                    position:'absolute',top:'calc(100% + 4px)',left:'50%',transform:'translateX(-50%)',
                    background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'8px',
                    boxShadow:'0 8px 24px rgba(0,0,0,0.12)',zIndex:99999,
                    maxHeight:'180px',overflowY:'auto',minWidth:'70px',padding:'4px',
                }}>
                    {options.map(opt => (
                        <div key={opt.value} data-active={opt.value === value ? 'true' : 'false'}
                            onMouseDown={e=>{e.stopPropagation();onChange(opt.value);setOpen(false);}}
                            style={{
                                padding:'5px 10px',fontSize:'12px',fontWeight: opt.value===value ? '700' : '500',
                                borderRadius:'5px',cursor:'pointer',textAlign:'center',
                                background: opt.value===value ? 'rgb(234, 88, 12)' : 'transparent',
                                color: opt.value===value ? '#fff' : '#374151',
                                transition:'background 0.1s',
                            }}
                            onMouseEnter={e=>{ if(opt.value!==value){ e.currentTarget.style.background='#fff5ed'; e.currentTarget.style.color='rgb(234, 88, 12)'; }}}
                            onMouseLeave={e=>{ if(opt.value!==value){ e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#374151'; }}}
                        >{opt.label}</div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function DateRangePicker({ fromDate, toDate, onFromChange, onToChange, width, variant }) {
    // Spec-style trigger button is now the consistent design across every page.
    const isSpec = true;
    const [isOpen, setIsOpen] = useState(false);
    // 'range' = pick a start + end; 'single' = pick one day (stored as from===to).
    // Default to single when the current selection is already a single day.
    const [mode, setMode] = useState(fromDate && toDate && fromDate === toDate ? 'single' : 'range');
    const [modeDdOpen, setModeDdOpen] = useState(false);
    const modeLabel = mode === 'single' ? 'Single Date' : 'Date Range';
    const [isMobile, setIsMobile] = useState(window.innerWidth <= 1024);
    const [mMonthDd, setMMonthDd] = useState(false);
    const [mYearDd, setMYearDd] = useState(false);
    const ref = useRef(null);
    const btnRef = useRef(null);
    const dropdownRef = useRef(null);
    const openToDateRef = useRef(fromDate ? new Date(fromDate + 'T00:00:00') : new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1));
    const [dropdownPos, setDropdownPos] = useState({top:0, right:0});
    // Local pick state: From is shown on open, To is only set after user explicitly selects it
    const [pickState, setPickState] = useState({ start: null, end: null });
    // Mobile sheet: staged (pending) selection, committed only on Apply
    const [mPendStart, setMPendStart] = useState(null);
    const [mPendEnd, setMPendEnd] = useState(null);
    const [sMonthDd, setSMonthDd] = useState(false);
    const isCompact = width != null ? width < 1024 : false;
    const startDate = fromDate ? new Date(fromDate + 'T00:00:00') : null;
    const endDate = toDate ? new Date(toDate + 'T00:00:00') : null;

    // When calendar opens: show current range so user can see selection, and any click resets to new FROM
    useEffect(() => {
        if (isOpen) {
            const s = fromDate ? new Date(fromDate + 'T00:00:00') : null;
            const e = toDate ? new Date(toDate + 'T00:00:00') : null;
            setPickState({ start: s, end: e });
            setMPendStart(s); setMPendEnd(e); setSMonthDd(false);
            if (s) openToDateRef.current = s;
        }
    }, [isOpen]);

    useEffect(() => {
        const handle = () => setIsMobile(window.innerWidth <= 1024);
        window.addEventListener('resize', handle);
        return () => window.removeEventListener('resize', handle);
    }, []);

    const MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    // Parse a "YYYY-MM-DD" string by its own parts — never via new Date(str), which
    // interprets the string as UTC and shifts the displayed day in non-UTC timezones.
    const parseYMD = (s) => {
        if (!s) return null;
        const [y, m, d] = String(s).split('-').map(Number);
        if (!y || !m || !d) return null;
        return { y, m, d };
    };
    const formatDisplay = (date) => {
        const p = parseYMD(date);
        if (!p) return '—';
        return `${String(p.d).padStart(2,'0')} ${MON[p.m-1]} ${p.y}`;
    };

    // Compact range for the labeled-card trigger: "01-31 May" (same month),
    // "01 May - 03 Jun" (same year), else full with years.
    const compactRange = (f, t) => {
        const fd = parseYMD(f), td = parseYMD(t);
        if (!fd || !td) return '';
        const day = (p) => String(p.d).padStart(2, '0');
        const sameYear = fd.y === td.y;
        if (sameYear && fd.m === td.m) return `${day(fd)}-${day(td)} ${MON[fd.m-1]}`;
        if (sameYear) return `${day(fd)} ${MON[fd.m-1]} - ${day(td)} ${MON[td.m-1]}`;
        return `${day(fd)} ${MON[fd.m-1]} ${fd.y} - ${day(td)} ${MON[td.m-1]} ${td.y}`;
    };

    const toYMD = (date) => {
        // Normalise to local noon first. react-datepicker can hand back a UTC-midnight
        // Date; reading getDate() on it in a timezone behind UTC rolls back a day
        // (e.g. picking the 12th showed 11). Noon is safe against any ±12h offset.
        const safe = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 12, 0, 0);
        const y = safe.getFullYear();
        const m = String(safe.getMonth()+1).padStart(2,'0');
        const d = String(safe.getDate()).padStart(2,'0');
        return `${y}-${m}-${d}`;
    };

    const handleChange = (dates) => {
        let [start, end] = dates;
        // ── Safety: ensure From ≤ To always. Handles two edge-cases that produce reversed ranges:
        //   1. react-datepicker's `selectsRange` resets to `[newDate, null]` when the user
        //      clicks an earlier date as the 2nd click. That stranded the previous To value
        //      in our parent state, producing pills like "12 May → 01 May".
        //   2. Some browsers/locales send the dates in reverse order in rare cases.
        if (start && end && start > end) {
            // User picked end BEFORE start in the same gesture — auto-swap so range stays valid
            [start, end] = [end, start];
        }
        // If react-datepicker reset to a fresh start (end=null) but parent still has an old To,
        // discard that old To so the pill never shows "newStart → oldEnd" when newStart > oldEnd.
        if (start && !end) {
            const newStartYMD = toYMD(start);
            if (toDate && newStartYMD > toDate) {
                // New start is later than stored To → clear To so user is prompted to re-pick end
                onToChange('');
            }
        }
        setPickState({ start, end: end || null });
        if (start) onFromChange(toYMD(start));
        if (end) { onToChange(toYMD(end)); setIsOpen(false); }
    };

    // Resolve a preset label to its {from, to} YMD pair (null for Custom Range).
    const presetRange = (label) => {
        const now = new Date();
        let from, to;
        if (label === 'Today') { from = to = now; }
        else if (label === 'Yesterday') { from = to = new Date(now.getTime()-86400000); }
        else if (label === 'Last 7 days') { from = new Date(now.getTime()-6*86400000); to = now; }
        else if (label === 'Last 30 days') { from = new Date(now.getTime()-29*86400000); to = now; }
        else if (label === 'This month') { from = new Date(now.getFullYear(), now.getMonth(), 1); to = now; }
        else return null;
        return { from: toYMD(from), to: toYMD(to) };
    };

    const applyPreset = (label) => {
        const r = presetRange(label);
        if (!r) return;
        onFromChange(r.from); onToChange(r.to); setIsOpen(false);
    };

    // Single-date mode: one click sets both from & to to the same day, then closes.
    const handleSingleChange = (date) => {
        if (!date) return;
        const ymd = toYMD(date);
        setPickState({ start: date, end: date });
        onFromChange(ymd); onToChange(ymd);
        setIsOpen(false);
    };

    // ── Mobile sheet helpers (staged selection + Apply) ──
    const fmtDispDate = (d) => d ? d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : 'Select';
    const handleMobilePick = (dates) => {
        // Single mode: react-datepicker passes a single Date (not an array).
        if (mode === 'single') {
            const d = Array.isArray(dates) ? dates[0] : dates;
            setMPendStart(d); setMPendEnd(d);
            return;
        }
        let [start, end] = dates;
        if (start && end && start > end) [start, end] = [end, start];
        setMPendStart(start); setMPendEnd(end || null);
    };
    const applyMobilePreset = (label) => {
        if (label === 'Custom Range') return;
        const now = new Date(); let from, to;
        if (label === 'Today') { from = to = now; }
        else if (label === 'Yesterday') { from = to = new Date(now.getTime()-86400000); }
        else if (label === 'Last 7d') { from = new Date(now.getTime()-6*86400000); to = now; }
        else if (label === 'This month') { from = new Date(now.getFullYear(), now.getMonth(), 1); to = now; }
        setMPendStart(from); setMPendEnd(to);
    };
    const commitMobile = () => {
        if (mPendStart && mPendEnd) { onFromChange(toYMD(mPendStart)); onToChange(toYMD(mPendEnd)); }
        setIsOpen(false);
    };

    useEffect(() => {
        const handle = (e) => {
            if (
                ref.current && !ref.current.contains(e.target) &&
                (!dropdownRef.current || !dropdownRef.current.contains(e.target))
            ) setIsOpen(false);
        };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, []);

    const calSizeRef = useRef({ w: 660, h: 460 });

    const computePos = (calWidth, calHeight) => {
        if (!btnRef.current) return null;
        const r = btnRef.current.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const MARGIN = 8;

        // Vertical: prefer below button, fallback above, else clamp
        let top;
        if (r.bottom + calHeight + MARGIN <= vh) {
            top = r.bottom + MARGIN;
        } else if (r.top - calHeight - MARGIN >= MARGIN) {
            top = r.top - calHeight - MARGIN;
        } else {
            top = Math.max(MARGIN, vh - calHeight - MARGIN);
        }

        // Horizontal: LEFT-align to the button's left edge so the panel stays put when
        // its width changes (single ↔ range switch grows/shrinks only the right side,
        // never shifting the box). Clamp within viewport, and keep clear of the sidebar.
        const sidebarEl = document.querySelector('.main-menu');
        const sidebarRight = sidebarEl ? sidebarEl.getBoundingClientRect().right : 0;
        const minLeft = Math.max(MARGIN, sidebarRight + MARGIN);

        let left = r.left;
        if (left < minLeft) left = minLeft;
        // Only pull left if the panel would overflow the right viewport edge.
        if (left + calWidth > vw - MARGIN) left = Math.max(minLeft, vw - calWidth - MARGIN);

        return { top, left, bottom: undefined, right: undefined };
    };

    const updatePos = () => {
        const pos = computePos(calSizeRef.current.w, calSizeRef.current.h);
        if (pos) setDropdownPos(pos);
    };

    useEffect(() => {
        if (!isOpen || isMobile) return;
        window.addEventListener('scroll', updatePos, true);
        return () => window.removeEventListener('scroll', updatePos, true);
    }, [isOpen, isMobile]);

    // After dropdown mounts: measure real size, cache it, then position
    useEffect(() => {
        if (isOpen && !isMobile) {
            const id = setTimeout(() => {
                if (dropdownRef.current) {
                    calSizeRef.current = {
                        w: dropdownRef.current.offsetWidth,
                        h: dropdownRef.current.offsetHeight,
                    };
                }
                updatePos();
            }, 0);
            return () => clearTimeout(id);
        }
    }, [isOpen, isMobile, mode]);

    const calendarCSS = `
        @keyframes drpFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
        @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
        .drp-cal .react-datepicker,.drp-cal .react-datepicker__month-container,.drp-cal .react-datepicker__header,.drp-cal .react-datepicker__month{border:none!important;box-shadow:none!important;border-radius:0!important;}
        .drp-cal .react-datepicker{font-family:inherit;background:#fff!important;display:flex!important;flex-direction:row!important;}
        .drp-cal .react-datepicker__month-container{padding:0 16px;background:#fff!important;float:none!important;}
        .drp-cal .react-datepicker__month-container+.react-datepicker__month-container{border-left:1px solid #f0f0f2!important;}
        .drp-cal .react-datepicker__header{background:#fff!important;background-color:#fff!important;border-bottom:none!important;padding:6px 0 0!important;}
        .drp-cal .react-datepicker__header--custom{background:#fff!important;}
        .drp-cal .react-datepicker__month{background:#fff!important;margin:0!important;}
        .drp-cal .react-datepicker__current-month{font-size:13.5px;font-weight:700;color:#1f2937;margin-bottom:8px;padding:2px 0;}
        .drp-cal .react-datepicker__day-names{margin-bottom:2px;background:#fff!important;}
        .drp-cal .react-datepicker__day-name{font-size:10.5px;font-weight:700;color:#9ca3af;width:34px;line-height:26px;text-transform:uppercase;letter-spacing:0.4px;margin:0;}
        .drp-cal .react-datepicker__week{display:flex;}
        .drp-cal .react-datepicker__day{width:34px;height:34px;line-height:34px;font-size:12.5px;border-radius:50%;margin:1px 0;font-weight:600;color:#1f2937;background:transparent;transition:background 0.1s;}
        .drp-cal .react-datepicker__day:hover{background:#f1f5f9;color:#1f2937;}
        .drp-cal .react-datepicker__day--today{background:transparent;font-weight:700;color:#c2410c;}
        .drp-cal .react-datepicker__day--selected,.drp-cal .react-datepicker__day--range-start,.drp-cal .react-datepicker__day--range-end{background:rgb(234, 88, 12)!important;color:#fff!important;font-weight:700;border-radius:50%!important;}
        .drp-cal .react-datepicker__day--selected:hover,.drp-cal .react-datepicker__day--range-start:hover,.drp-cal .react-datepicker__day--range-end:hover{background:#c2410c!important;color:#fff!important;}
        .drp-cal .react-datepicker__day--in-range{background:#ffedd5!important;color:#c2410c!important;border-radius:0!important;font-weight:600;}
        .drp-cal .react-datepicker__day--in-range:hover{background:#fed7aa!important;color:#c2410c!important;}
        .drp-cal .react-datepicker__day--range-start{border-radius:50% 0 0 50%!important;}
        .drp-cal .react-datepicker__day--range-end{border-radius:0 50% 50% 0!important;}
        .drp-cal .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50%!important;}
        .drp-cal .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start){background:#ffedd5!important;color:#c2410c!important;border-radius:0!important;}
        .drp-cal .react-datepicker__day--selecting-range-start{background:rgb(234, 88, 12)!important;color:#fff!important;border-radius:50% 0 0 50%!important;}
        .drp-cal .react-datepicker__day--outside-month{color:transparent!important;background:transparent!important;pointer-events:none!important;cursor:default!important;}
        .drp-cal .react-datepicker__day--outside-month::after{content:none!important;}
        .drp-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#1f2937;}
        .drp-cal .react-datepicker__day--disabled{color:#e2e8f0!important;cursor:not-allowed;background:transparent!important;font-weight:400;}
        .drp-cal .react-datepicker__navigation{display:none!important;}
        .drp-cal-nav-btn{width:28px;height:28px;border:1.5px solid #fed7aa!important;border-radius:6px;background:#fff7f0;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;outline:none!important;box-shadow:none!important;transition:background 0.15s;}
        .drp-cal-nav-btn:hover{background:#fed7aa;border-color:rgb(234, 88, 12)!important;}
        .drp-cal-nav-btn:focus,.drp-cal-nav-btn:active,.drp-cal-nav-btn:focus-visible{outline:none!important;box-shadow:none!important;border:1.5px solid rgb(234, 88, 12)!important;background:#fff7f0;}
        .drp-cal-nav-btn:disabled{opacity:0.3;cursor:default;}
        .drp-m-cal .react-datepicker{border:none;font-family:inherit;background:transparent;width:100%;}
        .drp-m-cal .react-datepicker__month-container{width:100%;}
        .drp-m-cal .react-datepicker__header{background:transparent;border-bottom:none;padding-top:4px;}
        .drp-m-cal .react-datepicker__current-month{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:6px;}
        .drp-m-cal .react-datepicker__day-names{margin-bottom:4px;}
        .drp-m-cal .react-datepicker__day-name{font-size:11px;font-weight:700;color:#94a3b8;width:calc(100%/7);line-height:28px;text-transform:uppercase;}
        .drp-m-cal .react-datepicker__day{width:calc(100%/7);height:36px;line-height:36px;font-size:13px;border-radius:8px;margin:1px 0;font-weight:600;color:#1e293b;transition:all 0.1s;}
        .drp-m-cal .react-datepicker__day:hover{background:#f1f5f9;color:#1f2937;}
        .drp-m-cal .react-datepicker__day--today{background:transparent;font-weight:700;color:#c2410c;}
        .drp-m-cal .react-datepicker__day--selected,.drp-m-cal .react-datepicker__day--range-start,.drp-m-cal .react-datepicker__day--range-end{background:rgb(234, 88, 12)!important;color:#fff!important;font-weight:700;border-radius:50%!important;}
        .drp-m-cal .react-datepicker__day--in-range{background:#ffedd5!important;color:#c2410c!important;border-radius:0!important;font-weight:600;margin:1px 0!important;}
        .drp-m-cal .react-datepicker__day--in-range:hover{background:#fed7aa!important;color:#c2410c!important;}
        .drp-m-cal .react-datepicker__day--range-start{border-radius:50% 0 0 50%!important;}
        .drp-m-cal .react-datepicker__day--range-end{border-radius:0 50% 50% 0!important;}
        .drp-m-cal .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50%!important;}
        .drp-m-cal .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--range-start){background:#ffedd5!important;color:#c2410c!important;border-radius:0!important;}
        .drp-m-cal .react-datepicker__day--outside-month{color:#cbd5e1!important;font-weight:400;}
        .drp-m-cal .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b;}
        .drp-m-cal .react-datepicker__day--disabled{color:#94a3b8!important;cursor:not-allowed;background:transparent!important;font-weight:400;}
        .drp-m-cal .react-datepicker__navigation{top:14px;width:32px;height:32px;border:1.5px solid #e5e7eb;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;}
        .drp-m-cal .react-datepicker__navigation-icon::before{border-color:#6b7280;border-width:2.5px 2.5px 0 0;width:7px;height:7px;top:10px;}
        .drp-preset-chip::-webkit-scrollbar{display:none;}
        @media(min-width:768px){
        .drp-cal .react-datepicker__navigation:hover{background:#fed7aa;border-color:rgb(234, 88, 12);}
        .drp-cal .react-datepicker__navigation:hover .react-datepicker__navigation-icon::before{border-color:rgb(234, 88, 12);}
        }
    `;

    const rangeDisplay = (
        <div style={{display:'flex',alignItems:'center',justifyContent:'center',gap:'0',padding:'12px 0 4px',borderTop:'1px solid #f0f0f0',marginTop:'8px'}}>
            <div onClick={() => setPickState({ start: null, end: null })} title="Click to change start date" style={{background: pickState.start && !pickState.end ? '#b45309' : '#1e293b',borderRadius:'8px 0 0 8px',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color:'#fff',display:'flex',alignItems:'center',gap:'8px',cursor:'pointer',transition:'background 0.15s'}}>
                <i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>
                {formatDisplay(fromDate)}
                {pickState.start && !pickState.end && <i className="fa fa-pencil" style={{fontSize:'9px',opacity:0.7,marginLeft:'2px'}}></i>}
            </div>
            <div style={{background:'#334155',padding:'8px 12px',display:'flex',alignItems:'center'}}>
                <i className="fa fa-arrow-right" style={{fontSize:'10px',color:'#94a3b8'}}></i>
            </div>
            <div style={{background: toDate ? '#1e293b' : '#475569',borderRadius:'0 8px 8px 0',padding:'8px 16px',fontSize:'13px',fontWeight:'700',color: toDate ? '#fff' : '#94a3b8',display:'flex',alignItems:'center',gap:'8px'}}>
                <i className="fa fa-calendar-o" style={{fontSize:'11px',opacity:0.6}}></i>
                {toDate ? formatDisplay(toDate) : 'Select end'}
            </div>
        </div>
    );

    const presets = ['Today','Yesterday','Last 7 days','Last 30 days','This month'];

    return (
        <div ref={ref} style={{position:'relative',width:'100%'}}>
            <style>{calendarCSS}</style>
            <style>{`.ph-drp-btn:focus,.ph-drp-btn:active,.ph-drp-btn:focus-visible{outline:none!important;box-shadow:none!important;}`}</style>
            {variant === 'labeled' ? (
            <button className="ph-drp-btn" type="button" ref={btnRef} onClick={() => { if (!isOpen) updatePos(); setIsOpen(!isOpen); }} style={{ display:'flex',alignItems:'center',justifyContent:'space-between',gap:'8px',width:'100%',height:'54px',padding:'8px 12px',borderRadius:'12px',background:'#fff',borderWidth:'1px',borderStyle:'solid',borderColor: isOpen ? 'rgb(234, 88, 12)' : '#eaecf2',boxShadow:'0 1px 2px rgba(16,24,40,0.04)',cursor:'pointer',outline:'none',boxSizing:'border-box',transition:'border-color 0.15s' }}>
                <span style={{ display:'flex',flexDirection:'column',alignItems:'flex-start',minWidth:0,flex:1 }}>
                    <span style={{ fontSize:'10px',fontWeight:'700',color:'#94a3b8',letterSpacing:'0.7px',textTransform:'uppercase',marginBottom:'3px',lineHeight:1 }}>Date Range</span>
                    <span style={{ display:'flex',alignItems:'center',gap:'7px',minWidth:0,maxWidth:'100%' }}>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <span style={{ fontSize:'14px',fontWeight:'700',color:'#0f1115',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis' }}>{fromDate && toDate ? compactRange(fromDate, toDate) : 'All dates'}</span>
                    </span>
                </span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><path d="M6 9l6 6 6-6"/></svg>
            </button>
            ) : (
            <button className="ph-drp-btn" type="button" ref={btnRef} onClick={() => {
                if (!isOpen) updatePos();
                setIsOpen(!isOpen);
            }} style={isSpec ? {
                display:'flex',alignItems:'center',gap:'10px',height:'42px',
                padding:'0 14px',borderRadius:'10px',cursor:'pointer',background:'#fff',
                borderWidth:'1.5px',borderStyle:'solid',borderColor: isOpen ? 'rgb(234, 88, 12)' : '#e8e8ec',
                boxShadow:'none',outline:'none',
                fontSize:'13px',fontWeight:'500',color:'#0f1115',boxSizing:'border-box',transition:'border-color 0.15s',whiteSpace:'nowrap',
            } : {
                display:'inline-flex',alignItems:'center',gap:'8px',height:'40px',
                background:'#fff',borderWidth:'1.5px',borderStyle:'solid',borderColor: isOpen ? 'rgb(234, 88, 12)' : '#e8edf2',borderRadius:'10px',
                padding:'0 14px',cursor:'pointer',outline:'none',transition:'border-color 0.15s',
                width:'100%',boxSizing:'border-box',
                boxShadow: isOpen ? '0 0 0 3px rgba(242,116,32,0.08)' : 'none',
            }}>
                {isSpec
                    ? <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6b7280" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    : <i className="fa fa-calendar" style={{fontSize:'12px',color:'rgb(234, 88, 12)'}}></i>}
                {fromDate && toDate && fromDate === toDate ? (
                    // Single day selected — show one date, no arrow.
                    <>
                        <span style={{fontSize:'13px',fontWeight:'500',color:'#0f1115'}}>{formatDisplay(fromDate)}</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6b7280" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0,marginLeft:'2px'}}><path d="M6 9l6 6 6-6"/></svg>
                    </>
                ) : fromDate && toDate ? (
                    isSpec ? (
                    <>
                        <span style={{fontSize:'13px',fontWeight:'500',color:'#0f1115'}}>{formatDisplay(fromDate)}</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                        <span style={{fontSize:'13px',fontWeight:'500',color:'#0f1115'}}>{formatDisplay(toDate)}</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6b7280" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0,marginLeft:'2px'}}><path d="M6 9l6 6 6-6"/></svg>
                    </>
                    ) : (
                    <>
                        <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(fromDate)}</span>
                        <i className="fa fa-arrow-right" style={{fontSize:'9px',color:'#d1d5db'}}></i>
                        <span style={{fontSize:'13px',fontWeight:'600',color:'#374151'}}>{formatDisplay(toDate)}</span>
                    </>
                    )
                ) : (
                    isSpec ? (
                    <>
                        <span style={{fontSize:'13px',fontWeight:'500',color:'#9ca3af'}}>Select date range</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6b7280" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0,marginLeft:'2px'}}><path d="M6 9l6 6 6-6"/></svg>
                    </>
                    ) : (
                    <span style={{fontSize:'13px',fontWeight:'500',color:'#94a3b8'}}>Select date range</span>
                    )
                )}
            </button>
            )}

            {/* ── MOBILE/TABLET: bottom sheet ── */}
            {isOpen && isMobile && (<>
                <div onMouseDown={()=>setIsOpen(false)} onTouchEnd={()=>setIsOpen(false)} style={{position:'fixed',inset:0,zIndex:9998,background:'rgba(0,0,0,0.4)'}}/>
                <div onMouseDown={e=>e.stopPropagation()} onTouchEnd={e=>e.stopPropagation()} style={{position:'fixed',bottom:0,left:0,right:0,zIndex:9999,background:'#fff',borderRadius:'20px 20px 0 0',paddingBottom:'env(safe-area-inset-bottom,16px)',boxShadow:'0 -8px 32px rgba(0,0,0,0.15)',maxHeight:'85vh',overflowY:'auto',animation:'slideUp 0.28s cubic-bezier(0.34,1.1,0.64,1)'}}>
                    <div style={{display:'flex',justifyContent:'center',paddingTop:'10px',paddingBottom:'4px'}}><div style={{width:'36px',height:'4px',borderRadius:'99px',background:'#e5e7eb'}}/></div>
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'4px 18px 14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{width:'30px',height:'30px',borderRadius:'9px',background:'#fff7ed',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <span style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Select Date Range</span>
                        </div>
                        <button type="button" onClick={()=>setIsOpen(false)} style={{background:'#f1f5f9',border:'none',outline:'none',borderRadius:'50%',width:'30px',height:'30px',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center'}}>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    {/* Mode dropdown: Single Date / Date Range */}
                    <div style={{padding:'0 18px 14px'}}>
                        <div style={{position:'relative'}}>
                            <button type="button" onClick={() => setModeDdOpen(v=>!v)} style={{
                                width:'100%',display:'flex',alignItems:'center',justifyContent:'space-between',
                                border:'1.5px solid #fed7aa',borderRadius:'12px',background:'#fff7f0',
                                color:'rgb(234, 88, 12)',fontSize:'14px',fontWeight:'700',padding:'11px 14px',cursor:'pointer',outline:'none',
                            }}>
                                <span style={{display:'inline-flex',alignItems:'center',gap:'8px'}}>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    {modeLabel}
                                </span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            {modeDdOpen && (
                                <div style={{position:'absolute',top:'calc(100% + 4px)',left:0,right:0,zIndex:20,background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'12px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',padding:'4px'}}>
                                    {[{k:'single',label:'Single Date'},{k:'range',label:'Date Range'}].map(opt => {
                                        const on = mode === opt.k;
                                        return (
                                            <div key={opt.k} onClick={() => { setMode(opt.k); if (opt.k === 'single') setMPendEnd(mPendStart); setModeDdOpen(false); }} style={{
                                                padding:'11px 14px',fontSize:'14px',fontWeight: on ? '800' : '600',borderRadius:'9px',cursor:'pointer',
                                                background: on ? 'rgb(234, 88, 12)' : 'transparent',color: on ? '#fff' : '#374151',
                                            }}>{opt.label}</div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                    <div style={{padding:'0 18px 14px'}}>
                        <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
                            <div style={{flex:1,background:'#fff',border:'2px solid '+(mPendStart?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:mPendStart?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>From</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:mPendStart?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{mPendStart?fmtDispDate(mPendStart):'Select'}</div>
                            </div>
                            <div style={{width:'36px',height:'36px',borderRadius:'10px',background:'rgb(234, 88, 12)',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'0 3px 10px rgba(234,88,12,0.35)'}}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </div>
                            <div style={{flex:1,background:'#fff',border:'2px solid '+(mPendEnd?'rgb(234, 88, 12)':'#e5e7eb'),borderRadius:'12px',padding:'8px 12px',boxShadow:mPendEnd?'0 0 0 3px rgba(234,88,12,0.08)':'none'}}>
                                <div style={{display:'flex',alignItems:'center',gap:'5px',marginBottom:'3px'}}>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span style={{fontSize:'10px',fontWeight:'800',color:'rgb(234, 88, 12)',letterSpacing:'0.6px',textTransform:'uppercase'}}>To</span>
                                </div>
                                <div style={{fontSize:'14px',fontWeight:'700',color:mPendEnd?'#0f172a':'#cbd5e1',whiteSpace:'nowrap'}}>{mPendEnd?fmtDispDate(mPendEnd):'Select'}</div>
                            </div>
                        </div>
                    </div>
                    {mode === 'range' && (
                    <div className="sp-presets" style={{display:'flex',gap:'8px',padding:'0 18px 14px',overflowX:'auto',WebkitOverflowScrolling:'touch'}}>
                        {['Today','Yesterday','Last 7d','This month','Custom Range'].map(label => {
                            const pr=(()=>{const now=new Date();let f,t;if(label==='Today'){f=t=now;}else if(label==='Yesterday'){f=t=new Date(now.getTime()-86400000);}else if(label==='Last 7d'){f=new Date(now.getTime()-6*86400000);t=now;}else if(label==='This month'){f=new Date(now.getFullYear(),now.getMonth(),1);t=now;}else return {f:null,t:null};return {f:toYMD(f),t:toYMD(t)};})();
                            const active = label!=='Custom Range' && mPendStart && mPendEnd && toYMD(mPendStart)===pr.f && toYMD(mPendEnd)===pr.t;
                            return (<button key={label} type="button" onClick={()=>applyMobilePreset(label)} style={{flexShrink:0,height:'34px',padding:'0 16px',borderRadius:'999px',border: active ? 'none' : '1.5px solid #e5e7eb',background: active ? '#111827' : '#fff',color: active ? '#fff' : '#475569',fontSize:'13px',fontWeight:'700',cursor:'pointer',outline:'none',whiteSpace:'nowrap'}}>{label}</button>);
                        })}
                    </div>
                    )}
                    <style>{`.sp-range .react-datepicker{width:100%;border:none;font-family:inherit;background:#fff !important;box-shadow:none !important}.sp-range .react-datepicker__month-container{width:100%;float:none;background:#fff !important}.sp-range .react-datepicker__month{background:#fff !important;margin:0 !important}.sp-range .react-datepicker__week{background:#fff !important}.sp-range .react-datepicker__header{background:#fff !important;border-bottom:none;padding:0}.sp-range .react-datepicker__header--custom{background:#fff !important;border-bottom:none !important;padding:0 !important}.sp-range .react-datepicker__day-names,.sp-range .react-datepicker__week{display:flex;justify-content:space-around}.sp-range .react-datepicker__day-name{width:calc(100%/7);height:34px;line-height:34px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;margin:0}.sp-range .react-datepicker__day{display:inline-flex;align-items:center;justify-content:center;width:calc(100%/7);height:42px;font-size:14px;font-weight:500;color:#334155;margin:0;border-radius:50%;transition:background 0.12s,color 0.12s;position:relative}.sp-range .react-datepicker__day:hover:not(.react-datepicker__day--selected):not(.react-datepicker__day--range-start):not(.react-datepicker__day--range-end){background:#f1f5f9;color:#0f172a}.sp-range .react-datepicker__day--today{font-weight:700;color:rgb(234, 88, 12);background:transparent}.sp-range .react-datepicker__day--in-range,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start){background:transparent !important;color:rgb(234, 88, 12) !important;font-weight:600;position:relative}.sp-range .react-datepicker__day--in-range::before,.sp-range .react-datepicker__day--in-selecting-range:not(.react-datepicker__day--selecting-range-start)::before{content:'';position:absolute;top:4px;bottom:4px;left:0;right:0;background:#fff7f0;z-index:-1}.sp-range .react-datepicker__day--selected,.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--selecting-range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--selected,.sp-range .react-datepicker__day--today.react-datepicker__day--range-start,.sp-range .react-datepicker__day--today.react-datepicker__day--range-end{background:transparent !important;color:#fff !important;font-weight:800 !important;font-size:13px;position:relative;z-index:1}
/* range band behind start/end (half-inset like reference) */
.sp-range .react-datepicker__day--range-start:not(.react-datepicker__day--range-end)::after{content:'';position:absolute;top:4px;bottom:4px;left:50%;right:0;background:#fff7f0;z-index:-2}
.sp-range .react-datepicker__day--range-end:not(.react-datepicker__day--range-start)::after{content:'';position:absolute;top:4px;bottom:4px;left:0;right:50%;background:#fff7f0;z-index:-2}
/* orange circle for selected/start/end */
.sp-range .react-datepicker__day--selected::before,.sp-range .react-datepicker__day--range-start::before,.sp-range .react-datepicker__day--range-end::before,.sp-range .react-datepicker__day--selecting-range-start::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:34px;height:34px;border-radius:50%;background:rgb(234, 88, 12);box-shadow:rgba(234, 88, 12, 0.5) 0px 4px 10px -3px;z-index:-1}.sp-range .react-datepicker__day--range-start,.sp-range .react-datepicker__day--range-end,.sp-range .react-datepicker__day--range-start.react-datepicker__day--range-end{border-radius:50% !important}.sp-range .react-datepicker__day--outside-month{color:#d1d5db}.sp-range .react-datepicker__day--disabled{color:#e5e7eb !important;background:transparent !important}.sp-range .react-datepicker__day--keyboard-selected{background:transparent;color:#1e293b}.sp-range .react-datepicker__navigation{display:none !important}.sp-range .react-datepicker__current-month{display:none !important}.sp-dd{position:relative;display:inline-block}.sp-dd-btn{border:1.5px solid #e5e7eb;border-radius:9px;padding:7px 26px 7px 14px;font-size:13px;font-weight:700;color:#1e293b;cursor:pointer;outline:none;background:#f4f4f6;position:relative}.sp-dd-btn:focus,.sp-dd-btn:active{outline:none;border-color:rgb(234, 88, 12)}.sp-dd-btn::after{content:'';position:absolute;right:10px;top:50%;transform:translateY(-50%);border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #94a3b8}.sp-dd-list{position:absolute;top:calc(100% + 4px);left:50%;transform:translateX(-50%);background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:99;max-height:180px;overflow-y:auto;min-width:84px;padding:4px}.sp-dd-list::-webkit-scrollbar{width:3px}.sp-dd-list::-webkit-scrollbar-thumb{background:#fed7aa;border-radius:3px}.sp-dd-item{padding:6px 12px;font-size:12px;font-weight:600;border-radius:6px;cursor:pointer;text-align:center;color:#374151;transition:all 0.1s}.sp-dd-item:hover{background:#fff7ed;color:rgb(234, 88, 12)}.sp-dd-item.active{background:rgb(234, 88, 12);color:#fff;font-weight:700}.sp-presets{scrollbar-width:none;-ms-overflow-style:none}.sp-presets::-webkit-scrollbar{display:none;width:0;height:0}`}</style>
                    <div className="sp-range" style={{padding:'4px 16px 0'}}>
                        <DatePicker inline selected={mPendStart} onChange={handleMobilePick} startDate={mode==='single'?undefined:mPendStart} endDate={mode==='single'?undefined:mPendEnd} selectsRange={mode==='range'} maxDate={new Date()} openToDate={mPendStart || openToDateRef.current}
                            renderCustomHeader={({date,changeYear,changeMonth,decreaseMonth,increaseMonth,prevMonthButtonDisabled,nextMonthButtonDisabled})=>{
                                const mnthsFull=['January','February','March','April','May','June','July','August','September','October','November','December'];
                                const mnths=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                const cy=new Date().getFullYear(); const yrs=Array.from({length:10},(_,i)=>cy-5+i);
                                const nb={width:'34px',height:'34px',border:'1.5px solid #e5e7eb',borderRadius:'50%',background:'#fff',cursor:'pointer',display:'flex',alignItems:'center',justifyContent:'center',outline:'none',flexShrink:0};
                                return (<div style={{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'12px',gap:'6px'}}>
                                    <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{...nb,opacity:prevMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                                    <div className="sp-dd" style={{flex:1,display:'flex',justifyContent:'center'}}>
                                        <button type="button" onClick={()=>setSMonthDd(v=>!v)} style={{display:'inline-flex',alignItems:'center',gap:'7px',background:'transparent',border:'none',outline:'none',cursor:'pointer',fontSize:'16px',fontWeight:'800',color:'#0f172a',padding:'4px 8px'}}>
                                            {mnthsFull[date.getMonth()]} {date.getFullYear()}
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        {sMonthDd&&(<div className="sp-dd-list" style={{minWidth:'200px',display:'grid',gridTemplateColumns:'1fr 1fr',gap:'4px',padding:'8px'}}>
                                            <div style={{gridColumn:'1 / -1',display:'flex',alignItems:'center',justifyContent:'space-between',padding:'2px 4px 6px'}}>
                                                <span style={{fontSize:'11px',fontWeight:'700',color:'#94a3b8'}}>MONTH</span>
                                                <select value={date.getFullYear()} onChange={e=>changeYear(Number(e.target.value))} style={{border:'1.5px solid #e5e7eb',borderRadius:'7px',fontSize:'12px',fontWeight:'700',color:'#0f172a',padding:'3px 6px',outline:'none',cursor:'pointer'}}>{yrs.map(y=><option key={y} value={y}>{y}</option>)}</select>
                                            </div>
                                            {mnths.map((m,i)=><div key={m} className={'sp-dd-item'+(date.getMonth()===i?' active':'')} onClick={()=>{changeMonth(i);setSMonthDd(false);}}>{m}</div>)}
                                        </div>)}
                                    </div>
                                    <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...nb,opacity:nextMonthButtonDisabled?0.3:1}}><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#475569" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                                </div>);
                            }}
                        />
                    </div>
                    <div style={{display:'grid',gridTemplateColumns:'1fr 1.6fr',gap:'12px',padding:'8px 18px 16px'}}>
                        <button type="button" onClick={()=>{setMPendStart(null);setMPendEnd(null);setIsOpen(false);}} style={{height:'52px',borderRadius:'14px',border:'1.5px solid #e5e7eb',background:'#fff',color:'#475569',fontSize:'15px',fontWeight:'700',cursor:'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'7px'}}>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Cancel
                        </button>
                        <button type="button" onClick={commitMobile} disabled={!mPendStart||!mPendEnd} style={{height:'52px',borderRadius:'14px',border:'none',background:(!mPendStart||!mPendEnd)?'#e2e8f0':'rgb(234, 88, 12)',color:(!mPendStart||!mPendEnd)?'#94a3b8':'#fff',fontSize:'15px',fontWeight:'800',letterSpacing:'0.2px',cursor:(!mPendStart||!mPendEnd)?'default':'pointer',outline:'none',display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',boxShadow:(!mPendStart||!mPendEnd)?'none':'0 6px 16px rgba(234,88,12,0.35)'}}>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Apply
                        </button>
                    </div>
                </div>
            </>)}

            {/* ── DESKTOP: dropdown via portal (escapes overflow:hidden parents) ── */}
            {isOpen && !isMobile && ReactDOM.createPortal(
                <>
                <div style={{position:'fixed',top:0,left:0,right:0,bottom:0,zIndex:999999}} onMouseDown={() => setIsOpen(false)}></div>
                <div ref={dropdownRef} onMouseDown={e => e.stopPropagation()} style={{position:'fixed',...(dropdownPos.top!=null?{top:dropdownPos.top}:{}),...(dropdownPos.bottom!=null?{bottom:dropdownPos.bottom}:{}),...(dropdownPos.left!=null?{left:dropdownPos.left}:{right:dropdownPos.right}),zIndex:1000000,background:'#fff',borderRadius:'12px',boxShadow:'0 4px 20px rgba(0,0,0,0.18)',display:'flex',flexDirection:'row',overflow:'hidden',maxHeight:'calc(100vh - 24px)',animation:'drpFadeIn 0.15s ease-out'}}>
                    <style>{calendarCSS}</style>
                    {/* Left sidebar: presets */}
                    {mode === 'range' && (
                    <div style={{display:'flex',flexDirection:'column',width:'150px',borderRight:'1px solid #e5e7eb',background:'#fff',flexShrink:0}}>
                        {(() => {
                            // Which preset (if any) matches the current selection? Else Custom Range is active.
                            const matched = fromDate && toDate
                                ? presets.find(p => { const r = presetRange(p); return r && r.from === fromDate && r.to === toDate; })
                                : null;
                            const activeLabel = matched || 'Custom Range';
                            return [...presets,'Custom Range'].map(label => {
                            const isCustom = label === 'Custom Range';
                            const isActive = label === activeLabel;
                            return (
                                <button key={label} type="button" onClick={() => { if (!isCustom) applyPreset(label); }} style={{
                                    border:'none',borderLeft: isActive ? '3px solid rgb(234, 88, 12)' : '3px solid transparent',
                                    background: isActive ? '#fff5ed' : 'transparent',
                                    padding:'11px 16px',fontSize:'13px',fontWeight: isActive ? '600' : '400',
                                    color: isActive ? 'rgb(234, 88, 12)' : '#374151',cursor:'pointer',outline:'none',
                                    textAlign:'left',transition:'all 0.1s',whiteSpace:'nowrap',
                                }}>
                                    {label}
                                </button>
                            );
                        });
                        })()}
                    </div>
                    )}
                    {/* Right: mode toggle + calendar + range bar */}
                    <div style={{display:'flex',flexDirection:'column'}}>
                        {/* Mode dropdown: Single Date / Date Range */}
                        <div style={{padding:'12px 16px 4px',display:'flex',justifyContent:'flex-start'}}>
                            <div style={{position:'relative'}}>
                                <button type="button" onClick={() => setModeDdOpen(v=>!v)} style={{
                                    display:'inline-flex',alignItems:'center',gap:'8px',
                                    border:'1.5px solid #fed7aa',borderRadius:'9px',background:'#fff7f0',
                                    color:'rgb(234, 88, 12)',fontSize:'13px',fontWeight:'700',
                                    padding:'7px 12px',cursor:'pointer',outline:'none',whiteSpace:'nowrap',
                                }}>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    {modeLabel}
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" style={{marginLeft:'2px'}}><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                                {modeDdOpen && (
                                    <div style={{position:'absolute',top:'calc(100% + 4px)',left:0,zIndex:20,background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'10px',boxShadow:'0 8px 24px rgba(0,0,0,0.12)',padding:'4px',minWidth:'150px'}}>
                                        {[{k:'single',label:'Single Date'},{k:'range',label:'Date Range'}].map(opt => {
                                            const on = mode === opt.k;
                                            return (
                                                <div key={opt.k} onClick={() => { setMode(opt.k); setModeDdOpen(false); }} style={{
                                                    padding:'8px 12px',fontSize:'13px',fontWeight: on ? '700' : '500',borderRadius:'7px',cursor:'pointer',
                                                    background: on ? 'rgb(234, 88, 12)' : 'transparent',color: on ? '#fff' : '#374151',transition:'background 0.1s',
                                                }}
                                                onMouseEnter={e=>{ if(!on){ e.currentTarget.style.background='#fff5ed'; e.currentTarget.style.color='rgb(234, 88, 12)'; }}}
                                                onMouseLeave={e=>{ if(!on){ e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#374151'; }}}
                                                >{opt.label}</div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>
                        <div style={{padding:'0 0 4px'}} className="drp-cal">
                            <DatePicker
                                selected={pickState.start}
                                onChange={mode === 'single' ? handleSingleChange : handleChange}
                                startDate={mode === 'single' ? undefined : pickState.start}
                                endDate={mode === 'single' ? undefined : pickState.end}
                                selectsRange={mode === 'range'}
                                inline
                                monthsShown={mode === 'single' ? 1 : 2}
                                maxDate={new Date()}
                                openToDate={openToDateRef.current}
                                renderCustomHeader={({
                                    monthDate, customHeaderCount,
                                    decreaseMonth, increaseMonth,
                                    prevMonthButtonDisabled, nextMonthButtonDisabled,
                                    changeMonth, changeYear,
                                }) => {
                                    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                    const currentYear = new Date().getFullYear();
                                    const years = Array.from({length:20},(_,i)=>currentYear-10+i);
                                    return (
                                        <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'8px 4px',gap:'4px',minHeight:'36px'}}>
                                            {customHeaderCount === 0 ? (
                                                <button type="button" className="drp-cal-nav-btn" onClick={decreaseMonth} disabled={prevMonthButtonDisabled}>
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                                                </button>
                                            ) : <div style={{width:'28px',flexShrink:0}}/>}
                                            <div style={{display:'flex',alignItems:'center',gap:'4px',flex:1,justifyContent:'center'}}>
                                                <DrpDropdown value={monthDate.getMonth()} options={months.map((m,i)=>({value:i,label:m}))} onChange={v=>changeMonth(v)} />
                                                <DrpDropdown value={monthDate.getFullYear()} options={years.map(y=>({value:y,label:String(y)}))} onChange={v=>changeYear(v)} />
                                            </div>
                                            {(customHeaderCount === 1 || mode === 'single') ? (
                                                <button type="button" className="drp-cal-nav-btn" onClick={increaseMonth} disabled={nextMonthButtonDisabled}>
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                                                </button>
                                            ) : <div style={{width:'28px',flexShrink:0}}/>}
                                        </div>
                                    );
                                }}
                            />
                        </div>
                        {mode === 'range' && rangeDisplay}
                    </div>
                </div>
                </>,
                document.body
            )}
        </div>
    );
}

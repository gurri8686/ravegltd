import React, { useState, useRef, useEffect, forwardRef, useImperativeHandle, useCallback } from "react";
import ReactDOM from "react-dom";
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";

const css = `
.orange-datepicker-input, .inv-date-picker {
  caret-color: transparent !important;
  cursor: pointer !important;
  user-select: none !important;
  border: none !important;
  outline: none !important;
  background: transparent !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  color: #1e293b !important;
  width: 100% !important;
  padding: 0 !important;
  line-height: 1 !important;
  font-family: inherit !important;
}
.orange-datepicker-input::placeholder, .inv-date-picker::placeholder {
  color: #94a3b8 !important;
  font-weight: 400 !important;
}
@media(max-width:768px){
  .react-datepicker-popper{margin-left:0 !important;}
  .react-datepicker{
    border-radius:16px !important;
    border:1px solid #e5e7eb !important;
    box-shadow:0 12px 40px rgba(0,0,0,0.18) !important;
    overflow:hidden !important;
  }
  .react-datepicker__month-container{
    width:100% !important;
  }
}
.odp-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 12px;
  border: 1.5px solid #e8edf2;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.15s;
  box-sizing: border-box;
}
.odp-wrapper:hover {
  border-color: #F27420;
}
.odp-wrapper.odp-has-value {
  border-color: #e8edf2;
}
.odp-popper-portal {
  position: fixed !important;
  z-index: 99999 !important;
  top: 0 !important;
  left: 0 !important;
  pointer-events: none !important;
}
.odp-popper-portal > * {
  pointer-events: all !important;
}
.react-datepicker__day-name,
.react-datepicker__day {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  line-height: 1 !important;
  width: 2rem !important;
  height: 2rem !important;
  margin: 0.1rem !important;
  font-size: 13px !important;
}
.react-datepicker__day-names {
  margin-bottom: 2px !important;
}
.react-datepicker__day-name {
  font-size: 11px !important;
  font-weight: 700 !important;
  color: #94a3b8 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.3px !important;
}
.react-datepicker__month {
  margin: 4px 8px 8px !important;
}
.react-datepicker__week {
  display: flex !important;
  align-items: center !important;
}
`;

// Portal container — renders into body to escape all overflow/clip parents
function BodyPortal({ children }) {
    const el = useRef(null);
    if (!el.current) {
        el.current = document.createElement('div');
        el.current.className = 'odp-popper-portal';
        document.body.appendChild(el.current);
    }
    useEffect(() => {
        return () => { if (el.current && document.body.contains(el.current)) document.body.removeChild(el.current); };
    }, []);
    return ReactDOM.createPortal(children, el.current);
}

function MonthYearDropdown({ value, options, onChange, onClose }) {
    const ref = useRef(null);
    useEffect(() => {
        const handle = (e) => { if (ref.current && !ref.current.contains(e.target)) onClose(); };
        document.addEventListener('mousedown', handle);
        return () => document.removeEventListener('mousedown', handle);
    }, [onClose]);
    return (
        <div ref={ref} style={{
            position:'absolute',top:'100%',left:'50%',transform:'translateX(-50%)',
            marginTop:'4px',background:'#fff',border:'1.5px solid #fed7aa',borderRadius:'12px',
            boxShadow:'0 8px 24px rgba(0,0,0,0.12)',padding:'8px',zIndex:100,
            display:'grid',gridTemplateColumns:'repeat(3, 1fr)',gap:'4px',minWidth:'180px',
        }}>
            {options.map(opt => (
                <button key={opt.value} type="button" onClick={() => { onChange(opt.value); onClose(); }}
                    style={{
                        border:'none',borderRadius:'8px',padding:'7px 4px',fontSize:'12px',fontWeight:'600',
                        cursor:'pointer',transition:'all 0.15s',textAlign:'center',lineHeight:'1.3',
                        background: opt.value === value ? '#F27420' : 'transparent',
                        color: opt.value === value ? '#fff' : '#374151',
                    }}
                    onMouseEnter={e => { if (opt.value !== value) { e.currentTarget.style.background='#FFF5ED'; e.currentTarget.style.color='#F27420'; }}}
                    onMouseLeave={e => { if (opt.value !== value) { e.currentTarget.style.background='transparent'; e.currentTarget.style.color='#374151'; }}}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

// PopperContainer renders the calendar into a fixed-position div in body
// so it escapes overflow/clip parents. Container is created by the parent
// OrangeDatePicker (via useEffect) so its lifecycle is correctly tied to mount/unmount,
// avoiding the "removeChild not a child of this node" error when React unmounts.
function PopperContainerInner({ container, wrapperRef, children }) {
    const positionCalendar = useCallback(() => {
        if (!container || !wrapperRef.current) return;
        const r = wrapperRef.current.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const isMobileView = vw <= 768;
        const calEl = container.querySelector('.react-datepicker');
        const calW = calEl ? calEl.offsetWidth + 4 : 290;
        const calH = calEl ? calEl.offsetHeight + 4 : 340;

        let top, left;
        if (isMobileView) {
            left = Math.round((vw - calW) / 2);
            top = r.bottom + 8;
            if (top + calH > vh - 16) top = Math.max(8, r.top - calH - 8);
            top = Math.max(8, Math.min(top, vh - calH - 8));
            left = Math.max(8, left);
        } else {
            top = r.bottom + 4;
            if (vw - r.left < calW) left = r.right - calW;
            else left = r.left;
            top = Math.max(8, Math.min(top, vh - calH - 8));
            left = Math.max(8, Math.min(left, vw - calW - 8));
        }
        container.style.transform = `translate(${left}px,${top}px)`;
        container.style.pointerEvents = 'none';
    }, [container, wrapperRef]);

    useEffect(() => {
        positionCalendar();
        const t1 = setTimeout(positionCalendar, 30);
        const t2 = setTimeout(positionCalendar, 150);
        window.addEventListener('scroll', positionCalendar, true);
        window.addEventListener('resize', positionCalendar);
        return () => {
            clearTimeout(t1); clearTimeout(t2);
            window.removeEventListener('scroll', positionCalendar, true);
            window.removeEventListener('resize', positionCalendar);
        };
    }, [positionCalendar]);

    if (!container) return null;
    return ReactDOM.createPortal(<div style={{ pointerEvents: 'all' }}>{children}</div>, container);
}

const OrangeDatePicker = forwardRef(function OrangeDatePicker({ value, onChange, placeholder, className, style, popperPlacement, allowKeyboard, noBorder, standalone }, ref) {
    const datePickerRef = useRef(null);
    const wrapperRef = useRef(null);
    const placementRef = useRef(popperPlacement || 'bottom-end');
    const selected = value ? new Date(value + 'T00:00:00') : null;
    const [showMonthPicker, setShowMonthPicker] = useState(false);
    const [showYearPicker, setShowYearPicker] = useState(false);
    const [isOpen, setIsOpen] = useState(false);

    // Per-instance container div for the calendar portal. Lifecycle is tied to mount/unmount
    // so React cleanup never tries to remove a stale node — fixes "removeChild not a child" error.
    const containerRef = useRef(null);
    const [containerReady, setContainerReady] = useState(false);
    useEffect(() => {
        const el = document.createElement('div');
        el.className = 'odp-popper-container';
        el.style.cssText = 'position:fixed;top:0;left:0;z-index:999;pointer-events:none;';
        document.body.appendChild(el);
        containerRef.current = el;
        setContainerReady(true);
        return () => {
            // Guard removal — node could already have been detached by parent unmount.
            try {
                if (el.parentNode) el.parentNode.removeChild(el);
            } catch (_) { /* swallow — DOM already cleaned */ }
            containerRef.current = null;
        };
    }, []);

    // react-datepicker calls this with the menu children; we forward them into our managed container.
    const popperContainer = useCallback(({ children }) => {
        if (!containerReady || !containerRef.current) return null;
        return <PopperContainerInner container={containerRef.current} wrapperRef={wrapperRef}>{children}</PopperContainerInner>;
    }, [containerReady]);

    useImperativeHandle(ref, () => ({
        close: () => { if (datePickerRef.current) datePickerRef.current.setOpen(false); }
    }));

    const handleChange = (date) => {
        if (date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            onChange(`${y}-${m}-${d}`);
        }
        setShowMonthPicker(false);
        setShowYearPicker(false);
    };

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 20 }, (_, i) => currentYear - 10 + i);
    const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

    const headerBtnStyle = {
        border:'1.5px solid #fed7aa',borderRadius:'8px',padding:'5px 12px',fontSize:'13px',
        fontWeight:'600',color:'#111827',cursor:'pointer',outline:'none',background:'#FFF7F2',
        display:'inline-flex',alignItems:'center',gap:'4px',lineHeight:'1.4',
    };
    const arrowBtnStyle = {
        background:'#FFF7F2',border:'1.5px solid #fed7aa',cursor:'pointer',fontSize:'14px',
        color:'#F27420',padding:'4px 8px',borderRadius:'8px',lineHeight:1,display:'flex',
        alignItems:'center',justifyContent:'center',fontWeight:'700',
    };

    const handleWrapperClick = () => {
        if (!datePickerRef.current) return;
        const input = wrapperRef.current?.querySelector('input');
        if (input) { input.click(); input.focus(); }
        else { datePickerRef.current.setOpen(true); }
    };

    return (
        <>
        <style>{css}</style>
        <div ref={wrapperRef} onClick={handleWrapperClick} className={standalone ? ('odp-wrapper' + (selected ? ' odp-has-value' : '')) : ''} style={standalone ? style : {display:'flex',alignItems:'center',gap:'8px',flex:1,minWidth:0,height:'100%',cursor:'pointer',...(style||{})}}>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{flexShrink:0}}>
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <DatePicker
                ref={datePickerRef}
                selected={selected}
                onChange={handleChange}
                dateFormat="dd/MM/yyyy"
                maxDate={new Date()}
                className={className || "orange-datepicker-input"}
                placeholderText={placeholder || "Select date"}
                onKeyDown={(e) => { if (!allowKeyboard) e.preventDefault(); }}
                readOnly={false}
                shouldCloseOnSelect={true}
                onCalendarOpen={() => setIsOpen(true)}
                onCalendarClose={() => { setIsOpen(false); setShowMonthPicker(false); setShowYearPicker(false); }}
                onClickOutside={() => { setShowMonthPicker(false); setShowYearPicker(false); }}
                popperContainer={popperContainer}
                renderCustomHeader={({
                    date, changeYear, changeMonth,
                    decreaseMonth, increaseMonth,
                    prevMonthButtonDisabled, nextMonthButtonDisabled,
                }) => (
                    <div style={{display:'flex',alignItems:'center',justifyContent:'space-between',padding:'6px 8px',gap:'6px',background:'#fff'}}>
                        <button type="button" onClick={decreaseMonth} disabled={prevMonthButtonDisabled} style={{...arrowBtnStyle,opacity:prevMonthButtonDisabled?0.3:1}}>&#8249;</button>
                        <div style={{display:'flex',alignItems:'center',gap:'6px',position:'relative'}}>
                            <button type="button" style={headerBtnStyle} onClick={() => { setShowMonthPicker(!showMonthPicker); setShowYearPicker(false); }}>
                                {months[date.getMonth()]}
                                <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <button type="button" style={headerBtnStyle} onClick={() => { setShowYearPicker(!showYearPicker); setShowMonthPicker(false); }}>
                                {date.getFullYear()}
                                <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#F27420" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            {showMonthPicker && (
                                <MonthYearDropdown value={date.getMonth()} options={months.map((m,i)=>({value:i,label:m}))} onChange={(v)=>changeMonth(v)} onClose={()=>setShowMonthPicker(false)} />
                            )}
                            {showYearPicker && (
                                <MonthYearDropdown value={date.getFullYear()} options={years.map(y=>({value:y,label:y}))} onChange={(v)=>changeYear(v)} onClose={()=>setShowYearPicker(false)} />
                            )}
                        </div>
                        <button type="button" onClick={increaseMonth} disabled={nextMonthButtonDisabled} style={{...arrowBtnStyle,opacity:nextMonthButtonDisabled?0.3:1}}>&#8250;</button>
                    </div>
                )}
            />
        </div>
        </>
    );
});

export default OrangeDatePicker;

import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider, useSelector, useDispatch } from 'react-redux';
import DataTable from 'react-data-table-component';
import axios from 'axios';
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import { ToastContainer } from 'react-toastify';
import { Modal, Button } from "react-bootstrap";
import useDataTableStyles from "../hooks/useDataTableStyles";
import useTableSearch from "./../hooks/useTableSearch";
import SpecTableLoading from "./../elements/SpecTableLoading";

const today = new Date();
const priorDate = new Date();
priorDate.setDate(today.getDate() - 30);
const formatDate = (date) => date.toISOString().slice(0, 10);

const slice = createSlice({
    name: 'actlog',
    initialState: {
        users: [], events: [],
        currentUser: "", currentEvent: "",
        toDate: formatDate(today), fromDate: formatDate(priorDate),
    },
    reducers: {
        setUsers:        (s, a) => { s.users = a.payload },
        setEvents:       (s, a) => { s.events = a.payload },
        setCurrentUser:  (s, a) => { s.currentUser = a.payload },
        setCurrentEvent: (s, a) => { s.currentEvent = a.payload },
        setToDate:       (s, a) => { s.toDate = a.payload },
        setFromDate:     (s, a) => { s.fromDate = a.payload },
    },
});

const { setUsers, setEvents, setCurrentUser, setCurrentEvent, setToDate, setFromDate } = slice.actions;
const store = configureStore({ reducer: { actlog: slice.reducer } });

// ─── Diff Modal ───────────────────────────────────────────────────────────────
function DiffModal({ show, onClose, oldData = {}, newData = {} }) {
    const allKeys = Array.from(new Set([...Object.keys(oldData), ...Object.keys(newData)]));
    return (
        <Modal show={show} onHide={onClose} size="xl" centered>
            <Modal.Header style={{borderBottom:'1px solid #f1f5f9',padding:'20px 24px'}}>
                <Modal.Title style={{fontSize:'16px',fontWeight:'800',color:'#0f172a'}}>Changes — Old vs New</Modal.Title>
            </Modal.Header>
            <Modal.Body style={{padding:'24px'}}>
                <div className="row g-4">
                    <div className="col-md-6">
                        <div style={{background:'#fef2f2',borderRadius:'10px',padding:'16px',border:'1px solid #fecaca'}}>
                            <div style={{fontSize:'12px',fontWeight:'700',color:'#dc2626',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'12px'}}>Old Values</div>
                            <table style={{width:'100%',borderCollapse:'collapse'}}>
                                <tbody>
                                    {allKeys.map(k => (
                                        <tr key={k} style={{borderBottom:'1px solid #fee2e2'}}>
                                            <td style={{padding:'6px 8px',fontSize:'12px',fontWeight:'600',color:'#374151',width:'45%'}}>{k}</td>
                                            <td style={{padding:'6px 8px',fontSize:'12px',color:'#6b7280'}}>{oldData[k] !== undefined ? String(oldData[k]) : '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div className="col-md-6">
                        <div style={{background:'#f0fdf4',borderRadius:'10px',padding:'16px',border:'1px solid #a7f3d0'}}>
                            <div style={{fontSize:'12px',fontWeight:'700',color:'#059669',textTransform:'uppercase',letterSpacing:'0.5px',marginBottom:'12px'}}>New Values</div>
                            <table style={{width:'100%',borderCollapse:'collapse'}}>
                                <tbody>
                                    {allKeys.map(k => (
                                        <tr key={k} style={{borderBottom:'1px solid #d1fae5'}}>
                                            <td style={{padding:'6px 8px',fontSize:'12px',fontWeight:'600',color:'#374151',width:'45%'}}>{k}</td>
                                            <td style={{padding:'6px 8px',fontSize:'12px',color:'#6b7280'}}>{newData[k] !== undefined ? String(newData[k]) : '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer style={{borderTop:'1px solid #f1f5f9',padding:'16px 24px'}}>
                <Button onClick={onClose} style={{background:'linear-gradient(135deg,#f97316,#ea580c)',border:'none',borderRadius:'8px',padding:'8px 20px',fontWeight:'600',fontSize:'13px'}}>
                    Close
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

// ─── Main App ─────────────────────────────────────────────────────────────────
export default function ActivityLogApp(props) {
    const dispatch = useDispatch();
    const { users, events, currentUser, currentEvent, toDate, fromDate } = useSelector(s => s.actlog);
    const [data, setData] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [rowData, setRowData] = useState({ old: {}, new: {} });

    const customStyles = useDataTableStyles();
    const { filteredData, searchText, setSearchText } = useTableSearch(data, true);

    // Load users & events dropdowns
    useEffect(() => {
        axios.get(props.listUserApi).then(r => { if(r.data.success) dispatch(setUsers(r.data.payload)); }).catch(console.error);
        axios.get(props.listEventsApi).then(r => { if(r.data.success) dispatch(setEvents(r.data.payload)); }).catch(console.error);
    }, []);

    // Fetch logs when filters change — shows orange spinner overlay while AJAX is in flight
    useEffect(() => {
        let cancelled = false;
        setIsLoading(true);
        axios.post(props.listApi, { user_id: currentUser, event: currentEvent, end_date: toDate, start_Date: fromDate })
            .then(r => { if (cancelled) return; if(r.data.success) setData(r.data.payload); })
            .catch(err => { if (!cancelled) console.error(err); })
            .finally(() => { if (!cancelled) setIsLoading(false); });
        return () => { cancelled = true; };
    }, [currentUser, currentEvent, toDate, fromDate]);

    const userOptions   = [{ value:'', label:'All Users' }, ...users.map(u => ({ value: u.id, label: `${u.first_name} ${u.last_name}` }))];
    const eventOptions  = [{ value:'', label:'All Events' }, ...events.map(e => ({ value: e.id, label: e.name }))];

    const eventBadge = (event) => {
        const map = { created: ['#ecfdf5','#059669','#a7f3d0'], updated: ['#eff6ff','#2563eb','#bfdbfe'], deleted: ['#fef2f2','#dc2626','#fecaca'] };
        const [bg, color, border] = map[event?.toLowerCase()] || ['#f8fafc','#64748b','#e2e8f0'];
        return <span style={{background:bg,color,border:`1px solid ${border}`,borderRadius:'20px',padding:'2px 10px',fontSize:'11px',fontWeight:'600',textTransform:'capitalize'}}>{event || '—'}</span>;
    };

    const hs = {fontSize:'11px',fontWeight:'700',color:'#9ca3af',letterSpacing:'0.6px',textTransform:'uppercase',whiteSpace:'nowrap'};
    const cellStyle = {fontSize:'13px',color:'#374151',fontWeight:'500'};

    const columns = [
        { name: <span style={hs}>Sl.No</span>, cell: (row, i) => <span style={{...cellStyle,color:'#6b7280'}}>{i+1}</span>, width:'65px', grow:0 },
        { name: <span style={hs}>ID</span>, selector: r => r.id, cell: r => <span style={{...cellStyle,color:'#f97316',fontWeight:'700'}}>{r.id}</span>, sortable:true, width:'75px' },
        { name: <span style={hs}>Event</span>, selector: r => r.event, cell: r => eventBadge(r.event), sortable:true, width:'110px' },
        { name: <span style={hs}>Description</span>, selector: r => r.description, cell: r => <span style={{...cellStyle,fontSize:'12px',lineHeight:'1.4'}}>{r.description}</span>, sortable:true, grow:3, wrap:true },
        { name: <span style={hs}>Log Name</span>, selector: r => r.log_name, cell: r => <span style={cellStyle}>{r.log_name}</span>, sortable:true, grow:1 },
        { name: <span style={hs}>Causer</span>, selector: r => r.causer_id, cell: r => <span style={cellStyle}>{r.causer_id || '—'}</span>, sortable:true, width:'90px' },
        { name: <span style={hs}>Subject</span>, selector: r => r.subject_id, cell: r => <span style={cellStyle}>{r.subject_id || '—'}</span>, sortable:true, width:'90px' },
        {
            name: <span style={hs}>Diff</span>,
            cell: r => (
                <button onClick={() => { setRowData({ old: r.properties?.old || {}, new: r.properties?.attributes || {} }); setShowModal(true); }}
                    style={{display:'inline-flex',alignItems:'center',gap:'4px',height:'28px',padding:'0 10px',borderRadius:'6px',background:'#fff7ed',color:'#f97316',border:'1px solid #fed7aa',fontSize:'11px',fontWeight:'600',cursor:'pointer',whiteSpace:'nowrap'}}>
                    <i className="fa fa-eye" style={{fontSize:'10px'}}></i> View
                </button>
            ),
            width:'80px', center:true,
        },
    ];

    return (
    <>
    <div style={{background:'#fff',borderRadius:'16px',border:'1px solid #e5e7eb',boxShadow:'0 2px 12px rgba(0,0,0,0.05)',overflow:'visible'}}>
        {/* Header */}
        <div style={{padding:'18px 24px',borderBottom:'1px solid #f1f5f9'}}>
            <div style={{display:'flex',alignItems:'center',gap:'10px',marginBottom:'16px'}}>
                <div style={{width:'34px',height:'34px',borderRadius:'9px',background:'linear-gradient(135deg,#f97316,#fb923c)',display:'flex',alignItems:'center',justifyContent:'center'}}>
                    <i className="fa fa-sign-in" style={{fontSize:'14px',color:'#fff'}}></i>
                </div>
                <div>
                    <div style={{fontSize:'15px',fontWeight:'800',color:'#0f172a'}}>Login Details</div>
                    <div style={{fontSize:'11px',color:'#94a3b8',fontWeight:'500'}}>User activity and audit trail</div>
                </div>
            </div>
            {/* Filters row */}
            <div style={{display:'flex',gap:'10px',flexWrap:'wrap',alignItems:'flex-end'}}>
                <div style={{flex:'1',minWidth:'160px',maxWidth:'220px'}}>
                    <div style={{fontSize:'11px',fontWeight:'600',color:'#64748b',marginBottom:'5px',textTransform:'uppercase',letterSpacing:'0.4px'}}>User</div>
                    <Select options={userOptions} styles={orangeSelectStyles} isClearable isSearchable placeholder="All Users"
                        onChange={opt => dispatch(setCurrentUser(opt?.value ?? ""))} />
                </div>
                <div style={{flex:'1',minWidth:'160px',maxWidth:'220px'}}>
                    <div style={{fontSize:'11px',fontWeight:'600',color:'#64748b',marginBottom:'5px',textTransform:'uppercase',letterSpacing:'0.4px'}}>Event</div>
                    <Select options={eventOptions} styles={orangeSelectStyles} isClearable isSearchable placeholder="All Events"
                        onChange={opt => dispatch(setCurrentEvent(opt?.value ?? ""))} />
                </div>
                <div style={{minWidth:'140px'}}>
                    <div style={{fontSize:'11px',fontWeight:'600',color:'#64748b',marginBottom:'5px',textTransform:'uppercase',letterSpacing:'0.4px'}}>From Date</div>
                    <input type="date" defaultValue={fromDate} onChange={e => dispatch(setFromDate(e.target.value))}
                        style={{height:'38px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',padding:'0 10px',fontSize:'13px',outline:'none'}}
                        onFocus={e=>e.target.style.borderColor='#f97316'} onBlur={e=>e.target.style.borderColor='#e2e8f0'} />
                </div>
                <div style={{minWidth:'140px'}}>
                    <div style={{fontSize:'11px',fontWeight:'600',color:'#64748b',marginBottom:'5px',textTransform:'uppercase',letterSpacing:'0.4px'}}>To Date</div>
                    <input type="date" defaultValue={toDate} onChange={e => dispatch(setToDate(e.target.value))}
                        style={{height:'38px',width:'100%',borderRadius:'10px',border:'1.5px solid #e2e8f0',padding:'0 10px',fontSize:'13px',outline:'none'}}
                        onFocus={e=>e.target.style.borderColor='#f97316'} onBlur={e=>e.target.style.borderColor='#e2e8f0'} />
                </div>
                <div style={{flex:'1',minWidth:'180px',maxWidth:'260px'}}>
                    <div style={{fontSize:'11px',fontWeight:'600',color:'#64748b',marginBottom:'5px',textTransform:'uppercase',letterSpacing:'0.4px'}}>Search</div>
                    <div style={{position:'relative'}}>
                        <i className="fa fa-search" style={{position:'absolute',left:'11px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',fontSize:'12px',pointerEvents:'none'}}></i>
                        <input type="text" placeholder="Search logs..." value={searchText} onChange={e=>setSearchText(e.target.value)}
                            style={{height:'38px',width:'100%',paddingLeft:'34px',paddingRight:'32px',borderRadius:'10px',border:'1.5px solid #e2e8f0',fontSize:'13px',background:'#f8fafc',outline:'none'}}
                            onFocus={e=>{e.target.style.borderColor='#f97316';e.target.style.background='#fff';}}
                            onBlur={e=>{e.target.style.borderColor='#e2e8f0';e.target.style.background='#f8fafc';}} />
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'10px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
                    </div>
                </div>
            </div>
        </div>
        {/* Table */}
        <div style={{position:'relative'}}>
        <DataTable columns={columns} data={filteredData} pagination highlightOnHover customStyles={customStyles}
            paginationPerPage={10} paginationRowsPerPageOptions={[5, 10, 20, 50]}
            progressPending={isLoading && data.length === 0}
            progressComponent={<SpecTableLoading label="Loading activity…" />}
            noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#94a3b8',fontSize:'14px'}}>No activity logs found</div>}
        />
        {isLoading && data.length > 0 && (
            <div style={{position:'absolute',inset:0,background:'rgba(255,255,255,0.55)',display:'flex',alignItems:'flex-start',justifyContent:'center',pointerEvents:'none',paddingTop:'72px',zIndex:5}}>
                <div style={{display:'inline-flex',alignItems:'center',gap:'10px',padding:'10px 18px',background:'#ffffff',border:'1px solid #fed7aa',borderRadius:'9999px',color:'#ea580c',fontSize:'13px',fontWeight:'600',boxShadow:'0 4px 12px rgba(15,23,42,0.10)'}}>
                    <i className="fa fa-spinner fa-spin" style={{fontSize:'14px'}}></i>
                    <span>Loading…</span>
                </div>
            </div>
        )}
        </div>
    </div>

    <DiffModal show={showModal} onClose={() => setShowModal(false)} oldData={rowData.old} newData={rowData.new} />
    <ToastContainer position="top-right" autoClose={3000} />
    </>
    );
}

if (document.getElementById('activity-log-app')) {
    const id = "activity-log-app";
    const root = createRoot(document.getElementById(id));
    const props = Object.assign({}, document.getElementById(id).dataset);
    root.render(<Provider store={store}><ActivityLogApp {...props} /></Provider>);
}

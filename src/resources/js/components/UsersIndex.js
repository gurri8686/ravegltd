import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider } from 'react-redux';
import DataTable from 'react-data-table-component';
import axios from 'axios';
import { ToastContainer, toast } from 'react-toastify';
import useDataTableStyles from "../hooks/useDataTableStyles";
import useTableSearch from "./../hooks/useTableSearch";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";

// Base font for the whole Users UI — the artifact spec used var(--ff); the project
// has no such CSS var, so it resolves to the app's Nunito stack.
const FF = "'Nunito', sans-serif";

const slice = createSlice({
    name: 'properties',
    initialState: { refresh: 0 },
    reducers: { triggerRefresh: (state) => { state.refresh = Date.now(); } },
});

const store = configureStore({ reducer: { properties: slice.reducer } });

/* ── Confirm Modal ─────────────────────────────────────────── */
function ConfirmModal({ user, onConfirm, onCancel, loading }) {
  if (!user) return null;
  const name = `${user.first_name || ''} ${user.last_name || ''}`.trim();
  const cmMobile = typeof window !== 'undefined' && window.innerWidth <= 767;
  return (
    <div style={{position:'fixed',inset:0,zIndex:99999,display:'flex',alignItems:'center',justifyContent:'center',padding:'20px'}}>
      <div onClick={onCancel} style={{position:'absolute',inset:0,background:'rgba(0,0,0,0.45)',backdropFilter:'blur(4px)'}}></div>
      <div style={{position:'relative',background:'#fff',borderRadius:'16px',width:'100%',maxWidth:'420px',padding:'44px 32px 32px',textAlign:'center',boxShadow:'0 20px 60px rgba(0,0,0,0.15)',fontFamily:FF}}>
        <div style={{width:'88px',height:'88px',borderRadius:'50%',border:'3px solid #ef4444',display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 22px',boxShadow:'0 0 0 8px rgba(239,68,68,0.08)',background:'#fff'}}>
          <i className="fa fa-trash" style={{fontSize:'30px',color:'#ef4444'}}></i>
        </div>
        <div style={{fontSize:'22px',fontWeight:'700',color:'#1f2937',marginBottom:'10px'}}>Are you sure?</div>
        <div style={{fontSize:'14px',color:'#6b7280',lineHeight:'1.7',marginBottom:'28px'}}>
          You are about to permanently delete <span style={{fontWeight:'700',color:'#1f2937'}}>"{name}"</span>.<br/>
          <span style={{color:'#ef4444',fontWeight:'600'}}>This action cannot be undone!</span>
        </div>
        <div style={{display:'flex',justifyContent:'center',gap:'12px',flexWrap: cmMobile ? 'wrap' : 'nowrap'}}>
          <button onClick={onCancel} disabled={loading}
            style={{...(cmMobile ? {flex:'1 1 120px'} : {width:'130px'}),height:'46px',borderRadius:'10px',border:'1px solid #d1d5db',background:'#fff',color:'#6b7280',fontWeight:'600',fontSize:'14px',cursor:'pointer',outline:'none'}}>
            Cancel
          </button>
          <button onClick={onConfirm} disabled={loading}
            style={{...(cmMobile ? {flex:'1 1 140px'} : {width:'160px'}),height:'46px',borderRadius:'10px',border:'none',background:loading?'#fca5a5':'#ef4444',color:'#fff',fontWeight:'700',fontSize:'14px',cursor:loading?'not-allowed':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:loading?'none':'0 4px 14px rgba(239,68,68,0.4)'}}>
            {loading
              ? <><i className="fa fa-spinner fa-spin" style={{fontSize:'13px'}}></i> Deleting...</>
              : <>Yes, delete it!</>}
          </button>
        </div>
      </div>
    </div>
  );
}

/* ── Actions Dropdown — 3-dots menu (Edit + Delete) ────────── */
function ActionsDropdown({ row, onDeleteClick }) {
  const iconBtn = (color) => ({
    width:'32px',height:'32px',borderRadius:'8px',
    border:'1px solid '+color+'33',
    background:color+'12',
    display:'inline-flex',alignItems:'center',justifyContent:'center',
    textDecoration:'none',color:color,fontSize:'13px',
    transition:'all 0.15s',cursor:'pointer',outline:'none',
  });
  return (
    <div style={{display:'flex',gap:'6px',justifyContent:'flex-end'}}>
      <a href={`/management/users/edit/${row.id}/edit`} title="Edit user" style={{...iconBtn('rgb(234, 88, 12)'), border:'1px solid rgba(234,88,12,0.2)', background:'rgba(234,88,12,0.08)'}}
        onMouseOver={e => { e.currentTarget.style.background='rgb(234, 88, 12)'; e.currentTarget.style.color='#fff'; e.currentTarget.style.borderColor='rgb(234, 88, 12)'; }}
        onMouseOut={e => { e.currentTarget.style.background='rgba(234,88,12,0.08)'; e.currentTarget.style.color='rgb(234, 88, 12)'; e.currentTarget.style.borderColor='rgba(234,88,12,0.2)'; }}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
      </a>
      <button type="button" title="Delete user" onClick={() => onDeleteClick(row)} style={iconBtn('#dc2626')}
        onMouseOver={e => { e.currentTarget.style.background='#dc2626'; e.currentTarget.style.color='#fff'; e.currentTarget.style.borderColor='#dc2626'; }}
        onMouseOut={e => { e.currentTarget.style.background='#dc262612'; e.currentTarget.style.color='#dc2626'; e.currentTarget.style.borderColor='#dc262633'; }}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
      </button>
    </div>
  );
}

/* ── Custom Pagination — exact spec footer ─────────────────── */
function UsersPagination({ rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage }) {
  const totalPages = Math.ceil(rowCount / rowsPerPage) || 1;
  const from = rowCount === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
  const to   = Math.min(currentPage * rowsPerPage, rowCount);
  const isFirst = currentPage <= 1;
  const isLast  = currentPage >= totalPages;
  const navBtn = (disabled) => ({width:'28px',height:'28px',borderRadius:'7px',background:'#fff',border:'1px solid #e8e8ec',color:disabled?'#c8c8cf':'#6b7280',cursor:disabled?'not-allowed':'pointer',display:'flex',alignItems:'center',justifyContent:'center',padding:0});

  return (
    <div style={{padding:'12px 22px',background:'#fafafb',borderTop:'1px solid #eeeeef',display:'flex',alignItems:'center',justifyContent:'flex-end',gap:'14px',fontFamily:FF}}>
      <div style={{display:'flex',alignItems:'center',gap:'10px'}}>
        <span style={{fontSize:'12.5px',color:'#6b7280',fontWeight:'600'}}>Rows per page:</span>
        <select value={rowsPerPage} onChange={e => onChangeRowsPerPage(Number(e.target.value), currentPage)}
          style={{height:'30px',padding:'0 26px 0 10px',borderRadius:'7px',background:'#fff7f0',color:'rgb(234, 88, 12)',border:'1px solid #f6c9a8',fontSize:'12.5px',fontWeight:'700',fontFamily:'inherit',cursor:'pointer'}}>
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
      </div>
      <span style={{fontSize:'12.5px',color:'#6b7280',fontWeight:'600'}}>{from}-{to} of {rowCount}</span>
      <div style={{display:'flex',gap:'2px'}}>
        <button disabled={isFirst} onClick={() => onChangePage(1)} style={navBtn(isFirst)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg>
        </button>
        <button disabled={isFirst} onClick={() => onChangePage(currentPage - 1)} style={navBtn(isFirst)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
        <button disabled={isLast} onClick={() => onChangePage(currentPage + 1)} style={navBtn(isLast)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
        <button disabled={isLast} onClick={() => onChangePage(totalPages)} style={navBtn(isLast)}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13 17l5-5-5-5M6 17l5-5-5-5"/></svg>
        </button>
      </div>
    </div>
  );
}

/* ── Table loading state — quick, lightweight spinner ──────── */
function UsersLoading() {
  return <SpecTableLoading label="Loading users…" />;
}

/* ── Main App ──────────────────────────────────────────────── */
export default function UsersIndex(props) {
	const [data, setData] = useState([]);
	const [loading, setLoading] = useState(true);
	const [deleteTarget, setDeleteTarget] = useState(null);
	const [deleting, setDeleting] = useState(false);
	const customStyles = useDataTableStyles();
	const { filteredData, searchText, setSearchText } = useTableSearch(data, true);
	const [isMobile, setIsMobile] = useState(typeof window !== 'undefined' && window.innerWidth <= 767);
	useEffect(() => {
		const onResize = () => setIsMobile(window.innerWidth <= 767);
		window.addEventListener('resize', onResize);
		return () => window.removeEventListener('resize', onResize);
	}, []);

	function handleDeleteClick(row) { setDeleteTarget(row); }
	function handleCancel() { setDeleteTarget(null); }

	function handleConfirmDelete() {
		if (!deleteTarget) return;
		setDeleting(true);
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
		const fd = new FormData();
		fd.append('_method', 'DELETE');
		fd.append('_token', csrfToken);
		fetch(`/management/users/delete/${deleteTarget.id}`, {
			method: 'POST', body: fd,
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(r => r.json())
		.then(res => {
			setDeleting(false);
			setDeleteTarget(null);
			if (res.success !== false) {
				setData(prev => prev.filter(r => r.id !== deleteTarget.id));
				toast.success('User deleted successfully.');
			} else {
				toast.error(res.message || 'Failed to delete user.');
			}
		})
		.catch(() => { setDeleting(false); setDeleteTarget(null); toast.error('Failed to delete user.'); });
	}

	const hs = {fontSize:'11px',fontWeight:'500',color:'#1f2937',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13.5px',color:'#374151'};

	// Exact-spec overrides — orange header tray, footer, row borders. Kept local to this
	// component so other tables sharing useDataTableStyles are not affected.
	const usersStyles = {
		...customStyles,
		table: { style: { ...customStyles.table.style, fontFamily:FF } },
		headRow: { style: { backgroundColor:'#fafbfc', borderBottom:'2px solid #eef2f7', minHeight:'44px' } },
		headCells: { style: { ...customStyles.headCells.style, fontSize:'11px', fontWeight:'700', color:'#64748b', letterSpacing:'0.7px', paddingLeft:'18px', paddingRight:'18px' } },
		rows: { ...customStyles.rows, style: { ...customStyles.rows.style, minHeight:'64px', borderBottomColor:'#f0f0f2' } },
		cells: { style: { ...customStyles.cells.style, fontSize:'13.5px', paddingLeft:'18px', paddingRight:'18px' } },
		pagination: { ...customStyles.pagination, style: { ...customStyles.pagination.style, backgroundColor:'#fafafb', borderTop:'1px solid #eeeeef', minHeight:'56px' } },
	};

	// Role name → dot colour (matches the spec's coloured status dots).
	const roleDot = (roleName) => {
		const r = (roleName || '').toLowerCase();
		if (r.includes('admin'))   return '#475569';
		if (r.includes('manager')) return '#d97706';
		return '#2563eb';
	};

	const columns = [
		{ name: <span style={hs}>Sl.No</span>, cell: (_, index) => <span style={{...cellStyle,color:'#374151'}}>{index + 1}</span>, width:'70px', grow:0, hide:768 },
		{ name: <span style={hs}>User</span>, selector: row => row.first_name, cell: row => {
			const name = `${row.first_name || ''} ${row.last_name || ''}`.trim();
			const initial = (row.first_name || '?').charAt(0).toUpperCase();
			return (
				<div style={{display:'flex',alignItems:'center',gap:'12px'}}>
					<span style={{width:'36px',height:'36px',borderRadius:'50%',flexShrink:0,background:'#ea580c',color:'#fff',display:'inline-flex',alignItems:'center',justifyContent:'center',fontWeight:'800',fontSize:'15px',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.25),0 4px 10px -3px rgba(234,88,12,0.4)'}}>{initial}</span>
					<div style={{minWidth:0}}>
						<div style={{fontWeight:'600',color:'#0f1115',fontSize:'13.5px'}}>{name}</div>
						<div style={{fontSize:'11.5px',color:'#6b7280',marginTop:'1px',whiteSpace:'nowrap',overflow:'hidden',textOverflow:'ellipsis'}}>{row.email}</div>
					</div>
				</div>
			);
		}, sortable:true, grow:1, minWidth:'200px' },
		{ name: <span style={hs}>Role</span>, selector: row => row.roles_list, cell: row => (
			<div style={{display:'flex',alignItems:'center',height:'100%'}}>
				<span style={{display:'inline-flex',alignItems:'center',gap:'7px',height:'26px',padding:'0 12px',borderRadius:'99px',background:'#fff',color:'#374151',border:'1px solid #e8e8ec',fontSize:'12.5px',fontWeight:'700',lineHeight:1,whiteSpace:'nowrap'}}>
					<span style={{width:'7px',height:'7px',borderRadius:'50%',background:roleDot(row.roles_list),flexShrink:0}}></span>
					{row.roles_list || '—'}
				</span>
			</div>
		), sortable:true, width:'170px', grow:0 },
		{ name: <span style={hs}>Status</span>, selector: row => row.is_active, cell: row => row.is_active == 1
			? <span style={{display:'inline-flex',alignItems:'center',gap:'6px',padding:'4px 12px',borderRadius:'99px',background:'#e8f8ee',color:'#15803d',border:'1px solid #bde5c9',fontSize:'11.5px',fontWeight:'800',letterSpacing:'0.2px'}}><span style={{width:'6px',height:'6px',borderRadius:'50%',background:'#16a34a'}}></span>Active</span>
			: <span style={{display:'inline-flex',alignItems:'center',gap:'6px',padding:'4px 12px',borderRadius:'99px',background:'#fef2f2',color:'#b91c1c',border:'1px solid #f8d2d2',fontSize:'11.5px',fontWeight:'800',letterSpacing:'0.2px'}}><span style={{width:'6px',height:'6px',borderRadius:'50%',background:'#dc2626'}}></span>Disabled</span>,
			sortable:true, width:'130px', grow:0 },
		{ name: <span style={hs}>Last Login</span>, selector: row => row.last_login_label, cell: row => <span style={{fontSize:'12.5px',color:'#6b7280'}}>{row.last_login_label || 'Not logged in yet'}</span>, sortable:true, width:'170px', grow:0, hide:768 },
		{ name: <span style={hs}>Actions</span>, cell: row => <div style={{display:'flex',justifyContent:'flex-end',width:'100%'}}><ActionsDropdown row={row} onDeleteClick={handleDeleteClick} /></div>, right:true, width:'120px', grow:0 },
	];

	useEffect(() => {
		setLoading(true);
		axios.get(props.listApi)
			.then(r => { if(r.data.success) setData(r.data.payload); })
			.catch(console.error)
			.finally(() => setLoading(false));
	}, []);

	return (
	<>
	<ConfirmModal user={deleteTarget} onConfirm={handleConfirmDelete} onCancel={handleCancel} loading={deleting} />
	<style>{`@media (max-width:767px){
		.users-card-wrap{background:transparent !important;border:none !important;box-shadow:none !important;border-radius:0 !important;overflow:visible !important;}
		.users-head-sec{background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);padding:14px 16px !important;margin-bottom:12px;}
		.users-search-sec{background:transparent !important;border:none !important;box-shadow:none !important;border-radius:0 !important;padding:0 0 12px !important;margin-bottom:0;}
		.users-search-input input{background:#fff !important;border:1px solid #ececf0 !important;border-radius:10px !important;padding:0 12px 0 36px !important;font-weight:500 !important;font-size:13px !important;color:#374151 !important;}
		.users-search-input input::placeholder{color:#9ca3af !important;font-weight:500 !important;}
		.users-search-icon{top:50% !important;transform:translateY(-50%) !important;display:flex !important;align-items:center !important;}
		.users-search-icon svg{width:15px !important;height:15px !important;stroke:#9ca3af !important;}
		.users-table-sec{background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);}
		.users-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}.users-table-scroll > div{min-width:580px;}
	}`}</style>
	<div className="users-card-wrap" style={{background:'#fff',borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',overflow:'hidden',fontFamily:FF}}>
		{/* Header — title row */}
		<div className="users-head-sec" style={{padding:'18px 22px 14px',display:'flex',alignItems:'center',gap:'14px'}}>
			<span style={{width:'42px',height:'42px',borderRadius:'11px',background:'#ea580c',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45)'}}>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
			</span>
			<div style={{flex:'1 1 0%',minWidth:0}}>
				<h2 style={{margin:0,fontSize:'18px',fontWeight:'800',color:'#0f1115',letterSpacing:'-0.2px'}}>Users</h2>
				<p style={{margin:'2px 0 0',fontSize:'13.5px',color:'#6b7280'}}>Manage system users, roles and access</p>
			</div>
		</div>
		{/* Header — search + Create row */}
		<div className="users-search-sec" style={{padding:'4px 22px 16px',display:'flex',alignItems:'center',gap:'12px'}}>
			<div className="users-search-input" style={{flex:'1 1 0%',position:'relative'}}>
				<span className="users-search-icon" style={{position:'absolute',left:'12px',top:'11px',color:'#9ca3af'}}>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
				</span>
				<input type="text" placeholder={isMobile ? "Search users…" : "Search by name, email or role…"} value={searchText} onChange={e=>setSearchText(e.target.value)}
					style={{width:'100%',height:'40px',padding:'0 14px 0 38px',borderRadius:'99px',border:'1px solid #e8e8ec',background:'#fafafb',fontSize:'13.5px',color:'#0f1115',outline:'none',fontFamily:'inherit',boxSizing:'border-box'}}
					onFocus={e=>{e.target.style.borderColor='#ea580c';e.target.style.background='#fff';}}
					onBlur={e=>{e.target.style.borderColor='#e8e8ec';e.target.style.background='#fafafb';}}
				/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'14px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
			</div>
			<a href="/management/users/create/create" style={{height:'42px',padding:'0 18px',borderRadius:'99px',background:'#ea580c',color:'#fff',border:'1px solid transparent',fontWeight:'700',fontSize:'13.5px',letterSpacing:'-0.1px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',textDecoration:'none',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45)',whiteSpace:'nowrap',flexShrink:0}}>
				<svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14"/></svg> Create User
			</a>
		</div>
		{/* Table */}
		<div className="users-table-sec users-table-scroll">
		<DataTable columns={columns} data={filteredData} pagination highlightOnHover customStyles={usersStyles}
			responsive={!isMobile}
			paginationPerPage={10} paginationRowsPerPageOptions={[10, 25, 50, 100]}
			paginationComponent={SpecPagination}
			progressPending={loading} progressComponent={<UsersLoading />}
			persistTableHead
			noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#9ca3af',fontSize:'14px'}}>No users found</div>}
		/>
		</div>
	</div>
	<ToastContainer position="top-right" autoClose={3000} />
	</>
	);
}

if (document.getElementById('users-index-app')) {
    const id = "users-index-app";
    const root = createRoot(document.getElementById(id));
    const props = Object.assign({}, document.getElementById(id).dataset);
    root.render(<Provider store={store}><UsersIndex {...props} /></Provider>);
}

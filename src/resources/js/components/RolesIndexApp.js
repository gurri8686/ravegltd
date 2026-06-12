import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider } from 'react-redux';
import DataTable from 'react-data-table-component';
import axios from 'axios';
import { ToastContainer, toast } from 'react-toastify';
import useDataTableStyles from "../hooks/useDataTableStyles";
import useTableSearch from "./../hooks/useTableSearch";
import Icon from "./../hooks/Icons";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";

// Base font for the whole Roles UI — the artifact spec used var(--ff); the project
// has no such CSS var, so it resolves to the app's Nunito stack.
const FF = "'Nunito', sans-serif";

const slice = createSlice({
    name: 'properties',
    initialState: { refresh: 0 },
    reducers: { triggerRefresh: (state) => { state.refresh = Date.now(); } },
});

const store = configureStore({ reducer: { properties: slice.reducer } });

/* ── Confirm Modal ─────────────────────────────────────────── */
function ConfirmModal({ role, onConfirm, onCancel, loading }) {
  if (!role) return null;
  return (
    <div style={{position:'fixed',inset:0,zIndex:99999,display:'flex',alignItems:'center',justifyContent:'center',padding:'20px'}}>
      {/* Backdrop */}
      <div onClick={onCancel} style={{position:'absolute',inset:0,background:'rgba(0,0,0,0.45)',backdropFilter:'blur(4px)'}}></div>
      {/* Card */}
      <div style={{position:'relative',background:'#fff',borderRadius:'16px',width:'100%',maxWidth:'420px',padding:'44px 32px 32px',textAlign:'center',boxShadow:'0 20px 60px rgba(0,0,0,0.15)',fontFamily:FF}}>
        {/* Icon circle — SweetAlert style */}
        <div style={{width:'88px',height:'88px',borderRadius:'50%',border:'3px solid #ef4444',display:'flex',alignItems:'center',justifyContent:'center',margin:'0 auto 22px',boxShadow:'0 0 0 8px rgba(239,68,68,0.08)',background:'#fff'}}>
          <i className="fa fa-trash" style={{fontSize:'30px',color:'#ef4444'}}></i>
        </div>
        {/* Title */}
        <div style={{fontSize:'22px',fontWeight:'700',color:'#1f2937',marginBottom:'10px'}}>Are you sure?</div>
        {/* Description */}
        <div style={{fontSize:'14px',color:'#6b7280',lineHeight:'1.7',marginBottom:'28px'}}>
          You are about to permanently delete <span style={{fontWeight:'700',color:'#1f2937'}}>"{role.name}"</span>.<br/>
          <span style={{color:'#ef4444',fontWeight:'600'}}>This action cannot be undone!</span>
        </div>
        {/* Buttons */}
        <div style={{display:'flex',justifyContent:'center',gap:'12px'}}>
          <button onClick={onCancel} disabled={loading}
            style={{width:'130px',height:'46px',borderRadius:'10px',border:'1px solid #d1d5db',background:'#fff',color:'#6b7280',fontWeight:'600',fontSize:'14px',cursor:'pointer',outline:'none',transition:'all 0.15s'}}
            onMouseOver={e=>e.currentTarget.style.background='#f9fafb'}
            onMouseOut={e=>e.currentTarget.style.background='#fff'}>
            Cancel
          </button>
          <button onClick={onConfirm} disabled={loading}
            style={{width:'160px',height:'46px',borderRadius:'10px',border:'none',background:loading?'#fca5a5':'#ef4444',color:'#fff',fontWeight:'700',fontSize:'14px',cursor:loading?'not-allowed':'pointer',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',outline:'none',boxShadow:loading?'none':'0 4px 14px rgba(239,68,68,0.4)',transition:'all 0.15s'}}
            onMouseOver={e=>{ if(!loading) e.currentTarget.style.background='#dc2626'; }}
            onMouseOut={e=>{ if(!loading) e.currentTarget.style.background='#ef4444'; }}>
            {loading
              ? <><i className="fa fa-spinner fa-spin" style={{fontSize:'13px'}}></i> Deleting...</>
              : <>Yes, delete it!</>}
          </button>
        </div>
      </div>
    </div>
  );
}

/* ── Action icons (inline) ──────────────────────────────────── */
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
      <a href={`/management/roles/role/edit/${row.id}/edit`} title="Edit role" style={{...iconBtn('rgb(234, 88, 12)'), border:'1px solid rgba(234,88,12,0.2)', background:'rgba(234,88,12,0.08)'}}
        onMouseOver={e => { e.currentTarget.style.background='rgb(234, 88, 12)'; e.currentTarget.style.color='#fff'; e.currentTarget.style.borderColor='rgb(234, 88, 12)'; }}
        onMouseOut={e => { e.currentTarget.style.background='rgba(234,88,12,0.08)'; e.currentTarget.style.color='rgb(234, 88, 12)'; e.currentTarget.style.borderColor='rgba(234,88,12,0.2)'; }}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
      </a>
      <button type="button" title="Delete role" onClick={() => onDeleteClick(row)} style={iconBtn('#dc2626')}
        onMouseOver={e => { e.currentTarget.style.background='#dc2626'; e.currentTarget.style.color='#fff'; e.currentTarget.style.borderColor='#dc2626'; }}
        onMouseOut={e => { e.currentTarget.style.background='#dc262612'; e.currentTarget.style.color='#dc2626'; e.currentTarget.style.borderColor='#dc262633'; }}>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
      </button>
    </div>
  );
}

/* ── Custom Pagination — exact spec footer ─────────────────── */
function RolesPagination({ rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage }) {
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
function RolesLoading() {
  return <SpecTableLoading label="Loading roles…" />;
}

/* ── Main App ──────────────────────────────────────────────── */
export default function RolesIndexApp(props) {
	const [data, setData] = useState([]);
	const [loading, setLoading] = useState(true);
	const [deleteTarget, setDeleteTarget] = useState(null);
	const [deleting, setDeleting] = useState(false);
	// Mobile detection (≤767px) — used ONLY to tighten columns on phones so the
	// Name column gets enough room. Desktop layout is unaffected.
	const [isMobile, setIsMobile] = useState(() => (typeof window !== 'undefined' && window.innerWidth <= 767));
	useEffect(() => {
		const onResize = () => setIsMobile(window.innerWidth <= 767);
		window.addEventListener('resize', onResize);
		return () => window.removeEventListener('resize', onResize);
	}, []);
	const customStyles = useDataTableStyles();
	const { filteredData, searchText, setSearchText } = useTableSearch(data, true);

	function handleDeleteClick(row) { setDeleteTarget(row); }
	function handleCancel() { setDeleteTarget(null); }

	function handleConfirmDelete() {
		if (!deleteTarget) return;
		setDeleting(true);
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
		const fd = new FormData();
		fd.append('_method', 'DELETE');
		fd.append('_token', csrfToken);
		fetch(`/management/roles/role/delete/${deleteTarget.id}`, {
			method: 'POST', body: fd,
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(r => r.json())
		.then(res => {
			setDeleting(false);
			setDeleteTarget(null);
			if (res.success !== false) {
				setData(prev => prev.filter(r => r.id !== deleteTarget.id));
				toast.success('Role deleted successfully.');
			}
		})
		.catch(() => { setDeleting(false); setDeleteTarget(null); toast.error('Failed to delete role.'); });
	}

	const hs = {fontSize:'11px',fontWeight:'500',color:'#1f2937',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13.5px',color:'#374151'};

	// Per-role avatar colours (mobile reference UI) — cycled by row id so each role
	// gets a distinct soft-tinted circle like the reference design.
	const avatarPalette = [
		{ bg:'#ede9fe', ink:'#7c3aed' }, // violet
		{ bg:'#dbeafe', ink:'#2563eb' }, // blue
		{ bg:'#dcfce7', ink:'#16a34a' }, // green
		{ bg:'#fef3c7', ink:'#d97706' }, // amber
		{ bg:'#fce7f3', ink:'#db2777' }, // pink
		{ bg:'#ccfbf1', ink:'#0d9488' }, // teal
	];
	const avatarColor = (id) => avatarPalette[(Number(id) || 0) % avatarPalette.length];

	// Exact-spec overrides — orange header tray, footer, row borders. Kept local to this
	// component so other tables sharing useDataTableStyles are not affected.
	const rolesStyles = {
		...customStyles,
		table: { style: { ...customStyles.table.style, fontFamily:FF } },
		headRow: { style: { backgroundColor:'#fafbfc', borderBottom:'2px solid #eef2f7', minHeight:'44px' } },
		headCells: { style: { ...customStyles.headCells.style, fontSize:'11px', fontWeight:'700', color:'#64748b', letterSpacing:'0.7px', paddingLeft: isMobile ? '10px' : '18px', paddingRight: isMobile ? '6px' : '18px' } },
		rows: { ...customStyles.rows, style: { ...customStyles.rows.style, minHeight:'58px', borderBottomColor:'#f0f0f2' } },
		cells: { style: { ...customStyles.cells.style, fontSize:'13.5px', paddingLeft: isMobile ? '10px' : '18px', paddingRight: isMobile ? '6px' : '18px' } },
		pagination: { ...customStyles.pagination, style: { ...customStyles.pagination.style, backgroundColor:'#fafafb', borderTop:'1px solid #eeeeef', minHeight:'56px' } },
	};

	const columns = [
		{ name: <span style={hs}>Sl.No</span>, cell: (_, index) => <span style={{...cellStyle,color:'#374151'}}>{index + 1}</span>, width:'70px', grow:0, hide:768 },
		{ name: <span style={hs}>{isMobile ? '#' : '#ID'}</span>, selector: row => row.id, cell: (row, index) => <span style={{fontSize:'12.5px',color: isMobile ? '#9ca3af' : 'rgb(234, 88, 12)',fontFamily:"ui-monospace,SFMono-Regular,Menlo,monospace",fontWeight:'800'}}>{isMobile ? (index + 1) : row.id}</span>, sortable: !isMobile, width: isMobile ? '40px' : '80px', grow:0 },
		{ name: <span style={{...hs,marginLeft: isMobile ? '0' : '14px'}}>{isMobile ? 'Role' : 'Name'}</span>, selector: row => row.name, cell: row => {
			const av = avatarColor(row.id);
			return (
			<div style={{display:'flex',alignItems:'center',gap:'10px',marginLeft: isMobile ? '0' : '14px'}}>
				<span style={{width:'30px',height:'30px',borderRadius: isMobile ? '50%' : '8px',background: isMobile ? av.bg : '#fff1e6',color: isMobile ? av.ink : '#ea580c',border: isMobile ? 'none' : '1px solid #f6c9a8',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
				</span>
				<div style={{minWidth:0}}>
					<div style={{fontWeight:'700',color:'#0f1115',fontSize:'13.5px',whiteSpace:'nowrap'}}>{row.name}</div>
					<div style={{display:'flex',gap:'6px',marginTop:'2px',alignItems:'center'}}>
						<span style={{fontSize:'11px',color:'#6b7280',fontWeight:'600',whiteSpace:'nowrap'}}>{row.permissions_count || 0} permissions</span>
					</div>
				</div>
			</div>
			);
		}, sortable:true, grow:1, minWidth: isMobile ? '140px' : undefined },
		{ name: <span style={{...hs,marginLeft: isMobile ? '0' : '-16px'}}>Users</span>, selector: row => row.customerrole_count, cell: row => {
			const n = row.customerrole_count || 0;
			const c = n > 0
				? {bg:'#fff1e6',ink:'rgb(234, 88, 12)',line:'#f6c9a8'}
				: {bg:'#f4f4f6',ink:'#9ca3af',line:'#e8e8ec'};
			return <span style={{display:'inline-flex',alignItems:'center',gap:'6px',padding:'3px 10px',borderRadius:'99px',background:c.bg,color:c.ink,border:`1px solid ${c.line}`,fontFamily:"ui-monospace,SFMono-Regular,Menlo,monospace",fontSize:'12px',fontWeight:'800',marginLeft: isMobile ? '0' : '-16px'}}>{n}</span>;
		}, sortable:true, center:true, width: isMobile ? '70px' : '110px', grow:0 },
		{ name: <span style={hs}>Created</span>, selector: row => row.created_at, cell: row => <span style={{...cellStyle,color:'#6b7280'}}>{row.created_at}</span>, sortable:true, width:'160px', grow:0, hide:768 },
		{ name: <span style={hs}>Actions</span>, cell: row => <div style={{display:'flex',justifyContent:'flex-end',width:'100%'}}><ActionsDropdown row={row} onDeleteClick={handleDeleteClick} /></div>, right:true, width: isMobile ? '84px' : '140px', grow:0 },
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
	<ConfirmModal role={deleteTarget} onConfirm={handleConfirmDelete} onCancel={handleCancel} loading={deleting} />
	<div style={{background: isMobile ? 'transparent' : '#fff',borderRadius: isMobile ? '0' : '0 0 16px 16px',border: isMobile ? 'none' : '1px solid #eaecf2',borderTop: isMobile ? 'none' : 'none',boxShadow: isMobile ? 'none' : '0 4px 16px rgba(0,0,0,0.04)',overflow: isMobile ? 'visible' : 'hidden',fontFamily:FF,display: isMobile ? 'flex' : 'block',flexDirection: isMobile ? 'column' : undefined,gap: isMobile ? '12px' : undefined}}>
		{/* Header — title row */}
		<div style={isMobile
			? {padding:'16px',display:'flex',alignItems:'center',gap:'14px',background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)'}
			: {padding:'18px 22px 14px',display:'flex',alignItems:'center',gap:'14px'}}>
			<span style={{width:'42px',height:'42px',borderRadius:'11px',background:'#ea580c',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45)'}}>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
			</span>
			<div style={{flex:'1 1 0%',minWidth:0}}>
				<h2 style={{margin:0,fontSize:'18px',fontWeight:'800',color:'#0f1115',letterSpacing:'-0.2px'}}>Roles</h2>
				<p style={{margin:'2px 0 0',fontSize:'13.5px',color:'#6b7280'}}>Manage user roles and what they can do</p>
			</div>
		</div>
		{/* Header — search + Create row */}
		<div style={isMobile
			? {padding:'0',display:'flex',alignItems:'center',gap:'10px'}
			: {padding:'4px 22px 16px',display:'flex',alignItems:'center',gap:'12px'}}>
			<div style={{flex:'1 1 0%',position:'relative'}}>
				<span style={{position:'absolute',left: isMobile ? '13px' : '12px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',display:'flex',alignItems:'center'}}>
					<svg width={isMobile ? '15' : '16'} height={isMobile ? '15' : '16'} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
				</span>
				<input type="text" placeholder={isMobile ? 'Search roles…' : 'Search by role name, ID or description…'} value={searchText} onChange={e=>setSearchText(e.target.value)}
					style={isMobile
						? {width:'100%',height:'44px',padding:'0 14px 0 38px',borderRadius:'12px',border:'1px solid #e8e8ec',background:'#fff',fontSize:'13px',fontWeight:'500',color:'#475569',outline:'none',fontFamily:'inherit',boxSizing:'border-box'}
						: {width:'100%',height:'40px',padding:'0 14px 0 38px',borderRadius:'99px',border:'1px solid #e8e8ec',background:'#fafafb',fontSize:'13.5px',color:'#0f1115',outline:'none',fontFamily:'inherit',boxSizing:'border-box'}}
					onFocus={e=>{e.target.style.borderColor='#ea580c';e.target.style.background='#fff';}}
					onBlur={e=>{e.target.style.borderColor='#e8e8ec';e.target.style.background= isMobile ? '#fff' : '#fafafb';}}
				/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'14px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
			</div>
			<a href="/management/roles/role/create/create" style={{height:'42px',padding:'0 18px',borderRadius:'99px',background:'#ea580c',color:'#fff',border:'1px solid transparent',fontWeight:'700',fontSize:'13.5px',letterSpacing:'-0.1px',display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'7px',textDecoration:'none',boxShadow:'inset 0 1px 0 rgba(255,255,255,0.3),0 1px 2px rgba(234,88,12,0.4),0 6px 16px -4px rgba(234,88,12,0.45)',whiteSpace:'nowrap',flexShrink:0}}>
				<svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14"/></svg> Create Role
			</a>
		</div>
		{/* Table */}
		<div style={isMobile ? {background:'#fff',border:'1px solid #eaecf2',borderRadius:'16px',boxShadow:'0 1px 4px rgba(0,0,0,0.06)',overflow:'hidden'} : {}}>
		<DataTable columns={columns} data={filteredData} pagination highlightOnHover customStyles={rolesStyles}
			paginationPerPage={10} paginationRowsPerPageOptions={[10, 25, 50, 100]}
			paginationComponent={SpecPagination}
			progressPending={loading} progressComponent={<RolesLoading />}
			persistTableHead
			noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#9ca3af',fontSize:'14px'}}>No roles found</div>}
		/>
		</div>
	</div>
	<ToastContainer position="top-right" autoClose={3000} />
	</>
	);
}

if (document.getElementById('roles-index-app')) {
    const id = "roles-index-app";
    const root = createRoot(document.getElementById(id));
    const props = Object.assign({}, document.getElementById(id).dataset);
    root.render(<Provider store={store}><RolesIndexApp {...props} /></Provider>);
}

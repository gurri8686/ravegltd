import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { configureStore, createSlice } from '@reduxjs/toolkit';
import { Provider } from 'react-redux';
import DataTable from 'react-data-table-component';
import axios from 'axios';
import { ToastContainer } from 'react-toastify';
import useDataTableStyles from "../hooks/useDataTableStyles";
import useTableSearch from "./../hooks/useTableSearch";
import SpecTableLoading from "./../elements/SpecTableLoading";
import SpecPagination from "./../elements/SpecPagination";

// Base font for the whole Permissions UI — the artifact spec used var(--ff); the project
// has no such CSS var, so it resolves to the app's Nunito stack.
const FF = "'Nunito', sans-serif";

const slice = createSlice({
    name: 'properties',
    initialState: { refresh: 0 },
    reducers: { triggerRefresh: (state) => { state.refresh = Date.now(); } },
});

const store = configureStore({ reducer: { properties: slice.reducer } });

/* ── Custom Pagination — exact spec footer ─────────────────── */
function PermPagination({ rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage }) {
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

/* ── Table loading state — shared orange dot-spinner ──────── */
function PermLoading() {
  return <SpecTableLoading label="Loading permissions…" />;
}

/* ── Main App ──────────────────────────────────────────────── */
export default function RolesPermissionIndexApp(props) {
	const [data, setData] = useState([]);
	const [loading, setLoading] = useState(true);
	const customStyles = useDataTableStyles();
	const { filteredData, searchText, setSearchText } = useTableSearch(data, true);
	const [isMobile, setIsMobile] = useState(typeof window !== 'undefined' && window.innerWidth <= 767);
	useEffect(() => {
		const onResize = () => setIsMobile(window.innerWidth <= 767);
		window.addEventListener('resize', onResize);
		return () => window.removeEventListener('resize', onResize);
	}, []);

	const hs = {fontSize:'11px',fontWeight:'500',color:'#1f2937',letterSpacing:'0.7px',textTransform:'uppercase',whiteSpace:'nowrap'};
	const cellStyle = {fontSize:'13.5px',color:'#374151'};

	// Exact-spec overrides — orange header tray, footer, row borders. Kept local to this
	// component so other tables sharing useDataTableStyles are not affected.
	const permStyles = {
		...customStyles,
		table: { style: { ...customStyles.table.style, fontFamily:FF } },
		headRow: { style: { backgroundColor:'#fafbfc', borderBottom:'2px solid #eef2f7', minHeight:'44px' } },
		headCells: { style: { ...customStyles.headCells.style, fontSize:'11px', fontWeight:'700', color:'#64748b', letterSpacing:'0.7px', paddingLeft:'18px', paddingRight:'18px' } },
		rows: { ...customStyles.rows, style: { ...customStyles.rows.style, minHeight:'58px', borderBottomColor:'#f0f0f2' } },
		cells: { style: { ...customStyles.cells.style, fontSize:'13.5px', paddingLeft:'18px', paddingRight:'18px' } },
		pagination: { ...customStyles.pagination, style: { ...customStyles.pagination.style, backgroundColor:'#fafafb', borderTop:'1px solid #eeeeef', minHeight:'56px' } },
	};

	const columns = [
		{ name: <span style={hs}>Sl.No</span>, cell: (_, index) => <span style={{...cellStyle,color:'#374151'}}>{index + 1}</span>, width:'70px', grow:0, hide:768 },
		{ name: <span style={hs}>{isMobile ? 'Sl.No' : '#ID'}</span>, selector: row => row.id, cell: (row, index) => <span style={{fontSize:'12.5px',color:'rgb(234, 88, 12)',fontFamily:"ui-monospace,SFMono-Regular,Menlo,monospace",fontWeight:'800'}}>{isMobile ? index + 1 : row.id}</span>, sortable:!isMobile, width:'80px', grow:0 },
		{ name: <span style={hs}>Role Name</span>, selector: row => row.name, cell: row => (
			<div style={{display:'flex',alignItems:'center',gap:'10px'}}>
				<span style={{width:'30px',height:'30px',borderRadius:'8px',background:'#fff1e6',color:'#ea580c',border:'1px solid #f6c9a8',display:'inline-flex',alignItems:'center',justifyContent:'center',flexShrink:0}}>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
				</span>
				<div style={{fontWeight:'600',color:'#0f1115',fontSize:'13.5px'}}>{row.name}</div>
			</div>
		), sortable:true, grow:1, minWidth:'180px' },
		{ name: <span style={hs}>Permissions</span>, cell: row => (
			<a href={`/management/roles/permission/edit/${row.id}/edit`} title="Manage permissions"
				style={{width:'32px',height:'32px',borderRadius:'8px',border:'none',background:'rgb(234, 88, 12)',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',color:'#fff',fontSize:'13px',transition:'all 0.15s',cursor:'pointer',boxShadow:'0 2px 6px rgba(234,88,12,0.25)'}}
				onMouseOver={e => { e.currentTarget.style.background='rgb(234, 88, 12)'; }}
				onMouseOut={e => { e.currentTarget.style.background='#ea580c'; }}>
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/></svg>
			</a>
		), sortable:false, width:'140px', grow:0 },
		{ name: <span style={hs}>Actions</span>, cell: row => (
			<div style={{display:'flex',justifyContent:'flex-end',width:'100%'}}>
				<a href={`/management/roles/permission/edit/${row.id}/edit`} title="Edit"
					style={{width:'32px',height:'32px',borderRadius:'8px',border:'none',background:'rgb(234, 88, 12)',display:'inline-flex',alignItems:'center',justifyContent:'center',textDecoration:'none',color:'#fff',fontSize:'13px',transition:'all 0.15s',cursor:'pointer',boxShadow:'0 2px 6px rgba(234,88,12,0.25)'}}
					onMouseOver={e => { e.currentTarget.style.background='#c2410c'; }}
					onMouseOut={e => { e.currentTarget.style.background='rgb(234, 88, 12)'; }}>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
				</a>
			</div>
		), right:true, width:'100px', grow:0 },
	];

	useEffect(() => {
		setLoading(true);
		axios.get(props.listApi)
			.then(r => {
				if (r.data && r.data.success) {
					setData(r.data.payload || []);
				} else {
					console.warn('[Permissions] API returned non-success response:', r.data);
				}
			})
			.catch(err => console.error('[Permissions] Failed to load roles:', err, 'URL:', props.listApi))
			.finally(() => setLoading(false));
	}, []);

	return (
	<>
	<style>{`@media (max-width:767px){
		.perm-card-wrap{background:transparent !important;border:none !important;box-shadow:none !important;border-radius:0 !important;overflow:visible !important;}
		.perm-head-sec{background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);padding:14px 16px !important;margin-bottom:12px;}
		.perm-search-sec{background:transparent !important;border:none !important;box-shadow:none !important;border-radius:0 !important;padding:0 0 12px !important;margin-bottom:0;}
		.perm-search-input input{background:#fff !important;border:1px solid #ececf0 !important;border-radius:10px !important;padding:0 12px 0 36px !important;font-weight:500 !important;font-size:13px !important;color:#374151 !important;}
		.perm-search-input input::placeholder{color:#9ca3af !important;font-weight:500 !important;}
		.perm-search-icon{top:50% !important;transform:translateY(-50%) !important;display:flex !important;align-items:center !important;}
		.perm-search-icon svg{width:15px !important;height:15px !important;stroke:#9ca3af !important;}
		.perm-table-sec{background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);}
		.perm-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}.perm-table-scroll > div{min-width:560px;}
	}`}</style>
	<div className="perm-card-wrap" style={{background:'#fff',borderRadius:'0 0 16px 16px',border:'1px solid #eaecf2',borderTop:'none',boxShadow:'0 4px 16px rgba(0,0,0,0.04)',overflow:'hidden',fontFamily:FF}}>
		{/* Header — title row */}
		<div className="perm-head-sec" style={{padding:'18px 22px 14px',display:'flex',alignItems:'center',gap:'14px'}}>
			<span style={{width:'42px',height:'42px',borderRadius:'11px',background:'rgb(234, 88, 12)',color:'#fff',display:'flex',alignItems:'center',justifyContent:'center',flexShrink:0,boxShadow:'inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45)'}}>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/></svg>
			</span>
			<div style={{flex:'1 1 0%',minWidth:0}}>
				<h2 style={{margin:0,fontSize:'18px',fontWeight:'800',color:'#0f1115',letterSpacing:'-0.2px'}}>Permissions</h2>
				<p style={{margin:'2px 0 0',fontSize:'13.5px',color:'#6b7280'}}>Assign permissions to roles</p>
			</div>
		</div>
		{/* Header — search row */}
		<div className="perm-search-sec" style={{padding:'4px 22px 16px',display:'flex',alignItems:'center',gap:'12px'}}>
			<div className="perm-search-input" style={{flex:'1 1 0%',position:'relative'}}>
				<span className="perm-search-icon" style={{position:'absolute',left:'12px',top:'11px',color:'#9ca3af'}}>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
				</span>
				<input type="text" placeholder="Search by role name or ID…" value={searchText} onChange={e=>setSearchText(e.target.value)}
					style={{width:'100%',height:'40px',padding:'0 14px 0 38px',borderRadius:'99px',border:'1px solid #e8e8ec',background:'#fafafb',fontSize:'13.5px',color:'#0f1115',outline:'none',fontFamily:'inherit',boxSizing:'border-box'}}
					onFocus={e=>{e.target.style.borderColor='#ea580c';e.target.style.background='#fff';}}
					onBlur={e=>{e.target.style.borderColor='#e8e8ec';e.target.style.background='#fafafb';}}
				/>
{searchText && <button type="button" onClick={() => {setSearchText('')}} style={{position:'absolute',right:'14px',top:'50%',transform:'translateY(-50%)',background:'none',border:'none',cursor:'pointer',padding:'0',lineHeight:1,display:'flex',alignItems:'center'}}><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>}
			</div>
		</div>
		{/* Table */}
		<div className="perm-table-sec perm-table-scroll">
		<DataTable columns={columns} data={filteredData} pagination highlightOnHover customStyles={permStyles}
			paginationPerPage={10} paginationRowsPerPageOptions={[10, 25, 50, 100]}
			paginationComponent={SpecPagination}
			progressPending={loading} progressComponent={<PermLoading />}
			persistTableHead
			noDataComponent={<div style={{padding:'48px',textAlign:'center',color:'#9ca3af',fontSize:'14px'}}>No roles found</div>}
		/>
		</div>
	</div>
	<ToastContainer position="top-right" autoClose={3000} />
	</>
	);
}

if (document.getElementById('roles-permission-index-app')) {
    const id = "roles-permission-index-app";
    const root = createRoot(document.getElementById(id));
    const props = Object.assign({}, document.getElementById(id).dataset);
    root.render(<Provider store={store}><RolesPermissionIndexApp {...props} /></Provider>);
}

import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import DataTable from 'react-data-table-component';
import Select from 'react-select';
import axios from 'axios';
import { ToastContainer } from 'react-toastify';
import useDataTableStyles from "../hooks/useDataTableStyles";
import useTableSearch from "./../hooks/useTableSearch";
import SpecTableLoading from "./../elements/SpecTableLoading";

function ActionsDropdown({ row }) {
    const btns = [
        { href: `/stock_closing/view/index?product=${row.id}`, icon: 'fa fa-cube', title: 'Closing Stock', color: '#8b5cf6' },
        { href: `/stock_check/view?product=${row.id}`, icon: 'fa fa-bar-chart', title: 'Stock Check', color: '#0ea5e9' },
    ];
    return (
        <div style={{ display: 'flex', gap: '6px', justifyContent: 'flex-end' }}>
            {btns.map((btn, i) => (
                <a key={i} href={btn.href} title={btn.title}
                    style={{ width: '32px', height: '32px', borderRadius: '8px', border: '1px solid #e2e8f0', background: '#fff', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', textDecoration: 'none', color: '#94a3b8', fontSize: '13px', transition: 'all 0.15s', cursor: 'pointer' }}
                    onMouseOver={(e) => { e.currentTarget.style.borderColor = btn.color; e.currentTarget.style.color = btn.color; e.currentTarget.style.background = btn.color + '0d'; }}
                    onMouseOut={(e) => { e.currentTarget.style.borderColor = '#e2e8f0'; e.currentTarget.style.color = '#94a3b8'; e.currentTarget.style.background = '#fff'; }}
                >
                    <i className={btn.icon}></i>
                </a>
            ))}
        </div>
    );
}

export default function StockManagerApp(props) {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const { filteredData: searchFiltered, searchText, setSearchText } = useTableSearch(data, true);
    const [statusFilter, setStatusFilter] = useState({ label: 'All', value: 'all' });
    const customStyles = useDataTableStyles();

    const statusOptions = [
        { label: 'All', value: 'all' },
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
    ];

    const filteredData = statusFilter.value === 'all'
        ? searchFiltered
        : statusFilter.value === 'active'
            ? searchFiltered.filter(r => r.is_active == 1)
            : searchFiltered.filter(r => r.is_active != 1);

    const hs = { fontSize: '11px', fontWeight: '700', color: '#9ca3af', letterSpacing: '0.6px', textTransform: 'uppercase', whiteSpace: 'nowrap' };
    const cellStyle = { fontSize: '13px', color: '#374151', fontWeight: '500' };

    const columns = [
        {
            name: <span style={hs}>Sl.No</span>,
            selector: row => row.id,
            cell: (row, index) => <span style={{ ...cellStyle, color: '#6b7280' }}>{index + 1}</span>,
            width: '70px', grow: 0, hide: 'sm',
        },
        {
            name: <span style={hs}>Product ID</span>,
            selector: row => row.product_id,
            cell: row => <span style={{ ...cellStyle, color: 'rgb(234, 88, 12)', fontWeight: '600' }}>{row.product_id}</span>,
            sortable: true, width: '110px', grow: 0,
        },
        {
            name: <span style={hs}>Name</span>,
            selector: row => row.name,
            cell: row => <span style={{ ...cellStyle, fontWeight: '600', color: '#111827' }}>{row.name}</span>,
            sortable: true,
        },
        {
            name: <span style={hs}>Status</span>,
            selector: row => row.is_active,
            cell: row => (row.is_active == 1
                ? <span style={{ background: '#ecfdf5', color: '#059669', border: '1px solid #a7f3d0', borderRadius: '20px', padding: '3px 12px', fontSize: '11px', fontWeight: '600' }}>Active</span>
                : <span style={{ background: '#fef2f2', color: '#dc2626', border: '1px solid #fecaca', borderRadius: '20px', padding: '3px 12px', fontSize: '11px', fontWeight: '600' }}>Inactive</span>),
            sortable: true, hide: 'sm',
        },
        {
            name: <span style={{ ...hs, display: 'block', textAlign: 'right' }}>Actions</span>,
            cell: row => <ActionsDropdown row={row} />,
            sortable: false, right: true, width: '120px', grow: 0,
        },
    ];

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await axios.get(props.listApi);
                if (response.data.success === true) {
                    setData(response.data.payload);
                }
            } catch (err) {
                console.error('Failed to load products', err);
            } finally {
                setLoading(false);
            }
        };
        fetchData();
    }, []);

    const labelStyle = { fontSize:'10.5px', fontWeight:'700', color:'#64748b', letterSpacing:'0.8px', textTransform:'uppercase', marginBottom:'8px', display:'block' };

    return (
        <div style={{ maxWidth: '1440px', margin: '0 auto' }}>
            <style>{`
                .sm-filter-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: end; padding: 20px 24px; border-bottom: 2px solid #f1f5f9; background: linear-gradient(to bottom, #fafbfc, #fff); }
                .sm-stock-col > div { width: 100% !important; }
                .sm-stock-col .react-select__control { min-height: 44px !important; height: 44px !important; border-radius: 12px !important; border: 1.5px solid #e2e8f0 !important; background: #fff !important; box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important; cursor: pointer !important; }
                .sm-stock-col .react-select__control--is-focused { border-color: rgb(234, 88, 12) !important; box-shadow: 0 0 0 4px rgba(234,88,12,0.08) !important; }
                .sm-stock-col .react-select__value-container { height: 44px !important; padding: 0 14px !important; }
                .sm-stock-col .react-select__indicators { height: 44px !important; }
                .sm-stock-col .react-select__indicator-separator { display: none !important; }
                .sm-stock-col .react-select__single-value { font-size: 13px !important; font-weight: 600 !important; color: #1e293b !important; }
                .sm-stock-col .react-select__menu { border-radius: 12px !important; border: 1px solid #e8ecf2 !important; box-shadow: 0 8px 24px rgba(0,0,0,0.1) !important; margin-top: 4px !important; z-index: 10 !important; }
                .sm-stock-col .react-select__option { font-size: 13px !important; font-weight: 500 !important; padding: 10px 14px !important; cursor: pointer !important; }
                .sm-stock-col .react-select__option--is-selected { background: rgb(234, 88, 12) !important; color: #fff !important; }
                .sm-stock-col .react-select__option--is-focused { background: #FFF5ED !important; color: rgb(234, 88, 12) !important; }
            `}</style>
            <div style={{ borderRadius: '16px', border: '1px solid #eaecf2', background: '#fff', overflow: 'hidden', boxShadow: '0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04)' }}>
                {/* ── Filter Bar ── */}
                <div className="sm-filter-row">
                    {/* Stock Status */}
                    <div className="sm-stock-col">
                        <label style={labelStyle}>Stock Status</label>
                        <Select
                            classNamePrefix="react-select"
                            options={statusOptions}
                            value={statusFilter}
                            onChange={(val) => setStatusFilter(val || { label: 'All', value: 'all' })}
                            isSearchable={false}
                            menuPortalTarget={document.body}
                            styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999 }) }}
                        />
                    </div>
                    {/* Search Product */}
                    <div>
                        <label style={labelStyle}>Search Product</label>
                        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                            <div style={{ position: 'relative', flex: 1 }}>
                                <svg style={{ position: 'absolute', left: '14px', top: '50%', transform: 'translateY(-50%)', pointerEvents: 'none' }} width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#c0c8d4" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" placeholder="Search product..."
                                    value={searchText}
                                    onChange={(e) => setSearchText(e.target.value)}
                                    style={{ height: '44px', width: '100%', borderRadius: '12px', border: '1.5px solid #e2e8f0', fontSize: '13px', fontWeight: '400', background: '#fff', padding: '0 36px 0 40px', outline: 'none', transition: 'border-color 0.15s, box-shadow 0.15s', color: '#1e293b', boxSizing: 'border-box', boxShadow: '0 1px 3px rgba(0,0,0,0.04)' }}
                                    onFocus={(e) => { e.target.style.borderColor = 'rgb(234, 88, 12)'; e.target.style.boxShadow = '0 0 0 3px rgba(234,88,12,0.1)'; }}
                                    onBlur={(e) => { e.target.style.borderColor = '#e2e8f0'; e.target.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)'; }}
                                />
                                {searchText && <button type="button" onClick={() => setSearchText('')} style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', padding: '0', lineHeight: 1, display: 'flex', alignItems: 'center' }}>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>}
                            </div>
                            {/* Download Excel */}
                            <button type="button" className="icon-tip" data-tip="Download Excel" onClick={() => {
                                const base = props.excelUrl || '/excel/stock_manager';
                                const url = base + '?status=' + (statusFilter?.value || 'all');
                                window.location.href = url;
                            }}
                                style={{ width: '44px', height: '44px', borderRadius: '12px', border: '1.5px solid #e2e8f0', background: '#fff', color: '#64748b', cursor: 'pointer', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', transition: 'all 0.15s', flexShrink: 0, boxShadow: '0 1px 3px rgba(0,0,0,0.04)' }}
                                onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgb(234, 88, 12)'; e.currentTarget.style.color = 'rgb(234, 88, 12)'; e.currentTarget.style.background = '#fff7ed'; }}
                                onMouseLeave={e => { e.currentTarget.style.borderColor = '#e2e8f0'; e.currentTarget.style.color = '#64748b'; e.currentTarget.style.background = '#fff'; }}
                            >
                                <i className="fa fa-download" style={{ fontSize: '14px' }}></i>
                            </button>
                        </div>
                    </div>
                </div>
                <DataTable
                    columns={columns}
                    data={filteredData}
                    pagination
                    highlightOnHover
                    customStyles={customStyles}
                    paginationPerPage={10}
                    paginationRowsPerPageOptions={[5, 10, 20, 50]}
                    progressPending={loading}
                    progressComponent={<SpecTableLoading label="Loading products…" />}
                    persistTableHead
                    noDataComponent={<div style={{ padding: '48px', textAlign: 'center', color: '#94a3b8', fontSize: '14px' }}>No products found</div>}
                />
            </div>
            <ToastContainer autoClose={3000} />
        </div>
    );
}

// ----------------- Mount App -----------------
if (document.getElementById('stock-manager-app')) {
    const root = createRoot(document.getElementById('stock-manager-app'));
    const element = document.getElementById('stock-manager-app');
    const props = Object.assign({}, element.dataset);
    root.render(<StockManagerApp {...props} />);
}

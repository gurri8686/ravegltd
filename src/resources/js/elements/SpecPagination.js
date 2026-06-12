import React, { useState, useEffect } from 'react';

/**
 * Spec pagination footer for react-data-table-component.
 * Pass it to <DataTable paginationComponent={SpecPagination} />.
 * RDT supplies: rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage.
 */
export default function SpecPagination({ rowsPerPage, rowCount, currentPage, onChangePage, onChangeRowsPerPage, rowsPerPageOptions = [10, 25, 50, 100] }) {
  const totalPages = Math.ceil(rowCount / rowsPerPage) || 1;
  const from = rowCount === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
  const to   = Math.min(currentPage * rowsPerPage, rowCount);
  const isFirst = currentPage <= 1;
  const isLast  = currentPage >= totalPages;
  // Mobile (≤767px) — wrap & shrink the footer so it doesn't squeeze. Desktop layout unchanged.
  const [isMobile, setIsMobile] = useState(() => (typeof window !== 'undefined' && window.innerWidth <= 767));
  useEffect(() => {
    const onResize = () => setIsMobile(window.innerWidth <= 767);
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);
  const navBtn = (disabled) => ({ width: isMobile ? '32px' : '28px', height: isMobile ? '32px' : '28px', borderRadius: '7px', background: '#fff', border: '1px solid #e8e8ec', color: disabled ? '#c8c8cf' : '#6b7280', cursor: disabled ? 'not-allowed' : 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 0 });

  return (
    <div style={ isMobile
      ? { padding: '10px 14px', borderTop: '1px solid #eeeeef', background: '#fafafb', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px', flexWrap: 'wrap' }
      : { padding: '12px 24px', borderTop: '1px solid #eeeeef', background: '#fafafb', display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '14px' }}>
      <span style={{ fontSize: isMobile ? '11.5px' : '12.5px', color: '#6b7280', fontWeight: '600', whiteSpace: 'nowrap' }}>{isMobile ? 'Rows:' : 'Rows per page:'}</span>
      <select value={rowsPerPage} onChange={e => onChangeRowsPerPage(Number(e.target.value), currentPage)}
        style={{ height: '30px', padding: '0 10px', borderRadius: '8px', border: '1px solid #e8e8ec', background: '#fff', fontSize: '12px', color: '#0f1115', fontFamily: 'inherit', cursor: 'pointer' }}>
        {rowsPerPageOptions.map(n => <option key={n} value={n}>{n}</option>)}
      </select>
      <span style={{ fontSize: isMobile ? '11.5px' : '12.5px', color: '#6b7280', fontWeight: '600', whiteSpace: 'nowrap', marginLeft: isMobile ? 'auto' : 0 }}>{from}&ndash;{to} of {rowCount}</span>
      <div style={{ display: 'flex', gap: isMobile ? '4px' : '2px' }}>
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

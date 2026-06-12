import React from 'react';

/**
 * Shared empty-state — matches the Sales / Purchase "No records found" UI.
 * Use anywhere a table/list has no rows so every page looks consistent:
 *   {rows.length === 0 && <SpecTableEmpty onClear={resetFilters} />}
 *   <DataTable noDataComponent={<SpecTableEmpty onClear={resetFilters} />} />
 *
 * Props:
 *   title    — bold heading (default "No records found")
 *   subtitle — helper line under the heading
 *   onClear  — clear-filters handler. Defaults to a full page reload so the
 *              "Clear filters" button always shows and does something sensible.
 *              Pass `false` to hide the button entirely.
 *   clearLabel — button text (default "Clear filters")
 */
export default function SpecTableEmpty({
  title = 'No records found',
  subtitle = 'Try changing the date range or search term. New invoices appear here as soon as you create them.',
  onClear,
  clearLabel = 'Clear filters',
}) {
  // Show the button by default (reload as a safe universal reset). Only hide it
  // when the caller explicitly passes onClear={false}.
  const handleClear = onClear === undefined
    ? () => { if (typeof window !== 'undefined') window.location.reload(); }
    : onClear;
  return (
    <div style={{ textAlign: 'center', padding: '40px 20px', width: '100%' }}>
      <div style={{ width: '60px', height: '60px', margin: '0 auto 12px', borderRadius: '14px', background: '#fafafb', border: '1px solid #e8e8ec', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#9ca3af' }}>
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>
      </div>
      <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f1115' }}>{title}</div>
      <div style={{ fontSize: '13px', color: '#6b7280', marginTop: '4px', maxWidth: '380px', marginInline: 'auto' }}>{subtitle}</div>
      {handleClear && (
        <button type="button" onClick={handleClear}
          style={{ height: '34px', padding: '0 12px', borderRadius: '10px', background: '#fff', color: '#0f1115', border: '1px solid #e8e8ec', fontWeight: '700', fontSize: '12.5px', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: '7px', boxShadow: '0 1px 2px rgba(15,17,21,0.04)', cursor: 'pointer', marginTop: '14px', outline: 'none' }}>
          <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M17 1l4 4-4 4" /><path d="M3 11V9a4 4 0 0 1 4-4h14" /><path d="M7 23l-4-4 4-4" /><path d="M21 13v2a4 4 0 0 1-4 4H3" /></svg>
          {clearLabel}
        </button>
      )}
    </div>
  );
}

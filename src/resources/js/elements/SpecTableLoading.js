import React from 'react';

/**
 * Shared loading pill — orange fa-spinner + label inside a rounded cream pill.
 * Use anywhere a loading indicator is needed:
 *   <DataTable progressComponent={<SpecTableLoading label="Loading invoices…" />} />
 *   {loading && <SpecTableLoading label="Loading…" />}
 *
 * Props:
 *   label   — text shown next to the spinner (default "Loading…")
 *   inline  — when true, no outer padding wrapper (use inside an already-padded
 *             container, e.g. an absolute overlay)
 */
export default function SpecTableLoading({ label = 'Loading…', inline = false }) {
  const pill = (
    <div style={{
      display: 'inline-flex',
      alignItems: 'center',
      gap: '10px',
      padding: '10px 18px',
      background: '#fff7ed',
      border: '1px solid #fed7aa',
      borderRadius: '9999px',
      color: '#ea580c',
      fontSize: '13px',
      fontWeight: '600',
    }}>
      <i className="fa fa-spinner fa-spin" style={{ fontSize: '14px' }}></i>
      <span>{label}</span>
    </div>
  );
  if (inline) return pill;
  return (
    <div style={{ padding: '56px 24px', textAlign: 'center', width: '100%' }}>
      {pill}
    </div>
  );
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceProduct;
use App\Models\CustomerInvoiceOrder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceProduct;
use App\Models\SupplierInvoiceOrder;
use App\Models\SupplierPayment;
use App\Services\StockProducts;
use Maatwebsite\Excel\Facades\Excel;
use Auth;
use DB;
use Carbon\Carbon;

class ImportDataController extends Controller
{
    /**
     * System fields available for mapping, per import type.
     * Each field has a key (used internally), a label (shown to user),
     * required flag, and aliases (used to auto-suggest a column match).
     */
    private function systemFields(string $type): array
    {
        if ($type === 'sales') {
            // Sales mapping mirrors the manual sales form fields:
            // Customer + Date (top), Product + Supplier + Qty + Unit Price + Total (per row),
            // plus optional Paid amount and Customer Return.
            //
            // ── "Entry By" is now a SEPARATE field (salesman_name) that resolves to a user/staff
            //    member and is stored on customer_invoices.salesman_id. The Supplier field is
            //    now truly optional and DISTINCT from "Entry By" — they are two different things
            //    despite the original Excel templates conflating them.
            return [
                ['key' => 'entity_name',   'label' => 'Customer',        'required' => true,  'aliases' => ['customer_name', 'customer', 'party_name', 'party']],
                ['key' => 'date',          'label' => 'Date',            'required' => false, 'aliases' => ['date', 'inv_date', 'invoice_date', 'transaction_date']],
                ['key' => 'invoice_no',    'label' => 'Invoice No',      'required' => false, 'aliases' => ['invoice_no', 'invoice_number', 'inv_no', 'bill_no']],
                ['key' => 'product_name',  'label' => 'Product',         'required' => true,  'aliases' => ['product_name', 'product', 'item', 'item_name']],
                ['key' => 'entry_by',      'label' => 'Supplier',        'required' => false, 'aliases' => ['supplier_name', 'supplier', 'party_name', 'party']],
                ['key' => 'salesman_name', 'label' => 'Entry By',        'required' => false, 'aliases' => ['entry_by', 'entryby', 'salesman', 'salesperson', 'user', 'created_by', 'staff']],
                ['key' => 'quantity',      'label' => 'Quantity',        'required' => true,  'aliases' => ['quantity', 'qty']],
                ['key' => 'unit_price',    'label' => 'Unit Price',      'required' => true,  'aliases' => ['unit_price', 'unitprice', 'price', 'rate']],
                ['key' => 'total',         'label' => 'Total',           'required' => false, 'aliases' => ['total', 'amount', 'sub_total', 'subtotal']],
                ['key' => 'paid_amount',   'label' => 'Paid',            'required' => false, 'aliases' => ['debit', 'paid', 'paid_amount', 'amount_paid', 'received']],
                ['key' => 'return_qty',    'label' => 'Customer Return', 'required' => false, 'aliases' => ['return', 'returns', 'return_qty', 'returned']],
            ];
        }

        // Purchase mapping (unchanged).
        $fields = [
            ['key' => 'date',         'label' => 'Date',         'required' => false, 'aliases' => ['date', 'inv_date', 'invoice_date', 'transaction_date']],
            ['key' => 'invoice_no',   'label' => 'Invoice No',   'required' => false, 'aliases' => ['invoice_no', 'invoice_number', 'inv_no', 'bill_no']],
            ['key' => 'product_name', 'label' => 'Product Name', 'required' => true,  'aliases' => ['product_name', 'product', 'item', 'item_name']],
            ['key' => 'quantity',     'label' => 'Quantity',     'required' => true,  'aliases' => ['quantity', 'qty']],
            ['key' => 'unit_price',   'label' => 'Unit Price',   'required' => true,  'aliases' => ['unit_price', 'unitprice', 'price', 'rate']],
            ['key' => 'total',        'label' => 'Total',        'required' => false, 'aliases' => ['total', 'amount', 'sub_total', 'subtotal']],
        ];
        array_splice($fields, 2, 0, [[
            'key' => 'entity_name', 'label' => 'Supplier Name', 'required' => true,
            'aliases' => ['supplier_name', 'supplier', 'party_name', 'party', 'inv_no']
        ]]);
        $fields[] = ['key' => 'sell_price',  'label' => 'Sell Price',      'required' => false, 'aliases' => ['sell_price', 'sale_price', 'selling_price']];
        $fields[] = ['key' => 'paid_amount', 'label' => 'Paid',    'required' => false, 'aliases' => ['debit', 'paid', 'paid_amount', 'amount_paid']];
        $fields[] = ['key' => 'return_qty',  'label' => 'Supplier Return', 'required' => false, 'aliases' => ['return', 'returns', 'return_qty', 'returned']];
        $fields[] = ['key' => 'dump_qty',    'label' => 'Dump',            'required' => false, 'aliases' => ['dump', 'dumps', 'dumped']];
        return $fields;
    }

    /**
     * Read only the headers (and a sample row) from the uploaded file
     * and return them along with the system fields and a suggested mapping.
     */
    public function headers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'type' => 'required|in:sales,purchase',
        ]);

        try {
            $rows = $this->parseFile($request->file('file'));
            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'The file is empty or has no data rows.']);
            }

            $type = $request->type;
            $fileColumns = [];
            foreach (array_keys($rows[0]) as $key) {
                if (in_array($key, ['sl_no', 'slno', 's_no', 'sno', 'sr_no', 'srno'])) continue;

                // Pick a sample (first non-empty value in this column)
                $sample = '';
                foreach ($rows as $r) {
                    $v = $r[$key] ?? null;
                    if ($v !== null && $v !== '' && $v !== '0' && $v !== '0.00') {
                        $sample = (string)$v;
                        break;
                    }
                }

                $label = strpos($key, '_col_') === 0
                    ? ($this->detectColumnName($rows, $key) ?: '(unnamed)')
                    : ucwords(str_replace('_', ' ', $key));

                $fileColumns[] = [
                    'key' => $key,
                    'label' => $label,
                    'sample' => mb_substr($sample, 0, 60),
                ];
            }

            $systemFields = $this->systemFields($type);

            // Build value profiles up-front for ALL columns
            $profiles = [];
            foreach ($fileColumns as $fc) {
                $profiles[$fc['key']] = $this->profileColumnValues($rows, $fc['key']);
            }

            // ── Label-first mapping (header alias) — HIGHEST priority ──
            // If a header explicitly matches a system field's alias (e.g. "Returns" → return_qty,
            // "Dumps" → dump_qty, "Debit" → paid_amount), trust the user's labeling and use that
            // column directly. The data sanity check only rejects clear contradictions
            // (e.g. label says "Date" but column is full of text names).
            $suggested = [];
            $usedColumns = [];
            foreach ($systemFields as $sf) {
                foreach ($sf['aliases'] as $alias) {
                    foreach ($fileColumns as $fc) {
                        if ($fc['key'] !== $alias) continue;
                        if (in_array($fc['key'], $usedColumns, true)) continue;
                        // Sanity check: reject only if data clearly contradicts the field type
                        $prof = $profiles[$fc['key']] ?? null;
                        if ($prof && $prof['total'] > 0 && !empty($prof['samples'])) {
                            if ($this->fieldContradictsProfile($sf['key'], $prof)) continue 2;
                        }
                        $suggested[$sf['key']] = $fc['key'];
                        $usedColumns[] = $fc['key'];
                        break 2;
                    }
                }
            }

            // ── Value-based fill for any field still unmapped ──
            // Looks at remaining columns' data and assigns by content (e.g. header was empty/wrong).
            $candidates = [];
            foreach ($systemFields as $sf) {
                if (isset($suggested[$sf['key']])) continue;
                foreach ($profiles as $colKey => $prof) {
                    if (in_array($colKey, $usedColumns, true)) continue;
                    if (!$prof || $prof['total'] === 0 || empty($prof['samples'])) continue;
                    $valueScore = $this->valueAffinityScore($sf['key'], $prof);
                    if ($valueScore < 0.55) continue;
                    $candidates[] = ['sys' => $sf['key'], 'col' => $colKey, 'score' => $valueScore];
                }
            }
            usort($candidates, function($a, $b) { return $b['score'] <=> $a['score']; });
            foreach ($candidates as $c) {
                if (isset($suggested[$c['sys']])) continue;
                if (in_array($c['col'], $usedColumns, true)) continue;
                $suggested[$c['sys']] = $c['col'];
                $usedColumns[] = $c['col'];
            }

            return response()->json([
                'success' => true,
                'file_columns' => $fileColumns,
                'system_fields' => $systemFields,
                'suggested' => $suggested,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to read headers: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply user-chosen mapping to each row.
     * `mapping` is { system_field_key => file_column_key }. We copy the value
     * from the file column into the system-field key so parseRow() picks it up.
     */
    private function applyMapping(array $rows, $mapping): array
    {
        if (!is_array($mapping) || empty($mapping)) return $rows;

        // Strip any system-field aliases that user didn't pick, so they don't shadow the mapped column.
        $allAliases = [];
        foreach (array_merge($this->systemFields('sales'), $this->systemFields('purchase')) as $sf) {
            $allAliases = array_merge($allAliases, $sf['aliases']);
        }
        $allAliases = array_unique($allAliases);
        $mappedSourceCols = array_values(array_filter($mapping, fn($v) => is_string($v) && $v !== ''));

        foreach ($rows as &$row) {
            // 1. Clear any existing alias keys that might shadow the mapping (unless user mapped to that exact column)
            foreach ($allAliases as $alias) {
                if (array_key_exists($alias, $row) && !in_array($alias, $mappedSourceCols, true)) {
                    $row[$alias] = null;
                }
            }
            // 2. Copy mapped source column value into the system-field key
            foreach ($mapping as $sysKey => $srcCol) {
                if (!is_string($srcCol) || $srcCol === '') continue;
                $row[$sysKey] = $row[$srcCol] ?? null;
            }
        }
        unset($row);
        return $rows;
    }

    /**
     * Preview uploaded Excel file and return parsed rows.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'type' => 'required|in:sales,purchase',
        ]);


        try {
            $rows = $this->parseFile($request->file('file'));

            // Apply user-chosen field mapping if provided
            $mapping = $request->input('mapping');
            $mappingArr = null;
            if ($mapping) {
                $mappingArr = is_array($mapping) ? $mapping : json_decode($mapping, true);
                $rows = $this->applyMapping($rows, $mappingArr);
            }

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'The file is empty or has no data rows.']);
            }

            $type = $request->type;

            // Build columns: if user provided a mapping, use system field order
            // and only include the fields they mapped. Otherwise fall back to raw file columns.
            $rawHeaders = [];
            $rawKeys = [];

            if (is_array($mappingArr) && !empty($mappingArr)) {
                $sysFields = $this->systemFields($type);
                foreach ($sysFields as $sf) {
                    $srcCol = $mappingArr[$sf['key']] ?? null;
                    if ($srcCol === null || $srcCol === '') continue; // user skipped this field
                    $rawKeys[] = $sf['key'];      // system-field key (after applyMapping these are populated)
                    $rawHeaders[] = $sf['label']; // human label
                }
            } else {
                // No mapping → original raw column behavior
                $headerRenameMap = [];
                if ($type === 'purchase') {
                    $headerRenameMap = [
                        'inv_no' => 'Supplier Name',
                        'inv_date' => 'Inv Date',
                    ];
                }

                foreach (array_keys($rows[0]) as $key) {
                    if (in_array($key, ['sl_no', 'slno', 's_no', 'sno', 'sr_no', 'srno'])) continue;
                    $rawKeys[] = $key;
                    if (isset($headerRenameMap[$key])) {
                        $rawHeaders[] = $headerRenameMap[$key];
                    } elseif (strpos($key, '_col_') === 0) {
                        $rawHeaders[] = $this->detectColumnName($rows, $key);
                    } else {
                        $rawHeaders[] = ucwords(str_replace('_', ' ', $key));
                    }
                }

                // Filter out columns that are completely empty or all zeros
                $nonEmptyCols = [];
                foreach ($rawKeys as $idx => $key) {
                    $hasRealData = false;
                    foreach ($rows as $row) {
                        $val = $row[$key] ?? null;
                        if ($val !== null && $val !== '' && $val !== '0' && $val !== '0.00' && $val !== '0.0') {
                            $hasRealData = true;
                            break;
                        }
                    }
                    if ($hasRealData) $nonEmptyCols[] = $idx;
                }
                $rawHeaders = array_values(array_intersect_key($rawHeaders, array_flip($nonEmptyCols)));
                $rawKeys = array_values(array_intersect_key($rawKeys, array_flip($nonEmptyCols)));
            }

            // Validate each row and build preview
            $previewRows = [];
            $errors = [];
            $errorSummary = [];
            $skippedSummaryCount = 0; // summary/total rows skipped silently — never reach the UI

            foreach ($rows as $i => $row) {
                $rowNum = $i + 1;
                $parsed = $this->parseRow($row, $type, $rowNum);

                // Silently drop summary/total rows — they have no business meaning and aren't imported.
                if (in_array('summary_row', $parsed['data']['error_types'] ?? [], true)) {
                    $skippedSummaryCount++;
                    continue;
                }

                if (!empty($parsed['errors'])) {
                    $errors = array_merge($errors, $parsed['errors']);
                }

                // Accumulate error_types into summary
                $rowErrorTypes = $parsed['data']['error_types'] ?? [];
                foreach ($rowErrorTypes as $et) {
                    $errorSummary[$et] = ($errorSummary[$et] ?? 0) + 1;
                }

                // Build raw display values
                $rawValues = [];
                foreach ($rawKeys as $key) {
                    // For mapped 'date' column, prefer the parsed date for display
                    if ($key === 'date' && !empty($parsed['data']['date'])) {
                        $rawValues[] = (string)$parsed['data']['date'];
                        continue;
                    }
                    // For mapped numeric columns, prefer the auto-fixed (positive) value from parseRow
                    if (in_array($key, ['quantity', 'unit_price', 'total', 'paid_amount', 'return_qty', 'dump_qty', 'sell_price'], true)
                        && isset($parsed['data'][$key]) && $parsed['data'][$key] !== '') {
                        $rawValues[] = (string)$parsed['data'][$key];
                        continue;
                    }
                    $val = isset($row[$key]) && $row[$key] !== null ? (string)$row[$key] : '';
                    // Show 0 / 0.00 as empty dash
                    if ($val === '0' || $val === '0.00' || $val === '0.0') {
                        $val = '';
                    }
                    $rawValues[] = $val;
                }

                $previewRows[] = [
                    'raw' => $rawValues,
                    'status' => $parsed['data']['status'],
                    'error_types' => $rowErrorTypes,
                    // Carry parsed fields so the duplicate pass below can group & fingerprint
                    // without re-running parseRow. Stripped before the JSON response.
                    '_parsed' => $parsed['data'],
                ];
            }

            // ── Preview-time duplicate detection — STRICT FULL-ROW EXACT MATCH ──────────
            // A row is a "duplicate" ONLY when EVERY meaningful column is identical to:
            //   (a) an EARLIER row in the same file, OR
            //   (b) a record that ALREADY EXISTS in the database.
            // Compared fields: date, entity (customer/supplier), product, quantity, unit price,
            // total, sell price. The FIRST occurrence stays valid; matches are flagged 'duplicate'.
            // Numbers are normalised (12 == 12.00) and text is trimmed + lower-cased.
            try {
                // Row-level fingerprint via the SHARED helper — the import uses the EXACT same
                // function, so what the preview flags as a duplicate is exactly what the import skips.
                // Identity = invoice_no (primary) + entity + product + qty + unit price;
                // date is used only as a fallback when there is no invoice number.
                $makeFp = function($invoiceNo, $date, $entity, $product, $qty, $unit) {
                    return $this->_buildExactRowFingerprint([
                        'invoice_no' => $invoiceNo, 'date' => $date, 'entity_name' => $entity,
                        'product_name' => $product, 'quantity' => $qty, 'unit_price' => $unit,
                    ]);
                };

                // ── Seed fingerprints from EXISTING DB records (so file rows already in DB are flagged) ──
                // Collect every entity name referenced by valid file rows, then bulk-load that entity's
                // existing invoices + products and fingerprint each product line.
                $seenRowFps = []; // fingerprint => first file-row index (or -1 if it came from the DB)
                $entityNames = [];
                foreach ($previewRows as $pr) {
                    if (($pr['status'] ?? '') !== 'valid') continue;
                    $en = strtolower(trim((string)(($pr['_parsed']['entity_name']) ?? '')));
                    if ($en !== '') $entityNames[$en] = true;
                }
                if (!empty($entityNames)) {
                    $entityIdToName = []; // id => lower-name (not needed) ; we just need invoices
                    if ($type === 'sales') {
                        $ents = Customer::whereIn(DB::raw('LOWER(name)'), array_keys($entityNames))->get(['id','name']);
                        $entIds = $ents->pluck('id')->all();
                        $nameById = $ents->pluck('name','id')->all();
                        if (!empty($entIds)) {
                            $hasOtherInv = \Schema::hasColumn('customer_invoices', 'other_invoice_id');
                            $cols = $hasOtherInv ? ['id','customer_id','created_at','other_invoice_id'] : ['id','customer_id','created_at'];
                            $dbInvoices = CustomerInvoice::whereIn('customer_id', $entIds)
                                ->with(['products' => function($q){ $q->where('is_archive', 0); }])
                                ->get($cols);
                            foreach ($dbInvoices as $inv) {
                                $invDate = $inv->created_at;
                                $invNo   = $hasOtherInv ? (string)($inv->other_invoice_id ?? '') : '';
                                $entName = $nameById[$inv->customer_id] ?? '';
                                foreach (($inv->products ?? []) as $p) {
                                    $prodName = '';
                                    try { $pm = \App\Models\Product::find($p->product_id); if ($pm) $prodName = $pm->name; } catch (\Throwable $e) {}
                                    $fp = $makeFp($invNo, $invDate, $entName, $prodName, $p->quantity, $p->unit_price);
                                    if (!isset($seenRowFps[$fp])) $seenRowFps[$fp] = -1; // -1 = exists in DB
                                }
                            }
                        }
                    } else {
                        $ents = Supplier::whereIn(DB::raw('LOWER(name)'), array_keys($entityNames))->get(['id','name']);
                        $entIds = $ents->pluck('id')->all();
                        $nameById = $ents->pluck('name','id')->all();
                        if (!empty($entIds)) {
                            $hasOtherInv = \Schema::hasColumn('supplier_invoices', 'other_invoice_id');
                            $cols = $hasOtherInv ? ['id','supplier_id','created_at','other_invoice_id'] : ['id','supplier_id','created_at'];
                            $dbInvoices = SupplierInvoice::whereIn('supplier_id', $entIds)
                                ->with(['products' => function($q){ $q->where('is_archive', 0); }])
                                ->get($cols);
                            foreach ($dbInvoices as $inv) {
                                $invDate = $inv->created_at;
                                $invNo   = $hasOtherInv ? (string)($inv->other_invoice_id ?? '') : '';
                                $entName = $nameById[$inv->supplier_id] ?? '';
                                foreach (($inv->products ?? []) as $p) {
                                    $prodName = '';
                                    try { $pm = \App\Models\Product::find($p->product_id); if ($pm) $prodName = $pm->name; } catch (\Throwable $e) {}
                                    $fp = $makeFp($invNo, $invDate, $entName, $prodName, $p->quantity, $p->unit_price);
                                    if (!isset($seenRowFps[$fp])) $seenRowFps[$fp] = -1; // -1 = exists in DB
                                }
                            }
                        }
                    }
                }

                // ── Scan file rows — flag any that match a DB record OR an earlier file row ──
                // $seenRowFps holds 5-core-field fps of DB records (value -1).
                // $seenFileFps holds 6-field fps (core + Entry By) of earlier file rows (value = row index)
                //   — so two file rows with a DIFFERENT Entry By are NOT treated as duplicates.
                $seenFileFps = [];
                foreach ($previewRows as $pi => $pr) {
                    if (($pr['status'] ?? '') !== 'valid') continue;
                    $pd = $pr['_parsed'] ?? [];
                    if (empty($pd)) continue;

                    // Core fp — for the DB-existing check. Invoice-number-primary identity.
                    $coreFp = $makeFp(
                        $pd['invoice_no'] ?? '', $pd['date'] ?? '', $pd['entity_name'] ?? '',
                        $pd['product_name'] ?? '', $pd['quantity'] ?? '', $pd['unit_price'] ?? ''
                    );
                    // 6-field fp (core + Entry By) — for the file-internal check.
                    $fileFp = $this->_buildExactRowFingerprint($pd, true);

                    $dupOf = null;
                    if (isset($seenRowFps[$coreFp]))      $dupOf = $seenRowFps[$coreFp];   // -1 → already in DB
                    elseif (isset($seenFileFps[$fileFp])) $dupOf = $seenFileFps[$fileFp];  // >=0 → earlier file row

                    if ($dupOf !== null) {
                        $previewRows[$pi]['is_duplicate'] = true;
                        $previewRows[$pi]['status'] = 'invalid'; // duplicates are not importable
                        $et = $previewRows[$pi]['error_types'] ?? [];
                        if (!in_array('duplicate', $et, true)) $et[] = 'duplicate';
                        $previewRows[$pi]['error_types'] = $et;
                        $errorSummary['duplicate'] = ($errorSummary['duplicate'] ?? 0) + 1;
                        // dup_of: >= 0 → earlier file row (original "Ready" row); -1 → exists in DB.
                        $previewRows[$pi]['dup_of'] = $dupOf;
                    } else {
                        // First occurrence in the file — remember its 6-field fp so a later row
                        // that is identical INCLUDING Entry By gets flagged.
                        $seenFileFps[$fileFp] = $pi;
                    }
                }
            } catch (\Throwable $e) {
                // Duplicate detection is best-effort in preview — never block the response.
                \Log::warning('IMPORT_PREVIEW_DUP_CHECK_FAILED', ['msg' => $e->getMessage()]);
            }

            // Strip the internal _parsed payload before returning to the client.
            foreach ($previewRows as &$pr) { unset($pr['_parsed']); }
            unset($pr);

            // Add "Status" to headers
            $rawHeaders[] = 'Status';

            $totalRows = count($previewRows);
            $validRows = count(array_filter($previewRows, fn($r) => $r['status'] === 'valid'));
            $invalidRows = $totalRows - $validRows;
            // Duplicate rows = rows tagged 'duplicate' by the preview duplicate pass.
            // These are a SUBSET of $invalidRows (a duplicate row is also marked invalid/not-importable).
            $duplicateRows = count(array_filter($previewRows, function($r){
                return in_array('duplicate', $r['error_types'] ?? [], true);
            }));
            return response()->json([
                'success' => true,
                'headers' => $rawHeaders,
                'rows' => $previewRows,
                'errors' => $errors,
                'error_summary' => $errorSummary,
                // total = importable rows in the preview. Summary rows are dropped silently so
                // the user is never asked to fix something the system already knows to skip.
                'total' => $totalRows,
                'valid' => $validRows,
                'invalid' => $invalidRows,
                // duplicates is a subset of invalid — surfaced separately for its own stat card / filter.
                'duplicates' => $duplicateRows,
                'skipped_summary' => $skippedSummaryCount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to parse file: ' . $e->getMessage()]);
        }
    }

    /**
     * Import the uploaded Excel file into the database.
     */
    public function import(Request $request, StockProducts $stockProducts)
    {
        // ── Unlimited execution & memory: huge Excel imports must never get killed mid-flight ──
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '3600');
        @ini_set('memory_limit', '1024M');
        @ini_set('default_socket_timeout', '3600');
        // Keep TCP connection alive: tell PHP to flush headers immediately and disable user-abort kill,
        // so even if the browser drops the request, the server keeps processing.
        ignore_user_abort(true);
        @ini_set('zlib.output_compression', 'Off'); // avoid buffer hold-back that triggers 504

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'type' => 'required|in:sales,purchase',
        ]);

        try {
            $rows = $this->parseFile($request->file('file'));
            $type = $request->type;

            if (empty($rows)) {
                return response()->json(['success' => false, 'message' => 'The file is empty.']);
            }

            // Apply user-chosen field mapping if provided
            $mapping = $request->input('mapping');
            $mappingArr = null;
            if ($mapping) {
                $mappingArr = is_array($mapping) ? $mapping : json_decode($mapping, true);
                $rows = $this->applyMapping($rows, $mappingArr);
            }

            // Build the column-index → row-key list that matches the preview header order.
            // Used to translate edited_rows keyed by column index into row keys.
            $previewKeys = [];
            if (is_array($mappingArr) && !empty($mappingArr)) {
                foreach ($this->systemFields($type) as $sf) {
                    $srcCol = $mappingArr[$sf['key']] ?? null;
                    if ($srcCol === null || $srcCol === '') continue;
                    $previewKeys[] = $sf['key'];
                }
            } elseif (!empty($rows)) {
                foreach (array_keys($rows[0]) as $key) {
                    if (in_array($key, ['sl_no', 'slno', 's_no', 'sno', 'sr_no', 'srno'])) continue;
                    $previewKeys[] = $key;
                }
            }

            // Apply client-side edits if provided
            $editedRows = $request->input('edited_rows');
            if ($editedRows) {
                $editedData = is_array($editedRows) ? $editedRows : json_decode($editedRows, true);
                if (is_array($editedData)) {
                    foreach ($editedData as $idx => $edits) {
                        if (!isset($rows[$idx]) || !is_array($edits)) continue;
                        foreach ($edits as $key => $value) {
                            // If the edit key is a numeric column index, translate it to the
                            // corresponding row key from the preview header order.
                            $resolvedKey = $key;
                            if (is_numeric($key) && isset($previewKeys[(int)$key])) {
                                $resolvedKey = $previewKeys[(int)$key];
                            }
                            $rows[$idx][$resolvedKey] = $value;
                            // Also fan-out to all known aliases so parseRow's fuzzy lookup hits the value
                            // regardless of which alias parseRow tries first.
                            foreach ($this->systemFields($type) as $sf) {
                                if ($sf['key'] === $resolvedKey || in_array($resolvedKey, $sf['aliases'], true)) {
                                    foreach ($sf['aliases'] as $alias) {
                                        $rows[$idx][$alias] = $value;
                                    }
                                    $rows[$idx][$sf['key']] = $value;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // Filter by selected rows if provided
            $selectedRows = $request->input('selected_rows');
            if ($selectedRows) {
                $selectedIndices = json_decode($selectedRows, true);
                if (is_array($selectedIndices)) {
                    $filteredRows = [];
                    foreach ($selectedIndices as $idx) {
                        if (isset($rows[$idx])) {
                            $filteredRows[] = $rows[$idx];
                        }
                    }
                    $rows = $filteredRows;
                }
            }

            // ── Chunked-import support ──
            // When the client sends `chunk_start` and `chunk_size`, only that slice is processed.
            // The same file is uploaded with each chunk; the slice keeps each request short enough
            // to fit inside Apache's 60s ProxyTimeout. Client polls progress by sending sequential
            // chunks until `chunk_start + chunk_size >= total_rows`.
            $chunkStart = $request->input('chunk_start');
            $chunkSize  = $request->input('chunk_size');
            $isChunked  = ($chunkStart !== null && $chunkSize !== null && is_numeric($chunkStart) && is_numeric($chunkSize));
            $totalRowsBeforeSlice = count($rows);
            if ($isChunked) {
                $chunkStart = (int)$chunkStart;
                $chunkSize  = (int)$chunkSize;
                $rows = array_slice($rows, $chunkStart, $chunkSize);
            }

            DB::beginTransaction();

            $imported = 0;
            $skipped = 0;
            $errorMessages = [];

            if ($type === 'sales') {
                $result = $this->importSales($rows, $stockProducts);
            } else {
                $result = $this->importPurchase($rows);
            }

            DB::commit();

            // Duplicate count comes straight from the row-level guard inside importSales/importPurchase
            // — the SAME full-row exact-match logic the preview uses, so preview and import always agree.
            $duplicateCount = (int)($result['duplicates'] ?? 0);

            $msg = $result['imported'] . ' rows imported, ' . $result['skipped'] . ' skipped';
            if ($duplicateCount > 0) {
                $msg .= ' (' . $duplicateCount . ' duplicate row' . ($duplicateCount !== 1 ? 's' : '') . ' detected and skipped)';
            }
            $msg .= '.';
            if ($result['imported'] === 0 && !empty($result['errors'])) {
                // If nothing was imported, lead with the first error so the user sees why
                $msg .= ' First issue: ' . $result['errors'][0];
            }

            $response = [
                'success' => true,
                'message' => $msg,
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
                'duplicates' => $duplicateCount,
                'errors' => $result['errors'],
            ];
            // For chunked mode, also return progress metadata so the client knows when to stop
            if ($isChunked) {
                $response['total_rows'] = $totalRowsBeforeSlice;
                $response['chunk_start'] = $chunkStart;
                $response['chunk_size'] = $chunkSize;
                $response['chunk_end'] = $chunkStart + count($rows);
                $response['has_more'] = ($chunkStart + count($rows)) < $totalRowsBeforeSlice;
            }
            // Mark success=false only for non-chunked failures where nothing imported & no duplicates seen
            if (!$isChunked && $result['imported'] === 0 && $duplicateCount === 0) {
                $response['success'] = !empty($result['errors']) ? false : true;
            }
            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Return all active customers (id + name) for the Sales-Import "Fix Errors" dropdown.
     */
    public function listCustomers()
    {
        $rows = Customer::orderBy('name')->get(['id', 'name'])->map(function ($c) {
            return ['id' => $c->id, 'name' => $c->name];
        });
        return response()->json(['success' => true, 'items' => $rows]);
    }

    /**
     * Return all active suppliers (id + name) for the Sales-Import "Fix Errors" dropdown.
     */
    public function listSuppliers()
    {
        $rows = Supplier::orderBy('name')->get(['id', 'name'])->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->name];
        });
        return response()->json(['success' => true, 'items' => $rows]);
    }

    /**
     * Lightweight create endpoint used by the import-error fix dropdown. Only requires `name`.
     * Mirrors the legacy customer-create flow's id stamping so downstream features keep working.
     */
    public function quickAddCustomer(Request $request)
    {
        $name = trim((string)$request->input('name'));
        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'Customer name is required.'], 422);
        }
        // Reuse existing customer if a row with the same name already exists (case-insensitive)
        $existing = Customer::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json(['success' => true, 'item' => ['id' => $existing->id, 'name' => $existing->name], 'reused' => true]);
        }
        $customer = new Customer();
        $customer->name = $name;
        // Optional fields (sent by the Sales-page inline create form; import flow omits them).
        $customer->email = $request->input('email');
        $customer->mobile = $request->input('mobile');
        $customer->address1 = $request->input('address1');
        $customer->credit_limit = $request->input('credit_limit');
        $customer->currency = $request->input('currency') ?: 'pound';
        $customer->is_active = 1;
        $customer->save();
        $customer->customer_id = 'C' . (100 + $customer->id);
        $customer->update();
        return response()->json(['success' => true, 'item' => ['id' => $customer->id, 'name' => $customer->name]]);
    }

    /**
     * Lightweight supplier create endpoint for the import-error fix dropdown.
     */
    public function quickAddSupplier(Request $request)
    {
        $name = trim((string)$request->input('name'));
        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'Supplier name is required.'], 422);
        }
        $existing = Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json(['success' => true, 'item' => ['id' => $existing->id, 'name' => $existing->name], 'reused' => true]);
        }
        $supplier = new Supplier();
        $supplier->name = $name;
        $supplier->is_active = 1;
        $supplier->save();
        $supplier->supplier_id = 'S' . (100 + $supplier->id);
        $supplier->update();
        return response()->json(['success' => true, 'item' => ['id' => $supplier->id, 'name' => $supplier->name]]);
    }

    /**
     * Return all active products (id + name) for the Sales/Purchase-Import "Fix Errors" dropdown.
     */
    public function listProducts()
    {
        $rows = Product::orderBy('name')->get(['id', 'name'])->map(function ($p) {
            return ['id' => $p->id, 'name' => $p->name];
        });
        return response()->json(['success' => true, 'items' => $rows]);
    }

    /**
     * Lightweight product create endpoint for the import-error fix dropdown.
     * Only requires `name` — same minimal pattern as Customer/Supplier quick-add. Full product
     * details (price, unit, category, stock) can be edited later from the Products page.
     */
    public function quickAddProduct(Request $request)
    {
        $name = trim((string)$request->input('name'));
        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'Product name is required.'], 422);
        }
        $existing = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json(['success' => true, 'item' => ['id' => $existing->id, 'name' => $existing->name], 'reused' => true]);
        }
        $product = new Product();
        $product->name = $name;
        // Optional fields (sent by the Sales-page inline create form; import flow omits them).
        $product->selling_price = $request->input('selling_price');
        $product->tax_vat = $request->input('tax_vat');
        $product->unit_weight = $request->input('unit_weight');
        $product->description = $request->input('description');
        $product->is_active = 1;
        $product->user_id = Auth::user()->id;
        $product->save();
        return response()->json(['success' => true, 'item' => ['id' => $product->id, 'name' => $product->name]]);
    }

    /**
     * Parse the Excel/CSV file into an array of associative rows.
     */
    private function parseFile($file)
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // For CSV files or when ZipArchive is not available, use CSV/manual parsing
        if ($ext === 'csv' || !class_exists('ZipArchive')) {
            if ($ext === 'csv') {
                $sheet = $this->parseCsv($file->getRealPath());
            } else {
                // Convert xlsx to csv-like array using a simple XML parser
                $sheet = $this->parseXlsxManual($file->getRealPath());
            }
        } else {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) { return $array; }
            }, $file);

            if (empty($data) || empty($data[0]) || count($data[0]) < 2) {
                return [];
            }
            $sheet = $data[0];
        }

        if (empty($sheet) || count($sheet) < 2) {
            return [];
        }

        $rawHeaders = array_shift($sheet);

        // Normalize headers: trim, lowercase, replace spaces with underscores
        $headers = [];
        $colIndex = 0;
        foreach ($rawHeaders as $h) {
            $normalized = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $h ?? ''), '_'));
            // If header is empty, assign a positional name like _col_0, _col_1
            if ($normalized === '') {
                $normalized = '_col_' . $colIndex;
            }
            // Handle duplicate headers by appending index
            if (in_array($normalized, $headers)) {
                $normalized = $normalized . '_' . $colIndex;
            }
            $headers[] = $normalized;
            $colIndex++;
        }

        $rows = [];
        foreach ($sheet as $row) {
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) continue;
            $assoc = [];
            foreach ($headers as $idx => $header) {
                $assoc[$header] = $row[$idx] ?? null;
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    /**
     * Parse a CSV file into a 2D array.
     */
    private function parseCsv($path)
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Parse an XLSX file using pure PHP zip reading (no ZipArchive needed).
     * Reads the ZIP binary format directly from the file.
     */
    private function parseXlsxManual($path)
    {
        $zipEntries = $this->readZipEntries($path);

        // Get shared strings (strip XML namespace to make SimpleXML work)
        $sharedStrings = [];
        if (isset($zipEntries['xl/sharedStrings.xml'])) {
            $cleanXml = $this->stripXmlNamespaces($zipEntries['xl/sharedStrings.xml']);
            $xml = @simplexml_load_string($cleanXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Get sheet1
        $sheetXmlContent = null;
        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/Sheet1.xml'] as $sheetPath) {
            if (isset($zipEntries[$sheetPath])) {
                $sheetXmlContent = $zipEntries[$sheetPath];
                break;
            }
        }

        if (!$sheetXmlContent) {
            throw new \Exception('Could not find worksheet in the Excel file.');
        }

        $cleanXml = $this->stripXmlNamespaces($sheetXmlContent);
        $xml = @simplexml_load_string($cleanXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            throw new \Exception('The Excel file appears to be empty.');
        }

        $sheetData = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];

            foreach ($row->c as $cell) {
                $attrs = $cell->attributes();
                $ref = (string) $attrs['r'];
                $type = isset($attrs['t']) ? (string) $attrs['t'] : '';

                preg_match('/^([A-Z]+)/', $ref, $m);
                $colIndex = $this->colToIndex($m[1]);

                $value = '';

                if ($type === 'inlineStr' && isset($cell->is->t)) {
                    // Inline string: <c t="inlineStr"><is><t>value</t></is></c>
                    $value = (string) $cell->is->t;
                } elseif (isset($cell->is->t)) {
                    // Inline string without explicit type
                    $value = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    // Normal value or shared string reference
                    $value = (string) $cell->v;
                    if ($type === 's' && isset($sharedStrings[(int)$value])) {
                        $value = $sharedStrings[(int)$value];
                    }
                }

                while (count($rowData) <= $colIndex) {
                    $rowData[] = null;
                }
                $rowData[$colIndex] = $value;
            }

            $sheetData[] = $rowData;
        }

        // Normalize row lengths
        if (!empty($sheetData)) {
            $maxCols = max(array_map('count', $sheetData));
            foreach ($sheetData as &$row) {
                while (count($row) < $maxCols) {
                    $row[] = null;
                }
            }
        }

        return $sheetData;
    }

    /**
     * Pure PHP zip reader - reads ZIP file entries without ZipArchive.
     * Parses the ZIP binary format (End of Central Directory → Central Directory → File data).
     */
    private function readZipEntries($path)
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new \Exception('Could not read the file.');
        }

        $size = strlen($data);

        // Find End of Central Directory record (search backwards for signature 0x06054b50)
        $eocdPos = false;
        for ($i = $size - 22; $i >= max(0, $size - 65557); $i--) {
            if (substr($data, $i, 4) === "\x50\x4b\x05\x06") {
                $eocdPos = $i;
                break;
            }
        }

        if ($eocdPos === false) {
            throw new \Exception('Invalid Excel file (not a valid zip).');
        }

        // Parse EOCD
        $eocd = unpack('vdisk/vcdDisk/vcdEntriesDisk/vcdEntries/VcdSize/VcdOffset/vcommentLen', substr($data, $eocdPos + 4, 18));
        $cdOffset = $eocd['cdOffset'];
        $cdEntries = $eocd['cdEntries'];

        $entries = [];
        $pos = $cdOffset;

        for ($e = 0; $e < $cdEntries; $e++) {
            if (substr($data, $pos, 4) !== "\x50\x4b\x01\x02") break;

            $header = unpack(
                'vversionMade/vversionNeeded/vflags/vmethod/vmtime/vmdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdiskStart/vintAttrs/VextAttrs/VlocalOffset',
                substr($data, $pos + 4, 42)
            );

            $fileName = substr($data, $pos + 46, $header['nameLen']);
            $pos += 46 + $header['nameLen'] + $header['extraLen'] + $header['commentLen'];

            // Only extract XML files we need
            if (!str_contains($fileName, '.xml')) continue;

            // Read from local file header
            $localPos = $header['localOffset'];
            if (substr($data, $localPos, 4) !== "\x50\x4b\x03\x04") continue;

            $local = unpack('vversionNeeded/vflags/vmethod/vmtime/vmdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen', substr($data, $localPos + 4, 26));
            $fileDataStart = $localPos + 30 + $local['nameLen'] + $local['extraLen'];
            $compressedData = substr($data, $fileDataStart, $header['compSize']);

            if ($header['method'] === 0) {
                // Stored (no compression)
                $entries[$fileName] = $compressedData;
            } elseif ($header['method'] === 8) {
                // Deflated
                $decompressed = @gzinflate($compressedData);
                if ($decompressed !== false) {
                    $entries[$fileName] = $decompressed;
                }
            }
        }

        return $entries;
    }

    /**
     * Strip XML namespaces so SimpleXML can access elements without namespace prefix.
     */
    private function stripXmlNamespaces($xml)
    {
        // Remove default namespace declarations
        $xml = preg_replace('/\s+xmlns\s*=\s*"[^"]*"/', '', $xml);
        // Remove prefixed namespace declarations
        $xml = preg_replace('/\s+xmlns:\w+\s*=\s*"[^"]*"/', '', $xml);
        // Remove namespace prefixes from tags (e.g., <x:row> → <row>)
        $xml = preg_replace('/<(\/?)\w+:/', '<$1', $xml);
        return $xml;
    }

    /**
     * Convert Excel column letter to zero-based index (A=0, B=1, AA=26, etc.)
     */
    private function colToIndex($col)
    {
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Parse and validate a single row for preview.
     */
    private function parseRow(array $row, string $type, int $rowNum)
    {
        $errors = [];
        $errorTypes = [];
        $status = 'valid';

        // Normalize field names - try common variations.
        // NOTE: `entry_by` here is the SUPPLIER name (legacy naming — kept for back-compat with
        // existing Excel templates). The salesman/staff who created the entry is read into
        // `salesmanName` from a separate "Entry By" column when mapped.
        $productName  = $this->getFieldExact($row, ['product_name', 'product', 'item', 'item_name']);
        $quantity     = $this->getFieldExact($row, ['quantity', 'qty']);
        $unitPrice    = $this->getFieldExact($row, ['unitprice', 'unit_price', 'price', 'rate']);
        $total        = $this->getFieldExact($row, ['total', 'amount', 'sub_total', 'subtotal']);
        $entryBy      = $this->getFieldExact($row, ['entry_by', 'supplier_name', 'supplier']); // SUPPLIER name (legacy alias entry_by)
        $salesmanName = $this->getFieldExact($row, ['salesman_name', 'salesman', 'salesperson', 'created_by', 'user', 'staff', 'entryby']); // Staff/User who entered the row
        $sellPrice    = $this->getFieldExact($row, ['sell_price', 'sale_price', 'selling_price']);
        $paidAmount   = $this->getFieldExact($row, ['paid_amount', 'debit', 'paid', 'amount_paid', 'received']);
        $returnQty    = $this->getFieldExact($row, ['return_qty', 'return', 'returns', 'returned']);
        $dumpQty      = $this->getFieldExact($row, ['dump_qty', 'dump', 'dumps', 'dumped']);

        if ($type === 'sales') {
            $date = $this->getFieldExact($row, ['date', 'inv_date']);
            $invoiceNo = $this->getFieldExact($row, ['invoice_no', 'invoice_number', 'inv_no']);
            $entityName = $this->getFieldExact($row, ['customer_name', 'customer', 'party_name', 'party']);
        } else {
            // Purchase files may have different column layouts
            $date = $this->getFieldExact($row, ['date', 'inv_date']);
            $entityName = $this->getFieldExact($row, ['supplier_name', 'supplier', 'party_name', 'party']);
            if (empty($entityName)) {
                $entityName = $this->getFieldExact($row, ['inv_no']);
            }
            $invoiceNo = $this->getFieldExact($row, ['invoice_no', 'invoice_number']);
        }

        // Fallback: if date is empty, check unnamed columns (_col_X) for date-like values
        if (empty($date)) {
            foreach ($row as $k => $v) {
                if (strpos($k, '_col_') === 0 && $v !== null && $v !== '') {
                    // Check if value looks like a date
                    if (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}/', $v) || preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}/', $v)) {
                        $date = $v;
                        break;
                    }
                }
            }
        }

        // Detect summary/total rows (e.g. last row with "TOTAL"/"GRAND TOTAL"/"SUM" word).
        // Triggers via three independent heuristics — any single match flags the row as a summary:
        //   (1) any KEY field literally contains a summary word ("total", "grand total", "sum", "subtotal", "totals")
        //   (2) ANY cell across the whole raw row contains a summary word (handles cases where "Total"
        //       sits in an unmapped column like Excel column A while qty/price look like valid numbers)
        //   (3) the row has no entity (customer/supplier) AND no product AND no date AND no invoice no,
        //       but has a numeric quantity OR total — that's the signature of a "Total" footer row
        //       that's accidentally being treated as data.
        // Expanded summary keyword list — covers all common Excel footer wording.
        $summaryWords = [
            'total', 'grand total', 'subtotal', 'sub total', 'sum', 'totals',
            'grand-total', 'sub-total', 'overall total', 'final total', 'net total',
            'gross total', 'total amount', 'total price', 'total cost', 'total value',
            'totales', 'totale', 'totalt', 'итого', 'सम्पूर्ण', // i18n variants
        ];
        $isSummary = function($v) use ($summaryWords) {
            if ($v === null || $v === '') return false;
            $val = strtolower(trim((string)$v));
            if (in_array($val, $summaryWords, true)) return true;
            // Loose match: word "total" anywhere in the cell, e.g. "GRAND TOTAL:", "Sub-Total =>", "Final Total"
            if (preg_match('/\b(grand\s*total|sub[\s\-]?total|final\s*total|net\s*total|gross\s*total|overall\s*total|total\s*amount|total\s*price)\b/i', $val)) return true;
            return false;
        };
        // Helper: detect currency-formatted cells like "£4,782.50" / "$1,234" / "₹500" / "€99.99"
        $isCurrencyAmount = function($v) {
            if ($v === null || $v === '') return false;
            $s = trim((string)$v);
            return (bool)preg_match('/^[£$€₹¥]\s*[\d,]+(\.\d+)?$/u', $s);
        };

        // Heuristic 1: explicit summary word in a key field
        $hitKeyField = $isSummary($date) || $isSummary($entityName) || $isSummary($productName) || $isSummary($invoiceNo ?? null);

        // Heuristic 2: any cell in the raw row contains a summary word (case-insensitive exact match
        // after trimming). Catches "Total" sitting in any column — mapped, unmapped, or stripped.
        $hitAnyCell = false;
        if (!$hitKeyField) {
            foreach ($row as $cellVal) {
                if ($isSummary($cellVal)) { $hitAnyCell = true; break; }
            }
        }

        // Heuristic 3: structural shape of a footer row — no identifying fields, but numbers present.
        // Real data always has at least one of: customer/supplier, product, date, invoice no.
        // A row that has ONLY numbers and no identifiers is almost certainly an Excel footer.
        $hitShape = false;
        if (!$hitKeyField && !$hitAnyCell) {
            $hasEntity   = !empty(trim((string)($entityName ?? '')));
            $hasProduct  = !empty(trim((string)($productName ?? '')));
            $hasDate     = !empty(trim((string)($date ?? '')));
            $hasInvoice  = !empty(trim((string)($invoiceNo ?? '')));
            $hasNumber   = (is_numeric($quantity) && (float)$quantity > 0)
                        || (is_numeric($total) && (float)$total > 0)
                        || (is_numeric($unitPrice) && (float)$unitPrice > 0);
            if (!$hasEntity && !$hasProduct && !$hasDate && !$hasInvoice && $hasNumber) {
                $hitShape = true;
            }
        }

        // Heuristic 4: "N items" / "N rows" / "N records" pattern in product column.
        // Excel summary footers often write "30 Items" or "30 items" in a column — that's a
        // count label, not a product name. Combined with missing date + missing entity + £-style
        // total, this is unambiguously a summary row that earlier heuristics miss.
        $hitItemCount = false;
        if (!$hitKeyField && !$hitAnyCell && !$hitShape) {
            $prodTrim = trim((string)($productName ?? ''));
            $entityTrim = trim((string)($entityName ?? ''));
            $dateTrim = trim((string)($date ?? ''));
            // Matches "30 Items", "30 ITEM", "30 rows", "30 records", "30 lines" (case-insensitive)
            if ($prodTrim !== '' && preg_match('/^\s*\d+\s*(items?|rows?|records?|lines?|entries?)\s*$/i', $prodTrim)) {
                // Require at least one of date/entity to be MISSING — real product rows always have both.
                if ($entityTrim === '' || $dateTrim === '') {
                    $hitItemCount = true;
                }
            }
        }

        // Heuristic 5: Currency-prefixed cell anywhere in the row (e.g. "£ 4782.50").
        // Excel summary footers commonly stringify the totals column as "£ 4782.50" instead of plain numeric.
        // Combined with missing date / missing entity, this is a footer row.
        $hitCurrency = false;
        if (!$hitKeyField && !$hitAnyCell && !$hitShape && !$hitItemCount) {
            $entityTrim2 = trim((string)($entityName ?? ''));
            $dateTrim2 = trim((string)($date ?? ''));
            $prodTrim2 = trim((string)($productName ?? ''));
            // Either entity OR date missing AND any cell has a currency-prefix value
            if ($entityTrim2 === '' || $dateTrim2 === '' || $prodTrim2 === '') {
                foreach ($row as $cellVal) {
                    if ($isCurrencyAmount($cellVal)) { $hitCurrency = true; break; }
                }
            }
        }

        // Heuristic 6: structural — date+entity+invoice all missing, but ANY numeric values present.
        // Catches footer rows that just have qty/total/price numbers and no identifying text at all.
        // This is broader than Heuristic 3 because it allows productName to be present (e.g. "30 Items").
        $hitFooterShape = false;
        if (!$hitKeyField && !$hitAnyCell && !$hitShape && !$hitItemCount && !$hitCurrency) {
            $hasEntity2  = !empty(trim((string)($entityName ?? '')));
            $hasDate2    = !empty(trim((string)($date ?? '')));
            $hasInvoice2 = !empty(trim((string)($invoiceNo ?? '')));
            // All identifying fields missing
            if (!$hasEntity2 && !$hasDate2 && !$hasInvoice2) {
                // And the row has at least one numeric cell (total/qty/price/etc.)
                $anyNumeric = false;
                foreach ($row as $cellVal) {
                    if (is_numeric($cellVal) && (float)$cellVal != 0) { $anyNumeric = true; break; }
                }
                if ($anyNumeric) $hitFooterShape = true;
            }
        }

        if ($hitKeyField || $hitAnyCell || $hitShape || $hitItemCount || $hitCurrency || $hitFooterShape) {
            // Date column has "TOTAL" — surface that as invalid_date so the cell highlights red and user can correct/remove it.
            $errorTypes = ['invalid_date', 'summary_row'];
            return [
                'data' => [
                    'date' => is_string($date) ? $date : '',
                    'invoice_no' => $invoiceNo ?? '',
                    'entity_name' => $entityName ?? '',
                    'product_name' => $productName ?? '',
                    'quantity' => $quantity ?? '',
                    'unit_price' => $unitPrice ?? '',
                    'total' => $total ?? '',
                    'entry_by' => $entryBy ?? '',
                    'salesman_name' => $salesmanName ?? '',
                    'sell_price' => $sellPrice ?? '',
                    'paid_amount' => $paidAmount ?? '',
                    'return_qty' => $returnQty ?? '',
                    'dump_qty' => $dumpQty ?? '',
                    'status' => 'invalid',
                    'error_types' => $errorTypes,
                ],
                'errors' => ["Row $rowNum: Looks like a summary/total row — skipped."],
            ];
        }

        // Validate required fields
        if ($type === 'purchase' && empty($entityName)) {
            $errors[] = "Row $rowNum: Supplier name is required.";
            $errorTypes[] = 'missing_supplier';
            $status = 'invalid';
        }
        if ($type === 'sales' && empty($entityName)) {
            $errors[] = "Row $rowNum: Customer name is required.";
            $errorTypes[] = 'missing_customer';
            $status = 'invalid';
        }
        if (empty($productName)) {
            $errors[] = "Row $rowNum: Product name is required.";
            $errorTypes[] = 'missing_product';
            $status = 'invalid';
        }

        // ── Validation rules (Sales & Purchase) ──
        // qty / unit_price / total / sell_price / paid / return / dump:
        //   • Blank where required → error (missing_*)
        //   • Non-numeric string (e.g. "abc") → error (invalid_*)
        //   • Negative numeric → SILENTLY converted to positive (abs), no error
        // Sales allows unit_price=0 and total=0 (free items); Purchase rejects zero.
        $isPurchase = ($type === 'purchase');

        // Quantity — required, numeric, non-zero. Negatives auto-converted to positive.
        $qtyStr = trim((string)($quantity ?? ''));
        if ($qtyStr === '' || $qtyStr === '-') {
            $errors[] = "Row $rowNum: Quantity is required.";
            $errorTypes[] = 'missing_quantity';
            $status = 'invalid';
        } elseif (!is_numeric($qtyStr)) {
            $errors[] = "Row $rowNum: Quantity must be a number.";
            $errorTypes[] = 'invalid_quantity';
            $status = 'invalid';
        } else {
            if ((float)$qtyStr < 0) { $quantity = abs((float)$qtyStr); }
            if ((float)$quantity == 0) {
                $errors[] = "Row $rowNum: Quantity cannot be zero.";
                $errorTypes[] = 'missing_quantity';
                $status = 'invalid';
            }
        }

        // Unit Price — required, numeric. Negatives auto-converted to positive.
        $priceStr = trim((string)($unitPrice ?? ''));
        if ($priceStr === '' || $priceStr === '-') {
            $errors[] = "Row $rowNum: Unit price is required.";
            $errorTypes[] = 'missing_price';
            $status = 'invalid';
        } elseif (!is_numeric($priceStr)) {
            $errors[] = "Row $rowNum: Unit price must be a number.";
            $errorTypes[] = 'invalid_price';
            $status = 'invalid';
        } else {
            if ((float)$priceStr < 0) { $unitPrice = abs((float)$priceStr); }
            if ($isPurchase && (float)$unitPrice == 0) {
                $errors[] = "Row $rowNum: Unit price cannot be zero.";
                $errorTypes[] = 'missing_price';
                $status = 'invalid';
            }
        }

        // Total — required, numeric. Negatives auto-converted to positive.
        $totalStr = trim((string)($total ?? ''));
        if ($totalStr === '' || $totalStr === '-') {
            $errors[] = "Row $rowNum: Total is required.";
            $errorTypes[] = 'invalid_total';
            $status = 'invalid';
        } elseif (!is_numeric($totalStr)) {
            $errors[] = "Row $rowNum: Total must be a number.";
            $errorTypes[] = 'invalid_total';
            $status = 'invalid';
        } else {
            if ((float)$totalStr < 0) { $total = abs((float)$totalStr); }
            if ($isPurchase && (float)$total == 0) {
                $errors[] = "Row $rowNum: Total cannot be zero.";
                $errorTypes[] = 'invalid_total';
                $status = 'invalid';
            }
        }

        // Sell Price — OPTIONAL. Reject text when filled. Negatives auto-converted to positive.
        $sellStr = trim((string)($sellPrice ?? ''));
        if ($sellStr !== '' && $sellStr !== '-') {
            if (!is_numeric($sellStr)) {
                $errors[] = "Row $rowNum: Sell price must be a number.";
                $errorTypes[] = 'invalid_sell_price';
                $status = 'invalid';
            } elseif ((float)$sellStr < 0) {
                $sellPrice = abs((float)$sellStr);
            }
        }

        // Paid Amount — OPTIONAL. Reject text when filled. Negatives auto-converted to positive.
        $paidStr = trim((string)($paidAmount ?? ''));
        if ($paidStr !== '' && $paidStr !== '-') {
            if (!is_numeric($paidStr)) {
                $errors[] = "Row $rowNum: Paid amount must be a number.";
                $errorTypes[] = 'invalid_paid';
                $status = 'invalid';
            } elseif ((float)$paidStr < 0) {
                $paidAmount = abs((float)$paidStr);
            }
        }

        // Supplier Return / Customer Return — OPTIONAL. Reject text when filled.
        // Negatives are ALWAYS auto-converted to positive — a return is never negative.
        $retStr = trim((string)($returnQty ?? ''));
        if ($retStr !== '' && $retStr !== '-') {
            if (!is_numeric($retStr)) {
                $errors[] = "Row $rowNum: Return quantity must be a number.";
                $errorTypes[] = 'invalid_return_qty';
                $status = 'invalid';
            } else {
                $returnQty = abs((float)$retStr);
            }
        }

        // Dump — OPTIONAL. Reject text when filled.
        // Negatives are ALWAYS auto-converted to positive — a dump count is never negative.
        $dumpStr = trim((string)($dumpQty ?? ''));
        if ($dumpStr !== '' && $dumpStr !== '-') {
            if (!is_numeric($dumpStr)) {
                $errors[] = "Row $rowNum: Dump quantity must be a number.";
                $errorTypes[] = 'invalid_dump_qty';
                $status = 'invalid';
            } else {
                $dumpQty = abs((float)$dumpStr);
            }
        }

        // Date validation: if the cell contains date-shaped content (any recognizable digits/separators),
        // try every parser and silently fix mis-formatted but parseable values. If the cell contains
        // PURE TEXT with no date signal at all (e.g. "abc", "N/A", "Today"), surface as an error.
        if (!empty($date) || $date === '0' || $date === 0) {
            $isValidDate = false;
            if (is_numeric($date) && $date > 0 && $date < 100000) {
                try {
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$date);
                    $isValidDate = true;
                } catch (\Exception $e) {
                    $isValidDate = false;
                }
            } else {
                // Try UK d/m/Y first (so 27/04/2026 isn't rejected as US m/d/Y).
                try {
                    $this->parseDateString((string)$date);
                    $isValidDate = true;
                } catch (\Exception $e) {
                    $isValidDate = false;
                }
            }
            if (!$isValidDate) {
                // Decide between silent auto-fix vs hard error.
                //   - Pure text (no digits / no date-shaped separators) → error: "Date must be a valid date."
                //   - Date-shaped but odd format (digits + separator present) → silent auto-fix to null.
                $dateStr = trim((string)$date);
                $looksLikeDate = (bool)preg_match('/\d/', $dateStr) && (bool)preg_match('/[\-\/\.\s]/', $dateStr);
                $looksLikeDate = $looksLikeDate || (bool)preg_match('/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december)\b/i', $dateStr);
                if (!$looksLikeDate) {
                    $errors[] = "Row $rowNum: Date must be a valid date.";
                    $errorTypes[] = 'invalid_date';
                    $status = 'invalid';
                } else {
                    // Silent auto-fix: drop unparseable but date-shaped value; importer falls back to today.
                    $date = null;
                }
            }
        }

        // Try to parse the date — UK style (d/m/Y) is preferred over Carbon's US default.
        $parsedDate = null;
        if ($date) {
            try {
                if (is_numeric($date)) {
                    $parsedDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date))->format('d/m/Y');
                } else {
                    $parsedDate = $this->parseDateString((string)$date);
                }
            } catch (\Exception $e) {
                $parsedDate = $date;
            }
        }

        // Calculate total if not provided
        if (empty($total) && is_numeric($quantity) && is_numeric($unitPrice)) {
            $total = round($quantity * $unitPrice, 2);
        }

        return [
            'data' => [
                'date' => $parsedDate ?? '',
                'invoice_no' => $invoiceNo ?? '',
                'entity_name' => $entityName ?? ($type === 'sales' ? 'Cash Customer' : ''),
                'product_name' => $productName ?? '',
                'quantity' => $quantity ?? '',
                'unit_price' => $unitPrice ?? '',
                'total' => $total ?? '',
                'entry_by' => $entryBy ?? '',
                'salesman_name' => $salesmanName ?? '',
                'sell_price' => $sellPrice ?? '',
                'paid_amount' => $paidAmount ?? '',
                'return_qty' => $returnQty ?? '',
                'dump_qty' => $dumpQty ?? '',
                'status' => $status,
                'error_types' => $errorTypes,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Auto-detect a meaningful name for an unnamed column by inspecting its values.
     */
    private function detectColumnName(array $rows, string $key)
    {
        // Check first few non-empty values to guess column type
        $samples = [];
        foreach ($rows as $row) {
            $val = $row[$key] ?? null;
            if ($val !== null && $val !== '') {
                $samples[] = $val;
                if (count($samples) >= 5) break;
            }
        }

        if (empty($samples)) return '';

        // Check if values look like dates
        $dateCount = 0;
        foreach ($samples as $val) {
            if (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}/', $val) || preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}/', $val)) {
                $dateCount++;
            }
        }
        if ($dateCount >= count($samples) * 0.8) return 'Date';

        // Check if all numeric
        $numCount = 0;
        foreach ($samples as $val) {
            if (is_numeric($val)) $numCount++;
        }
        if ($numCount >= count($samples) * 0.8) return 'Number';

        return '';
    }

    /**
     * Profile a column's value characteristics by inspecting up to 20 non-empty samples.
     * Returns { samples, total, dateRatio, intRatio, floatRatio, smallIntRatio (0-9999),
     *          decimalRatio (has dot), textRatio, avgLen, maxLen, hasCurrencyHint }.
     * Used by valueAffinityScore() to guess which system field a column belongs to.
     */
    private function profileColumnValues(array $rows, string $key): array
    {
        $samples = [];
        foreach ($rows as $row) {
            $v = $row[$key] ?? null;
            if ($v === null) continue;
            $s = trim((string)$v);
            if ($s === '' || $s === '0' || $s === '0.00' || $s === '0.0') continue;
            $samples[] = $s;
            if (count($samples) >= 20) break;
        }
        $total = max(1, count($samples));
        $date = 0; $intCnt = 0; $floatCnt = 0; $smallInt = 0; $decimal = 0; $text = 0;
        $lenSum = 0; $maxLen = 0; $currencyHint = false;
        $uniqueLower = [];
        foreach ($samples as $s) {
            $lenSum += mb_strlen($s); if (mb_strlen($s) > $maxLen) $maxLen = mb_strlen($s);
            $uniqueLower[mb_strtolower($s)] = true;
            // Date detection — ISO yyyy-mm-dd, UK dd/mm/yyyy, US mm/dd/yyyy, or Excel serial
            $isDate = preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/', $s)
                   || preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/', $s);
            if (!$isDate && is_numeric($s) && (float)$s > 30000 && (float)$s < 80000) {
                $isDate = true; // Excel serial date range (roughly 1982-2119)
            }
            if ($isDate) { $date++; continue; }
            // Currency-prefixed numbers like "£12.50" or "$5.00"
            if (preg_match('/^[\£\$\€\¥]\s*\d+(\.\d{1,2})?$/', $s)) {
                $currencyHint = true; $floatCnt++; $decimal++; continue;
            }
            if (is_numeric($s)) {
                $n = (float)$s;
                if (strpos($s, '.') !== false || (abs($n - intval($n)) > 0)) { $floatCnt++; $decimal++; }
                else { $intCnt++; if ($n >= 0 && $n < 10000) $smallInt++; }
                continue;
            }
            // Otherwise treat as text
            $text++;
        }
        return [
            'samples'      => $samples,
            'total'        => $total,
            'dateRatio'    => $date / $total,
            'intRatio'     => $intCnt / $total,
            'floatRatio'   => $floatCnt / $total,
            'smallIntRatio'=> $smallInt / $total,
            'decimalRatio' => $decimal / $total,
            'textRatio'    => $text / $total,
            'avgLen'       => $lenSum / $total,
            'maxLen'       => $maxLen,
            'hasCurrencyHint' => $currencyHint,
            'uniqueRatio'  => count($uniqueLower) / $total, // high → many distinct (products), low → few repeats (customers/suppliers)
        ];
    }

    /**
     * Score how well a column's value profile matches a given system field key.
     * Returns 0..1 confidence. Used after header-label matching fails to pick the best column.
     */
    private function valueAffinityScore(string $sysKey, array $prof): float
    {
        $textOnly = $prof['textRatio'];
        $numericTotal = $prof['intRatio'] + $prof['floatRatio'];
        switch ($sysKey) {
            case 'date':
                return $prof['dateRatio']; // 0..1 directly
            case 'quantity':
                // Quantity: numeric, mostly very small ints (typically 1-200). Low cardinality
                // (lots of repeated values like 1, 2, 5, 10). Reject high-cardinality unique ints
                // (those look more like invoice numbers).
                if ($numericTotal < 0.6) return 0;
                $base = $prof['intRatio'] * 0.7 + 0.3 * $prof['floatRatio'];
                if ($prof['smallIntRatio'] >= 0.7) $base += 0.15;
                // Bonus if values repeat a lot (most rows share common qty values)
                if ($prof['uniqueRatio'] <= 0.4) $base += 0.1;
                // Penalize highly-unique integer columns (likely invoice no, not qty)
                if ($prof['uniqueRatio'] >= 0.8 && $prof['intRatio'] >= 0.7) $base -= 0.25;
                return max(0, min(1, $base));
            case 'unit_price':
            case 'sell_price':
            case 'total':
            case 'paid_amount':
                // Money: numeric with decimals, currency prefix bonus
                if ($numericTotal < 0.6) return 0;
                $base = $prof['floatRatio'] * 0.8 + $prof['intRatio'] * 0.4;
                if ($prof['decimalRatio'] >= 0.5) $base += 0.15;
                if ($prof['hasCurrencyHint']) $base += 0.2;
                return min(1, $base);
            case 'invoice_no':
                // Invoice no: short codes — either integers (typically grouped, repeated per invoice)
                // or short alphanumeric strings. Decisive winner on unique integer columns.
                if ($prof['dateRatio'] >= 0.5) return 0;
                if ($prof['decimalRatio'] >= 0.3) return 0; // decimals mean money, not invoice
                // Reject obvious name columns (long text + low uniqueness = entity)
                if ($textOnly >= 0.7 && $prof['avgLen'] > 14) return 0;
                if ($textOnly >= 0.7 && $prof['uniqueRatio'] < 0.3) return 0;
                // Integer column → strong invoice signal. Invoice numbers can repeat across rows
                // (multiple products on the same invoice), so accept any uniqueRatio.
                if ($prof['intRatio'] >= 0.7 && $prof['decimalRatio'] < 0.1) {
                    $score = 0.85; // base higher than return/dump (max 0.7) so invoice wins ties
                    if ($prof['uniqueRatio'] >= 0.4) $score += 0.10; // many distinct = mostly unique invoices
                    return min(1, $score);
                }
                // Short alphanumeric codes (invoice strings like INV001, BILL-001)
                if ($textOnly >= 0.7 && $prof['avgLen'] <= 14 && $prof['uniqueRatio'] >= 0.3) {
                    $score = 0.7;
                    if ($prof['uniqueRatio'] >= 0.6) $score += 0.15;
                    return min(1, $score);
                }
                return 0;
            case 'entity_name':       // Customer / Supplier (primary entity name) — usually low cardinality (repeats)
            case 'entry_by':          // Supplier in sales — also low cardinality (few suppliers, many rows)
            case 'salesman_name':     // Staff/User who entered the row — very low cardinality (only a handful of users)
                if ($prof['dateRatio'] >= 0.4) return 0;
                if ($textOnly < 0.5) return 0;
                $base = $textOnly;
                if ($prof['avgLen'] >= 3 && $prof['avgLen'] <= 60) $base += 0.1;
                // Bonus for low cardinality (≤30% unique → strong "entity" signal)
                if ($prof['uniqueRatio'] <= 0.35) $base += 0.15;
                return min(1, $base);
            case 'product_name':       // Products typically have higher cardinality (many distinct items)
                if ($prof['dateRatio'] >= 0.4) return 0;
                if ($textOnly < 0.5) return 0;
                $base = $textOnly;
                if ($prof['avgLen'] >= 3 && $prof['avgLen'] <= 60) $base += 0.1;
                // Bonus for higher cardinality
                if ($prof['uniqueRatio'] >= 0.45) $base += 0.15;
                return min(1, $base);
            case 'return_qty':
            case 'dump_qty':
                // Returns/Dumps: small repeated integers (often 0 or 1-10). Must be heavily numeric,
                // mostly small ints, AND with low cardinality (lots of repeats). Reject high-cardinality
                // unique integer columns — those are invoice numbers, not return/dump counts.
                if ($numericTotal < 0.6) return 0;
                if ($prof['intRatio'] < 0.7) return 0;
                if ($prof['smallIntRatio'] < 0.7) return 0;
                // Hard reject: unique-int columns belong to invoice_no, not return/dump
                if ($prof['uniqueRatio'] >= 0.6) return 0;
                $base = 0.55;
                if ($prof['uniqueRatio'] <= 0.3) $base += 0.15; // strong repeat signal
                return min(1, $base);
        }
        return 0;
    }

    /**
     * Strict rejection check used in the fallback label-match pass.
     * Returns true when the column data clearly does NOT belong to the given system field
     * (e.g. label says "Date" but column is full of text names). Lets the fallback skip
     * obviously wrong matches without requiring a high affinity score.
     */
    private function fieldContradictsProfile(string $sysKey, array $prof): bool
    {
        if (empty($prof['samples'])) return false; // nothing to check, allow
        $numericTotal = $prof['intRatio'] + $prof['floatRatio'];
        switch ($sysKey) {
            case 'date':
                // Date column should be predominantly date-like; reject pure text or pure numeric without dates
                return $prof['dateRatio'] < 0.3;
            case 'quantity':
            case 'return_qty':
            case 'dump_qty':
                return $numericTotal < 0.4; // mostly text → not a qty column
            case 'unit_price':
            case 'sell_price':
            case 'total':
            case 'paid_amount':
                return $numericTotal < 0.4; // mostly text → not money
            case 'entity_name':
            case 'product_name':
            case 'entry_by':
            case 'salesman_name':
                // Name fields shouldn't be predominantly numeric or dates
                return ($prof['dateRatio'] >= 0.5) || ($numericTotal >= 0.7 && $prof['textRatio'] < 0.2);
            case 'invoice_no':
                // Invoice no shouldn't be: dates, money (decimals), or long-text-with-low-uniqueness (names)
                if ($prof['dateRatio'] >= 0.5) return true;
                if ($prof['decimalRatio'] >= 0.3) return true;
                if ($prof['textRatio'] >= 0.7 && $prof['avgLen'] > 14) return true;
                if ($prof['textRatio'] >= 0.7 && $prof['uniqueRatio'] < 0.3) return true;
                return false;
        }
        return false;
    }

    /**
     * Build a Carbon instance from whatever parseRow() produced for a row's date field.
     * parseRow returns 'd/m/Y' strings, but we also accept any other format defensively.
     * Returns null when the input is empty/unparseable so callers can fall back to today.
     */

    /**
     * STRICT row-level exact-match fingerprint — the SINGLE source of truth for duplicate
     * detection, used by BOTH the preview pass and the actual import.
     *
     * Core identity: date + entity (customer/supplier) + product + quantity + unit price.
     *
     * `total` is EXCLUDED — the import recomputes it as qty × unit_price, so a file's raw total
     * vs a DB-recalculated total could falsely differ; qty + unit_price already pin the money side.
     * `sell_price` is EXCLUDED too (optional, stored NULL when blank — unreliable identity key).
     *
     * Invoice-number-PRIMARY identity (business-correct):
     *   - When the row HAS an invoice number, the fingerprint is keyed on
     *     invoice_no + entity + product + qty + unit price. The DATE is deliberately NOT used —
     *     the invoice number already identifies the transaction. Two rows with DIFFERENT invoice
     *     numbers are therefore ALWAYS different transactions (never duplicates), even if every
     *     other field matches — that is a genuine repeat sale/purchase, not a duplicate.
     *   - When the row has NO invoice number, there is no transaction ID to rely on, so the
     *     fingerprint falls back to date + entity + product + qty + unit price (content match).
     *
     * Entry-By (the staff member, $r['salesman_name']) is part of the identity ONLY for
     * file-vs-file comparison ($includeEntryBy = true). It is LEFT OUT of the DB-seed fingerprint:
     * the DB stores a numeric salesman_id, not the raw name, so id→name is unreliable.
     *
     * `total` and `sell_price` are EXCLUDED — total is recomputed (qty × price) and sell_price is
     * optional/NULL-when-blank; qty + unit_price already pin the money side.
     *
     * Numbers normalised (12 == 12.00); text trimmed + lower-cased; dates normalised to Y-m-d.
     *
     * $r keys used: invoice_no, date, entity_name, product_name, quantity, unit_price, salesman_name.
     */
    private function _buildExactRowFingerprint(array $r, bool $includeEntryBy = false): string
    {
        $normCell = function($v) {
            $s = trim((string)($v ?? ''));
            if ($s === '') return '';
            if (is_numeric($s)) {
                return rtrim(rtrim(number_format((float)$s, 4, '.', ''), '0'), '.');
            }
            return mb_strtolower($s);
        };
        // Normalise ANY date representation to a canonical Y-m-d so a file's "27/04/2026"
        // and the DB's "2026-04-27 12:00:00" produce the SAME fingerprint.
        // IMPORTANT: dd/mm/yyyy is handled EXPLICITLY first — Carbon::parse() would otherwise
        // read it as US m/d/Y, mangling or rejecting UK-style dates.
        $normDate = function($v) {
            if ($v === null || $v === '') return '';
            $s = trim((string)$v);
            if ($s === '') return '';
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $s, $m)) {
                return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
            }
            if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $s, $m)) {
                return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
            }
            try { return \Carbon\Carbon::parse($s)->format('Y-m-d'); }
            catch (\Throwable $e) { return mb_strtolower($s); }
        };

        // Invoice number — "0"/"0.0" count as MISSING (Excel often stores blanks as 0).
        $invNo = trim((string)($r['invoice_no'] ?? ''));
        if ($invNo !== '' && is_numeric($invNo) && (float)$invNo == 0) $invNo = '';

        if ($invNo !== '') {
            // Invoice number present → it IS the transaction key (date NOT part of the identity).
            $parts = [
                'inv:' . mb_strtolower($invNo),
                $normCell($r['entity_name'] ?? ''),
                $normCell($r['product_name'] ?? ''),
                $normCell($r['quantity'] ?? ''),
                $normCell($r['unit_price'] ?? ''),
            ];
        } else {
            // No invoice number → fall back to a date-based content fingerprint.
            $parts = [
                'noinv',
                $normDate($r['date'] ?? ''),
                $normCell($r['entity_name'] ?? ''),
                $normCell($r['product_name'] ?? ''),
                $normCell($r['quantity'] ?? ''),
                $normCell($r['unit_price'] ?? ''),
            ];
        }
        if ($includeEntryBy) {
            $parts[] = 'eb:' . $normCell($r['salesman_name'] ?? '');
        }
        return md5(implode('|', $parts));
    }

    /**
     * Build a deterministic content fingerprint from a group of import rows.
     * Two imports produce the same fingerprint iff they have the same line items
     * (same products, quantities, and unit prices — order-independent).
     * Used to detect re-uploads of files that lack invoice numbers.
     */
    private function _buildInvoiceFingerprint(array $products): string
    {
        $parts = [];
        foreach ($products as $p) {
            $name  = strtolower(trim((string)($p['product_name'] ?? '')));
            $qty   = (float)($p['quantity'] ?? 0);
            $price = (float)($p['unit_price'] ?? 0);
            $total = (float)($p['total'] ?? 0);
            $sell  = (float)($p['sell_price'] ?? 0);
            $remarks = strtolower(trim((string)($p['remarks'] ?? '')));
            if ($name === '' && $qty === 0.0 && $price === 0.0) continue;
            // Round numerics to 2 decimals to avoid floating-point noise
            $parts[] = $name
                . '|' . number_format($qty, 2, '.', '')
                . '|' . number_format($price, 2, '.', '')
                . '|' . number_format($total, 2, '.', '')
                . '|' . number_format($sell, 2, '.', '')
                . '|' . $remarks;
        }
        if (empty($parts)) return '';
        sort($parts); // order-independent
        return md5(implode(';', $parts));
    }

    /**
     * Build the same fingerprint from an existing DB invoice's loaded products relation.
     * Caller must have eager-loaded `products` (or `oneProduct`) on the invoice.
     */
    private function _buildInvoiceFingerprintFromDb($invoice): string
    {
        $parts = [];
        $rows = $invoice->products ?? collect();
        foreach ($rows as $r) {
            // Product name — pull from related product if available, else fall back to product_info JSON snapshot
            $name = '';
            try {
                $product = \App\Models\Product::find($r->product_id);
                if ($product && $product->name) $name = strtolower(trim((string)$product->name));
            } catch (\Throwable $e) {}
            if ($name === '' && !empty($r->product_info)) {
                $info = is_string($r->product_info) ? json_decode($r->product_info, true) : $r->product_info;
                if (is_array($info) && !empty($info['name'])) $name = strtolower(trim((string)$info['name']));
            }
            $qty   = (float)($r->quantity ?? 0);
            $price = (float)($r->unit_price ?? 0);
            $total = (float)($r->sub_total ?? ($qty * $price));
            $sell  = (float)($r->sale_price ?? 0);
            $remarks = strtolower(trim((string)($r->remarks ?? '')));
            if ($name === '' && $qty === 0.0 && $price === 0.0) continue;
            $parts[] = $name
                . '|' . number_format($qty, 2, '.', '')
                . '|' . number_format($price, 2, '.', '')
                . '|' . number_format($total, 2, '.', '')
                . '|' . number_format($sell, 2, '.', '')
                . '|' . $remarks;
        }
        if (empty($parts)) return '';
        sort($parts);
        return md5(implode(';', $parts));
    }

    private function buildCarbonFromParsedDate($value): ?Carbon
    {
        if ($value === null || $value === '' || $value === false) return null;
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        $str = trim((string)$value);
        if ($str === '') return null;

        // Excel serial number passthrough
        if (is_numeric($str) && (float)$str > 0 && (float)$str < 100000) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$str));
            } catch (\Exception $e) {
                // fall through to text parsing
            }
        }

        // Preferred: d/m/Y (UK locale, what parseRow produces)
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $m)) {
            $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3];
            if ($d >= 1 && $d <= 31 && $mo >= 1 && $mo <= 12) {
                try {
                    return Carbon::createFromFormat('!d/m/Y', sprintf('%02d/%02d/%04d', $d, $mo, $y));
                } catch (\Exception $e) {}
            }
        }

        // ISO Y-m-d
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $str, $m)) {
            try {
                return Carbon::createFromFormat('!Y-m-d', sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]));
            } catch (\Exception $e) {}
        }

        // Final fallback — let Carbon try, but reject silently if it throws.
        try {
            return Carbon::parse($str);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a date string trying UK formats first (day-first) before Carbon's US default.
     * Returns formatted "d/m/Y" string, or throws on total failure.
     */
    private function parseDateString(string $date): string
    {
        $date = trim($date);
        // 1. ISO yyyy-mm-dd or yyyy/mm/dd — unambiguous
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $date, $m)) {
            return Carbon::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]))->format('d/m/Y');
        }
        // 2. dd/mm/yyyy or dd-mm-yyyy — UK style (preferred)
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $date, $m)) {
            $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3];
            // Sanity: if first part > 12 we know it's d/m/Y (month can't be >12).
            // Otherwise also assume UK d/m/Y by user's confirmed locale.
            if ($d <= 31 && $mo <= 12) {
                return Carbon::createFromFormat('d/m/Y', sprintf('%02d/%02d/%04d', $d, $mo, $y))->format('d/m/Y');
            }
        }
        // 3. Last resort — let Carbon try (US m/d/Y or text dates)
        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * Get a field value by exact key match first, then fuzzy match.
     */
    private function getFieldExact(array $row, array $possibleKeys)
    {
        // Exact match first
        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    /**
     * Get a field value by trying multiple possible column names (fuzzy).
     */
    private function getField(array $row, array $possibleKeys)
    {
        // Exact match first
        $result = $this->getFieldExact($row, $possibleKeys);
        if ($result !== null) return $result;

        // Fuzzy match - check if any key contains the search term
        foreach ($row as $k => $v) {
            foreach ($possibleKeys as $key) {
                if (str_contains($k, $key) && $v !== null && $v !== '') {
                    return $v;
                }
            }
        }
        return null;
    }

    /**
     * Import sales data - groups rows by invoice_no to create invoices.
     */
    private function importSales(array $rows, StockProducts $stockProducts)
    {
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $errors = [];
        $debugLogCount = 0;

        // ── Row-level duplicate guard — IDENTICAL logic to the preview pass ──
        // Two fingerprint maps:
        //   $dbRowFps     — 5-core-field fp of every EXISTING DB record (Entry By NOT included,
        //                   since the DB stores a numeric salesman_id, not the raw name).
        //   $seenFileFps  — 6-field fp (core + Entry By) of file rows already seen in THIS run,
        //                   so two file rows with a different Entry By are correctly NOT duplicates.
        $dbRowFps = [];
        $seenFileFps = [];
        try {
            $hasOtherInv = \Schema::hasColumn('customer_invoices', 'other_invoice_id');
            $cols = $hasOtherInv ? ['id','customer_id','created_at','other_invoice_id'] : ['id','customer_id','created_at'];
            $existing = CustomerInvoice::with(['products' => function($q){ $q->where('is_archive', 0); }])
                ->get($cols);
            $custNameById = Customer::pluck('name','id')->all();
            // Cache product names so we don't hit the DB once per product line.
            $prodNameById = \App\Models\Product::pluck('name','id')->all();
            foreach ($existing as $inv) {
                $entName = $custNameById[$inv->customer_id] ?? '';
                $invNo  = $hasOtherInv ? (string)($inv->other_invoice_id ?? '') : '';
                foreach (($inv->products ?? []) as $p) {
                    $prodName = $prodNameById[$p->product_id] ?? '';
                    $fp = $this->_buildExactRowFingerprint([
                        'invoice_no' => $invNo, 'date' => $inv->created_at,
                        'entity_name' => $entName, 'product_name' => $prodName,
                        'quantity' => $p->quantity, 'unit_price' => $p->unit_price,
                    ]); // core fp (no Entry By) — matches the file row's core fingerprint
                    $dbRowFps[$fp] = true;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('IMPORT_SALES_DUP_SEED_FAILED', ['msg' => $e->getMessage()]);
        }

        // ── Group rows by invoice number so multiple line-items sharing the same invoice
        //    number end up as ONE customer_invoice with multiple products, not N invoices.
        //    Falls back to (customer + date) composite key when invoice_no is missing.
        $invoiceGroups = [];
        foreach ($rows as $i => $row) {
            $parsed = $this->parseRow($row, 'sales', $i + 1);
            $data = $parsed['data'];

            // Silently drop summary/total footer rows — they reach neither preview nor DB.
            if (in_array('summary_row', $data['error_types'] ?? [], true)) {
                continue;
            }

            if ($data['status'] !== 'valid') {
                $skipped++;
                $errors = array_merge($errors, $parsed['errors']);
                continue;
            }

            // Duplicate check — a row is a duplicate if it matches EITHER:
            //   • an existing DB record (5 core fields), OR
            //   • an earlier row in this same file (6 fields = core + Entry By).
            $coreFp = $this->_buildExactRowFingerprint($data);
            $fileFp = $this->_buildExactRowFingerprint($data, true);
            if (isset($dbRowFps[$coreFp]) || isset($seenFileFps[$fileFp])) {
                $skipped++;
                $duplicates++;
                continue;
            }
            $seenFileFps[$fileFp] = true;

            // Normalize invoice_no: cast to string, trim. Handles Excel int vs string + stray whitespace.
            $rawInv = $data['invoice_no'] ?? '';
            $invNoNormalized = trim((string)$rawInv);

            // Treat "0" (and numeric variations like "0.0", "00") as MISSING — these mean the user
            // wants a new auto-generated invoice ID rather than a literal invoice number of zero.
            // A legitimate invoice number is never 0; Excel often stores blanks as 0.
            $isZeroInvNo = ($invNoNormalized !== '' && is_numeric($invNoNormalized) && (float)$invNoNormalized == 0);
            $hasRealInvNo = ($invNoNormalized !== '' && !$isZeroInvNo);

            if ($hasRealInvNo) {
                // Real invoice number → use it directly so same number → same group
                $invKey = 'inv:' . $invNoNormalized;
            } else {
                // Missing or "0" → new invoice. Group by (customer + date) so a customer's same-day
                // rows still merge into one invoice, but each customer+date combo creates a fresh one.
                $cust = strtolower(trim((string)($data['entity_name'] ?? '')));
                $dateKey = trim((string)($data['date'] ?? ''));
                $invKey = 'auto:' . $cust . '|' . $dateKey;
                // Safety net: if BOTH customer and date are empty, fall back to per-row so we don't
                // accidentally merge unrelated rows together.
                if ($cust === '' && $dateKey === '') {
                    $invKey = 'auto:row_' . $i;
                }
            }

            // Remember the original invoice number for the FIRST row of each group so we can store
            // it as other_invoice_id. Blank out "0" so the DB doesn't treat it as a real reference.
            $data['_original_invoice_no'] = $hasRealInvNo ? $invNoNormalized : '';
            $invoiceGroups[$invKey][] = $data;
        }

        $getinvoiceinformation = getinvoiceinformation();
        $getporterage = $getinvoiceinformation['porterage'];
        $getvat = $getinvoiceinformation['vat'];

        foreach ($invoiceGroups as $invKey => $products) {
            try {
                $first = $products[0];

                // Find or create customer
                $customerName = $first['entity_name'] ?: 'Cash Customer';
                $customer = Customer::where('name', $customerName)->first();
                if (!$customer) {
                    $customer = new Customer();
                    $customer->name = $customerName;
                    $customer->is_active = 1;
                    $customer->save();
                    $customer->customer_id = 'C' . (100 + $customer->id);
                    $customer->update();
                }

                // Parse date — use shared helper that always returns a Carbon or null.
                $invoiceDate = $this->buildCarbonFromParsedDate($first['date'] ?? null);

                // Effective date for all child rows
                $effectiveDate = $invoiceDate ? $invoiceDate->copy()->setTime(12, 0, 0) : Carbon::now();
                $stampStr = $effectiveDate->format('Y-m-d H:i:s');

                if ($debugLogCount < 10) {
                    \Log::info('SALES_IMPORT_DATE', [
                        'invKey' => $invKey,
                        'first_date_in' => $first['date'] ?? 'NULL',
                        'invoiceDate_resolved' => $invoiceDate ? $invoiceDate->format('Y-m-d') : 'NULL',
                        'stampStr' => $stampStr,
                    ]);
                    $debugLogCount++;
                }

                // Preserve the original invoice number from the file (e.g. "153385") so reports
                // can display it instead of the auto-generated DB id. Stored on `other_invoice_id`
                // which existing UI already prefers over the internal id when present.
                $origInvNo = $first['_original_invoice_no'] ?? '';

                // NOTE: Duplicate detection now happens ROW-BY-ROW at the top of this method
                // (the $seenRowFps full-row exact-match guard) — using the SAME fingerprint the
                // preview uses. The old invoice-level 3-tier check was removed because its
                // date-agnostic Tier 3 flagged groups with identical products but DIFFERENT dates
                // as duplicates, causing the import count to drift below the preview's "Ready" count.

                // ── Resolve "Entry By" / salesman name to a user ID ──
                // The Excel "Entry By" column is a STAFF MEMBER NAME (not a supplier — that's the
                // legacy confusion this fix corrects). If Excel provides a value, look up the user
                // by name (or email) and store their users.id in customer_invoices.salesman_id.
                // Fallback to the currently logged-in user when the column is missing or the name
                // doesn't match any existing user — so an invoice is never left with a NULL salesman.
                $salesmanIdToSet = Auth::user()->id; // default fallback
                $salesmanRaw = trim((string)($first['salesman_name'] ?? ''));
                if ($salesmanRaw !== '') {
                    // users table has first_name + last_name + email — try all reasonable matches.
                    $matchedUser = \App\Models\User::where(function($q) use ($salesmanRaw) {
                            $q->where('email', $salesmanRaw)
                              ->orWhere('first_name', $salesmanRaw)
                              ->orWhere('last_name', $salesmanRaw)
                              ->orWhereRaw("CONCAT(first_name,' ',last_name) = ?", [$salesmanRaw])
                              ->orWhereRaw("CONCAT(last_name,' ',first_name) = ?", [$salesmanRaw]);
                        })
                        ->first();
                    if ($matchedUser) {
                        $salesmanIdToSet = $matchedUser->id;
                    }
                    // No match → keep the logged-in user as fallback; we deliberately do NOT auto-create
                    // users from import data (security concern: spurious accounts).
                }

                // Create invoice
                $invoice = new CustomerInvoice();
                $invoice->customer_id = $customer->id;
                $invoice->salesman_id = $salesmanIdToSet;
                $invoice->status = 1;
                if (!empty($origInvNo) && \Schema::hasColumn('customer_invoices', 'other_invoice_id')) {
                    $invoice->other_invoice_id = $origInvNo;
                }
                $invoice->created_at = $stampStr;
                $invoice->updated_at = $stampStr;
                $invoice->save();
                // Force the backdated timestamp (Eloquent auto-overrides created_at on save)
                \DB::table('customer_invoices')->where('id', $invoice->id)
                    ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                \App\Services\CustomerPayments::initiateInvoice($invoice->id, $customer->id);

                $subTotal = 0;
                $totalPaidForInvoice = 0;

                foreach ($products as $prod) {
                    // Find or create product
                    $product = Product::where('name', $prod['product_name'])->first();
                    if (!$product) {
                        $product = new Product();
                        $product->name = $prod['product_name'];
                        $product->is_active = 1;
                        $product->user_id = Auth::user()->id;
                        $product->save();
                    }

                    // Find or create supplier from "Entry By" column
                    $supplierId = 0;
                    $supplierInvoiceId = 0;
                    $supplierInvoiceProductId = 0;
                    $supplierName = $prod['entry_by'] ?? null;

                    if (!empty($supplierName)) {
                        $supplier = Supplier::where('name', $supplierName)->first();
                        if (!$supplier) {
                            $supplier = new Supplier();
                            $supplier->name = $supplierName;
                            $supplier->is_active = 1;
                            $supplier->save();
                        }
                        $supplierId = $supplier->id;

                        // Auto-create supplier invoice for this date + supplier
                        $sipInvoice = SupplierInvoice::where('supplier_id', $supplier->id)
                            ->whereDate('created_at', $invoiceDate ? $invoiceDate->format('Y-m-d') : date('Y-m-d'))
                            ->first();

                        if (!$sipInvoice) {
                            $sipInvoice = new SupplierInvoice();
                            $sipInvoice->salesman_id = Auth::user()->id;
                            $sipInvoice->supplier_id = $supplier->id;
                            $sipInvoice->other_invoice_id = '';
                            $sipInvoice->created_at = $stampStr;
                            $sipInvoice->updated_at = $stampStr;
                            $sipInvoice->save();
                            \DB::table('supplier_invoices')->where('id', $sipInvoice->id)
                                ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                            SupplierPayment::create([
                                'supplier_id' => $supplier->id,
                                'supplier_invoice_id' => $sipInvoice->id,
                                'amount' => 0,
                                'initiated' => 1,
                            ]);
                        }
                        $supplierInvoiceId = $sipInvoice->id;

                        // Auto-create supplier invoice product
                        $qty = floatval($prod['quantity']);
                        $price = floatval($prod['unit_price']);
                        $rowTotal = round($qty * $price, 2);

                        $sip = new SupplierInvoiceProduct();
                        $sip->supplier_invoice_id = $sipInvoice->id;
                        $sip->supplier_id = $supplier->id;
                        $sip->product_id = $product->id;
                        $sip->quantity = $qty;
                        $sip->unit_price = $price;
                        $sip->sub_total = $rowTotal;
                        $sip->remarks = '';
                        $sip->created_at = $stampStr;
                        $sip->updated_at = $stampStr;
                        $sip->save();
                        \DB::table('supplier_invoice_products')->where('id', $sip->id)
                            ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                        $sioRow = SupplierInvoiceOrder::create([
                            'supplier_invoice_product_id' => $sip->id,
                            'sub_total' => $sip->sub_total,
                        ]);
                        if ($sioRow && isset($sioRow->id)) {
                            \DB::table('supplier_invoice_orders')->where('id', $sioRow->id)
                                ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                        }

                        $supplierInvoiceProductId = $sip->id;

                        // Record stock for purchase (backdated to invoice date)
                        $stockProductsService = app(StockProducts::class);
                        $supStockRow = $stockProductsService->recordStock([
                            'supplier_id' => $supplier->id,
                            'product_id' => $product->id,
                            'stock' => $qty,
                            'type' => 'supplier',
                            'invoice_id' => $sipInvoice->id,
                            'event' => 'stock_added',
                            'price' => $price,
                            'ref_id' => $sip->id,
                        ]);
                        if ($supStockRow && isset($supStockRow->id)) {
                            \DB::table('stock_products')->where('id', $supStockRow->id)
                                ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                        }
                    }

                    $qty = floatval($prod['quantity']);
                    $price = floatval($prod['unit_price']);
                    $rowTotal = round($qty * $price, 2);
                    $subTotal += $rowTotal;
                    $sellPrice = is_numeric($prod['sell_price'] ?? null) ? floatval($prod['sell_price']) : null;
                    $returnQty = is_numeric($prod['return_qty'] ?? null) ? floatval($prod['return_qty']) : 0;
                    $paidAmt   = is_numeric($prod['paid_amount'] ?? null) ? floatval($prod['paid_amount']) : 0;
                    if ($paidAmt > 0) $totalPaidForInvoice += $paidAmt;

                    $cipData = [
                        'product_id' => $product->id,
                        'supplier_id' => $supplierId,
                        'quantity' => $qty,
                        'remarks' => '',
                        'supplier_invoice_product_id' => $supplierInvoiceProductId,
                        'unit_price' => $price,
                        'sub_total' => $rowTotal,
                        'customer_id' => $customer->id,
                        'supplier_invoice_id' => $supplierInvoiceId,
                        'customer_invoice_id' => $invoice->id,
                        'product_info' => json_encode($product),
                    ];
                    if ($sellPrice !== null) $cipData['sale_price'] = $sellPrice;
                    $cip = CustomerInvoiceProduct::create($cipData);
                    \DB::table('customer_invoice_products')->where('id', $cip->id)
                        ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                    // Record stock consumption for sale (backdated)
                    if ($supplierId > 0) {
                        $stockProductsService = app(StockProducts::class);
                        $consStockRow = $stockProductsService->recordStock([
                            'supplier_invoice_product_id' => $supplierInvoiceProductId,
                            'supplier_invoice_id' => $supplierInvoiceId,
                            'customer_id' => $customer->id,
                            'product_id' => $product->id,
                            'stock' => $qty,
                            'type' => 'customer',
                            'invoice_id' => $invoice->id,
                            'event' => 'stock_consumed',
                            'price' => $price,
                            'ref_id' => $cip->id,
                        ]);
                        if ($consStockRow && isset($consStockRow->id)) {
                            \DB::table('stock_products')->where('id', $consStockRow->id)
                                ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                        }

                        // Customer return
                        if ($returnQty > 0) {
                            if ($returnQty > $qty) $returnQty = $qty;
                            $retStock = new \App\Models\StockProduct();
                            $retStock->customer_id = $customer->id;
                            $retStock->product_id = $product->id;
                            $retStock->type = 'customer';
                            $retStock->stock = $returnQty;
                            $retStock->invoice_id = $invoice->id;
                            $retStock->ref_id = $cip->id;
                            $retStock->event = 'customer_return';
                            $retStock->price = $price;
                            $retStock->remarks = 'Imported return';
                            $retStock->created_at = $stampStr;
                            $retStock->updated_at = $stampStr;
                            $retStock->save();
                        }
                    }

                    $imported++;
                }

                // Create invoice order totals
                $total = $subTotal + $getporterage + $getvat;
                $cio = new CustomerInvoiceOrder();
                $cio->customer_invoice_id = $invoice->id;
                $cio->customer_id = $customer->id;
                $cio->sub_total = $subTotal;
                $cio->total = $total;
                $cio->vat = $getvat;
                $cio->porterage = $getporterage;
                $cio->created_at = $stampStr;
                $cio->updated_at = $stampStr;
                $cio->save();
                \DB::table('customer_invoice_orders')->where('id', $cio->id)
                    ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                // Customer payment
                if ($totalPaidForInvoice > 0) {
                    try {
                        \App\Services\CustomerPayments::partialAmountPayable(
                            $invoice->id, $totalPaidForInvoice, 4, 'Imported payment'
                        );
                        // Backdate the customer_payments rows just created for this invoice
                        \DB::table('customer_payments')
                            ->where('customer_invoice_id', $invoice->id)
                            ->where('amount', $totalPaidForInvoice)
                            ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                    } catch (\Exception $payEx) {
                        // Don't fail the whole import on payment issue
                    }
                }

            } catch (\Exception $e) {
                $skipped += count($products);
                $errors[] = "Invoice '$invKey': " . $e->getMessage() . ' (Line: ' . $e->getLine() . ', File: ' . basename($e->getFile()) . ')';
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'duplicates' => $duplicates, 'errors' => $errors];
    }

    /**
     * Import purchase data - groups rows by invoice_no to create supplier invoices.
     */
    private function importPurchase(array $rows)
    {
        $imported = 0;
        $skipped = 0;
        $duplicates = 0;
        $errors = [];

        // ── Row-level duplicate guard — IDENTICAL logic to the preview pass ──
        //   $dbRowFps     — 5-core-field fp of every existing DB record (no Entry By).
        //   $seenFileFps  — 6-field fp (core + Entry By) of file rows seen earlier in THIS run.
        $dbRowFps = [];
        $seenFileFps = [];
        // Seed from existing supplier invoices so re-imports of already-saved rows are caught.
        try {
            $hasOtherInv = \Schema::hasColumn('supplier_invoices', 'other_invoice_id');
            $cols = $hasOtherInv ? ['id','supplier_id','created_at','other_invoice_id'] : ['id','supplier_id','created_at'];
            $existing = SupplierInvoice::with(['products' => function($q){ $q->where('is_archive', 0); }])
                ->get($cols);
            $supNameById = Supplier::pluck('name','id')->all();
            // Cache product names so we don't hit the DB once per product line.
            $prodNameById = \App\Models\Product::pluck('name','id')->all();
            foreach ($existing as $inv) {
                $entName = $supNameById[$inv->supplier_id] ?? '';
                $invNo  = $hasOtherInv ? (string)($inv->other_invoice_id ?? '') : '';
                foreach (($inv->products ?? []) as $p) {
                    $prodName = $prodNameById[$p->product_id] ?? '';
                    $fp = $this->_buildExactRowFingerprint([
                        'invoice_no' => $invNo, 'date' => $inv->created_at,
                        'entity_name' => $entName, 'product_name' => $prodName,
                        'quantity' => $p->quantity, 'unit_price' => $p->unit_price,
                    ]); // core fp (no Entry By)
                    $dbRowFps[$fp] = true;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('IMPORT_PURCHASE_DUP_SEED_FAILED', ['msg' => $e->getMessage()]);
        }

        // ── Group rows by invoice number so multiple line-items sharing the same invoice
        //    number end up as ONE supplier_invoice with multiple products.
        //    Falls back to (supplier + date) composite key when invoice_no is missing.
        $invoiceGroups = [];
        foreach ($rows as $i => $row) {
            $parsed = $this->parseRow($row, 'purchase', $i + 2);
            $data = $parsed['data'];

            // Silently drop summary/total footer rows — they reach neither preview nor DB.
            if (in_array('summary_row', $data['error_types'] ?? [], true)) {
                continue;
            }

            if ($data['status'] !== 'valid') {
                $skipped++;
                $errors = array_merge($errors, $parsed['errors']);
                continue;
            }

            // Duplicate check — a row is a duplicate if it matches EITHER:
            //   • an existing DB record (5 core fields), OR
            //   • an earlier row in this same file (6 fields = core + Entry By).
            $coreFp = $this->_buildExactRowFingerprint($data);
            $fileFp = $this->_buildExactRowFingerprint($data, true);
            if (isset($dbRowFps[$coreFp]) || isset($seenFileFps[$fileFp])) {
                $skipped++;
                $duplicates++;
                continue;
            }
            $seenFileFps[$fileFp] = true;

            // Normalize invoice_no: cast to string, trim. Handles Excel int vs string + stray whitespace.
            $rawInv = $data['invoice_no'] ?? '';
            $invNoNormalized = trim((string)$rawInv);

            // Treat "0" (and numeric variations like "0.0", "00") as MISSING — these mean the user
            // wants a new auto-generated invoice ID rather than a literal invoice number of zero.
            $isZeroInvNo = ($invNoNormalized !== '' && is_numeric($invNoNormalized) && (float)$invNoNormalized == 0);
            $hasRealInvNo = ($invNoNormalized !== '' && !$isZeroInvNo);

            if ($hasRealInvNo) {
                $invKey = 'inv:' . $invNoNormalized;
            } else {
                $supp    = strtolower(trim((string)($data['entity_name'] ?? '')));
                $dateKey = trim((string)($data['date'] ?? ''));
                $invKey  = 'auto:' . $supp . '|' . $dateKey;
                if ($supp === '' && $dateKey === '') {
                    $invKey = 'auto:row_' . $i;
                }
            }

            // Blank out "0" so the DB doesn't treat it as a real reference number.
            $data['_original_invoice_no'] = $hasRealInvNo ? $invNoNormalized : '';
            $invoiceGroups[$invKey][] = $data;
        }

        foreach ($invoiceGroups as $invKey => $products) {
            try {
                $first = $products[0];

                // Find or create supplier
                $supplierName = $first['entity_name'];
                if (empty($supplierName)) {
                    $skipped += count($products);
                    $errors[] = "Invoice '$invKey': Supplier name is required.";
                    continue;
                }

                $supplier = Supplier::where('name', $supplierName)->first();
                if (!$supplier) {
                    $supplier = new Supplier();
                    $supplier->name = $supplierName;
                    $supplier->is_active = 1;
                    $supplier->save();
                }

                // Parse date — fall back to today only when row date is empty/unrecognised
                $invoiceDate = $this->buildCarbonFromParsedDate($first['date'] ?? null);
                $effectiveDate = $invoiceDate ? $invoiceDate->copy()->setTime(12, 0, 0) : Carbon::now();
                $stampStr = $effectiveDate->format('Y-m-d H:i:s');

                // Preserve the original invoice number from the file (e.g. "153385") so reports
                // can display it instead of the auto-generated DB id.
                $origInvNo = $first['_original_invoice_no'] ?? '';

                // NOTE: Duplicate detection now happens ROW-BY-ROW at the top of this method
                // (the $seenRowFps full-row exact-match guard) — using the SAME fingerprint the
                // preview uses. The old invoice-level 3-tier check was removed because it flagged
                // groups with identical products but DIFFERENT dates as duplicates (date-agnostic
                // Tier 3), causing the import count to drift below the preview's "Ready" count.

                // Create supplier invoice
                $invoice = new SupplierInvoice();
                $invoice->salesman_id = Auth::user()->id;
                $invoice->supplier_id = $supplier->id;
                $invoice->other_invoice_id = $origInvNo;
                $invoice->created_at = $stampStr;
                $invoice->updated_at = $stampStr;
                $invoice->save();
                // Force the backdated timestamp (Eloquent auto-overrides created_at on save)
                \DB::table('supplier_invoices')->where('id', $invoice->id)
                    ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                // Placeholder payment row (zero amount, initiated)
                SupplierPayment::create([
                    'supplier_id' => $supplier->id,
                    'supplier_invoice_id' => $invoice->id,
                    'amount' => 0,
                    'initiated' => 1,
                ]);

                $totalPaidForInvoice = 0;

                foreach ($products as $prod) {
                    // Find or create product
                    $product = Product::where('name', $prod['product_name'])->first();
                    if (!$product) {
                        $product = new Product();
                        $product->name = $prod['product_name'];
                        $product->is_active = 1;
                        $product->user_id = Auth::user()->id;
                        $product->save();
                    }

                    $qty = floatval($prod['quantity']);
                    $price = floatval($prod['unit_price']);
                    $rowTotal = round($qty * $price, 2);
                    $sellPrice = is_numeric($prod['sell_price'] ?? null) ? floatval($prod['sell_price']) : null;
                    $returnQty = is_numeric($prod['return_qty'] ?? null) ? floatval($prod['return_qty']) : 0;
                    $dumpQty   = is_numeric($prod['dump_qty'] ?? null) ? floatval($prod['dump_qty']) : 0;
                    $paidAmt   = is_numeric($prod['paid_amount'] ?? null) ? floatval($prod['paid_amount']) : 0;

                    $sip = new SupplierInvoiceProduct();
                    $sip->supplier_invoice_id = $invoice->id;
                    $sip->supplier_id = $supplier->id;
                    $sip->product_id = $product->id;
                    $sip->quantity = $qty;
                    $sip->unit_price = $price;
                    $sip->sub_total = $rowTotal;
                    $sip->remarks = '';
                    if ($sellPrice !== null) $sip->sale_price = $sellPrice;
                    $sip->created_at = $stampStr;
                    $sip->updated_at = $stampStr;
                    $sip->save();
                    \DB::table('supplier_invoice_products')->where('id', $sip->id)
                        ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                    $sioRow = SupplierInvoiceOrder::create([
                        'supplier_invoice_product_id' => $sip->id,
                        'sub_total' => $sip->sub_total,
                    ]);
                    if ($sioRow && isset($sioRow->id)) {
                        \DB::table('supplier_invoice_orders')->where('id', $sioRow->id)
                            ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                    }

                    // ─── Stock added ─────────────────────────────────
                    $stockProducts = app(StockProducts::class);
                    $stockRow = $stockProducts->recordStock([
                        'supplier_id' => $supplier->id,
                        'product_id' => $product->id,
                        'stock' => $qty,
                        'type' => 'supplier',
                        'invoice_id' => $invoice->id,
                        'event' => 'stock_added',
                        'price' => $price,
                        'ref_id' => $sip->id,
                    ]);
                    // Backdate stock row to invoice date so Stock Check / Closing show on correct day
                    if ($stockRow && isset($stockRow->id)) {
                        \DB::table('stock_products')->where('id', $stockRow->id)
                            ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                    }

                    // ─── Supplier return ─────────────────────────────
                    if ($returnQty > 0) {
                        if ($returnQty > $qty) $returnQty = $qty;
                        $retStock = new \App\Models\StockProduct();
                        $retStock->supplier_id = $supplier->id;
                        $retStock->product_id = $product->id;
                        $retStock->type = 'supplier';
                        $retStock->stock = $returnQty;
                        $retStock->invoice_id = $invoice->id;
                        $retStock->ref_id = $sip->id;
                        $retStock->event = 'supplier_return';
                        $retStock->price = $price;
                        $retStock->remarks = 'Imported return';
                        $retStock->created_at = $stampStr;
                        $retStock->updated_at = $stampStr;
                        $retStock->save();
                    }

                    // ─── Dump ────────────────────────────────────────
                    if ($dumpQty > 0) {
                        $remainingForDump = $qty - $returnQty;
                        if ($dumpQty > $remainingForDump) $dumpQty = max(0, $remainingForDump);
                        if ($dumpQty > 0) {
                            $dumpStock = new \App\Models\StockProduct();
                            $dumpStock->supplier_id = $supplier->id;
                            $dumpStock->product_id = $product->id;
                            $dumpStock->type = 'supplier';
                            $dumpStock->stock = $dumpQty;
                            $dumpStock->invoice_id = $invoice->id;
                            $dumpStock->ref_id = $sip->id;
                            $dumpStock->event = 'dump';
                            $dumpStock->price = $price;
                            $dumpStock->remarks = 'Imported dump';
                            $dumpStock->created_at = $stampStr;
                            $dumpStock->updated_at = $stampStr;
                            $dumpStock->save();
                        }
                    }

                    if ($paidAmt > 0) $totalPaidForInvoice += $paidAmt;

                    $imported++;
                }

                // ─── Payment (Cash by default) ───────────────────────
                if ($totalPaidForInvoice > 0) {
                    // Two-row pattern matching partialAmountPayable: parent (runtime) + child (paid)
                    $parentPay = SupplierPayment::create([
                        'supplier_invoice_id' => 0,
                        'supplier_id' => $supplier->id,
                        'amount' => $totalPaidForInvoice,
                        'is_runtime_payment' => 1,
                        'payment_id' => 4, // 4 = Card (Debit/Credit)
                        'note' => 'Imported payment',
                        'mode' => 'partial',
                    ]);
                    \DB::table('supplier_payments')->where('id', $parentPay->id)
                        ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);

                    $childPay = SupplierPayment::create([
                        'supplier_invoice_id' => $invoice->id,
                        'supplier_id' => $supplier->id,
                        'supplier_payment_id' => $parentPay->id,
                        'amount' => $totalPaidForInvoice,
                        'is_paid' => 1,
                        'payment_id' => 4, // 4 = Card (Debit/Credit)
                        'note' => 'Imported payment',
                        'mode' => 'partial',
                    ]);
                    \DB::table('supplier_payments')->where('id', $childPay->id)
                        ->update(['created_at' => $stampStr, 'updated_at' => $stampStr]);
                }

            } catch (\Exception $e) {
                $skipped += count($products);
                $errors[] = "Invoice '$invKey': " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'duplicates' => $duplicates, 'errors' => $errors];
    }
}

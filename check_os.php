<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ts_organizations', 'root', '');

$from_date = date('Y-m-d', strtotime('-1 month'));
echo "From date (os_date): $from_date\n";

// Total records
$total = $pdo->query("SELECT COUNT(*) FROM stock_products WHERE is_archived=0")->fetchColumn();
echo "Total active stock_products: $total\n";

// Records before from_date by created_at
$before_created = $pdo->query("SELECT COUNT(*) FROM stock_products WHERE is_archived=0 AND DATE(created_at) < '$from_date'")->fetchColumn();
echo "Records with created_at < from_date: $before_created\n";

// Records before from_date by updated_at
$before_updated = $pdo->query("SELECT COUNT(*) FROM stock_products WHERE is_archived=0 AND DATE(updated_at) < '$from_date'")->fetchColumn();
echo "Records with updated_at < from_date: $before_updated\n";

// Sample records
echo "\nSample last 5 records:\n";
$rows = $pdo->query("SELECT id, product_id, event, DATE(created_at) as created, DATE(updated_at) as updated FROM stock_products WHERE is_archived=0 ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) {
    echo "id={$r['id']} product_id={$r['product_id']} event={$r['event']} created={$r['created']} updated={$r['updated']}\n";
}

// OS calculation result
echo "\nOS calculation (created_at < from_date):\n";
$os = $pdo->query("SELECT product_id,
    SUM(CASE WHEN event IN ('stock_added','stock_updated') THEN stock ELSE 0 END) as total_in,
    SUM(CASE WHEN event IN ('stock_consumed') THEN stock ELSE 0 END) as total_out
    FROM stock_products WHERE is_archived=0 AND DATE(created_at) < '$from_date'
    GROUP BY product_id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach($os as $r) {
    $net = $r['total_in'] - $r['total_out'];
    echo "product_id={$r['product_id']} in={$r['total_in']} out={$r['total_out']} net=$net\n";
}

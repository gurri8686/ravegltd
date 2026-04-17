<?php
return [
	'general' => [
    	'dashboard' => ['route'=>'dashboard.view.index','title'=>'Dashboard','icon' => 'fa fa-university'],
	],
	'Sales & Purchases' => [
		'sales' => ['route'=>'daily_report.daily_book_sales.view.index','title'=>'Sales','icon' => 'fa fa-bar-chart','active_prefix' => ['daily_report.daily_book_sales','data_entry.sales_entry']],
		'purchases' => ['route'=>'daily_report.daily_book_purchase.view.index','title'=>'Purchases','icon' => 'fa fa-book','active_prefix' => ['daily_report.daily_book_purchase','data_entry.purchase_entry']],
	],
	'products & stocks & P/L' => [
		'products' => [
			'route' => 'management.products.view.index',
			'title' => 'Products', 'icon' => 'fa fa-shopping-bag',
			'active_prefix' => ['management.products','product_history','product_profit_loss'],
		],
		'stock' => [
			'route' => 'stock_closing.view.index',
			'title' => 'Stock Manager','icon' => 'fa fa-cubes',
			'active_prefix' => ['stock_closing','stock_check'],
		]
	],
	'customers and suppliers' => [
		'customers' => ['route'=>'management.customers.view.index','title'=>'Customers','icon' => 'fa fa-user-secret'],
		'suppliers' => ['route'=>'management.suppliers.view.index','title'=>'Suppliers','icon' => 'feather icon-users'],
		'settings' => [
			'title' => 'Settings','icon' => 'fa fa-cog',
			'route' => 'management.settings.index',
			'active_prefix' => 'management.settings',
		],
	],
]
?>
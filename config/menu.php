<?php

return [
    /**
     * Module.
     */
    'dashboard' => ['view'],
    'management' => [
        /**
         * Sub Module
         */
        'roles' => [
            // 'modules'        => ['view','create', 'edit', 'delete'],
            // 'groups'         => ['view','create', 'edit', 'delete'],
            'role'           => ['view','create', 'edit', 'delete'],
            'permission' => ['view','create', 'edit', 'delete'],
        ],
        'users'              => [
			'view','create', 'edit', 'delete',
		],
        'customers'          => [ 
			//'nolink' => true,
			'view','create', 'edit', 'delete',
			//'on_account_payment' => ['view' => 'index','create' => [], 'delete' => [], 'alias' => 'On Account'],
		],
		//'on_account_payment' => ['view','create', 'edit', 'delete'],
        'suppliers'          => [
			'view','create', 'edit', 'delete',
			//'on_account_payment' => ['view','create', 'edit', 'delete', 'alias' => 'On Account'],
		],
        'products'           => ['view','create', 'edit', 'delete'],
        // 'passbooks'          => ['view','create', 'edit', 'delete'],

    ],
	'on_account_payment' => [
		'nolink' => true,
		'customers' => [
			'direct_link' => 'management/customers/on_account_payment/view','_target' => 'blank'
		],
		'suppliers' => [
			'direct_link' => 'management/suppliers/on_account_payment/view','_target' => 'blank'
		],
	],
    'data_entry' => [
        'sales_entry'         => [
            'create','view', 'edit', 'delete',
            /*'sales_customer' => ['ajaxCustomer'],
            'sales_date' => ['ajaxDate'],
			'invoice_email' => ['send'],
			'invoice_payment'      => [
				'create' => 'create',
				'edit'=> 'edit',
				'delete' => 'delete',
				'view'=>'list'
			],*/
        ],
        'purchase_entry'      => ['view','create', 'edit', 'delete'],
        //'stock_closing'       => ['alias' => 'Closing Stock','view','create', 'edit', 'delete'],
    ],
	'stock_returns' => [
		'nolink' => true,
		'customer_return' => ['view'],
		'supplier_return' => ['view'],
		'dump_return' => ['view']
	],
	'stock_manager' => [
		'nolink' => true,
		'stock_closing' => ['view'],
		'stock_check' => ['view'],
	],
	//'stock_closing'       => ['alias' => 'Closing Stock','view','create', 'edit', 'delete'],
	//'stock_check'       => ['alias' => 'Stock Check','view','create', 'edit', 'delete'],
    'historical_reports' => [
		'nolink' => true,
        'customer_history' => ['view'],
		'supplier_history' => ['view'],
		'product_history' => ['view'],
		//'product_profit_loss' => ['view'],
    ],
	'payment_history' => ['nolink' => true,
		'supplier_payment_history' => ['view','alias' => 'Suppliers'],
    ],
	'payments' => [ 'alias' => 'Make Payments',
		'customer_payment' => 	['view','create', 'edit', 'delete','alias' => 'Customers'],
		'supplier_payment' => 	['view','create', 'edit', 'delete', 'alias' => 'Suppliers'],
	],
    'daily_report' => [
        'daily_book_sales'         => ['view','create', 'edit', 'delete', 'alias' => 'Book Sales'],
        'daily_book_purchase'      => ['view','create', 'edit', 'delete', 'alias' => 'Book Purchase'],
    ],
	'statements' => [
		'sales' => [
			'direct_link' => 'data_entry/sales_entry/statements/view','_target' => 'blank'
		],
		'purchases' => [
			'direct_link' => 'data_entry/purchase_entry/statements/view','_target' => 'blank'
		]
	],
    /*'settings' => [
        'payment_methods'         => ['view','create', 'edit', 'delete']
    ],*/
    'activity_logs' => [
        'users'         => ['view']
    ],
];
?>

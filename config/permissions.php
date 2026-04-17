<?php

return [
    /**
     * Module.
     */
    'dashboard' => [],
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
			'view','create', 'edit', 'delete',
			//'on_account_payment' => ['view','create', 'edit', 'delete'],
		],
		//'on_account_payment' => ['view','create', 'edit', 'delete'],
        'suppliers'          => [
			'view','create', 'edit', 'delete',
			//'on_account_payment' => ['view','create', 'edit', 'delete'],
		],
        'products'           => ['view','create', 'edit', 'delete'],
        // 'passbooks'          => ['view','create', 'edit', 'delete'],

    ],
    'data_entry' => [
        'sales_entry'         => [
            'create','view', 'edit', 'delete',
            'sales_customer' => ['ajaxCustomer'],
            'sales_date' => ['ajaxDate'],
			'invoice_email' => ['send'],
			'invoice_payment'      => [
				'create' => 'create',
				'edit'=> 'edit',
				'delete' => 'delete',
				'view'=>'list'
			],
        ],
        'purchase_entry'      => ['view','create', 'edit', 'delete'],
        'closing_stock'       => ['view','create', 'edit', 'delete'],
    ],
    'historical_reports' => [
        'customer_history'         => ['view','create', 'edit', 'delete'],
    ],
	'payments' => [
		'customer_payment' => 	['view','create', 'edit', 'delete'],
	],
    'daily_report' => [
        'daily_book_sales'         => ['view','create', 'edit', 'delete'],
        'daily_book_purchase'      => ['view','create', 'edit', 'delete'],
    ],
    /*'settings' => [
        'payment_methods'         => ['view','create', 'edit', 'delete']
    ],
    'activity_logs' => [
        'users'         => ['view']
    ],*/
];
?>

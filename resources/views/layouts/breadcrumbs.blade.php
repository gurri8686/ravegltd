<?php 
$route = \Route::currentRouteName();

//echo $route;

$_details = [

    'dashboard.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Dashboard']
        ],
        'title' => 'Dashboard',
        'html' => (function () { return ""; })(),
    ],

    'management.roles.role.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Roles']
        ],
        'title' => 'Roles',
        'html' => (function () {
			if(hasPermissionMenu('management.roles.role.create.create')){
				return '<button class="btn btn-primary"><a class="text-white" href="'.route('management.roles.role.create.create').'"><i class="fa fa-plus"></i> Create New</a></button>';
			}
			return "";
		})(),
    ],

	'management.roles.role.create.create'=> [
        'breadcrumbs' => [
            ['link' => route('management.roles.role.view.index'), 'title' => 'Roles'],
			['link' => '', 'title' => 'Add new Role']
        ],
        'title' => 'Add Roles',
        'html' => (function () {
			return "";
		})(),
    ],

	'management.roles.role.edit.edit'=> [
        'breadcrumbs' => [
            ['link' => route('management.roles.role.view.index'), 'title' => 'Roles'],
			['link' => '', 'title' => 'Update Role']
        ],
        'title' => 'Update Roles',
        'html' => (function () {
			return "";
		})(),
    ],

    'management.roles.permission.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Permissions']
        ],
        'title' => 'Permissions',
        'html' => (function () { return ""; })(),
    ],

	'management.roles.permission.edit.edit' => [
        'breadcrumbs' => [
            ['link' => route('management.roles.permission.view.index'), 'title' => 'Permissions'],
			['link' => '', 'title' => 'Set Permissions']
        ],
        'title' => 'Permissions',
        'html' => (function () { return ""; })(),
    ],

    'management.users.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Users']
        ],
        'title' => 'Users',
        'html' => (function () { 
			if(hasPermissionMenu('management.users.create.create')){
				return '<a class="btn btn-primary glow" href="'.route('management.users.create.create').'"><i class="fa fa-plus"></i> Create New User</a>';
			}
			return ""; })(),
    ],

	'management.users.create.create' => [
        'breadcrumbs' => [
            ['link' => route('management.users.view.index'), 'title' => 'Users'],
			['link' => '', 'title' => 'Add User'],
        ],
        'title' => 'Add User',
        'html' => (function () { 
			return ""; })(),
    ],

	'management.users.edit.edit' => [
        'breadcrumbs' => [
            ['link' => route('management.users.view.index'), 'title' => 'Users'],
			['link' => '', 'title' => 'Update User'],
        ],
        'title' => 'Users',
        'html' => (function () { 
			return ""; })(),
    ],

    'management.customers.view.index' => [
		'breadcrumbs' => [
			['link' => 'Link', 'title' => 'All Customers']
		],
		'title' => 'Customer List',
		'html' => (function () {
					if(hasPermissionMenu('management.customers.create.create')){
						return '<a class="btn btn-primary glow" href="'.route('management.customers.create.create').'">
						<i class="fa fa-plus"></i> Create New</a>';
					}
					return "";
				})(),
	],

	'management.customers.create.create' => [
		'breadcrumbs' => [
			['link' => route('management.customers.view.index'), 'title' => 'Customers'],
			['link' => '', 'title' => 'Add Customer']
		],
		'title' => 'Add Customer',
		'html' => (function () { return ""; })(),
	],

	'management.customers.edit.edit' => [
		'breadcrumbs' => [
			['link' => route('management.customers.view.index'), 'title' => 'Customers'],
			['link' => '', 'title' => 'Update Customer']
		],
		'title' => 'Update Customer #'.request()->route('customer'),
		'html' => (function () { return ""; })(),
	],

    'management.suppliers.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Suppliers']
        ],
        'title' => 'All Suppliers',
        'html' => (function () { 
			if(hasPermissionMenu('management.suppliers.create.create')){
				return '<a class="btn btn-primary glow" href="'.route('management.suppliers.create.create').'">
				<i class="fa fa-plus"></i> Create New</a>';
			}
			return ""; 
		})(),
    ],

	'management.suppliers.create.create' => [
        'breadcrumbs' => [
            ['link' => route('management.suppliers.view.index'), 'title' => 'Suppliers'],
			['link' => '', 'title' => 'Add Supplier']
        ],
        'title' => 'Add Supplier',
        'html' => (function () { 
			if(hasPermissionMenu('management.suppliers.create.create')){
				return '<a class="btn btn-primary glow" href="'.route('management.suppliers.create.create').'">
				<i class="fa fa-plus"></i> Create New</a>';
			}
			return ""; 
		})(),
    ],

	'management.suppliers.edit.edit' => [
        'breadcrumbs' => [
            ['link' => route('management.suppliers.view.index'), 'title' => 'Suppliers'],
			['link' => '', 'title' => 'Update Supplier']
        ],
        'title' => 'Upadte Supplier #'.request()->route('supplier'),
        'html' => (function () { 
			if(hasPermissionMenu('management.suppliers.create.create')){
				return '<a class="btn btn-primary glow" href="'.route('management.suppliers.create.create').'">
				<i class="fa fa-plus"></i> Create New</a>';
			}
			return ""; 
		})(),
    ],

    'management.products.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Products']
        ],
        'title' => 'All Products',
        'html' => (function () { 
			if(hasPermissionMenu('management.users.create.create')){
				return '<a class="btn btn-primary glow" href="'.route('management.products.create.create').'"><i class="fa fa-plus"></i> Create Product</a>';
			}
			return ""; })(),
    ],

	'management.products.create.create' => [
        'breadcrumbs' => [
            ['link' => route('management.products.view.index'), 'title' => 'Products'],
			['link' => '', 'title' => 'Add New']
        ],
        'title' => 'Add New',
        'html' => (function () { 
			return ""; })(),
    ],

	'management.users.edit.edit' => [
        'breadcrumbs' => [
            ['link' => route('management.products.view.index'), 'title' => 'Products'],
			['link' => '', 'title' => 'Update Product']
        ],
        'title' => 'Update Product',
        'html' => (function () { 
			return ""; })(),
    ],

    'management.customers.on_account_payment.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Customer On Account Payments']
        ],
        'title' => 'Customer On Account Payments',
        'html' => (function () { return ""; })(),
    ],

    'management.suppliers.on_account_payment.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Supplier On Account Payments']
        ],
        'title' => 'Supplier On Account Payments',
        'html' => (function () { return ""; })(),
    ],

    'data_entry.sales_entry.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Sales Entry']
        ],
        'title' => 'Sales Entry',
        'html' => (function () { return ""; })(),
    ],

    'data_entry.purchase_entry.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Purchase Entry']
        ],
        'title' => 'Purchase Entry',
        'html' => (function () { return ""; })(),
    ],

    'customer_return.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Customer Returns']
        ],
        'title' => 'Customer Returns',
        'html' => (function () { return ""; })(),
    ],

    'supplier_return.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Supplier Returns']
        ],
        'title' => 'Supplier Returns',
        'html' => (function () { return ""; })(),
    ],

    'dump_return.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Dumps']
        ],
        'title' => 'Product Dumps',
        'html' => (function () { return ""; })(),
    ],

    'stock_closing.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Stock Closing']
        ],
        'title' => 'Stock Closing',
        'html' => (function () { return ""; })(),
    ],

    'stock_check.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Stock Check']
        ],
        'title' => 'Stock Check',
        'html' => (function () { return ""; })(),
    ],

    'customer_history.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Customer History']
        ],
        'title' => 'Customer History',
        'html' => (function () { return ""; })(),
    ],

    'supplier_history.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Supplier History']
        ],
        'title' => 'Supplier History',
        'html' => (function () { return ""; })(),
    ],

    'product_history.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Product History']
        ],
        'title' => 'Product History',
        'html' => (function () { return ""; })(),
    ],

    'product_profit_loss.view.index' => [
        'breadcrumbs' => [
            ['link' => route('management.products.view.index'), 'title' => 'Products'],
			['link' => '', 'title' => 'Product Profit & Loss']
        ],
        'title' => 'Product Profit / Loss',
        'html' => (function () { return ""; })(),
    ],

    'supplier_payment_history.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Supplier Payment History']
        ],
        'title' => 'Supplier Payment History',
        'html' => (function () { return ""; })(),
    ],

    'payments.customer_payment.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Customer Payments']
        ],
        'title' => 'Customer Payments',
        'html' => (function () { return ""; })(),
    ],

    'payments.supplier_payment.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'Supplier Payments']
        ],
        'title' => 'Supplier Payments',
        'html' => (function () { return ""; })(),
    ],

    'daily_report.daily_book_sales.view.index' => [
        'breadcrumbs' => [],
        'title' => '',
        'html' => (function () { return ""; })(),
    ],

    'daily_report.daily_book_purchase.view.index' => [
        'breadcrumbs' => [],
        'title' => '',
        'html' => (function () { return ""; })(),
    ],

	'data_entry.sales_entry.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'New Sales Entry']
        ],
        'title' => 'New Sales Entry',
        'html' => (function () { return ""; })(),
    ],

	'data_entry.sales_entry.invoice.index' => [
        'breadcrumbs' => [],
        'title' => '',
        'html' => (function () { return ""; })(),
    ],

	'data_entry.purchase_entry.view.index' => [
        'breadcrumbs' => [
            ['link' => '', 'title' => 'New Purchase Entry']
        ],
        'title' => 'New Purchase Entry',
        'html' => (function () { return ""; })(),
    ],

	'data_entry.purchase_entry.invoice.index' => [
        'breadcrumbs' => [],
        'title' => '',
        'html' => (function () { return ""; })(),
    ],

];
?>
<div class="content-header-left col-md-8 col-12 mb-2">
	<div class="row breadcrumbs-top">
	<div class="breadcrumb-wrapper col-12">
		@if($route !== 'data_entry.sales_entry.invoice.index' && $route !== 'data_entry.sales_entry.view.index' && $route !== 'data_entry.purchase_entry.invoice.index' && $route !== 'data_entry.purchase_entry.view.index' && $route !== 'product_history.view.index' && $route !== 'management.products.view.index' && $route !== 'product_profit_loss.view.index' && $route !== 'daily_report.daily_book_sales.view.index' && $route !== 'daily_report.daily_book_purchase.view.index' && $route !== 'management.roles.role.view.index' && $route !== 'management.roles.role.edit.edit' && $route !== 'management.roles.permission.view.index' && $route !== 'management.roles.permission.edit.edit' && $route !== 'management.users.view.index' && $route !== 'stock_closing.view.index' && $route !== 'stock_check.view.index' && $route !== 'management.users.edit.edit' && $route !== 'management.products.edit.edit' && $route !== 'management.products.create.create' && $route !== 'management.customers.view.index' && $route !== 'management.customers.create.create' && $route !== 'management.customers.edit.edit' && $route !== 'management.customers.on_account_payment.view.index' && $route !== 'customer_return.view.index' && $route !== 'customer_history.view.index' && $route !== 'payments.customer_payment.view.index' && $route !== 'dashboard.view.index' && $route !== 'settings.index')
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{route('dashboard.view.index')}}"><i class="fa fa-home"></i> Home</a></li>
			@if(isset($_details[$route]))
				@foreach($_details[$route]['breadcrumbs'] as $b)
					@if(!empty($b['link']))
						<li class="breadcrumb-item"><a href="{{$b['link']}}">{{$b['title']}}</a></li>
					@else
						<li class="breadcrumb-item">{{$b['title']}}</li>
					@endif
				@endforeach
			@endif
		</ol>
	@endif
		</div>
	</div>
	@if(isset($_details[$route]) && $route !== 'data_entry.sales_entry.view.index' && $route !== 'data_entry.purchase_entry.view.index')
		<h3 class="content-header-title mb-0">{{$_details[$route]['title']}}</h3>
	@endif
</div>

@if(isset($_details[$route]['html']) && !empty($_details[$route]['html']) )
<div class="content-header-left col-md-4 col-12 mb-2 text-right">
	@if(isset($_details[$route]['html']) && !empty($_details[$route]['html']) )
		{!! $_details[$route]['html'] !!}
	@endif
</div>
@endif
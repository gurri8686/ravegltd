<?php
use App\Models\RoleModel;
use App\Models\PermissionRoleModel;
use App\Models\ModelHasRole;
use Illuminate\Support\Facades\Route;

if(!function_exists('p'))
{
    function p($data)
    {
       echo "<pre>";
       print_r($data);
       echo "<pre>";
    }
}

if(!function_exists('nameFormat'))
{
    function nameFormat($data)
    {
        $data =str_replace('_',' ',$data);
        $data =ucwords($data);
        return $data;
    }
}
if(!function_exists('module'))
{
    function module($array)
    {
        $moduleName = array();
        foreach($array as $key => $value)
        {
            array_push($moduleName,$key);
        }
        return $moduleName;
    }
}
if(!function_exists('hasPermissionMenu'))
{
    function hasPermissionMenu($routeName)
    {
        global $arrayMenuPermission;
        global $roleName;
        $user = \Auth::user();
        $hasPermissionMenu=0;
        if($roleName=='admin' || $roleName=='Admin')
        {
            $hasPermissionMenu=1;
        }else
        {
            $result  = $user->can($routeName);
            if($result)
            {
                $hasPermissionMenu=1;
            }
            else
            {
                $match = '/'.str_replace('.*','',$routeName).'/';
                if(preg_grep($match, $arrayMenuPermission))
                {
                    $hasPermissionMenu=1;
                }
            }
        }
        return $hasPermissionMenu;
    }
}
if(!function_exists('hrefRoute'))
{
    function hrefRoute($data)
    {	
        $hrefRoute='';
        if(Route::has($data))
        {
            $hrefRoute=route($data);
        }
        else
        {
            $hrefRoute='#';
        }
        return $hrefRoute;
    }
}
if(!function_exists('isActive'))
{
    function isActive($data)
    {
        $active='';
        if(str_contains(\Request::route()->getName(), $data))
        {
            $active = 'active';
        }else{
            $active='';
        }

        return $active;
    }
}
if(!function_exists('isChecked'))
{
    function isChecked($valueCheckbox,$permissionsExist)
    {
        $checked='';
        $match = '/'.str_replace('.*','',$valueCheckbox).'/';
        if(preg_grep($match, $permissionsExist))
        {
            $checked='checked';
        }
        return $checked;
    }
}

if(!function_exists('formatTwoDecimal')){
	function formatTwoDecimal($value)
	{
		return number_format((float)$value, 2, '.', '');
	}
}

if(!function_exists('formatTwoDecimalCurrenty')){
	function formatTwoDecimalCurrenty($value)
	{
		return env('CURRENCY_SYMBOL').' '.number_format((float)$value, 2, '.', '');
	}
}

if(!function_exists('uk_ts')){
	/**
	 * Format a STORED UTC date/time for display in UK time (Europe/London —
	 * auto GMT in winter / BST in summer).
	 *
	 * Use this ONLY for true timestamps (created_at / updated_at and other
	 * recorded date-times). Do NOT use it for user-entered date filters or for
	 * pure business calendar dates (invoice date, payment date, …) — those carry
	 * no timezone and must be displayed as stored.
	 *
	 * @param  mixed   $value   a UTC datetime (string / Carbon / null)
	 * @param  string  $format  any PHP date() format
	 */
	function uk_ts($value, $format = 'd M Y, H:i')
	{
		if (empty($value)) {
			return '';
		}
		try {
			return \Illuminate\Support\Carbon::parse($value, 'UTC')
				->setTimezone('Europe/London')
				->format($format);
		} catch (\Throwable $e) {
			return is_string($value) ? $value : '';
		}
	}
}

if (!function_exists('route_exists_like')) {
    function route_exists_like($pattern)
    {
        $pattern = str_replace('.', '\.', $pattern); // escape dots
        $pattern = str_replace('*', '.*', $pattern); // convert * to regex wildcard

        foreach (Route::getRoutes() as $route) {
            if (preg_match('#^' . $pattern . '$#', $route->getName())) {
                return true;
            }
        }

        return false;
    }
}

if(!function_exists('routeExist'))
{
    function routeExist($data)
    {
	
		return route_exists_like($data);
		
		$hrefRoute=$status=$Route='';
        $Route = trim($data,'.*');
        $hrefRoute = substr(strrchr($Route, '.'), 1);
        if($hrefRoute=='view')
        {
            $hrefRoute = $Route.'.index';
            if(\Route::has($hrefRoute))
            {
                $status=1;
            }
            else
            {
                $hrefRoute = $Route.'.show';
                if(\Route::has($hrefRoute))
                {
                    $status=1;
                }
            }
        }elseif($hrefRoute=='create'){
            $hrefRoute = $Route.'.create';
            if(\Route::has($hrefRoute))
            {
                $status=1;
            }
            else
            {
                $hrefRoute = $Route.'.store';
                if(\Route::has($hrefRoute))
                {
                    $status=1;
                }
            }
        }elseif($hrefRoute=='edit'){
            $hrefRoute = $Route.'.edit';
            if(\Route::has($hrefRoute))
            {
                $status=1;
            }
            else
            {
                $hrefRoute = $Route.'.update';
                if(\Route::has($hrefRoute))
                {
                    $status=1;
                }
            }
        }elseif($hrefRoute=='delete'){
            $hrefRoute = $Route.'.destroy';
            if(\Route::has($hrefRoute))
            {
                $status=1;
            }
        }

        return $status;
    }
    if(!function_exists('getinvoiceinformation'))
    {
        function getinvoiceinformation()
        {
            $invoiceinformation=[
                'porterage' => 0,
                'vat' => 0,
             ];

            return $invoiceinformation;
        }
    }
    if(!function_exists('checkadmin'))
    {
        function checkadmin($id)
        {
          $hasPermissionMenu=0;
          $roleUser = ModelHasRole::where(['model_id'=>$id])->first();
          if($roleUser && $roleUser->role_id){
              $role = RoleModel::where(['id'=>$roleUser->role_id])->first();

                $hasPermissionMenu=0;
              if($role->name=='admin' || $role->name=='Admin')
              {
                  $hasPermissionMenu=1;
              }else
              {
                $hasPermissionMenu=0;
              }

          }

          return $hasPermissionMenu;
        }
    }
	
	if (!function_exists('renderSideMenu2')) {
		/**
		 * Recursive sidebar renderer with alias, nolink, direct_link, _target, and nested logic
		 */
		function renderSideMenu2($menu, $parentPath = '', $isRoot = true){
			if (!is_array($menu) || empty($menu)) {
				return '';
			}

			$html = '';

			if ($isRoot) {
				$html .= '<ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">';
				$html .= '<li class="navigation-header"><span>General</span><i class="feather icon-minus" data-toggle="tooltip" title="General"></i></li>';
			} else {
				$html .= '<ul class="menu-content">';
			}

			foreach ($menu as $key => $value) {
				if (is_numeric($key) || in_array($key, ['alias', 'nolink', 'direct_link', '_target'])) continue;
				if (is_array($value) && empty($value)) continue;

				$isActive = '';
				$displayName = function_exists('nameFormat') ? nameFormat($key) : ucwords(str_replace('_', ' ', $key));
				if (is_array($value) && isset($value['alias'])) {
					$displayName = $value['alias'];
				}

				// Clean children
				$children = is_array($value) ? $value : [];
				unset($children['alias'], $children['nolink'], $children['direct_link'], $children['_target']);
				foreach ($children as $k => $v) {
					if (is_array($v) && empty($v)) unset($children[$k]);
				}

				$isNoLink = isset($menu['nolink']) && $menu['nolink'] === true;

				// build current path
				$currentPath = $isNoLink
					? trim($parentPath)
					: trim(($parentPath ? $parentPath . ' ' : '') . $key);

				$effectivePath = str_replace(' ', '.', $currentPath);
				$link = '#';
				$targetAttr = '';

				// --- target attribute ---
				if (is_array($value) && isset($value['_target']) && in_array(strtolower($value['_target']), ['blank', '_blank'])) {
					$targetAttr = ' target="_blank"';
				}

				// --- direct_link support ---
				if (is_array($value) && isset($value['direct_link']) && !empty($value['direct_link'])) {
					$link = url($value['direct_link']);
				} else {
					// --- standard route generation logic ---
					$viewTarget = null;
					if (is_array($value)) {
						if (isset($value['view'])) {
							$viewTarget = $value['view'] ?: 'index';
						} else {
							foreach ($value as $v2) {
								if ($v2 === 'view') {
									$viewTarget = 'index';
									break;
								}
							}
						}
					}

					if ($viewTarget !== null) {
						$routeName = $effectivePath . '.view.' . $viewTarget;
						$urlPath = str_replace('.', '/', $effectivePath) . '/view/' . $viewTarget;
						$link = Route::has($routeName) ? route($routeName) : url($urlPath);
					}
				}

				// detect nested structure
				$hasNested = false;
				foreach ($children as $sub) {
					if (is_array($sub)) {
						$hasNested = true;
						break;
					}
				}

				if (function_exists('isActive')) {
					$isActive = isActive($effectivePath);
				}

				// choose icons
				$iconHtml = '';
				if ($isRoot) {
					$icons = [
						'dashboard'  => 'feather icon-home',
						'management' => 'feather icon-monitor',
						'data_entry' => 'fa fa-database',
						'payments'   => 'feather icon-credit-card',
					];
					$iconHtml = isset($icons[$key]) ? '<i class="' . $icons[$key] . '"></i>' : '<i class="feather icon-circle"></i>';
				}

				// recursive child menu
				$childHtml = '';
				if ($hasNested) {
					$nextParentPath = isset($value['nolink']) && $value['nolink'] === true
						? $parentPath
						: trim(($parentPath ? $parentPath . ' ' : '') . $key);

					$childHtml = renderSideMenu2($children, $nextParentPath, false);
				}

				// render
				if ($childHtml) {
					$html .= "<li class='nav-item has-sub " . e($isActive) . "'>";
					$html .= "<a href='#'{$targetAttr}>{$iconHtml}<span class='menu-title' data-i18n='Templates'>" . e($displayName) . "</span></a>";
					$html .= $childHtml;
					$html .= "</li>";
				} else {
					if ($isRoot) {
						$html .= "<li class='nav-item " . e($isActive) . "'>";
						$html .= "<a href='" . e($link) . "'{$targetAttr}>{$iconHtml}<span class='menu-title' data-i18n='Templates'>" . e($displayName) . "</span></a>";
						$html .= "</li>";
					} else {
						$html .= "<li class='nav-item " . e($isActive) . "'>";
						$html .= "<a class='menu-item' href='" . e($link) . "'{$targetAttr} data-toggle='modal' data-i18n='Vertical'>" . e($displayName) . "</a>";
						$html .= "</li>";
					}
				}
			}

			$html .= '</ul>';
			return $html;
		}
	}
	
	// menu starts.
	function renderMenu($menu){
		$html = '<ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">'.PHP_EOL;
		foreach ($menu as $section => $items) {
			// Section header
			$sectionTitle = ucfirst($section);
			$html .= '
			<li class="navigation-header" style="font-size:13px;">
				<span>'.$sectionTitle.'</span>
				<i class="feather icon-minus" data-toggle="tooltip" title="'.$sectionTitle.'"></i>
			</li>'.PHP_EOL;
			// Loop all items in this section
			foreach ($items as $key => $item) {
				$hasChildren = isset($item['children']) && is_array($item['children']);
				if (!$hasChildren) {
					// Simple top-level item
					$current_route = \Route::currentRouteName() ?? '';
					$is_active = $item['route'] == $current_route ? ' active ' : "";
					// Check active_prefix for related routes
					if (!$is_active && isset($item['active_prefix'])) {
						$prefixes = is_array($item['active_prefix']) ? $item['active_prefix'] : [$item['active_prefix']];
						foreach ($prefixes as $prefix) {
							if (str_starts_with($current_route, $prefix)) {
								$is_active = ' active ';
								break;
							}
						}
					}
					// Special-case: Customer History or Customer Return opened from Customers table
					// (?customer=) should keep the Customers sidebar item active, not the Customer History one.
					$customerQuery = request()->query('customer');
					$customerSubPages = ['customer_history.view.index', 'customer_return.view.index'];
					if (in_array($current_route, $customerSubPages) && !empty($customerQuery)) {
						if ($item['route'] === 'customer_history.view.index') {
							$is_active = '';
						} elseif ($item['route'] === 'management.customers.view.index') {
							$is_active = ' active ';
						}
					}
					// Same rule for Supplier sub-pages (?supplier=) — keep Suppliers active.
					$supplierQuery = request()->query('supplier');
					$supplierSubPages = ['supplier_history.view.index', 'supplier_return.view.index'];
					if (in_array($current_route, $supplierSubPages) && !empty($supplierQuery)) {
						if ($item['route'] === 'supplier_history.view.index') {
							$is_active = '';
						} elseif ($item['route'] === 'management.suppliers.view.index') {
							$is_active = ' active ';
						}
					}
					$html .= '
					<li class="nav-item '.$is_active.'">
						<a href="'.route($item['route']).'" data-label="'.e($item['title']).'">
							<i class="'.(isset($item['icon']) ? $item['icon'] : "").'"></i>
							<span class="menu-title" data-i18n="Templates">'.$item['title'].'</span>
						</a>
					</li>'.PHP_EOL;
				} else {
					// Parent with children — check if any child is active
					$current_route = \Route::currentRouteName();
					$parent_active = '';
					// Also check related routes not shown in menu
					$relatedRoutes = [
						'Customers' => [
							'management.customers.on_account_payment.view.index',
						],
						'Suppliers' => [
							'management.suppliers.on_account_payment.view.index',
							'supplier_payment_history.view.index',
							'dump_return.view.index',
						],
					];
					$extraRoutes = $relatedRoutes[$item['title']] ?? [];
					if (in_array($current_route, $extraRoutes)) {
						$parent_active = ' active open ';
					}
					foreach ($item['children'] as $child) {
						if (isset($child['route']) && $child['route'] == $current_route) {
							$parent_active = ' active open ';
							break;
						}
						if (isset($child['children'])) {
							foreach ($child['children'] as $gc) {
								if (isset($gc['route']) && $gc['route'] == $current_route) {
									$parent_active = ' active open ';
									break 2;
								}
							}
						}
					}
					$html .= '
					<li class="nav-item has-sub '.$parent_active.'">
						<a href="#" data-label="'.e($item['title']).'">
							<i class="'.(isset($item['icon']) ? $item['icon'] : "").'"></i>
							<span class="menu-title" data-i18n="Templates">'.$item['title'].'</span>
						</a>';
					// RECURSIVE CHILD RENDER
					$html .= renderChildMenu($item['children']);
					$html .= '</li>'.PHP_EOL;
				}
			}
		}
		$html .= '</ul>'.PHP_EOL;
		return $html;
	}


	/** Recursive function for children */
	function renderChildMenu($children)
	{
		$html = '<ul class="menu-content">'.PHP_EOL;

		foreach ($children as $key => $child) {

			$hasChildren = isset($child['children']) && is_array($child['children']);

			if (!$hasChildren) {	
				$current_route = \Route::currentRouteName();
				$is_active = $child['route'] == $current_route ? ' active ' : "";
				// Child leaf
				$html .= '
				<li class="nav-item '.$is_active.'">
					<a class="menu-item"
					   href="'.route($child['route']).'"
					   data-toggle="modal"
					   data-i18n="Vertical"><i class="'.(isset($child['icon']) ? $child['icon'] : "").'"></i>
					'.$child['title'].'</a>
				</li>'.PHP_EOL;

			} else {
				// Child has deeper children
				$html .= '
				<li class="nav-item has-sub">
					<a href="#" class="menu-item">'.$child['title'].'</a>';

				// recursive again
				$html .= renderChildMenu($child['children']);

				$html .= '
				</li>'.PHP_EOL;
			}
		}

		$html .= '</ul>'.PHP_EOL;

		return $html;
	}
	// menu ends.
	
	
	function buildRouteTree()
	{
		$tree = [];

		foreach (Route::getRoutes() as $route) {
			$name = $route->getName();
			if (!$name) continue; // skip unnamed routes

			$parts = explode('.', $name);

			// Remove last part (like "view" or "index")
			array_pop($parts);

			// Build nested tree
			$current = &$tree;
			foreach ($parts as $part) {
				if (!isset($current[$part])) {
					$current[$part] = [];
				}
				$current = &$current[$part];
			}
		}

		return $tree;
	}

	function renderRouteTree($tree)
	{
		$html = '<ul>';
		foreach ($tree as $key => $children) {
			$html .= "<li>{$key}";
			if (!empty($children)) {
				$html .= renderRouteTree($children);
			}
			$html .= "</li>";
		}
		$html .= '</ul>';
		return $html;
	}
	
	function mapData(array $mapping, $source)
	{
		$result = [];

		foreach ($mapping as $outputKey => $path) {
			$result[$outputKey] = getValueByPath($source, $path);
		}

		return $result;
	}

	function getValueByPath($data, string $path)
	{
		$segments = explode('.', $path);

		foreach ($segments as $segment) {
			if (is_array($data)) {
				$data = $data[$segment] ?? null;
			} elseif (is_object($data)) {
				$data = $data->$segment ?? null;
			} else {
				return null;
			}
		}

		return $data;
	}

	if (! function_exists('git_branch')) {
		function git_branch() {
			$headPath = base_path('.git/HEAD');

			if (! file_exists($headPath)) {
				return null;
			}

			$head = file_get_contents($headPath);

			if (strpos($head, 'ref:') !== false) {
				return basename(trim(str_replace('ref: refs/heads/', '', $head)));
			}

			return null;
		}
	}

}

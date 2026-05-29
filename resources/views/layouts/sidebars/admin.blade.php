<!-- BEGIN: Main Menu-->
<?php
$user = \Auth::user();
$roles = $user->roles->pluck('name')->toarray();
$GLOBALS['roleName'] = $roles[0] ?? '';
$permissions = $user->getAllPermissions()->toarray();
$GLOBALS['arrayMenuPermission'] = [];
foreach($permissions as $permission)
{
    $GLOBALS['arrayMenuPermission'][] = $permission['name'];
}
$GLOBALS['arrayMenuPermission']=array_unique($GLOBALS['arrayMenuPermission']);
$leftMenuData = (config('permissions'));
$module = module($leftMenuData);
?>

<?php /* ?>
// echo '<pre>';dd($GLOBALS); die;
<?php
function groupLeftMenu($array,$childClass='')
{
    $html = $valueCheckbox = $checked=$href=$active='' ;
    $html2 = '';$html3=$hrefRoute='';
    if(is_array($array))
    {
        $html1 = '<ul class="menu-content">';
        foreach($array as $key=>$value)
        {
            $groupName=$checked='';
            if(is_numeric($key))
            {
                //$groupName =  groupLeftMenu($value,$childClass);
                // $html2 .= "<li class='nav-item ".$active."'><a type='button' class='menu-item' href=".$href." data-toggle='modal'> ".nameFormat($groupName)."</a></li>";
            }else
            {
               // Skip roles and users from sidebar - they are on Settings page now
               $trimmedClass = trim($childClass);
               if(in_array($key, ['roles', 'users']) && ($trimmedClass === 'management' || strpos($trimmedClass, 'management') === 0)) { continue; }

               $valueCheckbox=str_replace(' ','.',$childClass.' '.$key).'.*';
                $hasPermission =  hasPermissionMenu($valueCheckbox);
                if($hasPermission==1)
                {
					//echo $childClass;
                    $href =  str_replace(' ','.',$childClass.' '.$key).'.view.index';
                    if($key=='sales_entry')
                    {
                        $link = route('data_entry.sales_entry.create.index');
                        $html2 .= "<li class='nav-item ".isActive(str_replace(' ','.',$childClass.' '.$key))."'><a  class='menu-item' href=".$link." data-toggle='modal' data-i18n='Vertical'>".nameFormat($key)."</a>".((($value == 'sales_customer') && ($value == 'sales_date')))?: groupLeftMenu($value,$childClass.' '.$key)."</li>";
                    }elseif($key=='purchase_entry')
                    {
                        $link = route('data_entry.purchase_entry.create.index');
                        $html2 .= "<li class='nav-item ".isActive(str_replace(' ','.',$childClass.' '.$key))."'><a  class='menu-item' href=".$link." data-toggle='modal' data-i18n='Vertical'>".nameFormat($key)."</a>".groupLeftMenu($value,$childClass.' '.$key)."</li>";
                    }
					elseif($key=='on_account_payment')
                    {
                        $link = route('management.customers.on_account_payment.create.create');
                        $html2 .= "<li class='nav-item ".isActive(str_replace(' ','.',$childClass.' '.$key))."'><a  class='menu-item' href=".$link." data-toggle='modal' data-i18n='Vertical'>".nameFormat($key)."</a>".groupLeftMenu($value,$childClass.' '.$key)."</li>";
                    }
					elseif($key=='customer_payment')
                    {
                        $link = route('payments.customer_payment.create.create');
                        $html2 .= "<li class='nav-item ".isActive(str_replace(' ','.',$childClass.' '.$key))."'><a  class='menu-item' href=".$link." data-toggle='modal' data-i18n='Vertical'>".nameFormat($key)."</a>".groupLeftMenu($value,$childClass.' '.$key)."</li>";
                    }
                    else
                    {
                        $html2 .= "<li class='nav-item ".isActive(str_replace(' ','.',$childClass.' '.$key))."'><a  class='menu-item' href=".hrefRoute($href)." data-toggle='modal' data-i18n='Vertical'>".nameFormat($key)."</a>".groupLeftMenu($value,$childClass.' '.$key)."</li>";
                    }
                }
            }
        }
        $html3 .= '</ul>';
    }
    if($html2)
    {
        return $html1.$html2.$html3;
    }
    return '';

}

?>
<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li class=" navigation-header"><span>General</span><i class=" feather icon-minus" data-toggle="tooltip" data-placement="right" data-original-title="General"></i></li>

            <?php
            foreach($module as $allModule)
            {
                $iconName='';
                if(hasPermissionMenu($allModule.'.*')){
                    if($allModule=='dashboard')
                    {
                        $iconName= 'feather icon-home';
                    }elseif($allModule=='management')
                    {
                        $iconName= 'feather icon-monitor';
                    }elseif($allModule=='data_entry')
                    {
                        $iconName= 'fa fa-database';
                    }elseif($allModule=='daily_report')
                    {
                        $iconName= 'fa fa-group';
                    }elseif($allModule=='settings')
                    {
                        $iconName= 'fa fa-group';
                    }elseif($allModule=='historical_reports')
                    {
                        $iconName= 'fa fa-history';
                    }else{
                        $iconName= 'fa fa-history';
                    }
                    ?>
                <li class='nav-item {{isActive(str_replace(' ','.',$allModule))}}'>
                    <a href="{{hrefRoute($allModule.'.view.index')}}"><i class="{{$iconName}}"></i><span class="menu-title" data-i18n="Templates">{{nameFormat($allModule)}}</span></a>
                    <?php echo   groupLeftMenu($leftMenuData[$allModule],$allModule); ?>
                </li>
            <?php } }  ?>
			<li class='nav-item {{ request()->is("management/settings*") ? "active" : "" }}'>
				<a href="/management/settings"><i class="fa fa-cog"></i><span class="menu-title">Settings</span></a>
			</li>
			<li>
				<a href="/data_entry/sales_entry/statements/view" target="_blank">Customer Statement</a>
			</li>
			<li>
				<a href="/data_entry/purchase_entry/statements/view" target="_blank">Supplier Statement</a>
			</li>
        </ul>
    </div>
</div>

<!-- Modal -->
<div class="modal fade text-left" id="sales_entry" tabindex="" role="dialog" aria-labelledby="myModalLabel9" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success white">
                <h4 class="modal-title" id="myModalLabel9"><i class="fa fa-tree"></i> New Sales Entry</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="status">Customer<span class="text-danger">*</span></label>
                            <select class="form-control" name="customer" id="customer">
                                <option value="">--Select--</option>
                                <option value="user1">User 1</option>
                                <option value="user2">User 2</option>
                            </select>
                            <div class="row"><div class="col-sm-12" data-validate="is_active"></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn grey btn-outline-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-success">Save</button>
            </div>
        </div>
    </div>
</div>
<!-- ENDS: Main Menu-->
<?php */ ?>
@php
    $modules = config('menu-categories'); // adjust name if your file is different
@endphp
<div class="main-menu menu-fixed menu-accordion menu-shadow expanded menu-dark" data-scroll-to-active="true">
    {{-- Sidebar header with logo + close button --}}
    <div class="sidebar-brand-logo">
        <a href="{{route('dashboard.view.index')}}" class="sidebar-brand-text">
            R & A Veg Ltd
        </a>
    </div>
    <div class="main-menu-content">
        {!! renderMenu($modules) !!}
    </div>
    <div class="sidebar-logout">
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
            <i class="fa fa-arrow-circle-left"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
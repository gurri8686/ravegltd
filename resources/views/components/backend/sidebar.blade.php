<div class="main-menu menu-fixed menu-dark menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            @if(sizeof($menu) > 0)
                @foreach($menu as $m)
                    <li class=" 
                       @if(!empty($m->active))
                       {{ strpos(Route::current()->getName(), $m->active) === 0 ? 'active' : '' }}
                       @endif
                       nav-item"><a href="{{$m->route_name ? route($m->route_name,[]) : 'javascript:void(0)' }}"><i class="{{$m->icon}}"></i><span class="menu-title" data-i18n="{{$m->title}}">{{$m->title}}</span></a>
                        @if(sizeof($m->childLevel_1) > 0)
                            <ul class="menu-content">
                                @foreach($m->childLevel_1 as $c_1)
                                    <li><a class="menu-item" href="{{$c_1->route_name ? route($c_1->route_name,[]) : 'javascript:void(0)' }}" data-i18n="{{$c_1->title}}">{{$c_1->title}}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
<div class="content-header-right col-md-6 col-12 mb-md-0 mb-2">
    <div class="btn-group float-md-right" role="group" aria-label="Button group with nested dropdown">
        <div class="btn-group" role="group">
            <button class="btn btn-outline-primary dropdown-toggle dropdown-menu-right" id="btnGroupDrop1" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="feather icon-settings icon-left"></i> {{__('Actions')}}</button>
            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                @if(sizeof($links) > 0)
                    @foreach($links as $link)
                        <a class="dropdown-item" href="{{$link['href']}}">{{__($link['a'])}}</a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
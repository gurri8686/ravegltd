<div class="content-header-left col-md-6 col-12 mb-2">
    <h3 class="content-header-title mb-0">{{__($title)}}</h3>
    <div class="row breadcrumbs-top">
        <div class="breadcrumb-wrapper col-12">
            @if(sizeof($links) > 0)
            <ol class="breadcrumb">
                @foreach($links as $link)
                    @if(empty($link['href']))
                        <li class="breadcrumb-item">{{__($link['a'])}}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{$link['href']}}">{{__($link['a'])}}</a></li>
                    @endif
                @endforeach
            </ol>
            @endif
        </div>
    </div>
</div>
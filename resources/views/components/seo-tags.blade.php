@if(@$tags['title'])
<title>{{env('APP_NAME')}} | {{$tags['title']}}</title>
@endif
@if(@$tags['keywords'])
<meta name="keywords" content="{{$tags['title']}}">
@endif
@if(@$tags['description'])
<meta name="description" content="{{$tags['title']}}">
@endif
@if(@$tags['author'])
<meta name="author" content="{{$tags['title']}}">
@endif
@extends('layouts.empty')

@section('sidebar')
    @include('layouts.sidebars.admin');
@endsection

@section('content')
    <center>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <h2>{{ __('Logout') }}</h2>
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
    </center>
@endsection

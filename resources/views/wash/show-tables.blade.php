@extends('layouts.test')

@section('content')
<div class="container mt-4">
    <h4 class="mb-4">Database Tables ({{ count($tableNames) }})</h4>

    <form action="{{ url('/wash-truncate') }}" method="POST">
        @csrf
        <div class="row">
            @foreach($tableNames as $table)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body pl-4 py-3">
                            <input 
                                type="checkbox" 
                                name="tables[]" 
								checked
                                value="{{ $table }}" 
                                id="table_{{ $loop->index }}" 
                                class="form-check-input me-2"
                            >
                            <label 
                                for="table_{{ $loop->index }}" 
                                class="form-check-label text-start mb-0 w-100"
                                style="cursor: pointer;"
                            >
                                {{ $table }}
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary px-4">
                Submit Selected Tables
            </button>
        </div>
    </form>
</div>

@endsection
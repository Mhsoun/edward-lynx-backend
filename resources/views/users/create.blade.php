@extends('layouts.default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h2 style="margin-top: 0px;">Create User</h2>
        </div>
        <div class="col-md-6" style="text-align: right"></div>
    </div>

    @if ($errors->count())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.store') }}" method="post" style="margin-top: 30px">

        @include('users.partials.form')

        <div class="row">
            <div class="col-md-12" style="text-align: right">
                <button class="btn btn-primary">Create User</button>
            </div>
        </div>
    </form>
@endsection
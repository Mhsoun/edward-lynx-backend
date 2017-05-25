@extends('layouts.default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h2 style="margin-top: 0px;">Edit User</h2>
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

    <form action="{{ route('users.update', $user) }}" method="post" style="margin-top: 30px">
        {{ method_field('PUT') }}

        @include('users.partials.form')

        <div class="row">
            <div class="col-md-12" style="text-align: right">
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
@endsection
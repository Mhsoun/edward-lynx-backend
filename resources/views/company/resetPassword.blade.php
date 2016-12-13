@extends('layouts.no-nav')
@section('content')
    {!! Form::open(['action' => ['CompanyController@resetPassword', 'id' => $user->id], 'method' => 'put', 'role' => 'form']) !!}
        <h2>{{ Lang::get('company.resetPassword') }} - {{ $user->name }}</h2>
        @include('errors.list')

        <div class="form-group">
            <label>{{ Lang::get('company.password')}}</label>
            <input type="password" class="form-control" name="password" style="max-width: 40%">
        </div>

        <div class="form-group">
            <label>{{ Lang::get('company.confirmPassword')}}</label>
            <input type="password" class="form-control" name="confirmPassword" style="max-width: 40%">
        </div>

        <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.change') }}</button
    {!! Form::close() !!}

    <br>
    <br>
    <a href="{{ action('CompanyController@edit', ['id' => $user->id]) }}">{{ Lang::get('buttons.back') }}</a>
@stop

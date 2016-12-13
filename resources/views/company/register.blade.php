@extends('layouts.default')

@section('content')
	<div class="mainBox">
		<h2>{{ Lang::get('company.header') }}</h2>

		@if (count($errors) > 0)
			<div class="alert alert-danger">
				There were some problems with your input.<br><br>
				<ul>
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		{!! Form::open(['action' => 'CompanyController@store', 'role' => 'form']) !!}
			<div class="form-group">
				<label>{{ Lang::get('company.name') }}</label>
				<input type="text" class="form-control" name="name" value="{{ old('name') }}" style="max-width: 40%">
			</div>

			<div class="form-group">
				<label>{{ Lang::get('company.email') }}</label>
				<input type="email" class="form-control" name="email" value="{{ old('email') }}" style="max-width: 40%">
			</div>

			<div class="form-group">
				<label>{{ Lang::get('company.otherInfo') }}</label>
				<textarea id="info" name="info" class="form-control" rows="5" style="max-width: 40%">{{ old('info') }}</textarea>
			</div>

			<div class="form-group">
				<button type="submit" class="btn btn-primary">
					{{ Lang::get('buttons.register') }}
				</button>
			</div>
		{!! Form::close() !!}

		<a href="{{ action('CompanyController@index') }}">{{ Lang::get('buttons.back') }}</a>
	</div>
@endsection

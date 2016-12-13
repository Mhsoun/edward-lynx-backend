{!! Form::open(['method' => 'put', 'action' => 'UserController@updatePassword']) !!}
	<h3>{{ Lang::get('settings.changePasswordHeader') }}</h3>
	<div class="form-group">
    	<label for="currentPassword">{{ Lang::get('settings.currentPassword') }}</label>
    	<input type="password" class="form-control" name="currentPassword" id="currentPassword" style="max-width: 40%">

		<label for="newPassword">{{ Lang::get('settings.newPassword') }}</label>
		<input type="password" class="form-control" name="newPassword" id="newPassword" style="max-width: 40%">

		<label for="newPasswordAgain">{{ Lang::get('settings.newPasswordAgain') }}</label>
		<input type="password" class="form-control" name="newPasswordAgain" id="newPasswordAgain" style="max-width: 40%">
	</div>

	<button type="submit" class="btn btn-primary">{{ Lang::get('buttons.change') }}</button>
{!! Form::close() !!}

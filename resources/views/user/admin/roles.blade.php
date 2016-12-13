@extends('layouts.default')
@section('content')
    <?php
        $languages = [
            (object)['id' => 'en', 'displayText' => Lang::get('editRoles.englishName'), 'placeholderText' => Lang::get('editRoles.englishNamePlaceholder')],
            (object)['id' => 'sv', 'displayText' => Lang::get('editRoles.swedishName'), 'placeholderText' => Lang::get('editRoles.swedishNamePlaceholder')],
            (object)['id' => 'fi', 'displayText' => Lang::get('editRoles.finnishName'), 'placeholderText' => Lang::get('editRoles.finnishNamePlaceholder')]
        ];
    ?>

    <div class="mainBox">
		<h2>{{ Lang::get('editRoles.addRole') }}</h2>
		@include('errors.list')
		{!! Form::open(['role' => 'form']) !!}
			<div class="form-group">
				<label>{{ Lang::get('editRoles.surveyType') }}</label>
				<div class="radio">
					<label>
						<input type="radio" name="surveyType" value="0" {{ old('surveyType') != null && old('surveyType') == "0" ? "checked" : "" }}>
						{{ Lang::get('surveys.individualType') }}
					</label>
				</div>

				<div class="radio">
					<label>
						<input type="radio" name="surveyType" value="1" {{ old('surveyType') != null && old('surveyType') == "1" ? "checked" : "" }}>
						{{ Lang::get('surveys.groupType') }}
					</label>
				</div>
			</div>

            @foreach($languages as $lang)
                <div class="form-group">
    				<label>{{ $lang->displayText }}</label>
    				<input type="input" class="form-control" style="max-width: 18em" name="{{ $lang->id . 'Name' }}"
    					   placeholder="{{ $lang->placeholderText }}"
    					   value="{{ old($lang->id . 'Name') }}">
    			</div>
            @endforeach

			<button type="submit" class="btn btn-primary">{{ Lang::get('buttons.add') }}</button>
		{!! Form::close() !!}

		<h2>{{ Lang::get('editRoles.headerText') }}</h2>
    	<table class="table" style="max-width: 70%">
			<tr>
	    		<th>{{ Lang::get('editRoles.surveyType') }}</th>
                @foreach($languages as $lang)
                    <th>{{ $lang->displayText }}</th>
                @endforeach
	    		<th></th>
			</tr>
			@foreach ($roles as $role)
				<tr id="role_{{ $role->id }}">
					<td>{{ \App\SurveyTypes::name($role->surveyType) }}</td>
                    @foreach($languages as $lang)
                        <?php $name = $role->name($lang->id); ?>
                        <td>
                            <span class="displayText {{ $lang->id }}">{{ $name }}</span>
                            <input type="text" style="display: none" class="editRole form-control editText {{ $lang->id }}" value="{{ $name }}" autocomplete="off">
                        </td>
                    @endforeach
                    <td>
                        <a class="btn btn-primary btn-xs" href="javascript:toggleEditRole('{{ $role->id}}')">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </a>

                        <a class="btn btn-success btn-xs editRole" style="display: none; margin-top: 5px;" href="javascript:updateRole('{{ $role->id}}')">
                            <span class="glyphicon glyphicon-floppy-disk"></span>
                        </a>
                    </td>
				</tr>
			@endforeach
    	</table>
    </div>

    <script type="text/javascript">
        //Toggles the edit for the given role
        function toggleEditRole(roleId) {
            var rowElement = $('#role_' + roleId);
            rowElement.find(".editRole").toggle();
        }

        //Updates the given role
        function updateRole(roleId) {
            var rowElement = $('#role_' + roleId);
            var newRole = {};

            @foreach($languages as $lang)
                newRole['{{ $lang->id }}'] = $(rowElement.find(".editText.{{ $lang->id }}")).val();
            @endforeach

            $.ajax({
                url: "/roles",
                method: "put",
                data: {
                    roleId: roleId,
                    names: newRole
                },
                dataType: "json"
            }).done(function(data) {
                if (data.success) {
                    for (var lang in newRole) {
                        $(rowElement.find(".displayText." + lang)).text(newRole[lang]);
                    }

                    toggleEditRole(roleId);
                }
            });
        }
    </script>
@stop

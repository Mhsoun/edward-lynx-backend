<div id="step3Subcompany">
	<h3>{{ Lang::get('groups.subcompany') }}</h3>
	{{ Lang::get('groups.subcompanyInfoText') }}

	<h4>{{ Lang::get('groups.selectSubcompany') }}</h4>
	<div class="form-group">
		<select id="selectSubcompany" class="form-control" style="max-width: 40%">
			<option value="all">{{ Lang::get('groups.entireCompany') }}</option>
			@foreach ($groups as $group)
				@if ($group->isSubcompany)
					<option value="{{ $group->id }}">{{ $group->name }}</option>
				@endif
			@endforeach
		</select>
	</div>
	<button class="btn btn-primary" type="button" onclick="SurveyStep6.Subcompany.select()">{{ Lang::get('buttons.select') }}</button>

	<h4>{{ Lang::get('groups.createSubcompany') }}</h4>
	<div class="form-group">
		<label>{{ Lang::get('groups.subcompanyName') }}</label>
		<input type="text" class="form-control" id="newSubcompanyName"
			   placeholder="{{ Lang::get('groups.subcompanyNamePlaceholder') }}" style="max-width: 40%">
	</div>
	<button class="btn btn-primary" type="button" onclick="SurveyStep6.Subcompany.create()">{{ Lang::get('buttons.create') }}</button>
</div>

<div id="step3IndividualBox" style="display: none">
	@include('survey.partials.step6Individual')
</div>

<div id="step3GroupBox" style="display: none">
	@include('survey.partials.step6Group')
</div>

<div id="step3NormalBox" style="display: none">
	@include('survey.partials.step6Normal')
</div>

<button id="step6NextBtn" class="btn btn-primary btn-lg pull-right nextBtn" type="button">{{ Lang::get('buttons.next') }}</button>

<script type="text/javascript">
	Survey.languageStrings["recipientName"] = "{!! Lang::get('surveys.recipientName') !!}";
	Survey.languageStrings["recipientNamePlaceholder"] = "{!! Lang::get('surveys.recipientNamePlaceholder') !!}";
	Survey.languageStrings["recipientEmail"] = "{!! Lang::get('surveys.recipientEmail') !!}";
    Survey.languageStrings["recipientEmailPlaceholder"] = "{!! Lang::get('surveys.recipientEmailPlaceholder') !!}";
    Survey.languageStrings["recipientPosition"] = "{!! Lang::get('surveys.recipientPosition') !!}";
    Survey.languageStrings["recipientPositionPlaceholder"] = "{!! Lang::get('surveys.recipientPositionPlaceholder') !!}";

    Survey.languageStrings["groups.deleteSubgroupConfirmation"] = "{!! Lang::get('groups.deleteSubgroupConfirmation') !!}";
	Survey.languageStrings["groups.deleteGroupConfirmation"] = "{!! Lang::get('groups.deleteGroupConfirmation') !!}";

	Survey.languageStrings["surveys.rolesToContinueText"] = "{!! Lang::get('surveys.rolesToContinueText') !!}";

	Survey.languageStrings["surveys.roleToEvaluate"] = "{!! Lang::get('surveys.roleToEvaluate') !!}";
	Survey.languageStrings["surveys.evalutingRole"] = "{!! Lang::get('surveys.evalutingRole') !!}";
	Survey.languageStrings["surveys.toEvaluate"] = "{!! Lang::get('surveys.toEvaluate') !!}";

	Survey.languageStrings["surveys.alreadyIncluded"] = "{!! Lang::get('surveys.alreadyIncluded') !!}";
	Survey.languageStrings["surveys.alreadyInvited"] = "{!! Lang::get('surveys.alreadyInvited') !!}";
	Survey.languageStrings["surveys.selectRecipients"] = "{!! Lang::get('surveys.selectRecipients') !!}";

	Survey.languageStrings["survey.confirmCreation"] = "{!! Lang::get('surveys.confirmCreation') !!}";
</script>
<script type="text/javascript" src="{{ asset('js/survey.step6.js') }}"></script>

<script type="text/javascript">
	@foreach ($recipients as $recipient)
		SurveyStep6.addExistingRecipient(
			{{ $recipient->id }},
			{!! json_encode($recipient->name) !!},
			{!! json_encode($recipient->mail) !!},
			{!! json_encode($recipient->position ?: "") !!});
	@endforeach

    @foreach (\App\Roles::getLMTT() as $role)
        SurveyStep6.Group.roles.push({
        	id: {{ $role->id }},
        	name: {!! json_encode($role->name()) !!}
        });
    @endforeach

	var groupMembers = [];
	@foreach ($groups as $group)
		@if (!$group->isSubcompany)
			groupMembers = [];
			@foreach ($group->members as $member)
	            groupMembers.push({
	                id: {{ $member->memberId }},
	                name: {!! json_encode($member->recipient->name) !!},
	                email: {!! json_encode($member->recipient->mail) !!},
	                roleId: {{ $member->roleId }},
	                position: {!! json_encode($member->recipient->position ?: "") !!},
	                included: false
	            });
			@endforeach

			@if ($group->parentGroupId != null)
				SurveyStep6.Group.addInSubcompany(
		        	{{ $group->id }},
		        	{!! json_encode($group->name) !!},
		        	groupMembers,
		        	{{ $group->parentGroupId }},
		        	{!! json_encode($group->parentGroup->name) !!});
			@else
				SurveyStep6.Group.add(
		        	{{ $group->id }},
		        	{!! json_encode($group->name) !!},
		        	groupMembers);
			@endif
        @endif
	@endforeach
</script>

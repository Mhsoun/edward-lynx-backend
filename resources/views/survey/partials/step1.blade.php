<h2>{{ Lang::get('surveys.step1InfoText') }}</h2>

<div class="form-group">
	@if (\App\SurveyTypes::canCreateIndividual(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
		<div class="radio">
			<label>
				<input type="radio" name="type" value="individual" autocomplete="off">
				{{ Lang::get('surveys.individualType') }}
			</label>
		</div>
	@endif

	@if (\App\SurveyTypes::canCreateGroup(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
		<div class="radio">
			<label>
				<input type="radio" name="type" value="group" autocomplete="off">
				{{ Lang::get('surveys.groupType') }}
			</label>
		</div>
	@endif

	@if (\App\SurveyTypes::canCreateProgress(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
		<div class="radio">
			<label>
				<input type="radio" name="type" value="progress" autocomplete="off">
				{{ Lang::get('surveys.progressType') }}
			</label>
		</div>
	@endif

	@if (\App\SurveyTypes::canCreateLTT(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
		<div class="radio">
			<label>
				<input type="radio" name="type" value="ltt" autocomplete="off">
				{{ Lang::get('surveys.lttType') }}
			</label>
		</div>
	@endif

	@if (\App\SurveyTypes::canCreateNormal(Auth::user()->allowedSurveyTypes) || Auth::user()->isAdmin)
		<div class="radio">
			<label>
				<input type="radio" name="type" value="normal" autocomplete="off">
				{{ Lang::get('surveys.normalType') }}
			</label>
		</div>
	@endif
</div>

<button id="step1NextBtn" class="btn btn-primary nextBtn btn-lg pull-right" type="button">{{ Lang::get('buttons.next') }}</button>
<script type="text/javascript" src="{{ asset('js/survey.step1.js') }}"></script>

<script type="text/javascript">
    @if (Auth::user()->isAdmin && isset($companyId))
        Survey.setCompanyId({{ $companyId }});
    @endif
</script>

<h2>{{ Lang::get('surveys.step7InfoText') }}</h2>

<table id="extraQuestionsTable" class="table" style="max-width: 60%">
	<tr class="firstRow">
		<th><a href="javascript:SurveyStep7.selectAllExtraQuestions()">{{ Lang::get('surveys.include') }}</a></th>
		<th>{{ Lang::get('surveys.recipientName') }}</th>
		<th>{{ Lang::get('surveys.selectableValues') }}</th>
		<th>{{ Lang::get('surveys.mandatory') }}</th>
	</tr>
	@foreach (\App\Models\ExtraQuestion::forUser($companyId) as $extraQuestion)
		@foreach ($extraQuestion->languages() as $lang)
			<tr class="extraQuestion {{ 'lang-' . $lang }}">
				<td><input type="checkbox" name="extraQuestions[]" value="{{ $extraQuestion->id }}"></td>
				<td>{{ $extraQuestion->name($lang) }}</td>
				@if ($extraQuestion->type == \App\ExtraAnswerValue::Options)
					<td>
						<select class="form-control">
							@foreach ($extraQuestion->values as $value)
								<option>{{ $value->name($lang) }}</option>
							@endforeach
						</select>
					</td>
				@elseif ($extraQuestion->type == \App\ExtraAnswerValue::Date)
					<td>{{ Lang::get('surveys.date') }}</td>
				@elseif ($extraQuestion->type == \App\ExtraAnswerValue::Text)
					<td>{{ Lang::get('surveys.freeText') }}</td>
				@else
					<td>
						<select class="form-control">
							@foreach ($extraQuestion->values as $value)
								<option>{{ $value->fullName($lang) }}</option>
							@endforeach
						</select>
					</td>
				@endif

				<td>{{ $extraQuestion->isOptional ? Lang::get('buttons.no') : Lang::get('buttons.yes') }}</td>
			</tr>
		@endforeach
	@endforeach
</table>

<h3>{{ Lang::get('surveys.addNewInformation') }}</h3>
<b>{{ Lang::get('surveys.recipientName') }}</b>
<input type="text" class="form-control" style="max-width: 20em; margin-bottom: 5px" id="extraQuestionName">

<b>{{ Lang::get('surveys.mandatory') }}</b>
<div class="checkbox">
	<label>
		<input type="checkbox" id="extraQuestionMandatory"> {{ Lang::get('buttons.yes') }}
	</label>
</div>

<b>{{ Lang::get('surveys.extraQuestionType') }}</b>
<div class="radio">
	<label>
		<input type="radio" name="extraQuestionType" value="text">
		{{ Lang::get('surveys.freeText') }}
	</label>
</div>

<div class="radio">
	<label>
		<input type="radio" name="extraQuestionType" value="date">
		{{ Lang::get('surveys.date') }}
	</label>
</div>

<div class="radio">
	<label>
		<input type="radio" name="extraQuestionType" value="options">
		{{ Lang::get('surveys.predefinedValues') }}
	</label>
</div>

<div class="radio">
	<label>
		<input type="radio" name="extraQuestionType" value="hierarchy">
		{{ Lang::get('surveys.hierarchy') }}
	</label>
</div>

<div style="display: none;" id="optionValuesBox">
	<b>{{ Lang::get('surveys.extraQuestionValues') }}</b>
	<div id="extraQuestionOptionValues" style="margin-bottom: 10px"></div>

	<input type="text" class="form-control" style="max-width: 20em; margin-bottom: 5px; display: inline" id="newOptionValue">
	<a class="textButton"><span class="glyphicon glyphicon-plus" id="addOptionValue"></span></a>
</div>

<div style="display: none;" id="hierarchyBox">
	<div id="hierarchyView"></div>
</div>

<button type="button" class="btn btn-primary" id="addExtraQuestionButton">{{ Lang::get('buttons.add') }}</button>

<button id="step7NextBtn" class="btn btn-success btn-lg pull-right" type="button">{{ Lang::get('buttons.finish') }}</button>

<script type="text/javascript">
	Survey.languageStrings["buttons.yes"] = "{!! Lang::get('buttons.yes') !!}";
	Survey.languageStrings["buttons.no"] = "{!! Lang::get('buttons.no') !!}";
	Survey.languageStrings["surveys.freeText"] = "{!! Lang::get('surveys.freeText') !!}";
	Survey.languageStrings["surveys.date"] = "{!! Lang::get('surveys.date') !!}";
</script>
<script type="text/javascript" src="{{ asset('js/survey.step7.js') }}"></script>

<h2>{{ Lang::get('surveys.step2InfoText') }}</h2>

<div class="form-group">
	<label for="name">{{ Lang::get('surveys.name') }}</label>
	<input id="name" name="name" type="text" class="form-control"
		   placeholder="{{ Lang::get('surveys.namePlaceholder') }}"
		   autocomplete="off"
		   style="max-width: 40%">
</div>

<div class="form-group">
	<label>{{ Lang::get('surveys.selectLanguage') }}</label>
	<select id="language" name="language" class="form-control bfh-languages" data-language="en" style="max-width: 40%">
		<option value="en">{{ Lang::get('surveys.languageEnglish') }}</option>
		<option value="sv">{{ Lang::get('surveys.languageSwedish') }}</option>
		<option value="fi">{{ Lang::get('surveys.languageFinnish') }}</option>
		{{-- <option value="ru">{{ Lang::get('surveys.languageRussian') }}</option>		 --}}
	</select>
</div>

<button id="step2NextBtn" class="btn btn-primary nextBtn btn-lg pull-right" type="button">{{ Lang::get('buttons.next') }}</button>
<script type="text/javascript" src="{{ asset('js/survey.step2.js') }}"></script>

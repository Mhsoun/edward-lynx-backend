@extends('layouts.default')
@section('content')
	<div class="mainBox">
		<h2>{{ $reportTemplate->name }}</h2>

		{!! Form::open(['method' => 'put', 'action' => ['ReportTemplateController@update', 'id' => $reportTemplate->id]]) !!}
			<?php $pages = $reportTemplate->pages(); ?>
			@if (count($pages) > 0)
				<h3>{{ Lang::get('reportTemplates.pageOrders') }}</h3>
				@include('reportTemplate.pageOrders', ['pages' => $pages])
				<br>
			@endif

			<h3>{{ Lang::get('reportTemplates.diagramsTexts') }}</h3>
			<div id="reportTexts" class="row">
				@foreach (\App\Models\DefaultText::defaultReportTextsFor(Auth::user(), $reportTemplate->surveyType)->reportTexts as $defaultText)
					<?php $getText = $defaultText->getText; ?>
					<?php
					 	$text = $getText($reportTemplate->surveyType, $reportTemplate->lang);
						$include = false;

						$diagram = $reportTemplate->diagrams()
							->where('typeId', '=', $text->type)
							->first();

						if ($diagram != null) {
							$include = $diagram->includeDiagram;

							if ($diagram->isDiagram) {
								$text->subject = $diagram->title;
							}

							$text->message = $diagram->text;
						}
					?>

					<div class="col-md-5">
						@if (isset($text->subject))
							<h4>{{ $defaultText->header }}</h4>
							<div class="checkbox">
								<label>
									<input type="checkbox" name="diagram_{{ $text->type }}_include"
									       value="yes" autocomplete="off"
										   {{ $include ? 'checked' : '' }}>
									{{ Lang::get('reportTemplates.includeInReport') }}
								</label>
							</div>

							@include('user.partials.editemail', [
								'emailHeader' => '',
								'subjectFieldName' => 'diagram_' . $text->type . '_title',
								'textFieldName' => 'diagram_' . $text->type . '_text',
								'email' => $text,
								'hideHelpBox' => true])
						@else
							@include('user.partials.editinformation', [
								'labelText' => $defaultText->header,
								'fieldName' => 'text_' . $text->type,
								'text' => $text->message,
								'small' => isset($defaultText->small) ? $defaultText->small : false])
						@endif
					</div>
				@endforeach
			</div>

			<button type="submit" class="btn btn-primary btn-lg">{{ Lang::get('buttons.save') }}</button>
		{!! Form::close() !!}

		<br>
		<a href="{{ action('ReportTemplateController@index') }}">{{ Lang::get('buttons.back') }}</a>
	</div>
@endsection

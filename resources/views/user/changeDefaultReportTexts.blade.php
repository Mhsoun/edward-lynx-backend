<p>{!! Lang::get('settings.defaultReportTextsText') !!}</p>
<div id="reportTexts" class="row">
    @foreach ($languages as $langId => $langName)
        <div class="lang lang-{{ $langId }}">
            @foreach (\App\Models\DefaultText::defaultReportTexts(Auth::user()) as $surveyType)
                <div class="type type-{{ $surveyType->id }}">
                    @foreach ($surveyType->reportTexts as $defaultText)
                        <?php $getText = $defaultText->getText; ?>
                        <?php $text = $getText($surveyType->id, $langId); ?>

                        <div class="col-md-5">
                            @if (isset($text->subject))
                                @include('user.partials.editemail', [
                                    'emailHeader' => $defaultText->header,
                                    'subjectFieldName' => 'email_' . $text->type . '_subject_' . $surveyType->id . '_' . $langId,
                                    'textFieldName' => 'email_' . $text->type . '_message_' . $surveyType->id . '_' . $langId,
                                    'email' => $text,
                                    'hideHelpBox' => true])
                            @else
                                @include('user.partials.editinformation', [
                                    'labelText' => $defaultText->header,
                                    'fieldName' => 'text_' . $text->type . '_' . $surveyType->id . '_' . $langId,
                                    'text' => $text->text,
                                    'small' => isset($defaultText->small) ? $defaultText->small : false])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</div>

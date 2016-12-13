<p>{!! Lang::get('settings.defaultEmailText') !!}</p>
<div id="emailTexts" class="row">
    @foreach ($languages as $langId => $langName)
        <div class="lang lang-{{ $langId }}">
            @foreach (\App\Models\DefaultText::defaultEmails(Auth::user()) as $surveyType)
                <div class="type type-{{ $surveyType->id }}">
                    @foreach ($surveyType->emails as $defaultEmail)
                        <?php $getEmail = $defaultEmail->getEmail; ?>
                        <?php $email = $getEmail($surveyType->id, $langId); ?>

                        <div class="col-md-5">
                            @include('user.partials.editemail', [
                                'emailHeader' => $defaultEmail->header,
                                'subjectFieldName' => 'email_' . $email->type . '_subject_' . $surveyType->id . '_' . $langId,
                                'textFieldName' => 'email_' . $email->type . '_message_' . $surveyType->id . '_' . $langId,
                                'email' => $email])
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</div>

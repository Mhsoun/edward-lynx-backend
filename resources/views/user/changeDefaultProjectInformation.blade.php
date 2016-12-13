<p>{!! Lang::get('settings.defaultProjectInformationText') !!}</p>
<div id="projectInformations" class="row">
    @foreach ($languages as $langId => $langName)
        <div class="lang lang-{{ $langId }}">
            @foreach (\App\Models\DefaultText::defaultInformations(Auth::user()) as $surveyType)
                <div class="type type-{{ $surveyType->id }}">
                    @foreach ($surveyType->texts as $defaultText)
                        <?php $getText = $defaultText->getText; ?>
                        <?php $text = $getText($surveyType->id, $langId); ?>

                        <div class="col-md-5">
                            @include('user.partials.editinformation', [
                                'labelText' => $defaultText->header,
                                'fieldName' => 'text_' . $text->type . '_' . $surveyType->id . '_' . $langId,
                                'text' => $text->text,
                                'small' => false])
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</div>

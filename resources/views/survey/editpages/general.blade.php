<div class="tab-pane" id="general">
    {!! Form::open(['action' => ['SurveyUpdateController@updateGeneral', $survey->id], 'method' => 'put']) !!}
        <br>
        <div class="form-group">
            <label for="name">{{ Lang::get('surveys.name') }}</label>
            <input name="name" type="text" class="form-control" value="{{ $survey->name }}" style="width: 40%;">
        </div>

        @include('shared.dateTimePicker', [
            'name' => 'startDate',
            'label' => Lang::get('surveys.startDate'),
            'value' => $survey->startDate
        ])

        @include('shared.dateTimePicker', [
            'name' => 'endDate',
            'label' => Lang::get('surveys.endDate'),
            'value' => $survey->endDate
        ])

        @if ($survey->type == \App\SurveyTypes::Progress)
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="showCategoryTitles" value="yes" {{ $survey->showCategoryTitles ? 'checked' : '' }}>
                        {{ Lang::get('surveys.showCategoryTitles') }}
                    </label>
                </div>
            </div>
        @endif

        <div class="form-group">
            @include('help.descriptionText', [
                'showLabel' => true,
                'labelText' => Lang::get('surveys.description'),
                'labelFor' => 'description',
                'boxName' => 'descriptionHelpBox'
            ])
            <textarea name="description" class="form-control" rows="8" style="width: 40%;">{{ $survey->description }}</textarea>
        </div>

        @if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress)
            <div class="form-group">
                @include('help.descriptionText', [
                    'showLabel' => true,
                    'labelText' => Lang::get('surveys.inviteTextLabel'),
                    'labelFor' => 'inviteText',
                    'boxName' => 'inviteTextHelpBox'
                ])
                <textarea name="inviteText" class="form-control" rows="8" style="width: 40%;">{{ $survey->inviteText }}</textarea>
            </div>
        @endif

        <div class="form-group">
            @include('help.descriptionText', [
                'showLabel' => true,
                'labelText' => Lang::get('surveys.questionInfoText'),
                'labelFor' => 'description',
                'boxName' => 'descriptionHelpBox'
            ])
            <textarea name="questionInfo" class="form-control" rows="8" style="width: 40%;">{{ $survey->questionInfoText }}</textarea>
        </div>

        <div class="form-group">
            @include('help.descriptionText', [
                'showLabel' => true,
                'labelText' => Lang::get('surveys.thankYouText'),
                'labelFor' => 'description',
                'boxName' => 'descriptionHelpBox'
            ])
            <textarea name="thankYou" class="form-control" rows="8" style="width: 40%;">{{ $survey->thankYouText }}</textarea>
        </div>

        <button class="btn btn-primary" type="submit">{{ Lang::get('buttons.update') }}</button>
    {!! Form::close() !!}
</div>

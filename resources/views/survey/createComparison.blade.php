@extends('layouts.default')

@section('content')
    <div class="mainBox">
        <h2>{{ Lang::get('surveys.createComparison') }}</h2>
        {{ Lang::get('surveys.createComparisonText') }}
        @include('errors.list')

        <?php
            $surveyName = $survey->name . ' (' . Lang::get('surveys.comparison') . ')';
            if (old('surveyName') !== null) {
                $surveyName = old('surveyName');
            }
        ?>

        <br>
        <br>

        {!! Form::open() !!}
            <div class="form-group">
                <label>{{ Lang::get('surveys.name') }}</label>
                <input name="surveyName" type="text" class="form-control"
                       value="{{ $surveyName }}" style="max-width: 40%">
            </div>

            @include('shared.dateTimePicker', [
                'name' => 'startDate',
                'label' => Lang::get('surveys.startDate'),
                'value' => old('startDate')
            ])

            @include('shared.dateTimePicker', [
                'name' => 'endDate',
                'label' => Lang::get('surveys.endDate'),
                'value' => old('endDate')
            ])

            @section('boxContent')
                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.toEvaluateEmail'),
                    'name' => 'toEvaluateInvitation',
                    'emailText' => $survey->toEvaluateText,
                    'useOld' => true
                ])

                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.userReportEmail'),
                    'name' => 'userReport',
                    'emailText' => $survey->userReportText,
                    'useOld' => true
                ])

                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.invitationEmail'),
                    'name' => 'invitation',
                    'emailText' => $survey->invitationText,
                    'useOld' => true
                ])

                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.remindingEmail'),
                    'name' => 'reminder',
                    'emailText' => $survey->manualRemindingText,
                    'useOld' => true
                ])

                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.inviteRemindingMail'),
                    'name' => 'inviteOthersReminder',
                    'emailText' => $survey->inviteOthersRemindingText,
                    'useOld' => true
                ])
            @overwrite
            @include('survey.partials.showHideBox', ['boxName' => 'emailBox', 'title' => Lang::get('surveys.editTabEmails')])

            <br>

            @section('boxContent')
                <div class="form-group">
                    @include('help.descriptionText', [
                        'showLabel' => true,
                        'labelText' => Lang::get('surveys.description'),
                        'labelFor' => 'description',
                        'boxName' => 'descriptionHelpBox'
                    ])
                    @include('survey.partials.editText', ['name' => 'description', 'value' => $survey->description])
                </div>

                <div class="form-group">
                    @include('help.descriptionText', [
                        'showLabel' => true,
                        'labelText' => Lang::get('surveys.inviteTextLabel'),
                        'labelFor' => 'inviteText',
                        'boxName' => 'inviteTextHelpBox'
                    ])
                    @include('survey.partials.editText', ['name' => 'inviteText', 'value' => $survey->inviteText])
                </div>

                <div class="form-group">
                    @include('help.descriptionText', [
                        'showLabel' => true,
                        'labelText' => Lang::get('surveys.questionInfoText'),
                        'labelFor' => 'questionInfo',
                        'boxName' => 'questionInfoHelpBox'
                    ])
                    @include('survey.partials.editText', ['name' => 'questionInfo', 'value' => $survey->questionInfoText])
                </div>

                <div class="form-group">
                    @include('help.descriptionText', [
                        'showLabel' => true,
                        'labelText' => Lang::get('surveys.thankYouText'),
                        'labelFor' => 'thankYou',
                        'boxName' => 'thankYouHelpBox'
                    ])
                    @include('survey.partials.editText', ['name' => 'thankYou', 'value' => $survey->thankYouText])
                </div>
            @overwrite
            @include('survey.partials.showHideBox', ['boxName' => 'textsBox', 'title' => Lang::get('surveys.texts')])

            <h3>{{ Lang::get('questions.categories') }}</h3>
            @foreach ($survey->categories as $category)
                <h4>{{ $category->category->title }}</h4>

                <ul>
                    @foreach ($category->questions as $question)
                        <li>{{ $question->question->text }}</li>
                    @endforeach
                </ul>
            @endforeach

            <h3>{{ Lang::get('surveys.participants') }}</h3>
            @foreach ($survey->candidates as $candidate)
                <h4>{{ $candidate->recipient->name }} ({{ $candidate->recipient->mail }})</h4>
                <ul>
                    <?php
                        $candidateRecipients = $candidate
                            ->invited()
                            ->where('recipientId', '!=', $candidate->recipientId)
                            ->get();
                    ?>
                    @foreach ($candidateRecipients as $recipient)
                        <li>{{ $recipient->recipient->name }} ({{ $recipient->recipient->mail }})</li>
                    @endforeach
                </ul>
            @endforeach

            <button type="submit" class="btn btn-primary btn-lg">{{ Lang::get('buttons.create') }}</button>
        {!! Form::close() !!}
    </div>

    <link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">
    <script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
    <script type="text/javascript">
        function toggleShowHideBox(boxName) {
            var box = $("#" + boxName);
            box.toggle();
        }
    </script>
@endsection

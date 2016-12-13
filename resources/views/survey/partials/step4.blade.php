<h2>{{ Lang::get('surveys.step5InfoText') }}</h2>
@include('shared.dateTimePicker', [
    'name' => 'startDate',
    'label' => Lang::get('surveys.startDate')
])

@include('shared.dateTimePicker', [
    'name' => 'endDate',
    'label' => Lang::get('surveys.endDate')
])

<div class="form-group">
    @include('help.descriptionText', [
        'showLabel' => true,
        'labelText' => Lang::get('surveys.description'),
        'labelFor' => 'description',
        'boxName' => 'descriptionHelpBox'
    ])
    <textarea id="description"
              name="description" class="form-control" rows="5" style="max-width: 40%"></textarea>
</div>

<div class="form-group" id="individualInviteTextBox">
    @include('help.descriptionText', [
        'showLabel' => true,
        'labelText' => Lang::get('surveys.inviteTextLabel'),
        'labelFor' => 'inviteText',
        'boxName' => 'inviteTextHelpBox'
    ])
    <textarea id="individualInviteText"
              name="individualInviteText" class="form-control" rows="5" style="max-width: 40%"></textarea>
</div>

<div class="form-group">
    @include('help.descriptionText', [
        'showLabel' => true,
        'labelText' => Lang::get('surveys.questionInfoText'),
        'labelFor' => 'questionInfo',
        'boxName' => 'questionInfoHelpBox'
    ])
    <textarea id="questionInfo" name="questionInfo" class="form-control" rows="5" style="max-width: 40%"></textarea>
</div>

<div class="form-group">
    @include('help.descriptionText', [
        'showLabel' => true,
        'labelText' => Lang::get('surveys.thankYouText'),
        'labelFor' => 'thankYou',
        'boxName' => 'thankYouHelpBox'
    ])
    <textarea id="thankYou" name="thankYou" class="form-control" rows="5" style="max-width: 40%"></textarea>
</div>

<button id="step4NextBtn" class="btn btn-primary nextBtn btn-lg pull-right" type="button">{{ Lang::get('buttons.next') }}</button>

<script type="text/javascript" src="{{ asset('js/survey.step4.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#startDatePicker').on('dp.change', function(e) {
            var endDatePicker = $('#endDatePicker').data("DateTimePicker");
            var minDate = e.date;

            endDatePicker.minDate(minDate);

            if (endDatePicker.date() != null) {
                if (endDatePicker.date().isBefore(minDate)) {
                    endDatePicker.date(minDate)
                }
            }
        });
    });
</script>
<?php
    $languages = ['en', 'sv', 'fi'];
    $defaultTexts = [];

    foreach ($languages as $lang) {
        $typeTexts = [];

        foreach (\App\Models\DefaultText::defaultInformations(Auth::user()) as $surveyType) {
            $texts = [];

            foreach ($surveyType->texts as $defaultText) {
                $getText = $defaultText->getText;
                $text = $getText($surveyType->id, $lang);
                array_push($texts, $text->text);
            }

            $typeTexts[$surveyType->id] = $texts;
        }

        $defaultTexts[$lang] = $typeTexts;
    }
?>
<script type="text/javascript">
    SurveyStep4.setDefaultInformations({!! json_encode($defaultTexts) !!});
</script>

<link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">
<script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>

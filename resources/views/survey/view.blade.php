@extends('layouts.default')
@section('content')
<div class="col-md-10">
    <div class="mainBox">
        @if ($survey->endDatePassed())
            <div class="alert alert-warning" role="alert">
                <h3 style="margin-top: 0px;">{{ Lang::get('surveys.endDatePassedHeader') }}</h3>
                {{ Lang::get('surveys.endDatePassedContent') }}
            </div>
        @endif

        <?php
            $bouncedEmails = $survey->recipients()->where('bounced', '=', true);
        ?>

        @if ($bouncedEmails->count() > 0)
            <div class="alert alert-warning" role="alert">
                <h3 style="margin-top: 0px;">{{ Lang::get('surveys.emailBouncedHeader') }}</h3>
                @foreach ($bouncedEmails->get() as $recipient)
                    <b>{{ $recipient->recipient->name }} ({{ $recipient->recipient->mail }})</b>
                    <br>
                @endforeach
            </div>
        @endif

        <h2>
            {{ Lang::get('surveys.survey') }}: {{ $survey->name }}
            ({{ $survey->typeName() }})
        </h2>
        @if (!$survey->endDatePassed())
            {{ sprintf(Lang::get('surveys.expireText'), $survey->endDate->format('Y-m-d H:i'), $survey->endDate->diffInDays()) }}
        @else
            {{ sprintf(Lang::get('surveys.expiredText'), $survey->endDate->format('Y-m-d H:i')) }}
        @endif
        @if (\App\SurveyTypes::isGroupLike($survey->type))
            <br>
            {{ Lang::get('surveys.targetGroupInfo') }}: {{ $survey->targetGroup->name }}.
        @endif

        @if ($survey->compareAgainstSurvey != null)
            <br>
            {{ Lang::get('surveys.compareAgainstSurveyText') }}
            <a href="{{ action('SurveyController@show', ['id' => $survey->compareAgainstSurvey->id]) }}">{{ $survey->compareAgainstSurvey->name }}</a>.
        @endif

        @if ($survey->type == \App\SurveyTypes::Progress && $survey->compareAgainstSurvey == null)
            <br>
            <a href="{{ action('SurveyController@createComparisonView', ['id' => $survey->id]) }}">{{ Lang::get('surveys.createComparisonLinkText') }}</a>
        @endif

        @include('errors.list')

        <h3>{{ Lang::get('surveys.automaticReminders') }}</h3>
        {!! Form::open(['action' => ['SurveyUpdateController@updateAutoReminder', $survey->id], 'method' => 'put'])  !!}
            <div class="checkbox">
                <label>
                    <input name="enableAutoReminding" type="checkbox" {{ $survey->enableAutoReminding ? "checked" : "" }}>
                    {{ Lang::get('surveys.enableAutoReminders') }}
                </label>
            </div>
            <div class="form-group">
                <label>{{ Lang::get('surveys.reminderTime') }}</label>
                <div class="input-group date" id="autoRemindingDatePicker" style="width: 40%;">
                    <input name="autoRemindingDate" id="autoRemindingDate" type="text" class="form-control" />
                    <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">{{ Lang::get('buttons.update') }}</button>
        {!! Form::close() !!}

        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            @include('survey.partials.viewIndividual')
        @elseif (\App\SurveyTypes::isGroupLike($survey->type))
            @include('survey.partials.viewGroup')
        @else
            @include('survey.partials.viewNormal')
        @endif

        @if ($survey->reports()->count() > 0)
            <h3>{{ Lang::get('surveys.reports') }}</h3>
            <ul id="reportsList">
                <?php $i = 0; $max = 3; $level = -1; ?>
                @foreach ($survey->reports()->orderBy('id', 'desc')->get() as $report)
                    @if ($i < $max)
                        <li><a href="{{ action('ReportController@viewReport', $report->id) }}">{{ $report->fileName }}</a></li>
                    @else
                        <li style="display: none" class="old_{{ $level }}"><a href="{{ action('ReportController@viewReport', $report->id) }}">{{ $report->fileName }}</a></li>
                    @endif

                    <?php
                        $i++;
                        if ($i % $max == 0) {
                            $level++;
                        }
                    ?>
                @endforeach
            </ul>

            @if ($i > $max)
                <a class="textButton" onclick="showOlderReports(this)">[{{ Lang::get('surveys.showOlder') }}]</a>
            @endif
        @endif

        <h3>{{ Lang::get('surveys.createReport') }}</h3>
        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            {!! Form::open(['method' => 'get', 'target' => '_blank', 'action' => ['ReportController@showReport', $survey->id]]) !!}
                <input type="hidden" name="recipientId" value="">
                @if ($survey->type == \App\SurveyTypes::Individual)
                    <h4>{{ Lang::get('surveys.include') }}</h4>
                    <input type="hidden" name="includeInGroupReport" id="includeInGroupReport" value="">
                    @foreach ($survey->candidates as $candidate)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox"
                                       autocomplete="off" checked="checked"
                                       value="{{ $candidate->recipientId }}"
                                       class="includeInGroupReport"
                                       onchange="updateIncludeInGroupReport()"> {{ $candidate->recipient->name }}
                            </label>
                        </div>
                    @endforeach
                @endif

                <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.generate') }} {{ Lang::get('surveys.groupReport') }}</button>
            {!! Form::close() !!}
        @else
            @if ($survey->type == \App\SurveyTypes::Normal)
                {!! Form::open(['method' => 'get', 'target' => '_blank', 'action' => ['ReportController@showReport', $survey->id]]) !!}
                    <h4 style="display: inline">{{ Lang::get('surveys.filter') }}</h4>
                    <span class="glyphicon glyphicon-plus textButton" onclick="javascript:toggleFilter()" id="filterButton"></span>
                    <div id="filterBox" style="display: none">
                        <table class="table" style="max-width: 100%">
                            <tr>
                                <th>Information</th>
                                <th>Value</th>
                            </tr>
                            @foreach (\App\ExtraAnswerValue::valuesForSurvey($survey) as $extraQuestion)
                                <tr>
                                    <td>{{ $extraQuestion->label() }}</td>
                                    <td>
                                        @if ($extraQuestion->type() == \App\ExtraAnswerValue::Text || $extraQuestion->type() == \App\ExtraAnswerValue::Date)
                                           <select class="form-control" name="extraQuestion_{{ $extraQuestion->id() }}" autocomplete="off">
                                                <option value="">{{ Lang::get('surveys.selectAll') }}</option>
                                                <?php
                                                    $type = $extraQuestion->type() == \App\ExtraAnswerValue::Text ? 'textValue' : 'dateValue';

                                                    $answers = $survey->extraAnswers()
                                                        ->where('extraQuestionId', '=', $extraQuestion->id())
                                                        ->distinct()
                                                        ->pluck($type);
                                                ?>
                                                @foreach ($answers as $value)
                                                    <option value="{{ $value }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($extraQuestion->type() == \App\ExtraAnswerValue::Options)
                                            <select class="form-control" name="extraQuestion_{{ $extraQuestion->id() }}" autocomplete="off">
                                                <option value="">{{ Lang::get('surveys.selectAll') }}</option>
                                                @foreach ($extraQuestion->options() as $id => $value)
                                                    <option value="{{ $id }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($extraQuestion->type() == \App\ExtraAnswerValue::Hierarchy)
                                            <select class="form-control" name="extraQuestion_{{ $extraQuestion->id() }}" autocomplete="off">
                                                <option value="">{{ Lang::get('surveys.selectAll') }}</option>
                                                @foreach ($extraQuestion->flattenOptions() as $value)
                                                    <option value="{{ $value->value }}">{{ $value->name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <br>
                    <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.generate') }}</button>
                {!! Form::close() !!}
            @else
                @if ($survey->answers()->count() > 0)
                    <a class="btn btn-primary" target="_blank" href="{{ action('ReportController@showReport', $survey->id) }}">
                        {{ Lang::get('buttons.generate') }}
                    </a>
                @endif
            @endif
        @endif

        <h3>{{ Lang::get('surveys.export') }}</h3>
        <a class="btn btn-primary" target="_blank" href="{{ action('SurveyExportController@exportCSV', $survey->id) }}">
            {{ Lang::get('surveys.exportCSV') }}
        </a>

        <a class="btn btn-primary" target="_blank" href="{{ action('SurveyExportController@exportExcel', $survey->id) }}">
            {{ Lang::get('surveys.exportExcel') }}
        </a>

        <br/>
        <br>
        <a href="{{ action('SurveyController@index') }}">{{ Lang::get('buttons.back') }}</a>
    </div>
</div>
<script type="text/javascript">
    //Selects all for reminders
    function selectAllReminders(tableName) {
        var notAnsweredTable = $("#" + tableName);
        var reminders = notAnsweredTable.find("input[name='recipients[]']");

        var numSelected = notAnsweredTable.find("input[name='recipients[]']:checked").length;
        var numNotSelected = reminders.length - numSelected;

        reminders.each(function(i, element) {
            element = $(element);
            element.prop("checked", numNotSelected != 0);
        });
    }

    //Toggles the filter
    function toggleFilter() {
        var filterBox = $("#filterBox");
        var filterButton = $("#filterButton");

        if (filterBox.is(':visible')) {
            filterBox.hide();
            filterButton.addClass("glyphicon-plus");
            filterButton.removeClass("glyphicon-minus");
        } else {
            filterBox.show();
            filterButton.removeClass("glyphicon-plus");
            filterButton.addClass("glyphicon-minus");
        }
    }

    //Filters out the given extra question
    function filterOutExtraQuestion(element) {
        $(element).parent().parent().remove();
    }

    $(function () {
        $('#autoRemindingDatePicker').datetimepicker({
            format: 'YYYY-MM-DD HH:mm',
            useCurrent: false,
            defaultDate: "{{ $survey->autoRemindingDate != null ? $survey->autoRemindingDate->format('Y-m-d H:i') : "" }}",
        });
    });

    var currentReportLevel = 0;
    function showOlderReports(e) {
        var reports = $("#reportsList").find(".old_" + currentReportLevel);

        reports.show();
        currentReportLevel++;

        if ($("#reportsList").find(".old_" + currentReportLevel).length == 0) {
            $(e).hide();
        }
    }

    //Updates which members to include in the group report
    function updateIncludeInGroupReport() {
        var includeInGroupReport = [];
        $(".includeInGroupReport").each(function (i, element) {
            element = $(element);
            if (element.is(':checked')) {
                includeInGroupReport.push(+element.val());
            }
        });

        $("#includeInGroupReport").val(includeInGroupReport.join(','));
    }
</script>

<link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">
<script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
<script type="text/javascript">
    //Displays a confirmation for the given participant
    function deleteParticipant(url, messageType) {
        var message = "";

        if (messageType == 1) {
            message = {!! json_encode(Lang::get('surveys.confirmDeleteParticipant')) !!};
        } else if (messageType == 2) {
            message = {!! json_encode(Lang::get('surveys.confirmDeleteCandidate')) !!}
        }

        if (confirm(message)) {
            document.location = url;
        }
    }
</script>
@stop

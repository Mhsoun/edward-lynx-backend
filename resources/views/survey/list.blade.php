<?php
    if (!isset($isEdwardLynx)) {
        $isEdwardLynx = false;
    }

    $minDate = null;
    $maxDate = null;

    foreach($surveys as $survey) {
        if ($minDate == null || $survey->startDate->lt($minDate)) {
            $minDate = $survey->startDate;
        }

        if ($maxDate == null || $survey->endDate->gt($maxDate)) {
            $maxDate = $survey->endDate;
        }
    }

    if ($minDate == null) {
        $minDate = "";
    }

    if ($maxDate == null) {
        $maxDate = "";
    }
?>

<div id="{{ $tableName }}">
    <h3 style="display: inline">{{ Lang::get('surveys.filter') }}</h3>
    <span class="glyphicon glyphicon-plus textButton" id="{{ $tableName }}FilterButton"></span>

    <div style="display: none" id="{{ $tableName }}FilterBox">
        <input class="search form-control" placeholder="{{ Lang::get('surveys.projectFilterPlaceholder') }}" style="max-width: 40%" />

        @include('shared.dateTimePicker', [
            'name' => $tableName . 'MinDate',
            'label' => Lang::get('surveys.startDate'),
            'value' => $minDate
        ])

        @include('shared.dateTimePicker', [
            'name' => $tableName . 'MaxDate',
            'label' => Lang::get('surveys.endDate'),
            'value' => $maxDate
        ])

        <button class="btn btn-primary" id="{{ $tableName }}FilterDate">{{ Lang::get('surveys.filterDate') }}</button>
    </div>

    <table class="table">
        <thead>
            <th><a class="sort textButton" data-sort="status">{{ Lang::get('surveys.status') }}</a></th>
            <th><a class="sort textButton" data-sort="type">{{ Lang::get('surveys.type') }}</a></th>
            <th><a class="sort textButton" data-sort="name">{{ Lang::get('surveys.name') }}</a></th>
            <th><a class="sort textButton" data-sort="lang">{{ Lang::get('surveys.language') }}</a></th>
            <th><a class="sort textButton" data-sort="numQuestions">{{ Lang::get('surveys.questions') }}</a></th>
            <th><a class="sort textButton" data-sort="numCompleted">{{ Lang::get('surveys.numberOfCompleted') }}</a></th>
            <th><a class="sort textButton" data-sort="startDate">{{ Lang::get('surveys.startDate') }}</a></th>
            <th><a class="sort textButton" data-sort="endDate">{{ Lang::get('surveys.endDate') }}</a></th>
            @if ($isEdwardLynx)
                <th><a class="sort textButton" data-sort="owner">{{ ucfirst(Lang::get('surveys.owner')) }}</a></th>
            @endif
            <th></th>
            <th></th>
        </thead>
        <tbody class="list">
            @foreach($surveys as $survey)
                <?php
                    $hasErrors = $survey->recipients()->where('bounced', '=', true)->count() > 0;
                ?>
                <tr id="survey_{{ $survey->id }}" class="surveyRow {{ $hasErrors ? 'alert alert-warning' : '' }}" data-owner="{{ $survey->ownerId }}">
                    <td>
                        @if ($hasErrors)
                            <span class="glyphicon glyphicon-warning-sign" title="{{ Lang::get('surveys.hasBouncedEmails') }}">
                                <span class="status" style="display: none">error</span>
                            </span>
                        @else
                            <span class="status" style="display: none">no</span>
                        @endif
                    </td>
                    <td class="type">{{ $survey->typeName() }}</td>
                    <td><a class="name" href="{{ action('SurveyController@show', $survey->id) }}">{{ $survey->name }}</a></td>
                    <td class="lang">{{ \App\Languages::name($survey->lang) }}</td>
                    <td class="numQuestions">{{ $survey->questions()->count() }}</td>
                    <td class="numCompleted">{{ $survey->numCompleted }} / {{ $survey->recipients()->count() }}</td>
                    <td class="startDate">{{ $survey->startDate->format('Y-m-d H:i') }}</td>
                    <td class="endDate">{{ $survey->endDate->format('Y-m-d H:i') }}</td>
                    @if ($isEdwardLynx)
                        <td class="owner">{{ \App\Models\User::find($survey->ownerId)->name }}</td>
                    @endif
                    <td>
                        <a class="btn btn-primary btn-xs" href="{{ action('SurveyController@edit', $survey->id) }}">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </a>
                    </td>
                    <td>
                        <a class="btn btn-danger btn-xs" href="javascript:deleteSurvey({{ $survey->id }})">
                            <span class="glyphicon glyphicon-trash"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var dateFormat = "YYYY-MM-DD H:i";
        var options = {
            valueNames: ['status', 'type', 'name', 'lang', 'numQuestions', 'numCompleted', 'startDate', 'endDate', 'owner']
        };

        var list = new List("{{ $tableName }}", options);

        $("#{{ $tableName }}FilterDate").click(function () {
            var minDate = moment($("#{{ $tableName }}MinDate").val() + " 00:00", dateFormat);
            var maxDate = moment($("#{{ $tableName }}MaxDate").val() + " 23:59", dateFormat);

            list.filter(function(item) {
                var startDate = moment(item.values().startDate, dateFormat);
                var endDate = moment(item.values().endDate, dateFormat);
                return startDate.isSameOrAfter(minDate) && endDate.isSameOrBefore(maxDate);
            });

            console.log([minDate, maxDate]);
        });

        $("#{{ $tableName }}FilterButton").click(function (e) {
            var filterButton = $(e.target);
            var filterBox = $("#{{ $tableName }}FilterBox");

            if (filterBox.is(":visible")) {
                filterButton.addClass('glyphicon-plus');
                filterButton.removeClass('glyphicon-minus');
                filterBox.hide();
            } else {
                filterButton.removeClass('glyphicon-plus');
                filterButton.addClass('glyphicon-minus');
                filterBox.show();
            }
        });
    });
</script>

@extends('layouts.default')
@section('content')
    <div class="container">
        <div class="mainBox">
            <h2>{{ Lang::get('performances.headerText') }}</h2>
            @if ($errorMessage != "")
                <div class="alert alert-danger" role="alert">
                    {{ $errorMessage }}
                </div>
            @endif

            {!! Form::open(['action' => 'AdminController@performanceIndex', 'method' => 'get']) !!}
                {{ Lang::get('performances.timePeriod') }}
                <div class="form-group">
                    <label for="startDate">{{ Lang::get('surveys.startDate') }}</label>
                    <div class="input-group date" id="startDatePicker" style="max-width: 40%">
                        <input name="startDate" id="startDate" type="text" class="form-control" value="{{ $startDate }}"/>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="endDate">{{ Lang::get('surveys.endDate') }}</label>
                    <div class="input-group date" id="endDatePicker" style="max-width: 40%">
                        <input name="endDate" id="endDate" type="text" class="form-control" value="{{ $endDate }}" />
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                </div>

                <div class="form-group" style="max-width: 40%">
                    <label>{{ Lang::get('performances.filterText') }}</label>
                    <input type="text" placeholder="{{ Lang::get('surveys.companySearchPlaceholder') }}"
                           class="form-control" autocomplete="off" name="company" id="companySearch"
                           value="{{ $companyFilter }}">
                </div>

                <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.show') }}</button>
            {!! Form::close() !!}

            <h3>{{ Lang::get('performances.summary') }}</h3>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfProjects') }}: {{ $data['global']->numSurveys }}</b>
            <br>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfPeopleInvited') }}: {{ $data['global']->numInvited }}</b>
            <br>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfPeopleAnswered') }}: {{ $data['global']->numAnswered }}</b>
            <br>
            <b>{{ Lang::get('performances.answerRatio') }}: {{ $data['global']->answerRatio }}%</b>
            <br>
            <br>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfQuestions') }}: {{ $data['global']->numQuestions }}</b>

            <h3>360</h3>
            <b>{{ ucfirst(Lang::get('performances.numberOfProjects')) }}: {{ $data['360']->numSurveys }}</b>
            <br>
            <b>{{ Lang::get('performances.numberOfCandidates') }}: {{ $data['360']->numCandidates }}</b>
            <br>
            <b>{{ ucfirst(Lang::get('performances.numberOfPeopleInvited')) }}: {{ $data['360']->numInvited }}</b>
            <br>
            <b>{{ ucfirst(Lang::get('performances.numberOfPeopleAnswered')) }}: {{ $data['360']->numAnswered }}</b>
            <br>
            <b>{{ Lang::get('performances.answerRatio') }}: {{ $data['360']->answerRatio }}%</b>

            <h3>LMTT</h3>
            <b>{{ ucfirst(Lang::get('performances.numberOfProjects')) }}: {{ $data['lmtt']->numSurveys }}</b>
            <br>
            <b>{{ ucfirst(Lang::get('performances.numberOfPeopleInvited')) }}: {{ $data['lmtt']->numInvited }}</b>
            <br>
            <b>{{ ucfirst(Lang::get('performances.numberOfPeopleAnswered')) }}: {{ $data['lmtt']->numAnswered }}</b>
            <br>
            <b>{{ Lang::get('performances.answerRatio') }}: {{ $data['lmtt']->answerRatio }}%</b>


            @if ($showNoneSurveyData)
                <h3>{{ Lang::get('performances.companies') }}</h3>
                <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfCompanies') }}: {{ $data['companies']->num }}</b>
                <br>
                <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfLicensedCompanies') }}: {{ $data['companies']->numLicensed }}</b>
            @endif

            <h3>{{ Lang::get('performances.groups') }}</h3>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfGroups') }}: {{ $data['groups']->num }}</b>
            <br>
            <b>{{ Lang::get('performances.total') }} {{ Lang::get('performances.numberOfGroupMembers') }}: {{ $data['groups']->numMembers }}</b>
        </div>
    </div>

    <script type="text/javascript" src="{{ asset('js/bootstrap3-typeahead.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">
    <script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
    <script type="text/javascript">
        //Load the companies
        var companies = [];

        @foreach ($users as $user)
            companies.push({
                id: "{{ $user->name }}",
                companyId: {{ $user->id }},
                name: "{{ $user->name }} ({{ strtolower(Lang::get('surveys.companyAdded')) }} {{ $user->created_at->toDateString() }})",
            });
        @endforeach

        $("#companySearch").typeahead({
            source: companies,
            updater: function(item) {
                return item.id;
            }
        });

        $(function () {
            var dateFormat = 'YYYY-MM-DD';

            $('#startDatePicker').datetimepicker({
                format: dateFormat,
            });

            $('#endDatePicker').datetimepicker({
                format: dateFormat
            });

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
</script>
@stop
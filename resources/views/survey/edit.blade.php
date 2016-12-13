@extends('layouts.default')
@section('content')
<link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">

<?php
    $activeTab = 'general';
    if (Session::get('activeTab') != null) {
        $activeTab = Session::get('activeTab');
    }
?>

<div class="mainBox">
    <h2>{{ Lang::get('surveys.editSurvey') }} {{ $survey->name }} ({{ $survey->typeName() }})</h2>

    @include('errors.list')
    @if (Session::get('changeText') != null)
        <h4>{{ Session::get('changeText') }}</h4>
    @endif
    <div id="content">
        <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
            <li class=""><a href="#general" data-toggle="tab">{{ Lang::get('surveys.editTabGeneral') }}</a></li>
            <li><a href="#emails" data-toggle="tab">{{ Lang::get('surveys.editTabEmails') }}</a></li>
            <li class=""><a href="#editParticipants" data-toggle="tab">{{ Lang::get('surveys.editTabEditParticipants') }}</a></li>
            @if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress)
                <li class=""><a href="#candidates" data-toggle="tab">{{ Lang::get('surveys.addCandidates') }}</a></li>
            @elseif (\App\SurveyTypes::isGroupLike($survey->type))
                <li><a href="#participants" data-toggle="tab">{{ Lang::get('surveys.addParticipants') }}</a></li>
            @elseif ($survey->type == \App\SurveyTypes::Normal)
                <li class=""><a href="#participants" data-toggle="tab">{{ Lang::get('surveys.addParticipants') }}</a></li>
            @endif
            <li class=""><a href="#questions" data-toggle="tab">{{ Lang::get('surveys.editTabQuestions') }}</a></li>
            <li class=""><a href="#reportTemplate" data-toggle="tab">{{ Lang::get('surveys.editTabReportTemplate') }}</a></li>
        </ul>
        <div class="tab-content">
            @include('survey.editpages.general')
            @include('survey.editpages.emails')
            @include('survey.editpages.participants')

            @if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress)
                @include('survey.editpages.addParticipantsIndividual')
            @elseif (\App\SurveyTypes::isGroupLike($survey->type))
                @include('survey.editpages.addParticipantsGroup')
            @elseif ($survey->type == \App\SurveyTypes::Normal)
                @include('survey.editpages.addParticipantsNormal')
            @endif

            @include('survey.editpages.questions')
            @include('survey.editpages.reportTemplate')
        </div>
    </div>
    <br>
    <a href="{{ action('SurveyController@index') }}">{{ Lang::get('buttons.back') }}</a>
    <script type="text/javascript" src="{{ asset('js/survey.edit.js') }}"></script>
    <script type="text/javascript">
        @if (\App\SurveyTypes::isGroupLike($survey->type))
            EditSurvey.setSurvey({{ $survey->ownerId }}, {{ $survey->targetGroupId }});
            @foreach (\App\Roles::getLMTT() as $role)
                EditSurvey.roles.push({
                    id: {{ $role->id }},
                    name: {!! json_encode($role->name()) !!}
                });
            @endforeach

            @foreach ($ownerRecipients as $recipient)
                EditSurvey.addExistingRecipient({
                    id: {{ $recipient->id }},
                    name: {!! json_encode($recipient->name) !!},
                    email: {!! json_encode($recipient->mail) !!},
                    position: {!! json_encode($recipient->position ?: "") !!}
                });
            @endforeach

            @foreach ($notIncludedMembers as $member)
                EditSurvey.addGroupMember({
                    id: {{ $member->recipient->id }},
                    name: {!! json_encode($member->recipient->name) !!},
                    email: {!! json_encode($member->recipient->mail) !!},
                    position: {!! json_encode($member->recipient->position ?: "") !!}
                });
            @endforeach
        @endif

        $(document).ready(function() {
            $("#tabs").tab();
            $("#tabs a[href=\"#{{ $activeTab }}\"]").tab('show')
        });
    </script>

    <script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/helpers.js') }}"></script>
    <script type="text/javascript">
        //Updates the role for the given recipient
        function updateRole(args, surveyId, invitedById, recipientId) {
            $.ajax({
                url: "/survey/" + surveyId + "/update-changerole",
                method: "put",
                data: {
                    invitedById: invitedById,
                    recipientId: recipientId,
                    roleId: +args.value
                }
            });
        }

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
</div>
@stop

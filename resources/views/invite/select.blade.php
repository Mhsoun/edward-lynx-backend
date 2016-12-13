@extends($isAdmin ? 'layouts.default' : 'layouts.no-nav')

<?php
    $endDatePassed = $survey->endDatePassed($surveyCandidate->recipientId, $surveyCandidate->recipientId);
    $canInvite = true;
    if ($endDatePassed && !$isAdmin) {
        $canInvite = false;
    }

    if (!$isAdmin && $survey->compareAgainstSurvey != null) {
        $canInvite = false;
    }
?>

@section('content')
    <div class="col-md-9">
        @if ($endDatePassed)
            <div class="alert alert-warning" role="alert">
                <h3 style="margin-top: 0px;">{{ Lang::get('surveys.endDatePassedHeader') }}</h3>
                {{ Lang::get('surveys.endDatePassedContent') }}
            </div>
        @endif

        @if ($isAdmin)
            <h3>{{ Lang::get('surveys.inviteHeaderAdmin') }} {{ $parserData["toEvaluateName"] }}</h3>
        @else
            <h3>{{ Lang::get('surveys.inviteHeader') }}</h3>
        @endif

        @if (\App\SurveyTypes::isNewProgress($survey))
            @include('survey.partials.progressProcess', ['currentStep' => 1])
        @endif

        @if ($survey->inviteText != "")
            <p>{!! \App\EmailContentParser::parse($survey->inviteText, $parserData) !!}</p>
        @else
            <p>{!! Lang::get('surveys.inviteText') !!}</p>
        @endif

        @if ($canInvite)
            <div class="form-group">
                <label for="recipientName">{{ Lang::get('surveys.recipientName') }}</label>
                <input type="text" class="form-control" id="recipientName"
                       placeholder="{{ Lang::get('surveys.recipientNamePlaceholder') }}"
                       style="max-width: 60%;">
            </div>

            <div class="form-group">
                <label for="recipientEmail">{{ Lang::get('surveys.recipientEmail') }}</label>
                <input type="email" class="form-control" id="recipientEmail"
                       placeholder="{{ Lang::get('surveys.recipientEmailPlaceholder') }}"
                       style="max-width: 60%;">
            </div>

            <div class="form-group">
                <label for="recipientRole">{{ Lang::get('surveys.recipientRole') }}</label>
                <select id="recipientRole" class="form-control" style="max-width: 60%;">
                    @foreach (\App\Roles::get360() as $role)
                        <option value="{{ $role->id }}">{{ $role->name() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" class="btn btn-default"
                    onclick="javascript:SurveyInvite.addNew()">{{ Lang::get('buttons.invite') }}</button>
        @endif

        <h4>{{ Lang::get('surveys.invited') }}</h4>
        <table id="invitedList" class="table">
            <colgroup>
                @if ($isAdmin)
                    <col />
                    <col width="30%" />
                    <col width="30%" />
                    <col />
                    <col />
                @else
                    <col width="30%" />
                    <col width="30%" />
                    <col />
                @endif
            </colgroup>
            <tr>
            	@if ($isAdmin)
            	    <th>{{ Lang::get('surveys.status') }}</th>
	                <th>{{ Lang::get('surveys.recipientName') }}</th>
	                <th>{{ Lang::get('surveys.recipientEmail') }}</th>
	                <th>{{ Lang::get('surveys.recipientRole') }}</th>
	                <th></th>
            	@else
	                <th>{{ Lang::get('surveys.recipientName') }}</th>
	                <th>{{ Lang::get('surveys.recipientEmail') }}</th>
	                <th>{{ Lang::get('surveys.recipientRole') }}</th>
            	@endif
            </tr>
            @foreach ($invitedRecipients as $recipient)
                <?php
                    $recipientStatus = "";

                    if ($isAdmin) {
	                    if ($recipient->hasAnswered) {
	                        $recipientStatus = "alert alert-success";
	                    } else if ($recipient->bounced) {
	                        $recipientStatus = "alert alert-warning";
	                    }
	                }
                ?>
                <tr class="{{ $recipientStatus }}">
                	@if ($isAdmin)
	                    <td>
	                        @if ($recipient->hasAnswered)
	                            <span class="glyphicon glyphicon-ok">
	                                <span class="status" style="display: none">answered</span>
	                            </span>
	                        @elseif ($recipient->bounced)
	                            <span class="glyphicon glyphicon-warning-sign" title="{{ Lang::get('surveys.emailBounced') }}">
	                                <span class="status" style="display: none">error</span>
	                            </span>
	                        @else
	                            <span class="status" style="display: none">no</span>
	                        @endif
	                    </td>
                    @endif
                    <td>{{ $recipient->recipient->name }}</td>
                    <td>{{ $recipient->recipient->mail }}</td>
                    <td>{{ \App\Roles::name($recipient->roleId) }}</td>
                    @if ($isAdmin)
	                    <td>
	                        @if (!$recipient->hasAnswered)
	                            <button class="btn btn-danger btn-xs" onclick="SurveyInvite.deleteRecipient({{ $recipient->recipientId }}, this)">
	                                <span class="glyphicon glyphicon-trash"></span>
	                            </button>
	                        @endif
	                    </td>
                    @endif
                </tr>
            @endforeach
        </table>

        @if ($isAdmin)
            <a href="{{ action('SurveyController@edit', $survey->id) }}">{{ Lang::get('buttons.back') }}</a>
        @endif
    </div>

    <script type="text/javascript" src="{{ asset('js/survey.invite.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/helpers.js') }}"></script>
    <script type="text/javascript">
        SurveyInvite.setSurveyLink("{{ $link }}");
        @foreach (\App\Roles::get360() as $role)
            SurveyInvite.roles.push({ id: {{ $role->id }}, name: {!! json_encode($role->name()) !!} });
        @endforeach
        SurveyInvite.setIsAdmin({!! json_encode($isAdmin) !!});

        SurveyInvite.languageStrings["recipientName"] = {!! json_encode(Lang::get('surveys.recipientName')) !!};
        SurveyInvite.languageStrings["recipientNamePlaceholder"] = {!! json_encode(Lang::get('surveys.recipientNamePlaceholder')) !!};
        SurveyInvite.languageStrings["recipientEmail"] = {!! json_encode(Lang::get('surveys.recipientEmail')) !!};
        SurveyInvite.languageStrings["recipientEmailPlaceholder"] = {!! json_encode( Lang::get('surveys.recipientEmailPlaceholder')) !!};
        SurveyInvite.languageStrings["recipientRole"] = {!! json_encode(Lang::get('surveys.recipientRole')) !!};
    </script>
@endsection

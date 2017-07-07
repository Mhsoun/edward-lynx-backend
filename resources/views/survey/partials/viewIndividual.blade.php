<h3>{{ Lang::get('surveys.candidates') }}</h3>
{!! Form::open(['action' => ['SurveyController@sendInviteOthersReminders', $survey->id], 'method' => 'put']) !!}
    <table class="table" id="candidatesTable">
        <col style="width: 18em">
        <col style="width: 15em">
        <tr>
            <th>{{ Lang::get('surveys.recipientName') }}</th>
            <th>{{ Lang::get('surveys.recipientEmail') }}</th>
            <th>{{ Lang::get('surveys.numberOfCompleted') }}</th>
            <th><a href="javascript:selectAllInviteOthersReminders('candidatesTable')">{{ Lang::get('surveys.sendReminderHeader') }}</a></th>
            <th>{{ Lang::get('surveys.link') }}</th>
            <th></th>
        </tr>
        @foreach ($survey->candidates as $candidate)
            @if ($candidate->exists())
                <?php $invitedAndAnswered = $candidate->invitedAndAnswered(); ?>
                <?php
                    $viewLink = action('SurveyController@showCandidate', [
                        'id' => $survey->id,
                        'candidateId' => $candidate->recipientId,
                    ]);

                    $deleteLink = action('SurveyUpdateController@updateDeleteParticipant', [
                        'id' => $survey->id,
                        'recipientId' => $candidate->recipientId,
                        'invitedById' => $candidate->recipientId
                    ]);

                    $reportLink = action('ReportController@showReport', [
                        'id' => $survey->id,
                        'recipientId' => $candidate->recipientId
                    ]);

                    $userReportLink = '';
                    if ($candidate->userReport() != null) {
                        $userReportLink = action('ReportController@showUserReport', [
                            'link' => $candidate->userReport()->link
                        ]);
                    }

                    $statusText = "";
                    $hasBounced = $candidate->invited()
                        ->where('bounced', '=', true)
                        ->count() > 0;

                    if ($hasBounced) {
                        $statusText = 'alert-warning';
                    }
                    
                    $email = $candidate->recipient->mail ? $candidate->recipient->mail : $candidate->recipient->email;
                ?>

                <tr class="{{ $statusText }}">
                    <td><a href="{{ $viewLink }}">{{ $candidate->recipient->name }}</a></td>
                    <td>{{ $email }}</td>
                    <td>{{ $invitedAndAnswered->answered }}/{{ $invitedAndAnswered->invited  }}</td>
                    <td><input type="checkbox" name="candidateReminderIds[]" value="{{ $candidate->recipientId }}"></td>
                    <td>
                        <a  title="{{ Lang::get('surveys.inviteLink') }}"
                            target="_blank"
                            href="{{ action('InviteController@show', ['link' => $candidate->link, 'admin' => 'yes']) }}">
                            {{ Lang::get('surveys.link') }}
                        </a>
                    </td>
                    <td>
                        @if ($candidate->hasAnswered())
                            <a class="btn btn-primary btn-xs" target="_blank" title="{{ Lang::get('surveys.createReport') }}" href="{{ $reportLink }}">
                                <span class="fa fa-pie-chart"></span>
                            </a>
                        @endif

                        @if ($userReportLink != '')
                            <a class="btn btn-primary btn-xs" target="_blank" title="{{ Lang::get('surveys.createUserReport') }}" href="{{ $userReportLink }}">
                                <span class="fa userAndPie"></span>
                            </a>
                        @endif

                        <a href="#share-report-modal" class="btn btn-default btn-xs" title="Share Report" data-toggle="modal" data-target="#share-report-modal"><i class="glyphicon glyphicon-list-alt"></i></a>
                        <a class="btn btn-danger btn-xs" href="javascript:deleteParticipant('{{ $deleteLink }}', 2)">
                            <span class="glyphicon glyphicon-trash"></span>
                        </a>
                    </td>
                </tr>
            @endif
        @endforeach
    </table>
    <button class="btn btn-primary pull-right" type="submit">{{ Lang::get('buttons.send') }}</button>
{!! Form::close() !!}

<div class="modal fade modal-loading" tabindex="-1" role="dialog" id="share-report-modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Share Report</h4>
            </div>
            <div class="modal-body">
                <i class="glyphicon glyphicon-refresh"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    //Selects all for reminders
    function selectAllInviteOthersReminders(tableName) {
        var table = $("#" + tableName);
        var reminders = table.find("input[name='candidateReminderIds[]']");

        var numSelected = table.find("input[name='candidateReminderIds[]']:checked").length;
        var numNotSelected = reminders.length - numSelected;

        reminders.each(function(i, element) {
            element = $(element);
            element.prop("checked", numNotSelected != 0);
        });
    }
</script>
<style>
@keyframes rotate {
  from {
    -webkit-transform: rotate(0deg);
  }
  to { 
    -webkit-transform: rotate(360deg);
  }
}
#share-report-modal.modal-loading .modal-body {
    text-align: center;
}
#share-report-modal.modal-loading .modal-body .glyphicon-refresh {
    animation: rotate 750ms infinite;
}
#share-report-modal.modal-loading .modal-footer {
    display: none;
}
</style>
<table id="{{ $tableName }}" class="table">
    <col>
    <col style="width: 15em">
    <col style="width: 15em">
    <thead>
        <th><a class="sort textButton" data-sort="status">{{ Lang::get('surveys.status') }}</a></th>
        <th><a class="sort textButton" data-sort="name">{{ Lang::get('surveys.recipientName') }}</a></th>
        <th><a class="sort textButton" data-sort="email">{{ Lang::get('surveys.recipientEmail') }}</a></th>
        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            <th><a class="sort textButton" data-sort="role">{{ Lang::get('surveys.recipientRole') }}</a></th>
        @endif
        <th>{{ Lang::get('surveys.link') }}</th>
        <th><a href="javascript:selectAllReminders('{{ $tableName }}')">{{ Lang::get('surveys.sendReminderHeader') }}</a></th>
        <th><a class="sort textButton" data-sort="lastReminder">{{ Lang::get('surveys.lastReminder') }}</a></th>
        <th></th>
    </thead>
    <tbody class="list">
        <?php
            $recipients = $recipients->sort(function($x, $y) {
                return strcmp($x->roleId, $y->roleId)
                       ?: strcmp($x->recipientId, $y->recipientId);
            });
        ?>
        @foreach ($recipients as $recipient)
            <?php
                $statusText = "";

                if ($recipient->bounced) {
                    $statusText = 'alert-warning';
                } else if ($recipient->hasAnswered) {
                    $statusText = 'alert-success';
                }

                $answersLink = "";

                $showAnswerParameters = [];
                if (\App\SurveyTypes::isIndividualLike($survey->type)) {
                    $showAnswerParameters = [
                        'recipientId' => $recipient->recipientId,
                        'candidateId' => $recipient->invitedById,
                    ];
                } else {
                    $showAnswerParameters = ['recipientId' => $recipient->recipientId];
                }

                $answersLink = action(
                    'SurveyController@showAnswers',
                    array_merge(['id' => $survey->id], $showAnswerParameters));

                $deleteLink = action('SurveyUpdateController@updateDeleteParticipant', [
                    'id' => $survey->id,
                    'recipientId' => $recipient->recipientId,
                    'invitedById' => $recipient->invitedById
                ]);
                    
                $email = $recipient->recipient->mail ? $recipient->recipient->mail : $recipient->recipient->email;
            ?>
            <tr title="{{ $recipient->bounced ? Lang::get('surveys.emailBounced') : '' }}" class="{{ $statusText }}">
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
                <td class="name">{{ $recipient->recipient->name }}</td>
                <td class="email">{{ $email }}</td>
                @if (\App\SurveyTypes::isIndividualLike($survey->type))
                    <td class="role">{{ \App\Roles::name($recipient->roleId)  }}</td>
                @endif
                @if (!$recipient->hasAnswered)
                    <td><a target="_blank" href="{{ action('AnswerController@show', $recipient->link) }}">{{ Lang::get('surveys.link') }}</a></td>
                    <td><input type="checkbox" name="recipients[]" value="{{ $recipient->invitedById . ':' . $recipient->recipientId }}"></td>
                @else
                    <td></td>
                    <td></td>
                @endif
                <td class="lastReminder">{{ $recipient->lastReminder != null ? $recipient->lastReminder->format('Y-m-d H:i') : "" }}</td>
                <td>
                    @if ($recipient->hasAnswered)
                        <a></a>
                        <a class="btn btn-primary btn-xs" title="{{ Lang::get('surveys.showAnswersText') }}" href="{{ $answersLink }}">
                            <span class="fa fa-list"></span>
                        </a>
                    @endif

                    <a class="btn btn-danger btn-xs" href="javascript:deleteParticipant('{{ $deleteLink }}', true)">
                        <span class="glyphicon glyphicon-trash"></span>
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<script src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.1.1/list.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var options = {
            valueNames: ['status', 'name', 'email', 'role', 'lastReminder']
        };

        new List("{{ $tableName }}", options);
    });

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

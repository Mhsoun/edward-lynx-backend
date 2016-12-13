<div class="tab-pane" id="editParticipants">
    <h3>{{ Lang::get('surveys.participants') }}</h3>
    {!! Form::open(['action' => ['SurveyUpdateController@updateEditParticipants', $survey->id], 'method' => 'put']) !!}
        <table class="table" style="max-width: 80%">
            <tr>
                <th>{{ Lang::get('surveys.recipientName') }}</th>
                <th>{{ Lang::get('surveys.recipientEmail') }}</th>
                @if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress)
                    <th>{{ Lang::get('surveys.invitedBy') }}</th>
                @endif
                @if ($survey->type != \App\SurveyTypes::Normal)
                    <th>{{ Lang::get('surveys.recipientRole') }}</th>
                @endif
                <th>{{ Lang::get('buttons.delete') }}</th>
            </tr>
            <?php
                $recipients = $survey->recipients->sort(function($x, $y) {
                    return strcmp($x->invitedById, $y->invitedById)
                           ?: strcmp($x->roleId, $y->roleId)
                           ?: strcmp($x->recipientId, $y->recipientId);
                });
            ?>
            @foreach ($recipients as $recipient)
                <?php
                    $show = true;

                    if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
                        if ($recipient->invitedByObj == null) {
                            $show = false;
                        }
                    }
                ?>
                @if ($show)
                    <tr>
                        <td>
                            <input type="text" class="form-control"
                                   name="recipient_{{ $recipient->invitedById }}_{{ $recipient->recipientId }}_name"
                                   value="{{ $recipient->recipient->name }}">
                        </td>
                        <td>
                            <input type="text" class="form-control"
                                   name="recipient_{{ $recipient->invitedById }}_{{ $recipient->recipientId }}_email"
                                   value="{{ $recipient->recipient->mail }}">
                        </td>
                        @if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress)
                            <td>
                                {{ $recipient->invitedByObj->name }}
                            </td>
                        @endif
                            @if ($survey->type != \App\SurveyTypes::Normal)
                                <td>
                                    @if ($recipient->roleId == \App\Roles::selfRoleId())
                                        {{ \App\Roles::name($recipient->roleId) }}
                                    @else
                                        <?php
                                            $roles = [];

                                            if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
                                                $roles = \App\Roles::get360();
                                            } else if (\App\SurveyTypes::isGroupLike($survey->type)) {
                                                $roles = \App\Roles::getLMTT();
                                            }
                                        ?>

                                        <select class="form-control"
                                                autocomplete="off"
                                                onchange="updateRole(this, {{ $survey->id }}, {{ $recipient->invitedById }}, {{ $recipient->recipientId }})">
                                            @foreach ($roles as $role)
                                                @if ($role->id == $recipient->roleId)
                                                    <option selected="selected" value="{{ $role->id }}">{{ $role->name() }}</option>
                                                @else
                                                    <option value="{{ $role->id }}">{{ $role->name() }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                            @endif
                        <td>
                            <?php
                                $params = ['id' => $survey->id, 'recipientId' => $recipient->recipientId];
                                $isCandidate = false;

                                if ($survey->type == \App\SurveyTypes::Individual || $survey->type == \App\SurveyTypes::Progress) {
                                    $params['invitedById'] = $recipient->invitedById;
                                    $isCandidate = $recipient->invitedById == $recipient->recipientId;
                                }
                            ?>
                            <a class="btn btn-danger btn-xs"
                                href="javascript:deleteParticipant('{{ action('SurveyUpdateController@updateDeleteParticipant', $params) }}', {{ $isCandidate ? 2 : 1}})">
                                <span class="glyphicon glyphicon-trash"></span>
                            </a>
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>

        <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.save') }}</button>
    {!! Form::close() !!}
</div>

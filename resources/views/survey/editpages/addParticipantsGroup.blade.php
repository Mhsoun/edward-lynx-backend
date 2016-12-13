<div class="tab-pane" id="participants">
    @include('survey.partials.addRecipient', [
        'newRecipientHeader' => Lang::get('groups.newMemberCreateNew'),
        'existingRecipients' => $ownerRecipients,
        'existingRecipientOnClick' => 'EditSurvey.createMemberFromExisting()',
        'newRecipientOnClick' => 'EditSurvey.createMember()'
    ])

    <h4>{{ Lang::get('surveys.participants') }}</h4>
    {!! Form::open(['action' => ['SurveyUpdateController@updateAddParticipants', $survey->id], 'method' => 'put']) !!}
        <table id="selectRecipientsTable" class="table">
            <tr class="tableHeader">
                <th><a href="javascript:EditSurvey.selectAllMembers()">{{ Lang::get('surveys.groupInclude') }}</a></th>
                <th>{{ Lang::get('surveys.groupMemberName') }}</th>
                <th>{{ Lang::get('surveys.groupMemberEmail') }}</th>
                <th>{{ Lang::get('surveys.groupMemberPosition') }}</th>
                <th>{{ Lang::get('surveys.groupMemberRole') }}</th>
                <th>{{ Lang::get('groups.memberDeleteHeader') }}</th>
            </tr>
            @foreach ($notIncludedMembers as $member)
                <tr id="member_{{ $member->member }}">
                    <td><input type="checkbox" name="newParticipants[]" value="{{ $member->memberId }}"></td>
                    <td>{{ $member->recipient->name }}</td>
                    <td>{{ $member->recipient->mail }}</td>
                    <td>{{ $member->recipient->position }}</td>
                    <td>
                        <select onchange="EditSurvey.updateRole(this, {{ $member->memberId }})" class="form-control" style="max-width: 60%">
                            @foreach (\App\Roles::getLMTT() as $role)
                                @if ($role->id == $member->roleId)
                                    <option selected="selected" value="{{ $role->id }}">{{ $role->name() }}</option>
                                @else
                                    <option value="{{ $role->id }}">{{ $role->name() }}</option>
                                @endif
                            @endforeach
                        </select>
                    </td>
                    <td><a class='textButton' onclick="EditSurvey.deleteMemberById({{ $member->memberId }})"><span class='glyphicon glyphicon-trash' /></a></td>
                </tr>
            @endforeach
        </table>

        <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.invite') }}</button>
    {!! Form::close() !!}
</div>

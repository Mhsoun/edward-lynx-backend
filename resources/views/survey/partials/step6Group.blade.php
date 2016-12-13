<div id="targetGroupBox" style="display: none">
    <h3>{{ Lang::get('surveys.targetGroup') }}</h3>
    {{ Lang::get('surveys.targetGroupInfo') }} '<span id="selectedGroupNameHeader"></span>'.
    {{ Lang::get('surveys.targetGroupActionText') }} <b><a class="textButton" href="javascript:SurveyStep6.Group.toggleEdit()">{{ strtolower(Lang::get('buttons.edit')) }}</a></b>,
    <b><a class="textButton" href="javascript:SurveyStep6.Group.delete()">{{ strtolower(Lang::get('buttons.delete')) }}</a></b> or
    <b><a class="textButton" href="javascript:SurveyStep6.Group.reselectGroup()">{{ Lang::get('surveys.selectAnotherGroup') }}</a></b>?
</div>

<div id="selectGroupStep">
    <h3>{{ Lang::get('surveys.toEvaluateGroup') }}</h3>
    <h4>{{ Lang::get('groups.createNew') }}</h4>

    <div class="form-group">
        <label for="newGroupName">{{ Lang::get('groups.groupName') }}</label>
        <input type="text" class="form-control" id="newGroupName"
               placeholder="{{ Lang::get('groups.groupNamePlaceholder') }}"
               style="max-width: 40%">
    </div>

    <button type="button" class="btn btn-primary" onclick="javascript:SurveyStep6.Group.create()">
        {{ Lang::get('buttons.create') }}
    </button>

    <h4>{{ Lang::get('groups.fromExisting') }}</h4>
    <select id="selectGroup" class="form-control" style="max-width: 40%">
        <option value="noSelect">{{ Lang::get('surveys.selectGroup') }}</option>
    </select>
    <br>
    <button type="button" class="btn btn-primary" onclick="javascript:SurveyStep6.Group.select()">
        {{ Lang::get('buttons.select') }}
    </button>
</div>

<div id="selectRecipientsStep" style="display: none">
    <div id="editGroupBox" style="display: none">
        <div class="form-group">
                <label>{{ Lang::get('groups.groupName') }}</label>
            <input type="text" id="editGroupName" class="form-control">
        </div>

        <button type="button" class="btn btn-success" onclick="SurveyStep6.Group.saveGroup()">{{ Lang::get('buttons.save') }}</button>
        <button type="button" class="btn btn-danger" onclick="SurveyStep6.Group.restore()">{{ Lang::get('buttons.discardChanges') }}</button>
    </div>

    <h3>{{ Lang::get('surveys.selectRecipients') }}</h3>
    <div id="selectNewGroupMemberBox" style="{{ $recipients->isEmpty() ? 'display: none;' : '' }}">
        <h4 style="margin-top: 0;">{{ Lang::get('groups.newMemberSelectForExisiting') }}</h4>
        <div id="form-group">
            <select class="form-control" id="selectNewGroupMember" style="max-width: 40%">
                @foreach ($recipients as $recipient)
                    <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->mail }})</option>
                @endforeach
            </select>
        </div>
        <br>
        <button type="button" class="btn btn-primary" onclick="SurveyStep6.Group.createMemberFromExisting()">{{ Lang::get('buttons.add') }}</button>
    </div>

    <br>

    <div>
        <h4 style="margin-top: 0;">{{ Lang::get('surveys.importFromCSV') }}</h4>
        <div class="form-group">
            <input type="file" id="importFile" name="importFile[]" multiple="multiple" />
        </div>

        <button type="button" id="importCSVButton" class="btn btn-primary">{{ Lang::get('buttons.import') }}</button>
    </div>

    <br>

    <div>
        <h4 style="margin-top: 0;">{{ Lang::get('groups.newMemberCreateNew') }}</h4>
        <div class="form-group">
            <label>{{ Lang::get('surveys.recipientName')  }}</label>
            <input type="text" class="form-control" id="newGroupMemberName"
                   placeholder="{{ Lang::get('surveys.recipientNamePlaceholder') }}" style="max-width: 40%">
        </div>
        <div class="form-group">
            <label>{{ Lang::get('surveys.recipientEmail') }}</label>
            <input type="email" class="form-control" id="newGroupMemberEmail"
                   placeholder="{{ Lang::get('surveys.recipientEmailPlaceholder') }}" style="max-width: 40%">
        </div>
        <div class="form-group">
            <label>{{ Lang::get('surveys.recipientPosition') }}</label>
            <input type="text" class="form-control" id="newGroupMemberPosition"
                   placeholder="{{ Lang::get('surveys.recipientPositionPlaceholder') }}" style="max-width: 40%">
        </div>

        <button type="button" class="btn btn-primary" onclick="SurveyStep6.Group.createMember()">{{ Lang::get('buttons.create') }}</button>
    </div>

    <h4>{{ Lang::get('surveys.participants') }}</h4>
    <table id="selectRecipientsTable" class="table">
        <tr class="tableHeader">
            <th><a href="javascript:SurveyStep6.Group.selectAllMembers()">{{ Lang::get('surveys.groupInclude') }}</a></th>
            <th>{{ Lang::get('surveys.groupMemberName') }}</th>
            <th>{{ Lang::get('surveys.groupMemberEmail') }}</th>
            <th>{{ Lang::get('surveys.groupMemberPosition') }}</th>
            <th>{{ Lang::get('surveys.groupMemberRole') }}</th>
            <th>{{ Lang::get('groups.memberDeleteHeader') }}</th>
        </tr>
    </table>

    <button type="button" id="selectRolesButton"
            class="btn btn-lg btn-primary pull-right"
            onclick="javascript:SurveyStep6.Group.selectRoles()">{{ Lang::get('buttons.next') }}</button>
</div>

<div id="selectRolesStep" style="display: none">
    <h3 id="selectGroupRoleHeader">{{ Lang::get('surveys.roleToEvaluate') }}</h3>
    <select id="selectGroupRole" class="form-control" style="max-width: 40%">
        @foreach (\App\Roles::getLMTT() as $role)
            <option value="{{ $role->id }}">{{ $role->name() }}</option>
        @endforeach
    </select>
    <br>
    <button type="button" id="selectRoleGroupButton"
            class="btn btn-lg btn-primary"
            onclick="javascript:SurveyStep6.Group.selectGroupRole()">{{ Lang::get('buttons.select') }}</button>

    <button type="button" id="doneSelectingRolesButton"
            class="btn btn-lg btn-success"
            onclick="javascript:SurveyStep6.Group.doneSelecting()">{{ Lang::get('buttons.save') }}</button>
</div>

<div id="groupResults" style="display: none">
    <p>{{ Lang::get('surveys.step3ConfirmationText') }}</p>
    <button class="btn btn-primary" type="button" onclick="javascript:SurveyStep6.Group.reselectRoles()">{{ Lang::get('buttons.back') }}</button>
    <div id="groupResultsContent"></div>
</div>

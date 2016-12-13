<h2>{{ Lang::get('surveys.addParticipants') }}</h2>
<div id="selectNormal">
    <div>
        <h4>{{ Lang::get('groups.newMemberCreateNew') }}</h4>
        <div class="form-group">
            <label for="newNormalRecipientName">{{ Lang::get('surveys.recipientName') }}</label>
            <input type="text" class="form-control" id="newNormalRecipientName"
                   placeholder="{{ Lang::get('surveys.recipientNamePlaceholder') }}" style="max-width: 40%">
        </div>

        <div class="form-group">
            <label for="newNormalRecipientEmail">{{ Lang::get('surveys.recipientEmail') }}</label>
            <input type="email" class="form-control" id="newNormalRecipientEmail"
                   placeholder="{{ Lang::get('surveys.recipientEmailPlaceholder') }}" style="max-width: 40%">
        </div>

        <button type="button" class="btn btn-default" id="addNewButton" onclick="javascript:SurveyStep6.Normal.addNew()">{{ Lang::get('buttons.add') }}</button>
    </div>

    <div>
        <h4>{{ Lang::get('surveys.fromExisting') }}</h4>
        <div class="form-group">
            <select id="normalExistingRecipients" class="form-control" style="max-width: 40%">
                @foreach($recipients as $recipient)
                    <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->mail }})</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn btn-default" id="addExistingButton" onclick="javascript:SurveyStep6.Normal.addExisting()">{{ Lang::get('buttons.add') }}</button>
    </div>

    <div>
        <h4>{{ Lang::get('surveys.importFromCSV') }}</h4>
        <div class="form-group">
            <input type="file" id="importNormalParticipantsFile" name="importNormalParticipantsFile[]" multiple="multiple" />
        </div>

        <button type="button" id="importParticipantsCSVButton" class="btn btn-default">{{ Lang::get('buttons.import') }}</button>
    </div>

    <h3>{{ Lang::get('surveys.participants') }}</h3>
    <table class="table" id="normalParticipantsTable" style="max-width: 70%">
        <tr>
            <th>{{ Lang::get('surveys.recipientName') }}</th>
            <th>{{ Lang::get('surveys.recipientEmail') }}</th>
            <th></th>
        </tr>
    </table>
</div>

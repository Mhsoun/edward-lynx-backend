<h2>{{ Lang::get('surveys.step3InfoIndividualText') }}</h2>
<div id="selectIndividual">
    <div class="row">
        <div class="col-md-6">
            <h4>{{ Lang::get('surveys.createNewRecipient') }}</h4>
            <div class="form-group">
                <label for="newRecipientName">{{ Lang::get('surveys.recipientName') }}</label>
                <input type="text" class="form-control" id="newRecipientName"
                       placeholder="{{ Lang::get('surveys.recipientNamePlaceholder') }}" style="width: 80%">
            </div>

            <div class="form-group">
                <label for="newRecipientEmail">{{ Lang::get('surveys.recipientEmail') }}</label>
                <input type="email" class="form-control" id="newRecipientEmail"
                       placeholder="{{ Lang::get('surveys.recipientEmailPlaceholder') }}" style="width: 80%">
            </div>

            <div class="form-group">
                <label for="newRecipientPosition">{{ Lang::get('surveys.recipientPosition') }}</label>
                <input type="text" class="form-control" id="newRecipientPosition"
                       placeholder="{{ Lang::get('surveys.recipientPositionPlaceholder') }}" style="width: 80%">
            </div>

            <div id="newRecipientEndDateBox">
                @include('shared.dateTimePicker', [
    				'name' => 'newRecipientEndDate',
    				'label' => Lang::get('surveys.endDate'),
                    'width' => '80%'
    			])

                @include('shared.dateTimePicker', [
    				'name' => 'newRecipientEndDateRecipients',
    				'label' => Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants'),
                    'width' => '80%'
    			])
            </div>

            <button type="button" class="btn btn-primary" id="addNewButton" onclick="javascript:SurveyStep6.Individual.addNew()">
                {{ Lang::get('buttons.add') }}
            </button>
        </div>

        <div class="col-md-6">
            <h4>{{ Lang::get('surveys.fromExisting') }}</h4>
            <div class="form-group">
                <select id="existingRecipients" class="form-control" style="width: 80%">
                    @foreach($recipients as $recipient)
                        <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->mail }})</option>
                    @endforeach
                </select>
            </div>

            <div id="existingRecipientEndDateBox">
                @include('shared.dateTimePicker', [
                    'name' => 'existingRecipientEndDate',
                    'label' => Lang::get('surveys.endDate'),
                    'width' => '80%'
                ])

                @include('shared.dateTimePicker', [
    				'name' => 'existingRecipientEndDateRecipients',
    				'label' => Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants'),
                    'width' => '80%'
    			])
            </div>

            <button type="button" class="btn btn-primary" id="addExistingButton" onclick="javascript:SurveyStep6.Individual.addExisting()">
                {{ Lang::get('buttons.add') }}
            </button>
        </div>

        <div class="col-md-6">
            <h4>{{ Lang::get('surveys.importFromCSV') }}</h4>
            <div class="form-group">
                <input type="file" id="importCandidateFile" name="importCandidateFile[]" multiple="multiple" />
            </div>

            <button type="button" id="importCandidateCSVButton" class="btn btn-primary">{{ Lang::get('buttons.import') }}</button>
        </div>
    </div>

    <h2>{{ Lang::get('surveys.addedCandidates') }}</h2>
    <table class="table" id="candidatesTable">
        <col style="width: 20em">
        <col style="width: 20em">
        <thead>
            <tr>
                <th>{{ Lang::get('surveys.recipientName') }}</th>
                <th>{{ Lang::get('surveys.recipientEmail') }}</th>
                <th>{{ Lang::get('surveys.recipientPosition') }}</th>
                <th id="endDateColumn">{{ Lang::get('surveys.endDate') }}</th>
                <th id="endDateRecipientsColumn">{{ Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants') }}</th>
                <th></th>
            </tr>
        </thead>
    </table>
</div>

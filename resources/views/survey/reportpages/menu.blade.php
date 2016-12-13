@if (!$userReportView)
    {!! Form::open(['action' => 'ReportController@createPDF', 'id' => 'reportForm']) !!}
        <input type='hidden' id='htmlContent' name='htmlContent' value=''>
        <input type='hidden' name='surveyId' value={{ $survey->id }}>
        <input type='hidden' name='isGroupReport' value="{{ $isIndividual && $toEvaluate == null }}">
        <input type='hidden' name='recipientId' value="{{ $isIndividual && $toEvaluate != null ? $toEvaluate->recipientId : '' }}">
        <br/>
        <br/>
        <p>
            {{ Lang::get('report.pressToGenerate') }}
        </p>

        <?php
            $introPageText = \App\EmailContentParser::parse(
                getReportText($survey, 'defaultIntroPageReportText', $reportTemplate)->text,
                $parserData,
                true);

            $answeredText = \App\EmailContentParser::parse(
                getReportText($survey, 'defaultAnsweredReportText', $reportTemplate)->text,
                $parserData,
                true);

            $invitedText = \App\EmailContentParser::parse(
                getReportText($survey, 'defaultInvitedReportText', $reportTemplate)->text,
                $parserData,
                true);

            $mainTitle = \App\EmailContentParser::parse(
                getReportText($survey, 'defaultMainTitleReportText', $reportTemplate)->text,
                $parserData,
                true);

            $parserData['mainTitle'] = $mainTitle;

            $footerText = \App\EmailContentParser::parse(
                getReportText($survey, 'defaultFooterReportText', $reportTemplate)->text,
                $parserData,
                true);
        ?>

        <div class="form-group">
            <?php
                $languages = [
                    'en' => Lang::get('surveys.languageEnglish'),
                    'sv' => Lang::get('surveys.languageSwedish'),
                    'fi' => Lang::get('surveys.languageFinnish')
                ];
            ?>
            <label>{{ Lang::get('surveys.selectLanguage') }}</label>
            <select autocomplete="off" name="lang" id="lang" class="form-control bfh-languages" data-language="en" style="max-width: 40%">
                @foreach ($languages as $key => $value)
                    @if ($key == $lang)
                        <option selected="selected" value="{{ $key }}">{{ $value }}</option>
                    @else
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endif
                @endforeach
            </select>
            <button class="btn btn-primary" type="button" style="margin-top: 10px;" onclick="changeLanguage()">{{ Lang::get('buttons.change') }}</button>
        </div>

        <div class="form-group">
            <label>{{ Lang::get('report.mainTitle') }}</label>
            <input type="input" id="mainTitleText" name="mainTitle" class="form-control"
                   style="max-width: 60%"
                   value="{{ $mainTitle }}">
        </div>

        <div class="form-group">
            <label>{{ Lang::get('report.footer') }}</label>
            <input type="input" name="footer" class="form-control"
                   style="max-width: 60%"
                   value="{{ $footerText }}">
        </div>

        <div class="form-group">
            <label>{{ Lang::get('report.introPage') }}</label>
            <textarea type="input" id="introPage" name="introPage" class="form-control"
                   style="max-width: 60%"
                   rows="7"
                   value="">{{ $introPageText }}</textarea>
        </div>

        <div class="form-group">
            <label>{{ Lang::get('report.answered') }}</label>
            <input type="input" id="answeredText" name="answeredText" class="form-control" style="max-width: 60%"
                   value="{{ $answeredText }}">
        </div>

        <div class="form-group">
            <label>{{ Lang::get('report.invited') }}</label>
            <input type="input" id="invitedText" name="invitedText" class="form-control" style="max-width: 60%"
                   value="{{ $invitedText }}">
        </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="insertBackcoverPage" checked="checked" value="yes"> {{ Lang::get('report.insertBackcoverPage') }}
            </label>
        </div>

        @if (isset($inlineReport))
            <input type="hidden" name="inlineReport" value="{{ $inlineReport }}">
        @endif

        <div class="checkbox">
            <label>
                <input type="checkbox" name="preview" value="yes"> {{ Lang::get('report.preview') }}
            </label>
        </div>

        @if ($includeInGroupReport != null)
            <input type="hidden" name="includeInGroupReport" value="{{ implode(',', $includeInGroupReport) }}">
        @endif

        <button id="create" type="submit" class ="btn btn-primary" disabled="disabled">{{ Lang::get('report.createPDF') }}</button>

        @if (!$userReportView)
            <button type="button" class ="btn btn-primary" onclick="toggleResutlCalcuations(true)">{{ Lang::get('report.explainCalculations') }}</button>
        @endif
    {!! Form::close() !!}

    <div id="resultCalcuations" class="helpBox" style="display: none;">
        <button type="button" class="close" aria-label="Close" onclick="toggleResutlCalcuations(false)"><span aria-hidden="true">&times;</span></button>
        <h2 style="margin-top: 0px;">Result calculations</h2>
        When the average values are calculated, the answers with N/A or text are not taken into account.
        <br>
        For each answer type, the answers are converted into the following numeric values:

        @foreach (\App\AnswerType::answerTypes() as $answerType)
            @if (!$answerType->isText())
                <h3>{{ $answerType->descriptionText() }}</h3>
                <table class="table" style="margin-bottom: 0px;">
                    <col width="70%">
                    <col width="30%">
                    <tr>
                        <th>Answer</th>
                        <th>Numeric value</th>
                    </tr>
                    @foreach ($answerType->values() as $value)
                        <tr>
                            <td>{{ $value->description }}</td>
                            <td>{{ round($value->value / $answerType->maxValue() * 100, 0) }}%</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach
    </div>

    <hr/>
    <br/>
    <br/>
@else
    {!! Form::open(['action' => 'ReportController@createUserPDF', 'id' => 'reportForm']) !!}
        <link rel="stylesheet" href="{{ asset('css/snippet.css') }}">

        @if ($survey->type == \App\SurveyTypes::Progress)
            @include('survey.partials.progressProcess', ['currentStep' => 2])
        @endif

        <p>
            {{ Lang::get('report.pressToGenerate') }}
        </p>
        <input type='hidden' name='htmlContent' id='htmlContent' value=''>
        <input type='hidden' name='userLink' value="{{ $userLink }}">
        <button id="create" type="submit" class ="btn btn-primary" disabled="disabled">{{ Lang::get('report.createPDF') }}</button>
    {!! Form::close() !!}
@endif

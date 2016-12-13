<script type="text/javascript">
    <?php $i = 0; ?>
    @foreach ($blindSpots->overestimated as $question)
        drawQuestionDiagram(
            "barchart_blindspot_over_{{ $i }}",
            {!! json_encode(getAnswerValues($question->id)) !!},
            {!! json_encode($selfRoleName) !!},
            {{ $question->self }},
            -1,
            {!! json_encode(Lang::get('roles.others')) !!},
            {{ $question->others }});
        <?php $i++; ?>
    @endforeach

    <?php $i = 0; ?>
    @foreach ($blindSpots->underestimated as $question)
        drawQuestionDiagram(
            "barchart_blindspot_under_{{ $i }}",
            {!! json_encode(getAnswerValues($question->id)) !!},
            {!! json_encode($selfRoleName) !!},
            {{ $question->self }},
            -1,
            {!! json_encode(Lang::get('roles.others')) !!},
            {{ $question->others }});
        <?php $i++; ?>
    @endforeach
</script>

@if (count($blindSpots->overestimated) > 0 || count($blindSpots->underestimated) > 0)
    @section('diagramContent')
        <!-- Overestimated -->
        @section('diagramContent')
            <?php $i = 0; ?>
            @foreach ($blindSpots->overestimated as $question)
                <div class="description">
                    <h4>{{ $question->category }}</h4>
                    <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                    <div id="barchart_blindspot_over_{{ $i }}" class="questionDiagram"></div>
                    <br>
                </div>
                <?php $i++; ?>
            @endforeach

            @if (count($blindSpots->overestimated) == 0)
                <b>{{ Lang::get('report.noOverestimatedText') }}</b>
            @endif
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'blinspotOver',
            'titleText' => getReportText($survey, 'defaultBlindspotsOverReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultBlindspotsOverReportText', $reportTemplate)->message,
            'pageBreak' => count($blindSpots->underestimated) > 0,
            'noIncludeBox' => true,
            'titleLevel' => 'h4'
        ])

        <!-- Underestimated -->
        @section('diagramContent')
            <?php $i = 0; ?>
            @foreach ($blindSpots->underestimated as $question)
                <div class="description">
                    <h4>{{ $question->category }}</h4>
                    <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>
                    <div id="barchart_blindspot_under_{{ $i }}" class="questionDiagram"></div>
                    <br>
                </div>
                <?php $i++; ?>
            @endforeach

            @if (count($blindSpots->underestimated) == 0)
                <b>{{ Lang::get('report.noUnderestimatedText') }}</b>
            @endif
        @overwrite

        @include('survey.reportpages.diagram', [
            'diagramName' => 'blinspotUnder',
            'titleText' => getReportText($survey, 'defaultBlindspotsUnderReportText', $reportTemplate)->subject,
            'bodyText' => getReportText($survey, 'defaultBlindspotsUnderReportText', $reportTemplate)->message,
            'pageBreak' => true,
            'noIncludeBox' => true,
            'titleLevel' => 'h4'
        ])
    @overwrite

    @include('survey.reportpages.diagram', [
        'diagramName' => 'blindspots',
        'includeTitle' => Lang::get('report.blindspots'),
        'reportText' => getReportText($survey, 'defaultBlindspotsReportText', $reportTemplate),
        'pageBreak' => false,
        'isPage' => true
    ])
@else
    <h3 class="previewOnly">{{ Lang::get('report.foundNoBlindSpots') }}</h3>
@endif

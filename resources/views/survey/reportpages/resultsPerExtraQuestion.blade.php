<script type="text/javascript">
    <?php $i = 0; ?>
    @foreach ($categoriesByExtraAnswer as $extraQuestion)
        @foreach ($extraQuestion->values as $value)
            chartsToDraw.push(function() {
                drawValuesChart("extra_question_{{ $i}}", {!! json_encode(array_map(function ($value) {
                    return (object)[
                        'name' => $value->name,
                        'value' => $value->average,
                    ];
                }, $value->categories)) !!});
            });
            <?php $i++; ?>
        @endforeach
    @endforeach
</script>

@section('diagramContent')
    <?php $i = 0; ?>
    @foreach ($categoriesByExtraAnswer as $extraQuestion)
        <h4>{{ \App\EmailContentParser::parse($extraQuestion->name, $surveyParserData, true, true) }}</h4>
        @foreach ($extraQuestion->values as $value)
            <i><b>{{ $value->value }}</b></i>
            <div id="extra_question_{{ $i }}"></div>
            <br>
            <?php $i++; ?>
        @endforeach
    @endforeach
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'resultsPerExtraQuestion',
    'includeTitle' => Lang::get('report.resultsPerExtraQuestion'),
    'reportText' => getReportText($survey, 'defaultResultsPerExtraQuestionReportText', $reportTemplate),
    'pageBreak' => true,
])

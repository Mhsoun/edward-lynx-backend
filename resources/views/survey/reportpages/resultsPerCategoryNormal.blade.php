<script type="text/javascript">
    @foreach ($extraAnswersByCategoriesSummary as $category)
        @foreach ($category->extraQuestions as $extraQuestion)
            @if (count($extraQuestion->values) > 0)
                chartsToDraw.push(function() {
                    drawValuesChart("category_{{ $category->id }}_{{ $extraQuestion->id }}", {!! json_encode(array_map(function ($value) {
                        return (object)[
                            'name' => $value->value,
                            'value' => $value->average != 0 ? $value->average : null,
                        ];
                    }, $extraQuestion->values)) !!}, {{ $category->average }});
                });
            @endif
        @endforeach
    @endforeach
</script>

@section('diagramContent')
    @foreach ($extraAnswersByCategoriesSummary as $category)
        <h4>{{ $category->name }}</h4>
        @foreach ($category->extraQuestions as $extraQuestion)
            @if (count($extraQuestion->values) > 0)
                <i>{{ \App\EmailContentParser::parse($extraQuestion->name, $surveyParserData, true, true) }}</i>
                <br>
                <div id="category_{{ $category->id }}_{{ $extraQuestion->id }}"></div>
            @endif
        @endforeach
    @endforeach
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'resultsPerCategory',
    'includeTitle' => Lang::get('report.resultsPerCategory'),
    'reportText' => getReportText($survey, 'defaultResultsPerCategoryReportText', $reportTemplate),
    'pageBreak' => true,
])

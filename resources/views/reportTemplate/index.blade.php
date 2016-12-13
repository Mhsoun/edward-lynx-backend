@extends('layouts.default')
@section('content')
    <div class="mainBox">
        <h2>{{ Lang::get('reportTemplates.reportTemplates') }}</h2>

        <table class="table">
            <tr>
                <th>{{ Lang::get('reportTemplates.name') }}</th>
                <th>{{ Lang::get('surveys.type') }}</th>
                <th>{{ Lang::get('surveys.language') }}</th>
                <th></th>
            </tr>
            @foreach ($reportTemplates as $reportTemplate)
                <tr>
                    <td><a href="{{ action('ReportTemplateController@edit', ['id' => $reportTemplate->id]) }}">{{ $reportTemplate->name }}</a></td>
                    <td>{{ \App\SurveyTypes::name($reportTemplate->surveyType) }}</td>
                    <td>{{ \App\Languages::name($reportTemplate->lang) }}</td>
                    <td>
                        {{-- <a class="btn btn-danger btn-xs" href="{{ action('ReportTemplateController@delete', ['id' => $reportTemplate->id]) }}"> --}}
                        <a class="btn btn-danger btn-xs" href="javascript:deleteTemplate('{{ action('ReportTemplateController@delete', ['id' => $reportTemplate->id]) }}')">
                            <span class="glyphicon glyphicon-trash"></span>
                        </a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <script type="text/javascript">
        function deleteTemplate(url) {
            if (confirm({!! json_encode(Lang::get('reportTemplates.deleteConfirmation')) !!})) {
                document.location = url;
            }
        }
    </script>
@endsection

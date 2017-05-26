@extends('layouts.default')
@section('content')
    <div class="container">
        <div class="mainBox">
            @if ($errors->count() > 0)
                <div class="alert alert-danger">
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    @foreach ($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <h2>{{ Lang::get('welcome.companies') }}</h2>
            <p>
                {{ Lang::get('welcome.companiesText') }}
            </p>

            <a class="btn btn-primary" href="{{ action('CompanyController@create') }}">{{ Lang::get('welcome.register') }}</a>

            <h4>{{ Lang::get('welcome.filterCompany') }}</h4>
            <div class="input-group" style="max-width: 40%">
                <input type="text" placeholder="{{ Lang::get('surveys.companySearchPlaceholder') }}" class="form-control" autocomplete="off" id="companySearch">
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-primary" onclick="searchForCompanies()">{{ Lang::get('buttons.filter') }}</button>
                </span>
            </div>

            <table class="table table-striped" id="companyTable">
                <col style="width: 30%">
                <col style="width: 30%">
                <col>
                <col style="width: 30%">
                <thead>
                    <tr class="tableHeader">
                        <th>{{ Lang::get('welcome.companyName') }}</th>
                        <th>{{ Lang::get('welcome.email') }}</th>
                        <th>{{ Lang::get('company.projects') }}</th>
                        <th>{{ Lang::get('company.surveyTypes') }}</th>
                        <th>{{ Lang::get('welcome.validated') }}</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @unless($user->isAdmin)
                        <tr id="company_{{ $user->id }}">
                            <td><a href="{{ action('CompanyController@edit', $user->id) }}" class="companyName">{{ $user->name }}</a></td>
                            <td>{{ $user->email }}</td>
                            <td><a href="{{ action('CompanyController@viewProjects', ['id' => $user->id]) }}">{{ Lang::get('company.projects') }}</a></td>
                            <td>
                                {{ implode(", ", \App\SurveyTypes::names(\App\SurveyTypes::getTypes($user->allowedSurveyTypes))) }}
                            </td>
                            <td>
                                @if ($user->isValidated == 1)
                                    {{ Lang::get('general.yes') }}
                                @else
                                    {{ Lang::get('general.no') }}
                                @endif
                            </td>
                            <td>
                                <a href="{{ action('CompanyController@edit', $user->id) }}">
                                    <p data-toggle="tooltip" title="Edit">
                                        <button class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-pencil"></span></button>
                                    </p>
                                </a>
                            </td>
                            <td>
                                <p data-toggle="tooltip" title=@if ($user->subUsers()->count() > 0) "Cannot delete: Company has users." @else "Delete" @endif>
                                    <button class="btn btn-danger btn-xs" data-toggle="modal" data-target="#modalRemove{!! $user->id !!}" @if($user->subUsers()->count() > 0) disabled @endif>
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </button>
                                </p>
                            </td>
                            <div class="modal fade" id="modalRemove{!! $user->id !!}" tabindex="-1" role="dialog"
                                 aria-labelledby="modalRemoveLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span></button>
                                            <h4 class="modal-title" id="myModalLabel">{{ Lang::get('welcome.deleteHeader') }} {!! $user->name !!}</h4>
                                        </div>
                                        <div class="modal-body">
                                            {{ Lang::get('welcome.confirmDeletion') }} {!! $user->name !!}?
                                        </div>
                                        <div class="modal-footer">
                                            <a href="{{ action('CompanyController@destroy', $user->id) }}">
                                                <button type="button" id="ok" class="btn btn-success"
                                                        value="{!! $user->id !!}">{{ Lang::get('buttons.ok') }}
                                                </button>
                                            </a>
                                            <button type="button" class="btn btn-danger" data-dismiss="modal">{{ Lang::get('buttons.cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tr>
                    @endunless
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        var companySearch = $("#companySearch");

        //Searches for companies
        function searchForCompanies() {
            var query = companySearch.val().toLowerCase();
            var companyTable = $("#companyTable");

            companyTable.find("tr").each(function(i, element) {
                element = $(element);
                if (!element.hasClass("tableHeader")) {
                    var name = element.find(".companyName").text().toLowerCase();

                    if (name.contains(query)) {
                        element.show();
                    } else {
                        element.hide();
                    }
                }
            });
        }

        companySearch.keyup(searchForCompanies);
    </script>
@stop

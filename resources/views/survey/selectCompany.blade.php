@extends('layouts.default')
@section('content')
    <div class="container">
        <div class="mainBox">
            <h2> {{Lang::get('surveys.selectCompany')}} </h2>
            <a class="btn btn-primary" href="{{ action('SurveyController@createCompany', Auth::user()->id) }}">{{ Lang::get('surveys.createForThis') }}</a>
            <br>

            <br>
            <div class="row">
                <div class="col-md-8">
                    {{ Lang::get('surveys.selectCompanyDescription') }}
                    {!! Form::open(['action' => 'SurveyController@createCompanyByName', 'method' => 'get']) !!}
                        <div class="input-group" style="max-width: 60%">
                            <input type="text" placeholder="{{ Lang::get('surveys.companySearchPlaceholder') }}"
                                   class="form-control" autocomplete="off" name="company" id="companySearch">
                            <span class="input-group-btn">
                                <button type="button" id="selectButton" class="btn btn-primary" onclick="searchForCompanies()">{{ Lang::get('buttons.filter') }}</button>
                            </span>
                        </div>
                    {!! Form::close() !!}
                    <br>

                    <table id="companyTable" class="table table-striped table-hover">
                        <tr class="tableHeader">
                            <th>{{ Lang::get('welcome.companyName') }}</th>
                            <th>{{ Lang::get('surveys.companyAdded') }}</th>
                        </tr>
                        @foreach($users as $user)
                            <tr>
                                <td class="companyName"><a href="{{action('SurveyController@createCompany', $user->id)  }}">{{ $user->name }}</a></td>
                                <td>{{ $user->created_at->toDateString() }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
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

@extends('layouts.default')
@section('content')
    <div class="mainBox">
    	<div class="container">
			<h2 style="margin-bottom: 0px;">{{ Lang::get('settings.header') }}</h2>
	    	@include('errors.list')

	    	@if (Session::get('changeText') != null)
	    		<h4>{{ Session::get('changeText') }}</h4>
	    	@endif

            <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
                <li class="active"><a href="#password" data-toggle="tab">{{ Lang::get('settings.changePasswordHeader') }}</a></li>
                <li><a href="#language" data-toggle="tab">{{ Lang::get('settings.changeLanguageHeader') }}</a></li>
                <li><a href="#texts" data-toggle="tab">{{ Lang::get('settings.defaultTexts') }}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="password">
                    @include('user.changePassword')
                </div>
                <div class="tab-pane" id="language">
                    @include('user.changeLanguage')
                </div>
                <div class="tab-pane" id="texts">
                    <h3>{{ Lang::get('settings.defaultTexts') }}</h3>
    			    <ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
    			        <li class="active"><a href="#emails" data-toggle="tab">{{ Lang::get('settings.emailsHeader') }}</a></li>
    			        <li><a href="#descriptions" data-toggle="tab">{{ Lang::get('settings.projectHeader') }}</a></li>
    			        <li><a href="#reportTexts" data-toggle="tab">{{ Lang::get('settings.reportTextsHeader') }}</a></li>
    			    </ul>
                    {!! Form::open(['method' => 'put', 'action' => 'UserController@updateDefaultTexts']) !!}
        			    <div class="tab-content" style="margin-top: 5px">
                                @include('user.partials.switchMenu', [
                                    'langFieldName' => 'changeLanguage',
                                    'typeFieldName' => 'changeType'
                                ])

            			        <div class="tab-pane active" id="emails">
            						@include('user.changeDefaultEmails')
            			        </div>
            			        <div class="tab-pane" id="descriptions">
            			            @include('user.changeDefaultProjectInformation')
            			        </div>
            			        <div class="tab-pane" id="reportTexts">
            			            @include('user.changeDefaultReportTexts')
            			        </div>
        			    </div>
                        <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.save') }}</button>
                    {!! Form::close() !!}
                </div>
            </div>
		</div>
    </div>

    <script type="text/javascript" src="{{ asset('js/user.settings.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var changeLanguage = $("#changeLanguage");
            var changeType = $("#changeType");

            function switchTexts() {
                var lang = changeLanguage.val();
                var type = changeType.val();

                UserSettings.switchTexts($("#emailTexts"), lang, type);
                UserSettings.switchTexts($("#projectInformations"), lang, type);
                UserSettings.switchTexts($("#reportTexts"), lang, type);
            }

           changeLanguage.change(switchTexts);
           changeType.change(switchTexts);

           switchTexts();
        });
    </script>
@stop

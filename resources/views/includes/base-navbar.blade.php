<?php
    $customLogoPath = '';
    if ($user != null) {
        $customLogoPath = 'images/logos/' . $user->name . '_logo.png';
    }
?>

<header class="navbar navbar-inverse navbar-fixed-top" id="navColor" role="banner"
        style="background-color: #{{ $user != null && !$user->isAdmin ? $user->navColor : 'DDDDDD' }}">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            @if ($user != null && file_exists($customLogoPath))
                <a class="navbar-brand" href="{{ url("/") }}" style="">
                    <div style="background:url('{{ asset($customLogoPath) }}') no-repeat center center; width: 150px; height: 75px; background-size: 100%;"></div>
                </a>
            @else
                <a class="navbar-brand" href="{{ url("/") }}">
                    <img style="max-width: 150px;" src="{{ asset('images/logo-transparent.png') }}" alt="logo">
                </a>
            @endif
        </div>
        <div class="collapse navbar-collapse">
            @if ($showMenu == true)
                <ul class="nav navbar-nav">
                    <li><a href="{{ action('UserController@index') }}">{{ Lang::get('nav.home') }}</a></li>
                    <li><a href="{{ action('SurveyController@create') }}">{{ Lang::get('nav.createSurvey') }}</a></li>
                    <li><a href="{{ action('SurveyController@index') }}">{{ Lang::get('nav.surveys') }}</a></li>
                    <li><a href="{{ action('ReportTemplateController@index') }}">{{ Lang::get('nav.reportTemplates') }}</a></li>
                    <li><a href="{{ action('UserController@settings') }}">{{ Lang::get('nav.settings') }}</a></li>
                    @if ($user != null && $user->isAdmin)
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Lang::get('nav.admin') }} <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="{{ action('CompanyController@index') }}">{{ Lang::get('nav.companies') }}</a></li>
                                <li><a href="{{ action('UsersController@index') }}">Users</a></li>
                                <li><a href="{{ action('AdminController@performanceIndex') }}">{{ Lang::get('nav.performance') }}</a></li>
                                <li><a href="{{ action('AdminController@rolesIndex') }}">{{ Lang::get('nav.roles') }}</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>
            @endif
            <ul class="nav navbar-nav navbar-right">
                @if ($user != null && file_exists($customLogoPath))
                    <a class="navbar-brand" href="{{ url("/") }}">
                        <img style="max-width: 150px;" src="{{ asset('images/logo-transparent.png') }}" alt="logo">
                    </a>
                @endif

                @if ($showMenu == true)
                    @if ($user == null)
                        <li><a href="{{ url('/auth/login') }}">{{ Lang::get('nav.login') }}</a></li>
                    @else
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ $user->name }} <span class="caret"></span></a>
                            <ul class="dropdown-menu" role="menu">
                                <li>
                                    <a href="{{ url('/logout')}}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">   {{ Lang::get('nav.logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none">
                                        {{ csrf_field() }}
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endif
            </ul>
        </div>
    </div>
</header>

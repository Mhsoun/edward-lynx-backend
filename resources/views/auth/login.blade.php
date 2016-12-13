@extends('layouts.no-nav')

@section('content')
<style type="text/css">
#login-modal input[type=text], input[type=password] {
    margin-top: 10px;
}

.modal-backdrop.in {
    filter: alpha(opacity=50);
    opacity: .8;
}

.modal-content {
    background-color: #ececec;
    border: 1px solid #bdc3c7;
    border-radius: 0px;
    outline: 0;
}

.modal-header {
    min-height: 16.43px;
    padding: 15px 15px 15px 15px;
    border-bottom: 0px;
}

.modal-body {
    position: relative;
    padding: 5px 15px 5px 15px;
}

.modal-footer {
    padding: 10px 15px 15px 15px;
    text-align: left;
    border-top: 0px;
}

.checkbox {
    margin-bottom: 0px;
}
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-5 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-heading"><h4 style="margin: 0">{{ Lang::get('auths.loginHeader') }}</h4></div>
                <div class="panel-body">
                    @if (count($errors) > 0)
                        <div class="alert alert-danger">
                            There were some problems with your input.<br><br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div id="div-forms">
                        <form id="login-form" role="form" method="POST" action="{{ URL::to('/auth/login') }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <div class="modal-body">
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="{{ Lang::get('auths.email') }}">
                                <input type="password" class="form-control" name="password" placeholder="{{ Lang::get('auths.password') }}">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox"> <input type="checkbox" name="remember"> {{ Lang::get('auths.rememberMe') }}
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div>
                                    <button type="submit" class="btn btn-primary btn-md">{{ Lang::get('buttons.login') }}</button>
                                </div>
                                <div style="margin-top: 10px">
                                    <a href="{{ URL::to('/password/email') }}">{{ Lang::get('auths.forgetPassword') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

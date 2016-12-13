{!! Form::open(['method' => 'put', 'action' => 'UserController@updateLanguage']) !!}
    <h3>{{ Lang::get('settings.changeLanguageHeader') }}</h3>
    <div class="form-group">
        <select class="form-control" id="language" name="language" autocomplete="off" style="max-width: 40%">
            @foreach ($languages as $name => $text)
                <option value="{{ $name }}" {{ $name == Auth::user()->lang ? 'selected=selected' : "" }}>{{ $text }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary">{{ Lang::get('buttons.change') }}</button>
{!! Form::close() !!}

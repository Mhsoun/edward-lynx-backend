<div class="pull-right">
    @if ($page > 1)
        <button type="button" class="btn btn-primary btn-lg" onclick="AnswerView.changePage(-1)">{{ Lang::get('buttons.back') }}</button>
    @endif

    @if ($page == $numPages)
        <button type="submit" class="btn btn-primary btn-lg">{{ Lang::get('surveys.answerBtn') }}</button>
    @else
        <button type="button" class="btn btn-primary btn-lg" onclick="AnswerView.changePage(1)">{{ Lang::get('buttons.next') }}</button>
    @endif

    <h4 style="display: inline; margin-left: 15px">{{ $page }} / {{ $numPages }}</h4>
</div>
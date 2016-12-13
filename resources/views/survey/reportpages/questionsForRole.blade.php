<div class="row">
    <?php $i = 0; ?>
    @foreach ($roles as $role)
        <div {!! $i % 2 === 0 ? 'class="left" style="clear: left;"' : 'class="right"' !!}>
            <h3>{{ $role->name }}</h3>
            <?php $prevCategory = -1; $order = 1; ?>
            @foreach ($getQuestions($role) as $question)
                @if ($prevCategory != $question->categoryId)
                    <h4 style="margin-bottom: 5px">{{ $question->category }}</h4>
                @endif
                    <div style="padding-left: 30px;">
                        {{ $order }}. <i><b>{{ \App\EmailContentParser::parse($question->title, $surveyParserData, true, true) }}</b></i>: {{ round($question->average * 100) }}%
                    </div>
                <?php $prevCategory = $question->categoryId; $order++ ?>
            @endforeach
        </div>
        <?php $i++ ?>
    @endforeach
</div>

@extends('layouts.no-nav')
@section('content')
    @if ($survey->endDatePassed($surveyRecipient->invitedById, $surveyRecipient->recipientId))
        <div class="alert alert-warning" role="alert">
            <h3 style="margin-top: 0px;">{{ Lang::get('surveys.endDatePassedHeader') }}</h3>
            {{ Lang::get('surveys.endDatePassedContent') }}
        </div>
    @endif

    <h3 id="errorInfoBox" class="alert alert-danger" style="display: {{ $errors->any() ? 'block' : 'none' }}">
        {{ Lang::get('surveys.answerHighlightedQuestions') }}
    </h3>

    @if (\App\SurveyTypes::isNewProgress($survey) && $surveyRecipient->isCandidate())
        @include('survey.partials.progressProcess', ['currentStep' => 0])
    @endif

    {!! Form::open(['action' => ['AnswerController@store', $link], 'id' => 'answerForm']) !!}
        <?php
            $page = 1;
            $numPages = count($categories) + 1;

            $hasExtraQuestion = $survey->extraQuestions()->count() > 0;

            if ($survey->type == \App\SurveyTypes::Normal) {
                // $numPages++;

                if ($hasExtraQuestion) {
                    $numPages++;
                }
            }
        ?>

        <!-- Intro page -->
        <div id="page_{{ $page }}">
            @if ($survey->type != \App\SurveyTypes::Normal)
                <h2>{{ $survey->typeName() }}: {{ $survey->name }}</h2>
            @else
                <h2>{{ $survey->name }}</h2>
            @endif
            <p>
                {!! \App\EmailContentParser::parse($survey->description, $parserData) !!}
            </p>

            @include('answer.partials.nav', ['page' => $page, 'numPages' => $numPages])
        </div>
        <?php $page++; ?>

        <!-- Extra questions -->
        @if ($survey->type == \App\SurveyTypes::Normal && $hasExtraQuestion)
            <div id="page_{{ $page }}" style="display: none">
                <h3>{{ Lang::get('surveys.preInformationText') }}</h3>
                @include('answer.partials.extraquestions', ['survey' => $survey])
                @include('answer.partials.nav', ['page' => $page, 'numPages' => $numPages])
            </div>

            <?php $page++; ?>
        @endif

        <!-- Normal questions -->
        <?php $showCategory = $survey->showCategoryTitles; ?>
        @foreach ($categories as $category)
            <div id="page_{{ $page }}" style="display: none">
                @if ($survey->questionInfoText != "")
                    {!! \App\EmailContentParser::parse($survey->questionInfoText, $parserData) !!}
                @endif

                @include('answer.partials.category', ['showCategory' => $showCategory, 'category' => $category])
                @include('answer.partials.nav', ['page' => $page, 'numPages' => $numPages])
            </div>
            <?php $page++; ?>
        @endforeach

        <!-- Best/worst -->
        {{-- @if ($survey->type == \App\SurveyTypes::Normal)
            <div id="page_{{ $page }}" style="{{ $page > 1 ? "display: none" : "" }}">
                <h3>{{ Lang::get('surveys.selectWorstThree') }}</h3>
                <select id="selectTopList" class="form-control" style="max-width: 30%; display: inline"></select>
                <button type="button" class="btn btn-primary" style="margin-bottom: 3px" id="selectTopButton">{{ Lang::get('buttons.select') }}</button>
                <div id="topList"></div>

                <h3>{{ Lang::get('surveys.selectTopThree') }}</h3>
                <select id="selectWorstList" class="form-control" style="max-width: 30%; display: inline"></select>
                <button type="button" class="btn btn-primary" style="margin-bottom: 3px" id="selectWorstButton">{{ Lang::get('buttons.select') }}</button>
                <div id="worstList"></div>

                <br>
                @include('answer.partials.nav', ['page' => $page, 'numPages' => $numPages])
            </div>
        @endif --}}
        <?php /* $page++; */ ?>
    {!! Form::close() !!}
    <script src="{{ asset('js/answer.view.js') }} "></script>
    <script type="text/javascript">
        @foreach ($survey->categories as $category)
            AnswerView.addCategory({
                id: {{ $category->category->id }},
                name: {!! json_encode($category->category->title) !!}
            });
        @endforeach
        AnswerView.setAnswerLink({!! json_encode($link) !!});
    </script>
@stop

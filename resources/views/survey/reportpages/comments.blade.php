<?php
    //Esimates the number of lines the given text takes
    function countNumLines($text) {
        return ceil(strlen($text) / 120);
    }

    //Split the comments into pages
    $commentPages = [];
    $currentPage = [];
    $numLines = 2 + countNumLines(getReportText($survey, 'defaultCommentsReportText', $reportTemplate)->message);
    $pageLimit = 53;
    $totalComments = 0;

    $commentsList = [];
    if (\App\SurveyTypes::isGroupLike($survey->type)) {
        //Check which categories that has other questions than text
        $hasOtherThanText = [];
        foreach ($categories as $category) {
            $hasOtherThanText[$category->id] = true;
        }

        foreach ($comments as $comment) {
            if (!array_key_exists($comment->categoryId, $hasOtherThanText)) {
                array_push($commentsList, $comment);
            }
        }
    } else {
        $commentsList = $comments;
    }

    foreach ($commentsList as $comment) {
        $commentTitle = \App\EmailContentParser::parse(
            $comment->title,
            $surveyParserData,
            true);

        $questionAnswers = (object)[
            'title' => $commentTitle,
            'id' => $comment->id,
            'answers' => [],
            'isFirst' => true
        ];

        $titleLines = countNumLines($commentTitle);

        if ($numLines + $titleLines > $pageLimit) {
            array_push($commentPages, $currentPage);
            $currentPage = [];
            $numLines = 0;
        }

        $numLines += $titleLines;
        array_push($currentPage, $questionAnswers);

        foreach ($comment->answers as $answer) {
            $answerLines = countNumLines($answer->text);

            if ($numLines + $answerLines > $pageLimit) {
                $questionAnswers = (object)[
                    'title' => $commentTitle,
                    'id' => $comment->id,
                    'answers' => [],
                    'isFirst' => false
                ];

                $numLines = $titleLines;
                array_push($commentPages, $currentPage);
                $currentPage = [];
                array_push($currentPage, $questionAnswers);
            }

            array_push($questionAnswers->answers, $answer);
            $numLines += $answerLines;
            $totalComments++;
        }
    }

    if (count($currentPage) > 0) {
        array_push($commentPages, $currentPage);
    }
?>

@if ($totalComments > 0)
    @section('diagramContent')
        <?php $id = -1; ?>
        @foreach ($commentPages as $page)
            <div class="pageBreak">
                @foreach ($page as $comment)
                    <?php
                        if ($comment->id != $id) {
                            $id = $comment->id;
                        }
                    ?>
                    <div class="comment description">
                        <span class="{{ !$comment->isFirst ? 'secondaryCommentTitle' : '' }}">
                            <i class="comment_{{ $comment->id }}_text"><b>{{ $comment->title }}</b></i>
                            @if ($comment->isFirst)
                                <a class="textButton editTextButton" onclick="showEditQuestionTitle({{ $comment->id }})">
                                    <span class="glyphicon glyphicon-pencil"></span>
                                </a>
                                <div class="comment_{{ $comment->id }}_edit" class="editBox" style="display: none">
                                    <textarea class="editText form-control" style="max-width: 40%" rows="2" autocomplete="off">{{ $comment->title }}</textarea>
                                    <br>
                                    <button class="btn btn-primary" onclick="saveEditQuestionTitle({{ $comment->id }})">{{ Lang::get('buttons.save') }}</button>
                                </div>
                            @endif
                            <br>
                        </span>

                        @foreach (\App\ArrayHelpers::shuffle($comment->answers) as $answer)
                            <span class="commentAnswer">&bull; {{ $answer->text }}</span>
                            <br>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    @overwrite

    @include('survey.reportpages.diagram', [
        'diagramName' => 'comments',
        'includeTitle' => Lang::get('report.comments'),
        'reportText' => getReportText($survey, 'defaultCommentsReportText', $reportTemplate),
        'pageBreak' => false,
        'isPage' => true
    ])

    <script type="text/javascript">
        //Toggles the edit for given question title
        function toggleEditQuestionTitle(commentId) {
            $(".comment_" + commentId + "_edit").toggle();
        }

        //Shows the edit box for given question title
        function showEditQuestionTitle(commentId) {
            var editBox = $(".comment_" + commentId + "_edit");
            var commentBox = $(".comment_" + commentId + "_text");
            editBox.find(".editText").val(commentBox.html());
            toggleEditQuestionTitle(commentId);
        }

        //Saves the edit for the given question title
        function saveEditQuestionTitle(commentId) {
            var editBox = $(".comment_" + commentId + "_edit");
            var commentBox = $(".comment_" + commentId + "_text");
            commentBox.html(editBox.find(".editText").val());
            toggleEditQuestionTitle(commentId);
        }
    </script>
@endif

<?php
    if (isset($reportText)) {
        $titleText = $reportText->subject;
        $bodyText = $reportText->message;
        $diagramId = $reportText->type;
    }

    if (isset($reportTemplate) && $reportTemplate != null && isset($diagramId)) {
        $visible = includeDiagram($reportTemplate, $diagramId);
    }

    if (!isset($visible)) {
        $visible = true;
    }

    if (!isset($titleLevel)) {
        $titleLevel = "h3";
    }

    if (!isset($visible)) {
        $visible = true;
    }

    if (!isset($isPage)) {
        $isPage = false;
    }

    $pageBoxDiv = '';
    if ($isPage) {
        $pageBoxDiv = 'class="diagramPageBox" id="' . $diagramName . 'PageBox"';
    }

    if (!isset($hasTitleAndText)) {
        $hasTitleAndText = true;
    }
?>

<div {!! $pageBoxDiv !!}>
    @if (((isset($noIncludeBox) && !$noIncludeBox) || !isset($noIncludeBox)) && !$userReportView)
        <div class="includeCheckbox checkbox">
            <label>
                <input type="checkbox" onclick="toggleShowInReport(this, '{{ $diagramName }}Page')" {{ $visible ? 'checked' : '' }} autocomplete="off">
                {{ Lang::get('surveys.include') }} {{ $includeTitle }}
            </label>
        </div>
    @endif

    <div id="{{ $diagramName }}Page" class="diagramPage {{ $pageBreak ? 'pageBreak' : '' }}">
        @if ($hasTitleAndText)
            <{{ $titleLevel  }} class="editTitle" id="{{ $diagramName }}Title">{{ $titleText }}</{{ $titleLevel }}>
        @endif

        @if (!$userReportView && $hasTitleAndText)
            <a class="textButton editTextButton"
               onclick="showEditText('{{ $diagramName }}Title', '{{ $diagramName }}Text', '{{ $diagramName }}Box')">
               <span class="glyphicon glyphicon-pencil"></span>
            </a>
        @endif

        @if (!$userReportView && $isPage)
            <a class="textButton" id="{{ $diagramName }}MoveUp">
                <span class="glyphicon glyphicon-menu-up" style="float: left; font-size: large"></span>
            </a>

            <a class="textButton" id="{{ $diagramName }}MoveDown">
                <span class="glyphicon glyphicon-menu-down" style="float: left; font-size: large; margin-top: 12px; margin-left: -18px; margin-right: 5px"></span>
            </a>
        @endif

        @if ($hasTitleAndText)
            <p id="{{ $diagramName }}Text" class="description">{!! $bodyText !!}</p>

            @if (!$userReportView)
                <div style="display: none" id="{{ $diagramName }}Box">
                    <input type="textbox" class="editTitle form-control" style="max-width: 40%; margin-bottom: 5px">
                    <textarea class="editText form-control" style="max-width: 40%" rows="5"></textarea>
                    <br>
                    <button class="btn btn-primary" onclick="saveEditText('{{ $diagramName }}Title', '{{ $diagramName }}Text', '{{ $diagramName }}Box')">
                        {{ Lang::get('buttons.save') }}
                    </button>
                </div>
            @endif
        @endif

        @yield('diagramContent')
    </div>
</div>

@if (!$visible)
    <script type="text/javascript">
        $(document).ready(function() {
            $("#{{ $diagramName }}Page").hide();
        });
    </script>
@endif

@if (!$userReportView && $isPage)
    <script type="text/javascript">
        $("#{{ $diagramName }}MoveUp").click(function () {
            var page = $("#{{ $diagramName }}PageBox");
            var prev = page.prevAll(".diagramPageBox");
            if (prev.length > 0) {
                page.insertBefore(prev[0]);
            }
        });

        $("#{{ $diagramName }}MoveDown").click(function () {
            var page = $("#{{ $diagramName }}PageBox");
            var next = page.nextAll(".diagramPageBox");
            if (next.length > 0) {
                page.insertAfter(next[0]);
            }
        });
    </script>
@endif

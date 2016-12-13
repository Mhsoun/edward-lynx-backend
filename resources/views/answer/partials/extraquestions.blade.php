<div style="margin-bottom: 20px; max-width: 40%">
    @foreach (\App\ExtraAnswerValue::valuesForSurvey($survey) as $answerValue)
        <?php
            $oldValue = old('extraAnswer_' . $answerValue->id());
            $className = "extraQuestion" . (!$answerValue->isOptional() ? " required" : "");
            $formId = "extraAnswer_" . $answerValue->id();
            $hasErrors = $errors->has($formId);
        ?>

        <div class="{{ $className }} {{ $hasErrors ? 'alert alert-danger' : '' }}">
            <label>
                {!! \App\EmailContentParser::parse($answerValue->label(), $parserData, true) !!}
                {{ !$answerValue->isOptional() ? " (*)" : "" }}
            </label>

            @if ($answerValue->type() == \App\ExtraAnswerValue::Text)
                <input name="{{ $formId }}"
                       class="form-control extraAnswer textValue" type="text" value="{{ $oldValue }}"
                       autocomplete="off"
                       data-question-id="{{ $answerValue->id() }}">
            @elseif ($answerValue->type() == \App\ExtraAnswerValue::Options)
                <select name="{{ $formId }}"
                        class="form-control extraAnswer optionsValue"
                        autocomplete="off" data-question-id="{{ $answerValue->id() }}">
                    @foreach ($answerValue->options() as $value => $text)
                        <option value="{{ $value }}">{{ $text }}</option>
                    @endforeach
                </select>
            @elseif ($answerValue->type() == \App\ExtraAnswerValue::Date)
                <input name="{{ $formId }}"
                       class="form-control extraAnswer dateValue" type="text" placeholder="YYYY-MM-DD" value="{{ $oldValue }}"
                       autocomplete="off"
                       data-question-id="{{ $answerValue->id() }}">
            @elseif ($answerValue->type() == \App\ExtraAnswerValue::Hierarchy)
                <div id="hierarchyBox_{{ $answerValue->id() }}">
                    @include('answer.partials.hierarchy', ['id' => $answerValue->id(), 'parentId' => 'root', 'level' => 0, 'values' => $answerValue->options()])
                </div>
            @endif

            @if ($hasErrors)
                <ul>
                    @foreach ($errors->get($formId) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</div>

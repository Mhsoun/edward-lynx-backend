<select class="form-control hierarchySelect" id="select_{{ $parentId }}" data-parent-id="{{ $parentId }}" style="max-width: {{ max(100 - 10 * $level, 10) }}%" autocomplete="off">
    <option value="" selected>{{ Lang::get('extraQuestions.selectValue') }}</option>
    @foreach ($values as $value)
        <option value="{{ $value->value }}">{{ $value->name }}</option>
    @endforeach
</select>

<script type="text/javascript">
    $("#select_{{ $parentId }}").change(function(e) {
        if (e.target.value != "") {
            $(".level_{{ $level }}").hide();
            $(".level_{{ $level }}").find(".hierarchySelect").val("");
            $("#children_" + e.target.value).show();
            $("#select_" + e.target.value).val("");

            var value = "";
            if (leafValues[e.target.value] != undefined) {
                value = e.target.value;
            }

            $("[name='extraAnswer_{{ $id }}']").val(value)
        }
    });

    @if ($level == 0)
        var leafValues = {};
    @endif
</script>

@foreach ($values as $value)
    @if (count($value->children) > 0)
        <div id="children_{{ $value->value }}" class="hierarchyLevel level_{{ $level }}" style="display: none" data-level="{{ $level }}">
            @include('answer.partials.hierarchy', ['id' => $id, 'parentId' => $value->value, 'level' => $level + 1, 'values' => $value->children])
        </div>
    @else
        <script type="text/javascript">
            leafValues[{{ $value->value }}] = true;
        </script>
    @endif
@endforeach

@if ($level == 0)
    <input type="hidden" class="extraAnswer hierarchyValue" name="extraAnswer_{{ $id }}" autocomplete="off" data-question-id="{{ $id }}">
@endif

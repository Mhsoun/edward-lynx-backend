<?php
    if (!isset($width)) {
        $width = '40%';
    }
?>

<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <div class="input-group date" id="{{ $name }}Picker" style="width: {{ $width }};">
        <input name="{{ $name }}" id="{{ $name }}" type="text" class="form-control" />
        <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
        </span>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#{{ $name }}Picker').datetimepicker({
            format: 'YYYY-MM-DD',
            useCurrent: false,
            defaultDate: "{{ isset($value) ? $value : '' }}",
        });
    });
</script>

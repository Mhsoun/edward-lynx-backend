<?php
    if (old($name) !== "" && old($name) !== null) {
        $value = old($name);
    }
?>

<textarea name="{{ $name }}" class="form-control" rows="8" style="width: 40%;">{{ $value }}</textarea>

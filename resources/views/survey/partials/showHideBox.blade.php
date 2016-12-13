<h3 style="display: inline">{{ $title }}</h3>
<a class="textButton" onclick="toggleShowHideBox('{{ $boxName }}')"><span class="glyphicon glyphicon-plus"></span></a>
<br>
<div id="{{ $boxName }}" style="display: none">
    @yield('boxContent')
</div>

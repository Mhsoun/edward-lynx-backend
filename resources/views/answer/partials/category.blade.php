<!-- Main category -->
@if ($showCategory)
	<h3>{!! \App\EmailContentParser::parse($category->title, $parserData, true) !!}</h3>
	@if ($category->description != "")
		<p>
			{!! \App\EmailContentParser::parse($category->description, $parserData) !!}
		</p>
	@endif
@endif

@include('answer.partials.questions', ['category' => $category])

<!-- Child categories -->
@foreach ($category->childCategories as $childCategory)
    <h4>{!! \App\EmailContentParser::parse($childCategory->title, $parserData, true) !!}</h4>
    @if ($childCategory->description != "")
		<p>
			{!! \App\EmailContentParser::parse($childCategory->description, $parserData) !!}
		</p>
	@endif

    @include('answer.partials.questions', ['category' => $childCategory])
@endforeach

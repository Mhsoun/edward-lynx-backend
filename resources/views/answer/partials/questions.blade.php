<table class="table">
    @foreach ($category->questions as $question)
        @include('answer.partials.question', ['question' => $question])
    @endforeach
</table>

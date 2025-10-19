<h3 class="text-lg font-semibold">{{ $question->question_text }}</h3>
{{-- Add an ID to the form for easier selection in JavaScript --}}
<form id="question-form" action="{{ route('quiz.answer', [$quiz, $question]) }}" method="POST" class="mt-4">
	@csrf
	<div class="space-y-4">
		@foreach ($question->options as $option)
			<div class="form-control">
				<label class="label cursor-pointer justify-start gap-4">
					<input type="radio" name="answer" value="{{ $option }}" class="radio checked:bg-blue-500" required />
					<span class="label-text">{{ $option }}</span>
				</label>
			</div>
		@endforeach
	</div>
	<div class="mt-6">
		<button type="submit" class="btn btn-primary">Submit Answer</button>
	</div>
</form>

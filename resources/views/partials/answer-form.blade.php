<form id="question-form" action="{{ route('quiz.answer', [$quiz, $question]) }}" method="POST">
	@csrf
	<div class="space-y-4">
		@foreach ($question->options as $option)
			<div class="form-control question-option text-xl">
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

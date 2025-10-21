<h3 class="text-lg font-semibold">{{ $question->question_text }}</h3>
{{--
    Modified: All inline style attributes have been removed.
    This allows the JavaScript in the parent view to fully control the layout (either ordered or floating)
    without conflicting styles. The form is now a clean structure.
--}}
<form id="question-form" action="{{ route('quiz.answer', [$quiz, $question]) }}" method="POST" class="mt-4">
	@csrf
	<div class="space-y-4">
		@foreach ($question->options as $option)
			{{-- The 'question-option' class is used as a selector in JavaScript. --}}
			<div class="form-control question-option">
				<label class="label cursor-pointer justify-start gap-4">
					<input type="radio" name="answer" value="{{ $option }}" class="radio checked:bg-blue-500" required />
					<span class="label-text">{{ $option }}</span>
				</label>
			</div>
		@endforeach
	</div>
	{{-- This button is now positioned by default CSS, but can be moved by JS in floating mode. --}}
	<div class="mt-6">
		<button type="submit" class="btn btn-primary">Submit Answer</button>
	</div>
</form>

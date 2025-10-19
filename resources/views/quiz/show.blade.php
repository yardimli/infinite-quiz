@extends('layouts.app')

@section('header')
	<h2 class="font-semibold text-xl text-base-content leading-tight">
		Quiz: {{ $quiz->prompt }}
	</h2>
@endsection

@section('content')
	{{-- Calculate initial quiz stats --}}
	@php
		$correctCount = $quiz->questions->where('is_correct', true)->count();
		$goal = 50; // The goal for quiz completion
	@endphp
	
	<div class="py-12">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			
			{{-- Progress Bar Section --}}
			<div class="mb-4 p-4 bg-base-100 rounded-lg shadow-sm">
				<label for="quiz-progress" class="label">
					<span class="label-text font-semibold">Progress</span>
					<span class="label-text-alt font-semibold"><span id="progress-text">{{ $correctCount }}</span>/{{ $goal }} Correct</span>
				</label>
				<progress id="quiz-progress" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
			</div>
			
			<div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 min-h-[20rem] flex items-center justify-center">
					<div id="quiz-area">
						@if(isset($question))
							@include('partials.question', ['quiz' => $quiz, 'question' => $question])
						@endif
					</div>
					{{-- Feedback Area for Correct/Wrong Answers --}}
					<div id="feedback-area" class="hidden text-center">
						{{-- Content will be set dynamically by JavaScript --}}
					</div>
					<div id="spinner" class="hidden text-center">
						<span class="loading loading-spinner loading-lg"></span>
						<p class="mt-4">Generating next question...</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	{{-- Completion Dialog --}}
	<dialog id="completion-dialog" class="modal">
		<div class="modal-box">
			<h3 class="font-bold text-lg">Congratulations!</h3>
			<p class="py-4">You have completed the quiz by answering 50 questions correctly. Well done!</p>
			<div class="modal-action">
				<a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
			</div>
		</div>
	</dialog>
	
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const quizArea = document.getElementById('quiz-area');
			const spinner = document.getElementById('spinner');
			const feedbackArea = document.getElementById('feedback-area'); // New feedback element
			const generateUrl = "{{ route('quiz.generate', $quiz) }}";
			const progressBar = document.getElementById('quiz-progress');
			const progressText = document.getElementById('progress-text');
			const completionDialog = document.getElementById('completion-dialog');
			const goal = {{ $goal }};
			let correctAnswers = {{ $correctCount }};
			
			// Function to check for quiz completion
			function checkCompletion() {
				if (correctAnswers >= goal) {
					completionDialog.showModal();
				}
			}
			
			// Initial check in case the user reloads a completed quiz
			checkCompletion();
			
			// If quiz-area is empty, generate the first question
			if (quizArea.innerHTML.trim() === '') {
				generateNewQuestion();
			}
			
			// Use event delegation to handle dynamic form submission
			document.body.addEventListener('submit', function(event) {
				if (event.target.id === 'question-form') {
					event.preventDefault();
					submitAnswer(event.target);
				}
			});
			
			function submitAnswer(form) {
				const formData = new FormData(form);
				const actionUrl = form.action;
				
				// Disable form elements to prevent multiple submissions
				form.querySelectorAll('input, button').forEach(el => el.disabled = true);
				
				fetch(actionUrl, {
					method: 'POST',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest',
					},
					body: formData
				})
					.then(response => response.json())
					.then(data => {
						// Show feedback (correct/wrong)
						showFeedback(data.is_correct, data.correct_answer);
						
						// Update progress if the answer was correct
						if (data.is_correct) {
							correctAnswers = data.correct_count; // Update count from server response
							progressBar.value = correctAnswers;
							progressText.innerText = correctAnswers;
						}
						
						// Check if the quiz is complete
						checkCompletion();
						
						// Generate the next question after a short delay to show feedback
						setTimeout(() => {
							if (correctAnswers < goal) {
								generateNewQuestion();
							}
						}, 2000); // 2-second delay
					})
					.catch(error => {
						console.error('Error submitting answer:', error);
						quizArea.innerHTML = '<p class="text-red-500">An error occurred. Please refresh and try again.</p>';
					});
			}
			
			function showFeedback(isCorrect, correctAnswer) {
				quizArea.classList.add('hidden');
				feedbackArea.classList.remove('hidden');
				if (isCorrect) {
					feedbackArea.innerHTML = '<h2 class="text-2xl font-bold text-green-500">Correct!</h2>';
				} else {
					feedbackArea.innerHTML = `<h2 class="text-2xl font-bold text-red-500">Wrong!</h2><p class="mt-2">The correct answer was: <strong>${correctAnswer}</strong></p>`;
				}
			}
			
			function generateNewQuestion() {
				quizArea.classList.add('hidden');
				feedbackArea.classList.add('hidden'); // Hide feedback area
				spinner.classList.remove('hidden');
				
				fetch(generateUrl, {
					method: 'POST',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
						'Accept': 'application/json',
					}
				})
					.then(response => {
						if (!response.ok) {
							throw new Error('Network response was not ok');
						}
						return response.json();
					})
					.then(data => {
						if (data.error) {
							quizArea.innerHTML = `<p class="text-red-500">Error: ${data.error}</p>`;
						} else {
							quizArea.innerHTML = data.question_html;
						}
					})
					.catch(error => {
						console.error('Error generating question:', error);
						quizArea.innerHTML = '<p class="text-red-500">An error occurred while generating the question. Please try again.</p>';
					})
					.finally(() => {
						spinner.classList.add('hidden');
						quizArea.classList.remove('hidden');
					});
			}
		});
	</script>
@endsection

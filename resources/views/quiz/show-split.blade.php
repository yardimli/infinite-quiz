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
		$goal = $quiz->question_goal;
	@endphp
	
	<div class="py-6">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			
			{{-- Progress and Controls Bar --}}
			<div class="mb-4 p-4 bg-base-100 rounded-lg shadow-sm">
				<label for="quiz-progress" class="label">
					<span class="label-text font-semibold">Progress</span>
					<span class="label-text-alt font-semibold"><span id="progress-text">{{ $correctCount }}</span>/{{ $goal }} Correct</span>
				</label>
				<progress id="quiz-progress" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
			</div>
			
			{{-- Main Quiz Container --}}
			<div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
				<div id="quiz-container" class="relative p-6 min-h-[20rem]">
					{{-- New: Split screen layout --}}
					<div id="split-screen-area" class="grid grid-cols-2 gap-x-6 relative">
						{{-- Left Panel --}}
						<div id="quiz-panel-left" class="quiz-panel">
							{{-- Content will be injected by JS --}}
						</div>
						
						{{-- Divider --}}
						<div class="absolute top-0 bottom-0 left-1/2 w-px bg-base-300"></div>
						
						{{-- Right Panel --}}
						<div id="quiz-panel-right" class="quiz-panel">
							{{-- Content will be injected by JS --}}
						</div>
					</div>
					
					{{-- New: Centralized submit button area --}}
					<div id="submit-area" class="text-center mt-6" style="display: none;">
						<button id="submit-button" class="btn btn-primary">Submit Answer</button>
					</div>
					
					<div id="feedback-area" class="hidden text-center"></div>
					<div id="spinner" class="hidden text-center">
						<span class="loading loading-spinner loading-lg"></span>
						<p class="mt-4">Generating next question...</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<dialog id="completion-dialog" class="modal">
		<div class="modal-box">
			<h3 class="font-bold text-lg">Congratulations!</h3>
			<p class="py-4">You have completed the quiz by answering {{ $goal }} questions correctly. Well done!</p>
			<div class="modal-action">
				<a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
			</div>
		</div>
	</dialog>
	
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const splitScreenArea = document.getElementById('split-screen-area');
			const leftPanel = document.getElementById('quiz-panel-left');
			const rightPanel = document.getElementById('quiz-panel-right');
			const submitArea = document.getElementById('submit-area');
			const submitButton = document.getElementById('submit-button');
			const spinner = document.getElementById('spinner');
			const feedbackArea = document.getElementById('feedback-area');
			const generateUrl = "{{ route('quiz.generate', $quiz) }}";
			const progressBar = document.getElementById('quiz-progress');
			const progressText = document.getElementById('progress-text');
			const completionDialog = document.getElementById('completion-dialog');
			const goal = {{ $goal }};
			let correctAnswers = {{ $correctCount }};
			let currentQuestionData = null;
			
			// New: State for selected answers
			let leftSelection = null;
			let rightSelection = null;
			
			/**
			 * New: Checks if the selections in both panels match and shows/hides the submit button.
			 */
			function checkSelections() {
				const leftChecked = document.querySelector('#quiz-panel-left input[name="answer_left"]:checked');
				const rightChecked = document.querySelector('#quiz-panel-right input[name="answer_right"]:checked');
				
				leftSelection = leftChecked ? leftChecked.value : null;
				rightSelection = rightChecked ? rightChecked.value : null;
				
				if (leftSelection && rightSelection && leftSelection === rightSelection) {
					submitArea.style.display = 'block';
				} else {
					submitArea.style.display = 'none';
				}
			}
			
			/**
			 * New: Builds the HTML for a single quiz panel.
			 * @param {object} data - The question data.
			 * @param {string} side - 'left' or 'right' to create unique radio button names.
			 */
			function buildPanelHtml(data, side) {
				let optionsHtml = data.options.map(option => `
                    <div class="form-control question-option text-xl">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="radio" name="answer_${side}" value="${option}" class="radio checked:bg-blue-500" required />
                            <span class="label-text">${option}</span>
                        </label>
                    </div>
                `).join('');
				
				return `
                    <h3 class="text-3xl font-semibold">${data.question_text}</h3>
                    <div class="mt-4 space-y-4">${optionsHtml}</div>
                `;
			}
			
			function checkCompletion() {
				if (correctAnswers >= goal) {
					completionDialog.showModal();
				}
			}
			
			checkCompletion();
			
			// Initial question generation if none exists
			@if(!isset($question))
			generateNewQuestion();
			@else
			// New: If a question exists on page load, render it.
			currentQuestionData = {
				question_text: `{!! addslashes($question->question_text) !!}`,
				options: {!! json_encode($question->options) !!},
				form_action: "{{ route('quiz.answer', ['quiz' => $quiz, 'question' => $question]) }}"
			};
			leftPanel.innerHTML = buildPanelHtml(currentQuestionData, 'left');
			rightPanel.innerHTML = buildPanelHtml(currentQuestionData, 'right');
			@endif
			
			// New: Event listener for changes in the quiz panels
			splitScreenArea.addEventListener('change', checkSelections);
			
			// New: Event listener for the central submit button
			submitButton.addEventListener('click', function() {
				if (leftSelection) {
					submitAnswer(currentQuestionData.form_action, leftSelection);
				}
			});
			
			function submitAnswer(actionUrl, selectedAnswer) {
				submitButton.disabled = true;
				
				const formData = new FormData();
				formData.append('answer', selectedAnswer);
				
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
						showFeedback(data.is_correct, data.correct_answer);
						
						if (data.is_correct) {
							correctAnswers = data.correct_count;
							progressBar.value = correctAnswers;
							progressText.innerText = correctAnswers;
						}
						
						checkCompletion();
						
						setTimeout(() => {
							if (correctAnswers < goal) {
								generateNewQuestion();
							}
						}, 2000);
					})
					.catch(error => {
						console.error('Error submitting answer:', error);
						splitScreenArea.innerHTML = '<p class="text-red-500 col-span-2">An error occurred. Please refresh and try again.</p>';
					});
			}
			
			function showFeedback(isCorrect, correctAnswer) {
				splitScreenArea.classList.add('hidden');
				submitArea.style.display = 'none';
				feedbackArea.classList.remove('hidden');
				if (isCorrect) {
					feedbackArea.innerHTML = '<h2 class="text-2xl font-bold text-green-500">Correct!</h2>';
				} else {
					feedbackArea.innerHTML = `<h2 class="text-2xl font-bold text-red-500">Wrong!</h2><p class="mt-2">The correct answer was: <strong>${correctAnswer}</strong></p>`;
				}
			}
			
			function generateNewQuestion() {
				splitScreenArea.classList.add('hidden');
				feedbackArea.classList.add('hidden');
				submitArea.style.display = 'none';
				spinner.classList.remove('hidden');
				
				// Reset selections
				leftSelection = null;
				rightSelection = null;
				
				fetch(generateUrl, {
					method: 'POST',
					headers: {
						'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
						'Accept': 'application/json',
					}
				})
					.then(response => {
						if (!response.ok) throw new Error('Network response was not ok');
						return response.json();
					})
					.then(data => {
						if (data.error) {
							splitScreenArea.innerHTML = `<p class="text-red-500 col-span-2">Error: ${data.error}</p>`;
						} else {
							currentQuestionData = data;
							leftPanel.innerHTML = buildPanelHtml(data, 'left');
							rightPanel.innerHTML = buildPanelHtml(data, 'right');
						}
					})
					.catch(error => {
						console.error('Error generating question:', error);
						splitScreenArea.innerHTML = '<p class="text-red-500 col-span-2">An error occurred while generating the question. Please try again.</p>';
					})
					.finally(() => {
						spinner.classList.add('hidden');
						splitScreenArea.classList.remove('hidden');
						submitButton.disabled = false;
					});
			}
		});
	</script>
@endsection

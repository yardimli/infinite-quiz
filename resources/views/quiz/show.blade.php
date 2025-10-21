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
	
	<div class="py-12">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			
			{{-- Modified: Added a checkbox to toggle the answer layout. --}}
			<div class="mb-4 p-4 bg-base-100 rounded-lg shadow-sm">
				<label for="quiz-progress" class="label">
					<span class="label-text font-semibold">Progress</span>
					<span class="label-text-alt font-semibold"><span id="progress-text">{{ $correctCount }}</span>/{{ $goal }} Correct</span>
				</label>
				<progress id="quiz-progress" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
				
				{{-- Added: A checkbox to control the answer layout, with persistence via localStorage. --}}
				<div class="form-control mt-4">
					<label class="label cursor-pointer justify-start gap-4">
						<input type="checkbox" id="floating-answers-toggle" class="checkbox checkbox-primary" />
						<span class="label-text">Enable Floating Answers</span>
					</label>
				</div>
			</div>
			
			<div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 min-h-[20rem] flex items-center justify-center">
					<div id="quiz-area">
						@if(isset($question))
							@include('partials.question', ['quiz' => $quiz, 'question' => $question])
						@endif
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
			const quizArea = document.getElementById('quiz-area');
			const spinner = document.getElementById('spinner');
			const feedbackArea = document.getElementById('feedback-area');
			const generateUrl = "{{ route('quiz.generate', $quiz) }}";
			const progressBar = document.getElementById('quiz-progress');
			const progressText = document.getElementById('progress-text');
			const completionDialog = document.getElementById('completion-dialog');
			const goal = {{ $goal }};
			let correctAnswers = {{ $correctCount }};
			
			// --- Modified: Added comprehensive layout management logic ---
			const floatingAnswersToggle = document.getElementById('floating-answers-toggle');
			const layoutStorageKey = 'quizLayoutFloating';
			
			/**
			 * Removes all inline styles to revert the form to a standard ordered list.
			 * @param {HTMLElement} container - The element containing the question form.
			 */
			function resetAnswerLayout(container) {
				const form = container.querySelector('#question-form');
				if (!form) return;
				
				const options = form.querySelectorAll('.question-option');
				options.forEach(option => {
					option.style.position = '';
					option.style.top = '';
					option.style.left = '';
					option.style.visibility = '';
				});
				
				form.style.position = '';
				form.style.width = '';
				form.style.height = '';
				
				const submitContainer = form.querySelector('button[type="submit"]').parentElement;
				if (submitContainer) {
					submitContainer.style.position = '';
					submitContainer.style.left = '';
					submitContainer.style.top = '';
				}
			}
			
			/**
			 * Applies absolute positioning to randomize the layout of answer options.
			 * @param {HTMLElement} container - The element containing the question form.
			 */
			function positionAnswers(container) {
				const form = container.querySelector('#question-form');
				if (!form) return;
				
				// Set the form as the positioning canvas.
				form.style.position = 'relative';
				form.style.width = '600px';
				form.style.height = '250px';
				
				// Position the submit button within the canvas.
				const submitContainer = form.querySelector('button[type="submit"]').parentElement;
				if (submitContainer) {
					submitContainer.style.position = 'absolute';
					submitContainer.style.left = '0';
					submitContainer.style.top = '200px';
				}
				
				const options = form.querySelectorAll('.question-option');
				const containerWidth = 500;
				const containerHeight = 200;
				const placedElements = [];
				
				function checkOverlap(rect1, rect2) {
					const padding = 5;
					return (
						rect1.left < rect2.right + padding &&
						rect1.right > rect2.left - padding &&
						rect1.top < rect2.bottom + padding &&
						rect1.bottom > rect2.top - padding
					);
				}
				
				options.forEach(option => {
					option.style.position = 'absolute'; // Enable absolute positioning.
					option.style.visibility = 'hidden';
					const optionWidth = option.offsetWidth;
					const optionHeight = option.offsetHeight;
					let newPos, overlaps, attempts = 0;
					
					do {
						const randomTop = Math.floor(Math.random() * (containerHeight - optionHeight));
						const randomLeft = Math.floor(Math.random() * (containerWidth - optionWidth));
						newPos = { top: randomTop, left: randomLeft, right: randomLeft + optionWidth, bottom: randomTop + optionHeight };
						overlaps = placedElements.some(placed => checkOverlap(newPos, placed));
						attempts++;
					} while (overlaps && attempts < 100);
					
					option.style.top = `${newPos.top}px`;
					option.style.left = `${newPos.left}px`;
					option.style.visibility = 'visible';
					placedElements.push(newPos);
				});
			}
			
			/**
			 * Checks the toggle state and applies the corresponding layout.
			 * @param {HTMLElement} container - The element containing the question form.
			 */
			function applyAnswerLayout(container) {
				if (floatingAnswersToggle.checked) {
					positionAnswers(container);
				} else {
					resetAnswerLayout(container);
				}
			}
			
			// Event listener to switch layouts when the checkbox is toggled.
			floatingAnswersToggle.addEventListener('change', function() {
				localStorage.setItem(layoutStorageKey, this.checked);
				applyAnswerLayout(quizArea);
			});
			
			// On page load, set the checkbox and layout based on stored preference or default to true.
			const savedLayoutPreference = localStorage.getItem(layoutStorageKey);
			floatingAnswersToggle.checked = savedLayoutPreference === null ? true : (savedLayoutPreference === 'true');
			// --- End of layout management logic ---
			
			function checkCompletion() {
				if (correctAnswers >= goal) {
					completionDialog.showModal();
				}
			}
			
			checkCompletion();
			
			// If a question exists on load, apply the selected layout. Otherwise, generate the first question.
			if (quizArea.innerHTML.trim() !== '') {
				applyAnswerLayout(quizArea);
			} else {
				generateNewQuestion();
			}
			
			document.body.addEventListener('submit', function(event) {
				if (event.target.id === 'question-form') {
					event.preventDefault();
					submitAnswer(event.target);
				}
			});
			
			function submitAnswer(form) {
				const formData = new FormData(form);
				const actionUrl = form.action;
				
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
				feedbackArea.classList.add('hidden');
				spinner.classList.remove('hidden');
				
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
							quizArea.innerHTML = `<p class="text-red-500">Error: ${data.error}</p>`;
						} else {
							quizArea.innerHTML = data.question_html;
							// Modified: Apply the user-selected layout to the new question.
							applyAnswerLayout(quizArea);
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

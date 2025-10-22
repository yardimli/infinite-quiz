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
	
	<canvas id="jeelizGlanceTrackerCanvas" class="fixed bottom-4 right-4 w-48 h-36 z-20 rounded-lg shadow-lg border-2 border-base-100" style="display: none;"></canvas>
	
	<div class="py-6">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			
			<div class="mb-4 p-4 bg-base-100 rounded-lg shadow-sm">
				<label for="quiz-progress" class="label">
					<span class="label-text font-semibold">Progress</span>
					<span class="label-text-alt font-semibold"><span id="progress-text">{{ $correctCount }}</span>/{{ $goal }} Correct</span>
				</label>
				<progress id="quiz-progress" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
				
				{{-- Modified: The "Floating Answers" toggle has been removed as this view is dedicated to the list layout. --}}
				<div class="mt-4 border-t border-base-300 pt-4 flex flex-wrap items-center gap-x-8 gap-y-2">
					{{-- Gaze Tracking Toggle --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="gaze-tracking-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Enable Gaze Tracking</span>
						</label>
					</div>
					
					{{-- Added: Checkbox to control the visibility of the video feed. --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="gaze-display-video-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Display Video Feed</span>
						</label>
					</div>
					
					{{-- Modified: Gaze Delay Input replaced with Sensibility Slider --}}
					<div class="form-control">
						<label class="label justify-start gap-x-2">
							<span class="label-text">Sensibility:</span>
							<input type="range" id="gaze-sensibility-input" class="range range-xs w-24" value="0.2" min="0" max="1" step="0.1" />
							<span id="gaze-sensibility-value" class="label-text font-mono w-8 text-center">0.2</span>
						</label>
					</div>
				</div>
			</div>
			
			<div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
				<div id="quiz-container" class="relative p-6 min-h-[20rem] flex items-center justify-center">
					<div id="gaze-overlay" class="absolute inset-0 bg-base-300/20 z-10 hidden items-center justify-center text-center rounded-lg">
						<div>
							<p class="text-xl font-semibold">look at the screen.</p>
							<span class="loading loading-dots loading-md mt-4"></span>
						</div>
					</div>
					
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
	
	{{-- Added: Include the Jeeliz Glance Tracker library from CDN. --}}
	<script src="/js/jeelizGlanceTracker.js"></script>
	
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
			
			// --- Modified: All floating layout management JavaScript has been removed for this view. ---
			
			// --- Gaze Tracking Logic ---
			const gazeTrackingToggle = document.getElementById('gaze-tracking-toggle');
			const gazeDisplayVideoToggle = document.getElementById('gaze-display-video-toggle');
			const gazeSensibilityInput = document.getElementById('gaze-sensibility-input');
			const gazeSensibilityValue = document.getElementById('gaze-sensibility-value');
			const gazeOverlay = document.getElementById('gaze-overlay');
			const gazeTrackerCanvas = document.getElementById('jeelizGlanceTrackerCanvas');
			const gazeStorageKey = 'quizGazeTrackingEnabled';
			const displayVideoStorageKey = 'quizGazeDisplayVideoEnabled';
			const gazeSensibilityStorageKey = 'quizGazeTrackingSensibility';
			let isGazeTrackerInitialized = false;
			
			function showGazeOverlay() {
				gazeOverlay.classList.remove('hidden');
				gazeOverlay.classList.add('flex');
			}
			
			function hideGazeOverlay() {
				gazeOverlay.classList.add('hidden');
				gazeOverlay.classList.remove('flex');
			}
			
			function initializeGazeTracker() {
				if (isGazeTrackerInitialized) {
					JEELIZGLANCETRACKER.toggle_pause(false, true);
					return;
				}
				
				const sensibility = parseFloat(gazeSensibilityInput.value);
				const isDisplayVideo = gazeDisplayVideoToggle.checked;
				
				JEELIZGLANCETRACKER.init({
					canvasId: 'jeelizGlanceTrackerCanvas',
					NNCPath: '/js/',
					sensibility: sensibility,
					isDisplayVideo: isDisplayVideo,
					callbackTrack: function (isWatching) {
						if (isWatching) {
							hideGazeOverlay();
						} else {
							showGazeOverlay();
						}
					},
					callbackReady: (error) => {
						if (error) {
							console.error('Gaze Tracker initialization error:', error);
							gazeTrackingToggle.checked = false;
							gazeTrackingToggle.disabled = true;
							gazeSensibilityInput.disabled = true;
							gazeDisplayVideoToggle.disabled = true;
							alert('Could not initialize Gaze Tracker. Please ensure you have granted camera access.');
							return;
						}
						isGazeTrackerInitialized = true;
						JEELIZGLANCETRACKER.toggle_pause(false, true);
						showGazeOverlay();
					}
				});
			}
			
			function pauseGazeTracker() {
				if (isGazeTrackerInitialized) {
					JEELIZGLANCETRACKER.toggle_pause(true, true);
				}
				hideGazeOverlay();
				gazeTrackerCanvas.style.display = 'none';
			}
			
			gazeTrackingToggle.addEventListener('change', function() {
				localStorage.setItem(gazeStorageKey, this.checked);
				if (this.checked) {
					gazeTrackerCanvas.style.display = 'block';
					initializeGazeTracker();
				} else {
					pauseGazeTracker();
				}
			});
			
			gazeDisplayVideoToggle.addEventListener('change', function() {
				localStorage.setItem(displayVideoStorageKey, this.checked);
				location.reload();
			});
			
			gazeSensibilityInput.addEventListener('input', function() {
				gazeSensibilityValue.textContent = parseFloat(this.value).toFixed(1);
			});
			
			gazeSensibilityInput.addEventListener('change', function() {
				localStorage.setItem(gazeSensibilityStorageKey, this.value);
				location.reload();
			});
			
			const savedGazePreference = localStorage.getItem(gazeStorageKey);
			const savedGazeSensibility = localStorage.getItem(gazeSensibilityStorageKey);
			const savedDisplayVideoPreference = localStorage.getItem(displayVideoStorageKey);
			
			if (savedGazeSensibility) {
				const sensibility = parseFloat(savedGazeSensibility).toFixed(1);
				gazeSensibilityInput.value = sensibility;
				gazeSensibilityValue.textContent = sensibility;
			}
			
			gazeDisplayVideoToggle.checked = savedDisplayVideoPreference === null ? true : (savedDisplayVideoPreference === 'true');
			gazeTrackingToggle.checked = savedGazePreference === 'true';
			
			if (gazeTrackingToggle.checked) {
				gazeTrackerCanvas.style.display = 'block';
				initializeGazeTracker();
			} else {
				gazeTrackerCanvas.style.display = 'none';
			}
			// --- End of Gaze Tracking Logic ---
			
			/**
			 * Modified: Handles the "Slow Question Show" feature, now with configurable word count.
			 * If enabled, it reveals the question word by word (or in chunks).
			 * @param {HTMLElement} container - The element containing the question.
			 */
			function initSlowShow(container) {
				const slowShowEnabled = localStorage.getItem('slowShowEnabled') === 'true';
				// New: Get the number of words to show per click from localStorage.
				const wordsPerClick = parseInt(localStorage.getItem('slowShowWords') || '1', 10);
				
				if (!slowShowEnabled) {
					return; // Exit if the feature is not enabled.
				}
				
				const questionTextElement = container.querySelector('#question-text');
				const questionForm = container.querySelector('#question-form');
				
				if (!questionTextElement || !questionForm) {
					return; // Exit if necessary elements aren't found.
				}
				
				// Hide the answers form initially.
				questionForm.style.visibility = 'hidden';
				
				const words = questionTextElement.textContent.trim().split(/\s+/);
				questionTextElement.innerHTML = ''; // Clear the original text.
				
				// Wrap each word in a span for individual control.
				words.forEach(word => {
					const span = document.createElement('span');
					span.textContent = word + ' '; // Add space back.
					span.classList.add('transition-opacity', 'duration-300', 'opacity-5');
					questionTextElement.appendChild(span);
				});
				
				const wordSpans = Array.from(questionTextElement.querySelectorAll('span'));
				if (wordSpans.length === 0) {
					questionForm.style.visibility = 'visible';
					return; // No words to process.
				}
				
				let currentWordIndex = 0;
				
				// Create the 'next word' button.
				const nextWordButton = document.createElement('button');
				nextWordButton.innerHTML = '&#9660;'; // Down arrow symbol.
				nextWordButton.classList.add('btn', 'btn-primary', 'btn-circle', 'btn-sm', 'absolute');
				nextWordButton.style.transition = 'left 0.2s ease-out, top 0.2s ease-out';
				
				// The container for the question text needs to be relative for absolute positioning of the button.
				container.style.position = 'relative';
				container.appendChild(nextWordButton);
				
				function positionButton() {
					if (currentWordIndex >= wordSpans.length) return;
					
					const currentSpan = wordSpans[currentWordIndex];
					const parentRect = container.getBoundingClientRect();
					const spanRect = currentSpan.getBoundingClientRect();
					
					// Calculate position relative to the container.
					const top = (spanRect.bottom - parentRect.top) + 5; // 5px below the word.
					const left = (spanRect.left - parentRect.left) + (spanRect.width / 2) - (nextWordButton.offsetWidth / 2); // Centered under the word.
					
					nextWordButton.style.top = `${top}px`;
					nextWordButton.style.left = `${left}px`;
				}
				
				/**
				 * Modified: This function now reveals a batch of words based on the 'wordsPerClick' setting.
				 */
				function advanceWord() {
					if (currentWordIndex < wordSpans.length) {
						// New: Calculate the end index for the batch of words to reveal.
						const endIndex = Math.min(currentWordIndex + wordsPerClick, wordSpans.length);
						
						// New: Loop through the batch and reveal each word.
						for (let i = currentWordIndex; i < endIndex; i++) {
							wordSpans[i].classList.remove('opacity-5');
							wordSpans[i].classList.add('opacity-100');
						}
						
						// New: Update the current word index to the new position.
						currentWordIndex = endIndex;
						
						if (currentWordIndex < wordSpans.length) {
							// If there's a next word, reposition the button.
							positionButton();
						} else {
							// Last word has been revealed.
							nextWordButton.remove();
							questionForm.style.visibility = 'visible';
						}
					}
				}
				
				nextWordButton.addEventListener('click', advanceWord);
				
				// Use a short timeout to ensure the DOM has rendered the spans before calculating their positions.
				setTimeout(positionButton, 100);
			}
			
			function checkCompletion() {
				if (correctAnswers >= goal) {
					completionDialog.showModal();
				}
			}
			
			checkCompletion();
			
			// If no question exists on load, generate the first one.
			if (quizArea.innerHTML.trim() === '') {
				generateNewQuestion();
			} else {
				// Modified: Initialize slow show for the first question if it exists.
				initSlowShow(quizArea);
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
							// Modified: Initialize slow show for the new question.
							initSlowShow(quizArea);
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

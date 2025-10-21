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
	
	{{-- Added: The canvas required by the gaze tracker library. It's positioned at the bottom right. --}}
	<canvas id="jeelizGlanceTrackerCanvas" class="fixed bottom-4 right-4 w-48 h-36 z-20 rounded-lg shadow-lg border-2 border-base-100" style="display: none;"></canvas>
	
	<div class="py-6">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			
			<div class="mb-4 p-4 bg-base-100 rounded-lg shadow-sm">
				<label for="quiz-progress" class="label">
					<span class="label-text font-semibold">Progress</span>
					<span class="label-text-alt font-semibold"><span id="progress-text">{{ $correctCount }}</span>/{{ $goal }} Correct</span>
				</label>
				<progress id="quiz-progress" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
				
				{{-- Modified: The layout controls are now in a single horizontal flex container. --}}
				<div class="mt-4 border-t border-base-300 pt-4 flex flex-wrap items-center gap-x-8 gap-y-2">
					{{-- Floating Answers Toggle --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="floating-answers-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Enable Floating Answers</span>
						</label>
					</div>
					
					{{-- Gaze Tracking Toggle --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="gaze-tracking-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Enable Gaze Tracking</span>
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
				{{-- Modified: Added id="quiz-container" and position relative for the gaze tracking overlay. --}}
				<div id="quiz-container" class="relative p-6 min-h-[20rem] flex items-center justify-center">
					{{-- Added: Gaze tracking overlay that covers the quiz area when the user looks away. --}}
					<div id="gaze-overlay" class="absolute inset-0 bg-base-300/90 z-10 hidden items-center justify-center text-center rounded-lg">
						<div>
							<p class="text-xl font-semibold">Please look at the screen to continue.</p>
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
				form.style.height = '300px';
				
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
			
			// --- Modified: Gaze Tracking Logic ---
			const gazeTrackingToggle = document.getElementById('gaze-tracking-toggle');
			// New: Selectors for the new sensibility input and its value display.
			const gazeSensibilityInput = document.getElementById('gaze-sensibility-input');
			const gazeSensibilityValue = document.getElementById('gaze-sensibility-value');
			const gazeOverlay = document.getElementById('gaze-overlay');
			const gazeTrackerCanvas = document.getElementById('jeelizGlanceTrackerCanvas');
			const gazeStorageKey = 'quizGazeTrackingEnabled';
			// New: Storage key for the sensibility value.
			const gazeSensibilityStorageKey = 'quizGazeTrackingSensibility';
			let isGazeTrackerInitialized = false;
			
			/**
			 * Shows the gaze overlay.
			 */
			function showGazeOverlay() {
				gazeOverlay.classList.remove('hidden');
				gazeOverlay.classList.add('flex'); // Use flex to center content.
			}
			
			/**
			 * Hides the gaze overlay.
			 */
			function hideGazeOverlay() {
				gazeOverlay.classList.add('hidden');
				gazeOverlay.classList.remove('flex');
			}
			
			/**
			 * Initializes the Jeeliz Glance Tracker library.
			 */
			function initializeGazeTracker() {
				if (isGazeTrackerInitialized) {
					JEELIZGLANCETRACKER.toggle_pause(false, true); // Resume if already initialized.
					return;
				}
				
				// New: Read sensibility from the input for initialization.
				const sensibility = parseFloat(gazeSensibilityInput.value);
				
				JEELIZGLANCETRACKER.init({
					canvasId: 'jeelizGlanceTrackerCanvas',
					NNCPath: '/js/',
					// Modified: Use the dynamic sensibility value.
					sensibility: sensibility,
					isDisplayVideo: true,
					callbackTrack: function (isWatching) {
						if (isWatching) {
							console.log("Gaze ON");
							hideGazeOverlay();
						} else {
							console.log("Gaze OFF");
							showGazeOverlay();
						}
					},
					callbackReady: (error) => {
						if (error) {
							console.error('Gaze Tracker initialization error:', error);
							gazeTrackingToggle.checked = false;
							gazeTrackingToggle.disabled = true;
							gazeSensibilityInput.disabled = true;
							alert('Could not initialize Gaze Tracker. Please ensure you have granted camera access.');
							return;
						}
						console.log('Gaze Tracker is ready.');
						isGazeTrackerInitialized = true;
						JEELIZGLANCETRACKER.toggle_pause(false, true); // Start the tracker.
						showGazeOverlay(); // Show overlay initially until gaze is detected.
					}
				});
			}
			
			/**
			 * Pauses the gaze tracker and hides the overlay and canvas.
			 */
			function pauseGazeTracker() {
				if (isGazeTrackerInitialized) {
					JEELIZGLANCETRACKER.toggle_pause(true, true);
				}
				hideGazeOverlay();
				gazeTrackerCanvas.style.display = 'none';
			}
			
			// Event listener for the gaze tracking toggle.
			gazeTrackingToggle.addEventListener('change', function() {
				localStorage.setItem(gazeStorageKey, this.checked);
				if (this.checked) {
					gazeTrackerCanvas.style.display = 'block';
					initializeGazeTracker();
				} else {
					pauseGazeTracker();
				}
			});
			
			// Modified: Event listener for the sensibility slider.
			gazeSensibilityInput.addEventListener('input', function() {
				// Update the displayed value as the slider moves.
				gazeSensibilityValue.textContent = parseFloat(this.value).toFixed(1);
			});
			
			// New: Event listener to save the value and refresh when the user finishes changing the slider.
			gazeSensibilityInput.addEventListener('change', function() {
				localStorage.setItem(gazeSensibilityStorageKey, this.value);
				// Refresh the page to re-initialize the tracker with the new value.
				location.reload();
			});
			
			// On page load, set up gaze tracking based on stored preferences.
			const savedGazePreference = localStorage.getItem(gazeStorageKey);
			// New: Retrieve the saved sensibility value.
			const savedGazeSensibility = localStorage.getItem(gazeSensibilityStorageKey);
			
			// New: Set the slider and text to the saved value, or the default.
			if (savedGazeSensibility) {
				const sensibility = parseFloat(savedGazeSensibility).toFixed(1);
				gazeSensibilityInput.value = sensibility;
				gazeSensibilityValue.textContent = sensibility;
			}
			
			gazeTrackingToggle.checked = savedGazePreference === 'true'; // Default to false.
			
			// Initialize if it was enabled on last visit.
			if (gazeTrackingToggle.checked) {
				gazeTrackerCanvas.style.display = 'block';
				initializeGazeTracker();
			} else {
				gazeTrackerCanvas.style.display = 'none';
			}
			// --- End of Gaze Tracking Logic ---
			
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

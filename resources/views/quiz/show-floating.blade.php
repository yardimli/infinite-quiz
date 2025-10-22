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
				
				{{-- Modified: The controls section now includes padding inputs for the floating layout. --}}
				<div class="mt-4 border-t border-base-300 pt-4 flex flex-wrap items-center gap-x-8 gap-y-2">
					{{-- Gaze Tracking Toggle --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="gaze-tracking-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Enable Gaze Tracking</span>
						</label>
					</div>
					
					{{-- Checkbox to control the visibility of the video feed. --}}
					<div class="form-control">
						<label class="label cursor-pointer justify-start gap-4">
							<input type="checkbox" id="gaze-display-video-toggle" class="checkbox checkbox-primary" />
							<span class="label-text">Display Video Feed</span>
						</label>
					</div>
					
					{{-- Gaze Delay Input replaced with Sensibility Slider --}}
					<div class="form-control">
						<label class="label justify-start gap-x-2">
							<span class="label-text">Sensibility:</span>
							<input type="range" id="gaze-sensibility-input" class="range range-xs w-24" value="0.2" min="0" max="1" step="0.1" />
							<span id="gaze-sensibility-value" class="label-text font-mono w-8 text-center">0.2</span>
						</label>
					</div>
					
					{{-- Added: Inputs for horizontal and vertical padding for floating answers. --}}
					<div class="form-control">
						<label class="label justify-start gap-x-2">
							<span class="label-text">H-Padding:</span>
							<input type="number" id="h-padding-input" class="input input-bordered input-sm w-20" value="15" min="0" max="100">
						</label>
					</div>
					<div class="form-control">
						<label class="label justify-start gap-x-2">
							<span class="label-text">V-Padding:</span>
							<input type="number" id="v-padding-input" class="input input-bordered input-sm w-20" value="15" min="0" max="100">
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
			
			// --- Modified: Layout management logic for floating answers only. ---
			const hPaddingInput = document.getElementById('h-padding-input'); // Added
			const vPaddingInput = document.getElementById('v-padding-input'); // Added
			const hPaddingStorageKey = 'quizHPadding'; // Added
			const vPaddingStorageKey = 'quizVPadding'; // Added
			
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
				
				/**
				 * Modified: checkOverlap now uses configurable padding values from the new inputs.
				 * @param {object} rect1 - The bounding box of the first element.
				 * @param {object} rect2 - The bounding box of the second element.
				 * @returns {boolean} - True if the elements overlap.
				 */
				function checkOverlap(rect1, rect2) {
					// New: Get horizontal and vertical padding values from the input fields, with a default fallback.
					const hPadding = parseInt(hPaddingInput.value, 10) || 5;
					const vPadding = parseInt(vPaddingInput.value, 10) || 5;
					return (
						rect1.left < rect2.right + hPadding &&
						rect1.right > rect2.left - hPadding &&
						rect1.top < rect2.bottom + vPadding &&
						rect1.bottom > rect2.top - vPadding
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
			 * Applies the floating layout.
			 * @param {HTMLElement} container - The element containing the question form.
			 */
			function applyAnswerLayout(container) {
				positionAnswers(container);
			}
			// --- End of layout management logic ---
			
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
			
			// Added: Event listeners and localStorage logic for padding inputs.
			hPaddingInput.addEventListener('change', function() {
				localStorage.setItem(hPaddingStorageKey, this.value);
				if (quizArea.innerHTML.trim() !== '' && spinner.classList.contains('hidden')) {
					applyAnswerLayout(quizArea); // Re-apply layout when padding changes.
				}
			});
			
			vPaddingInput.addEventListener('change', function() {
				localStorage.setItem(vPaddingStorageKey, this.value);
				if (quizArea.innerHTML.trim() !== '' && spinner.classList.contains('hidden')) {
					applyAnswerLayout(quizArea); // Re-apply layout when padding changes.
				}
			});
			
			const savedHPadding = localStorage.getItem(hPaddingStorageKey);
			const savedVPadding = localStorage.getItem(vPaddingStorageKey);
			
			if (savedHPadding) {
				hPaddingInput.value = savedHPadding;
			}
			
			if (savedVPadding) {
				vPaddingInput.value = savedVPadding;
			}
			
			/**
			 * Handles the "Slow Question Show" feature, now with configurable word count.
			 * If enabled, it reveals the question word by word (or in chunks).
			 * @param {HTMLElement} container - The element containing the question.
			 */
			function initSlowShow(container) {
				const slowShowEnabled = localStorage.getItem('slowShowEnabled') === 'true';
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
				 * This function now reveals a batch of words based on the 'wordsPerClick' setting.
				 */
				function advanceWord() {
					if (currentWordIndex < wordSpans.length) {
						const endIndex = Math.min(currentWordIndex + wordsPerClick, wordSpans.length);
						
						for (let i = currentWordIndex; i < endIndex; i++) {
							wordSpans[i].classList.remove('opacity-5');
							wordSpans[i].classList.add('opacity-100');
						}
						
						currentWordIndex = endIndex;
						
						if (currentWordIndex < wordSpans.length) {
							positionButton();
						} else {
							nextWordButton.remove();
							questionForm.style.visibility = 'visible';
						}
					}
				}
				
				nextWordButton.addEventListener('click', advanceWord);
				
				setTimeout(positionButton, 100);
			}
			
			function checkCompletion() {
				if (correctAnswers >= goal) {
					completionDialog.showModal();
				}
			}
			
			checkCompletion();
			
			if (quizArea.innerHTML.trim() !== '') {
				applyAnswerLayout(quizArea);
				initSlowShow(quizArea);
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
							requestAnimationFrame(() => {
								applyAnswerLayout(quizArea);
								initSlowShow(quizArea);
							});
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

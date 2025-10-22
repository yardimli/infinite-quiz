@extends('layouts.app')


@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('quiz.store') }}" method="POST" class="max-w-4xl mx-auto">
                        @csrf
                        <div class="form-control">
                            <label class="label" for="prompt">
                                <span class="label-text">What subject do you want a quiz on?</span>
                            </label>
                            <textarea id="prompt" class="textarea textarea-bordered h-20 w-full" name="prompt" placeholder="e.g., 'The Roman Empire' or 'Quantum Physics'" required>{{ old('prompt') }}</textarea>
                        </div>
                        
                        {{-- Modified: Grouped inputs for questions, answers, and model into a single row. --}}
                        <div class="flex flex-col md:flex-row gap-4 mt-4">
                            
                            {{-- Input for the user to define the quiz length. --}}
                            <div class="form-control flex-1">
                                <label class="label" for="question_goal">
                                    <span class="label-text"># of Questions</span>
                                </label>
                                <input type="number" id="question_goal" name="question_goal" class="input input-bordered w-full" value="20" min="1" max="100" required>
                            </div>
                            
                            {{-- Added: Input for the user to define the number of answers per question. --}}
                            <div class="form-control flex-1">
                                <label class="label" for="answer_count">
                                    <span class="label-text"># of Answers</span>
                                </label>
                                <input type="number" id="answer_count" name="answer_count" class="input input-bordered w-full" value="4" min="2" max="6" required>
                            </div>
                            
                            {{-- The select input for the AI model --}}
                            <div class="form-control flex-1">
                                <label class="label" for="llm_model">
                                    <span class="label-text">AI Model</span>
                                </label>
                                <select name="llm_model" id="llm_model" class="select select-bordered w-full" required>
                                    {{-- The placeholder option is selected only if no default model is provided from the controller --}}
                                    <option disabled @empty($defaultLlm) selected @endempty>Choose an AI Model</option>
                                    @foreach ($llmModels as $group)
                                        <optgroup label="{{ $group['group'] }}">
                                            @foreach ($group['models'] as $model)
                                                {{-- This option is selected if its ID matches the default LLM from the environment variables --}}
                                                <option value="{{ $model['id'] }}" @if(isset($defaultLlm) && $model['id'] === $defaultLlm) selected @endif>{{ $model['name'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="btn btn-primary">Create New Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-8">
                {{-- Modified: Added a flex container and an input for words per click. --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Your Quizzes</h3>
                    <div class="flex items-center gap-4">
                        <div class="form-control">
                            <label class="label cursor-pointer gap-2">
                                <span class="label-text">Slow Question Show</span>
                                <input type="checkbox" id="slow-show-toggle" class="checkbox checkbox-primary" />
                            </label>
                        </div>
                        {{-- New: Input to control the number of words shown per click. --}}
                        <div class="form-control">
                            <label class="label" for="slow-show-words">
                                <span class="label-text">Words per click:</span>
                            </label>
                            <input type="number" id="slow-show-words" class="input input-bordered input-sm w-20" value="1" min="1" max="20">
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid gap-4">
                    @forelse ($quizzes as $quiz)
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">{{ $quiz->prompt }}</h2>
                                <div class="text-sm text-base-content/60">
                                    <span>Model: {{ $quiz->llm_model }}</span>
                                    <span class="mx-2">|</span>
                                    <span>Created: {{ $quiz->created_at->diffForHumans() }}</span>
                                    <span class="mx-2">|</span>
                                    <span>Last Accessed: {{ $quiz->updated_at->diffForHumans() }}</span>
                                </div>
                                @php
                                    $answeredCount = $quiz->questions->whereNotNull('user_choice')->count();
                                    $correctCount = $quiz->questions->where('is_correct', true)->count();
                                    $wrongCount = $quiz->questions->where('is_correct', false)->count();
                                    $percentage = $answeredCount > 0 ? round(($correctCount / $answeredCount) * 100) : 0;
                                    // Modified: The goal is now read from each specific quiz object.
                                    $goal = $quiz->question_goal;
                                @endphp
                                
                                <div class="my-2">
                                    <label for="quiz-progress-{{ $quiz->id }}" class="label px-0">
                                        <span class="label-text font-semibold">Goal Progress</span>
                                        <span class="label-text-alt font-semibold">{{ $correctCount }}/{{ $goal }}</span>
                                    </label>
                                    <progress id="quiz-progress-{{ $quiz->id }}" class="progress progress-primary w-full" value="{{ $correctCount }}" max="{{ $goal }}"></progress>
                                </div>
                                
                                <p>Answered: {{ $answeredCount }} | Correct: {{ $correctCount }} | Wrong: {{ $wrongCount }} | Score: {{ $percentage }}%</p>
                                <!-- Modified: Replaced the single action button with two specific layout options. -->
                                <div class="card-actions justify-end">
                                    <a href="{{ route('quiz.show', ['quiz' => $quiz, 'layout' => 'list']) }}" class="btn btn-secondary">
                                        {{ $answeredCount > 0 ? 'Resume (List)' : 'Start (List)' }}
                                    </a>
                                    <a href="{{ route('quiz.show', ['quiz' => $quiz, 'layout' => 'floating']) }}" class="btn btn-primary">
                                        {{ $answeredCount > 0 ? 'Resume (Floating)' : 'Start (Floating)' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p>You haven't started any quizzes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    {{-- Modified: Script now manages the state for both the toggle and the new words-per-click input. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('slow-show-toggle');
            const wordsInput = document.getElementById('slow-show-words'); // New: Get the number input.
            const toggleStorageKey = 'slowShowEnabled';
            const wordsStorageKey = 'slowShowWords'; // New: Storage key for word count.
            
            if (toggle && wordsInput) { // Modified: Check for both elements.
                // Set the toggle's initial state from localStorage.
                toggle.checked = localStorage.getItem(toggleStorageKey) === 'true';
                
                // New: Set the input's initial state from localStorage, or default to 1.
                wordsInput.value = localStorage.getItem(wordsStorageKey) || '1';
                
                // Add an event listener to update localStorage when the toggle is changed.
                toggle.addEventListener('change', function() {
                    localStorage.setItem(toggleStorageKey, this.checked);
                });
                
                // New: Add an event listener to update localStorage when the number input is changed.
                wordsInput.addEventListener('change', function() {
                    localStorage.setItem(wordsStorageKey, this.value);
                });
            }
        });
    </script>
@endsection

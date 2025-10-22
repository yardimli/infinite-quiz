@extends('layouts.app')


@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('quiz.store') }}" method="POST" class="max-w-xl mx-auto">
                        @csrf
                        <div class="form-control">
                            <label class="label" for="prompt">
                                <span class="label-text">What subject do you want a quiz on?</span>
                            </label>
                            <textarea id="prompt" class="textarea textarea-bordered h-20 w-full" name="prompt" placeholder="e.g., 'The Roman Empire' or 'Quantum Physics'" required>{{ old('prompt') }}</textarea>
                        </div>
                        
                        {{-- Added: Input for the user to define the quiz length. --}}
                        <div class="form-control mt-4">
                            <label class="label" for="question_goal">
                                <span class="label-text">How many questions to complete the quiz?</span>
                            </label>
                            <input type="number" id="question_goal" name="question_goal" class="input input-bordered w-full" value="20" min="1" max="100" required>
                        </div>
                        
                        <div class="form-control mt-4">
                            {{-- The select input for the AI model --}}
                            <label class="label" for="llm_model">
                                <span class="label-text">Choose an AI Model</span>
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
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Create New Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-8">
                {{-- Modified: Added a flex container to align title and new checkbox. --}}
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Your Quizzes</h3>
                    {{-- Added: Checkbox to toggle the "Slow Question Show" feature. --}}
                    <div class="form-control">
                        <label class="label cursor-pointer gap-2">
                            <span class="label-text">Slow Question Show</span>
                            <input type="checkbox" id="slow-show-toggle" class="checkbox checkbox-primary" />
                        </label>
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
    
    {{-- Added: Script to manage the state of the "Slow Show" toggle. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('slow-show-toggle');
            const storageKey = 'slowShowEnabled';
            
            if (toggle) {
                // Set the toggle's initial state based on the value in localStorage.
                toggle.checked = localStorage.getItem(storageKey) === 'true';
                
                // Add an event listener to update localStorage when the toggle is changed.
                toggle.addEventListener('change', function() {
                    localStorage.setItem(storageKey, this.checked);
                });
            }
        });
    </script>
@endsection

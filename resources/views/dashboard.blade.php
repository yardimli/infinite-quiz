@extends('layouts.app')


@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-base-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- Modified form to be centered and have a max-width --}}
                    <form action="{{ route('quiz.store') }}" method="POST" class="max-w-xl mx-auto">
                        @csrf
                        <div class="form-control">
                            <label class="label" for="prompt">
                                <span class="label-text">What subject do you want a quiz on?</span>
                            </label>
                            {{-- Made the textarea larger by adding h-40 --}}
                            <textarea id="prompt" class="textarea textarea-bordered h-20 w-full" name="prompt" placeholder="e.g., 'The Roman Empire' or 'Quantum Physics'" required>{{ old('prompt') }}</textarea>
                        </div>
                        
                        {{-- Removed the label from the dropdown and adjusted margin --}}
                        <div class="form-control mt-4">
                            <select name="llm_model" id="llm_model" class="select select-bordered w-full" required>
                                <option disabled selected>Choose an AI Model</option>
                                @foreach ($llmModels as $group)
                                    <optgroup label="{{ $group['group'] }}">
                                        @foreach ($group['models'] as $model)
                                            <option value="{{ $model['id'] }}">{{ $model['name'] }}</option>
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
                <h3 class="text-lg font-semibold">Your Quizzes</h3>
                <div class="mt-4 grid gap-4">
                    @forelse ($quizzes as $quiz)
                        <div class="card bg-base-100 shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title">{{ $quiz->prompt }}</h2>
                                <div class="text-sm text-base-content/60">Model: {{ $quiz->llm_model }}</div>
                                @php
                                    $answeredCount = $quiz->questions->whereNotNull('user_choice')->count();
                                    $correctCount = $quiz->questions->where('is_correct', true)->count();
                                    $wrongCount = $quiz->questions->where('is_correct', false)->count();
                                    $percentage = $answeredCount > 0 ? round(($correctCount / $answeredCount) * 100) : 0;
                                @endphp
                                <p>Answered: {{ $answeredCount }} | Correct: {{ $correctCount }} | Wrong: {{ $wrongCount }} | Score: {{ $percentage }}%</p>
                                <div class="card-actions justify-end">
                                    <a href="{{ route('quiz.show', $quiz) }}" class="btn btn-primary">
                                        {{ $answeredCount > 0 ? 'Resume Quiz' : 'Start Quiz' }}
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
@endsection

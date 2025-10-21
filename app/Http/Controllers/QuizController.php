<?php

	namespace App\Http\Controllers;

	use App\Helpers\LlmHelper;
	use App\Models\Question;
	use App\Models\Quiz;
	use Illuminate\Http\Request;

	class QuizController extends Controller
	{
// ... create() method is not needed for this flow

		public function store(Request $request)
		{
// Modified: Added validation for the new question_goal field.
			$request->validate([
				'prompt' => 'required|string|max:1000',
				'llm_model' => 'required|string|max:255',
				'question_goal' => 'required|integer|min:1|max:100',
			]);

// Modified: Included question_goal when creating the new quiz.
			$quiz = auth()->user()->quizzes()->create([
				'prompt' => $request->prompt,
				'llm_model' => $request->llm_model,
				'question_goal' => $request->question_goal,
			]);

			return redirect()->route('quiz.show', $quiz);
		}

		public function show(Quiz $quiz)
		{
			$this->authorize('view', $quiz);

// Eager load questions to calculate initial score
			$quiz->load('questions');

// Check if there's an unanswered question first
			$unansweredQuestion = $quiz->questions()->whereNull('user_choice')->orderBy('created_at', 'desc')->first();

			if ($unansweredQuestion) {
				return view('quiz.show', ['quiz' => $quiz, 'question' => $unansweredQuestion]);
			}

// If no unanswered questions, the view will trigger generation
			return view('quiz.show', ['quiz' => $quiz]);
		}

		public function answer(Request $request, Quiz $quiz, Question $question)
		{
			$this->authorize('update', $quiz);

			$request->validate([
				'answer' => 'required|string',
			]);

// Prevent answering the same question twice
			if ($question->user_choice !== null) {
				return response()->json(['error' => 'This question has already been answered.'], 400);
			}

			$is_correct = $request->answer === $question->correct_answer;

			$question->update([
				'user_choice' => $request->answer,
				'is_correct' => $is_correct,
			]);

// Return the result and the new total of correct answers for the quiz
			return response()->json([
				'is_correct' => $is_correct,
				'correct_answer' => $question->correct_answer,
				'correct_count' => $quiz->questions()->where('is_correct', true)->count(),
			]);
		}

		public function generate(Quiz $quiz)
		{
			$this->authorize('update', $quiz);

// Modified system prompt to ask LLM to adjust difficulty
			$system_prompt = "You are a quiz generation assistant. Based on the user's topic and the history of previous questions (including whether the user answered correctly), create a new, unique, multiple-choice question. The question should have 4 possible answers. Adjust the difficulty of the new question based on the user's performance; if they are answering correctly, make the next question slightly harder. If they are struggling, make it slightly easier.
Respond ONLY with a valid JSON object in the following format:
{\"question\": \"The text of the question\", \"options\": [\"Answer A\", \"Answer B\", \"Answer C\", \"Answer D\"], \"correct_answer\": \"The correct answer text which must be one of the options\"}";

			$previous_questions = $quiz->questions()->get()->map(function ($q) {
				return [
					'question' => $q->question_text,
					'user_answered' => $q->user_choice,
					'was_correct' => $q->is_correct, // Pass/fail result
				];
			})->toArray();

// Modified user prompt to include performance context
			$chat_messages = [
				['role' => 'user', 'content' => "The quiz topic is: '{$quiz->prompt}'. The user's performance on previous questions is as follows: " . json_encode($previous_questions) . ". Please generate the next question, adjusting the difficulty based on their performance."]
			];

			$llmResult = LlmHelper::llm_no_tool_call($quiz->llm_model, $system_prompt, $chat_messages);

			if (isset($llmResult['error']) || !isset($llmResult['question'])) {
				return response()->json(['error' => $llmResult['error'] ?? 'Failed to generate a valid question from the LLM.'], 500);
			}

			$question = $quiz->questions()->create([
				'question_text' => $llmResult['question'],
				'options' => $llmResult['options'],
				'correct_answer' => $llmResult['correct_answer'],
			]);

			return response()->json([
				'question_html' => view('partials.question', ['quiz' => $quiz, 'question' => $question])->render()
			]);
		}
	}

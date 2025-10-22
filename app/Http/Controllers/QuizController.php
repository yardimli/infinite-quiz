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
			// Modified: Added validation for the new question_goal and answer_count fields.
			$request->validate([
				'prompt' => 'required|string|max:1000',
				'llm_model' => 'required|string|max:255',
				'question_goal' => 'required|integer|min:1|max:100',
				'answer_count' => 'required|integer|min:2|max:6', // Added: validation for answer count
			]);

			// Modified: Included question_goal and answer_count when creating the new quiz.
			$quiz = auth()->user()->quizzes()->create([
				'prompt' => $request->prompt,
				'llm_model' => $request->llm_model,
				'question_goal' => $request->question_goal,
				'answer_count' => $request->answer_count, // Added: save answer count
			]);

			return redirect()->route('quiz.show', $quiz);
		}

		/**
		 * Display a specific quiz, choosing the view based on the layout parameter.
		 *
		 * @param Request $request
		 * @param Quiz $quiz
		 * @return \Illuminate\View\View
		 */
		public function show(Request $request, Quiz $quiz)
		{
			$this->authorize('view', $quiz);

			// Eager load questions to calculate initial score
			$quiz->load('questions');

			// Check if there's an unanswered question first
			$unansweredQuestion = $quiz->questions()->whereNull('user_choice')->orderBy('created_at', 'desc')->first();

			// Modified: Get the desired layout from the URL query string, defaulting to 'floating'.
			$layout = $request->query('layout', 'floating');

			$viewData = [
				'quiz' => $quiz,
				'question' => $unansweredQuestion,
			];

			// Modified: Determine which Blade view to render based on the layout parameter.
			$viewName = $layout === 'list' ? 'quiz.show-list' : 'quiz.show-floating';

			// The selected view will handle the case where no questions exist yet and trigger generation.
			return view($viewName, $viewData);
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

			// Added: Get the number of answers from the quiz, defaulting to 4 for older quizzes.
			$answer_count = $quiz->answer_count ?? 4;

			// Modified system prompt to dynamically request the number of answers and adjust difficulty.
			$system_prompt = "You are a quiz generation assistant. Based on the user's topic and the history of previous questions (including whether the user answered correctly), create a new, unique, multiple-choice question. The question should have {$answer_count} possible answers. Adjust the difficulty of the new question based on the user's performance; if they are answering correctly, make the next question slightly harder. If they are struggling, make it slightly easier.
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

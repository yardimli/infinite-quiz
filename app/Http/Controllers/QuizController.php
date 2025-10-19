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
			$request->validate([
				'prompt' => 'required|string|max:1000',
				'llm_model' => 'required|string|max:255',
			]);

			$quiz = auth()->user()->quizzes()->create([
				'prompt' => $request->prompt,
				'llm_model' => $request->llm_model,
			]);

			return redirect()->route('quiz.show', $quiz);
		}

		public function show(Quiz $quiz)
		{
			$this->authorize('view', $quiz);

			// Check if there's an unanswered question first
			$unansweredQuestion = $quiz->questions()->whereNull('user_choice')->first();

			if ($unansweredQuestion) {
				return view('quiz.show', ['quiz' => $quiz, 'question' => $unansweredQuestion]);
			}

			// If no unanswered questions, generate a new one
			return view('quiz.show', ['quiz' => $quiz]);
		}

		public function answer(Request $request, Quiz $quiz, Question $question)
		{
			$this->authorize('update', $quiz);

			$request->validate([
				'answer' => 'required|string',
			]);

			$question->update([
				'user_choice' => $request->answer,
				'is_correct' => $request->answer === $question->correct_answer,
			]);

			return redirect()->route('quiz.show', $quiz);
		}

		public function generate(Quiz $quiz)
		{
			$this->authorize('update', $quiz);

			$system_prompt = "You are a quiz generation assistant. Based on the user's topic and the history of previous questions, create a new, unique, multiple-choice question. The question should have 4 possible answers.
        Respond ONLY with a valid JSON object in the following format:
        {\"question\": \"The text of the question\", \"options\": [\"Answer A\", \"Answer B\", \"Answer C\", \"Answer D\"], \"correct_answer\": \"The correct answer text which must be one of the options\"}";

			$previous_questions = $quiz->questions()->get()->map(function ($q) {
				return [
					'question' => $q->question_text,
					'user_answered' => $q->user_choice,
					'was_correct' => $q->is_correct
				];
			})->toArray();

			$chat_messages = [
				['role' => 'user', 'content' => "The quiz topic is: '{$quiz->prompt}'. The following questions have already been asked: " . json_encode($previous_questions) . ". Please generate the next question."]
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

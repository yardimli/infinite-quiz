<?php

	namespace App\Http\Controllers;

	use App\Helpers\LlmHelper; // Import the helper
	use Illuminate\Http\Request;

	class DashboardController extends Controller
	{
		public function index()
		{
			$quizzes = auth()->user()->quizzes()->with('questions')->latest()->get();

			$llmModels = LlmHelper::getVerifiedGroupedModels(); // Get the models

			// Get the default model from the environment file.
			$defaultLlm = env('DEFAULT_LLM');

			// Pass all necessary data to the view, including the default model.
			return view('dashboard', compact('quizzes', 'llmModels', 'defaultLlm'));
		}
	}

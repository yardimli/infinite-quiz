<?php

	namespace App\Http\Controllers;

	use App\Helpers\LlmHelper; // Import the helper
	use Illuminate\Http\Request;

	class DashboardController extends Controller
	{
		public function index()
		{
			$quizzes = auth()->user()->quizzes()->with('questions')->latest()->get();
			// Modified section: Added a comment to note that API keys should be handled in the helper via .env.
			// Note: The LlmHelper should be configured to use any required API keys from the .env file for security and flexibility.
			$llmModels = LlmHelper::getVerifiedGroupedModels(); // Get the models

			return view('dashboard', compact('quizzes', 'llmModels')); // Pass them to the view
		}
	}

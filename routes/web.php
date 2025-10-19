<?php

	use App\Http\Controllers\DashboardController;
	use App\Http\Controllers\ProfileController;
	use App\Http\Controllers\QuizController;
	use Illuminate\Support\Facades\Route;

	/*
	|--------------------------------------------------------------------------
	| Web Routes
	|--------------------------------------------------------------------------
	|
	| Here is where you can register web routes for your application. These
	| routes are loaded by the RouteServiceProvider and all of them will
	| be assigned to the "web" middleware group. Make something great!
	|
	*/

	Route::get('/', function () {
		return view('welcome');
	});

// Modified section: Removed a duplicate dashboard route definition that was pointing to a closure instead of the controller.
	Route::middleware('auth')->group(function () {
		Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
		Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

		Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

		Route::get('/quiz/create', [QuizController::class, 'create'])->name('quiz.create');
		Route::post('/quiz', [QuizController::class, 'store'])->name('quiz.store');
		Route::get('/quiz/{quiz}', [QuizController::class, 'show'])->name('quiz.show');
		Route::post('/quiz/{quiz}/generate', [QuizController::class, 'generate'])->name('quiz.generate'); // New AJAX route
		Route::post('/quiz/{quiz}/question/{question}/answer', [QuizController::class, 'answer'])->name('quiz.answer');
	});

	require __DIR__ . '/auth.php';

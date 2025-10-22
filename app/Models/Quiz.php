<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class Quiz extends Model
	{
		use HasFactory;

		// Note: A database migration is required to add the 'answer_count' column.
		// Example: php artisan make:migration add_answer_count_to_quizzes_table --table=quizzes

		// Modified: Added 'question_goal' and 'answer_count' to the list of mass-assignable attributes.
		protected $fillable = [
			'user_id',
			'prompt',
			'llm_model',
			'question_goal',
			'answer_count', // Added: allow mass assignment for the number of answers.
		];

		// ... rest of the model is unchanged
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}

		public function questions(): HasMany
		{
			return $this->hasMany(Question::class);
		}
	}

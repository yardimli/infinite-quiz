<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class Question extends Model
	{
		use HasFactory;

		/**
		 * The attributes that are mass assignable.
		 *
		 * @var array<int, string>
		 */
		protected $fillable = [
			'quiz_id',
			'question_text',
			'options',
			'correct_answer',
			'user_choice',
			'is_correct',
		];

		/**
		 * The attributes that should be cast.
		 *
		 * @var array<string, string>
		 */
		protected $casts = [
			'options' => 'json',
			'is_correct' => 'boolean',
		];

		/**
		 * Get the quiz that the question belongs to.
		 */
		public function quiz(): BelongsTo
		{
			return $this->belongsTo(Quiz::class);
		}
	}

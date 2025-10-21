<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class Quiz extends Model
	{
		use HasFactory;

// Modified: Added 'question_goal' to the list of mass-assignable attributes.
		protected $fillable = [
			'user_id',
			'prompt',
			'llm_model',
			'question_goal',
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

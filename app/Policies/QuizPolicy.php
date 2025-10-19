<?php

	namespace App\Policies;

	use App\Models\Quiz;
	use App\Models\User;
	use Illuminate\Auth\Access\HandlesAuthorization;

	class QuizPolicy
	{
		use HandlesAuthorization;

		public function view(User $user, Quiz $quiz)
		{
			return $user->id === $quiz->user_id;
		}

		public function update(User $user, Quiz $quiz)
		{
			return $user->id === $quiz->user_id;
		}
	}

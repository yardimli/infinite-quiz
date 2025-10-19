<?php

	namespace App\Helpers;

	use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Cache;

	class LlmHelper
	{
		/**
		 * Provides a static, curated list of LLMs grouped for the UI dropdown.
		 *
		 * @return array The structured list of models.
		 */
		public static function getStaticGroupedModels(): array
		{
			// This structure is based on the user-provided image for the desired model list.
			return [
				[
					'group' => 'Popular',
					'models' => [
						['id' => 'openrouter/sonoma-dusk-alpha', 'name' => 'Sonoma Dusk Alpha'],
						['id' => 'openrouter/sonoma-sky-alpha', 'name' => 'Sonoma Sky Alpha'],
						['id' => 'openai/gpt-4o', 'name' => 'OpenAI GPT-4o'],
						['id' => 'anthropic/claude-3.7-sonnet', 'name' => 'Claude 3.7 Sonnet'],
						['id' => 'anthropic/claude-3.7-sonnet:thinking', 'name' => 'Claude 3.7 Sonnet (Thinking)'],
						['id' => 'google/gemini-2.5-pro', 'name' => 'Google: Gemini 2.5 Pro'],
						['id' => 'deepseek/deepseek-chat-v3.1', 'name' => 'DeepSeek Chat V3.1'],
					],
				],
				[
					'group' => 'New',
					'models' => [
						['id' => 'anthropic/claude-sonnet-4', 'name' => 'Claude Sonnet 4'],
						['id' => 'openai/gpt-5', 'name' => 'OpenAI GPT-5'],
						['id' => 'openai/gpt-oss-120b', 'name' => 'OpenAI: gpt-oss-120b'],
						['id' => 'openai/gpt-5-chat', 'name' => 'OpenAI GPT-5 Chat'],
						['id' => 'openai/gpt-5-mini', 'name' => 'OpenAI GPT-5 mini'],
						['id' => 'moonshotai/kimi-k2-0905', 'name' => 'MoonshotAI: Kimi K2 0905'],
						['id' => 'z-ai/glm-4.5', 'name' => 'Z.AI: GLM 4.5'],
					],
				],
				[
					'group' => 'Other',
					'models' => [
						['id' => 'google/gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash'],
						['id' => 'openai/gpt-4.1', 'name' => 'OpenAI GPT-4.1'],
						['id' => 'openai/gpt-4o-mini', 'name' => 'OpenAI GPT-4o mini'],
					],
				],
				[
					'group' => 'NSFW',
					'models' => [
						['id' => 'qwen/qwen3-235b-a22b-2507', 'name' => 'Qwen 3 235b'],
						['id' => 'google/gemma-3-27b-it', 'name' => 'Gemma 3 27b'],
						['id' => 'mistralai/mistral-medium-3.1', 'name' => 'Mistral Medium 3.1'],
						['id' => 'mistralai/mistral-large-2411', 'name' => 'Mistral Large'],
						['id' => 'microsoft/wizardlm-2-8x22b', 'name' => 'WizardLM 2 8x22b'],
						['id' => 'x-ai/grok-4', 'name' => 'Grok 4'],
					],
				],
			];
		}

		/**
		 * Fetches the static model list and verifies it against the live OpenRouter API.
		 * The result is cached for 15 minutes to reduce API calls.
		 *
		 * @return array The verified and grouped list of available models.
		 */
		public static function getVerifiedGroupedModels(): array
		{
			return Cache::remember('verified_grouped_llms', now()->addMinutes(15), function () {
				try {
					$response = Http::timeout(30)
						->withHeaders([
							'HTTP-Referer' => env('APP_URL', 'https://playground.computer'),
							'X-Title' => env('APP_NAME', 'Laravel Quiz App'),
						])
						->get('https://openrouter.ai/api/v1/models');

					if (!$response->successful()) {
						Log::error('Failed to fetch models from OpenRouter to verify availability.', [
							'status' => $response->status(),
							'body' => $response->body()
						]);
						return self::getStaticGroupedModels(); // Fallback to static list
					}

					$liveModelsData = $response->json();
					if (empty($liveModelsData['data'])) {
						Log::warning('OpenRouter API returned no model data.');
						return self::getStaticGroupedModels(); // Fallback
					}

					$availableModelIds = array_flip(array_column($liveModelsData['data'], 'id'));
					$staticGroupedModels = self::getStaticGroupedModels();
					$verifiedGroupedModels = [];

					foreach ($staticGroupedModels as $group) {
						$verifiedModelsInGroup = [];
						foreach ($group['models'] as $model) {
							if (isset($availableModelIds[$model['id']])) {
								$verifiedModelsInGroup[] = $model;
							}
						}

						if (!empty($verifiedModelsInGroup)) {
							$verifiedGroupedModels[] = [
								'group' => $group['group'],
								'models' => $verifiedModelsInGroup,
							];
						}
					}
					return $verifiedGroupedModels;
				} catch (\Exception $e) {
					Log::error('Exception while fetching or verifying OpenRouter models: ' . $e->getMessage());
					return self::getStaticGroupedModels(); // Fallback on any exception
				}
			});
		}

		/**
		 * Makes a structured call to the OpenRouter API.
		 *
		 * @param string $llm_model The model ID to use.
		 * @param string $system_prompt The system prompt.
		 * @param array $chat_messages The history of messages.
		 * @param bool $return_json Whether to parse the response as JSON.
		 * @param int $max_retries Number of times to retry on failure.
		 * @return array The parsed response or an error.
		 */
		public static function llm_no_tool_call(string $llm_model, string $system_prompt, array $chat_messages, bool $return_json = true, int $max_retries = 1): array
		{
			set_time_limit(300);
			session_write_close();

			$llm_base_url = 'https://openrouter.ai/api/v1/chat/completions';
			$llm_api_key = env('OPEN_ROUTER_KEY');

			if (empty($llm_api_key)) {
				Log::error("OpenRouter API Key is not configured.");
				return ['error' => 'API key not configured'];
			}

			$all_messages = [['role' => 'system', 'content' => $system_prompt], ...$chat_messages];

			$data = [
				'model' => $llm_model,
				'messages' => $all_messages,
				'temperature' => 0.7,
				'max_tokens' => 4096,
				'stream' => false,
			];

			// For models that support it, enforce JSON output
			if (str_contains($llm_model, 'gpt-') || str_contains($llm_model, 'claude-3.5') || str_contains($llm_model, 'gemini-1.5')) {
				$data['response_format'] = ['type' => 'json_object'];
			}

			Log::info("LLM Request to {$llm_base_url} ({$llm_model})");
			$attempt = 0;
			$last_error = null;

			while ($attempt <= $max_retries) {
				$attempt++;
				try {
					$response = Http::withToken($llm_api_key)
						->withHeaders([
							'HTTP-Referer' => env('APP_URL', 'https://playground.computer'),
							'X-Title' => env('APP_NAME', 'Laravel Quiz App'),
						])
						->timeout(180)
						->post($llm_base_url, $data);

					if (!$response->successful()) {
						$last_error = "LLM API Error: Status " . $response->status();
						Log::error($last_error, ['body' => $response->body()]);
						if ($attempt > $max_retries) break;
						sleep(2);
						continue;
					}

					$complete_rst = $response->json();
					$content = $complete_rst['choices'][0]['message']['content'] ?? null;

					if ($content === null) {
						$last_error = "Could not find content in LLM response structure.";
						Log::error($last_error, ['response' => $complete_rst]);
						if ($attempt > $max_retries) break;
						sleep(2);
						continue;
					}

					if (!$return_json) {
						return ['content' => $content];
					}

					$decoded_json = json_decode($content, true);

					// Check if json_decode was successful
					if (json_last_error() === JSON_ERROR_NONE) {
						return $decoded_json; // Success
					}

					// If decoding fails, log the error and retry if possible
					$last_error = "Failed to decode JSON response from LLM. Error: " . json_last_error_msg();
					Log::error($last_error, ['content' => $content]);
					if ($attempt > $max_retries) break;
					sleep(2);
					continue;

				} catch (\Exception $e) {
					$last_error = "General Exception during LLM call: " . $e->getMessage();
					Log::error($last_error);
					if ($attempt > $max_retries) break;
					sleep(pow(2, $attempt));
				}
			}

			return ['error' => $last_error ?: 'LLM call failed after retries.'];
		}
	}

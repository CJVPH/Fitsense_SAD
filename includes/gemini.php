<?php
/**
 * FitSense — Gemini API Client
 *
 * Provides the GeminiClient class for building prompts, sending requests to
 * the Google Gemini API, and parsing structured JSON responses.
 *
 * The API key is read from the GEMINI_API_KEY constant (loaded from env) and
 * is never exposed to the browser or included in error responses.
 *
 * Requirements: 1.4, 6.2, 6.3, 6.5, 7.1
 */

require_once __DIR__ . '/../config/database.php';

class GeminiClient
{
    private PDO $pdo;

    /**
     * Initialise the client with a shared PDO connection.
     */
    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    // ─── Intent Detection ─────────────────────────────────────────────────────

    /**
     * Determine whether the user message is requesting a structured workout
     * or meal plan.
     *
     * @param  string $message Raw user message.
     * @return bool            True when the message matches a workout/meal plan intent.
     */
    public function isStructuredRequest(string $message): bool
    {
        $pattern = '/\b('
            . 'workout\s+plan|workout\s+routine|exercise\s+plan|training\s+plan|training\s+routine'
            . '|meal\s+plan|diet\s+plan|nutrition\s+plan|eating\s+plan|food\s+plan'
            . '|give\s+me\s+a\s+workout|create\s+a\s+workout|make\s+me\s+a\s+workout'
            . '|give\s+me\s+a\s+meal|create\s+a\s+meal|make\s+me\s+a\s+meal'
            . ')\b/i';

        return (bool) preg_match($pattern, $message);
    }

    // ─── Prompt Construction ──────────────────────────────────────────────────

    /**
     * Build the full prompt string to send to Gemini.
     *
     * Injects system context, the member's profile and active goal, the list
     * of active exercises from the database, an optional structured-format
     * instruction (when the message is a structured request), and the user
     * message.
     *
     * Profile values are decoded with htmlspecialchars_decode() to reverse any
     * HTML encoding applied during storage or sanitisation.
     *
     * @param  string $userMessage   The member's raw message.
     * @param  array  $memberProfile Row from member_profiles (joined with users).
     * @param  array  $goal          Active row from fitness_goals.
     * @return string                Complete prompt ready for the Gemini API.
     */
    public function buildPrompt(string $userMessage, array $memberProfile, array $goal): string
    {
        $firstName          = htmlspecialchars_decode($memberProfile['first_name']          ?? '', ENT_QUOTES | ENT_HTML5);
        $lastName           = htmlspecialchars_decode($memberProfile['last_name']           ?? '', ENT_QUOTES | ENT_HTML5);
        $age                = htmlspecialchars_decode((string)($memberProfile['age']        ?? ''), ENT_QUOTES | ENT_HTML5);
        $heightCm           = htmlspecialchars_decode((string)($memberProfile['height_cm']  ?? ''), ENT_QUOTES | ENT_HTML5);
        $weightKg           = htmlspecialchars_decode((string)($memberProfile['current_weight_kg'] ?? ''), ENT_QUOTES | ENT_HTML5);
        $targetWeightKg     = htmlspecialchars_decode((string)($memberProfile['target_weight_kg']  ?? ''), ENT_QUOTES | ENT_HTML5);
        $fitnessLevel       = htmlspecialchars_decode($memberProfile['fitness_level']       ?? '', ENT_QUOTES | ENT_HTML5);
        $goalType           = htmlspecialchars_decode($goal['goal_type']                    ?? '', ENT_QUOTES | ENT_HTML5);
        $goalDescription    = htmlspecialchars_decode($goal['description']                  ?? '', ENT_QUOTES | ENT_HTML5);
        $medicalConditions  = htmlspecialchars_decode($memberProfile['medical_conditions']  ?? '', ENT_QUOTES | ENT_HTML5);
        $address            = htmlspecialchars_decode($memberProfile['address']             ?? '', ENT_QUOTES | ENT_HTML5);
        $workSchedule       = htmlspecialchars_decode($memberProfile['work_schedule']       ?? '', ENT_QUOTES | ENT_HTML5);
        $occupation         = htmlspecialchars_decode($memberProfile['occupation']          ?? '', ENT_QUOTES | ENT_HTML5);
        $sleepHours         = htmlspecialchars_decode((string)($memberProfile['sleep_hours_per_night'] ?? ''), ENT_QUOTES | ENT_HTML5);
        $activityLevel      = htmlspecialchars_decode($memberProfile['activity_level']      ?? '', ENT_QUOTES | ENT_HTML5);
        $dietaryPreference  = htmlspecialchars_decode($memberProfile['dietary_preference']  ?? '', ENT_QUOTES | ENT_HTML5);
        $allergies          = htmlspecialchars_decode($memberProfile['allergies']           ?? '', ENT_QUOTES | ENT_HTML5);

        // Format work schedule for readability
        $workScheduleLabel = match($workSchedule) {
            'day_shift'       => 'Day shift worker',
            'night_shift'     => 'Night shift worker',
            'rotating_shift'  => 'Rotating shift worker',
            'work_from_home'  => 'Works from home',
            'student'         => 'Student',
            'not_working'     => 'Not currently working',
            'other'           => 'Other schedule',
            default           => '',
        };

        $activityLabel = match($activityLevel) {
            'sedentary'          => 'Sedentary (little or no exercise)',
            'lightly_active'     => 'Lightly active (1-3 days/week)',
            'moderately_active'  => 'Moderately active (3-5 days/week)',
            'very_active'        => 'Very active (6-7 days/week)',
            'extremely_active'   => 'Extremely active (athlete/physical job)',
            default              => '',
        };

        // Fetch active exercises from the database.
        $stmt = $this->pdo->query('SELECT name FROM exercises WHERE is_active = TRUE ORDER BY name');
        $exercises = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $exerciseList = !empty($exercises) ? implode(', ', $exercises) : 'No exercises available';

        $prompt  = "[SYSTEM CONTEXT]\n";
        $prompt .= "You are FitSense AI, a personal fitness coach. You are warm, motivating, and highly personalized.\n";
        $prompt .= "You have full access to the member's profile below. Use it in EVERY response to give tailored advice.\n";
        $prompt .= "Always address the member by their first name. Consider their work schedule, sleep, and lifestyle.\n";
        $prompt .= "Include a safety disclaimer at the end of any workout or nutrition advice.\n";
        $prompt .= "Only provide fitness, nutrition, and wellness guidance. Do not answer unrelated questions.\n";
        $prompt .= "\n";
        $prompt .= "[MEMBER PROFILE]\n";
        $prompt .= "Name: {$firstName} {$lastName}\n";
        $prompt .= "Age: {$age} | Height: {$heightCm}cm | Current Weight: {$weightKg}kg";
        if ($targetWeightKg) $prompt .= " | Target Weight: {$targetWeightKg}kg";
        $prompt .= "\n";
        $prompt .= "Fitness Level: {$fitnessLevel}\n";
        $prompt .= "Primary Goal: {$goalType}" . ($goalDescription ? " — {$goalDescription}" : '') . "\n";
        if ($activityLabel)     $prompt .= "Daily Activity Level: {$activityLabel}\n";
        if ($workScheduleLabel) $prompt .= "Work Schedule: {$workScheduleLabel}" . ($occupation ? " ({$occupation})" : '') . "\n";
        if ($sleepHours)        $prompt .= "Sleep: {$sleepHours} hours per night\n";
        if ($dietaryPreference && $dietaryPreference !== 'no_preference') $prompt .= "Dietary Preference: {$dietaryPreference}\n";
        if ($allergies)         $prompt .= "Allergies/Intolerances: {$allergies}\n";
        if ($medicalConditions) $prompt .= "Medical Conditions: {$medicalConditions}\n";
        if ($address)           $prompt .= "Location: {$address}\n";
        $prompt .= "\n";
        $prompt .= "[AVAILABLE EXERCISES]\n";
        $prompt .= $exerciseList . "\n";

        if ($this->isStructuredRequest($userMessage)) {
            $prompt .= "\n";
            $prompt .= "[FORMAT INSTRUCTION]\n";
            $prompt .= "If providing a workout plan, return it in this exact JSON structure wrapped in ```json ... ```:\n";
            $prompt .= '{"title":"...","exercises":[{"name":"...","sets":N,"reps":N,"rest_seconds":N,"notes":"..."}],"duration_minutes":N}';
            $prompt .= "\n";
            $prompt .= "If providing a meal plan, return it in this exact JSON structure wrapped in ```json ... ```:\n";
            $prompt .= '{"title":"...","meals":[{"name":"...","ingredients":["..."],"protein_g":N,"carbs_g":N,"fat_g":N,"calories":N}]}';
            $prompt .= "\n";
        }

        $prompt .= "\n";
        $prompt .= "[USER MESSAGE]\n";
        $prompt .= $userMessage;

        return $prompt;
    }

    // ─── API Request ──────────────────────────────────────────────────────────

    /**
     * Send a prompt to the Gemini API and return the response text.
     *
     * Uses PHP's file_get_contents() with a stream context, falling back to
     * cURL when available. The API key is sent in the x-goog-api-key header
     * and is never included in any returned error payload.
     *
     * @param  string $prompt The fully constructed prompt string.
     * @return array          ['success' => true, 'text' => string]
     *                        or ['success' => false, 'error' => string]
     */
    public function sendRequest(string $prompt): array
    {
        $body = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        $apiKey  = GEMINI_API_KEY;
        $apiUrl  = GEMINI_API_URL;
        $timeout = GEMINI_TIMEOUT_SECONDS;

        $maxRetries = 3;
        $lastError  = 'Something went wrong — please try again.';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                if (function_exists('curl_init')) {
                    $result = $this->sendWithCurl($apiUrl, $apiKey, $body, $timeout);
                } else {
                    $result = $this->sendWithFileGetContents($apiUrl, $apiKey, $body, $timeout);
                }

                if ($result['http_code'] === 200) {
                    $decoded = json_decode($result['body'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('GeminiClient: malformed JSON — ' . json_last_error_msg());
                        $lastError = 'Something went wrong — please try again.';
                        continue;
                    }
                    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text === null) {
                        error_log('GeminiClient: unexpected response structure');
                        $lastError = 'Something went wrong — please try again.';
                        continue;
                    }
                    return ['success' => true, 'text' => $text];
                }

                // 503 / 429 — retry after short delay
                if (in_array($result['http_code'], [429, 503], true)) {
                    error_log("GeminiClient: HTTP {$result['http_code']} on attempt {$attempt}, retrying…");
                    $lastError = 'Something went wrong — please try again.';
                    if ($attempt < $maxRetries) sleep(2);
                    continue;
                }

                error_log('GeminiClient: non-200 response, status=' . $result['http_code']);
                $lastError = 'Something went wrong — please try again.';
                break;

            } catch (Throwable $e) {
                error_log('GeminiClient: exception — ' . $e->getMessage());
                $lastError = 'Something went wrong — please try again.';
                if ($attempt < $maxRetries) sleep(1);
            }
        }

        return ['success' => false, 'error' => $lastError];
    }

    // ─── Structured Response Parsing ─────────────────────────────────────────

    /**
     * Extract and decode a JSON block from a Gemini response string.
     *
     * Looks for a ```json ... ``` code fence in the response text and attempts
     * to decode the content. Returns null on any failure.
     *
     * @param  string     $text Raw Gemini response text.
     * @return array|null       Decoded JSON array, or null on failure.
     */
    public function parseStructuredResponse(string $text): ?array
    {
        try {
            if (!preg_match('/```json\s*([\s\S]*?)\s*```/i', $text, $matches)) {
                return null;
            }

            $json = trim($matches[1]);
            $decoded = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return null;
            }

            return $decoded;

        } catch (Throwable $e) {
            error_log('GeminiClient::parseStructuredResponse — ' . $e->getMessage());
            return null;
        }
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Perform the HTTP POST using cURL.
     *
     * @param  string $url     API endpoint URL.
     * @param  string $apiKey  Gemini API key.
     * @param  string $body    JSON request body.
     * @param  int    $timeout Request timeout in seconds.
     * @return array           ['http_code' => int, 'body' => string]
     */
    private function sendWithCurl(string $url, string $apiKey, string $body, int $timeout): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        return ['http_code' => $httpCode, 'body' => (string) $response];
    }

    /**
     * Perform the HTTP POST using file_get_contents() with a stream context.
     *
     * @param  string $url     API endpoint URL.
     * @param  string $apiKey  Gemini API key.
     * @param  string $body    JSON request body.
     * @param  int    $timeout Request timeout in seconds.
     * @return array           ['http_code' => int, 'body' => string]
     */
    private function sendWithFileGetContents(string $url, string $apiKey, string $body, int $timeout): array
    {
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $apiKey,
                ]),
                'content'       => $body,
                'timeout'       => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException('file_get_contents failed for Gemini API request');
        }

        // Extract HTTP status code from response headers.
        $httpCode = 200;
        if (!empty($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) {
                    $httpCode = (int) $m[1];
                    break;
                }
            }
        }

        return ['http_code' => $httpCode, 'body' => $response];
    }
}

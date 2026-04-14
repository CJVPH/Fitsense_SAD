<?php
/**
 * FitSense — OpenAI GPT-4o Client
 *
 * Backup AI client using OpenAI's Chat Completions API.
 * Same interface as ClaudeClient and GeminiClient.
 */

require_once __DIR__ . '/../config/database.php';

class OpenAIClient
{
    private PDO    $pdo;
    private string $apiKey;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->pdo     = Database::getConnection();
        $this->apiKey  = OPENAI_API_KEY;
        $this->model   = OPENAI_MODEL;
        $this->timeout = OPENAI_TIMEOUT_SECONDS;
    }

    // ─── Intent Detection ─────────────────────────────────────────────────────

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

    public function buildPrompt(string $userMessage, array $memberProfile, array $goal): string
    {
        $firstName         = htmlspecialchars_decode($memberProfile['first_name']           ?? '', ENT_QUOTES | ENT_HTML5);
        $lastName          = htmlspecialchars_decode($memberProfile['last_name']            ?? '', ENT_QUOTES | ENT_HTML5);
        $age               = $memberProfile['age']                ?? '';
        $heightCm          = $memberProfile['height_cm']          ?? '';
        $weightKg          = $memberProfile['current_weight_kg']  ?? '';
        $targetWeightKg    = $memberProfile['target_weight_kg']   ?? '';
        $fitnessLevel      = $memberProfile['fitness_level']      ?? '';
        $goalType          = $goal['goal_type']                   ?? '';
        $goalDescription   = $goal['description']                 ?? '';
        $medicalConditions = $memberProfile['medical_conditions'] ?? '';
        $address           = $memberProfile['address']            ?? '';
        $workSchedule      = $memberProfile['work_schedule']      ?? '';
        $occupation        = $memberProfile['occupation']         ?? '';
        $sleepHours        = $memberProfile['sleep_hours_per_night'] ?? '';
        $activityLevel     = $memberProfile['activity_level']     ?? '';
        $dietaryPreference = $memberProfile['dietary_preference'] ?? '';
        $allergies         = $memberProfile['allergies']          ?? '';

        $workScheduleLabel = match($workSchedule) {
            'day_shift'      => 'Day shift worker',
            'night_shift'    => 'Night shift worker',
            'rotating_shift' => 'Rotating shift worker',
            'work_from_home' => 'Works from home',
            'student'        => 'Student',
            'not_working'    => 'Not currently working',
            default          => '',
        };

        $activityLabel = match($activityLevel) {
            'sedentary'         => 'Sedentary (little or no exercise)',
            'lightly_active'    => 'Lightly active (1-3 days/week)',
            'moderately_active' => 'Moderately active (3-5 days/week)',
            'very_active'       => 'Very active (6-7 days/week)',
            'extremely_active'  => 'Extremely active (athlete/physical job)',
            default             => '',
        };

        $stmt      = $this->pdo->query('SELECT name FROM exercises WHERE is_active = TRUE ORDER BY name');
        $exercises = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $exerciseList = !empty($exercises) ? implode(', ', $exercises) : 'No exercises available';

        $systemMsg  = "You are FitSense AI, a warm and empathetic personal fitness coach. ";
        $systemMsg .= "You have full access to the member's profile and use it in every response to give deeply personalized advice. ";
        $systemMsg .= "Always address the member by their first name. Be encouraging, specific, and human-centered. ";
        $systemMsg .= "Consider their work schedule, sleep, lifestyle, and health conditions in every recommendation. ";
        $systemMsg .= "Include a brief safety disclaimer at the end of any workout or nutrition advice. ";
        $systemMsg .= "Only provide fitness, nutrition, and wellness guidance.\n\n";

        $systemMsg .= "MEMBER PROFILE:\n";
        $systemMsg .= "Name: {$firstName} {$lastName}\n";
        $systemMsg .= "Age: {$age} | Height: {$heightCm}cm | Current Weight: {$weightKg}kg";
        if ($targetWeightKg) $systemMsg .= " | Target: {$targetWeightKg}kg";
        $systemMsg .= "\nFitness Level: {$fitnessLevel}\n";
        $systemMsg .= "Goal: {$goalType}" . ($goalDescription ? " — {$goalDescription}" : '') . "\n";
        if ($activityLabel)     $systemMsg .= "Activity Level: {$activityLabel}\n";
        if ($workScheduleLabel) $systemMsg .= "Schedule: {$workScheduleLabel}" . ($occupation ? " ({$occupation})" : '') . "\n";
        if ($sleepHours)        $systemMsg .= "Sleep: {$sleepHours}h/night\n";
        if ($dietaryPreference && $dietaryPreference !== 'no_preference') $systemMsg .= "Diet: {$dietaryPreference}\n";
        if ($allergies)         $systemMsg .= "Allergies: {$allergies}\n";
        if ($medicalConditions) $systemMsg .= "Medical: {$medicalConditions}\n";
        if ($address)           $systemMsg .= "Location: {$address}\n";
        $systemMsg .= "\nAVAILABLE EXERCISES: {$exerciseList}";

        if ($this->isStructuredRequest($userMessage)) {
            $systemMsg .= "\n\nFORMAT: If providing a workout plan, return JSON wrapped in ```json ... ```: "
                       . '{"title":"...","exercises":[{"name":"...","sets":N,"reps":N,"rest_seconds":N,"notes":"..."}],"duration_minutes":N}';
            $systemMsg .= "\nIf providing a meal plan: "
                       . '{"title":"...","meals":[{"name":"...","ingredients":["..."],"protein_g":N,"carbs_g":N,"fat_g":N,"calories":N}]}';
        }

        return json_encode([
            'system'  => $systemMsg,
            'message' => $userMessage,
        ]);
    }

    // ─── API Request ──────────────────────────────────────────────────────────

    public function sendRequest(string $promptJson): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'OpenAI API key not configured.'];
        }

        $parts   = json_decode($promptJson, true);
        $system  = $parts['system']  ?? '';
        $message = $parts['message'] ?? '';

        $body = json_encode([
            'model'    => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $message],
            ],
            'max_tokens'  => 1024,
            'temperature' => 0.7,
        ]);

        $maxRetries = 3;
        $lastError  = 'Something went wrong — please try again.';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_TIMEOUT        => $this->timeout,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->apiKey,
                    ],
                ]);
                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if ($response === false) {
                    throw new \RuntimeException('cURL error: ' . $curlErr);
                }

                if ($httpCode === 200) {
                    $decoded = json_decode($response, true);
                    $text    = $decoded['choices'][0]['message']['content'] ?? null;
                    if ($text === null) {
                        error_log('OpenAIClient: unexpected response — ' . $response);
                        $lastError = 'Something went wrong — please try again.';
                        continue;
                    }
                    return ['success' => true, 'text' => $text];
                }

                if (in_array($httpCode, [429, 503], true)) {
                    error_log("OpenAIClient: HTTP {$httpCode} on attempt {$attempt}, retrying…");
                    if ($attempt < $maxRetries) sleep(2);
                    continue;
                }

                if ($httpCode === 401) {
                    return ['success' => false, 'error' => 'AI service authentication failed. Please contact the administrator.'];
                }

                error_log("OpenAIClient: HTTP {$httpCode} — " . $response);
                $lastError = 'Something went wrong — please try again.';
                break;

            } catch (\Throwable $e) {
                error_log('OpenAIClient: exception — ' . $e->getMessage());
                $lastError = 'Something went wrong — please try again.';
                if ($attempt < $maxRetries) sleep(1);
            }
        }

        return ['success' => false, 'error' => $lastError];
    }

    // ─── Structured Response Parsing ─────────────────────────────────────────

    public function parseStructuredResponse(string $text): ?array
    {
        try {
            if (!preg_match('/```json\s*([\s\S]*?)\s*```/i', $text, $matches)) {
                return null;
            }
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return null;
            }
            return $decoded;
        } catch (\Throwable $e) {
            error_log('OpenAIClient::parseStructuredResponse — ' . $e->getMessage());
            return null;
        }
    }
}

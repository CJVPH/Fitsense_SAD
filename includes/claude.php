<?php
/**
 * FitSense — Claude API Client (Anthropic)
 *
 * Drop-in replacement for GeminiClient.
 * Uses Claude claude-sonnet-4-5 via the Anthropic Messages API.
 * API key is read from CLAUDE_API_KEY constant (loaded from .env).
 */

require_once __DIR__ . '/../config/database.php';

class ClaudeClient
{
    private PDO    $pdo;
    private string $apiKey;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->pdo     = Database::getConnection();
        $this->apiKey  = CLAUDE_API_KEY;
        $this->model   = CLAUDE_MODEL;
        $this->timeout = CLAUDE_TIMEOUT_SECONDS;
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

    public function buildPrompt(string $userMessage, array $memberProfile, array $goal, array $history = []): string
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

        // ── System prompt: FitSense knowledge + member profile ────────────────
        $system  = "You are FitSense AI, the official AI fitness coach of Biofitness Gym — a real gym located in Anonas, Cubao, Philippines.\n\n";
        $system .= "ABOUT FITSENSE & BIOFITNESS GYM:\n";
        $system .= "FitSense is a web-based AI fitness management system built specifically for Biofitness Gym. ";
        $system .= "It combines AI-powered coaching, trainer oversight, and member progress tracking. ";
        $system .= "Members get personalized workout and meal plans reviewed by their assigned human trainer. ";
        $system .= "The gym offers memberships and personal training services. ";
        $system .= "FitSense was developed as a capstone project by IT students from the Technological Institute of the Philippines.\n\n";
        $system .= "YOUR ROLE:\n";
        $system .= "You are a warm, empathetic, and knowledgeable personal fitness coach. ";
        $system .= "You have full access to the member's profile and remember everything from this conversation. ";
        $system .= "Always address the member by their first name. Be encouraging, specific, and human-centered. ";
        $system .= "Consider their work schedule, sleep, lifestyle, and health conditions in every recommendation. ";
        $system .= "Include a brief safety disclaimer at the end of any workout or nutrition advice. ";
        $system .= "Only provide fitness, nutrition, and wellness guidance. ";
        $system .= "If asked about the gym location, it is at Biofitness Gym, Cainta, Rizal, Philippines.\n\n";
        $system .= "IMPORTANT: You have memory of this entire conversation. Reference previous messages naturally when relevant.";

        // ── Member profile context ─────────────────────────────────────────────
        $profile  = "MEMBER PROFILE (always up to date):\n";
        $profile .= "Name: {$firstName} {$lastName}\n";
        $profile .= "Age: {$age} | Height: {$heightCm}cm | Current Weight: {$weightKg}kg";
        if ($targetWeightKg) $profile .= " | Target: {$targetWeightKg}kg";
        $profile .= "\nFitness Level: {$fitnessLevel}\n";
        $profile .= "Goal: {$goalType}" . ($goalDescription ? " — {$goalDescription}" : '') . "\n";
        if ($activityLabel)     $profile .= "Activity Level: {$activityLabel}\n";
        if ($workScheduleLabel) $profile .= "Schedule: {$workScheduleLabel}" . ($occupation ? " ({$occupation})" : '') . "\n";
        if ($sleepHours)        $profile .= "Sleep: {$sleepHours}h/night\n";
        if ($dietaryPreference && $dietaryPreference !== 'no_preference') $profile .= "Diet: {$dietaryPreference}\n";
        if ($allergies)         $profile .= "Allergies: {$allergies}\n";
        if ($medicalConditions) $profile .= "Medical: {$medicalConditions}\n";
        if ($address)           $profile .= "Location: {$address}\n";
        $profile .= "\nAVAILABLE EXERCISES IN GYM LIBRARY: {$exerciseList}\n";

        if ($this->isStructuredRequest($userMessage)) {
            $profile .= "\nFORMAT: If providing a workout plan, return JSON wrapped in ```json ... ```: "
                     . '{"title":"...","exercises":[{"name":"...","sets":N,"reps":N,"rest_seconds":N,"notes":"..."}],"duration_minutes":N}' . "\n";
            $profile .= "If providing a meal plan: "
                     . '{"title":"...","meals":[{"name":"...","ingredients":["..."],"protein_g":N,"carbs_g":N,"fat_g":N,"calories":N}]}' . "\n";
        }

        // ── Build conversation history for multi-turn memory ───────────────────
        $messages = [];
        foreach ($history as $msg) {
            $role    = $msg['sender_type'] === 'member' ? 'user' : 'assistant';
            $content = trim($msg['message'] ?? '');
            if ($content === '') continue;
            // Skip recommendation JSON blobs to save tokens
            if ($msg['message_type'] === 'recommendation') {
                $content = '[AI provided a structured workout/meal plan recommendation]';
            }
            $messages[] = ['role' => $role, 'content' => $content];
        }
        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return json_encode([
            'system'   => $system . "\n\n" . $profile,
            'messages' => $messages,
        ]);
    }

    // ─── API Request ──────────────────────────────────────────────────────────

    public function sendRequest(string $promptJson): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'Claude API key not configured. Please contact the administrator.'];
        }

        $parts    = json_decode($promptJson, true);
        $system   = $parts['system']   ?? '';
        $messages = $parts['messages'] ?? [];

        // Ensure alternating user/assistant roles (Claude requirement)
        $cleaned = [];
        $lastRole = null;
        foreach ($messages as $msg) {
            if ($msg['role'] === $lastRole) {
                // Merge consecutive same-role messages
                $cleaned[count($cleaned) - 1]['content'] .= "\n" . $msg['content'];
            } else {
                $cleaned[] = $msg;
                $lastRole  = $msg['role'];
            }
        }
        // Must start with user
        if (empty($cleaned) || $cleaned[0]['role'] !== 'user') {
            $cleaned = array_filter($cleaned, fn($m) => $m['role'] === 'user' || $m['role'] === 'assistant');
            $cleaned = array_values($cleaned);
        }

        $body = json_encode([
            'model'      => $this->model,
            'max_tokens' => 4096,
            'system'     => $system,
            'messages'   => $cleaned,
        ]);

        $maxRetries = 3;
        $lastError  = 'Something went wrong — please try again.';

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $ch = curl_init('https://api.anthropic.com/v1/messages');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_TIMEOUT        => $this->timeout,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'x-api-key: ' . $this->apiKey,
                        'anthropic-version: 2023-06-01',
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
                    $text    = $decoded['content'][0]['text'] ?? null;
                    if ($text === null) {
                        error_log('ClaudeClient: unexpected response structure — ' . $response);
                        $lastError = 'Something went wrong — please try again.';
                        continue;
                    }
                    return ['success' => true, 'text' => $text];
                }

                // Retryable errors
                if (in_array($httpCode, [429, 503, 529], true)) {
                    error_log("ClaudeClient: HTTP {$httpCode} on attempt {$attempt}, retrying…");
                    if ($attempt < $maxRetries) sleep(2);
                    continue;
                }

                // Auth error — no point retrying
                if ($httpCode === 401) {
                    error_log('ClaudeClient: 401 Unauthorized — check API key');
                    return ['success' => false, 'error' => 'AI service authentication failed. Please contact the administrator.'];
                }

                error_log("ClaudeClient: HTTP {$httpCode} — " . $response);
                $lastError = 'Something went wrong — please try again.';
                break;

            } catch (\Throwable $e) {
                error_log('ClaudeClient: exception — ' . $e->getMessage());
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
            error_log('ClaudeClient::parseStructuredResponse — ' . $e->getMessage());
            return null;
        }
    }
}

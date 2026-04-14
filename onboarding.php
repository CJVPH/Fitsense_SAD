<?php
/**
 * FitSense — Member Onboarding (Expanded Health Profile)
 */
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->requireRole('member');

$pdo    = Database::getConnection();
$userId = (int) $_SESSION['user_id'];

// If onboarding already completed, go to chat
$check = $pdo->prepare('SELECT onboarding_completed FROM member_profiles WHERE user_id = ? LIMIT 1');
$check->execute([$userId]);
$row = $check->fetch();
if ($row && (bool) $row['onboarding_completed']) {
    header('Location: chat.php');
    exit;
}

$csrfToken = generateCsrfToken();
$firstName = htmlspecialchars($_SESSION['first_name'] ?? 'there', ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
<title>Set Up Your Profile — FitSense</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.step { display: none; }
.step.active { display: block; }
.option-card { cursor: pointer; transition: all .15s; }
.option-card.selected { border-color: #facc15 !important; background: rgba(250,204,21,.08); }
</style>
</head>
<body class="bg-black min-h-screen flex items-center justify-center px-4 py-10" style="min-width:375px">
<div class="w-full max-w-md">

  <!-- Logo -->
  <div class="text-center mb-6 flex items-center justify-center gap-2">
    <svg class="w-8 h-7 text-yellow-400" viewBox="0 0 640 512" fill="currentColor"><path d="M104 96h-48C42.75 96 32 106.8 32 120V224C14.33 224 0 238.3 0 256c0 17.67 14.33 32 31.1 32L32 392C32 405.3 42.75 416 56 416h48C117.3 416 128 405.3 128 392v-272C128 106.8 117.3 96 104 96zM456 32h-48C394.8 32 384 42.75 384 56V224H256V56C256 42.75 245.3 32 232 32h-48C170.8 32 160 42.75 160 56v400C160 469.3 170.8 480 184 480h48C245.3 480 256 469.3 256 456V288h128v168c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V56C480 42.75 469.3 32 456 32zM608 224V120C608 106.8 597.3 96 584 96h-48C522.8 96 512 106.8 512 120v272c0 13.25 10.75 24 24 24h48c13.25 0 24-10.75 24-24V288c17.67 0 32-14.33 32-32C640 238.3 625.7 224 608 224z"/></svg>
    <span class="text-yellow-400 font-bold text-3xl tracking-tight">FitSense</span>
  </div>

  <!-- Progress bar -->
  <div class="mb-6">
    <div class="flex justify-between text-xs text-zinc-500 mb-2">
      <span id="step-label">Step 1 of 5</span>
      <span id="step-pct">20%</span>
    </div>
    <div class="w-full bg-zinc-800 rounded-full h-1.5">
      <div id="progress-bar" class="bg-yellow-400 h-1.5 rounded-full transition-all duration-300" style="width:20%"></div>
    </div>
  </div>

  <!-- Card -->
  <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-6 shadow-xl">
    <div id="global-error" class="hidden border border-red-500 text-red-300 bg-red-950 rounded-lg p-3 mb-4 text-sm" role="alert"></div>

    <!-- ── STEP 1: Body Measurements ── -->
    <div id="step-1" class="step active">
      <h2 class="text-white text-xl font-bold mb-1">Hi <?php echo $firstName; ?>! Let's set up your profile.</h2>
      <p class="text-zinc-400 text-sm mb-5">Your AI coach uses this to give you personalized advice.</p>

      <div class="space-y-4">
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Current Weight (kg) *</label>
          <input id="weight" type="number" min="20" max="500" step="0.1" placeholder="e.g. 75"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          <div id="weight-err" class="hidden text-red-400 text-xs mt-1"></div>
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Target Weight (kg) <span class="text-zinc-500 font-normal">(optional)</span></label>
          <input id="target_weight" type="number" min="20" max="500" step="0.1" placeholder="e.g. 68"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Height *</label>
          <!-- Unit toggle -->
          <div class="flex gap-2 mb-2">
            <button type="button" id="height-unit-cm"
              onclick="setHeightUnit('cm')"
              class="flex-1 py-1.5 rounded-lg text-xs font-semibold border border-yellow-400 bg-yellow-400 text-black transition-colors">cm</button>
            <button type="button" id="height-unit-ft"
              onclick="setHeightUnit('ft')"
              class="flex-1 py-1.5 rounded-lg text-xs font-semibold border border-zinc-600 text-zinc-400 transition-colors">ft / in</button>
          </div>
          <!-- cm input -->
          <div id="height-cm-input">
            <input id="height" type="number" min="50" max="300" step="0.1" placeholder="e.g. 175"
              class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          </div>
          <!-- ft/in input -->
          <div id="height-ft-input" class="hidden flex gap-2">
            <div class="flex-1">
              <input id="height_ft" type="number" min="1" max="9" placeholder="ft" oninput="convertFtToCm()"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
            <div class="flex-1">
              <input id="height_in" type="number" min="0" max="11" placeholder="in" oninput="convertFtToCm()"
                class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
            </div>
          </div>
          <p id="height-converted" class="hidden text-zinc-400 text-xs mt-1"></p>
          <div id="height-err" class="hidden text-red-400 text-xs mt-1"></div>
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Age *</label>
          <input id="age" type="number" min="10" max="120" placeholder="e.g. 25"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          <div id="age-err" class="hidden text-red-400 text-xs mt-1"></div>
        </div>
      </div>
      <button onclick="goStep(2)" class="mt-6 w-full bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] transition-colors">Next →</button>
    </div>

    <!-- ── STEP 2: Fitness Level & Goal ── -->
    <div id="step-2" class="step">
      <h2 class="text-white text-xl font-bold mb-1">Your Fitness Level & Goal</h2>
      <p class="text-zinc-400 text-sm mb-5">Be honest — this shapes every recommendation your AI coach gives you.</p>

      <p class="text-yellow-400 text-sm font-semibold mb-2">Fitness Level *</p>
      <div class="space-y-2 mb-5">
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="fitness_level" value="beginner" class="sr-only">
          <div><p class="text-white font-semibold text-sm">Beginner</p><p class="text-zinc-400 text-xs mt-0.5">New to exercise or returning after a long break</p></div>
        </label>
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="fitness_level" value="intermediate" class="sr-only">
          <div><p class="text-white font-semibold text-sm">Intermediate</p><p class="text-zinc-400 text-xs mt-0.5">Exercise regularly and comfortable with most movements</p></div>
        </label>
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="fitness_level" value="advanced" class="sr-only">
          <div><p class="text-white font-semibold text-sm">Advanced</p><p class="text-zinc-400 text-xs mt-0.5">Train consistently and handle high-intensity workouts</p></div>
        </label>
      </div>
      <div id="fitness-err" class="hidden text-red-400 text-xs mb-3"></div>

      <p class="text-yellow-400 text-sm font-semibold mb-2">Primary Goal *</p>
      <div class="space-y-2 mb-2">
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="goal_type" value="lose_weight" class="sr-only">
          <div><p class="text-white font-semibold text-sm">🏃 Lose Weight</p><p class="text-zinc-400 text-xs mt-0.5">Burn fat and improve cardiovascular fitness</p></div>
        </label>
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="goal_type" value="build_muscle" class="sr-only">
          <div><p class="text-white font-semibold text-sm">💪 Build Muscle</p><p class="text-zinc-400 text-xs mt-0.5">Increase strength and muscle mass</p></div>
        </label>
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="goal_type" value="improve_stamina" class="sr-only">
          <div><p class="text-white font-semibold text-sm">⚡ Improve Stamina</p><p class="text-zinc-400 text-xs mt-0.5">Boost endurance and overall fitness</p></div>
        </label>
        <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-4 min-h-[44px]">
          <input type="radio" name="goal_type" value="maintain_fitness" class="sr-only">
          <div><p class="text-white font-semibold text-sm">🎯 Maintain Fitness</p><p class="text-zinc-400 text-xs mt-0.5">Stay active and keep current fitness level</p></div>
        </label>
      </div>
      <div id="goal-err" class="hidden text-red-400 text-xs mb-3"></div>

      <div class="flex gap-3 mt-4">
        <button onclick="goStep(1)" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white font-semibold rounded-xl px-4 py-3 min-h-[44px] transition-colors">← Back</button>
        <button onclick="goStep(3)" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] transition-colors">Next →</button>
      </div>
    </div>

    <!-- ── STEP 3: Lifestyle ── -->
    <div id="step-3" class="step">
      <h2 class="text-white text-xl font-bold mb-1">Your Lifestyle</h2>
      <p class="text-zinc-400 text-sm mb-5">Your schedule and daily habits help the AI coach plan around your real life.</p>

      <div class="space-y-4">
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-2">Work Schedule *</label>
          <div class="grid grid-cols-2 gap-2">
            <?php foreach ([
              'day_shift'      => '☀️ Day Shift',
              'night_shift'    => '🌙 Night Shift',
              'rotating_shift' => '🔄 Rotating',
              'work_from_home' => '🏠 Work From Home',
              'student'        => '📚 Student',
              'not_working'    => '🛋️ Not Working',
            ] as $val => $label): ?>
            <label class="option-card border-2 border-zinc-600 rounded-xl p-3 text-center min-h-[44px] flex items-center justify-center">
              <input type="radio" name="work_schedule" value="<?php echo $val; ?>" class="sr-only">
              <span class="text-white text-xs font-medium"><?php echo $label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div id="schedule-err" class="hidden text-red-400 text-xs mt-1"></div>
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Occupation <span class="text-zinc-500 font-normal">(optional)</span></label>
          <input id="occupation" type="text" placeholder="e.g. Nurse, Engineer, Teacher"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-2">Daily Activity Level *</label>
          <div class="space-y-2">
            <?php foreach ([
              'sedentary'         => ['🪑 Sedentary', 'Little or no exercise outside of work'],
              'lightly_active'    => ['🚶 Lightly Active', 'Light exercise 1-3 days/week'],
              'moderately_active' => ['🏃 Moderately Active', 'Moderate exercise 3-5 days/week'],
              'very_active'       => ['🏋️ Very Active', 'Hard exercise 6-7 days/week'],
              'extremely_active'  => ['⚡ Extremely Active', 'Athlete or physical job'],
            ] as $val => [$label, $desc]): ?>
            <label class="option-card flex items-start gap-3 border-2 border-zinc-600 rounded-xl p-3 min-h-[44px]">
              <input type="radio" name="activity_level" value="<?php echo $val; ?>" class="sr-only">
              <div><p class="text-white font-semibold text-sm"><?php echo $label; ?></p><p class="text-zinc-400 text-xs"><?php echo $desc; ?></p></div>
            </label>
            <?php endforeach; ?>
          </div>
          <div id="activity-err" class="hidden text-red-400 text-xs mt-1"></div>
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Average Sleep (hours/night) <span class="text-zinc-500 font-normal">(optional)</span></label>
          <input id="sleep_hours" type="number" min="1" max="24" step="0.5" placeholder="e.g. 7"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
      </div>

      <div class="flex gap-3 mt-6">
        <button onclick="goStep(2)" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white font-semibold rounded-xl px-4 py-3 min-h-[44px] transition-colors">← Back</button>
        <button onclick="goStep(4)" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] transition-colors">Next →</button>
      </div>
    </div>

    <!-- ── STEP 4: Diet & Health ── -->
    <div id="step-4" class="step">
      <h2 class="text-white text-xl font-bold mb-1">Diet & Health</h2>
      <p class="text-zinc-400 text-sm mb-5">Helps your AI coach give safe, appropriate nutrition advice.</p>

      <div class="space-y-4">
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-2">Dietary Preference</label>
          <div class="grid grid-cols-2 gap-2">
            <?php foreach ([
              'no_preference' => '🍽️ No Preference',
              'vegetarian'    => '🥦 Vegetarian',
              'vegan'         => '🌱 Vegan',
              'keto'          => '🥩 Keto',
              'halal'         => '☪️ Halal',
              'gluten_free'   => '🌾 Gluten-Free',
            ] as $val => $label): ?>
            <label class="option-card border-2 border-zinc-600 rounded-xl p-3 text-center min-h-[44px] flex items-center justify-center">
              <input type="radio" name="dietary_preference" value="<?php echo $val; ?>" class="sr-only">
              <span class="text-white text-xs font-medium"><?php echo $label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Food Allergies / Intolerances <span class="text-zinc-500 font-normal">(optional)</span></label>
          <input id="allergies" type="text" placeholder="e.g. Nuts, Dairy, Shellfish"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Medical Conditions <span class="text-zinc-500 font-normal">(optional)</span></label>
          <textarea id="medical_conditions" rows="3" placeholder="e.g. Hypertension, Diabetes, Asthma — or leave blank"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-yellow-400 resize-none"></textarea>
          <p class="text-zinc-500 text-xs mt-1">This helps your AI coach and trainer keep your workouts safe.</p>
        </div>
      </div>

      <div class="flex gap-3 mt-6">
        <button onclick="goStep(3)" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white font-semibold rounded-xl px-4 py-3 min-h-[44px] transition-colors">← Back</button>
        <button onclick="goStep(5)" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] transition-colors">Next →</button>
      </div>
    </div>

    <!-- ── STEP 5: Personal Info ── -->
    <div id="step-5" class="step">
      <h2 class="text-white text-xl font-bold mb-1">Almost done!</h2>
      <p class="text-zinc-400 text-sm mb-5">A few last details to complete your profile.</p>

      <div class="space-y-4">
        <div>
          <label class="block text-yellow-400 text-sm font-semibold mb-1">Address <span class="text-zinc-500 font-normal">(optional)</span></label>
          <input id="address" type="text" placeholder="e.g. Cainta, Rizal"
            class="w-full bg-black border border-zinc-600 text-white rounded-lg px-4 py-3 min-h-[44px] text-sm focus:outline-none focus:border-yellow-400">
          <p class="text-zinc-500 text-xs mt-1">Used to suggest nearby activities or gym schedules.</p>
        </div>
        <div class="bg-zinc-800 border border-zinc-700 rounded-xl p-4">
          <p class="text-yellow-400 font-semibold text-sm mb-1">🤖 Your AI Coach is Ready</p>
          <p class="text-zinc-400 text-xs leading-relaxed">Once you save, your AI fitness coach will know your full profile — your weight, goals, schedule, diet, and health conditions. Every recommendation will be built around <strong class="text-white">you</strong>.</p>
        </div>
      </div>

      <div class="flex gap-3 mt-6">
        <button onclick="goStep(4)" class="flex-1 bg-zinc-700 hover:bg-zinc-600 text-white font-semibold rounded-xl px-4 py-3 min-h-[44px] transition-colors">← Back</button>
        <button id="submit-btn" onclick="submitProfile()" class="flex-1 bg-yellow-400 hover:bg-yellow-300 text-black font-bold rounded-xl px-4 py-3 min-h-[44px] transition-colors">
          Save & Start 🎉
        </button>
      </div>
    </div>

  </div><!-- /card -->
</div><!-- /container -->

<div id="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-xl text-sm font-medium shadow-lg pointer-events-none opacity-0 transition-opacity duration-300 bg-zinc-800 border border-yellow-400 text-yellow-300" role="status" aria-live="polite"></div>

<script>
const CSRF = <?php echo json_encode($csrfToken); ?>;
const TOTAL_STEPS = 5;
let currentStep = 1;

// ── Progress ──────────────────────────────────────────────────────────────────
function updateProgress(step) {
    const pct = Math.round((step / TOTAL_STEPS) * 100);
    document.getElementById('step-label').textContent = 'Step ' + step + ' of ' + TOTAL_STEPS;
    document.getElementById('step-pct').textContent   = pct + '%';
    document.getElementById('progress-bar').style.width = pct + '%';
}

// ── Option card selection ─────────────────────────────────────────────────────
document.querySelectorAll('.option-card').forEach(function(card) {
    card.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (!radio) return;
        const name = radio.name;
        document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
            r.closest('.option-card').classList.remove('selected');
        });
        radio.checked = true;
        this.classList.add('selected');
    });
});

// ── Step navigation ───────────────────────────────────────────────────────────
function goStep(next) {
    if (!validateStep(currentStep)) return;
    document.getElementById('step-' + currentStep).classList.remove('active');
    currentStep = next;
    document.getElementById('step-' + currentStep).classList.add('active');
    updateProgress(currentStep);
    document.getElementById('global-error').classList.add('hidden');
    window.scrollTo(0, 0);
}

function validateStep(step) {
    let ok = true;
    if (step === 1) {
        const w = parseFloat(document.getElementById('weight').value);
        const h = parseFloat(document.getElementById('height').value);
        const a = parseInt(document.getElementById('age').value);
        if (!w || w < 20 || w > 500) { showFieldErr('weight-err', 'Enter a valid weight (20–500 kg).'); ok = false; } else hideFieldErr('weight-err');
        if (!h || h < 50 || h > 300) { showFieldErr('height-err', 'Enter a valid height (50–300 cm).'); ok = false; } else hideFieldErr('height-err');
        if (!a || a < 10 || a > 120) { showFieldErr('age-err', 'Enter a valid age (10–120).'); ok = false; } else hideFieldErr('age-err');
    }
    if (step === 2) {
        if (!document.querySelector('input[name="fitness_level"]:checked')) { showFieldErr('fitness-err', 'Please select your fitness level.'); ok = false; } else hideFieldErr('fitness-err');
        if (!document.querySelector('input[name="goal_type"]:checked'))     { showFieldErr('goal-err', 'Please select your primary goal.'); ok = false; } else hideFieldErr('goal-err');
    }
    if (step === 3) {
        if (!document.querySelector('input[name="work_schedule"]:checked'))  { showFieldErr('schedule-err', 'Please select your work schedule.'); ok = false; } else hideFieldErr('schedule-err');
        if (!document.querySelector('input[name="activity_level"]:checked')) { showFieldErr('activity-err', 'Please select your activity level.'); ok = false; } else hideFieldErr('activity-err');
    }
    return ok;
}

function showFieldErr(id, msg) { const el = document.getElementById(id); if(el){el.textContent=msg;el.classList.remove('hidden');} }
function hideFieldErr(id)      { const el = document.getElementById(id); if(el){el.classList.add('hidden');} }

// ── Submit ────────────────────────────────────────────────────────────────────
async function submitProfile() {
    if (!validateStep(5)) return;
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Saving…';
    document.getElementById('global-error').classList.add('hidden');

    const payload = {
        action:                'save_onboarding',
        csrf_token:            CSRF,
        current_weight_kg:     parseFloat(document.getElementById('weight').value),
        target_weight_kg:      parseFloat(document.getElementById('target_weight').value) || null,
        height_cm:             parseFloat(document.getElementById('height').value),
        age:                   parseInt(document.getElementById('age').value),
        fitness_level:         document.querySelector('input[name="fitness_level"]:checked')?.value,
        goal_type:             document.querySelector('input[name="goal_type"]:checked')?.value,
        work_schedule:         document.querySelector('input[name="work_schedule"]:checked')?.value,
        activity_level:        document.querySelector('input[name="activity_level"]:checked')?.value,
        dietary_preference:    document.querySelector('input[name="dietary_preference"]:checked')?.value || 'no_preference',
        occupation:            document.getElementById('occupation').value.trim() || null,
        sleep_hours_per_night: parseFloat(document.getElementById('sleep_hours').value) || null,
        allergies:             document.getElementById('allergies').value.trim() || null,
        medical_conditions:    document.getElementById('medical_conditions').value.trim() || null,
        address:               document.getElementById('address').value.trim() || null,
    };

    try {
        const res  = await fetch('api/members.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        if (data.success) {
            showToast('Profile saved! Your AI coach is ready. 🎉');
            setTimeout(() => { window.location.href = 'chat.php'; }, 1500);
        } else {
            const errEl = document.getElementById('global-error');
            errEl.textContent = (data.errors || [data.message || 'Something went wrong.']).join(' ');
            errEl.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Save & Start 🎉';
        }
    } catch(e) {
        document.getElementById('global-error').textContent = 'Network error. Please try again.';
        document.getElementById('global-error').classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Save & Start 🎉';
    }
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.opacity = '1';
    setTimeout(() => { t.style.opacity = '0'; }, 3000);
}

// ── Height unit toggle ────────────────────────────────────────────────────────
var _heightUnit = 'cm';
function setHeightUnit(unit) {
    _heightUnit = unit;
    var cmBtn  = document.getElementById('height-unit-cm');
    var ftBtn  = document.getElementById('height-unit-ft');
    var cmDiv  = document.getElementById('height-cm-input');
    var ftDiv  = document.getElementById('height-ft-input');
    var convEl = document.getElementById('height-converted');
    if (unit === 'cm') {
        cmBtn.classList.add('bg-yellow-400','text-black','border-yellow-400');
        cmBtn.classList.remove('text-zinc-400','border-zinc-600');
        ftBtn.classList.remove('bg-yellow-400','text-black','border-yellow-400');
        ftBtn.classList.add('text-zinc-400','border-zinc-600');
        cmDiv.classList.remove('hidden');
        ftDiv.classList.add('hidden');
        convEl.classList.add('hidden');
    } else {
        ftBtn.classList.add('bg-yellow-400','text-black','border-yellow-400');
        ftBtn.classList.remove('text-zinc-400','border-zinc-600');
        cmBtn.classList.remove('bg-yellow-400','text-black','border-yellow-400');
        cmBtn.classList.add('text-zinc-400','border-zinc-600');
        cmDiv.classList.add('hidden');
        ftDiv.classList.remove('hidden');
        convertFtToCm();
    }
}
function convertFtToCm() {
    var ft  = parseFloat(document.getElementById('height_ft').value) || 0;
    var ins = parseFloat(document.getElementById('height_in').value) || 0;
    var cm  = (ft * 30.48) + (ins * 2.54);
    var convEl = document.getElementById('height-converted');
    if (ft > 0 || ins > 0) {
        document.getElementById('height').value = cm.toFixed(1);
        convEl.textContent = '≈ ' + cm.toFixed(1) + ' cm';
        convEl.classList.remove('hidden');
    } else {
        document.getElementById('height').value = '';
        convEl.classList.add('hidden');
    }
}

updateProgress(1);
</script>
</body>
</html>

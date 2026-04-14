-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 05:25 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fitsense_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_recommendations`
--

CREATE TABLE `ai_recommendations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('workout','meal_plan','general_advice') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `ai_prompt` text DEFAULT NULL,
  `ai_response` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','modified') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `trainer_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_recommendations`
--

INSERT INTO `ai_recommendations` (`id`, `user_id`, `type`, `title`, `content`, `ai_prompt`, `ai_response`, `status`, `reviewed_by`, `trainer_notes`, `created_at`, `updated_at`) VALUES
(1, 16, 'workout', 'Beginner Full-Body Foundation', '{\"title\":\"Beginner Full-Body Foundation\",\"exercises\":[{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":12,\"rest_seconds\":60,\"notes\":\"Keep your chest up, push your hips back as if sitting in a chair, and go as deep as comfortable while maintaining good form.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":8,\"rest_seconds\":90,\"notes\":\"If regular push-ups are too challenging, start with push-ups on your knees or against an elevated surface (like a bench). Focus on a controlled movement down and up.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Perform 10 reps per leg (20 total per set). Start with very light dumbbells or even just your bodyweight to master the form. Step forward, lowering your hips until both knees are bent at approximately a 90-degree angle.\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":12,\"rest_seconds\":60,\"notes\":\"Sit upright, keeping your back straight. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the movement both on the pull and on the release.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":0,\"rest_seconds\":60,\"notes\":\"Hold for 30-45 seconds. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"}],\"duration_minutes\":40}', '[SYSTEM CONTEXT]\nYou are an AI fitness coach for FitSense gym. Respond in a friendly, motivating tone.\nAlways include a safety disclaimer at the end of any workout or nutrition advice.\nOnly provide fitness, nutrition, and wellness guidance. Do not answer unrelated questions.\n\n[MEMBER PROFILE]\nName: Karl\nAge: 21 | Height: 178.00cm | Weight: 80.00kg\nFitness Level: beginner\nActive Goal:  — \n\n[AVAILABLE EXERCISES]\nBarbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\n\n[FORMAT INSTRUCTION]\nIf providing a workout plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"exercises\":[{\"name\":\"...\",\"sets\":N,\"reps\":N,\"rest_seconds\":N,\"notes\":\"...\"}],\"duration_minutes\":N}\nIf providing a meal plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"meals\":[{\"name\":\"...\",\"ingredients\":[\"...\"],\"protein_g\":N,\"carbs_g\":N,\"fat_g\":N,\"calories\":N}]}\n\n[USER MESSAGE]\nCreate a workout plan', 'Hey Karl! It\'s awesome to see you ready to kickstart your fitness journey with a workout plan. As a beginner, focusing on fundamental movements and building a strong foundation is key. This full-body workout is designed to help you get started safely and effectively. Let\'s get moving!\n\n```json\n{\n  \"title\": \"Beginner Full-Body Foundation\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Keep your chest up, push your hips back as if sitting in a chair, and go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"If regular push-ups are too challenging, start with push-ups on your knees or against an elevated surface (like a bench). Focus on a controlled movement down and up.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Perform 10 reps per leg (20 total per set). Start with very light dumbbells or even just your bodyweight to master the form. Step forward, lowering your hips until both knees are bent at approximately a 90-degree angle.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Sit upright, keeping your back straight. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the movement both on the pull and on the release.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 0,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30-45 seconds. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\nRemember to warm up for 5-10 minutes before starting (light cardio like jogging in place or dynamic stretches) and cool down for 5-10 minutes afterward (static stretches). Focus on proper form for each exercise – it\'s more important than how many reps you do or how much weight you lift! You\'ve got this, Karl!\n\n---\n**Safety Disclaimer:**\nPlease consult with a healthcare professional or your doctor before starting any new fitness program, especially if you have any pre-existing health conditions or concerns. Always listen to your body and stop if you experience pain or discomfort. Ensure you are using correct form to prevent injuries.', 'pending', NULL, NULL, '2026-03-20 13:41:57', NULL),
(2, 16, 'workout', 'Karl\'s Beginner Full-Body Strength', '{\"title\":\"Karl\'s Beginner Full-Body Strength\",\"exercises\":[{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":12,\"rest_seconds\":60,\"notes\":\"Focus on keeping your chest up and core engaged. Go as deep as comfortable while maintaining good form.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":8,\"rest_seconds\":60,\"notes\":\"If traditional push-ups are challenging, try them on your knees or against a wall. Keep your body in a straight line from head to heels\\/knees.\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release on the way back.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":8,\"rest_seconds\":60,\"notes\":\"Step forward, lowering until both knees are at 90 degrees. Keep your front knee aligned with your ankle. Use light dumbbells to start.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":30,\"rest_seconds\":60,\"notes\":\"Hold for 30 seconds. Keep your body in a straight line from head to heels. Engage your core and glutes, don\'t let your hips sag or rise too high.\"},{\"name\":\"Treadmill Running\",\"sets\":1,\"reps\":15,\"rest_seconds\":0,\"notes\":\"Start with a brisk walk for 5 minutes, then gradually increase to a light jog if comfortable for the remaining 10 minutes. Focus on a steady, comfortable pace.\"}],\"duration_minutes\":50}', '[SYSTEM CONTEXT]\nYou are an AI fitness coach for FitSense gym. Respond in a friendly, motivating tone.\nAlways include a safety disclaimer at the end of any workout or nutrition advice.\nOnly provide fitness, nutrition, and wellness guidance. Do not answer unrelated questions.\n\n[MEMBER PROFILE]\nName: Karl\nAge: 21 | Height: 178.00cm | Weight: 80.00kg\nFitness Level: beginner\nActive Goal:  — \n\n[AVAILABLE EXERCISES]\nBarbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\n\n[FORMAT INSTRUCTION]\nIf providing a workout plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"exercises\":[{\"name\":\"...\",\"sets\":N,\"reps\":N,\"rest_seconds\":N,\"notes\":\"...\"}],\"duration_minutes\":N}\nIf providing a meal plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"meals\":[{\"name\":\"...\",\"ingredients\":[\"...\"],\"protein_g\":N,\"carbs_g\":N,\"fat_g\":N,\"calories\":N}]}\n\n[USER MESSAGE]\nWorkout Plan', 'Hey Karl! Awesome to hear you\'re ready to dive into a workout plan. As a beginner, focusing on foundational movements is key to building strength and good habits. This full-body workout is designed to get you started, focusing on proper form and engaging different muscle groups. You\'ve got this!\n\nHere\'s a plan to kick things off:\n\n```json\n{\n  \"title\": \"Karl\'s Beginner Full-Body Strength\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Focus on keeping your chest up and core engaged. Go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 60,\n      \"notes\": \"If traditional push-ups are challenging, try them on your knees or against a wall. Keep your body in a straight line from head to heels/knees.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release on the way back.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 60,\n      \"notes\": \"Step forward, lowering until both knees are at 90 degrees. Keep your front knee aligned with your ankle. Use light dumbbells to start.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 30,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30 seconds. Keep your body in a straight line from head to heels. Engage your core and glutes, don\'t let your hips sag or rise too high.\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 15,\n      \"rest_seconds\": 0,\n      \"notes\": \"Start with a brisk walk for 5 minutes, then gradually increase to a light jog if comfortable for the remaining 10 minutes. Focus on a steady, comfortable pace.\"\n    }\n  ],\n  \"duration_minutes\": 50\n}\n```\n\nRemember to listen to your body and adjust as needed. Let\'s get moving!\n\n***\n\n**Safety Disclaimer:** Always consult with a healthcare professional before starting any new fitness program, especially if you have any pre-existing health conditions or concerns. Pay close attention to your body\'s signals and stop immediately if you experience any pain or discomfort. Proper form is crucial to prevent injuries, so consider watching video demonstrations or asking a gym staff member for form checks when trying new exercises.', 'pending', NULL, NULL, '2026-03-31 05:47:47', NULL),
(3, 16, 'workout', 'Full-Body Beginner Blast!', '{\"title\":\"Full-Body Beginner Blast!\",\"exercises\":[{\"name\":\"Treadmill Running\",\"sets\":1,\"reps\":5,\"rest_seconds\":0,\"notes\":\"Light jog or brisk walk to warm up your muscles and get your heart rate up.\"},{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Focus on keeping your chest up and your knees tracking over your toes. Go as deep as comfortable.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":8,\"rest_seconds\":60,\"notes\":\"If regular push-ups are too challenging, perform them on your knees. Keep your core tight!\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Sit tall, pull the handle towards your belly button, squeezing your shoulder blades together.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":8,\"rest_seconds\":90,\"notes\":\"(Per leg) Take a step forward, lowering your back knee towards the ground. Keep your front knee behind your toes.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":30,\"rest_seconds\":60,\"notes\":\"Hold your body in a straight line from head to heels, engaging your core and glutes. Don\'t let your hips sag!\"}],\"duration_minutes\":45}', '[SYSTEM CONTEXT]\nYou are an AI fitness coach for FitSense gym. Respond in a friendly, motivating tone.\nAlways include a safety disclaimer at the end of any workout or nutrition advice.\nOnly provide fitness, nutrition, and wellness guidance. Do not answer unrelated questions.\n\n[MEMBER PROFILE]\nName: Karl\nAge: 21 | Height: 178.00cm | Weight: 80.00kg\nFitness Level: beginner\nActive Goal:  — \n\n[AVAILABLE EXERCISES]\nBarbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\n\n[FORMAT INSTRUCTION]\nIf providing a workout plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"exercises\":[{\"name\":\"...\",\"sets\":N,\"reps\":N,\"rest_seconds\":N,\"notes\":\"...\"}],\"duration_minutes\":N}\nIf providing a meal plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"meals\":[{\"name\":\"...\",\"ingredients\":[\"...\"],\"protein_g\":N,\"carbs_g\":N,\"fat_g\":N,\"calories\":N}]}\n\n[USER MESSAGE]\nWorkout Plan', 'Hey Karl! That\'s awesome you\'re ready to dive into a workout plan. As a beginner, it\'s all about building a solid foundation, focusing on good form, and getting your body used to moving. This full-body workout is a fantastic starting point!\n\nHere\'s a plan designed to hit all your major muscle groups and get you feeling strong:\n\n```json\n{\"title\":\"Full-Body Beginner Blast!\",\"exercises\":[{\"name\":\"Treadmill Running\",\"sets\":1,\"reps\":5,\"rest_seconds\":0,\"notes\":\"Light jog or brisk walk to warm up your muscles and get your heart rate up.\"},{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Focus on keeping your chest up and your knees tracking over your toes. Go as deep as comfortable.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":8,\"rest_seconds\":60,\"notes\":\"If regular push-ups are too challenging, perform them on your knees. Keep your core tight!\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Sit tall, pull the handle towards your belly button, squeezing your shoulder blades together.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":8,\"rest_seconds\":90,\"notes\":\"(Per leg) Take a step forward, lowering your back knee towards the ground. Keep your front knee behind your toes.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":30,\"rest_seconds\":60,\"notes\":\"Hold your body in a straight line from head to heels, engaging your core and glutes. Don\'t let your hips sag!\"}],\"duration_minutes\":45}\n```\n\nRemember, consistency is key! Aim to do this workout 2-3 times a week, with rest days in between. Don\'t be afraid to adjust the reps or sets as you get stronger. You\'ve got this!\n\n***Safety Disclaimer:*** *Always consult with a healthcare professional before starting any new exercise program, especially if you have any pre-existing health conditions or concerns. Listen to your body, and stop if you experience pain or discomfort. Proper form is crucial to prevent injuries, so consider watching video demonstrations or asking a FitSense coach for guidance on new exercises.*', 'pending', NULL, NULL, '2026-03-31 05:57:12', NULL),
(4, 16, 'workout', 'Beginner Full-Body Blast', '{\"title\":\"Beginner Full-Body Blast\",\"exercises\":[{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":12,\"rest_seconds\":60,\"notes\":\"Stand with feet shoulder-width apart. Keep your chest up, look straight ahead, and lower your hips as if sitting in a chair. Go as deep as comfortable while maintaining good form.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"If traditional push-ups are too challenging, try them on your knees or against a sturdy wall. Focus on lowering your chest towards the ground and pushing back up with control.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Perform 10 repetitions on *each leg* per set. Take a step forward, lowering your back knee until it\'s just above the ground. Ensure your front knee doesn\'t go past your toes. Start with bodyweight if dumbbells feel too heavy.\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":12,\"rest_seconds\":60,\"notes\":\"Sit tall with your chest up. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release back to the starting position.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":0,\"rest_seconds\":60,\"notes\":\"Hold for 30-45 seconds per set. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"}],\"duration_minutes\":45}', '[SYSTEM CONTEXT]\nYou are an AI fitness coach for FitSense gym. Respond in a friendly, motivating tone.\nAlways include a safety disclaimer at the end of any workout or nutrition advice.\nOnly provide fitness, nutrition, and wellness guidance. Do not answer unrelated questions.\n\n[MEMBER PROFILE]\nName: Karl\nAge: 21 | Height: 178.00cm | Weight: 80.00kg\nFitness Level: beginner\nActive Goal:  — \n\n[AVAILABLE EXERCISES]\nBarbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\n\n[FORMAT INSTRUCTION]\nIf providing a workout plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"exercises\":[{\"name\":\"...\",\"sets\":N,\"reps\":N,\"rest_seconds\":N,\"notes\":\"...\"}],\"duration_minutes\":N}\nIf providing a meal plan, return it in this exact JSON structure wrapped in ```json ... ```:\n{\"title\":\"...\",\"meals\":[{\"name\":\"...\",\"ingredients\":[\"...\"],\"protein_g\":N,\"carbs_g\":N,\"fat_g\":N,\"calories\":N}]}\n\n[USER MESSAGE]\nCreate a workout plan', 'Hey Karl! It\'s fantastic that you\'re ready to dive into your fitness journey with a workout plan! As a beginner, focusing on foundational movements and proper form is key, and we\'ll get you started right.\n\nThis plan is designed to be a great full-body workout to build strength and get comfortable with some core exercises. Remember to listen to your body and focus on good technique!\n\n```json\n{\n  \"title\": \"Beginner Full-Body Blast\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Stand with feet shoulder-width apart. Keep your chest up, look straight ahead, and lower your hips as if sitting in a chair. Go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"If traditional push-ups are too challenging, try them on your knees or against a sturdy wall. Focus on lowering your chest towards the ground and pushing back up with control.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Perform 10 repetitions on *each leg* per set. Take a step forward, lowering your back knee until it\'s just above the ground. Ensure your front knee doesn\'t go past your toes. Start with bodyweight if dumbbells feel too heavy.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Sit tall with your chest up. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release back to the starting position.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 0,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30-45 seconds per set. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"\n    }\n  ],\n  \"duration_minutes\": 45\n}\n```\n\nThis workout is a fantastic starting point for building a strong foundation, Karl! Aim to complete it 2-3 times a week, with a rest day in between. You\'ve got this!\n\n***\n\n**Safety Disclaimer:** Always consult with a healthcare professional before starting any new fitness program, especially if you have any pre-existing medical conditions. Listen to your body, maintain proper form, and stop immediately if you experience any pain or discomfort. Stay hydrated!', 'pending', NULL, NULL, '2026-03-31 06:00:29', NULL),
(6, 21, 'workout', 'Push A — Chest, Shoulders & Triceps', '{\"title\":\"Push A \\u2014 Chest, Shoulders & Triceps\",\"exercises\":[{\"name\":\"Push-Up\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Keep core tight and body in a straight line. Great chest and tricep activator for beginners, Karl!\"},{\"name\":\"Dumbbell Bench Press\",\"sets\":3,\"reps\":10,\"rest_seconds\":90,\"notes\":\"Use a light-to-moderate weight. Focus on controlled movement \\u2014 slow down, push up. Build that mind-muscle connection!\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":1,\"rest_seconds\":60,\"notes\":\"Hold for 20\\u201330 seconds per set. Builds core stability which supports ALL your other lifts.\"}],\"duration_minutes\":35}', '{\"system\":\"You are FitSense AI, a warm and empathetic personal fitness coach. You have full access to the member\'s profile and use it in every response to give deeply personalized advice. Always address the member by their first name. Be encouraging, specific, and human-centered. Consider their work schedule, sleep, lifestyle, and health conditions in every recommendation. Include a brief safety disclaimer at the end of any workout or nutrition advice. Only provide fitness, nutrition, and wellness guidance.\",\"context\":\"MEMBER PROFILE:\\nName: Karl \\nAge: 21 | Height: 180.30cm | Current Weight: 75.00kg | Target: 65.00kg\\nFitness Level: beginner\\nGoal: lose_weight\\nActivity Level: Lightly active (1-3 days\\/week)\\nSchedule: Day shift worker (Student)\\nSleep: 6.0h\\/night\\nAllergies: Shrimp\\nMedical: Diabetes\\nLocation: Angono, Rizal\\n\\nAVAILABLE EXERCISES: Barbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\\n\\nFORMAT: If providing a workout plan, return JSON wrapped in ```json ... ```: {\\\"title\\\":\\\"...\\\",\\\"exercises\\\":[{\\\"name\\\":\\\"...\\\",\\\"sets\\\":N,\\\"reps\\\":N,\\\"rest_seconds\\\":N,\\\"notes\\\":\\\"...\\\"}],\\\"duration_minutes\\\":N}\\nIf providing a meal plan: {\\\"title\\\":\\\"...\\\",\\\"meals\\\":[{\\\"name\\\":\\\"...\\\",\\\"ingredients\\\":[\\\"...\\\"],\\\"protein_g\\\":N,\\\"carbs_g\\\":N,\\\"fat_g\\\":N,\\\"calories\\\":N}]}\\n\",\"message\":\"Create a personalized workout plan for me based on my fitness level and goals.  I want you to generate me a table 6 day workout PPL.\"}', '# Hey Karl! 💪 Let\'s Build Your 6-Day PPL Plan!\n\nI love the ambition, Karl! A **Push-Pull-Legs** split is a fantastic choice for your weight loss goal. Since you\'re a beginner, I\'ve kept the volume manageable while still being effective enough to burn calories and build the lean muscle that\'ll help you drop those 10kg. I\'ve also designed this with your student day shift schedule and 6-hour sleep in mind — so nothing too brutal that\'ll wipe you out! 😊\n\n---\n\n> ⚠️ **Before we dive in** — Karl, since you have **diabetes**, please monitor your blood sugar **before and after** every session. Keep a fast-acting carb snack nearby during workouts. Consult your physician before starting this program.\n\n---\n\n## 🗓️ Your 6-Day PPL Split Overview\n\n| Day | Focus | Type |\n|-----|-------|------|\n| **Monday** | Push A | Chest, Shoulders, Triceps |\n| **Tuesday** | Pull A | Back, Biceps |\n| **Wednesday** | Legs A | Quads, Hamstrings, Glutes |\n| **Thursday** | Push B | Chest, Shoulders, Triceps |\n| **Friday** | Pull B | Back, Biceps |\n| **Saturday** | Legs B | Quads, Hamstrings, Glutes |\n| **Sunday** | 🛌 Rest | Recovery |\n\n---\n\n## 📋 Workout Plans (JSON Format)\n\n### 🔵 DAY 1 — Push A\n\n```json\n{\n  \"title\": \"Push A — Chest, Shoulders & Triceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Keep core tight and body in a straight line. Great chest and tricep activator for beginners, Karl!\"\n    },\n    {\n      \"name\": \"Dumbbell Bench Press\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Use a light-to-moderate weight. Focus on controlled movement — slow down, push up. Build that mind-muscle connection!\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 1,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 20–30 seconds per set. Builds core stability which supports ALL your other lifts.\"\n    }\n  ],\n  \"duration_minutes\": 35\n}\n```\n\n---\n\n### 🔴 DAY 2 — Pull A\n\n```json\n{\n  \"title\": \"Pull A — Back & Biceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Pull to your lower chest, squeeze your shoulder blades together. Light weight to nail the form first, Karl!\"\n    },\n    {\n      \"name\": \"Pull-Up\",\n      \"sets\": 3,\n      \"reps\": 5,\n      \"rest_seconds\": 90,\n      \"notes\": \"5 reps is a solid beginner target. Use an assisted machine or resistance band if needed — no shame in that!\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 1,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold 20–30 seconds. Core is your foundation — we train it every day!\"\n    }\n  ],\n  \"duration_minutes\": 35\n}\n```\n\n---\n\n### 🟢 DAY 3 — Legs A\n\n```json\n{\n  \"title\": \"Legs A — Quads, Hamstrings & Glutes\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Feet shoulder-width apart, chest up, sit back like you\'re reaching for a chair. Perfect form before adding weight!\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"10 reps each leg. Keep your front knee behind your toes. Great for balance and glute activation, Karl!\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 1,\n      \"rest_seconds\": 0,\n      \"notes\": \"15-minute light jog or brisk walk at 5–6 km/h. This cardio finisher will torch extra calories and support your 65kg goal!\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\n---\n\n### 🔵 DAY 4 — Push B\n\n```json\n{\n  \"title\": \"Push B — Chest, Shoulders & Triceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Dumbbell Bench Press\",\n      \"sets\": 4,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"One more set vs Push A — slightly heavier if you felt comfortable Monday. Progressive overload is how you grow, Karl!\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"2 extra reps vs Monday — you\'re already improving! Try incline push-ups to target upper chest if regular ones feel easy.\"\n    },\n    {\n      \"name\": \"Burpee\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"The ultimate fat-burner! Full body explosive movement. Take your time between reps — quality over speed as a beginner.\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\n---\n\n### 🔴 DAY 5 — Pull B\n\n```json\n{\n  \"title\": \"Pull B — Back & Biceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Pull-Up\",\n      \"sets\": 4,\n      \"reps\": 5,\n      \"rest_seconds\": 90,\n      \"notes\": \"One more set vs Pull A. Even if you can only do 3–4 reps, that\'s still progress. Every rep counts, Karl!\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 4,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"Slightly heavier than Pull A if form was solid. Imagine crushing a pencil between your shoulder blades on every pull.\"\n    },\n    {\n      \"name\": \"Barbell Deadlift\",\n      \"sets\": 3,\n      \"reps\": 6,\n      \"rest_seconds\": 120,\n      \"notes\": \"START VERY LIGHT — just the bar or 20kg max. Deadlifts are a powerhouse move but form is everything. Hinge at hips, flat back, bar close to shins.\"\n    }\n  ],\n  \"duration_minutes\": 45\n}\n```\n\n---\n\n### 🟢 DAY 6 — Legs B\n\n```json\n{\n  \"title\": \"Legs B — Quads, Hamstrings & Glutes + Cardio\",\n  \"exercises\": [\n    {\n      \"name\": \"Barbell Deadlift\",\n      \"sets\": 3,\n      \"reps\": 6,\n      \"rest_seconds\": 120,\n      \"notes\": \"Same as Pull B — very light weight, perfect form. Deadlifts on leg day targets hamstrings and glutes heavily. You\'ve got this!\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 4,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"12 reps each leg — up from Legs A. Add light dumbbells if bodyweight lunges felt easy on Wednesday.\"\n    },\n    {\n      \"name\": \"Burpee\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Finish strong, Karl! 10 burpees per set — this will really fire up your metabolism and accelerate that 10kg weight loss goal.\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 1,\n      \"rest_seconds\": 0,\n      \"notes\": \"20-minute brisk walk or light jog to close out the week. Your weekly cardio anchor — keep it consistent!\"\n    }\n  ],\n  \"duration_minutes\": 50\n}\n```\n\n---\n\n## 📊 Weekly Summary Table\n\n| Day | Workout | Duration | Key Focus |\n|-----|---------|----------|-----------|\n| Mon | Push A | ~35 min | Form & Foundation |\n| Tue | Pull A | ~35 min | Back Activation |\n| Wed | Legs A | ~40 min | Lower Body + Cardio |\n| Thu | Push B | ~40 min | Volume Increase |\n| Fri | Pull B | ~45 min | Strength + Deadlift Intro |\n| Sat | Legs B | ~50 min | Full Lower + Cardio Finisher |\n| Sun | Rest | — | 💤 Recovery |\n| | **TOTAL** | **~245 min** | |\n\n---\n\n## 💡 Karl\'s Personal Tips\n\n- 🩸 **Diabetes Check** — Test blood sugar before & after every session. Aim to work out 1–2 hours after a meal for stable glucose levels\n- 😴 **Sleep** — Try to protect your 6 hours religiously. Sleep is when your body actually burns fat and repairs muscle\n- 💧 **Hydration** — Drink 2.5–3L of water daily, especially as a student in Angono\'s warm climate\n- 📈 **Progressive Overload** — Add 1–2 reps or slightly more weight every 1–2 weeks. That\'s how 75kg becomes 65kg!\n- 🎯 **Consistency > Perfection** — Missing one day is fine. Missing a week is where progress dies\n\n---\n\n> ⚠️ **Safety Disclaimer:** This workout plan is designed for general fitness purposes. Karl, because you have **Type 1/Type 2 Diabetes**, please consult your physician or endocrinologist before beginning this program. Monitor blood glucose levels regularly and stop exercising immediately if you feel dizzy, faint, or experience hypoglycemic symptoms. Always warm up for 5 minutes before each session and cool down/stretch for 5 minutes after. FitSense AI is not a substitute for professional medical advice.\n\n---\n\nYou\'ve got everything you need, Karl — now let\'s turn that goal of **65kg** into reality! 🔥 Want me to also create a **meal plan** to complement this workout? 🥗', 'pending', NULL, NULL, '2026-04-14 03:05:49', NULL),
(7, 21, 'meal_plan', 'Karl\'s Diabetes-Friendly Weight Loss Meal Plan', '{\"title\":\"Karl\'s Diabetes-Friendly Weight Loss Meal Plan\",\"meals\":[{\"name\":\"\\ud83c\\udf05 Breakfast \\u2014 Egg & Oats Power Bowl (7:00 AM)\",\"ingredients\":[\"\\u00bd cup rolled oats (not instant)\",\"2 whole eggs (scrambled or boiled)\",\"1 medium banana (sliced on top)\",\"1 tsp chia seeds\",\"1 glass of water or unsweetened black coffee\"],\"protein_g\":18,\"carbs_g\":42,\"fat_g\":10,\"calories\":330},{\"name\":\"\\u2600\\ufe0f Mid-Morning Snack \\u2014 Protein Fruit Combo (10:00 AM)\",\"ingredients\":[\"1 small apple\",\"2 tbsp peanut butter (no added sugar)\",\"1 glass of water\"],\"protein_g\":7,\"carbs_g\":20,\"fat_g\":9,\"calories\":185},{\"name\":\"\\ud83c\\udf24\\ufe0f Lunch \\u2014 Grilled Chicken & Brown Rice Bowl (12:30 PM)\",\"ingredients\":[\"120g grilled chicken breast\",\"\\u00be cup cooked brown rice\",\"1 cup steamed mixed veggies (kangkong, sitaw, carrots)\",\"1 tsp olive oil or coconut oil for cooking\",\"Calamansi + pepper for seasoning (no salty sauces)\"],\"protein_g\":38,\"carbs_g\":48,\"fat_g\":9,\"calories\":430},{\"name\":\"\\ud83c\\udf25\\ufe0f Afternoon Snack \\u2014 Boiled Egg & Veggie Sticks (3:30 PM)\",\"ingredients\":[\"1 hard-boiled egg\",\"\\u00bd cucumber (sliced)\",\"5 cherry tomatoes\",\"1 glass of water\"],\"protein_g\":7,\"carbs_g\":7,\"fat_g\":5,\"calories\":100},{\"name\":\"\\ud83c\\udf19 Dinner \\u2014 Bangus & Sweet Potato Plate (7:00 PM)\",\"ingredients\":[\"130g grilled or baked bangus (milkfish)\",\"1 medium sweet potato (boiled)\",\"1 cup pinakbet without bagoong (use patis sparingly)\",\"1 glass of water\"],\"protein_g\":35,\"carbs_g\":38,\"fat_g\":12,\"calories\":400},{\"name\":\"\\ud83c\\udf1b Light Evening Snack (Optional) \\u2014 Greek Yogurt (9:00 PM)\",\"ingredients\":[\"\\u00be cup plain low-fat Greek yogurt (unsweetened)\",\"A few pumpkin seeds or sunflower seeds\",\"1 glass of water\"],\"protein_g\":15,\"carbs_g\":10,\"fat_g\":4,\"calories\":135}]}', '{\"system\":\"You are FitSense AI, a warm and empathetic personal fitness coach. You have full access to the member\'s profile and use it in every response to give deeply personalized advice. Always address the member by their first name. Be encouraging, specific, and human-centered. Consider their work schedule, sleep, lifestyle, and health conditions in every recommendation. Include a brief safety disclaimer at the end of any workout or nutrition advice. Only provide fitness, nutrition, and wellness guidance.\",\"context\":\"MEMBER PROFILE:\\nName: Karl \\nAge: 21 | Height: 180.30cm | Current Weight: 75.00kg | Target: 65.00kg\\nFitness Level: beginner\\nGoal: lose_weight\\nActivity Level: Lightly active (1-3 days\\/week)\\nSchedule: Day shift worker (Student)\\nSleep: 6.0h\\/night\\nAllergies: Shrimp\\nMedical: Diabetes\\nLocation: Angono, Rizal\\n\\nAVAILABLE EXERCISES: Barbell Deadlift, Bodyweight Squat, Burpee, Dumbbell Bench Press, Dumbbell Lunges, Plank, Pull-Up, Push-Up, Seated Cable Row, Treadmill Running\\n\\nFORMAT: If providing a workout plan, return JSON wrapped in ```json ... ```: {\\\"title\\\":\\\"...\\\",\\\"exercises\\\":[{\\\"name\\\":\\\"...\\\",\\\"sets\\\":N,\\\"reps\\\":N,\\\"rest_seconds\\\":N,\\\"notes\\\":\\\"...\\\"}],\\\"duration_minutes\\\":N}\\nIf providing a meal plan: {\\\"title\\\":\\\"...\\\",\\\"meals\\\":[{\\\"name\\\":\\\"...\\\",\\\"ingredients\\\":[\\\"...\\\"],\\\"protein_g\\\":N,\\\"carbs_g\\\":N,\\\"fat_g\\\":N,\\\"calories\\\":N}]}\\n\",\"message\":\"Give me a personalized meal plan and nutrition guide based on my fitness goals\"}', '# 🥗 Your Personalized Nutrition Plan, Karl!\n\nI love that you\'re taking this step! Since you\'re a 21-year-old student in Angono working toward losing 10kg, I\'ve built this plan carefully around your **diabetes**, your **shrimp allergy**, your student schedule, and your need for **blood sugar-friendly** foods. Let\'s fuel your body the smart way! 💪\n\n---\n\n## 🎯 Your Daily Nutrition Targets\n\n| Metric | Value |\n|---|---|\n| **Goal Calories** | ~1,750 kcal/day |\n| **Protein** | ~130g (preserve muscle while losing fat) |\n| **Carbs** | ~175g (low-GI focused for diabetes) |\n| **Fats** | ~55g (heart-healthy sources) |\n| **Deficit** | ~500 kcal below maintenance |\n\n> ⚠️ Because you have **diabetes**, carbohydrate timing and quality are especially important. Low-GI carbs help keep your blood sugar stable throughout your day as a student.\n\n---\n\n## 🍽️ Your Personalized Meal Plan\n\n```json\n{\n  \"title\": \"Karl\'s Diabetes-Friendly Weight Loss Meal Plan\",\n  \"meals\": [\n    {\n      \"name\": \"🌅 Breakfast — Egg & Oats Power Bowl (7:00 AM)\",\n      \"ingredients\": [\n        \"½ cup rolled oats (not instant)\",\n        \"2 whole eggs (scrambled or boiled)\",\n        \"1 medium banana (sliced on top)\",\n        \"1 tsp chia seeds\",\n        \"1 glass of water or unsweetened black coffee\"\n      ],\n      \"protein_g\": 18,\n      \"carbs_g\": 42,\n      \"fat_g\": 10,\n      \"calories\": 330\n    },\n    {\n      \"name\": \"☀️ Mid-Morning Snack — Protein Fruit Combo (10:00 AM)\",\n      \"ingredients\": [\n        \"1 small apple\",\n        \"2 tbsp peanut butter (no added sugar)\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 7,\n      \"carbs_g\": 20,\n      \"fat_g\": 9,\n      \"calories\": 185\n    },\n    {\n      \"name\": \"🌤️ Lunch — Grilled Chicken & Brown Rice Bowl (12:30 PM)\",\n      \"ingredients\": [\n        \"120g grilled chicken breast\",\n        \"¾ cup cooked brown rice\",\n        \"1 cup steamed mixed veggies (kangkong, sitaw, carrots)\",\n        \"1 tsp olive oil or coconut oil for cooking\",\n        \"Calamansi + pepper for seasoning (no salty sauces)\"\n      ],\n      \"protein_g\": 38,\n      \"carbs_g\": 48,\n      \"fat_g\": 9,\n      \"calories\": 430\n    },\n    {\n      \"name\": \"🌥️ Afternoon Snack — Boiled Egg & Veggie Sticks (3:30 PM)\",\n      \"ingredients\": [\n        \"1 hard-boiled egg\",\n        \"½ cucumber (sliced)\",\n        \"5 cherry tomatoes\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 7,\n      \"carbs_g\": 7,\n      \"fat_g\": 5,\n      \"calories\": 100\n    },\n    {\n      \"name\": \"🌙 Dinner — Bangus & Sweet Potato Plate (7:00 PM)\",\n      \"ingredients\": [\n        \"130g grilled or baked bangus (milkfish)\",\n        \"1 medium sweet potato (boiled)\",\n        \"1 cup pinakbet without bagoong (use patis sparingly)\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 35,\n      \"carbs_g\": 38,\n      \"fat_g\": 12,\n      \"calories\": 400\n    },\n    {\n      \"name\": \"🌛 Light Evening Snack (Optional) — Greek Yogurt (9:00 PM)\",\n      \"ingredients\": [\n        \"¾ cup plain low-fat Greek yogurt (unsweetened)\",\n        \"A few pumpkin seeds or sunflower seeds\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 15,\n      \"carbs_g\": 10,\n      \"fat_g\": 4,\n      \"calories\": 135\n    }\n  ]\n}\n```\n\n---\n\n## 📊 Daily Total Summary\n\n| Nutrient | Amount |\n|---|---|\n| 🔥 **Calories** | ~1,580 kcal |\n| 💪 **Protein** | ~120g |\n| 🌾 **Carbs** | ~165g |\n| 🥑 **Fats** | ~49g |\n\n---\n\n## 🩺 Diabetes-Specific Nutrition Tips for You, Karl\n\n### ✅ DO These:\n- 🕐 **Eat every 3–4 hours** — prevents blood sugar crashes between classes\n- 🌾 **Choose low-GI carbs always** — brown rice, oats, sweet potato, whole wheat over white rice and white bread\n- 🥗 **Eat veggies first** — this slows glucose absorption from your meal\n- 💧 **Drink 8–10 glasses of water daily** — you\'re only sleeping 6 hours, so hydration is extra important for your metabolism\n- 🐟 **Local fish is your best friend** — bangus, tilapia, galunggong are affordable, accessible in Angono, and excellent protein sources!\n\n### ❌ AVOID These:\n- 🥤 Sugary drinks (softdrinks, juice, instant coffee mixes)\n- 🍚 Extra-large rice servings — control your carb portions\n- 🍜 Instant noodles as a student meal shortcut\n- 🍟 Fried street food — swap for grilled or boiled options\n- 🍬 Sweets and pastries between classes\n\n---\n\n## 💤 A Note on Your Sleep, Karl\n\nYou\'re only getting **6 hours of sleep**, which can actually **raise cortisol and slow fat loss** — and for someone with diabetes, poor sleep also affects **insulin sensitivity**. Even getting to **7 hours** can make a real difference in your progress. I know student life is busy, but try to protect your sleep as much as possible! 🙏\n\n---\n\n## 🛒 Budget-Friendly Student Tips (Angono, Rizal)\n\n- Buy **eggs, tofu, and bangus** in bulk from your local palengke — affordable and diabetes-friendly!\n- Cook your own meals when possible — **tinola, sinigang, pinakbet** are all great options with the right portions\n- Prep your snacks the night before so you don\'t grab unhealthy food between classes\n- Bring a **water bottle** to school every day\n\n---\n\n> ⚕️ **Safety Disclaimer:** This meal plan is for general wellness and weight loss guidance only. Because Karl has **diabetes**, it is strongly recommended to consult with a **licensed dietitian or physician** before making significant changes to your diet. Monitor your **blood glucose levels** regularly, especially when adjusting carbohydrate intake. Do not skip meals, as this may cause hypoglycemia. This plan is not a substitute for professional medical nutrition therapy.\n\n---\n\nYou\'ve got this, Karl! 🌟 Small, consistent changes every day add up to big results. Want me to create a **beginner workout plan** to pair with this meal plan? 💪', 'pending', NULL, NULL, '2026-04-14 03:07:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `type` enum('general','maintenance','event','policy') DEFAULT NULL,
  `target_audience` enum('all','members','trainers','admins') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `type`, `target_audience`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Hello Biofitness Members!', 'We are NOT open for today March 20, 2026 due to Eid al-Fitr. Hope you have your rest day for today. Stay Strong!!!', NULL, 'all', 1, 1, '2026-03-20 06:56:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'account_locked', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:34:20'),
(2, 1, 'account_locked', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:36:28'),
(3, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:38:49'),
(4, 1, 'create_user', 'users', 2, NULL, '{\"username\":\"trainer.123\",\"role\":\"trainer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:40:17'),
(5, 1, 'update_user', 'users', 2, '{\"first_name\":\"Trainer\",\"last_name\":\"123\",\"email\":\"trainer@gmail.com\",\"phone\":\"123123123\",\"role\":\"trainer\"}', '{\"action\":\"update_user\",\"first_name\":\"Trainer\",\"last_name\":\"123\",\"email\":\"trainer@gmail.com\",\"phone\":\"123123123\",\"role\":\"trainer\",\"assigned_trainer_id\":null,\"csrf_token\":\"1b7b9253d184f6f07eaa6da2b832d8e91ea752a740df3ea4cedd4b3c842234ef\",\"user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:40:30'),
(6, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:40:31'),
(7, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:42:28'),
(8, 1, 'update_user', 'users', 1, '{\"first_name\":\"System\",\"last_name\":\"Admin\",\"email\":\"admin@fitsense.local\",\"phone\":null,\"role\":\"admin\"}', '{\"action\":\"update_user\",\"first_name\":\"Admin\",\"last_name\":\"Blockstock\",\"email\":\"admin@fitsense.local\",\"phone\":\"\",\"role\":\"admin\",\"assigned_trainer_id\":null,\"csrf_token\":\"681c143908655112a950b46c8eb285d430309cd83b20c357f2c99b094c32acdb\",\"user_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:57:25'),
(9, 1, 'update_user', 'users', 1, '{\"first_name\":\"Admin\",\"last_name\":\"Blockstock\",\"email\":\"admin@fitsense.local\",\"phone\":null,\"role\":\"admin\"}', '{\"action\":\"update_user\",\"first_name\":\"Admin\",\"last_name\":\"User\",\"email\":\"admin@fitsense.local\",\"phone\":\"\",\"role\":\"admin\",\"assigned_trainer_id\":null,\"csrf_token\":\"681c143908655112a950b46c8eb285d430309cd83b20c357f2c99b094c32acdb\",\"user_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 06:57:30'),
(10, 1, 'create_user', 'users', 3, NULL, '{\"username\":\"karl.123\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:21:19'),
(11, 1, 'delete_user', 'users', 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:21:32'),
(12, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:30:29'),
(13, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:30:35'),
(14, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:30:38'),
(15, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:31:02'),
(16, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:31:40'),
(17, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:31:43'),
(18, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 07:40:36'),
(19, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 08:48:41'),
(20, 1, 'update_inquiry', 'contact_inquiries', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 08:58:57'),
(21, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 08:59:14'),
(22, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 08:59:31'),
(23, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 08:59:48'),
(24, 1, 'update_user', 'users', 2, '{\"first_name\":\"Trainer\",\"last_name\":\"123\",\"email\":\"trainer@gmail.com\",\"phone\":\"123123123\",\"role\":\"trainer\"}', '{\"action\":\"update_user\",\"first_name\":\"CJ\",\"last_name\":\"Vergara\",\"email\":\"cjthetrainer@gmail.com\",\"phone\":\"09123456789\",\"role\":\"trainer\",\"assigned_trainer_id\":null,\"csrf_token\":\"1041fb6dd0daca6a7f7c0b6907d99c1e48df8c6da6f9e2e0312c0d8b50ee0bc2\",\"user_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:02:56'),
(25, 1, 'update_user', 'users', 1, '{\"first_name\":\"Admin\",\"last_name\":\"User\",\"email\":\"admin@fitsense.local\",\"phone\":null,\"role\":\"admin\"}', '{\"action\":\"update_user\",\"first_name\":\"Admin\",\"last_name\":\"User\",\"email\":\"admin@fitsense.com\",\"phone\":\"09123456789\",\"role\":\"admin\",\"assigned_trainer_id\":null,\"csrf_token\":\"1041fb6dd0daca6a7f7c0b6907d99c1e48df8c6da6f9e2e0312c0d8b50ee0bc2\",\"user_id\":1,\"new_password\":\"admin123\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:03:23'),
(26, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:03:33'),
(27, 1, 'create_user', 'users', 6, NULL, '{\"username\":\"123123.123\",\"role\":\"trainer\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:04:01'),
(28, 1, 'delete_user', 'users', 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:06:40'),
(29, 1, 'create_user', 'users', 15, NULL, '{\"username\":\"karl.1231\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:07:36'),
(30, 1, 'delete_user', 'users', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:07:54'),
(31, 1, 'suspend_user', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:09:12'),
(32, 1, 'activate_user', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:17:06'),
(33, 1, 'deactivate_user', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:17:09'),
(34, 1, 'activate_user', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:17:12'),
(35, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:18:51'),
(36, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:24:00'),
(37, 1, 'reset_temp_password', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:24:17'),
(38, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:24:28'),
(39, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:24:37'),
(40, 2, 'password_changed', 'users', 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 09:34:18'),
(41, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 09:59:16'),
(42, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 09:59:54'),
(43, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 10:00:16'),
(44, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 10:00:46'),
(45, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:24:19'),
(46, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:24:23'),
(47, 1, 'create_user', 'users', 16, NULL, '{\"username\":\"karl.blox\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:24:49'),
(48, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:25:04'),
(49, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:25:18'),
(50, 16, 'password_changed', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:25:24'),
(51, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:25:44'),
(52, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:26:18'),
(53, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:26:18'),
(54, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:26:32'),
(55, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 10:41:21'),
(56, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 10:44:27'),
(57, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 11:45:05'),
(58, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 12:47:07'),
(59, 16, 'password_changed', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 13:20:03'),
(60, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:04:47'),
(61, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:12:19'),
(62, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:14:21'),
(63, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:15:02'),
(64, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:15:24'),
(65, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:16:28'),
(66, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:16:41'),
(67, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 14:17:06'),
(68, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:10:17'),
(69, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:10:17'),
(70, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:10:26'),
(71, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:18:29'),
(72, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:18:33'),
(73, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:18:33'),
(74, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:18:42'),
(75, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:21:30'),
(76, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36', '2026-03-20 15:24:03'),
(77, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:36:46'),
(78, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:38:21'),
(79, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:38:21'),
(80, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:38:29'),
(81, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:41:40'),
(82, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:41:47'),
(83, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:43:53'),
(84, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-20 15:44:04'),
(85, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 09:06:15'),
(86, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:46:30'),
(87, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:48:05'),
(88, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:48:10'),
(89, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:48:10'),
(90, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:48:16'),
(91, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:49:07'),
(92, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:49:24'),
(93, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:49:30'),
(94, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:49:37'),
(95, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:51:50'),
(96, 16, 'account_locked', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:52:37'),
(97, 1, 'account_locked', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:52:54'),
(98, 16, 'account_locked', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:53:52'),
(99, 16, 'account_locked', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:54:16'),
(100, 1, 'account_locked', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:54:37'),
(101, 1, 'account_locked', 'users', 1, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 04:54:56'),
(102, 16, 'account_locked', 'users', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:15:02'),
(103, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:18:03'),
(104, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:18:28'),
(105, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:18:43'),
(106, 2, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:19:04'),
(107, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:19:21'),
(108, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:19:21'),
(109, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 05:19:29'),
(110, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:00:10'),
(111, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:00:16'),
(112, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:31:36'),
(113, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:57:48'),
(114, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:58:58'),
(115, 1, 'create_user', 'users', 17, NULL, '{\"username\":\"testing.testing123\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 06:59:47'),
(116, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 07:00:46'),
(117, NULL, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 07:01:11'),
(118, NULL, 'password_changed', 'users', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-31 07:01:51'),
(119, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 06:29:09'),
(120, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:27:38'),
(121, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:27:49'),
(122, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:27:49'),
(123, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:28:09'),
(124, 1, 'delete_user', 'users', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:28:38'),
(125, 1, 'create_user', 'users', 18, NULL, '{\"username\":\"karl.blockstock\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:29:01'),
(126, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:30:27'),
(127, 18, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 07:30:38'),
(128, 18, 'password_changed', 'users', 18, NULL, NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-04-10 07:30:58'),
(129, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 13:52:44'),
(130, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:06:57'),
(131, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 14:07:04'),
(132, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-11 15:22:24'),
(133, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:16:59'),
(134, 1, 'create_user', 'users', 20, NULL, '{\"username\":\"karls.blox\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:17:34'),
(135, 1, 'reset_temp_password', 'users', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:17:50'),
(136, 1, 'reset_temp_password', 'users', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:18:01'),
(137, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:20:27'),
(138, NULL, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:20:39'),
(139, NULL, 'password_changed', 'users', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:20:53'),
(140, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:34:50'),
(141, 2, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 01:52:49'),
(142, NULL, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:21:53'),
(143, NULL, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:33:36'),
(144, NULL, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:33:49'),
(145, NULL, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:54:23'),
(146, 16, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:54:36'),
(147, 16, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:55:12'),
(148, NULL, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:55:31'),
(149, NULL, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:55:42'),
(150, 1, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:56:10'),
(151, 1, 'delete_user', 'users', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:56:20'),
(152, 1, 'create_user', 'users', 21, NULL, '{\"username\":\"karl.blockstock1\",\"role\":\"member\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:56:40'),
(153, 1, 'logout', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:57:00'),
(154, 21, 'login', NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:57:09'),
(155, 21, 'password_changed', 'users', 21, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-14 02:57:16');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_type` enum('member','trainer','ai') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','recommendation','system') DEFAULT 'text',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `sender_type`, `sender_id`, `message`, `message_type`, `is_read`, `created_at`, `session_id`) VALUES
(1, 16, 'trainer', 2, 'Hi. Good to know you!', 'text', 1, '2026-03-20 10:30:37', NULL),
(2, 16, 'trainer', 2, 'Hello? Are you there?', 'text', 1, '2026-03-20 10:36:15', NULL),
(3, 16, 'trainer', 2, 'Hey', 'text', 1, '2026-03-20 10:40:45', NULL),
(4, 16, 'trainer', 2, 'Please reply :(((', 'text', 1, '2026-03-20 10:40:57', NULL),
(5, 16, 'trainer', 2, 'testing', 'text', 1, '2026-03-20 10:41:07', NULL),
(6, 16, 'member', 16, 'heyy', 'text', 1, '2026-03-20 11:38:02', NULL),
(7, 16, 'member', 16, 'Hey', 'text', 1, '2026-03-20 12:42:22', NULL),
(8, 16, 'member', 16, 'Create a workout plan', 'text', 1, '2026-03-20 13:41:57', 16014109),
(9, 16, 'ai', NULL, 'Hey Karl! It\'s awesome to see you ready to kickstart your fitness journey with a workout plan. As a beginner, focusing on fundamental movements and building a strong foundation is key. This full-body workout is designed to help you get started safely and effectively. Let\'s get moving!\n\n```json\n{\n  \"title\": \"Beginner Full-Body Foundation\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Keep your chest up, push your hips back as if sitting in a chair, and go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"If regular push-ups are too challenging, start with push-ups on your knees or against an elevated surface (like a bench). Focus on a controlled movement down and up.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Perform 10 reps per leg (20 total per set). Start with very light dumbbells or even just your bodyweight to master the form. Step forward, lowering your hips until both knees are bent at approximately a 90-degree angle.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Sit upright, keeping your back straight. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the movement both on the pull and on the release.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 0,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30-45 seconds. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\nRemember to warm up for 5-10 minutes before starting (light cardio like jogging in place or dynamic stretches) and cool down for 5-10 minutes afterward (static stretches). Focus on proper form for each exercise – it\'s more important than how many reps you do or how much weight you lift! You\'ve got this, Karl!\n\n---\n**Safety Disclaimer:**\nPlease consult with a healthcare professional or your doctor before starting any new fitness program, especially if you have any pre-existing health conditions or concerns. Always listen to your body and stop if you experience pain or discomfort. Ensure you are using correct form to prevent injuries.', 'recommendation', 0, '2026-03-20 13:41:57', 16014109),
(10, 16, 'member', 16, 'How do I lose weight?', 'text', 1, '2026-03-20 13:42:22', 16014131),
(11, 16, 'ai', NULL, 'Hey Karl! That\'s a fantastic goal to set, and I\'m here to help you get started on your weight loss journey. It\'s totally achievable, and we\'ll break it down into simple, manageable steps.\n\nAt its core, losing weight comes down to creating a **calorie deficit**, which means you\'re burning more calories than you\'re consuming. But don\'t worry, it\'s not about drastic deprivation! It\'s about making sustainable, healthy choices.\n\nHere’s a great starting point for you as a beginner:\n\n### **1. Nutrition: Fueling Your Body Smartly**\n\n*   **Focus on Whole Foods:** Prioritize fresh fruits, vegetables, lean proteins (like chicken, fish, beans, lentils), and whole grains (oats, brown rice). These foods are packed with nutrients, fiber, and protein, which help you feel full and satisfied.\n*   **Portion Awareness:** Pay attention to your portion sizes. Sometimes, we eat more than we need without realizing it. Eating slowly and mindfully can help you recognize when you\'re full.\n*   **Hydrate, Hydrate, Hydrate!** Drink plenty of water throughout the day. Sometimes, your body can confuse thirst for hunger. Water also helps with metabolism and overall health.\n*   **Limit Processed Foods & Sugary Drinks:** Foods high in added sugars, unhealthy fats, and refined carbs (like sodas, pastries, fried foods) tend to be high in calories but low in nutrients, making it harder to feel full.\n*   **Don\'t Skip Meals:** Aim for regular, balanced meals to keep your metabolism steady and prevent overeating due to extreme hunger.\n\n### **2. Exercise: Moving Your Body Regularly**\n\nCombining cardiovascular exercise with strength training is a powerful duo for weight loss!\n\n*   **Cardio for Calorie Burn:** Cardio helps you burn calories and improve your cardiovascular health. As a beginner, starting with **Treadmill Running** (or brisk walking if running feels too much initially) is excellent. Aim for 20-30 minutes, 3-4 times a week, and gradually increase intensity or duration as you get fitter.\n*   **Strength Training for Muscle Building:** Building muscle helps boost your metabolism, even at rest! Don\'t worry, you won\'t get bulky – you\'ll get stronger and leaner. Here are some great beginner-friendly exercises from our list:\n    *   **Bodyweight Squat:** Fantastic for your legs and glutes.\n    *   **Push-Up:** Great for chest, shoulders, and triceps. You can start on your knees if a full push-up is too challenging.\n    *   **Plank:** Amazing for core strength. Hold it for as long as you can with good form, then rest and repeat.\n    *   **Dumbbell Lunges:** If you have access to light dumbbells, or even just bodyweight lunges, these are great for your lower body.\n    *   **Seated Cable Row:** A fantastic exercise for your back and biceps (if you have access to the equipment).\n    *   Aim for 2-3 strength sessions per week, doing 2-3 sets of 8-12 repetitions for each exercise.\n\n### **3. Lifestyle Habits for Success**\n\n*   **Prioritize Sleep:** Aim for 7-9 hours of quality sleep each night. Lack of sleep can disrupt hormones that regulate appetite, making weight loss harder.\n*   **Manage Stress:** Find healthy ways to manage stress, whether it\'s through meditation, hobbies, or spending time in nature. Stress can sometimes lead to emotional eating.\n*   **Be Patient and Consistent:** Weight loss is a journey, not a race. There will be good days and challenging days. The key is to stay consistent with your healthy habits. Celebrate small victories along the way!\n\nRemember, Karl, every step you take, no matter how small, brings you closer to your goal. I\'m here to support you! Let\'s get started!\n\n---\n**Safety Disclaimer:** Always consult with a healthcare professional or your doctor before starting any new diet or exercise program, especially if you have any pre-existing health conditions or concerns. Listen to your body and stop if you experience any pain or discomfort. Ensure proper form to prevent injuries.', 'text', 0, '2026-03-20 13:42:22', 16014131),
(12, 16, 'member', 16, 'Hello po', 'text', 1, '2026-03-27 09:06:25', 16014131),
(13, 16, 'ai', NULL, 'Hello Karl! Great to hear from you! 👋 I\'m your AI fitness coach from FitSense gym, ready to help you on your fitness journey.\n\nIt looks like you\'re a beginner, which is fantastic – everyone starts somewhere, and we\'re here to support you every step of the way!\n\nWhat are your fitness goals right now? Are you looking to build strength, improve your cardio, lose weight, or something else entirely? Let me know, and we can start building a plan together! 😊', 'text', 0, '2026-03-27 09:06:25', 16014131),
(14, 16, 'member', 16, 'Wassup po kuya', 'text', 1, '2026-03-27 09:06:36', 16014131),
(15, 16, 'ai', NULL, 'Wassup Karl! 👋 It\'s awesome to connect with you. I\'m your AI fitness coach from FitSense, ready to help you smash your goals!\n\nSince you\'re a beginner, we can set you up for some fantastic progress. To get started, what are you hoping to achieve with your fitness journey? Are you looking to build strength, improve your cardio, lose some weight, or something else entirely? Let me know, and we\'ll craft a plan just for you! 💪', 'text', 0, '2026-03-27 09:06:36', 16014131),
(16, 16, 'trainer', 2, 'Hello po!', 'text', 1, '2026-03-31 04:50:05', NULL),
(17, 16, 'member', 16, 'Hey', 'text', 1, '2026-03-31 05:21:42', NULL),
(18, 16, 'member', 16, 'Workout Plan', 'text', 1, '2026-03-31 05:47:47', 16014131),
(19, 16, 'ai', NULL, 'Hey Karl! Awesome to hear you\'re ready to dive into a workout plan. As a beginner, focusing on foundational movements is key to building strength and good habits. This full-body workout is designed to get you started, focusing on proper form and engaging different muscle groups. You\'ve got this!\n\nHere\'s a plan to kick things off:\n\n```json\n{\n  \"title\": \"Karl\'s Beginner Full-Body Strength\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Focus on keeping your chest up and core engaged. Go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 60,\n      \"notes\": \"If traditional push-ups are challenging, try them on your knees or against a wall. Keep your body in a straight line from head to heels/knees.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release on the way back.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 60,\n      \"notes\": \"Step forward, lowering until both knees are at 90 degrees. Keep your front knee aligned with your ankle. Use light dumbbells to start.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 30,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30 seconds. Keep your body in a straight line from head to heels. Engage your core and glutes, don\'t let your hips sag or rise too high.\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 15,\n      \"rest_seconds\": 0,\n      \"notes\": \"Start with a brisk walk for 5 minutes, then gradually increase to a light jog if comfortable for the remaining 10 minutes. Focus on a steady, comfortable pace.\"\n    }\n  ],\n  \"duration_minutes\": 50\n}\n```\n\nRemember to listen to your body and adjust as needed. Let\'s get moving!\n\n***\n\n**Safety Disclaimer:** Always consult with a healthcare professional before starting any new fitness program, especially if you have any pre-existing health conditions or concerns. Pay close attention to your body\'s signals and stop immediately if you experience any pain or discomfort. Proper form is crucial to prevent injuries, so consider watching video demonstrations or asking a gym staff member for form checks when trying new exercises.', 'recommendation', 0, '2026-03-31 05:47:47', 16014131),
(20, 16, 'member', 16, 'Workout Plan', 'text', 1, '2026-03-31 05:57:12', 16014131),
(21, 16, 'ai', NULL, 'Hey Karl! That\'s awesome you\'re ready to dive into a workout plan. As a beginner, it\'s all about building a solid foundation, focusing on good form, and getting your body used to moving. This full-body workout is a fantastic starting point!\n\nHere\'s a plan designed to hit all your major muscle groups and get you feeling strong:\n\n```json\n{\"title\":\"Full-Body Beginner Blast!\",\"exercises\":[{\"name\":\"Treadmill Running\",\"sets\":1,\"reps\":5,\"rest_seconds\":0,\"notes\":\"Light jog or brisk walk to warm up your muscles and get your heart rate up.\"},{\"name\":\"Bodyweight Squat\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Focus on keeping your chest up and your knees tracking over your toes. Go as deep as comfortable.\"},{\"name\":\"Push-Up\",\"sets\":3,\"reps\":8,\"rest_seconds\":60,\"notes\":\"If regular push-ups are too challenging, perform them on your knees. Keep your core tight!\"},{\"name\":\"Seated Cable Row\",\"sets\":3,\"reps\":10,\"rest_seconds\":60,\"notes\":\"Sit tall, pull the handle towards your belly button, squeezing your shoulder blades together.\"},{\"name\":\"Dumbbell Lunges\",\"sets\":3,\"reps\":8,\"rest_seconds\":90,\"notes\":\"(Per leg) Take a step forward, lowering your back knee towards the ground. Keep your front knee behind your toes.\"},{\"name\":\"Plank\",\"sets\":3,\"reps\":30,\"rest_seconds\":60,\"notes\":\"Hold your body in a straight line from head to heels, engaging your core and glutes. Don\'t let your hips sag!\"}],\"duration_minutes\":45}\n```\n\nRemember, consistency is key! Aim to do this workout 2-3 times a week, with rest days in between. Don\'t be afraid to adjust the reps or sets as you get stronger. You\'ve got this!\n\n***Safety Disclaimer:*** *Always consult with a healthcare professional before starting any new exercise program, especially if you have any pre-existing health conditions or concerns. Listen to your body, and stop if you experience pain or discomfort. Proper form is crucial to prevent injuries, so consider watching video demonstrations or asking a FitSense coach for guidance on new exercises.*', 'recommendation', 0, '2026-03-31 05:57:12', 16014131),
(22, 16, 'member', 16, '??\n          Nutrition', 'text', 1, '2026-03-31 05:57:19', 16014131),
(23, 16, 'ai', NULL, 'Hey Karl! Great to hear you\'re focusing on nutrition – it\'s such a vital part of your fitness journey, especially as a beginner. It can feel a bit overwhelming at first, but let\'s break it down into some simple, powerful steps!\n\nThink of your body as a high-performance machine; it needs the right fuel to run its best, recover, and grow stronger. Here are some key principles to get you started:\n\n1.  **Prioritize Whole Foods:** Aim for foods that are as close to their natural state as possible. Think fresh fruits and veggies, lean proteins (chicken, fish, eggs, beans, lentils), whole grains (oats, brown rice, whole wheat bread), and healthy fats (avocado, nuts, seeds, olive oil). These provide essential nutrients without a lot of processed extras.\n2.  **Hydration is Key:** Water is your best friend! It helps with everything from nutrient transport and energy levels to joint lubrication and workout performance. Try to drink plenty throughout the day – a good rule of thumb is aiming for around 2-3 liters, but listen to your body and adjust based on your activity level.\n3.  **Balanced Meals:** Try to include a source of lean protein, complex carbohydrates, and healthy fats in most of your meals. This helps keep you full, provides sustained energy, and supports muscle recovery and growth.\n    *   **Protein:** Helps build and repair muscle.\n    *   **Complex Carbs:** Provide energy for your workouts and daily activities.\n    *   **Healthy Fats:** Important for hormone function and overall health.\n4.  **Mindful Eating:** Pay attention to your body\'s hunger and fullness cues. Eat when you\'re hungry, and stop when you\'re satisfied, not stuffed. Eating slowly can help with this!\n5.  **Limit Processed Foods & Sugary Drinks:** These often provide empty calories without much nutritional value and can hinder your progress. An occasional treat is fine, but try to make them the exception, not the rule.\n6.  **Consistency Over Perfection:** Don\'t aim for perfection right away. Small, consistent changes add up to big results over time. If you have an off day, just get back on track with your next meal!\n\nRemember, nutrition doesn\'t have to be complicated to be effective. Focus on these fundamentals, and you\'ll be giving your body exactly what it needs to thrive!\n\n***\n\n*Disclaimer: This general nutrition advice is for informational purposes only and does not constitute medical advice. It\'s always a good idea to consult with a qualified healthcare professional or registered dietitian before making significant changes to your diet, especially if you have any underlying health conditions or specific dietary needs. Listen to your body and adjust as needed.*', 'text', 0, '2026-03-31 05:57:19', 16014131),
(24, 16, 'member', 16, 'Create a workout plan', 'text', 1, '2026-03-31 06:00:29', 16936819),
(25, 16, 'ai', NULL, 'Hey Karl! It\'s fantastic that you\'re ready to dive into your fitness journey with a workout plan! As a beginner, focusing on foundational movements and proper form is key, and we\'ll get you started right.\n\nThis plan is designed to be a great full-body workout to build strength and get comfortable with some core exercises. Remember to listen to your body and focus on good technique!\n\n```json\n{\n  \"title\": \"Beginner Full-Body Blast\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Stand with feet shoulder-width apart. Keep your chest up, look straight ahead, and lower your hips as if sitting in a chair. Go as deep as comfortable while maintaining good form.\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"If traditional push-ups are too challenging, try them on your knees or against a sturdy wall. Focus on lowering your chest towards the ground and pushing back up with control.\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Perform 10 repetitions on *each leg* per set. Take a step forward, lowering your back knee until it\'s just above the ground. Ensure your front knee doesn\'t go past your toes. Start with bodyweight if dumbbells feel too heavy.\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Sit tall with your chest up. Pull the handle towards your lower abdomen, squeezing your shoulder blades together. Control the release back to the starting position.\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 0,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 30-45 seconds per set. Keep your body in a straight line from head to heels, engaging your core and glutes. Avoid letting your hips sag or rise too high.\"\n    }\n  ],\n  \"duration_minutes\": 45\n}\n```\n\nThis workout is a fantastic starting point for building a strong foundation, Karl! Aim to complete it 2-3 times a week, with a rest day in between. You\'ve got this!\n\n***\n\n**Safety Disclaimer:** Always consult with a healthcare professional before starting any new fitness program, especially if you have any pre-existing medical conditions. Listen to your body, maintain proper form, and stop immediately if you experience any pain or discomfort. Stay hydrated!', 'recommendation', 0, '2026-03-31 06:00:29', 16936819),
(26, 16, 'member', 16, 'Hey', 'text', 1, '2026-03-31 06:24:44', 16938280),
(27, 16, 'ai', NULL, 'Hey Karl! 👋 Awesome to hear from you! I\'m your AI fitness coach here at FitSense, and I\'m super excited to help you get started on your fitness journey!\n\nSince you\'re just kicking things off as a beginner, this is a fantastic time to set some goals. What are you hoping to achieve? Are you looking to build strength, improve your cardio, maybe lose a little weight, or just feel more energetic overall?\n\nLet me know what\'s on your mind, and we can start building a plan together! 💪', 'text', 0, '2026-03-31 06:24:44', 16938280),
(28, 16, 'member', 16, 'Do you know my weight and profile?', 'text', 1, '2026-03-31 06:25:02', 16938280),
(29, 16, 'ai', NULL, 'Absolutely, Karl! As your FitSense AI coach, I do have your profile right here. I know you\'re 21 years old, stand at 178cm, weigh 80kg, and are currently at a beginner fitness level.\n\nHow can I help you crush your fitness goals today? Are you looking for a workout, nutrition tips, or perhaps some wellness guidance? Let\'s get started!', 'text', 0, '2026-03-31 06:25:02', 16938280),
(30, 16, 'member', 16, 'Wassup man', 'text', 1, '2026-04-11 13:55:10', NULL),
(31, 16, 'member', 16, 'Hey', 'text', 1, '2026-04-11 14:07:20', 16938280),
(32, 16, 'ai', NULL, 'Hey Karl! It\'s great to hear from you. 👋\n\nI\'m FitSense AI, your personal fitness coach, and I\'m excited to help you on your journey to build muscle. It\'s fantastic that you\'re taking this step, especially while managing your diabetes and working your day shifts.\n\nHow can I help you kick things off today, Karl? Are you looking for a workout plan, some nutrition tips tailored to your halal preferences, or perhaps some advice on how to integrate fitness into your daily routine in Angono, Rizal? Let\'s get started!', 'text', 0, '2026-04-11 14:07:20', 16938280),
(39, 18, 'trainer', 2, 'oy', 'text', 0, '2026-04-14 01:53:47', NULL),
(53, 16, 'member', 16, 'Hey', 'text', 0, '2026-04-14 02:54:48', 16135278),
(54, 16, 'ai', NULL, 'Hey Karl! 👋 Welcome! Great to have you here!\n\nI\'m FitSense AI, your personal fitness coach. I\'m here to help you crush your goals in a way that works specifically for **you** — your schedule, your lifestyle, and your health needs.\n\nI can see you\'re on a great journey — looking to **build muscle** while working toward your target weight of **75kg**. That\'s totally achievable, especially at 21 when your body responds really well to training! 💪\n\nHere\'s a little of what I can help you with:\n\n* 🏋️ **Workout plans** tailored to your beginner level\n* 🥗 **Halal-friendly nutrition advice** that supports muscle building\n* 💤 **Recovery tips** to make the most of your 7 hours of sleep\n* 🩺 **Diabetes-conscious guidance** to keep you safe while training\n\n---\n\nSo tell me, Karl — **what\'s on your mind today?** Are you looking to:\n\n1. Get a workout plan to start with?\n2. Learn about what to eat to support your goals?\n3. Ask something specific you\'ve been wondering about?\n\nWhatever it is, I\'ve got you! Let\'s make this journey feel good and sustainable. 😊', 'text', 0, '2026-04-14 02:54:48', 16135278),
(55, 21, 'member', 21, 'Hi there!', 'text', 0, '2026-04-14 02:58:44', 21135508),
(56, 21, 'ai', NULL, '# Hey Karl! 👋 Welcome!\n\nSo great to have you here! I\'m FitSense, your personal fitness coach, and I\'m genuinely excited to help you on your journey! 🌟\n\nI\'ve already taken a look at your profile, and I can see you\'ve got a really **clear and achievable goal** — losing 10kg to reach 65kg. That\'s absolutely something we can work toward together, and I\'m here every step of the way!\n\nHere\'s a little snapshot of what I know about you so far:\n\n| | |\n|---|---|\n| 🎯 **Goal** | Lose 10kg |\n| 💪 **Level** | Beginner (perfect starting point!) |\n| 📅 **Schedule** | Student on day shift |\n| 😴 **Sleep** | 6 hours/night |\n| 🏃 **Activity** | Lightly active |\n\nA couple of things I\'m already keeping in mind for you:\n- Your **diabetes** means we\'ll be extra smart about nutrition and exercise intensity\n- Your **student schedule** means we\'ll keep things **practical and time-efficient**\n- Your **sleep** is something we might want to work on together too!\n\n---\n\n**So Karl, where would you like to start?** 😊\n\n- 🏋️ A beginner workout plan?\n- 🥗 Nutrition guidance?\n- 💡 General wellness tips?', 'text', 0, '2026-04-14 02:58:44', 21135508),
(57, 21, 'member', 21, 'I want you to generate me a table which is 6 day workout PPL that is compatible with my body.', 'text', 0, '2026-04-14 03:00:01', 21135508),
(58, 21, 'ai', NULL, '# 💪 Your Personalized 6-Day PPL Workout Plan, Karl!\n\nI love your commitment to hitting 6 days, Karl! Since you\'re a beginner aiming to shed that 10kg, I\'ve designed this PPL split to be **progressive, safe, and diabetes-friendly** — keeping sessions efficient since I know your student schedule can get busy. Let\'s get it! 🔥\n\n---\n\n## 🗓️ Weekly Split Overview\n\n| Day | Focus | Type |\n|-----|-------|------|\n| Day 1 | **Push** | Chest, Shoulders, Triceps |\n| Day 2 | **Pull** | Back, Biceps |\n| Day 3 | **Legs** | Quads, Hamstrings, Glutes |\n| Day 4 | **Push** | Chest, Shoulders, Triceps |\n| Day 5 | **Pull** | Back, Biceps |\n| Day 6 | **Legs** | Quads, Hamstrings, Glutes |\n| Day 7 | 😴 **REST** | Recovery & Light Walking |\n\n---\n\n## 🅐 DAY 1 & 4 — PUSH (Chest, Shoulders, Triceps)\n\n| # | Exercise | Sets | Reps | Rest | Notes |\n|---|----------|------|------|------|-------|\n| 1 | Push-Up | 3 | 8–12 | 60s | Keep core tight, full range of motion |\n| 2 | Dumbbell Bench Press | 3 | 10–12 | 75s | Control the descent slowly |\n| 3 | Bodyweight Squat *(finisher)* | 2 | 15 | 45s | Warm your knees before pressing |\n| 4 | Plank | 3 | 20–30s | 45s | Build core stability for lifts |\n| 5 | Burpee | 2 | 8–10 | 60s | Great calorie burn — go at your pace! |\n\n> 🕐 **Estimated Duration:** 35–45 minutes\n\n---\n\n## 🅑 DAY 2 & 5 — PULL (Back, Biceps)\n\n| # | Exercise | Sets | Reps | Rest | Notes |\n|---|----------|------|------|------|-------|\n| 1 | Seated Cable Row | 3 | 10–12 | 75s | Squeeze shoulder blades together |\n| 2 | Pull-Up | 3 | 3–6 | 90s | Use assisted band if needed — no shame! |\n| 3 | Barbell Deadlift | 3 | 6–8 | 90s | **Start VERY light** — focus on form first |\n| 4 | Plank | 2 | 30s | 45s | Core support for pulling movements |\n| 5 | Treadmill Running | 1 | 10 min | — | Light jog or brisk walk is perfectly fine |\n\n> 🕐 **Estimated Duration:** 40–50 minutes\n\n---\n\n## 🅒 DAY 3 & 6 — LEGS (Quads, Hamstrings, Glutes)\n\n| # | Exercise | Sets | Reps | Rest | Notes |\n|---|----------|------|------|------|-------|\n| 1 | Bodyweight Squat | 3 | 15–20 | 60s | Warm-up, perfect your form here |\n| 2 | Dumbbell Lunges | 3 | 10–12/leg | 75s | Keep your front knee behind your toes |\n| 3 | Barbell Deadlift | 3 | 6–8 | 90s | Engages hamstrings & glutes deeply |\n| 4 | Burpee | 2 | 8–10 | 60s | Boosts heart rate for fat burning 🔥 |\n| 5 | Treadmill Running | 1 | 10–15 min | — | Cool-down pace — walk if needed |\n\n> 🕐 **Estimated Duration:**', 'text', 0, '2026-04-14 03:00:01', 21135508),
(59, 21, 'member', 21, 'Create a personalized workout plan for me based on my fitness level and goals.  I want you to generate me a table 6 day workout PPL.', 'text', 0, '2026-04-14 03:05:49', 21135896),
(60, 21, 'ai', NULL, '# Hey Karl! 💪 Let\'s Build Your 6-Day PPL Plan!\n\nI love the ambition, Karl! A **Push-Pull-Legs** split is a fantastic choice for your weight loss goal. Since you\'re a beginner, I\'ve kept the volume manageable while still being effective enough to burn calories and build the lean muscle that\'ll help you drop those 10kg. I\'ve also designed this with your student day shift schedule and 6-hour sleep in mind — so nothing too brutal that\'ll wipe you out! 😊\n\n---\n\n> ⚠️ **Before we dive in** — Karl, since you have **diabetes**, please monitor your blood sugar **before and after** every session. Keep a fast-acting carb snack nearby during workouts. Consult your physician before starting this program.\n\n---\n\n## 🗓️ Your 6-Day PPL Split Overview\n\n| Day | Focus | Type |\n|-----|-------|------|\n| **Monday** | Push A | Chest, Shoulders, Triceps |\n| **Tuesday** | Pull A | Back, Biceps |\n| **Wednesday** | Legs A | Quads, Hamstrings, Glutes |\n| **Thursday** | Push B | Chest, Shoulders, Triceps |\n| **Friday** | Pull B | Back, Biceps |\n| **Saturday** | Legs B | Quads, Hamstrings, Glutes |\n| **Sunday** | 🛌 Rest | Recovery |\n\n---\n\n## 📋 Workout Plans (JSON Format)\n\n### 🔵 DAY 1 — Push A\n\n```json\n{\n  \"title\": \"Push A — Chest, Shoulders & Triceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"Keep core tight and body in a straight line. Great chest and tricep activator for beginners, Karl!\"\n    },\n    {\n      \"name\": \"Dumbbell Bench Press\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Use a light-to-moderate weight. Focus on controlled movement — slow down, push up. Build that mind-muscle connection!\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 1,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold for 20–30 seconds per set. Builds core stability which supports ALL your other lifts.\"\n    }\n  ],\n  \"duration_minutes\": 35\n}\n```\n\n---\n\n### 🔴 DAY 2 — Pull A\n\n```json\n{\n  \"title\": \"Pull A — Back & Biceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Pull to your lower chest, squeeze your shoulder blades together. Light weight to nail the form first, Karl!\"\n    },\n    {\n      \"name\": \"Pull-Up\",\n      \"sets\": 3,\n      \"reps\": 5,\n      \"rest_seconds\": 90,\n      \"notes\": \"5 reps is a solid beginner target. Use an assisted machine or resistance band if needed — no shame in that!\"\n    },\n    {\n      \"name\": \"Plank\",\n      \"sets\": 3,\n      \"reps\": 1,\n      \"rest_seconds\": 60,\n      \"notes\": \"Hold 20–30 seconds. Core is your foundation — we train it every day!\"\n    }\n  ],\n  \"duration_minutes\": 35\n}\n```\n\n---\n\n### 🟢 DAY 3 — Legs A\n\n```json\n{\n  \"title\": \"Legs A — Quads, Hamstrings & Glutes\",\n  \"exercises\": [\n    {\n      \"name\": \"Bodyweight Squat\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"Feet shoulder-width apart, chest up, sit back like you\'re reaching for a chair. Perfect form before adding weight!\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 60,\n      \"notes\": \"10 reps each leg. Keep your front knee behind your toes. Great for balance and glute activation, Karl!\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 1,\n      \"rest_seconds\": 0,\n      \"notes\": \"15-minute light jog or brisk walk at 5–6 km/h. This cardio finisher will torch extra calories and support your 65kg goal!\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\n---\n\n### 🔵 DAY 4 — Push B\n\n```json\n{\n  \"title\": \"Push B — Chest, Shoulders & Triceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Dumbbell Bench Press\",\n      \"sets\": 4,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"One more set vs Push A — slightly heavier if you felt comfortable Monday. Progressive overload is how you grow, Karl!\"\n    },\n    {\n      \"name\": \"Push-Up\",\n      \"sets\": 3,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"2 extra reps vs Monday — you\'re already improving! Try incline push-ups to target upper chest if regular ones feel easy.\"\n    },\n    {\n      \"name\": \"Burpee\",\n      \"sets\": 3,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"The ultimate fat-burner! Full body explosive movement. Take your time between reps — quality over speed as a beginner.\"\n    }\n  ],\n  \"duration_minutes\": 40\n}\n```\n\n---\n\n### 🔴 DAY 5 — Pull B\n\n```json\n{\n  \"title\": \"Pull B — Back & Biceps\",\n  \"exercises\": [\n    {\n      \"name\": \"Pull-Up\",\n      \"sets\": 4,\n      \"reps\": 5,\n      \"rest_seconds\": 90,\n      \"notes\": \"One more set vs Pull A. Even if you can only do 3–4 reps, that\'s still progress. Every rep counts, Karl!\"\n    },\n    {\n      \"name\": \"Seated Cable Row\",\n      \"sets\": 4,\n      \"reps\": 8,\n      \"rest_seconds\": 90,\n      \"notes\": \"Slightly heavier than Pull A if form was solid. Imagine crushing a pencil between your shoulder blades on every pull.\"\n    },\n    {\n      \"name\": \"Barbell Deadlift\",\n      \"sets\": 3,\n      \"reps\": 6,\n      \"rest_seconds\": 120,\n      \"notes\": \"START VERY LIGHT — just the bar or 20kg max. Deadlifts are a powerhouse move but form is everything. Hinge at hips, flat back, bar close to shins.\"\n    }\n  ],\n  \"duration_minutes\": 45\n}\n```\n\n---\n\n### 🟢 DAY 6 — Legs B\n\n```json\n{\n  \"title\": \"Legs B — Quads, Hamstrings & Glutes + Cardio\",\n  \"exercises\": [\n    {\n      \"name\": \"Barbell Deadlift\",\n      \"sets\": 3,\n      \"reps\": 6,\n      \"rest_seconds\": 120,\n      \"notes\": \"Same as Pull B — very light weight, perfect form. Deadlifts on leg day targets hamstrings and glutes heavily. You\'ve got this!\"\n    },\n    {\n      \"name\": \"Dumbbell Lunges\",\n      \"sets\": 4,\n      \"reps\": 12,\n      \"rest_seconds\": 60,\n      \"notes\": \"12 reps each leg — up from Legs A. Add light dumbbells if bodyweight lunges felt easy on Wednesday.\"\n    },\n    {\n      \"name\": \"Burpee\",\n      \"sets\": 3,\n      \"reps\": 10,\n      \"rest_seconds\": 90,\n      \"notes\": \"Finish strong, Karl! 10 burpees per set — this will really fire up your metabolism and accelerate that 10kg weight loss goal.\"\n    },\n    {\n      \"name\": \"Treadmill Running\",\n      \"sets\": 1,\n      \"reps\": 1,\n      \"rest_seconds\": 0,\n      \"notes\": \"20-minute brisk walk or light jog to close out the week. Your weekly cardio anchor — keep it consistent!\"\n    }\n  ],\n  \"duration_minutes\": 50\n}\n```\n\n---\n\n## 📊 Weekly Summary Table\n\n| Day | Workout | Duration | Key Focus |\n|-----|---------|----------|-----------|\n| Mon | Push A | ~35 min | Form & Foundation |\n| Tue | Pull A | ~35 min | Back Activation |\n| Wed | Legs A | ~40 min | Lower Body + Cardio |\n| Thu | Push B | ~40 min | Volume Increase |\n| Fri | Pull B | ~45 min | Strength + Deadlift Intro |\n| Sat | Legs B | ~50 min | Full Lower + Cardio Finisher |\n| Sun | Rest | — | 💤 Recovery |\n| | **TOTAL** | **~245 min** | |\n\n---\n\n## 💡 Karl\'s Personal Tips\n\n- 🩸 **Diabetes Check** — Test blood sugar before & after every session. Aim to work out 1–2 hours after a meal for stable glucose levels\n- 😴 **Sleep** — Try to protect your 6 hours religiously. Sleep is when your body actually burns fat and repairs muscle\n- 💧 **Hydration** — Drink 2.5–3L of water daily, especially as a student in Angono\'s warm climate\n- 📈 **Progressive Overload** — Add 1–2 reps or slightly more weight every 1–2 weeks. That\'s how 75kg becomes 65kg!\n- 🎯 **Consistency > Perfection** — Missing one day is fine. Missing a week is where progress dies\n\n---\n\n> ⚠️ **Safety Disclaimer:** This workout plan is designed for general fitness purposes. Karl, because you have **Type 1/Type 2 Diabetes**, please consult your physician or endocrinologist before beginning this program. Monitor blood glucose levels regularly and stop exercising immediately if you feel dizzy, faint, or experience hypoglycemic symptoms. Always warm up for 5 minutes before each session and cool down/stretch for 5 minutes after. FitSense AI is not a substitute for professional medical advice.\n\n---\n\nYou\'ve got everything you need, Karl — now let\'s turn that goal of **65kg** into reality! 🔥 Want me to also create a **meal plan** to complement this workout? 🥗', 'recommendation', 0, '2026-04-14 03:05:49', 21135896),
(61, 21, 'member', 21, 'Testing', 'text', 0, '2026-04-14 03:06:10', 21135960),
(62, 21, 'ai', NULL, 'Hey Karl! 👋\n\nWelcome to FitSense AI! I\'m here and ready to help you on your fitness journey! 💪\n\nI can see your profile is all set up — a 21-year-old student from Angono, Rizal working toward your weight loss goal. That\'s awesome that you\'re taking this step!\n\nHere\'s what I can help you with:\n\n* 🏋️ **Workout Plans** tailored to your beginner level and schedule\n* 🥗 **Nutrition Advice** that considers your diabetes and shrimp allergy\n* 😴 **Wellness Tips** to help you make the most of your 6 hours of sleep\n* 📊 **Progress Guidance** to keep you on track to your 65kg goal\n\n---\n\n**Just ask me anything like:**\n\n* *\"Give me a workout plan for this week\"*\n* *\"What should I eat to lose weight with diabetes?\"*\n* *\"How do I stay consistent as a student?\"*\n\n---\n\nI\'m here to make this journey feel **doable, safe, and even enjoyable** for you, Karl! 😊\n\nSo — what would you like to work on first? 🚀', 'text', 0, '2026-04-14 03:06:10', 21135960),
(63, 21, 'member', 21, 'Give me a personalized meal plan and nutrition guide based on my fitness goals', 'text', 0, '2026-04-14 03:07:05', 21135988),
(64, 21, 'ai', NULL, '# 🥗 Your Personalized Nutrition Plan, Karl!\n\nI love that you\'re taking this step! Since you\'re a 21-year-old student in Angono working toward losing 10kg, I\'ve built this plan carefully around your **diabetes**, your **shrimp allergy**, your student schedule, and your need for **blood sugar-friendly** foods. Let\'s fuel your body the smart way! 💪\n\n---\n\n## 🎯 Your Daily Nutrition Targets\n\n| Metric | Value |\n|---|---|\n| **Goal Calories** | ~1,750 kcal/day |\n| **Protein** | ~130g (preserve muscle while losing fat) |\n| **Carbs** | ~175g (low-GI focused for diabetes) |\n| **Fats** | ~55g (heart-healthy sources) |\n| **Deficit** | ~500 kcal below maintenance |\n\n> ⚠️ Because you have **diabetes**, carbohydrate timing and quality are especially important. Low-GI carbs help keep your blood sugar stable throughout your day as a student.\n\n---\n\n## 🍽️ Your Personalized Meal Plan\n\n```json\n{\n  \"title\": \"Karl\'s Diabetes-Friendly Weight Loss Meal Plan\",\n  \"meals\": [\n    {\n      \"name\": \"🌅 Breakfast — Egg & Oats Power Bowl (7:00 AM)\",\n      \"ingredients\": [\n        \"½ cup rolled oats (not instant)\",\n        \"2 whole eggs (scrambled or boiled)\",\n        \"1 medium banana (sliced on top)\",\n        \"1 tsp chia seeds\",\n        \"1 glass of water or unsweetened black coffee\"\n      ],\n      \"protein_g\": 18,\n      \"carbs_g\": 42,\n      \"fat_g\": 10,\n      \"calories\": 330\n    },\n    {\n      \"name\": \"☀️ Mid-Morning Snack — Protein Fruit Combo (10:00 AM)\",\n      \"ingredients\": [\n        \"1 small apple\",\n        \"2 tbsp peanut butter (no added sugar)\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 7,\n      \"carbs_g\": 20,\n      \"fat_g\": 9,\n      \"calories\": 185\n    },\n    {\n      \"name\": \"🌤️ Lunch — Grilled Chicken & Brown Rice Bowl (12:30 PM)\",\n      \"ingredients\": [\n        \"120g grilled chicken breast\",\n        \"¾ cup cooked brown rice\",\n        \"1 cup steamed mixed veggies (kangkong, sitaw, carrots)\",\n        \"1 tsp olive oil or coconut oil for cooking\",\n        \"Calamansi + pepper for seasoning (no salty sauces)\"\n      ],\n      \"protein_g\": 38,\n      \"carbs_g\": 48,\n      \"fat_g\": 9,\n      \"calories\": 430\n    },\n    {\n      \"name\": \"🌥️ Afternoon Snack — Boiled Egg & Veggie Sticks (3:30 PM)\",\n      \"ingredients\": [\n        \"1 hard-boiled egg\",\n        \"½ cucumber (sliced)\",\n        \"5 cherry tomatoes\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 7,\n      \"carbs_g\": 7,\n      \"fat_g\": 5,\n      \"calories\": 100\n    },\n    {\n      \"name\": \"🌙 Dinner — Bangus & Sweet Potato Plate (7:00 PM)\",\n      \"ingredients\": [\n        \"130g grilled or baked bangus (milkfish)\",\n        \"1 medium sweet potato (boiled)\",\n        \"1 cup pinakbet without bagoong (use patis sparingly)\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 35,\n      \"carbs_g\": 38,\n      \"fat_g\": 12,\n      \"calories\": 400\n    },\n    {\n      \"name\": \"🌛 Light Evening Snack (Optional) — Greek Yogurt (9:00 PM)\",\n      \"ingredients\": [\n        \"¾ cup plain low-fat Greek yogurt (unsweetened)\",\n        \"A few pumpkin seeds or sunflower seeds\",\n        \"1 glass of water\"\n      ],\n      \"protein_g\": 15,\n      \"carbs_g\": 10,\n      \"fat_g\": 4,\n      \"calories\": 135\n    }\n  ]\n}\n```\n\n---\n\n## 📊 Daily Total Summary\n\n| Nutrient | Amount |\n|---|---|\n| 🔥 **Calories** | ~1,580 kcal |\n| 💪 **Protein** | ~120g |\n| 🌾 **Carbs** | ~165g |\n| 🥑 **Fats** | ~49g |\n\n---\n\n## 🩺 Diabetes-Specific Nutrition Tips for You, Karl\n\n### ✅ DO These:\n- 🕐 **Eat every 3–4 hours** — prevents blood sugar crashes between classes\n- 🌾 **Choose low-GI carbs always** — brown rice, oats, sweet potato, whole wheat over white rice and white bread\n- 🥗 **Eat veggies first** — this slows glucose absorption from your meal\n- 💧 **Drink 8–10 glasses of water daily** — you\'re only sleeping 6 hours, so hydration is extra important for your metabolism\n- 🐟 **Local fish is your best friend** — bangus, tilapia, galunggong are affordable, accessible in Angono, and excellent protein sources!\n\n### ❌ AVOID These:\n- 🥤 Sugary drinks (softdrinks, juice, instant coffee mixes)\n- 🍚 Extra-large rice servings — control your carb portions\n- 🍜 Instant noodles as a student meal shortcut\n- 🍟 Fried street food — swap for grilled or boiled options\n- 🍬 Sweets and pastries between classes\n\n---\n\n## 💤 A Note on Your Sleep, Karl\n\nYou\'re only getting **6 hours of sleep**, which can actually **raise cortisol and slow fat loss** — and for someone with diabetes, poor sleep also affects **insulin sensitivity**. Even getting to **7 hours** can make a real difference in your progress. I know student life is busy, but try to protect your sleep as much as possible! 🙏\n\n---\n\n## 🛒 Budget-Friendly Student Tips (Angono, Rizal)\n\n- Buy **eggs, tofu, and bangus** in bulk from your local palengke — affordable and diabetes-friendly!\n- Cook your own meals when possible — **tinola, sinigang, pinakbet** are all great options with the right portions\n- Prep your snacks the night before so you don\'t grab unhealthy food between classes\n- Bring a **water bottle** to school every day\n\n---\n\n> ⚕️ **Safety Disclaimer:** This meal plan is for general wellness and weight loss guidance only. Because Karl has **diabetes**, it is strongly recommended to consult with a **licensed dietitian or physician** before making significant changes to your diet. Monitor your **blood glucose levels** regularly, especially when adjusting carbohydrate intake. Do not skip meals, as this may cause hypoglycemia. This plan is not a substitute for professional medical nutrition therapy.\n\n---\n\nYou\'ve got this, Karl! 🌟 Small, consistent changes every day add up to big results. Want me to create a **beginner workout plan** to pair with this meal plan? 💪', 'recommendation', 0, '2026-04-14 03:07:05', 21135988),
(65, 21, 'member', 21, 'I think your chat is cutted you didnt fully finished it', 'text', 0, '2026-04-14 03:07:51', 21135508),
(66, 21, 'ai', NULL, 'Hey Karl! 😊\n\nIt looks like this might be the **start of our conversation** on my end — I don\'t have any previous message from you to continue from!\n\nCould you help me out by letting me know:\n\n- **What were we talking about?** (e.g., workout plan, nutrition advice, etc.)\n- **Or feel free to ask your question again** and I\'ll give you a full, complete answer!\n\nI\'m here and ready to give you my **full attention** — no cut-off responses this time, I promise! 💪', 'text', 0, '2026-04-14 03:07:51', 21135508),
(67, 21, 'member', 21, 'Give me motivation tips and strategies to stay consistent with my fitness goals', 'text', 0, '2026-04-14 03:08:41', 21136090),
(68, 21, 'ai', NULL, '# Staying Consistent with Your Fitness Goals, Karl! 💪\n\nI love that you\'re thinking about the *mental side* of fitness — because honestly, consistency is what separates results from wishful thinking. Let me give you strategies that are **built specifically for your life situation.**\n\n---\n\n## 🧠 Understanding YOUR Challenges First\n\nKarl, let\'s be real about what you\'re working with:\n- **Student life** can be unpredictable and draining\n- **Only 6 hours of sleep** means your energy and willpower are already stretched\n- **10kg weight loss goal** is significant but absolutely achievable\n- **Managing diabetes** requires extra discipline — which means you already have more mental strength than you give yourself credit for!\n\n---\n\n## 🎯 Core Motivation Strategies\n\n### 1. 🔥 Start Embarrassingly Small\nSince you\'re a **beginner**, resist the urge to go all-in immediately.\n\n> *\"I will do 10 push-ups after waking up\"*\n> is better than\n> *\"I will work out 2 hours daily\"*\n\nSmall wins **build momentum.** Momentum builds habits. Habits build results.\n\n---\n\n### 2. 📅 Schedule Workouts Like Classes\nYou\'re a student — you don\'t skip exams, right?\n\n**Treat your workout like a non-negotiable class:**\n- Block **3 specific days per week** in your schedule\n- Even **20-30 minutes** is enough as a beginner\n- Suggested days: **Monday, Wednesday, Friday** or **Tuesday, Thursday, Saturday**\n\n---\n\n### 3. 📊 Track Everything Visually\n**Try the \"Don\'t Break the Chain\" method:**\n\n```\nWeek 1:  ✅ ✅ ❌ ✅ ❌ ✅ ❌\nWeek 2:  ✅ ✅ ✅ ✅ ❌ ✅ ❌\nWeek 3:  ✅ ✅ ✅ ✅ ✅ ✅ ❌\n```\nSeeing your streak grow makes you *not want to break it.* Use a simple notebook or free apps like **Habitica or Streaks.**\n\n---\n\n### 4. 💤 Protect Your Sleep (Seriously, Karl)\nAt **6 hours/night**, your body is already in recovery debt. Poor sleep:\n- ❌ Increases hunger hormones (bad for weight loss)\n- ❌ Spikes blood sugar (critical with your diabetes)\n- ❌ Kills workout motivation\n\n**Try to add even 30 more minutes of sleep** — it\'s one of the highest-impact changes you can make right now.\n\n---\n\n### 5. 🎯 Keep Your \"Why\" Visible\nAsk yourself honestly:\n\n> *\"Why do I want to lose these 10kg?\"*\n\nWrite it down. Put it on your phone wallpaper. Read it when motivation dips. Your **why** is your fuel when feelings fail.\n\n---\n\n### 6. 🏘️ Use Your Environment (Angono Advantage!)\n- Find a **workout buddy** from school or your neighborhood\n- Even walking around **Angono\'s streets or near Laguna de Bay** counts as cardio!\n- Community accountability is powerful — tell someone your goals\n\n---\n\n### 7. 📉 Expect Bad Days — Plan for Them\nMotivation **will** disappear sometimes. That\'s normal. Have a backup plan:\n\n| Feeling | Backup Plan |\n|---|---|\n| Too tired | Do just 10 minutes |\n| No equipment | 20 bodyweight squats + push-ups |\n| Stressed from school | 15-min walk outside |\n| Skipped yesterday | Forgive yourself, start today |\n\n> *\"Never miss twice\"* — missing once is human, missing twice is a habit.\n\n---\n\n### 8. 🏆 Celebrate Non-Scale Victories\nThe weighing scale can be **discouraging** — especially early on. Celebrate other wins:\n\n- ✅ Slept better last night\n- ✅ Walked instead of riding a trike\n- ✅ Drank 8 glasses of water\n- ✅ Resisted an unhealthy snack\n- ✅ Did your workout even when tired\n\nThese **all matter**, Karl. They\'re all progress.\n\n---\n\n## 📆 Your Simple Weekly Motivation Routine\n\n```\n🌅 Every Morning:\n   → Read your \"why\"\n   → Drink a glass of water\n\n📅 Workout Days (3x/week):\n   → Lay out clothes the night before\n   → Start with just 5 minutes — momentum follows\n\n🌙 Every Night:\n   → Log one win from the day\n   → Sleep 30 mins earlier than usual\n\n📊 Every Sunday:\n   → Review your week without judgment\n   → Plan next week\'s 3 workout days\n```\n\n---\n\n## 💬 One Last Thing, Karl...\n\nYou\'re **21 years old**, managing your health with diabetes, studying hard, and still choosing to invest in yourself. That already shows incredible character. The 10kg will come — **but the discipline and confidence you build along the way? That stays with you forever.**\n\nYou\'ve got this. One day at a time. 🌟\n\n---\n\n> ⚠️ **Safety Disclaimer:** As someone managing diabetes, please consult your doctor or healthcare provider before making significant changes to your exercise routine or diet. Monitor your blood sugar levels before and after workouts, carry a fast-acting carbohydrate source during exercise, and stop immediately if you feel dizzy, shaky, or unwell. Your health always comes first.', 'text', 0, '2026-04-14 03:08:41', 21136090),
(69, 21, 'member', 21, 'Do you know what I chatted you? Like our previous convo up in this chat?', 'text', 0, '2026-04-14 03:09:57', 21136090),
(70, 21, 'ai', NULL, 'Hey Karl! 😊\n\nTo be straightforward with you — **I don\'t have memory of any previous conversations** we may have had before this current session. Each new chat starts fresh for me, so I can only see what\'s been shared **in this current conversation**, which is just your question right now.\n\n**However, what I DO have is your full profile!** So I already know the important stuff:\n\n- 🎯 Your goal to go from **75kg → 65kg**\n- 🩺 Your **diabetes** condition\n- 📚 Your life as a **student on day shift**\n- 😴 Your **6 hours of sleep**\n- 🏋️ Your **beginner fitness level**\n\nSo even without past chat history, I can still give you **personalized advice** right where you are today! 💪\n\n---\n\nIs there something specific from a previous conversation you\'d like to **revisit or continue**? Just let me know what you were working on — whether it was a **workout plan, meal advice, or something else** — and I\'ll pick right up with you! 😊', 'text', 0, '2026-04-14 03:09:57', 21136090),
(71, 21, 'member', 21, 'Hi do you know our previous chats?', 'text', 0, '2026-04-14 03:16:45', 21136591);
INSERT INTO `chat_messages` (`id`, `user_id`, `sender_type`, `sender_id`, `message`, `message_type`, `is_read`, `created_at`, `session_id`) VALUES
(72, 21, 'ai', NULL, 'Hey Karl! 👋\n\nGreat question! To be transparent with you — **I only have memory of our current conversation**, which is just starting now. I don\'t have access to any previous chat sessions we may have had before this one.\n\nBut no worries! I have your **full profile right here**, so I already know the important stuff:\n\n- 🎯 **Goal:** Lose weight (75kg → 65kg)\n- 🏋️ **Level:** Beginner\n- 📅 **Schedule:** Student, day shift\n- 😴 **Sleep:** 6 hours/night\n- ⚕️ **Medical:** Diabetes\n- 🚫 **Allergy:** Shrimp\n- 📍 **From:** Angono, Rizal\n\nSo we can **pick up right where you need to be** — whether that\'s a workout plan, meal advice, or just a fitness question! 💪\n\nWhat can I help you with today, Karl? 😊', 'text', 0, '2026-04-14 03:16:45', 21136591),
(73, 21, 'member', 21, 'Where is the gym located?', 'text', 0, '2026-04-14 03:17:12', 21136591),
(74, 21, 'ai', NULL, 'Hey Karl! 😊\n\nTo answer your questions:\n\n---\n\n**💬 About Previous Chats:**\nUnfortunately, **I don\'t have access to previous chat sessions**. My memory only covers our **current conversation** starting from right now. Each new session starts fresh for me.\n\nBut the good news? **Your profile is always saved in the system**, so I still know all your important details — your goals, health conditions, schedule, and more! 🙌\n\n---\n\n**📍 About the Gym Location:**\n**Biofitness Gym** is located in:\n> 🏠 **Cainta, Rizal, Philippines**\n\nAnd since you\'re from **Angono, Rizal**, you\'re actually really close! Angono and Cainta are neighboring towns, so the commute should be pretty convenient for you. No excuses to skip leg day! 😄🦵\n\n---\n\nIs there anything else you\'d like to know, Karl? Whether it\'s a **workout plan**, **meal advice**, or anything fitness-related, I\'m here for you! 💪🔥', 'text', 0, '2026-04-14 03:17:12', 21136591),
(75, 21, 'member', 21, 'I think not, think again pelase', 'text', 0, '2026-04-14 03:18:12', 21136591),
(76, 21, 'ai', NULL, 'Hey Karl! 😊\n\nYou\'re right, let me correct that! 🙏\n\n---\n\n**📍 Biofitness Gym is located at:**\n> 🏠 **Anonas, Cubao, Philippines**\n\n---\n\nSorry about the confusion earlier! That was my mistake. 😅\n\nAnd since you\'re coming from **Angono, Rizal**, you can commute via:\n- 🚌 Bus or jeepney going to **Cubao**\n- 🚇 Or take the **LRT-2 to Anonas Station** — which is super convenient since the gym is right in that area!\n\n---\n\nIs there anything else I can help you with today, Karl? Whether it\'s a **workout plan, meal tips, or fitness advice**, I\'ve got you covered! 💪😊', 'text', 0, '2026-04-14 03:18:12', 21136591),
(77, 21, 'member', 21, 'Hmmm Ill think first', 'text', 0, '2026-04-14 03:20:41', 21136591),
(78, 21, 'ai', NULL, 'Haha no worries, Karl! Take your time! 😄\n\nI\'ll be right here whenever you\'re ready! 💪\n\nWhether it\'s about:\n- 🏋️ **Workout plans**\n- 🥗 **Meal & nutrition advice**\n- 📍 **Gym info**\n- 💡 **General fitness questions**\n\nJust type away whenever you\'re set! 😊🔥', 'text', 0, '2026-04-14 03:20:41', 21136591),
(79, 21, 'member', 21, 'Oh I think I know now!!', 'text', 0, '2026-04-14 03:20:59', 21136591),
(80, 21, 'ai', NULL, 'Haha yes, Karl! 🎉 Let me correct myself again!\n\n---\n\n**📍 Biofitness Gym is located at:**\n> 🏠 **Anonas, Cubao, Philippines**\n\nWait — actually, let me be straightforward with you! 😄\n\nLooking back at my information, the gym is located at:\n> 🏠 **Anonas, Cubao, Philippines**\n\nHmm but you seem to think otherwise! Go ahead and tell me — **what do you think the correct location is?** 😊 I want to make sure I\'m giving you the right information! 🙏', 'text', 0, '2026-04-14 03:20:59', 21136591),
(81, 21, 'member', 21, 'Give me motivation tips and strategies to stay consistent with my fitness goals', 'text', 0, '2026-04-14 03:21:35', 21136871),
(82, 21, 'ai', NULL, '# 🔥 Staying Consistent with Your Fitness Goals, Karl!\n\nI love that you\'re thinking about this — because **consistency is truly the secret weapon** in any fitness journey. Losing 10kg is absolutely achievable for you, and building the right mindset habits will make all the difference!\n\n---\n\n## 💡 Motivation Strategies Tailored for You\n\n### 1. 🎯 Focus on Small, Visible Wins\nDon\'t just think \"I need to lose 10kg.\" Instead, celebrate:\n- ✅ First week of working out → **Win!**\n- ✅ Chose water over soda today → **Win!**\n- ✅ Hit 7,000 steps → **Win!**\n\n> Small wins build **massive momentum** over time.\n\n---\n\n### 2. 📅 Work WITH Your Student Schedule\nAs a day shift student, your energy and time are limited. Try:\n- **Workout 2-3x per week** — not every day\n- **Keep sessions to 30-45 minutes** — short but effective\n- **Schedule workouts like a class** — block it in your calendar\n\n> You don\'t need to be perfect. You just need to **show up.**\n\n---\n\n### 3. 😴 Protect Your Sleep (This is Big for You!)\nKarl, you\'re currently sleeping **only 6 hours/night** — and this actually works *against* fat loss and motivation because:\n\n- 😓 Poor sleep = higher hunger hormones\n- 😓 Low energy = skipped workouts\n- 😓 Slower recovery = less results\n\n**Try to aim for at least 7-8 hours** whenever possible, especially on rest days!\n\n---\n\n### 4. 🩺 Use Your Health as Your \"Why\"\nWith your diabetes in mind, every workout you do is **genuinely improving your health** — not just your appearance. Exercise helps:\n- 📉 Regulate blood sugar levels\n- ❤️ Improve insulin sensitivity\n- ⚡ Boost daily energy\n\n> Every rep you do is a step toward a **healthier, longer life.** That\'s powerful, Karl.\n\n---\n\n### 5. 📸 Track Your Progress Visually\n- Take a **progress photo** every 2 weeks\n- Log your workouts (even just in your phone notes)\n- Weigh yourself **once a week**, same time, same conditions\n\n> The scale isn\'t everything — notice how your **clothes fit, energy levels, and mood** improve too!\n\n---\n\n### 6. 🧠 Reframe \"Motivation\" vs. \"Discipline\"\nHere\'s the truth:\n\n| Motivation | Discipline |\n|---|---|\n| Comes and goes | Shows up every day |\n| Feels good | Sometimes feels hard |\n| Unreliable | Builds character |\n\n> **Don\'t wait to feel motivated.** Build a routine small enough that it\'s hard to skip.\n\n---\n\n### 7. 🤝 Use Your Trainer as Accountability\nAt **Biofitness Gym**, your assigned trainer is there to review your progress and guide you. **Check in regularly** — even just sending a message keeps you accountable!\n\n---\n\n## ⚡ Your Weekly Consistency Challenge\n\n| Day | Action |\n|---|---|\n| Monday | Workout #1 |\n| Wednesday | Workout #2 |\n| Friday | Workout #3 |\n| Daily | Log your meals + drink 8 glasses of water |\n| Sunday | Reflect — what went well this week? |\n\n---\n\n## 💬 Remember This, Karl:\n\n> *\"You don\'t have to be extreme. You just have to be consistent.\"*\n\nYou\'re 21, you have so much time and potential ahead of you. Every single effort you make right now is **building a healthier version of yourself** — one that future Karl will thank you for. 💪\n\nYou\'ve got this! Is there a specific part of your routine you\'re struggling to stay consistent with? I\'d love to help you troubleshoot it! 😊', 'text', 0, '2026-04-14 03:21:35', 21136871);

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_inquiries`
--

INSERT INTO `contact_inquiries` (`id`, `name`, `email`, `subject`, `message`, `user_id`, `status`, `created_at`) VALUES
(1, 'Karl Blockstock', 'blockstockkarl@gmail.com', 'Membership Inquiry', 'I am interested!!', NULL, 'replied', '2026-03-20 08:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `muscle_groups` varchar(255) DEFAULT NULL,
  `equipment_needed` varchar(255) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `safety_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `category`, `muscle_groups`, `equipment_needed`, `difficulty_level`, `instructions`, `safety_notes`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Push-Up', 'Strength', 'Chest, Shoulders, Triceps', 'None', 'beginner', 'Start in a high plank position with hands shoulder-width apart. Lower your chest to the floor by bending your elbows, then push back up to the starting position.', 'Keep your core tight and body in a straight line throughout. Avoid flaring elbows out past 45 degrees.', NULL, 1, '2026-03-20 06:31:22', NULL),
(2, 'Bodyweight Squat', 'Strength', 'Quadriceps, Glutes, Hamstrings', 'None', 'beginner', 'Stand with feet shoulder-width apart, toes slightly turned out. Push hips back and bend knees to lower until thighs are parallel to the floor, then drive through heels to stand.', 'Keep knees tracking over toes. Do not let heels lift off the floor.', NULL, 1, '2026-03-20 06:31:22', NULL),
(3, 'Plank', 'Core', 'Core, Shoulders, Glutes', 'None', 'beginner', 'Hold a forearm plank position with elbows directly under shoulders. Keep hips level and hold for the target duration.', 'Avoid sagging hips or raising them too high. Breathe steadily throughout.', NULL, 1, '2026-03-20 06:31:22', NULL),
(4, 'Dumbbell Bench Press', 'Strength', 'Chest, Shoulders, Triceps', 'Dumbbells, Bench', 'intermediate', 'Lie on a flat bench holding a dumbbell in each hand at chest level. Press the dumbbells up until arms are fully extended, then lower with control.', 'Keep feet flat on the floor. Do not bounce the weights off your chest.', NULL, 1, '2026-03-20 06:31:22', NULL),
(5, 'Barbell Deadlift', 'Strength', 'Hamstrings, Glutes, Lower Back, Traps', 'Barbell, Weight Plates', 'intermediate', 'Stand with feet hip-width apart, bar over mid-foot. Hinge at hips and grip the bar just outside your legs. Drive through the floor to stand tall, then hinge back down with control.', 'Maintain a neutral spine throughout. Start light to learn the movement pattern before adding load.', NULL, 1, '2026-03-20 06:31:22', NULL),
(6, 'Pull-Up', 'Strength', 'Lats, Biceps, Rear Deltoids', 'Pull-Up Bar', 'intermediate', 'Hang from a bar with an overhand grip slightly wider than shoulder-width. Pull your chest toward the bar by driving elbows down, then lower with control.', 'Avoid swinging or kipping. If unable to complete a full rep, use a resistance band for assistance.', NULL, 1, '2026-03-20 06:31:22', NULL),
(7, 'Treadmill Running', 'Cardio', 'Legs, Cardiovascular System', 'Treadmill', 'beginner', 'Set treadmill to a comfortable pace. Run with an upright posture, relaxed shoulders, and a midfoot strike. Begin with a 5-minute warm-up walk.', 'Stay hydrated. Stop immediately if you feel chest pain or dizziness. Use the safety clip.', NULL, 1, '2026-03-20 06:31:22', NULL),
(8, 'Dumbbell Lunges', 'Strength', 'Quadriceps, Glutes, Hamstrings, Calves', 'Dumbbells', 'beginner', 'Stand holding dumbbells at your sides. Step forward with one foot and lower your back knee toward the floor, then push off the front foot to return to standing. Alternate legs.', 'Keep your front knee behind your toes. Maintain an upright torso throughout.', NULL, 1, '2026-03-20 06:31:22', NULL),
(9, 'Seated Cable Row', 'Strength', 'Lats, Rhomboids, Biceps', 'Cable Machine', 'intermediate', 'Sit at a cable row station with feet on the platform and knees slightly bent. Pull the handle to your lower abdomen, squeezing shoulder blades together, then extend arms with control.', 'Avoid rounding your lower back. Do not use momentum to pull the weight.', NULL, 1, '2026-03-20 06:31:22', NULL),
(10, 'Burpee', 'Cardio', 'Full Body, Cardiovascular System', 'None', 'advanced', 'From standing, drop hands to the floor, jump feet back to a plank, perform a push-up, jump feet forward, then explosively jump up with arms overhead.', 'Land softly to protect your joints. Modify by stepping instead of jumping if needed.', NULL, 1, '2026-03-20 06:31:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fitness_goals`
--

CREATE TABLE `fitness_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_type` enum('lose_weight','build_muscle','improve_stamina','maintain_fitness','other') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target_value` decimal(8,2) DEFAULT NULL,
  `target_unit` varchar(20) DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('active','completed','paused') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fitness_goals`
--

INSERT INTO `fitness_goals` (`id`, `user_id`, `goal_type`, `description`, `target_value`, `target_unit`, `target_date`, `status`) VALUES
(1, 18, 'lose_weight', NULL, NULL, NULL, NULL, 'active'),
(2, 16, 'build_muscle', NULL, NULL, NULL, NULL, 'active'),
(4, 21, 'lose_weight', NULL, NULL, NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `member_profiles`
--

CREATE TABLE `member_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `current_weight_kg` decimal(5,2) DEFAULT NULL,
  `target_weight_kg` decimal(5,2) DEFAULT NULL,
  `fitness_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `medical_conditions` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `membership_start` date DEFAULT NULL,
  `membership_end` date DEFAULT NULL,
  `assigned_trainer_id` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `work_schedule` enum('day_shift','night_shift','rotating_shift','work_from_home','student','not_working','other') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `sleep_hours_per_night` decimal(3,1) DEFAULT NULL,
  `activity_level` enum('sedentary','lightly_active','moderately_active','very_active','extremely_active') DEFAULT NULL,
  `dietary_preference` enum('no_preference','vegetarian','vegan','keto','halal','gluten_free','other') DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_profiles`
--

INSERT INTO `member_profiles` (`id`, `user_id`, `age`, `height_cm`, `current_weight_kg`, `target_weight_kg`, `fitness_level`, `medical_conditions`, `emergency_contact_name`, `emergency_contact_phone`, `membership_start`, `membership_end`, `assigned_trainer_id`, `address`, `work_schedule`, `occupation`, `sleep_hours_per_night`, `activity_level`, `dietary_preference`, `allergies`, `onboarding_completed`) VALUES
(3, 16, 21, 175.00, 80.00, 75.00, 'beginner', 'Diabetes', NULL, NULL, NULL, NULL, 2, 'Angono, Rizal', 'day_shift', NULL, 7.0, 'lightly_active', 'halal', NULL, 1),
(5, 18, 20, 172.00, 80.00, 65.00, 'intermediate', 'Diabetes', NULL, NULL, NULL, NULL, 2, 'R. Tolentino St. Brgy San Isidro Angono, Rizal', 'night_shift', 'Engineer', 6.0, 'moderately_active', 'vegetarian', 'Nuts', 1),
(7, 21, 21, 180.30, 75.00, 65.00, 'beginner', 'Diabetes', NULL, NULL, NULL, NULL, 2, 'Angono, Rizal', 'day_shift', 'Student', 6.0, 'lightly_active', 'no_preference', 'Shrimp', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
('maintenance_mode', 'false', NULL, NULL),
('max_ai_requests_per_day', '50', NULL, NULL),
('password_min_length', '8', NULL, NULL),
('session_timeout', '3600', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('member','trainer','admin') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `needs_password_change` tinyint(1) DEFAULT 1,
  `theme_preference` enum('dark','light') NOT NULL DEFAULT 'dark',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `status`, `needs_password_change`, `theme_preference`, `created_at`, `updated_at`, `last_login`, `profile_photo`) VALUES
(1, 'admin', 'admin@fitsense.com', '$2y$10$UpdX2NlR8YeE1xogIANLX.Fz7fkP2zMNiMYAqHMkhu49C794g0BXm', 'admin', 'Admin', 'User', '09123456789', 'active', 0, 'dark', '2026-03-20 06:31:22', '2026-04-14 02:56:13', '2026-04-14 02:56:10', NULL),
(2, 'cjvergara', 'cjthetrainer@gmail.com', '$2y$10$4Q.IWM9TH80XPl37Hr/Cy.1RKWmP8EXWKLALHcHT/Je0/bSnAWU9q', 'trainer', 'CJ', 'Vergara', '09123456789', 'active', 0, 'dark', '2026-03-20 06:40:17', '2026-04-14 02:49:30', '2026-04-14 01:52:49', 'uploads/avatars/trainer_2_1774001505.png'),
(16, 'karl.blox', 'karlblox@gmail.com', '$2y$10$bvbnCjDd1xqiKJ078x1.quHNziTiYafHhqcesgsjeD6TTdk81a1IK', 'member', 'Karl', 'Blockstock', '09123456789', 'active', 0, 'dark', '2026-03-20 10:24:49', '2026-04-14 02:55:02', '2026-04-14 02:54:36', 'uploads/avatars/member_16_1774012727.jpg'),
(18, 'karl.blockstock', 'blockstockkc@gmail.com', '$2y$10$m9Juj1TWfrAA.ZOHwQ63i.amH4seDTE9G/Krl74tBNXLuphrEvZqG', 'member', 'Karl', 'Blockstock', '123456789', 'active', 0, 'dark', '2026-04-10 07:28:57', '2026-04-10 07:30:58', '2026-04-10 07:30:38', NULL),
(21, 'karl.blockstock1', 'qkcblockstock@tip.edu.ph', '$2y$10$1fZB33stQ.BNj/B7/J/Yu.XSwFQFRXKAu3opTWLnc2DWh0UPJewzq', 'member', 'Karl', 'Blockstock', '09765725385', 'active', 0, 'dark', '2026-04-14 02:56:36', '2026-04-14 03:06:24', '2026-04-14 02:57:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `weight_logs`
--

CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `log_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weight_logs`
--

INSERT INTO `weight_logs` (`id`, `user_id`, `weight_kg`, `log_date`, `notes`, `created_at`) VALUES
(1, 16, 76.00, '2026-03-19', NULL, '2026-03-20 13:21:21'),
(2, 16, 77.70, '2026-03-20', NULL, '2026-03-20 13:26:28'),
(3, 21, 75.00, '2026-04-14', NULL, '2026-04-14 02:58:00');

-- --------------------------------------------------------

--
-- Table structure for table `workout_sessions`
--

CREATE TABLE `workout_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recommendation_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `exercises_completed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exercises_completed`)),
  `notes` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `calories_burned` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workout_sessions`
--

INSERT INTO `workout_sessions` (`id`, `user_id`, `recommendation_id`, `session_date`, `duration_minutes`, `exercises_completed`, `notes`, `rating`, `calories_burned`, `created_at`) VALUES
(1, 16, NULL, '2026-03-20', 45, '[]', 'Very good!', 5, 1800, '2026-03-20 12:42:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `member_profiles`
--
ALTER TABLE `member_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_trainer_id` (`assigned_trainer_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`log_date`);

--
-- Indexes for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recommendation_id` (`recommendation_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `member_profiles`
--
ALTER TABLE `member_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `weight_logs`
--
ALTER TABLE `weight_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_recommendations`
--
ALTER TABLE `ai_recommendations`
  ADD CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `exercises_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fitness_goals`
--
ALTER TABLE `fitness_goals`
  ADD CONSTRAINT `fitness_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_profiles`
--
ALTER TABLE `member_profiles`
  ADD CONSTRAINT `member_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `member_profiles_ibfk_2` FOREIGN KEY (`assigned_trainer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD CONSTRAINT `weight_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD CONSTRAINT `workout_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_sessions_ibfk_2` FOREIGN KEY (`recommendation_id`) REFERENCES `ai_recommendations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

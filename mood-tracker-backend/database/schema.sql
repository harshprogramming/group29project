CREATE DATABASE IF NOT EXISTS mood_tracker;
USE mood_tracker;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Reminder;
DROP TABLE IF EXISTS activityentry;
DROP TABLE IF EXISTS RecommendationRule;
DROP TABLE IF EXISTS MoodEntry;
DROP TABLE IF EXISTS Users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    age INT NULL,
    gender VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE MoodEntry (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    stress_level TINYINT NOT NULL,
    mood_level TINYINT NOT NULL,
    emotions VARCHAR(50) NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mood_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE,
    INDEX idx_mood_user_date_time (user_id, date, time),
    INDEX idx_mood_emotion (user_id, emotions),
    INDEX idx_mood_stress_mood (user_id, stress_level, mood_level)
);

CREATE TABLE activityentry (
    activity_entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NULL,
    note TEXT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    duration_minutes INT NULL,
    effort_level TINYINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE,
    INDEX idx_activity_user_date (user_id, date),
    INDEX idx_activity_user_name (user_id, activity_name),
    INDEX idx_activity_user_date_time (user_id, date, time)
);

CREATE TABLE RecommendationRule (
    recommendation_rule_id INT AUTO_INCREMENT PRIMARY KEY,
    emotion_name VARCHAR(50) NOT NULL,
    min_stress_level TINYINT NOT NULL,
    max_stress_level TINYINT NOT NULL,
    min_mood_level TINYINT NOT NULL,
    max_mood_level TINYINT NOT NULL,
    activity_name VARCHAR(100) NOT NULL,
    recommendation_text TEXT NOT NULL,
    priority_score INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rule_lookup (
        emotion_name,
        is_active,
        min_stress_level,
        max_stress_level,
        min_mood_level,
        max_mood_level,
        priority_score
    ),
    INDEX idx_rule_activity (activity_name)
);

CREATE TABLE Reminder (
    reminder_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reminder_time DATETIME NOT NULL,
    reminder_message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reminder_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
);

INSERT INTO RecommendationRule (
    emotion_name,
    min_stress_level,
    max_stress_level,
    min_mood_level,
    max_mood_level,
    activity_name,
    recommendation_text,
    priority_score,
    is_active
) VALUES
('Happy', 1, 2, 4, 5, 'Walking',
 'You seem to be in a strong emotional state. A short walk can help maintain your positive mood and keep your routine balanced.',
 4, 1),

('Happy', 1, 3, 4, 5, 'Journaling',
 'You are doing well right now. Journaling can help you capture what is working so you can repeat it later.',
 3, 1),

('Calm', 1, 2, 3, 5, 'Reading',
 'You seem calm and steady. Reading can help preserve that balance and give you space to recharge.',
 4, 1),

('Calm', 1, 3, 3, 5, 'Stretching',
 'A light stretching session fits well with your current calm state and can help keep stress low.',
 3, 1),

('Motivated', 1, 3, 4, 5, 'Jogging',
 'You seem motivated and energized. Jogging is a good way to channel that momentum productively.',
 4, 1),

('Motivated', 1, 3, 4, 5, 'Walking',
 'You have positive energy right now. A walk can help you stay focused without overloading yourself.',
 3, 1),

('Neutral', 2, 3, 3, 3, 'Journaling',
 'You appear emotionally steady. Journaling can help you reflect and notice patterns before stress builds.',
 4, 1),

('Neutral', 2, 4, 2, 3, 'Stretching',
 'Your mood is balanced but could use support. Stretching may help prevent tension from building up.',
 3, 1),

('Sad', 2, 4, 1, 2, 'Talking to a Friend',
 'Your recent mood suggests you may benefit from connection and support. Talking to a friend could help lighten the emotional load.',
 5, 1),

('Sad', 2, 5, 1, 2, 'Listening to Music',
 'Music can be a gentle way to support yourself when your mood is low and emotions feel heavy.',
 4, 1),

('Sad', 1, 4, 1, 3, 'Journaling',
 'Journaling may help you process what you are feeling and understand what might be contributing to this mood.',
 3, 1),

('Angry', 3, 5, 1, 3, 'Walking',
 'A short walk can help create space between the feeling and your reaction while lowering emotional intensity.',
 5, 1),

('Angry', 3, 5, 1, 3, 'Deep Breathing',
 'Your stress and mood levels suggest it may help to slow down and regulate your breathing before responding.',
 4, 1),

('Angry', 2, 5, 1, 3, 'Stretching',
 'Stretching may help release some physical tension that often comes with anger and frustration.',
 3, 1),

('Stressed', 4, 5, 1, 2, 'Deep Breathing',
 'Your current state suggests a high-stress moment. Deep breathing is recommended to help calm your body and reduce overwhelm.',
 6, 1),

('Stressed', 4, 5, 1, 2, 'Meditation',
 'Meditation may help you slow racing thoughts and recover from a high-stress period.',
 5, 1),

('Stressed', 3, 5, 1, 3, 'Walking',
 'A short walk can help break the stress cycle and support emotional reset.',
 4, 1),

('Stressed', 3, 5, 2, 3, 'Stretching',
 'Stretching may help release tension and support recovery when stress is elevated.',
 3, 1);
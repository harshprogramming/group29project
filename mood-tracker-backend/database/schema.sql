CREATE DATABASE IF NOT EXISTS mood_tracker;
USE mood_tracker;

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
    intensity TINYINT NOT NULL,
    stress_level TINYINT NOT NULL,
    mood_level TINYINT NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mood_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Emotion_type (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    emotion_name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE Stress_factor (
    factor_id INT AUTO_INCREMENT PRIMARY KEY,
    factor_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE Recommendation (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    target_emotion VARCHAR(60) NOT NULL,
    suggested_activity VARCHAR(100) NOT NULL
);

CREATE TABLE MoodEntry_Emotion (
    entry_id INT NOT NULL,
    type_id INT NOT NULL,
    PRIMARY KEY (entry_id, type_id),
    CONSTRAINT fk_me_entry FOREIGN KEY (entry_id) REFERENCES MoodEntry(entry_id) ON DELETE CASCADE,
    CONSTRAINT fk_me_type FOREIGN KEY (type_id) REFERENCES Emotion_type(type_id) ON DELETE CASCADE
);

CREATE TABLE MoodEntry_Stress (
    entry_id INT NOT NULL,
    factor_id INT NOT NULL,
    PRIMARY KEY (entry_id, factor_id),
    CONSTRAINT fk_ms_entry FOREIGN KEY (entry_id) REFERENCES MoodEntry(entry_id) ON DELETE CASCADE,
    CONSTRAINT fk_ms_factor FOREIGN KEY (factor_id) REFERENCES Stress_factor(factor_id) ON DELETE CASCADE
);

CREATE TABLE MoodEntry_Activity (
    entry_id INT NOT NULL,
    activity_id INT NOT NULL,
    PRIMARY KEY (entry_id, activity_id),
    CONSTRAINT fk_ma_entry FOREIGN KEY (entry_id) REFERENCES MoodEntry(entry_id) ON DELETE CASCADE,
    CONSTRAINT fk_ma_activity FOREIGN KEY (activity_id) REFERENCES Activities(activity_id) ON DELETE CASCADE
);

INSERT INTO Emotion_type (emotion_name) VALUES
('Happy'),
('Sad'),
('Anxious'),
('Calm'),
('Angry'),
('Overwhelmed'),
('Tired'),
('Motivated');

INSERT INTO Stress_factor (factor_name) VALUES
('School'),
('Work'),
('Money'),
('Family'),
('Health'),
('Exams'),
('Relationships'),
('Deadlines');

INSERT INTO Activities (activity_name) VALUES
('Gym'),
('Reading'),
('Gaming'),
('Walking'),
('Meditation'),
('Sleeping'),
('Music'),
('Talking to Friends');

INSERT INTO Recommendation (target_emotion, suggested_activity) VALUES
('Anxious', 'Meditation'),
('Sad', 'Walking'),
('Angry', 'Gym'),
('Tired', 'Sleeping'),
('Overwhelmed', 'Reading'),
('Calm', 'Music');
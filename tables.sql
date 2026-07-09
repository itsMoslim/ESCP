-- Admin
CREATE TABLE admin (
    username VARCHAR(50) PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coaches
CREATE TABLE coaches (
    username VARCHAR(50) PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    verification_flag BOOLEAN DEFAULT FALSE,   -- admin must verify coach before active
    account_active BOOLEAN DEFAULT TRUE,       -- coach can be deactivated if needed
    hourly_rate DECIMAL(10,2),                 -- budget clarity (per hour/session)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Players
CREATE TABLE players (
    username VARCHAR(50) PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Profiles
CREATE TABLE profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_type ENUM('Coach','Player') NOT NULL,
    username VARCHAR(50) NOT NULL,
    bio TEXT NOT NULL,
    experience_years INT NOT NULL,
    rating DECIMAL(3,2) DEFAULT 0,   -- starts at 0, updated later
    profile_picture VARCHAR(255) 
);


CREATE TABLE player_preferences (
    pref_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    coaching_goal ENUM('Improving aim','Team coordination','Better mechanics and positioning') NOT NULL,
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
    UNIQUE (profile_id) -- one preferences record per player
);




-- Games
CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE game_ranks (
    rank_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    rank_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);

CREATE TABLE game_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);

CREATE TABLE user_games (
    user_game_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    game_id INT NOT NULL,
    rank_id INT,
    role_id INT,
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    FOREIGN KEY (rank_id) REFERENCES game_ranks(rank_id),
    FOREIGN KEY (role_id) REFERENCES game_roles(role_id),
    UNIQUE (profile_id, game_id, role_id) -- prevents duplicate entries
);
CREATE TABLE coach_formats (
    format_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    game_id INT NOT NULL,
    format ENUM('Video-On-Demand (VOD) Review','Live Coaching','Both') NOT NULL,
    coaching_goal TEXT,
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (game_id) REFERENCES games(game_id),
    UNIQUE (profile_id, game_id)
);

-- Solo Sessions
CREATE TABLE solo_sessions (
    solo_session_id INT AUTO_INCREMENT PRIMARY KEY,
    coach_profile_id INT NOT NULL,
    player_profile_id INT,
    game_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    status ENUM('Available','Requested','Confirmed','Completed','Cancelled') DEFAULT 'Available'
);

-- Group Sessions
CREATE TABLE group_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    coach_profile_id INT NOT NULL,
    game_id INT NOT NULL,
    training_detail TEXT,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    min_participants INT DEFAULT 5,
    max_participants INT DEFAULT 10,
    fee DECIMAL(10,2) NOT NULL,
    status ENUM('Scheduled','Ongoing','Completed','Cancelled') DEFAULT 'Scheduled'
);

CREATE TABLE group_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    player_profile_id INT NOT NULL,
    result ENUM('Win','Loss','Completed','Pending') DEFAULT 'Pending'
);

-- Payments
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    player_profile_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Paid','Refunded') DEFAULT 'Paid',
    paid_at TIMESTAMP NULL
);

CREATE TABLE payment_sessions (
    payment_session_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    session_type ENUM('Group','Solo') NOT NULL,
    session_id INT NOT NULL
);

-- Reviews
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    session_type ENUM('Solo','Group') NOT NULL,
    coach_profile_id INT NOT NULL,
    player_profile_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_profile_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (player_profile_id) REFERENCES profiles(profile_id)
);



CREATE TABLE security_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(255) NOT NULL UNIQUE
);


CREATE TABLE user_security_questions (
    user_sec_id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (question_id) REFERENCES security_questions(question_id),
    UNIQUE (profile_id, question_id)
);


CREATE TABLE coach_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    session_type ENUM('Solo','Group') NOT NULL,
    coach_id INT NOT NULL,
    player_id INT NOT NULL,
    rating ENUM('Good','Average','Below Average') NOT NULL,
    numeric_rating FLOAT NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (player_id) REFERENCES profiles(profile_id)
);


CREATE TABLE articles (
    article_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES profiles(profile_id)
);


CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (article_id) REFERENCES articles(article_id),
    FOREIGN KEY (user_id) REFERENCES profiles(profile_id)
);



-- Quest

CREATE TABLE quizzes (
    quiz_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    game_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);


CREATE TABLE quiz_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
);


CREATE TABLE quiz_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    player_profile_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,   -- percentage score
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id),
    FOREIGN KEY (player_profile_id) REFERENCES profiles(profile_id)
);


-- Gamification 

CREATE TABLE challenges (
    challenge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    target_value INT NULL,   -- nullable for permanent rewards
    status ENUM('Permanent','Monthly') NOT NULL
);

CREATE TABLE rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    badge_image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id)
);

CREATE TABLE player_challenges_reward (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_profile_id INT NOT NULL,
    challenge_id INT NOT NULL,
    progress_value INT NULL,
    date_completed TIMESTAMP NULL,
    expiry_date TIMESTAMP NULL,
    challenge_status ENUM('Pending','Completed') DEFAULT 'Pending',
    reward_status ENUM('Active','Expired') DEFAULT 'Active',
    FOREIGN KEY (player_profile_id) REFERENCES profiles(profile_id),
    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id)
);

CREATE TABLE support_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


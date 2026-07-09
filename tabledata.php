<?php
include 'db_connect.php';

// --- Admin (only one) ---
$adminPw = password_hash("admin0password", PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO admin (username, password_hash, email) VALUES
('admin_master', '$adminPw', 'admin@example.com')");


// --- Coaches ---
$coachPw1 = password_hash("coach1pass", PASSWORD_DEFAULT);
$coachPw2 = password_hash("coach2pass", PASSWORD_DEFAULT);
$coachPw3 = password_hash("coach3pass", PASSWORD_DEFAULT);
$coachPw4 = password_hash("coach4pass", PASSWORD_DEFAULT);
$coachPw5 = password_hash("coach5pass", PASSWORD_DEFAULT);
$coachPw6 = password_hash("coach6pass", PASSWORD_DEFAULT);
$coachPw7 = password_hash("coach7pass", PASSWORD_DEFAULT);
$coachPw8 = password_hash("coach8pass", PASSWORD_DEFAULT);
$coachPw9 = password_hash("coach9pass", PASSWORD_DEFAULT);
$coachPw10 = password_hash("coach10pass", PASSWORD_DEFAULT);

$conn->query("INSERT INTO coaches (username, fullname, email, password_hash, verification_flag, account_active, hourly_rate) VALUES
('coach1','Fahad Al-Mutairi','fahad.riyadh@escp.sa','$coachPw1',TRUE,TRUE,150.00),
('coach2','Aisha Al-Qahtani','aisha.jeddah@escp.sa','$coachPw2',FALSE,FALSE,120.00),
('coach3','Mohammed Al-Harbi','mohammed.dammam@escp.sa','$coachPw3',TRUE,TRUE,200.00),
('coach4','Noura Al-Shehri','noura.madina@escp.sa','$coachPw4',TRUE,TRUE,180.00),
('coach5','Salman Al-Otaibi','salman.makkah@escp.sa','$coachPw5',FALSE,TRUE,100.00),
('coach6','Khalid Al-Saleh','khalid.riyadh@escp.sa','$coachPw6',TRUE,TRUE,220.00),
('coach7','Fatimah Al-Dossari','fatimah.jeddah@escp.sa','$coachPw7',TRUE,TRUE,160.00),
('coach8','Abdulrahman Al-Ghamdi','abdulrahman.dammam@escp.sa','$coachPw8',FALSE,TRUE,140.00),
('coach9','Huda Al-Shammari','huda.madina@escp.sa','$coachPw9',TRUE,TRUE,130.00),
('coach10','Saad Al-Anazi','saad.makkah@escp.sa','$coachPw10',TRUE,TRUE,170.00)");


// --- Players ---
$playerPw1 = password_hash("player1pass", PASSWORD_DEFAULT);
$playerPw2 = password_hash("player2pass", PASSWORD_DEFAULT);
$playerPw3 = password_hash("player3pass", PASSWORD_DEFAULT);

$conn->query("INSERT INTO players (username, fullname, email, password_hash, account_active) VALUES
('player1','Abdullah Al-Harbi','abdullah.riyadh@escp.sa','$playerPw1',TRUE),
('player2','Mariam Al-Fahad','mariam.jeddah@escp.sa','$playerPw2',TRUE),
('player3','Yousef Al-Nasser','yousef.dammam@escp.sa','$playerPw3',TRUE)");


// --- Profiles ---
$conn->query("INSERT INTO profiles (profile_type,username,bio,experience_years,rating,profile_picture) VALUES
('Coach','coach1','Valorant FPS Specialist from Riyadh',5,4.6,'coach1.png'),
('Coach','coach2','Overwatch Support Coach from Jeddah',3,4.2,'coach2.png'),
('Player','player1','Competitive Valorant player in Riyadh',2,3.8,'player1.png'),
('Player','player2','Rocket League enthusiast from Jeddah',1,3.5,'player2.png'),
('Player','player3','League of Legends ADC from Dammam',4,4.0,'player3.png'),
('Coach','coach3','Dammam-based Rocket League strategist',6,4.7,'coach3.png'),
('Coach','coach4','Madina LoL macro coach',4,4.3,'coach4.png'),
('Coach','coach5','Makkah FPS aim trainer',7,4.1,'coach5.png'),
('Coach','coach6','Riyadh esports analyst',8,4.8,'coach6.png'),
('Coach','coach7','Jeddah Overwatch DPS mentor',5,4.5,'coach7.png'),
('Coach','coach8','Dammam Rocket League goalkeeper coach',3,4.0,'coach8.png'),
('Coach','coach9','Madina LoL support specialist',2,3.9,'coach9.png'),
('Coach','coach10','Makkah Valorant initiator coach',6,4.4,'coach10.png')");


// --- Player Preferences ---
$conn->query("INSERT INTO player_preferences (profile_id, coaching_goal) VALUES
(3, 'Improving aim'),   -- player1
(4, 'Team coordination'), -- player2
(5, 'Better mechanics and positioning') -- player3
");


// --- Games ---
$conn->query("INSERT INTO games (name) VALUES 
('Valorant'),('Overwatch 2'),('Rocket League'),('League of Legends')");

// --- Game Ranks & Roles ---
$conn->query("INSERT INTO game_ranks (game_id,rank_name) VALUES
(1,'Iron'),(1,'Bronze'),(1,'Silver'),(1,'Gold'),(1,'Platinum'),(1,'Diamond'),(1,'Ascendant'),(1,'Immortal'),(1,'Radiant'),
(2,'Bronze'),(2,'Silver'),(2,'Gold'),(2,'Platinum'),(2,'Diamond'),(2,'Master'),(2,'Grandmaster'),(2,'Champion'),
(3,'Bronze'),(3,'Silver'),(3,'Gold'),(3,'Platinum'),(3,'Diamond'),(3,'Champion'),(3,'Grand Champion'),(3,'Supersonic Legend'),
(4,'Iron'),(4,'Bronze'),(4,'Silver'),(4,'Gold'),(4,'Platinum'),(4,'Emerald'),(4,'Diamond'),(4,'Master'),(4,'Grandmaster'),(4,'Challenger')");

$conn->query("INSERT INTO game_roles (game_id,role_name) VALUES
(1,'Duelist'),(1,'Initiator'),(1,'Controller'),(1,'Sentinel'),
(2,'Tank'),(2,'Damage (DPS)'),(2,'Support'),
(3,'First Man'),(3,'Second Man'),(3,'Third Man'),(3,'Goalkeeper'),
(4,'Top'),(4,'Jungle'),(4,'Mid'),(4,'ADC'),(4,'Support')");

// --- User Games ---
$conn->query("INSERT INTO user_games (profile_id, game_id, rank_id, role_id) VALUES
(3, 1, 5, 1),   -- player1: Valorant, rank Platinum (id=5), role Duelist (id=1)
(4, 3, 21, 9),  -- player2: Rocket League, rank Platinum (id=21), role Second Man (id=9)
(5, 4, 31, 15),  -- player3: LoL, rank Diamond (id=31), role ADC (id=15)
(1, 1, 9, 3),   -- coach1: Valorant, rank Radiant (id=9), role Controller (id=3)
(2, 2, 14, 6),  -- coach2: Overwatch, rank Diamond (id=14), role Damage (DPS) (id=6)
(6, 3, 25, 8),  -- coach3: Rocket League, rank Supersonic Legend (id=25), role First Man (id=8)
(7, 4, 32, 15),  -- coach4: LoL, rank Master (id=32), role ADC (id=15)
(8, 1, 6, 1),   -- coach5: Valorant, rank Diamond (id=6), role Duelist (id=1)
(9, 3, 22, 11),  -- coach6: Rocket League, rank Diamond (id=22), role Goalkeeper (id=11)
(10, 2, 13, 6), -- coach7: Overwatch, rank Platinum (id=13), role Damage (DPS) (id=6)
(11, 3, 23, 11), -- coach8: Rocket League, rank Champion (id=23), role Goalkeeper (id=11)
(12, 4, 30, 16), -- coach9: LoL, rank Emerald (id=30), role Support (id=16)
(13, 1, 7, 2)   -- coach10: Valorant, rank Ascendant (id=7), role Initiator (id=2)
");


// --- Coach Formats ---
$conn->query("INSERT INTO coach_formats (profile_id, game_id, format, coaching_goal) VALUES
(1, 1, 'Video-On-Demand (VOD) Review', 'Improving aim'),                         -- coach1 Valorant
(2, 2, 'Live Coaching', 'Team coordination'),                  -- coach2 Overwatch
(6, 3, 'Both', 'Team coordination'),                           -- coach3 Rocket League
(7, 4, 'Live Coaching', 'Better mechanics and positioning'),   -- coach4 LoL
(8, 1, 'Video-On-Demand (VOD) Review', 'Improving aim'),                         -- coach5 Valorant
(9, 3, 'Both', 'Better mechanics and positioning'),            -- coach6 (generic game)
(10, 2, 'Live Coaching', 'Improving aim'),                     -- coach7 Overwatch
(11, 3, 'Video-On-Demand (VOD) Review', 'Better mechanics and positioning'),     -- coach8 Rocket League
(12, 4, 'Live Coaching', 'Team coordination'),                 -- coach9 LoL
(13, 1, 'Both', 'Improving aim')                               -- coach10 Valorant
");

// --- Solo Sessions ---
$conn->query("INSERT INTO solo_sessions (coach_profile_id,player_profile_id,game_id,session_date,start_time,end_time,hourly_rate,status) VALUES
(1,3,1,'2026-03-20','10:00:00','11:00:00',100.00,'Completed'),   
(2,4,2,'2026-04-05','12:00:00','13:00:00',120.00,'Scheduled'),   
(3,5,3,'2026-04-03','14:00:00','15:00:00',150.00,'Ongoing'),     
(7,3,4,'2026-03-25','16:00:00','17:00:00',200.00,'Completed'),
(12,3,4,'2026-03-27','15:00:00','16:00:00',130.00,'Completed'),
(1,3,1,'2026-04-07','18:00:00','19:00:00',100.00,'Scheduled'),
(1,4,1,'2026-04-10','10:00:00','11:00:00',110.00,'Requested'),
(1,5,3,'2026-04-12','12:00:00','13:00:00',130.00,'Requested'),
(13,5,3,'2026-04-12','14:00:00','15:00:00',170.00,'Completed'),
(1,NULL,2,'2026-04-15','14:00:00','15:00:00',150.00,'Available'),
(2,NULL,4,'2026-04-18','16:00:00','17:00:00',150.00,'Available'),
(1,NULL,1,'2026-04-20','18:00:00','19:00:00',150.00,'Available')
");


// --- Group Sessions ---
$conn->query("INSERT INTO group_sessions (coach_profile_id,game_id,training_detail,session_date,start_time,end_time,min_participants,max_participants,fee,status) VALUES
(6,1,'Rocket League Team Training','2026-04-03','10:00:00','12:00:00',5,10,500.00,'Ongoing'),   
(7,2,'League of Legends Strategy','2026-04-10','13:00:00','15:00:00',5,10,600.00,'Scheduled'),    
(8,3,'Rocket League Defense','2026-03-22','16:00:00','18:00:00',5,10,700.00,'Completed'),  
(9,4,'Rocket League Pathing','2026-04-12','19:00:00','21:00:00',5,10,800.00,'Scheduled'),     
(10,1,'Valorant Aim Practice','2026-03-18','09:00:00','11:00:00',5,10,500.00,'Completed'),
(1,1,'Valorant Advanced Strategy','2026-04-08','10:00:00','12:00:00',5,10,550.00,'Scheduled'),
(1,3,'Valorant Team Rotation','2026-04-03','14:00:00','16:00:00',5,10,600.00,'Ongoing')
");


// --- Group Enrollments ---
$conn->query("INSERT INTO group_enrollments (session_id,player_profile_id,result) VALUES
(1,3,'Completed'),
(1,4,'Win'),
(2,5,'Loss'),
(3,3,'Pending'),
(4,4,'Win')");

// --- Payments ---
$conn->query("INSERT INTO payments (player_profile_id,amount,status, paid_at) VALUES
(3,100.00,'Paid', NOW()),
(4,120.00,'Refunded', NOW()),
(5,150.00,'Paid', NOW()),
(3,200.00,'Refunded', NOW()),
(4,100.00,'Paid', NOW())");

// --- Payment Sessions ---
$conn->query("INSERT INTO payment_sessions (payment_id,session_type,session_id) VALUES
(1,'Solo',1),
(2,'Solo',2),
(3,'Solo',3),
(4,'Group',1),
(5,'Group',2)");

// --- Reviews ---
$conn->query("INSERT INTO reviews 
(session_id, session_type, coach_profile_id, player_profile_id, rating, comments) VALUES
(1, 'Solo', 1, 3, 5, 'Excellent Valorant coaching'),
(2, 'Group', 2, 4, 4, 'Good Overwatch tips'),
(3, 'Group', 1, 5, 3, 'Rocket League defense needs more detail'),
(4, 'Solo', 2, 3, 5, 'LoL jungle pathing was perfect'),
(5, 'Solo', 1, 4, 4, 'Valorant aim practice improved my skills'),
(6, 'Group', 1, 3, 5, 'Great teamwork session, very insightful'),
(7, 'Solo', 1, 5, 4, 'Helpful one-on-one coaching, improved my aim')
");



// --- Security Questions ---
$conn->query("INSERT INTO security_questions (question_text) VALUES
('What is your mother\'s maiden name?'),
('What was the name of your first school?'),
('What is your favorite sports team?'),
('What is the name of your hometown?'),
('What was the make of your first car?')");

// --- User Security Questions (Saudi-centric sample answers, hashed) ---
$ans1 = password_hash("AlQahtani", PASSWORD_DEFAULT);
$ans2 = password_hash("Riyadh Primary", PASSWORD_DEFAULT);
$ans3 = password_hash("AlHilal", PASSWORD_DEFAULT);
$ans4 = password_hash("Jeddah", PASSWORD_DEFAULT);
$ans5 = password_hash("Toyota Corolla", PASSWORD_DEFAULT);

$conn->query("INSERT INTO user_security_questions (profile_id, question_id, answer_hash) VALUES
(1,1,'$ans1'),   -- Coach Fahad
(2,2,'$ans2'),   -- Coach Aisha
(3,3,'$ans3'),   -- Player Abdullah
(4,4,'$ans4'),   -- Player Mariam
(5,5,'$ans5')"); // Player Yousef

// --- Coach Feedback ---
$conn->query("INSERT INTO coach_feedback 
(session_id, session_type, coach_id, player_id, rating, numeric_rating, comments) VALUES
(1, 'Solo', 1, 3, 'Good', 4.5, 'Strong aim and teamwork'),
(5, 'Group', 1, 3, 'Average', 3.0, 'Needs improvement in positioning'),
(3, 'Group', 2, 3, 'Good', 4.2, 'Excellent communication during group play'),
(2, 'Solo', 2, 4, 'Below Average', 2.5, 'Struggled with mechanics, needs practice'),
(4, 'Solo', 1, 5, 'Good', 4.0, 'Solid performance in jungle pathing')");



// --- Update Player Ratings ---
$conn->query("UPDATE profiles 
SET rating = (SELECT AVG(numeric_rating) FROM coach_feedback WHERE player_id = 3) 
WHERE profile_id = 3");

$conn->query("UPDATE profiles 
SET rating = (SELECT AVG(numeric_rating) FROM coach_feedback WHERE player_id = 4) 
WHERE profile_id = 4");

$conn->query("UPDATE profiles 
SET rating = (SELECT AVG(numeric_rating) FROM coach_feedback WHERE player_id = 5) 
WHERE profile_id = 5");

// --- articles ---
$conn->query("INSERT INTO articles (user_id, title, content, created_at, updated_at) VALUES
(3, 'Climbing Valorant Ranks in Riyadh', 'Sharing my journey as a competitive Valorant player, focusing on aim drills, communication, and teamwork strategies that helped me climb from Gold to Diamond.', '2026-03-01 14:30:00', NULL),
(1, 'FPS Training Tips from a Coach', 'As a Valorant FPS Specialist, I emphasize daily aim routines, crosshair placement, and reviewing recorded matches to improve consistency.', '2026-03-02 10:15:00', NULL),
(4, 'Rocket League Progress Journal', 'I started Rocket League training last year. This post covers my struggles with rotations, aerials, and how coaching sessions improved my confidence.', '2026-03-03 18:45:00', NULL),
(6, 'Rocket League Strategy Insights', 'From a coach perspective, I explain how positioning and boost management can change the outcome of matches. Players often overlook these basics.', '2026-03-04 09:00:00', NULL),
(5, 'League of Legends ADC Journey', 'As an ADC main, I share how I improved my laning phase, CSing, and map awareness. Coaching helped me transition from casual play to ranked consistency.', '2026-03-05 20:20:00', NULL)
");


// --- comments ---
$conn->query("INSERT INTO comments (article_id, user_id, content, created_at) VALUES
(1, 2, 'Great progress! Communication really makes a difference in Valorant.', '2026-03-01 15:00:00'),
(1, 10, 'As a coach, I agree — teamwork is often more important than raw aim.', '2026-03-01 16:10:00'),

(2, 3, 'Thanks coach! I’ll try adding aim drills into my daily routine.', '2026-03-02 11:00:00'),
(2, 5, 'Crosshair placement advice helped me a lot in LoL too — fundamentals matter.', '2026-03-02 12:30:00'),

(3, 6, 'Rotations are tough at first, but once you master them, the game feels smoother.', '2026-03-03 19:00:00'),
(3, 4, 'I also struggled with aerials — practice packs helped me improve.', '2026-03-03 19:30:00'),
(3, 8, 'Boost management is key. Try focusing on small boost pads instead of big ones.', '2026-03-03 20:00:00'),
(3, 11, 'Confidence grows with consistency. Keep grinding!', '2026-03-03 21:15:00'),

(4, 12, 'Positioning tips are gold — I often lose matches because of poor rotations.', '2026-03-04 09:30:00'),
(4, 7, 'Boost management is underrated. Thanks for highlighting it!', '2026-03-04 10:00:00')
");


// --- Quizzes ---
$conn->query("INSERT INTO quizzes (title, description, game_id) VALUES
('Valorant Basics','Test your Valorant knowledge',1),
('Overwatch 2 Strategy','Support role fundamentals',2),
('Rocket League Skills','Gameplay mechanics quiz',3),
('League of Legends Macro','Map awareness and rotations',4)
");

// --- QuizQuestions ---
$conn->query("INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES
(1,'Max rounds in competitive?','12','13','24','25','B'),
(1,'Agent with Sky Smokes?','Brimstone','Omen','Astra','Viper','A'),
(1,'Spike plant time?','3','4','5','7','C'),
(1,'Ultimate points needed for Jett?','6','7','8','9','B'),
(1,'Max credits economy?','9000','10000','12000','15000','C'),
(2,'Support hero with immortality field?','Ana','Baptiste','Mercy','Moira','B'),
(2,'Payload maps objective?','Capture','Escort','Defend','Hybrid','B'),
(2,'Ultimate of Zenyatta?','Transcendence','Sound Barrier','Nano Boost','Coalescence','A'),
(2,'Tank hero with shield deploy?','Reinhardt','Sigma','Winston','D.Va','A'),
(2,'Overwatch team size?','5','6','7','8','A'),
(3,'Max team size ranked?','2','3','4','5','B'),
(3,'Boost pad full boost?','Small','Large','None','Both','B'),
(3,'Kickoff strategy name?','Cheat','Fake','Speed','All','D'),
(3,'Ball reset after goal?','Center','Corner','Goal line','Random','A'),
(3,'Overtime duration?','Unlimited','5 min','10 min','15 min','A'),
(4,'Dragon buff type?','Infernal','Ocean','Mountain','All','D'),
(4,'Baron Nashor spawn time?','15','20','25','30','B'),
(4,'Role ADC stands for?','Attack Damage Carry','Ability Damage Control','Armor Damage Counter','All Damage Champion','A'),
(4,'Blue buff grants?','Mana regen','Health regen','Attack speed','Armor','A'),
(4,'Turret plating gold until?','10 min','12 min','14 min','15 min','C')
");

// --- QuizAttempts ---
$conn->query("INSERT INTO quiz_attempts (quiz_id, player_profile_id, score, attempt_date) VALUES
(1,3,65.00,'2026-02-10'),
(2,3,60.00,'2026-02-15'),
(3,3,80.00,'2026-03-05'),
(4,3,75.00,'2026-03-12'),
(1,3,85.00,'2026-03-20'),
(2,4,55.00,'2026-02-08'),
(3,4,60.00,'2026-02-18'),
(1,4,65.00,'2026-03-07'),
(4,4,50.00,'2026-03-15'),
(2,5,72.00,'2026-03-10')
");

// --- Challenges ---
$conn->query("INSERT INTO challenges (name, description, target_value, status) VALUES
('Complete Profile','Fill all profile fields',NULL,'Permanent'),
('Good Bio','Write a bio of at least 100 characters',NULL,'Permanent'),
('Take 2 Quizzes','Complete 2 quizzes this month',2,'Monthly'),
('Score 70% in 3 Quizzes','Achieve 70%+ in 3 quizzes this month',3,'Monthly')
");

// --- Rewards ---
$conn->query("INSERT INTO rewards (challenge_id, badge_image_path) VALUES
(1,'assets/pictures/complete_profile.png'),
(2,'assets/pictures/good_bio.png'),
(3,'assets/pictures/two_quizzes.png'),
(4,'assets/pictures/three_quizzes_70.png')
");

// --- PlayerChallengesReward ---
$conn->query("INSERT INTO player_challenges_reward (player_profile_id, challenge_id, progress_value, date_completed, expiry_date, challenge_status, reward_status) VALUES
(3,1,NULL,'2026-01-05',NULL,'Completed','Active'),
(3,2,NULL,'2026-01-05',NULL,'Completed','Active'),
(3,3,1,NULL,'2026-03-31','Pending','Active'),
(3,4,2,NULL,'2026-03-31','Pending','Active'),
(4,3,2,'2026-03-15','2026-03-31','Completed','Active'),
(5,4,1,NULL,'2026-03-31','Pending','Active')
");

$conn->query("INSERT INTO support_requests (name, email, subject, message) VALUES
('Ali Mohammed', 'ali.Moha@example.com', 'Login Issue', 'I am unable to log in with my credentials.'),
('Sara Ahmed', 'sara.ahmed@example.com', 'Payment Query', 'My payment did not reflect in the dashboard.'),
('Abdulaziz Hussain', 'Aziz.hussain@example.com', 'Session Booking', 'I want to book a Rocket League session.'),
('Fatima Faisal', 'fatima.faisal@example.com', 'Profile Update', 'Can you help me update my bio information?'),
('Yasser Fahad', 'yasser.fahad@example.com', 'Bug Report', 'Leaderboard is not showing correctly.'),
('Ayesha Malik', 'ayesha.malik@example.com', 'Feature Request', 'Please add badminton coaching option.'),
('Turki Abdullah', 'turki.abdullah@example.com', 'General Inquiry', 'How do I become a verified coach?')");


echo "Data inserted successfully.<br>";
$conn->close();
?>

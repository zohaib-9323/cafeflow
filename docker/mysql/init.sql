-- Separate database for the automated test suite so tests never touch dev data.
CREATE DATABASE IF NOT EXISTS cafeflow_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON cafeflow_testing.* TO 'cafeflow'@'%';

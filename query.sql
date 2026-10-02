CREATE DATABASE IF NOT EXISTS student_system;
USE student_system;
--user page--
CREATE TABLE IF NOT EXISTS Login (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(30) NOT NULL,
    Password VARCHAR(10) NOT NULL
);
--student page--

# e-learning-website
We are Going to build a Best e-learning platform


# Project README

**Language:** English

## Overview

This README file explains your complete e-learning / course platform project. It includes project overview, folder structure, installation steps, Git workflow, database schema, and setup instructions. You can modify any section according to your actual project files.

---

## Project Description

This is a web‑based learning platform where users can browse courses, enroll, manage profiles, and view content. Instructors/admins can create and manage courses.
**Tech Stack:** PHP (backend), MySQL database, HTML/CSS/JS (frontend).

---

## Folder Structure (Suggested)

```
/ (project-root)
├─ index.php
├─ login.php
├─ register.php
├─ courses.php
├─ course.php
├─ Backend/
│  ├─ db_connect.php
│  ├─ auth.php
│  └─ ...
├─ assets/
│  ├─ css/
│  ├─ js/
│  └─ images/
├─ uploads/
└─ README.md
```

---

## Requirements

* PHP 7.4+ (recommended PHP 8+)
* MySQL / MariaDB
* Apache or Nginx
* Composer (optional)

---

## Local Setup Instructions

1. Clone the repository:

```bash
git clone <your-repo-url>.git
cd <repo-folder>
```

2. Create your database and update `.env` or your config file.
3. Import the SQL tables (given below).
4. Start the local server:

```bash
php -S localhost:8000
```

---

## Example Environment Config (.env)

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=elearning_db
DB_USER=root
DB_PASS=your_password
APP_URL=http://localhost:8000
UPLOAD_DIR=uploads/
```

If you don’t use `.env`, place these values inside `db_connect.php` safely.

---

## Git Workflow

```bash
# Create new feature branch
git checkout -b feature/<feature-name>

# Stage changes
git add .

# Commit
git commit -m "feat: add new feature"

# Push
git push origin feature/<feature-name>
```

---

# Database Schema (Actual Database You Provided)

Below is the exact SQL structure based on your real tables from the screenshots.

---

## 1. users

```sql
CREATE TABLE users (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100),
  email VARCHAR(100),
  avatar VARCHAR(255),
  password VARCHAR(255),
  updated_at DATETIME,
  reg_date TIMESTAMP,
  role ENUM('student','admin','instructor') DEFAULT 'student',
  google_id VARCHAR(255),
  facebook_id VARCHAR(255),
  bio TEXT,
  phone VARCHAR(20),
  social_links TEXT
);
```

---

## 2. courses

```sql
CREATE TABLE courses (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255),
  thumbnail VARCHAR(255),
  instructor VARCHAR(150),
  description TEXT,
  price DECIMAL(10,2),
  image VARCHAR(255),
  video_url VARCHAR(255),
  created_at TIMESTAMP,
  category VARCHAR(100)
);
```

---

## 3. enrollments

```sql
CREATE TABLE enrollments (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11),
  course_id INT(11),
  enrolled_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
```

---

## 4. wishlist

```sql
CREATE TABLE wishlist (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11),
  course_id INT(11),
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
```

---

## Combined SQL Script

```sql
CREATE DATABASE IF NOT EXISTS eduflect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduflect;
-- Paste all CREATE TABLE queries here
```

---

## Contact

If you want to add, remove, or modify anything in this README, just tell me!

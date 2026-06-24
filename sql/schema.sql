-- Chapter 12 schema (builds on Chapter 11)
-- Run this file ONCE against an existing Ch11 database, OR use the full
-- fresh-install block below.

-- ─── Option A: Fresh install ─────────────────────────────────────────────────


DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('member','admin') NOT NULL DEFAULT 'member',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- password = "password" (bcrypt)
INSERT INTO users (name, email, password_hash, role) VALUES
  ('Demo Admin',  'admin@books.test',  '$2y$12$VOeFe18jg8gJ1t5WhZlSFeOYjtGgcUYs4kcFGka.fb7mBrMyFnE22', 'admin'),
  ('Demo Member', 'member@books.test', '$2y$12$VOeFe18jg8gJ1t5WhZlSFeOYjtGgcUYs4kcFGka.fb7mBrMyFnE22', 'member');

CREATE TABLE books (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(200) NOT NULL,
  author     VARCHAR(150) NOT NULL,
  year       SMALLINT     NOT NULL,
  genre      VARCHAR(80)  NOT NULL DEFAULT 'Uncategorised',
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO books (title, author, year, genre, created_by) VALUES
  ('Clean Code',           'Robert C. Martin', 2008, 'Software Engineering', 1),
  ('Eloquent JavaScript',  'Marijn Haverbeke', 2018, 'Programming',          2),
  ('Vue.js 3 By Example',  'John Au-Yeung',    2021, 'Web Development',      1);

CREATE TABLE IF NOT EXISTS audit_log (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actor_id    INT NULL,
  action      VARCHAR(50)  NOT NULL,
  target      VARCHAR(80)  NULL,
  ip_address  VARCHAR(45)  NULL,
  detail      VARCHAR(500) NULL,
  INDEX idx_action (action),
  INDEX idx_actor  (actor_id)
) ENGINE=InnoDB;

-- ─── Option B: Upgrade existing Ch11 DB (run manually in HeidiSQL) ───────────
-- ALTER TABLE books
--   ADD COLUMN created_by INT NULL AFTER genre,
--   ADD CONSTRAINT fk_books_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
-- UPDATE books SET created_by = 1 WHERE id IN (1, 3);
-- UPDATE books SET created_by = 2 WHERE id = 2;
-- CREATE TABLE IF NOT EXISTS audit_log ( ... );  -- see above

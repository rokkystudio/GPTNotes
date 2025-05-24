-- Создание базы данных
CREATE DATABASE IF NOT EXISTS gptnotes DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gptnotes;

-- Основная таблица заметок
CREATE TABLE notes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- простой ключ
    title VARCHAR(255) NOT NULL,                          -- заголовок заметки
    text TEXT,                                            -- содержимое заметки
    tags TEXT,                                            -- строка с тегами (через запятую)
    date DATETIME DEFAULT CURRENT_TIMESTAMP               -- дата создания/обновления
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Временный кэш команд
CREATE TABLE cache (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- простой ключ
    title VARCHAR(255) NOT NULL,                          -- заголовок заметки
    cmd VARCHAR(255) NOT NULL,                            -- отложенная команда
    text TEXT                                             -- содержимое заметки
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
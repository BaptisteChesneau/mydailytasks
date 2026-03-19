-- ============================================================
-- myDailyTasks - Fichier d'initialisation de la base de données
-- Crée la BDD "timetracking" et la table "tasks" si elles
-- n'existent pas déjà.
-- ============================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `timetracking`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données
USE `timetracking`;

-- Création de la table des tâches
CREATE TABLE IF NOT EXISTS `tasks` (
    `task_id`   INT UNSIGNED    NOT NULL AUTO_INCREMENT COMMENT 'Identifiant unique de la tâche',
    `name`      VARCHAR(255)    NOT NULL                COMMENT 'Nom / intitulé de la tâche',
    `duration`  SMALLINT UNSIGNED NOT NULL              COMMENT 'Durée de la tâche en minutes (multiple de 10)',
    `date`      DATE            NOT NULL                COMMENT 'Date à laquelle la tâche a été réalisée',
    PRIMARY KEY (`task_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Table principale des tâches du time tracker';
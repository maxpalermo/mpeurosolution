-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Nov 07, 2024 alle 08:01
-- Versione del server: 10.1.48-MariaDB
-- Versione PHP: 8.3.11
SET
    FOREIGN_KEY_CHECKS = 0;

SET
    SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
    time_zone = "+00:00";

--
-- Database: `ps_dalavoro_16`
--
-- --------------------------------------------------------
--
-- Struttura della tabella `[[PREFIX]]customer_eurosolution`
--
DROP TABLE IF EXISTS `[[PREFIX]]customer_eurosolution`;

CREATE TABLE IF NOT EXISTS `[[PREFIX]]customer_eurosolution` (
    `id_customer` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_eurosolution` int(11) DEFAULT NULL,
    `id_employee` int(11) UNSIGNED NOT NULL DEFAULT '0',
    `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_customer`),
    UNIQUE KEY `idx_unique_id` (`id_customer`, `id_eurosolution`),
    KEY `idx_id_eurosolution` (`id_eurosolution`),
    KEY `idx_id_employee` (`id_employee`)
) ENGINE = [[ENGINE_TYPE]] AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
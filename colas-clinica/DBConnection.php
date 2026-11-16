<?php

// Modifica estas líneas en DBConnection.php
if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'db')) {
    mkdir(__DIR__ . DIRECTORY_SEPARATOR . 'db', 0777, true);
}

if (!defined('db_file')) {
    define('db_file', __DIR__ . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'colas-clinica.db');
}
/** Define DB File Path */
if (!defined('tZone')) define('tZone', "America/Bogota");
if (!defined('dZone')) define('dZone', ini_get('date.timezone'));

/** DB Connection Class */
class DBConnection extends SQLite3
{
    protected $db;
    function __construct()
    {
        /** Opening Database */
        $this->open(db_file);
        $this->exec("PRAGMA foreign_keys = ON;");

        /** Create Tables */
        $this->exec("CREATE TABLE IF NOT EXISTS `user_list` (
        `user_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        `fullname` TEXT NOT NULL,
        `username` TEXT NOT NULL,
        `password` TEXT NOT NULL,
        `type` TINYINT(1) NOT NULL Default 0,
        `status` TINYINT(1) NOT NULL Default 0,
        `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

        $this->exec("CREATE TABLE IF NOT EXISTS `patient_list` (
        `patient_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
        `queue_no` TEXT NOT NULL,
        `fullname` TEXT NOT NULL,
        `address` TEXT NOT NULL,
        `contact` TEXT NOT NULL,
        `age` INTEGER NOT NULL,
        `weight` FLOAT NOT NULL Default 0,
        `bp_rate` TEXT NULL,
        `status` TINYINT(2) NOT NULL Default 0,
        `notify` TINYINT(2) NOT NULL Default 1,
        `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

        $this->exec("INSERT OR IGNORE INTO `user_list` VALUES (1, 'Administrator', 'admin', '$2y$10\$Aj/jjNbcT1vNZrp.9ELpheF9rgjP9RInWb8RSuTGAKcoKJE26HCb6', 1, 1, CURRENT_TIMESTAMP)");
    }
    function __destruct()
    {
        $this->close();
    }
}

$conn = new DBConnection();

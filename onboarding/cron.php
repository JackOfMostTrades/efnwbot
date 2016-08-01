<?php
  require_once('../config.php');
  require_once('../common.inc.php');
  require_once('discord_sync.inc.php');

  if (defined('STDIN')) {
    define('DEBUG', 1);
  }

  $db = new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATABASE.';charset=utf8', SQL_USER_RO, SQL_PASSWD_RO);
  synchronize_all_user_roles($db);


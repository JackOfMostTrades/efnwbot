#!/usr/bin/env php
<?php
  require_once(__DIR__ . '/vendor/autoload.php');

  $bot = new EverfreeNorthwest\Discord\BotMain;
  $bot->run();

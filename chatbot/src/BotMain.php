<?php

namespace EverfreeNorthwest\Discord;

require_once(__DIR__ . '/../../onboarding/config.php');

use Discord\Discord;
use Discord\WebSockets\WebSocket;
use EverfreeNorthwest\Discord\Command\Commands;

class BotMain {

  private $commands;

  public function __construct() {
    $this->commands = new Commands();
  }

  private function log_command($message) {
    echo
      "User " . $message->author->username . " (" . $message->author->id . ")"
      . " sent " . trim($message->content)
      . " in #" . $message->channel->name . " (" . $message->channel->id . ").\n";
  }

  private function runOnce($discord) {
    $initTimer = $discord->loop->addTimer(15, function() use ($discord) {
      echo "Timing out initial connection.".PHP_EOL;
      $discord->loop->stop();
    });

    $discord->on('ready', function($discord) use ($initTimer) {
      echo "Bot is ready!".PHP_EOL;
      $discord->loop->cancelTimer($initTimer);
    });
    $discord->on('close', function($discord) {
      echo "Bot disconnected.".PHP_EOL;
      $discord->loop->stop();
    });
    $discord->on('reconnecting', function() {
      echo "Bot reconnecting.".PHP_EOL;
    });
    $discord->on('reconnected', function() {
      echo "Bot reconnected.".PHP_EOL;
    });
    $discord->on('ws-reconnect-max', function($discord) {
      echo "Max reconnect attempts reached. Killing loop.".PHP_EOL;
      $discord->loop->stop();
    });
    $discord->on('error', function($error) {
      echo "Error: $error".PHP_EOL;
    });

    // We will listen for messages
    $discord->on('message', function ($message, $discord) {
      try {
        if ($message->content[0] === "!") {
          $cmd = NULL;
          $sppos = strpos($message->content, " ");
          if ($sppos === FALSE) {
            $cmd = substr($message->content, 1);
          } else {
            $cmd = substr($message->content, 1, $sppos-1);
          }
          $cmd = $this->commands->getCommand($cmd);
          if ($cmd != NULL) {
            $this->log_command($message);
            $cmd->execute($message);
          }
        }
      } catch (Exception $e) {
        echo $e->getTraceAsString();
      }
    });

    $discord->run();
  }

  public function run() {
    $discord = new Discord([
      'token' => BOT_TOKEN
    ]);
    while (true) {
      echo "Bot starting...".PHP_EOL;
      try {
        $this->runOnce($discord);
      } catch (Exception $e) {
        echo $e->getTraceAsString();
      }
      echo "Bot stopped! Going to restart.".PHP_EOL;
    }
  }
}


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
    $ws      = new WebSocket($discord);
    $initTimer = $ws->loop->addTimer(15, function() use ($ws) {
      echo "Timing out initial connection.".PHP_EOL;
      $ws->loop->stop();
    });

    $ws->on('ready', function($discord) use ($ws, $initTimer) {
      echo "Bot is ready!".PHP_EOL;
      $ws->loop->cancelTimer($initTimer);
    });
    $ws->on('close', function() use ($ws) {
      echo "Bot disconnected.".PHP_EOL;
      $ws->loop->stop();
    });
    $ws->on('reconnecting', function() {
      echo "Bot reconnecting.".PHP_EOL;
    });
    $ws->on('reconnected', function() {
      echo "Bot reconnected.".PHP_EOL;
    });
    $ws->on('ws-reconnect-max', function() use ($ws) {
      echo "Max reconnect attempts reached. Killing loop.".PHP_EOL;
      $ws->loop->stop();
    });
    $ws->on('error', function($error) {
      echo "Error: $error".PHP_EOL;
    });

    // We will listen for messages
    $ws->on('message', function ($message, $discord) {
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

    $ws->run();
  }

  public function run() {
    $discord = new Discord(BOT_TOKEN);
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


<?php

namespace EverfreeNorthwest\Discord;

require_once(__DIR__ . '/../../onboarding/config.php');

use Discord\Discord;
use Discord\WebSockets\WebSocket;
use EverfreeNorthwest\Discord\Command\Commands;

class BotMain {

  private function log_command($message) {
    echo
      "User " . $message->author->username . " (" . $message->author->id . ")"
      . " sent " . trim($message->content)
      . " in #" . $message->channel->name . " (" . $message->channel->id . ").\n";
  }

  function run() {
    $discord = new Discord(BOT_TOKEN);
    $ws      = new WebSocket($discord);
    $commands = new Commands();

    $ws->on('ready', function ($discord) use ($ws, $commands) {
      echo "Bot is ready!".PHP_EOL;

      // We will listen for messages
      $ws->on('message', function ($message, $discord) use ($commands) {
        try {
          if ($message->content[0] === "!") {
            $cmd = NULL;
            $sppos = strpos($message->content, " ");
            if ($sppos === FALSE) {
              $cmd = substr($message->content, 1);
            } else {
              $cmd = substr($message->content, 1, $sppos-1);
            }
            $cmd = $commands->getCommand($cmd);
            if ($cmd != NULL) {
              $this->log_command($message);
              $cmd->execute($message);
            }
          }
        } catch (Exception $e) {
          echo $e->getTraceAsString();
        }
      });
    });

    $ws->run();
  }
}


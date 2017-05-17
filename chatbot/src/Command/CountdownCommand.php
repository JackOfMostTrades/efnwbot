<?php
  namespace EverfreeNorthwest\Discord\Command;

  class CountdownCommand implements Command {
    public function getCommand() {
      return "countdown";
    }
    public function getDescription() {
      return "Gets the number of days until Everfree Northwest.";
    }
    public function execute($message) {
      $now = time();
      $start = strtotime('2018-05-18');
      $days = ceil(($start-$now)/(60*60*24));
      $message->channel->sendMessage("There are $days days until Everfree Northwest 2018!");
    }
  }

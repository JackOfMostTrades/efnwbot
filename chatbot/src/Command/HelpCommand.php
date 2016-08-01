<?php
  namespace EverfreeNorthwest\Discord\Command;

  class HelpCommand implements Command {

    private $commands;
    public function __construct(Commands $commands) {
      $this->commands = $commands;
    }

    public function getCommand() {
      return "help";
    }
    public function getDescription() {
      return "Learn about all the commands available from this bot.";
    }
    public function execute($message) {
      $cmds = $this->commands->getAllCommands();
      usort($cmds, function($a, $b) { return strcmp($a->getCommand(), $b->getCommand()); });

      $msg = array("Here are all the commands I know about!");
      foreach ($cmds as $cmd) {
        array_push($msg, "!" . $cmd->getCommand() . " " . $cmd->getDescription());
      }
      $message->channel->sendMessage(implode("\n", $msg));
    }
  }

<?php
  namespace EverfreeNorthwest\Discord\Command;

  class Commands {
    private $commands;

    public function __construct() {
      $this->commands = array();
      $this->addCommand(new CountdownCommand());
      $this->addCommand(new PonyCommand());
      $this->addCommand(new HelpCommand($this));
    }

    private function addCommand($cmd) {
      $this->commands[$cmd->getCommand()] = $cmd;
    }

    public function getCommand($cmd) {
      if (array_key_exists($cmd, $this->commands)) {
        return $this->commands[$cmd];
      }
      return NULL;
    }
    public function getAllCommands() {
      return array_values($this->commands);
    }
  }


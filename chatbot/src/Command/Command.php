<?php
  namespace EverfreeNorthwest\Discord\Command;

  interface Command {
    public function getCommand();
    public function getDescription();
    public function execute($message);
  }

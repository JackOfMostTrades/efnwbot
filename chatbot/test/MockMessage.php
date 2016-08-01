<?php
  class MockMessage {
    public $content;
    public $channel;
    public $outgoing;
    
    public function __construct($content) {
      $this->content = $content;
      $this->channel = $this;
      $this->outgoing = array();
    }

    public function sendMessage($msg) {
      array_push($this->outgoing, $msg);
    }
  }


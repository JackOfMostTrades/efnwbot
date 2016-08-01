<?php

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../MockMessage.php');

use EverfreeNorthwest\Discord\Command\PonyCommand;

class PonyCommandTest extends PHPUnit_Framework_TestCase
{
    public function testPony()
    {
        $cmd = new PonyCommand();
        $msg = new MockMessage("!pony happy");
        $cmd->execute($msg);
        $this->assertEquals(1, count($msg->outgoing));
        $this->assertEquals(1, preg_match('/^https:\/\//', $msg->outgoing[0]));
    }

    public function testUnknownTag()
    {
        $cmd = new PonyCommand();
        $msg = new MockMessage("!pony satohueshpisrchaseontuhaeo");
        $cmd->execute($msg);
        $this->assertEquals(1, count($msg->outgoing));
        $this->assertEquals("I have no idea what you're looking for...\nhttps://derpicdn.net/img/2016/4/5/1125252/medium.png", $msg->outgoing[0]);
    }
}
?>

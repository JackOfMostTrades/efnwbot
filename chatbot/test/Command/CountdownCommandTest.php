<?php

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../MockMessage.php');

use EverfreeNorthwest\Discord\Command\CountdownCommand;

class CountdownCommandTest extends PHPUnit_Framework_TestCase
{
    public function testCountdown()
    {
        $cmd = new CountdownCommand();
        $msg = new MockMessage("!countdown");
        $cmd->execute($msg);
        $this->assertEquals(1, count($msg->outgoing));
        $this->assertEquals(1, preg_match('/^There are [0-9]+ days until Everfree Northwest 2017!$/', $msg->outgoing[0]));
    }
}
?>

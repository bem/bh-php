<?php

require "vendor/autoload.php";

use BEM\BH;
use BEM\Context;

abstract class PHPUnit_Framework_BHTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @before
     */
    public function setup()
    {
        parent::setup();
        $this->bh = new BH();
        $this->ctx = new Context($this->bh);
    }
}

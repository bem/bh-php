<?php

use BEM\BH;

class bh_additionals extends PHPUnit_Framework_BHTestCase
{
    public function test_itShouldReturnStringUntouched()
    {
        $this->assertEquals('1', $this->bh->apply(1));
        $this->assertEquals('-1', $this->bh->apply(-1));
        $this->assertEquals('1', $this->bh->apply(true));
    }
}

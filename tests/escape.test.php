<?php

use BEM\BH;

class escapesTest extends PHPUnit_Framework_TestCase {

    function testXmlEscape () {
        $this->assertEquals(BH::xmlEscape('<b>&</b>'), '&lt;b&gt;&amp;&lt;/b&gt;');
    }

    function testAttrEscape () {
        $this->assertEquals(BH::attrEscape('<b id="a">&</b>'), '&lt;b id=&quot;a&quot;&gt;&amp;&lt;/b&gt;');
    }
}

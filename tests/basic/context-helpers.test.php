<?php

class context_helpersTest extends PHPUnit_Framework_BHTestCase
{
    public function test_isArray()
    {
        $falsyArr = [];
        $falsyArr[1] = '';
        $falsyObj = new \ArrayObject();
        $falsyObj[1] = '';

        $this->assertFalse($this->ctx->isArray('1'));
        $this->assertTrue($this->ctx->isArray([]));
        $this->assertTrue($this->ctx->isArray(new \ArrayObject()));
        $this->assertTrue($this->ctx->isArray([null]));
        $this->assertTrue($this->ctx->isArray(new \ArrayObject([1])));
        $this->assertFalse($this->ctx->isArray($falsyArr));
        $this->assertFalse($this->ctx->isArray($falsyObj));
    }

    public function test_phpize()
    {
        $this->assertInstanceOf('\\BEM\\JsonCollection', $this->ctx->phpize([]));
        $this->assertInstanceOf('\\BEM\\Json', $this->ctx->phpize(['block' => 'name']));
    }
}

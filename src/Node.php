<?php

namespace BEM;

class Node {

    public $json;
    public $arr;
    public $index;
    public $blockName;
    public $blockMods;
    public $parentNode;
    public $position;

    /**
     * @param array $node lib
     */
    function __construct ($node) {
        foreach ($node as $k => &$v) {
            if (!property_exists($this, $k)) {
                throw new \Exception('Unknown key ' . $k);
            }
            $this->$k = $v;
        }
    }

}

<?php

namespace BEM;

require_once "helpers.php";

class JsonCollection extends \ArrayObject implements \JsonSerializable {

    public function __construct($input = []) {
        parent::__construct($input, \ArrayObject::ARRAY_AS_PROPS);
        $this->_updateIndexes();
    }

    // JsonSerializable
    public function jsonSerialize() {
        echo 'serializing' . PHP_EOL;
        var_dump($this->getArrayCopy());
        return $this->getArrayCopy();
    }

    public function append ($obj) {
        if (isList($obj) || $obj instanceof \Iterator) {
            // rollup lists
            foreach ($obj as $item) {
                // filter empty arrays inside
                if (!(is_array($item) && empty($item))) {
                    self::append($item);
                }
            }
        } else {
            parent::append(JsonCollection::normalizeItem($obj));
        }
    }

    public $_listLength = 0;

    protected function _updateIndexes () {
        $j = 0;
        foreach ($this as $item) {
            if ($item instanceof Json) {
                $j++;
            }
        }
        $this->_listLength = $j;
    }

    /**
     * Normalize bemJson node or list or tree
     * @param  array|object $bemJson
     * @return JsonCollection
     */
    public static function normalize ($bemJson) {
        switch (true) {
            // casual list
            case isList($bemJson);
                $bemJson = static::flattenList($bemJson);
            case $bemJson instanceof \Iterator:
                $content = [];
                foreach ($bemJson as $node) {
                    $content[] = static::normalizeItem($node);
                }
                $ret = $content;
                break;

            // casual bemJson node
            case is_array($bemJson):
                $ret = [static::normalizeItem($bemJson)];
                break;

            // instance of JsonCollection
            case is_object($bemJson) && ($bemJson instanceof self):
                return $bemJson;

            // instance of Json
            case is_object($bemJson) && ($bemJson instanceof Json):
                $ret = [$bemJson];
                break;

            // custom object (not array)
            case is_object($bemJson):
                $ret = [new Json($bemJson)];
                break;

            case is_scalar($bemJson):
                $ret = [$bemJson];
                break;

            default:
                throw new \InvalidArgumentException('Passed variable is not an array or object or string');
        }

        $res = new static($ret);

        return $res;
    }

    /**
     * @param mixed $node
     * @return string|number|Json
     */
    public static function normalizeItem ($node) {
        if (is_scalar($node) || is_null($node)) {
            return $node;
        }
        if ($node instanceof Json) {
            return $node;
        }
        if (is_object($node)) {
            $node = (array)$node;
        }
        if (is_array($node) && empty($node)) {
            return null;
        }
        return new Json($node);
    }

    /**
     * Brings items of inner simple arrays to root level if exists
     * @param  array $a
     * @return array
     */
    public static function flattenList ($a) {
        if (!isList($a)) {
            return $a;
        }

        do {
            $flatten = false;
            for ($i = 0, $l = sizeof($a); $i < $l; $i++) {
                if (isList($a[$i])) {
                    $flatten = true;
                }
            }
            if ($flatten) {
                $res = [];
                for ($i = 0; $i < $l; $i++) {
                    if (!isList($a[$i])) {
                        // filter empty arrays inside
                        if (!(is_array($a[$i]) && empty($a[$i]))) {
                            $res[] = $a[$i];
                        }
                    } else {
                        $res = array_merge($res, (array)$a[$i]);
                    }
                }
                $a = $res;
            }
        } while ($flatten);

        return $a;
    }

}

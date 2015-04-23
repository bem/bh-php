<?php

namespace BEM;

require_once "helpers.php";

class JsonCollection extends \ArrayObject {

    public function __construct($input = []) {
        parent::__construct($input);
        $this->_updateIndexes();
    }

    public function append ($obj) {
        if ($obj instanceof Json) {
            parent::append($obj);

        } elseif ($obj instanceof \Iterator || isList($obj)) {
            // rollup lists
            foreach ($obj as $item) {
                // filter empty arrays inside
                if (!(is_array($item) && empty($item))) {
                    static::append($item);
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
        $isArr = is_array($bemJson);

        switch (true) {
            // casual bemJson node (assoc array)
            case $isArr && !key_exists('0', $bemJson):
                $ret = empty($bemJson) ? [] : [new Json($bemJson)];
                break;

            case is_scalar($bemJson) || $bemJson === null:
                $ret = [$bemJson];
                break;

            // instance of JsonCollection
            case $bemJson instanceof self:
                return $bemJson;

            // casual list
            case $isArr && isList($bemJson);
                $bemJson = static::flattenList($bemJson);
            case $bemJson instanceof \Iterator:
                $content = [];
                foreach ($bemJson as $node) {
                    $content[] = static::normalizeItem($node);
                }
                $ret = $content;
                break;

            // instance of Json
            case $bemJson instanceof Json:
                $ret = [$bemJson];
                break;

            default:
                throw new \InvalidArgumentException('Passed variable is not an array or object or string');
        }

        return new static($ret);
    }

    /**
     * @param mixed $node
     * @return string|number|Json
     */
    public static function normalizeItem ($node) {
        if (null === $node || is_scalar($node)) {
            return $node;
        }
        if ($node instanceof Json) {
            return $node;
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
                    break;
                }
            }
            if (!$flatten) {
                break;
            }
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
        } while ($flatten);

        return $a;
    }

}

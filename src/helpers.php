<?php

namespace BEM;

if (!defined('__undefined')) {
    define('__undefined', '\0undefined');
}

function weakjson_decode($s) {
    static $parser;
    $parser = $parser ?: new WeakJsonParser();
    return $parser->parse($s);
}

/**
 * Checks an array to be a simple array or arrayobject
 * @param boolean $array
 */
function isList ($a) {
    return is_array($a) && (isset($a[0]) || empty($a) || array_key_exists(0, $a))
        || $a instanceof \ArrayObject && (isset($a[0]) || !count($a) || array_key_exists(0, $a));
}

function d() {
    echo join(' ', array_map(function ($d) {
        return _prettifyObj($d);
    }, func_get_args())) . "\n";
}

function _prettifyObj($d) {
    static $z = 0;
    switch (true) {
        case is_null($d):     return 'null';
        case is_bool($d):     return $d ? 'true' : 'false';
        case is_numeric($d):  return $d;
        case is_string($d):
            return empty($d) || is_numeric($d) || $z !== 0 ||
                $d !== addslashes($d) || strpos($d, ' ') !== false ? '"' . addslashes($d) . '"' : $d;
        case is_array($d) && empty($d):
            return '[]';
        case isList($d):
            $z++;
            $out = '[ ' . join(",\1", array_map(function ($i) use (&$z) {
                $z++;
                $out = _prettifyObj($i);
                $z--;
                return $out;
            }, $d)) . ' ]';
            $out = str_replace("\1", strlen($out) > 80 ? "\n" . str_repeat('  ', $z + 1) : ' ', $out);
            $z--;
            return $out;
        case is_array($d):
            $out = [];
            foreach ($d as $k => $v) {
                $z++;
                $out[] = _prettifyObj($k) . ': ' . _prettifyObj($v);
                $z--;
            }
            $out = "{ " . join(",\1", $out) . ' }';
            $out = str_replace("\1", strlen($out) > 80 ? "\n" . str_repeat('  ', $z + 1) : ' ', $out);
            return $out;
        case is_object($d): //, 'stdClass') || is_a($d, '\\BEM\\Context'):
            $out = [];
            foreach ($d as $k => $v) {
                $z++;
                $out[] = _prettifyObj($k) . ': ' . _prettifyObj($v);
                $z--;
            }
            $cls = get_class($d);
            $out = "{ " . ($cls === 'stdClass' ? '' : '/*' . $cls . "*/\1") . join(",\1", $out) . ' }';
            $out = str_replace("\1", strlen($out) > 80 ? "\n" . str_repeat('  ', $z + 1) : ' ', $out);
            return $out;
        case is_object($d):
            return str_replace("\n", "\n" . str_repeat("  ", $z), json_encode($d, JSON_PRETTY_PRINT));
        default:
            return var_export($d, 1);
    }
    return 'xxx';
}

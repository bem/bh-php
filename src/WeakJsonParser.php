<?php

namespace BEM;

/**
 * json decoder & encoder
 *
 * wrapper for json_encode/decode functions.
 * have a json parser for non-standart js
 *
 * @version     0.1
 * @author  alex 'alex_ez' yaroshevich <qfox@ya.ru>
 * @date    20 oct 2008
 * @todo    optimization, change parsing algorithms
 *
 */

class WeakJsonParser {

    /**
     * @param $a
     * @return string
     */
    public static function encode($a) {
        if (is_callable('json_encode')) {
            return json_encode($a);
        }

        switch (true) {
            case is_null($a):
                return 'null';
            default:
                $a = (string)($a);
            case is_string($a):
                return '"'.addslashes($a).'"';
            case ctype_digit($a):
                return (string)$a;
            case is_numeric($a):
                return (string)floatval($a);
            case is_bool($a):
                return $a ? 'true' : 'false';
            case is_resource($a):
                return '\"resource\"';
            case is_array($a) && isset($a[0]) && isset($a[count($a)-1]): // listy
                $r = array();
                foreach ($a as $v) {
                    $r[] = static::encode($v);
                }
                return '['.join(',', $r).']';
            case is_array($a) || is_object($a):
                $r = array();
                foreach ($a as $k => $v) {
                    $r[] = static::encode($k).':'.static::encode($v);
                }
                return '{'.join(',', $r).'}';
        }
    }

    /**
     * @test ["{qwe:'asd'}"] >>> {"qwe":"asd"}
     * @param string $s
     * @return array|string|float|int|null
     */
    public static function decode ($s) {
        if (!is_string($s)) {
            return null;
        }

        $s = trim($s);
        if (is_callable('json_decode')) {
            $_r = json_decode($s, 1);
            if (!is_null($_r)) {
                return $_r;
            }
        }

        if (!is_null($_r = self::parseScalar($s))) {
            return $_r;
        }

        $ch = mb_substr($s, 0, 1);
        switch (true) {
            case $s === 'null' || $s === 'undefined':
                $_r = null;
                break;
            case $ch === '[':
                $_sub = mb_substr($s, 0);
                $_end = self::findEnd($_sub);
                $_sub = mb_substr($_sub, 1, $_end-2);
                $_r = self::parseArray($_sub);
                break;
            case $ch === '{':
                $_sub = mb_substr($s, 0);
                $_end = self::findEnd($_sub);
                $_sub = mb_substr($_sub, 1, $_end-2);
                $_r = self::parseObject(trim($_sub));
                break;
            default:
                $_r = $s;
        }
        // end of parser

        return $_r;
    }

    /**
     *
     * @param string $s
     * @return string|float|int|null
     */
    static function parseScalar($s) {
        $s = trim($s);
        $r = null;
        switch (true) {
            case $s === 'null' || $s === 'undefined':
                break;
            case ctype_digit($s):
                $r = intval($s);
                break;
            case is_numeric($s):
                $r = floatval($s);
                break;
            case $r = self::parseString($s):
                break;
            default:
        }
        return $r;
    }

    /**
     * @test [" 'asd' "] >>> "asd" --- single quotes
     * @test [" \"zxc\" "] >>> "zxc" --- double quotes
     * @test ["''"] >>> "" --- empty single quoted
     * @test ["\"\""] >>> "" --- empty double quoted
     * @test ["'\\\"\\''"] >>> "\"'" --- single quoted escaped double and single quotes
     * @test ["_a1s_d"] >>> "_a1s_d" --- unquoted string "_a1s_d"
     * @param string $s
     * @return string|null
     */
    static function parseString($s) {
        $s = trim($s);
        $sl = mb_strlen($s);
        $r = null;
        // parse simple key string
        if ($s[0] === $s[$sl-1] && ($s[0] === '"' || $s[0] === "'")) {
            $r = stripslashes(mb_substr($s, 1, -1));
        } else {
            $t = (mb_strpos($s, '_') === false)? $s : strtr($s, '_', 'a');
            if (ctype_alpha($t[0]) && ctype_alnum($t)) {
                $r = $s;
            }
        }
        return $r;
    }

    static function parseObject($s) {
        $r = array();
        $s = trim($s);
        for ($l = mb_strlen($s), $i=0; $i < $l; $i++) {
            // skip delimiter and spaces
            $ch = mb_substr($s, $i, 1);
            if ($ch === ',') {
                is_null($k) ? $r[] = null : $r[$_k] = null;
                continue;
            }
            if (trim($ch) === '') {
                continue;
            }

            $_sub = mb_substr($s, $i);
            $_end = self::findEnd($_sub);
            $_k = self::parseString(mb_substr($s, $i, $_end));

            $i += $_end;

            while (mb_substr($s, $i - 1, 1) != ':' && $i < $l) {
                $i++;
            }

            $_sub = mb_substr($s, $i);
            $_end = self::findEnd($_sub);
            $_v = mb_substr($s, $i, $_end);
            $i += $_end;

            $r[$_k] = static::decode($_v);
            $_k = null;
        }
        return $r;
    }

    static function parseArray($s) {
        $r = array();
        $s = trim($s);
        for ($l = mb_strlen($s), $i = 0; $i < $l; $i++) {
            // skip delimiter and spaces
            $ch = mb_substr($s, $i, 1);
            if ($ch === ',') {
                $r[] = null;
                continue;
            }
            if (trim($ch) === '') {
                continue;
            }

            $_sub = mb_substr($s, $i);
            $_end = self::findEnd($_sub);

            $_v = mb_substr($s, $i, $_end);

            $r[] = static::decode(trim($_v));

            $i += $_end;
        }

        return $r;
    }

    /**
     * find end of block
     * @param string $s our string
     * @param bool $sqm skip quote blocks mode
     * @param string $sc stop character
     */
    static function findEnd($s, $sqm = false, $sc = null) {
        $_s = $s; $s = ltrim($s);
        $left = mb_strlen($_s) - mb_strlen($s);
        $fc = mb_substr($s, 0, 1);

        if (!mb_strlen($s)) {
            return $left;
        }

        // fetch closing character, if need
        $_fc2sc = array('"'=>'"', "'"=>"'", '{'=>'}', '['=>']', '('=>')', '<'=>'>');
        if (is_null($sc)) {
            $sc = isset($_fc2sc[$fc])? $_fc2sc[$fc] : null; // stop character
            $sqm = $sqm || ('"' === $sc || "'" === $sc);
            $sc || $sc = ",:";
        }

        $parenthesis = (isset($_fc2sc[$sc]) || in_array($sc, $_fc2sc));

        // parenthesis validation.
        $sq = ('"' === $fc || "'" === $fc) ? $fc : false;
        $cc = $fc;

        // pc - previous, cc - current character
        for ($l = mb_strlen($s), $j = 1, $p = 1; $p && $j < $l; $j++) {
            $pc = $cc;
            $cc = mb_substr($s, $j, 1);

            if (!$sqm && ('"' === $cc || "'" === $cc))  {
                $_nos = ($pc !== "\\"); // no open slash
                if (!$_nos) {
                    for ($_j = $j; $_j > 1; $_j--) {
                        if (mb_substr($s, $_j-1, 1) != "\\") {
                            break;
                        }
                        $_nos = ($j - $_j + 1) % 2;
                    }
                }
                if ($_nos && $sq === false) {
                    $sq = $cc;
                } elseif ($_nos && $sq == $cc) {
                    $sq = false;
                }

            } elseif ($sqm) {
                $_nos = ($pc !== "\\"); // no open backslash
                if (!$_nos) {
                    for ($_j = $j; $_j > 1; $_j--) {
                        if (mb_substr($s, $_j-1,1) != "\\") {
                            break;
                        }
                        $_nos = ($j - $_j + 1) % 2;
                    }
                }
                if (!$_nos) {
                    continue;
                }
            }

            if (!$parenthesis && $sq === false && mb_strpos($sc, $cc) !== false) {
                $p = 0;
            } elseif (!$sqm && $sq === false) {
                $p += ($cc === $sc) ? (-1) : (($cc === $fc) ? 1 : 0);
            } elseif ($sqm) {
                $p -= ($cc === $sc);
            }
            //echo "\ti: ".($sq?'-':'')."$j\tpp: $pp, p: $p, pc: $pc, cc: $cc,\tsq: $sq, cc=': ".('"' === $cc || "'" === $cc) .", pc!=\\: ".($pc != "\\")."\n";
        }

        if (!$parenthesis && mb_strpos($sc, $cc) !== false) {
            $j--;
        }

        // parenthesis parse error. errors with disclosed parenthesis
        if ($parenthesis && $p) {
            // throw new Exception ?
            trigger_error("disclosed parenthesis ".$fc."; s: ".$s."; pos: ".$j."\n");
        }

        return $j+$left;
    }
}

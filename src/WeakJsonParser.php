<?php

namespace BEM;

/**
 * json-like format reader
 */

class WeakJsonParser
{
    // const CONTEXT_ROOT = 0x01;
    // const CONTEXT_OBJECT = 0x02;
    // const CONTEXT_ARRAY = 0x03;

    // protected $ctx;
    // protected $ctxs;

    protected $s;
    protected $pos;
    protected $len;

    public function parse($s)
    {
        $this->s = $s;
        $this->pos = 0;
        $this->len = strlen($s);
        // $this->ctxs = [];

        $res = $this->parseValue();
        $this->skipWs();
        if ($this->pos < $this->len) {
            $this->err('Inconsistent content');
        }

        return $res;
    }

    protected function parseValue()
    {
        $res = null;

        $this->skipWs();
        if ($this->pos >= $this->len) {
            return $res;
        }

        $ch = $this->s[$this->pos];

        // signed
        if ($ch === '-' || $ch === '+') {
            $this->pos++;
            $res = $this->parseValue();
            if ($res === null) {
                $this->err('Invalid signed expression');
            }
            $res = ($ch === '-' ? -1 : 1) * $res;
        }

        // hexadigits
        elseif ($ch === '0' && $this->pos + 1 < $this->len && $this->s[$this->pos + 1] === 'x') {
            $res = $this->parseHexanumeric();
        }

        // digits
        elseif (ctype_digit($ch) || $ch === '.') {
            $res = $this->parseNumeric();
        }

        // quotes
        elseif ($ch === '"' || $ch === "'") {
            $res = $this->parseQuotedString();
        }

        // objects
        elseif ($ch === '{') {
            $res = [];
            // $this->ctx(self::CONTEXT_OBJECT);
            $start = $this->pos;
            $this->pos++;
            $this->skipWs();
            $ch = $this->ch();
            while ($ch !== '}' && $this->pos < $this->len) {
                $key = $this->parseKey();
                if ($this->skipColon() !== 1) {
                    $this->err('Syntax error, expect one `:`');
                }
                $value = $this->parseValue();
                $commas = $this->skipCommas();
                if ($commas > 1) {
                    $this->err('Syntax error, too many commas `,`');
                }
                //$this->d(compact('key', 'value', 'commas'));
                $res[$key] = $value;
                $ch = $this->ch(); //$this->s[$this->pos];
            }
            if ($ch !== '}') {
                $this->err('Unclosed brace `{`');
            }
            $this->pos++;
            // $this->ctx();
        } elseif ($ch === '}') {
            // if ($this->ctx !== self::CONTEXT_OBJECT) $this->err('Unpaired brace `}`');
            $this->err('Unexpected `}`');
        } elseif ($ch === '[') {
            // $this->ctx(self::CONTEXT_ARRAY);
            $start = $this->pos;
            $this->pos++;
            $this->skipWs();
            $ch = $this->ch();
            if ($ch !== ']' && $this->pos + 1 >= $this->len) {
                $this->err('Unclosed bracket `[`');
            }
            $res = [];
            $k = 0;
            while ($ch !== ']' && $this->pos < $this->len) {
                $res[$k] = $this->parseValue();
                $commas = $this->skipCommas();
                if ($commas > 1) {
                    foreach (range(1, $commas - 1) as $i) {
                        $res[$k + $i] = null;
                    }
                }
                $k += $commas;
                $ch = $this->ch();
            }
            if ($ch !== ']') {
                $this->err('Expected bracket `]`');
            }
            $this->pos++;
            // $this->ctx();
        } elseif ($ch === ']') {
            // if ($this->ctx() !== self::CONTEXT_ARRAY) $this->err('Unpaired bracket `]`');
            $this->err('Unexpected `]`');
        }
        // keywords
        elseif ($ch === 't') {
            $this->parseExactKey('true', 4);
            $res = true;
        } elseif ($ch === 'f') {
            $this->parseExactKey('false', 5);
            $res = false;
        } elseif ($ch === 'n') {
            $this->parseExactKey('null', 4);
            $res = null;
        } elseif ($ch === 'u') {
            $this->parseExactKey('undefined', 9);
            $res = null;
        }

        return $res;
    }

    protected function parseKey()
    {
        $res = null;
        $ch = $this->ch();
        if ($ch === '"' || $ch === "'") {
            $res = $this->parseQuotedString();
        } else {
            $res = $ch;
            while (ctype_alnum($ch = $this->nextCh()) || $ch === '_') {
                $res .= $ch;
            }
        }
        return $res;
    }

    protected function parseNumeric()
    {
        $i = $this->pos;
        $sp = false;
        while ($i < $this->len) {
            $ch = $this->s[$i];
            if (!ctype_digit($ch) xor (!$sp && $ch === '.')) {
                break;
            }
            $sp = $sp || $ch === '.';
            $i++;
        }
        $res = substr($this->s, $this->pos, $i - $this->pos);
        $this->pos = $i;
        return $sp ? (float)$res : (int)$res;
    }

    protected function parseHexanumeric()
    {
        $i = $this->pos + 2;
        while ($i < $this->len) {
            $ch = $this->s[$i];
            if (!ctype_xdigit($ch)) {
                break;
            }
            $i++;
        }
        $res = substr($this->s, $this->pos + 2, $i - $this->pos - 2);
        $this->pos = $i;
        return hexdec($res);
    }

    protected function parseQuotedString()
    {
        $q = $this->ch();
        $start = $this->pos;
        $ch = $this->nextCh();
        while ($this->pos < $this->len) {
            if ($ch === $q) {
                // done
                break;
            } elseif ($ch === '\\') {
                $this->pos++;
            }
            $ch = $this->nextCh();
        }
        if ($ch !== $q) {
            $this->err('Unpaired quote `' . $q . '`');
        }
        $res = substr($this->s, $start + 1, $this->pos - $start - 1);
        $res = stripslashes($res); // check it please
        $this->pos++;
        return $res;
    }

    protected function parseExactKey($s, $l = null)
    {
        $res = null;
        $l = $l ?: strlen($s);
        if (substr($this->s, $this->pos, $l) === $s) {
            $res = $s;
        }
        if ($this->pos + $l < $this->len) {
            $nch = $this->s[$this->pos + $l];
            if (ctype_alnum($nch) || $nch === '_') {
                $res = null;
            }
        }
        if ($res === null) {
            $this->err('Unexpected keyword');
        }
        $this->pos += $l;
        return $res;
    }

    protected function skipWs()
    {
        while ($this->pos < $this->len) {
            $ch = $this->s[$this->pos];
            if (!ctype_space($ch)) {
                break;
            }
            $this->pos++;
        }
    }

    protected function skipColon()
    {
        $res = 0;
        while ($this->pos < $this->len) {
            $ch = $this->s[$this->pos];
            if (!ctype_space($ch) && $ch !== ':') {
                break;
            }
            $res += $ch === ':';
            $this->pos++;
        }
        return $res;
    }

    protected function skipCommas()
    {
        $res = 0;
        while ($this->pos < $this->len) {
            $ch = $this->s[$this->pos];
            if (!ctype_space($ch) && $ch !== ',') {
                break;
            }
            $res += $ch === ',';
            $this->pos++;
        }
        return $res;
    }

    protected function ch()
    {
        return isset($this->s[$this->pos]) ? $this->s[$this->pos] : null;
    }

    protected function nextCh()
    {
        $this->pos++;
        if ($this->pos > $this->len) {
            $this->pos = $this->len; //$this->err('Syntax error at ' . $this->loc());
        }
        return $this->ch();
    }

    protected function d()
    {
        static $first = null;
        if (!$first) {
            $first = microtime(1);
        }
        $s = str_replace(array("\r", "\t", "\n", " "), '', $this->s);
        $out = [sprintf("%.3fms", (microtime(1) - $first)*1000), $s, $this->ch(), $this->pos, $this->len];
        call_user_func_array(__NAMESPACE__.'\\d', array_merge($out, func_get_args()));
    }

    /*
    protected function ctx ($ctx = null) {
        if ($ctx === null) {
            if (empty($this->ctxs)) {
                $this->err('Syntax error');
            }
            $this->ctx = array_pop($this->ctxs);
            return $this->ctx;
        }
        $this->ctx = $ctx;
        $this->ctxs[] = $ctx;
    }
    */

    protected function err($msg = null)
    {
        $msg = $msg ?: 'Silent error';
        throw new \Exception($msg . ' ' . $this->humanLoc());
    }

    protected function loc($pos = null)
    {
        return Location::build($this->s, $pos ?: $this->pos);
    }

    protected function humanLoc($pos = null)
    {
        $pos = $pos ?: $this->pos;
        $loc = $this->loc($pos);
        $nearlyStart = max(0, $loc->column - 15);
        $nearly = mb_substr($loc->str, $nearlyStart, 25);
        $pointerPos = $loc->column - $nearlyStart;
        $linearr = ($pointerPos > 1 ? str_repeat('-', $pointerPos - 1) : '') . '^';
        return 'at ' . $loc . "\n$nearly\n$linearr";
    }
}

class Location
{
    public $line = 1;
    public $column = 0;
    public $str = '';
    public function __construct($line, $column, $lineStr)
    {
        $this->line = $line;
        $this->column = $column;
        $this->str = $lineStr;
    }
    public function __toString()
    {
        return 'line ' . $this->line . ' column ' . $this->column;
    }

    public static function build($s, $pos)
    {
        $line = 1;
        $column = 0;
        $i = 0; // line start
        $len = strlen($s);
        while ($i < $len) {
            $j = strpos($s, "\n", $i);
            if ($j === false) {
                break;
            }
            if ($j >= $pos) {
                break;
            }
            $i = $j + 1;
            $line++;
        }
        $lineStr = $s;
        if ($j !== false) { // not last line
            $lineStr = substr($s, $i, $j - $i);
        }
        $column = mb_strlen(substr($lineStr, 0, $pos - $i + 1));
        return new Location($line, $column, $lineStr);
    }
}

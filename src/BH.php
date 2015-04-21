<?php

namespace BEM;

require_once "helpers.php";

class BH {

    static public $_toHtmlCallId = 0;

    /**
     * Используется для идентификации шаблонов.
     * Каждому шаблону дается уникальный id для того, чтобы избежать повторного применения
     * шаблона к одному и тому же узлу BEMJSON-дерева.
     * @var integer
     * @protected
     */
    protected $_lastMatchId = 0;

    /**
     * Плоский массив для хранения матчеров.
     * Каждый элемент — массив с двумя элементами: [{String} выражение, {Function} шаблон}]
     * @var array
     * @protected
     */
    protected $_matchers = [];

    /**
     * Неймспейс для библиотек. Сюда можно писать различный функционал для дальнейшего использования в шаблонах.
     *
     * ```javascript
     * bh.lib.objects = bh.lib.objects || [];
     * bh.lib.objects.inverse = bh.lib.objects.inverse || function(obj) { ... };
     * ```
     * @var array
     */
    protected $lib = [];

    /**
     * Опции BH. Задаются через setOptions.
     * @var array
     */
    protected $_options = [];
    protected $_optJsAttrName = 'onclick';
    protected $_optJsAttrIsJs = true;
    protected $_optEscapeContent = false;

    /**
     * Флаг, включающий автоматическую систему поиска зацикливаний. Следует использовать в development-режиме,
     * чтобы определять причины зацикливания.
     * @var boolean
     * @protected
     */
    protected $_infiniteLoopDetection = false;

    protected $ctx = null;

    static protected $selfCloseHtmlTags = [
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'menuitem' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1
    ];

    /**
     * BH: BEMJSON -> HTML процессор.
     * @constructor
     */
    function __construct () {
    }

    /**
     * Задает опции шаблонизации.
     *
     * @chainable
     * @param array $options
     *        string $options[jsAttrName] Атрибут, в который записывается значение поля `js`. По умолчанию, `onclick`.
     *        string $options[jsAttrScheme] Схема данных для `js`-значения.
     *               Форматы:
     *                     `js` — значение по умолчанию. Получаем `return { ... }`.
     *                     `json` — JSON-формат. Получаем `{ ... }`.
     * @return BH $this
     */
    function setOptions ($options) {
        $this->_options = [];
        foreach ($options as $k => $option) {
            $this->_options[$k] = $options[$k];
        }

        if (!empty($options['jsAttrName'])) {
            $this->_optJsAttrName = $options['jsAttrName'];
        }

        if (!empty($options['jsAttrScheme'])) {
            $this->_optJsAttrIsJs = $options['jsAttrScheme'] === 'js';
        }

        if (!empty($options['escapeContent'])) {
            $this->_optEscapeContent = $options['escapeContent'];
        }

        return $this;
    }

    /**
     * Возвращает опции шаблонизации.
     *
     * @return array
     */
    function getOptions () {
        return $this->_options;
    }

    /**
     * Включает/выключает механизм определения зацикливаний.
     *
     * @chainable
     * @param boolean enable
     * @return BH
     */
    function enableInfiniteLoopDetection ($enable) {
        $this->_infiniteLoopDetection = $enable;
        return $this;
    }

    /**
     * Преобразует BEMJSON в HTML-код
     *
     * @param BemJson $bemJson
     * @return string
     */
    function apply ($bemJson) {
        return $this->toHtml($this->processBemJson($bemJson));
    }

    /**
     * Объявляет шаблон.
     * ```javascript
     * bh.match('page', function(ctx) {
     *     ctx.mix([{ block: 'ua' }]);
     *     ctx.cls('ua_js_no ua_css_standard');
     * });
     * bh.match('block_mod_modVal', function(ctx) {
     *     ctx.tag('span');
     * });
     * bh.match('block__elem', function(ctx) {
     *     ctx.attr('disabled', 'disabled');
     * });
     * bh.match('block__elem_elemMod', function(ctx) {
     *     ctx.mix([{ block: 'link' }]);
     * });
     * bh.match('block__elem_elemMod_elemModVal', function(ctx) {
     *     ctx.mod('active', 'yes');
     * });
     * bh.match('block_blockMod__elem', function(ctx) {
     *     ctx.param('checked', true);
     * });
     * bh.match('block_blockMod_blockModVal__elem', function(ctx) {
     *     ctx.content({
     *         elem: 'wrapper',
     *         content: ctx
     *     };
     * });
     * ```
     *
     * @chainable
     * @param string|array $expr bemSelector or map with matchers
     * @param callable [$matcher]
     * @return BH
     */
    function match ($expr, $matcher = null) {
        if (!$expr) {
            return $this;
        }

        if (isList($expr)) {
            foreach ($expr as $match) {
                $this->match($match, $matcher);
            }
            return $this;
        }

        if (is_array($expr)) {
            foreach ($expr as $k => $matcher) {
                $this->match($k, $matcher);
            }
            return $this;
        }

        $this->_matchers[] = [ // better to make it via Matcher object with __invoke
            'expr' => $expr,
            'fn'   => $matcher,
            '__id' => $this->_lastMatchId
        ];
        $this->_lastMatchId++;

        // cleanup cached matcher to rebuild it on next render
        $this->_matcher = null;

        return $this;
    }

    /**
     * Вспомогательный метод для компиляции шаблонов с целью их быстрого дальнейшего исполнения.
     * @return string
     */
    function buildMatcher () {
        $res = [];
        $vars = []; //'$bh = $this'];
        $allMatchers = $this->_matchers;
        $declarations = [];
        // Matchers->iterate
        for ($i = sizeof($allMatchers) - 1; $i >= 0; $i--) {
            $matcherInfo = $allMatchers[$i];
            $expr = $matcherInfo['expr'];
            $decl = ['fn' => $matcherInfo['fn'], '__id' => $matcherInfo['__id'], 'index' => $i]
                + static::parseBemCssClasses($matcherInfo['expr']);
            $declarations[] = $decl;
        }

        $res[] = 'return function ($ctx, $json) use ($ms) {';

        $res[] = 'switch ($json->block ?: __undefined) {';
        $declByBlock = static::groupBy($declarations, 'block');
        foreach ($declByBlock as $blockName => $blockData) {
            $res[] = 'case "' . $blockName . '":';

            $res[] = '  switch ($json->elem ?: __undefined) {';
            $declsByElem = static::groupBy($blockData, 'elem');
            foreach ($declsByElem as $elemName => $decls) {
                $elemCase = $elemName === __undefined ? '__undefined' : '"' . $elemName . '"';
                $res[] = '  case ' . $elemCase . ':';

                foreach ($decls as $decl) {
                    $__id = $decl['__id'];
                    $conds = [];
                    $conds[] = ('!isset($json->__m[' . $__id . '])');
                    if (isset($decl['elemMod'])) {
                        $modKey = $decl['elemMod'];
                        $conds[] = (
                            'isset($json->mods) && $json->mods->{"' . $modKey . '"} === ' .
                                ($decl['elemModVal'] === true ? 'true' : '"' . $decl['elemModVal'] . '"'));
                    }
                    if (isset($decl['blockMod'])) {
                        $modKey = $decl["blockMod"];
                        $conds[] = (
                            'isset($json->blockMods) && $json->blockMods->{"' . $modKey . '"} === ' .
                                ($decl['blockModVal'] === true ? 'true' : '"' . $decl['blockModVal'] . '"'));
                    }

                    $res[] = ('    if (' . join(' && ', $conds) . ') {');
                    $res[] = ('      $json->__m[' . $__id . '] = true;');
                    $res[] = ('      $subRes = $ms[' . $decl['index'] . ']["fn"]($ctx, $json);');
                    $res[] = ('      if ($subRes !== null) { return ($subRes ?: ""); }');
                    $res[] = ('      if ($json->_stop) return;');
                    $res[] = ('    }');
                }

                $res[] = ('    return;');
            }
            $res[] = ('}');
            $res[] = ('  return;');
        }
        $res[] = ('}');
        $res[] = ('};');

        return "return function (\$ms) {\n" . join("\n", $res) . "\n};";
    }

    protected $_matcherCalls = 0;
    protected $_matcher = null;

    function getMatcher () {
        if ($this->_matcher) return $this->_matcher;

        // debugging purposes only (!!!)
        // $debug = false; //true;

        // $key = md5(join('|', array_map(function ($e) { return $e['expr']; }, $this->_matchers)));
        // $file = "./tmp/bh-matchers-{$key}.php";
        // $constructor = @include $file;
        // if (!$constructor) {
        // if ($debug) {
            $code = $this->buildMatcher();
            // file_put_contents($file, "<?php\n" . $code);
            // file_put_contents("./bh-matcher.php", "<?php\n" . $fn);
            // $constructor = include("./bh-matcher.php");
            $constructor = eval($code);
        // }

        $this->_matcherCalls = 0;
        $this->_matcher = $constructor($this->_matchers);

        return $this->_matcher;
    }

    /**
     * Раскрывает BEMJSON, превращая его из краткого в полный.
     * @param Json|array $bemJson
     * @param string [$blockName]
     * @param boolean [$ignoreContent]
     * @return Json
     */
    function processBemJson ($bemJson, $blockName = null, $ignoreContent = null) {
        if (empty($bemJson)) {
            return is_array($bemJson)
                ? '<div></div>'
                : '';
        }

        // trying to parse
        if (is_scalar($bemJson)) {
            // string? like '{...}' || '[{...}]' || '([...])'
            if (!is_string($bemJson)) {
                // return as is
                return (string)$bemJson;
            }

            // deprecated feature:
            $bemJson = trim($bemJson, "\n\t\r ()\x0B\0");
            $c = $bemJson[0];
            $l = $bemJson[strlen($bemJson) - 1];
            if ($c === '{' && $l === '}' || $c === '[' && $l === ']') {
                // if it looks like json object - parse and process
                return $this->processBemJson(weakjson_decode($bemJson));
            } else {
                // return as is
                return $bemJson;
            }
        }

        $resultArr = JsonCollection::normalize($bemJson);
        if (sizeof($resultArr) > 1) {
            $resultArr = new \ArrayObject([$resultArr]);
        }

        $bemJson = $resultArr[0];
        $blockMods = null;
        if ($bemJson instanceof Json) {
            $blockMods = (!$bemJson->elem && isset($bemJson->mods)) ? $bemJson->mods : $bemJson->blockMods;
        }

        $steps = [];
        $steps[] = new Step(
            $bemJson,
            $resultArr,
            0,
            $blockName,
            $blockMods
        );

        // var compiledMatcher = (this._fastMatcher || (this._fastMatcher = Function('ms', this.buildMatcher())(this._matchers)));
        if (!$this->_matcher) $this->getMatcher();
        $compiledMatcher = $this->_matcher;

        $processContent = !$ignoreContent;
        $infiniteLoopDetection = $this->_infiniteLoopDetection;

        $ctx = new Context($this);

        // js: while (node = nodes.shift()) {
        while ($step = array_shift($steps)) {
            $json = $step->json;
            $blockName = $step->blockName;
            $blockMods = $step->blockMods;

            if ($json instanceof JsonCollection) {
                $j = 0;
                $arr = $json;
                foreach ($arr as $i => $child) {
                    if (!is_object($child)) {
                        continue;
                    }
                    // walk each bem node inside collection
                    $steps[] = new Step(
                        $child,
                        $arr,
                        $i,
                        $blockName,
                        $blockMods,
                        ++$j,
                        $step
                    );
                }
                $arr->_listLength = $j;

            } else {
                $stopProcess = false;

                if (is_scalar($json) || is_null($json)) {
                    // skip
                } elseif ($json->elem) {
                    $blockName = $json->block = isset($json->block) ? $json->block : $blockName;
                    // sync mods:
                    // blockMods = json.blockMods = json.blockMods || blockMods
                    $blockMods = $json->blockMods = isset($json->blockMods) ? $json->blockMods : $blockMods;
                    // sync elem mods:
                    if (isset($json->elemMods)) {
                        $json->mods = $json->elemMods;
                    }
                } elseif ($json->block) {
                    $blockName = $json->block;
                    $blockMods = $json->blockMods = $json->mods;
                }

                if ($json && $json->block) {

                    if ($infiniteLoopDetection) {
                        $json->__processCounter = (key_exists('__processCounter', $json) ? $json->__processCounter : 0) + 1;
                        $this->_matcherCalls++;
                        if ($json->__processCounter > 100) {
                            throw new \Exception('Infinite json loop detected at "' . $json->block . ($json->elem ? '__' . $json->elem : '') . '".');
                        }
                        if ($this->_matcherCalls > 1000) {
                            throw new \Exception('Infinite matcher loop detected at "' . $json->block . ($json->elem ? '__' . $json->elem : '') . '".');
                        }
                    }

                    if (!$json->_stop) {
                        $ctx->node = $step;
                        $ctx->ctx = $json;
                        $subRes = $compiledMatcher($ctx, $json);
                        if ($subRes !== null) {
                            $json = JsonCollection::normalize($subRes);
                            $step->json = $json;
                            $step->blockName = $blockName;
                            $step->blockMods = $blockMods;
                            $steps[] = $step;
                            $stopProcess = true;
                        }
                    }

                }

                if (!$stopProcess && $processContent && isset($json->content) && !is_scalar($json->content)) {
                    $content = $json->content;
                    //if ($content instanceof JsonCollection) {

                        $arr = $content;
                        $j = 0;
                        foreach ($content as $i => $child) {
                            if (!(is_object($child) || is_array($child))) {
                                continue;
                            }
                            $steps[] = new Step(
                                $child,
                                $content,
                                $i,
                                $blockName,
                                $blockMods,
                                ++$j,
                                $step
                            );
                        }
                        $content->_listLength = $j;

                    /*} else {
                        // commented since 24 nov '14
                        // throw new \Exception('Do we need it?');
                    }*/
                }
            }

            $step->arr[$step->index] = $json;
        }

        //d('processBemjson#' . ($_callId) . ' out ', $resultArr[0]);
        return $resultArr[0];
    }

    /**
     * Превращает раскрытый BEMJSON в HTML.
     * @param BemJson $json
     * @return string
     */
    public function toHtml ($json) {
        if ($json === false || $json === null) {
            return '';
        }

        if (is_scalar($json)) {
            return $this->_optEscapeContent ? self::xmlEscape($json) : $json;
        }

        if (isList($json)) {
            $res = '';
            foreach ($json as $item) {
                if ($item !== false && $item !== null) {
                    $res .= $this->toHtml($item);
                }
            }
            return $res;

        } else {
            $isBEM = $json->bem !== false;

            if ($json->tag === false || $json->tag === '') {
                return is_null($json->html) && is_null($json->content) ? '' : $this->toHtml($json->content);
            }

            $cls = '';
            $attrs = '';
            $hasMixJsParams = false;

            if (!empty($json->attrs)) {
                foreach ($json->attrs as $jkey => $jval) {
                    if ($jval !== null) {
                        $attrs .= ' ' . $jkey /*escape?*/ . '="' . static::attrEscape($jval) . '"';
                    }
                }
            }

            if ($isBEM) {
                // hardcoded naming
                $base = (!empty($json->block) ? $json->block : '') . (!empty($json->elem) ? '__' . $json->elem : '');

                $jsParams = false;
                if (!empty($json->block)) {
                    $cls = static::toBemCssClasses($json, $base);
                    if ($json->js !== null && $json->js !== false) {
                        $jsParams = [];
                        $jsParams[$base] = $json->js === true ? [] : $this->_filterNulls($json->js);
                    }
                }

                $addJSInitClass = $jsParams && !$json->elem;

                if ($json->mix) {
                    foreach ($json->mix as $mix) {
                        if (!$mix || $mix->bem === false) {
                            continue;
                        }

                        // var mixBlock = mix.block || json.block || ''
                        $mixBlock = $mix->block ?: $json->block;
                        if (!$mixBlock) {
                            continue;
                        }

                        // mixElem = mix.elem || (mix.block ? null : json.block && json.elem)
                        $mixElem = $mix->elem ?: ($mix->block ? null : ($json->block ? $json->elem : null));
                        $mixBase = $mixBlock . ($mixElem ? '__' . $mixElem : '');

                        $cls .= static::toBemCssClasses($mix, $mixBase, $base);
                        if ($mix->js !== null && $mix->js !== false) {
                            $jsParams = $jsParams ?: [];
                            $jsParams[$mixBase] = $mix->js === true ? [] : $this->_filterNulls($mix->js);
                            $hasMixJsParams = true;
                            if (!$addJSInitClass) $addJSInitClass = ($mixBlock && !$mixElem);
                        }
                    }
                }

                if ($jsParams) {
                    if ($addJSInitClass) $cls .= ' i-bem';
                    $jsData = !$hasMixJsParams && $json->js === true ?
                        '{&quot;' . $base . '&quot;:{}}' :
                        self::attrEscape(str_replace('[]', '{}',
                            json_encode($jsParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
                    $attrs .= ' ' . ($json->jsAttr ?: $this->_optJsAttrName) . '="' .
                        ($this->_optJsAttrIsJs ? 'return ' . $jsData : $jsData) . '"';
                }
            }

            $cls = (string)$cls;
            if ($json->cls !== null) {
                $cls .= ($cls ? ' ' : '') . self::attrEscape($json->cls);
            }

            $tag = $json->tag !== null ? $json->tag : 'div';
            $res = '<' . $tag . ($cls ? ' class="' . $cls . '"' : '') . ($attrs ? $attrs : '');

            if (isset(static::$selfCloseHtmlTags[$tag])) {
                $res .= '/>';
            } else {
                $res .= '>';
                if (!empty($json->html)) {
                    $res .= $json->html;
                } elseif (!is_null($json->content)) {
                    $content = $json->content;
                    if (isList($content)) {
                        foreach ($content as $item) {
                            if ($item !== false && $item !== null) {
                                $res .= $this->toHtml($item);
                            }
                        }
                    } else {
                        $res .= $this->toHtml($content);
                    }
                }
                $res .= '</' . $tag . '>';
            }
            return $res;
        }
    }

    // todo: add encoding here
    public static function xmlEscape($s) {
        return htmlspecialchars($s, ENT_NOQUOTES);
    }

    public static function attrEscape($s) {
        if (is_bool($s)) {
            return $s ? 'true' : 'false';
        }
        return htmlspecialchars($s, ENT_QUOTES);
    }

    public static function toBemCssClasses($json, $base, $parentBase = null) {
        $res = '';

        if ($parentBase !== $base) {
            if ($parentBase) {
                $res .= ' ';
            }
            $res .= $base;
        }

        // if (mods = json.mods || json.elem && json.elemMods)
        $mods = isset($json->mods) ? $json->mods :
            ($json->elem && isset($json->elemMods) ? $json->elemMods : null);
        if ($mods) {
            foreach ($mods as $k => $mod) {
                if ($mod || $mod === 0) {
                    $res .= ' ' . $base . '_' . $k . ($mod === true ? '' : '_' . $mod);
                }
            }
        }

        return $res;
    }

    // @todo fixup hardcoded leveling
    public static function parseBemCssClasses ($expr) {
        list ($blockBits, $elemBits) = explode('__', $expr . "__\1");

        list ($block, $blockMod, $blockModVal) = explode('_', $blockBits . "_\1_\1");
        $blockMod = $blockMod === "\1" ? null : $blockMod;
        $blockModVal = $blockMod ? ($blockModVal !== "\1" ? $blockModVal : true) : null;

        if ($elemBits !== "\1") {
            list ($elem, $elemMod, $elemModVal) = explode('_', $elemBits . "_\1_\1_\1");
            $elemMod = $elemMod === "\1" ? null : $elemMod;
            $elemModVal = $elemMod ? ($elemModVal !== "\1" ? $elemModVal : true) : null;
        }

        return compact('block', 'blockMod', 'blockModVal', 'elem', 'elemMod', 'elemModVal');
    }

    /**
     * Group up selectors by some key
     * @param array data
     * @param string key
     * @return array
     */
    public static function groupBy ($data, $key) {
        $res = [];
        for ($i = 0, $l = sizeof($data); $i < $l; $i++) {
            $item = $data[$i];
            $value = empty($item[$key]) ? __undefined : $item[$key];
            if (empty($res[$value])) {
                $res[$value] = [];
            }
            $res[$value][] = $item;
        }
        return $res;
    }

    protected static function _filterNulls ($arr) {
        return array_filter($arr, function ($e) {
            return $e !== null;
        });
    }
};

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
     * Флаг, включающий автоматическую систему поиска зацикливаний. Следует использовать в development-режиме,
     * чтобы определять причины зацикливания.
     * @var boolean
     * @protected
     */
    protected $_infiniteLoopDetection = false;

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

    protected $_inited = false;

    /**
     * Опции BH. Задаются через setOptions.
     * @var array
     */
    protected $_options = [];
    protected $_optJsAttrName = 'onclick';
    protected $_optJsAttrIsJs = true;
    protected $_optEscapeContent = false;

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
            '__id' => ($this->_lastMatchId++)
        ];

        // cleanup cached matcher to rebuild it on next render
        $this->_fastMatcher = null;

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
            //$vars[] = '$_m' . $i . ' = $ms[' . $i . ']["fn"]';
            $decl = ['fn' => $matcherInfo['fn'], '__id' => $matcherInfo['__id'], 'index' => $i]
                + static::parseBemCssClasses($matcherInfo['expr']);
            $declarations[] = $decl;
        }

        $res[] = 'return function ($ctx, $json) use ($ms) {'; // ?
        // $res[] = join(";\n", $vars) . ';';

        $res[] = ('$b = $json->block ?: __undefined;');
        $res[] = ('$e = $json->elem ?: __undefined;');
        // $res[] = ('switch ($b) { /*block*/');
        $bElse = '';
        $declByBlock = static::groupBy($declarations, 'block');
        foreach ($declByBlock as $blockName => $blockData) {
            $res[] = ($bElse . 'if ($b === "' . static::strEscape($blockName) . '") {');
            $bElse = 'else';

            $eElse = '';
            $declsByElem = static::groupBy($blockData, 'elem');
            foreach ($declsByElem as $elemName => $decls) {
                $elemCase = $elemName === __undefined ? '__undefined' : '"' . static::strEscape($elemName) . '"';
                $res[] = '  ' . $eElse . 'if ($e === ' . $elemCase . ') {';

                $eElse = 'else';
                foreach ($decls as $decl) {
                    $__id = $decl['__id'];
                    $conds = [];
                    $conds[] = ('!isset($json->__m[' . $__id . '])');
                    if (isset($decl['elemMod'])) {
                        $modKey = static::strEscape($decl['elemMod']);
                        $conds[] = (
                            'isset($json->mods) && $json->mods->{"' . $modKey . '"} === ' .
                                ($decl['elemModVal'] === true ? 'true' : '"' . static::strEscape($decl['elemModVal']) . '"'));
                    }
                    if (isset($decl['blockMod'])) {
                        $modKey = static::strEscape($decl["blockMod"]);
                        $conds[] = (
                            'isset($json->blockMods) && $json->blockMods->{"' . $modKey . '"} === ' .
                                ($decl['blockModVal'] === true ? 'true' : '"' . static::strEscape($decl['blockModVal']) . '"'));
                    }

                    $res[] = ('    if (' . join(' && ', $conds) . ') {');
                    $res[] = ('      $json->__m[' . $__id . '] = true;');
                    $res[] = ('      $subRes = $ms[' . $decl['index'] . ']["fn"]($ctx, $json);');
                    $res[] = ('      if ($subRes !== null) { return ($subRes ?: ""); }');
                    $res[] = ('      if ($json->_stop) return;');
                    $res[] = ('    }');
                }

                $res[] = ('  }');
            }
            $res[] = ('}');
        }
        $res[] = ('};');
        $res = "return function (\$ms) {\n" . join("\n", $res) . "\n};";

        // debugging purposes only (!!!)
        // file_put_contents("./tmp/bh-matcher.php", "<?php\n" . $res);
        // $constructor = include("./tmp/bh-matcher.php");

        $constructor = eval($res);
        return $constructor->bindTo($this);
    }

    /**
     * Раскрывает BEMJSON, превращая его из краткого в полный.
     * @param Json|array $bemJson
     * @param string [$blockName]
     * @param boolean [$ignoreContent]
     * @return Json
     */
    function processBemJson ($bemJson, $blockName = null, $ignoreContent = null) {
        //$_callId = static::$_toHtmlCallId ++;
        //d('processBemjson#' . $_callId, $bemJson);
        if (empty($bemJson)) {
            return is_array($bemJson) ? '<div></div>' : '';
        }

        if (!$this->_inited) {
            $this->_init();
        }

        // trying to parse
        if (is_scalar($bemJson)) {
            // string? like '{...}' || '[{...}]' || '([...])'
            if (!is_string($bemJson)) {
                // return as is
                return $bemJson;
            }
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
        $steps[] = new Step([
            'json' => $bemJson,
            'arr' => $resultArr,
            'index' => 0,
            'blockName' => $blockName,
            'blockMods' => $blockMods
        ]);

        // var compiledMatcher = (this._fastMatcher || (this._fastMatcher = Function('ms', this.buildMatcher())(this._matchers)));
        if (empty($this->_fastMatcher)) {
            $fn = $this->buildMatcher();
            $this->_fastMatcher = [
                '__processCounter' => 0,
                'fn' => $fn($this->_matchers)
            ];
        }
        $compiledMatcher =& $this->_fastMatcher;

        $processContent = !$ignoreContent;
        $infiniteLoopDetection = $this->_infiniteLoopDetection;

        $ctx = new Context($this);

        // js: while (node = nodes.shift()) {
        $stepn = 0;
        while ($step = array_shift($steps)) {
            $json = $step->json;
            $blockName = $step->blockName;
            $blockMods = $step->blockMods;

            // d($stepn++, $step);
            if ($json instanceof JsonCollection) {
                $j = 0;
                $arr = $json;
                foreach ($arr as $i => $child) {
                    if (is_array($child)) {
                        $child = $arr[$i] = new Json($child);
                    }
                    if (is_object($child)) {
                        $steps[] = new Step([
                            'json' => $child,
                            'arr' => $arr,
                            'index' => $i,
                            'position' => ++$j,
                            'blockName' => $blockName,
                            'blockMods' => $blockMods,
                            'parentNode' => $step // step
                        ]);
                    }
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
                        $compiledMatcher['__processCounter']++;
                        if ($json->__processCounter > 100) {
                            throw new \Exception('Infinite json loop detected at "' . $json->block . ($json->elem ? '__' . $json->elem : '') . '".');
                        }
                        if ($compiledMatcher['__processCounter'] > 1000) {
                            throw new \Exception('Infinite matcher loop detected at "' . $json->block . ($json->elem ? '__' . $json->elem : '') . '".');
                        }
                    }

                    if (!$json->_stop) {
                        $ctx->node = $step;
                        $ctx->ctx = $json;
                        $subRes = $compiledMatcher['fn']($ctx, $json);
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
                    if ($content instanceof JsonCollection) {
                        $arr = $content;
                        for ($i = 0, $j = 0, $l = sizeof($arr); $i < $l; $i++) {
                            $child = $content[$i];
                            if (is_object($child) || is_array($child)) {
                                $steps[] = new Step([
                                    'json' => $child,
                                    'arr' => $content,
                                    'index' => $i,
                                    'position' => ++$j,
                                    'blockName' => $blockName,
                                    'blockMods' => $blockMods,
                                    'parentNode' => $step
                                ]);
                            }
                        }
                        $content->_listLength = $j;

                    } else {
                        throw new \Exception('asdasd');
                        /*$steps[] = new Step([
                            'json' => $content,
                            'arr' => JsonCollection::normalize($json),
                            'index' => 'content',
                            'blockName' => $blockName,
                            'blockMods' => $blockMods,
                            'parentNode' => $step
                        ]);*/
                    }
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
            return $this->_optEscapeContent ? $this->xmlEscape($json) : $json;
        }

        if (isList($json) || $json instanceof JsonCollection) {
            $res = '';
            foreach ($json as $item) {
                if ($item !== false && $item !== null) {
                    $res .= $this->toHtml($item);
                }
            }
            return $res;

        } else {
            $isBEM = (!key_exists('bem', $json)) || ($json->bem !== false);

            if (key_exists('tag', $json) && !$json->tag) {
                return !key_exists('html', $json) && is_null($json->content) ? '' : $this->toHtml($json->content);
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
                    if (key_exists('js', $json) && $json->js !== false) {
                        $jsParams = [];
                        $jsParams[$base] = $json->js === true ? [] : $json->js;
                    }
                }

                if (key_exists('mix', $json) && $json->mix) {
                    foreach ($json->mix as $mix) {
                        if (!$mix || (key_exists('bem', $mix) && $mix->bem === false)) {
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
                        if (key_exists('js', $mix) && $mix->js !== false) {
                            $jsParams = $jsParams ?: [];
                            $jsParams[$mixBase] = $mix->js === true ? [] : $mix->js;
                            $hasMixJsParams = true;
                        }
                    }
                }

                if ($jsParams) {
                    $cls = $cls . ' i-bem';
                    $jsData = !$hasMixJsParams && $json->js === true ?
                        '{&quot;' . $base . '&quot;:{}}' :
                        $this->attrEscape(str_replace('[]', '{}', json_encode($jsParams, JSON_UNESCAPED_UNICODE)));
                    $attrs .= ' ' . (key_exists('jsAttr', $json) ? $json->jsAttr : $this->_optJsAttrName) . '="' .
                        ($this->_optJsAttrIsJs ? 'return ' . $jsData : $jsData) . '"';
                }
            }

            $cls = (!empty($cls) ? $cls : '') .
                (key_exists('cls', $json) ? (!empty($cls) ? ' ' : '') . $json->cls : '');

            $tag = key_exists('tag', $json) ? $json->tag : 'div';
            $res = '<' . $tag . ($cls ? ' class="' . $this->attrEscape($cls) . '"' : '') . ($attrs ? $attrs : '');

            if (isset(static::$selfCloseHtmlTags[$tag])) {
                $res .= '/>';
            } else {
                $res .= '>';
                if (!empty($json->html)) {
                    $res .= $json->html;
                } elseif (!is_null($json->content)) {
                    $content = $json->content;
                    if (isList($content) || $content instanceof JsonCollection) {
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

    /**
     * Инициализация BH.
     */
    protected function _init () {
        $this->_inited = true;

        // Копируем ссылку на BEM.I18N в bh.lib.i18n, если это возможно.
        // if (typeof BEM !== 'undefined' && typeof BEM.I18N !== 'undefined') {
        //    $this->lib.i18n = $this->lib.i18n || BEM.I18N;
        // }
    }

    // todo: add encoding here
    public static function xmlEscape($s) {
        return htmlspecialchars($s, ENT_NOQUOTES);
    }

    public static function attrEscape($s) {
        if (!is_string($s)) {
            if (is_bool($s)) {
                return $s ? 'true' : 'false';
            }
        }
        return htmlspecialchars($s, ENT_QUOTES);
    }

    public static function strEscape($s) {
        return str_replace(array('\\', '"'), array('\\\\', '\\"'), $s);
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
                if ($mod) {
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
};

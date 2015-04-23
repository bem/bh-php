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
     * $bh->lib->i18n = BEM.I18N;
     * $bh->lib->objects = bh.lib.objects || [];
     * $bh->lib->objects.inverse = bh.lib.objects.inverse || function(obj) { ... };
     * ```
     * @var ArrayObject
     */
    public $lib;

    /**
     * Опции BH. Задаются через setOptions.
     * @var array
     */
    protected $_options = [];

    protected $_optJsAttrName = 'onclick';
    protected $_optJsAttrIsJs = true;
    protected $_optJsCls = 'i-bem';
    protected $_optEscapeContent = false;
    protected $_optNobaseMods = false;

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
        $this->lib = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
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

        if (isset($options['jsAttrName'])) {
            $this->_optJsAttrName = $options['jsAttrName'];
        }

        if (isset($options['jsAttrScheme'])) {
            $this->_optJsAttrIsJs = $options['jsAttrScheme'] === 'js';
        }

        if (isset($options['jsCls'])) {
            $this->_optJsCls = $options['jsCls'];
        }

        if (isset($options['clsNobaseMods'])) {
            $this->_optNobaseMods = true;
        }

        if (isset($options['escapeContent'])) {
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
     * Объявляет глобальный шаблон, применяемый перед остальными.
     *
     * ```php
     * $bh->beforeEach(function ($ctx, $json) {
     *     $ctx->attr('onclick', $json->counter);
     * });
     * ```
     *
     * @param Closure $matcher
     * @return BH
     */
    function beforeEach ($matcher) {
        return $this->match('$before', $matcher);
    }

    /**
     * Объявляет глобальный шаблон, применяемый после остальных.
     *
     * ```php
     * $bh->afterEach(function ($ctx) {
     *     $ctx->tag('xdiv');
     * });
     * ```
     *
     * @param Closure $matcher
     * @return BH
     */
    function afterEach ($matcher) {
        return $this->match('$after', $matcher);
    }

    /**
     * Вставляет вызов шаблона в очередь вызова.
     *
     * @param Array $res
     * @param String $fnId
     * @param Number $index
     */
    static protected function pushMatcher(&$res, $fnId, $index) {
        $res[] = ('      $json->_m[' . $fnId . '] = true;');
        $res[] = ('      $subRes = $ms[' . $index . ']["fn"]($ctx, $json);');
        $res[] = ('      if ($subRes !== null) { return ($subRes ?: ""); }');
        $res[] = ('      if ($json->_stop) return;');
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

        $declByBlock = static::groupBy($declarations, 'block');

        if (isset($declByBlock['$before'])) {
            foreach ($declByBlock['$before'] as $decl) {
                static::pushMatcher($res, $decl['__id'], $decl['index']);
            }
        }

        $afterEach = isset($declByBlock['$after']) ? $declByBlock['$after'] : null;
        unset($declByBlock['$before'], $declByBlock['$after']);

        if ($declByBlock) :
        $res[] = 'switch ($json->block ?: __undefined) {';
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
                    $conds[] = ('!isset($json->_m[' . $__id . '])');
                    if (isset($decl['elemMod'])) {
                        $modKey = $decl['elemMod'];
                        $conds[] = (
                            'isset($json->elemMods) && $json->elemMods->{"' . $modKey . '"} === ' .
                                ($decl['elemModVal'] === true ? 'true' : '"' . $decl['elemModVal'] . '"'));
                    }
                    if (isset($decl['blockMod'])) {
                        $modKey = $decl["blockMod"];
                        $conds[] = (
                            'isset($json->mods) && $json->mods->{"' . $modKey . '"} === ' .
                                ($decl['blockModVal'] === true ? 'true' : '"' . $decl['blockModVal'] . '"'));
                    }

                    $res[] = ('    if (' . join(' && ', $conds) . ') {');
                    static::pushMatcher($res, $__id, $decl['index']);
                    $res[] = ('    }');
                }

                $res[] = ('    break;');
            }
            $res[] = ('}');
            $res[] = ('  break;');
        }
        $res[] = ('}');
        endif;

        if ($afterEach) {
            foreach ($afterEach as $decl) {
                static::pushMatcher($res, $decl['__id'], $decl['index']);
            }
        }

        $res[] = ('};');

        return "return function (\$ms) {\n" . join("\n", $res) . "\n};";
    }

    protected $_matcherCalls = 0;
    protected $_matcher = null;

    function getMatcher () {
        if ($this->_matcher) return $this->_matcher;

        // debugging purposes only (!!!)
        // $key = md5(join('|', array_map(function ($e) { return $e['expr']; }, $this->_matchers)));
        // $file = "./tmp/bh-matchers-{$key}.php";
        // $constructor = @include $file;
        // if (!$constructor) {
            $code = $this->buildMatcher();
        //     file_put_contents($file, "<?php\n" . $code);
        //     $constructor = include $file;
            $constructor = eval($code);
        // }

        $this->warmUp($constructor);

        return $this->_matcher;
    }

    public function dumpFuel () {
        return $this->buildMatcher();
    }

    public function warmUp (\Closure $constructor) {
        $this->_matcherCalls = 0;
        $this->_matcher = $constructor($this->_matchers);
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

        $steps = [];
        $steps[] = new Step(
            $bemJson,
            $resultArr,
            0,
            $blockName,
            null
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
            $blockName = $step->block;
            $blockMods = $step->mods;

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

                $step->arr[$step->index] = $json;
                continue;
            }

            $stopProcess = false;

            if (is_scalar($json) || empty($json)) {
                // skip
                continue;

            } elseif ($json->elem) {
                $blockName = $json->block = $json->block ?: $blockName;
                if (!isset($json->elemMods)) {
                    $json->elemMods = $json->mods;
                    $json->mods = null;
                }
                $blockMods = $json->mods = isset($json->mods) ? $json->mods : $blockMods;

            } elseif ($json->block) {
                $blockName = $json->block;
                $blockMods = $json->mods;
            }

            if ($json instanceof Json) {
                if ($infiniteLoopDetection) {
                    $json->_matcherCalls++;
                    $this->_matcherCalls++;
                    if ($json->_matcherCalls > 100) {
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
                        $step->block = $blockName;
                        $step->mods = $blockMods;
                        $steps[] = $step;
                        $stopProcess = true;
                    }
                }
            }

            if (!$stopProcess && $processContent && isset($json->content) && !is_scalar($json->content)) {
                $content = $json->content;

                $j = 0;
                foreach ($content as $i => $child) {
                    if (is_scalar($child) || empty($child)) {
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
            }
        }

        return $resultArr[0];
    }

    /**
     * Превращает раскрытый BEMJSON в HTML.
     * @param BemJson $json
     * @return string
     */
    public function toHtml ($json) {
        if (!$json) {
            return (string)$json;
        }

        if (is_scalar($json)) {
            return $this->_optEscapeContent ? htmlspecialchars($json, ENT_NOQUOTES) : $json;
        }

        if (isList($json)) {
            $res = '';
            foreach ($json as $item) {
                if ($item !== false && $item !== null) {
                    $res .= $this->toHtml($item);
                }
            }
            return $res;
        }

        if ($json->tag === false || $json->tag === '') {
            return $json->content === null ? '' : $this->toHtml($json->content);
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

        if ($json->bem !== false) {
            // hardcoded naming
            $base = ($json->block ? $json->block : '') . ($json->elem ? '__' . $json->elem : '');

            $jsParams = false;
            if ($json->block) {
                $cls = static::toBemCssClasses($json, $base, null, $this->_optNobaseMods);
                if ($json->js !== null && $json->js !== false) {
                    $jsParams = [];
                    $jsParams[$base] = $json->js === true ? [] : array_filter($json->js, 'sizeof');
                }
            }

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

                    $cls .= static::toBemCssClasses($mix, $mixBase, $base, $this->_optNobaseMods);
                    if ($mix->js !== null && $mix->js !== false) {
                        $jsParams = $jsParams ?: [];
                        $jsParams[$mixBase] = $mix->js === true ? [] : array_filter($mix->js, 'sizeof');
                        $hasMixJsParams = true;
                    }
                }
            }

            if ($jsParams) {
                if ($this->_optJsCls) $cls .= ' ' . $this->_optJsCls;
                $jsData = !$hasMixJsParams && $json->js === true ?
                    '{&quot;' . $base . '&quot;:{}}' :
                    static::attrEscape(str_replace('[]', '{}',
                        json_encode($jsParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
                $attrs .= ' ' . ($json->jsAttr ?: $this->_optJsAttrName) . '="' .
                    ($this->_optJsAttrIsJs ? 'return ' . $jsData : $jsData) . '"';
            }
        }

        $cls = (string)$cls;
        if ($json->cls !== null) {
            $cls .= ($cls ? ' ' : '') . static::attrEscape($json->cls);
        }

        $tag = $json->tag !== null ? $json->tag : 'div';
        $res = '<' . $tag . ($cls ? ' class="' . $cls . '"' : '') . ($attrs ? $attrs : '');

        if (isset(static::$selfCloseHtmlTags[$tag])) {
            return $res . '/>';
        }

        $res .= '>';
        if (!empty($json->html)) {
            $res .= $json->html;

        } elseif ($json->content !== null) {
            $res .= $this->toHtml($json->content);
        }
        $res .= '</' . $tag . '>';

        return $res;
    }

    public static function attrEscape($s, $enc = null) {
        if (is_bool($s)) {
            return $s ? 'true' : 'false';
        }
        return htmlspecialchars($s, ENT_QUOTES);
    }

    public static function toBemCssClasses($json, $base, $parentBase = null, $nobase = false) {
        $res = '';

        if ($parentBase !== $base) {
            if ($parentBase) {
                $res .= ' ';
            }
            $res .= $base;
        }

        // if (mods = json.elem && json.elemMods || json.mods)
        $mods = $json->getMods();
        if ($mods) {
            foreach ($mods as $k => $mod) {
                if ($mod || $mod === 0) {
                    $res .= ' ' . ($nobase ? '' : $base) . '_' . $k . ($mod === true ? '' : '_' . $mod);
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

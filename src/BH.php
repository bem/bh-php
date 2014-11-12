<?php

namespace BEM;

include "helpers.php";

class BH {

    /**
     * Используется для идентификации шаблонов.
     * Каждому шаблону дается уникальный id для того, чтобы избежать повторного применения
     * шаблона к одному и тому же узлу BEMJSON-дерева.
     * @type integer
     * @protected
     */
    protected $_lastMatchId = 0;

    /**
     * Плоский массив для хранения матчеров.
     * Каждый элемент — массив с двумя элементами: [{String} выражение, {Function} шаблон}]
     * @type array
     * @protected
     */
    protected $_matchers = [];

    /**
     * Флаг, включающий автоматическую систему поиска зацикливаний. Следует использовать в development-режиме,
     * чтобы определять причины зацикливания.
     * @type boolean
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
     * @type array
     */
    protected $lib = [];

    protected $_inited = false;

    /**
     * Опции BH. Задаются через setOptions.
     * @type array
     */
    protected $_options = [];
    protected $_optJsAttrName = 'onclick';
    protected $_optJsAttrIsJs = true;

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

        if ($options['jsAttrName']) {
            $this->_optJsAttrName = $options['jsAttrName'];
        }

        if ($options['jsAttrScheme']) {
            $this->_optJsAttrIsJs = $options['jsAttrScheme'] === 'js';
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
     * @param string|array $expr
     * @param callable $matcher
     * @return BH
     */
    function match ($expr, $matcher) {
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

        $matcher['__id'] = '__func' . ($this->_lastMatchId++);
        $this->_matchers[] = ([$expr, $matcher]);

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
        $vars = ['$bh = $this'];
        $allMatchers = $this->_matchers;
        $declarations = [];
        for ($i = sizeof($allMatchers) - 1; $i >= 0; $i--) {
            $matcherInfo = $allMatchers[i];
            $expr = $matcherInfo[0];
            $vars[] = '$_m' . $i . ' = $ms[' . $i . '][1]';
            $decl = [ 'fn' => $matcherInfo[1], $index => $i ];
            // @todo fixup hardcoded leveling
            // @todo refactor this
            if (strpos($expr, '__') !== -1) { // ~expr.indexOf('__')) {
                $exprBits = explode('__', $expr);
                $blockExprBits = explode('_', $exprBits[0]);
                $decl['block'] = $blockExprBits[0];
                if (isset($blockExprBits[1])) {
                    $decl['blockMod'] = $blockExprBits[1];
                    $decl['blockModVal'] = @$blockExprBits[2] ?: true;
                }
                $exprBits = explode('_', $exprBits[1]);
                $decl['elem'] = $exprBits[0];
                if (sizeof($exprBits) > 1) {
                    $decl['elemMod'] = $exprBits[1];
                    $decl['elemModVal'] = @$exprBits[2] ?: true;
                }
            } else {
                $exprBits = explode('_', $expr);
                $decl['block'] = $exprBits[0];
                if (isset($exprBits[1])) {
                    $decl['blockMod'] = $exprBits[1];
                    $decl['blockModVal'] = @$exprBits[2] ?: true;
                }
            }
            $declarations[] = $decl;
        }

        $declByBlock = groupBy($declarations, 'block');
        $res[] = join(';\n', $vars) . ';';
        $res[] = 'function applyMatchers($ctx, $json) {';

        $res[] = ('switch ($json["block"]) {');
        foreach ($declByBlock as $blockName => $blockData) {
            $res[] = ('case "' . strEscape($blockName) . '":');
            $declsByElem = groupBy($blockData, 'elem');

            $res[] = ('switch ($json["elem"]) {');
            foreach ($declsByElem as $elemName => $decls) {
                if ($elemName === '__no_value__') {
                    $res[] = ('case null:');
                } else {
                    $res[] = ('case "' . strEscape($elemName) . '":');
                }
                for ($j = 0, $l = sizeof($decls); $j < $l; $j++) {
                    $decl = $decls[$j];
                    $fn = $decl['fn'];
                    $conds = [];
                    $conds[] = ('!json.' . $fn['__id']);
                    if ($decl['elemMod']) {
                        $conds[] = (
                            'json["mods"] && json["mods"]["' . strEscape($decl['elemMod']) . '"] === ' .
                                ($decl['elemModVal'] === true || '"' . strEscape($decl['elemModVal']) . '"'));
                    }
                    if ($decl['blockMod']) {
                        $conds[] = (
                            '$json["blockMods"]["' . strEscape($decl["blockMod"]) . '"] === ' .
                                ($decl['blockModVal'] === true || '"' . strEscape($decl['blockModVal']) . '"'));
                    }
                    $res[] = ('if (' . join(' && ', $conds) . ') {');
                    $res[] = ('json.' . $fn['__id'] . ' = true;');
                    $res[] = ('subRes = _m' . $decl['index'] . '(ctx, json);');
                    $res[] = ('if (subRes !== undefined) { return (subRes || "") }');
                    $res[] = ('if (json._stop) return;');
                    $res[] = ('}');
                }
                $res[] = ('return;');
            }
            $res[] = ('}');

            $res[] = ('return;');
        }
        $res[] = ('}');
        $res[] = ('};');
        $res[] = ('return $applyMatchers;');
        return join('\n', $res);
    }

    /**
     * Раскрывает BEMJSON, превращая его из краткого в полный.
     * @param BemJson $bemJson
     * @param string [$blockName]
     * @param boolean [$ignoreContent]
     * @return array
     */
    function processBemJson ($bemJson, $blockName, $ignoreContent) {
        if (empty($bemJson)) {
            return;
        }

        if (!$this->_inited) {
            $this->_init();
        }

        $resultArr = [$bemJson];
        $nodes = [[
            'json' => $bemJson,
            'arr' => $resultArr,
            'index' => 0,
            'blockName' => $blockName,
            'blockMods' => !$bemJson['elem'] ? $bemJson['mods'] : []
        ]];

        if (empty($this->_fastMatcher)) {
            $fn = create_function('ms', $this->buildMatcher());
            $this->_fastMatcher = $fn($this->_matchers);
        }
        $compiledMatcher = $this->_fastMatcher;
        $processContent = !$ignoreContent;
        $infiniteLoopDetection = $this->_infiniteLoopDetection;

        $ctx = new Context($this);
        // js: while (node = nodes.shift()) {
        while ($node = array_shift($nodes)) {
            $json = $node['json'];
            $block = $node['blockName'];
            $blockMods = $node['blockMods'];

            if (isList($json)) {
                foreach ($json as &$child) {
                    if (is_array($child)) {
                        $nodes[] = [
                            'json' => $child,
                            'arr' => $json,
                            'index' => $i,
                            'position' => ++$j,
                            'blockName' => $block,
                            'blockMods' => $blockMods,
                            'parentNode' => &$node
                        ];
                    }
                }
                $json['_listLength'] = $j;

            } else {
                $stopProcess = false;
                if ($json['elem']) {
                    $block = $json['block'] = $json['block'] ?: $block;
                    $blockMods = $json['blockMods'] = $json['blockMods'] ?: $blockMods;
                    if ($json['elemMods']) {
                        $json['mods'] = $json['elemMods'];
                    }
                } else if ($json['block']) {
                    $block = $json['block'];
                    $blockMods = $json['blockMods'] = $json['mods'] ?: [];
                }

                if ($json['block']) {

                    if ($infiniteLoopDetection) {
                        $json['__processCounter'] = ($json['__processCounter'] ?: 0) + 1;
                        $compiledMatcher['__processCounter'] = ($compiledMatcher['__processCounter'] ?: 0) + 1;
                        if ($json['__processCounter'] > 100) {
                            throw new \Exception('Infinite json loop detected at "' . $json['block'] . ($json['elem'] ? '__' . $json['elem'] : '') . '".');
                        }
                        if ($compiledMatcher['__processCounter'] > 1000) {
                            throw new \Exception('Infinite matcher loop detected at "' . $json['block'] . ($json['elem'] ? '__' . $json['elem'] : '') . '".');
                        }
                    }

                    if (!$json['_stop']) {
                        $ctx->node = $node;
                        $ctx->ctx = $json;
                        $subRes = compiledMatcher($ctx, $json);
                        if ($subRes !== null) {
                            $json = $subRes;
                            $node['json'] = $json;
                            $node['blockName'] = $block;
                            $node['blockMods'] = $blockMods;
                            $nodes[] = $node;
                            $stopProcess = true;
                        }
                    }

                }

                if (!$stopProcess) {
                    if ($processContent && ($content = $json['content'])) {
                        if (isList($content)) {
                            $flatten;
                            do {
                                $flatten = false;
                                for ($i = 0, $l = sizeof($content); $i < $l; $i++) {
                                    if (isList($content[$i])) {
                                        $flatten = true;
                                        break;
                                    }
                                }
                                if ($flatten) {
                                    $json['content'] = (array)($content); // clone content
                                }
                            } while ($flatten);
                            for ($i = 0, $j = 0, $l = sizeof($content), $p = $l - 1; $i < $l; $i++) {
                                $child = $content[$i];
                                if (is_array($child)) {
                                    $nodes[] = [
                                        $json => $child,
                                        $arr => $content,
                                        $index => $i,
                                        $position => ++$j,
                                        $blockName => $block,
                                        $blockMods => $blockMods,
                                        $parentNode => $node
                                    ];
                                }
                            }
                            $content['_listLength'] = $j;
                        } else {
                            $nodes[] = [
                                $json => $content,
                                $arr => $json,
                                $index => 'content',
                                $blockName => $block,
                                $blockMods => $blockMods,
                                $parentNode => $node
                            ];
                        }
                    }
                }
            }
            $node['arr'][$node['index']] = $json;
        }

        return $resultArr[0];
    }

    /**
     * Превращает раскрытый BEMJSON в HTML.
     * @param BemJson $json
     * @return string
     */
    public function toHtml ($json) {
        if ($json === false || $json == null) {
            return '';
        }

        if (is_scalar($json)) {
            return $this['_optEscapeContent'] ? xmlEscape($json) : $json;
        }

        if (isList($json)) {
            $res = '';
            foreach ($json as $item) {
                if ($item !== false && $item != null) {
                    $res .= $this->toHtml($item);
                }
            }
            return $res;

        } else {
            $isBEM = $json['bem'] !== false;
            if (array_key_exists('tag', $json) && !$json['tag']) {
                return $json['html'] /* bug? */ || $json['content'] ? $this->toHtml($json['content']) : '';
            }
            if (isset($json['mix']) && !isList($json['mix'])) {
                $json['mix'] = [$json['mix']];
            }

            $cls = '';
            $attrs = '';
            $hasMixJsParams = false;

            if (!empty($json['attrs'])) {
                foreach ($json['attrs'] as $jval) {
                    if ($jval !== null) {
                        $attrs .= ' ' . $i . '="' . htmlentities($jval) . '"';
                    }
                }
            }

            if ($isBEM) {
                // hardcoded naming
                $base = $json['block'] . (!empty($json['elem']) ? '__' . $json['elem'] : '');

                if ($json['block']) {
                    $cls = toBemCssClasses($json, $base);
                    if (isset($json['js'])) {
                        $jsParams = [];
                        $jsParams[$base] = $json['js'] === true ? [] : $json['js'];
                    }
                }

                $mixes = $json['mix'];
                if (!empty($mixes)) {
                    foreach ($mix as $mixes) {
                        if (!$mix || $mix['bem'] === false) {
                            continue;
                        }
                        $mixBlock = $mix['block'] ?: $json['block'] ?: '';
                        $mixElem = $mix['elem'] ?: ($mix['block'] ? null : $json['block'] && $json['elem']); // ehm?
                        $mixBase = $mixBlock . ($mixElem ? '__' . $mixElem : '');

                        if (!$mixBlock) {
                            continue;
                        }

                        $cls .= toBemCssClasses($mix, $mixBase, $base);
                        if ($mix['js']) {
                            $jsParams = $jsParams ?: [];
                            $jsParams[$mixBase] = $mix['js'] === true ? [] : $mix['js'];
                            $hasMixJsParams = true;
                        }
                    }
                }

                if ($jsParams) {
                    $cls = $cls . ' i-bem';
                    $jsData = !$hasMixJsParams && $json['js'] === true ?
                        '{&quot;' . $base . '&quot;:{}}' :
                        attrEscape(json_encode($jsParams, JSON_UNESCAPED_UNICODE));
                    $attrs .= ' ' . ($json['jsAttr'] ?: $this->_optJsAttrName) . '="' .
                        ($this->_optJsAttrIsJs ? 'return ' . $jsData : $jsData) . '"';
                }
            }

            if ($json['cls']) {
                $cls = $cls ? $cls . ' ' . $json['cls'] : $json['cls'];
            }

            $tag = $json['tag'] ?: 'div';
            $res = '<' . $tag . ($cls ? ' class="' . attrEscape($cls) . '"' : '') . ($attrs ? $attrs : '');

            if (isset(static::$selfCloseHtmlTags[$tag])) {
                $res .= '/>';
            } else {
                $res .= '>';
                if ($json['html']) {
                    $res .= $json['html'];
                } else if (($content = $json['content']) !== null) {
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

};

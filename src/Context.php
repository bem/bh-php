<?php

namespace BEM;

/**
 * BemJson node context class
 */
class Context {
    /**
     * Shared genId for DOM nodes
     * @var integer
     */
    static $lastGenId = 0;

    protected $_expandoId = null;
    public $ctx = null;

    /**
     * Context constructor
     * @param \BEM\BH $bh parent class
     */
    function __construct($bh) {
        $this->bh = $bh;
        $this->_expandoId = floor(microtime(1)*1000);
    }

    /**
     * Проверяет, является ли переменная примитивом
     * ```javascript
     * bh.match('link', function(ctx) {
     *     ctx.tag(ctx.isSimple(ctx.content()) ? 'span' : 'div');
     * });
     * ```
     * @param mixed $obj
     * @return boolean
     */
    function isSimple ($obj) {
        return is_scalar($obj);
    }

    /**
     * Расширяет один объект свойствами другого (других)
     * Аналог jQuery.extend.
     * ```javascript
     * obj = ctx.extend(obj, {a: 1});
     * ```
     * @param array $target
     * @return array
     */
    function extend ($target) {
        if (!$target || !is_array($target)) { // probably wrong
            $target = [];
        }

        // array_merge?
        $args = func_get_args();
        for ($i = 1, $l = sizeof($args); $i < $l; $i++) {
            $obj = $args[$i];
            if (!$obj) {
                continue;
            }
            foreach ($obj as $k => $v) {
                $target[$k] = $v;
            }
        }

        return $target;
    }

    /**
     * Возвращает позицию элемента в рамках родителя.
     * Отсчет производится с 1 (единицы).
     * ```javascript
     * bh.match('list__item', function(ctx) {
     *     ctx.mod('pos', ctx.position());
     * });
     * ```
     * @return integer
     */
    function position () {
        $node = $this->node;
        return $node['index'] === 'content' ? 1 : $node['position'];
    }

    /**
     * Возвращает true, если текущий BEMJSON-элемент первый в рамках родительского BEMJSON-элемента.
     * ```javascript
     * bh.match('list__item', function(ctx) {
     *     if (ctx.isFirst()) {
     *         ctx.mod('first', 'yes');
     *     }
     * });
     * ```
     * @return boolean
     */
    function isFirst () {
        $node = $this->node;
        return $node['index'] === 'content' || $node['position'] === 1;
    }

    /**
     * Возвращает true, если текущий BEMJSON-элемент последний в рамках родительского BEMJSON-элемента.
     * ```javascript
     * bh.match('list__item', function(ctx) {
     *     if (ctx.isLast()) {
     *         ctx.mod('last', 'yes');
     *     }
     * });
     * ```
     * @return boolean
     */
    function isLast () {
        $node = $this->node;
        return $node['index'] === 'content' || $node['position'] === $node['arr']['_listLength'];
    }

    /**
     * Передает параметр вглубь BEMJSON-дерева.
     * **force** — задать значение параметра даже если оно было задано ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.content({ elem: 'control' });
     *     ctx.tParam('value', ctx.param('value'));
     * });
     * bh.match('input__control', function(ctx) {
     *     ctx.attr('value', ctx.tParam('value'));
     * });
     * ```
     * @param string $key
     * @param mixed [$value]
     * @param boolean [$force]
     * @return Context|mixed
     */
    function tParam ($key, $value = null, $force = false) {
        $keyName = '__tp_' + $key;
        $node = $this->node;

        if (func_num_args() > 1) {
            if ($force || !isset($node[$keyName])) {
                $node[$keyName] = $value;
            }
            return $this;
        }

        while ($node) {
            if (isset($node[$keyName])) {
                return $node[$keyName];
            }
            $node = $node['parentNode'];
        }

        return null;
    }

    /**
     * Применяет матчинг для переданного фрагмента BEMJSON.
     * Возвращает результат преобразований.
     * @param BemJson bemJson
     * @return array
     */
    function apply ($bemJson) {
        $prevCtx = $this->ctx;
        $prevNode = $this->node;
        $res = $this->bh->processBemJson($bemJson, $prevCtx['block']);
        $this->ctx = $prevCtx;
        $this->node = $prevNode;
        return $res;
    }

    /**
     * Выполняет преобразования данного BEMJSON-элемента остальными шаблонами.
     * Может понадобиться, например, чтобы добавить элемент в самый конец содержимого, если в базовых шаблонах в конец содержимого добавляются другие элементы.
     * Пример:
     * ```javascript
     * bh.match('header', function(ctx) {
     *    ctx.content([
     *        ctx.content(),
     *        { elem: 'under' }
     *    ], true);
     * });
     * bh.match('header_float_yes', function(ctx) {
     *    ctx.applyBase();
     *    ctx.content([
     *        ctx.content(),
     *        { elem: 'clear' }
     *    ], true);
     * });
     * ```
     * @return {Ctx}
     */
    function applyBase () {
        $node = $this->node;
        $json = $node['json'];

        if (!$json['elem'] && $json['mods']) {
            $json['blockMods'] = $json['mods'];
        }

        $block = $json['block'];
        $blockMods = $json['blockMods'];

        $subRes = $this->bh->_fastMatcher($this, $json);
        if ($subRes !== null) {
            $this->ctx = $node['arr'][$node['index']] = $node['json'] = $subRes;
            $node['blockName'] = $block; // need check
            $node['blockMods'] = $blockMods;
        }

        return $this;
    }

    /**
     * Останавливает выполнение прочих шаблонов для данного BEMJSON-элемента.
     * Пример:
     * ```javascript
     * bh.match('button', function(ctx) {
     *     ctx.tag('button', true);
     * });
     * bh.match('button', function(ctx) {
     *     ctx.tag('span');
     *     ctx.stop();
     * });
     * ```
     * @return Context
     */
    function stop () {
        $this->ctx->_stop = true;
        return $this;
    }

    /**
     * Возвращает уникальный идентификатор. Может использоваться, например,
     * чтобы задать соответствие между `label` и `input`.
     * @return string
     */
    function generateId () {
        return 'uniq' . $this->_expandoId . (++ static::$lastGenId);
    }

    /**
     * Возвращает/устанавливает модификатор в зависимости от аргументов.
     * **force** — задать модификатор даже если он был задан ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.mod('native', 'yes');
     *     ctx.mod('disabled', true);
     * });
     * bh.match('input_islands_yes', function(ctx) {
     *     ctx.mod('native', '', true);
     *     ctx.mod('disabled', false, true);
     * });
     * ```
     * @param string $key
     * @param string|boolean [$value]
     * @param boolean [$force]
     * @return string|null|Context
     */
    function mod ($key, $value, $force) {
        if (func_num_args() > 1) {
            $mods = @$this->ctx['mods'] ?: ($this->ctx['mods'] = []);
            $mods[$key] = !isset($mods[$key]) || $force ? $value : $mods[$key];
            return $this;
        } else {
            $mods = @$this->ctx['mods'];
            return $mods ? $mods[$key] : null;
        }
    }

    /**
     * Возвращает/устанавливает модификаторы в зависимости от аргументов.
     * **force** — задать модификаторы даже если они были заданы ранее.
     * ```javascript
     * bh.match('paranja', function(ctx) {
     *     ctx.mods({
     *         theme: 'normal',
     *         disabled: true
     *     });
     * });
     * ```
     * @param array [$values]
     * @param boolean [$force]
     * @return array|Context
     */
    function mods ($values = null, $force = false) {
        $mods = @$this->ctx['mods'] ?: ($this->ctx['mods'] = []);
        if ($values === null) {
            return $mods;
        }

        $this->ctx['mods'] = $force ?
            $this->extend($mods, $values)
            : $this->extend($values, $mods);

        return $this;
    }

    /**
     * Возвращает/устанавливает тег в зависимости от аргументов.
     * **force** — задать значение тега даже если оно было задано ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.tag('input');
     * });
     * ```
     * @param string [$tagName]
     * @param boolean [$force]
     * @return string|null|Context
     */
    function tag ($tagName = null, $force = false) {
        if ($tagName === null) {
            return isset($this->ctx['tag']) ? $this->ctx['tag'] : null;
        }

        $this->ctx['tag'] = empty($this->ctx['tag']) || $force ? $tagName : $this->ctx['tag'];

        return $this;
    }

    /**
     * Возвращает/устанавливает значение mix в зависимости от аргументов.
     * При установке значения, если force равен true, то переданный микс заменяет прежнее значение,
     * в противном случае миксы складываются.
     * ```javascript
     * bh.match('button_pseudo_yes', function(ctx) {
     *     ctx.mix({ block: 'link', mods: { pseudo: 'yes' } });
     *     ctx.mix([
     *         { elem: 'text' },
     *         { block: 'ajax' }
     *     ]);
     * });
     * ```
     * @param array|BemJson [$mix]
     * @param boolean [$force]
     * @return array|null|Context
     */
    function mix ($mix = null, $force = false) {
        if ($mix === null) {
            return isset($this->ctx['mix']) ? $this->ctx['mix'] : null;
        }

        if ($force) {
            $this->ctx['mix'] = $mix;
            return $this;
        }

        if ($this->ctx['mix']) {
            $this->ctx['mix'] = is_array($this->ctx['mix']) ?
                array_merge($this->ctx['mix'], (array)$mix) :
                array_merge([$this->ctx['mix']], (array)$mix);
        } else {
            $this->ctx['mix'] = $mix;
        }

        return $this;
    }

    /**
     * Возвращает/устанавливает значение атрибута в зависимости от аргументов.
     * **force** — задать значение атрибута даже если оно было задано ранее.
     * @param string $key
     * @param string [$value]
     * @param boolean [$force]
     * @return string|null|Context
     */
    function attr ($key, $value = null, $force = false) {
        if ($value === null) {
            return isset($this->ctx['attrs'][$key]) ? $this->ctx['attrs'][$key] : null;
        }

        if (empty($this->ctx['attrs'])) {
            $this->ctx['attrs'] = [];
        }
        $this->ctx['attrs'] = empty($attrs[$key]) || $force ? $value : $attrs[$key];

        return $this;
    }

    /**
     * Возвращает/устанавливает атрибуты в зависимости от аргументов.
     * **force** — задать атрибуты даже если они были заданы ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.attrs({
     *         name: ctx.param('name'),
     *         autocomplete: 'off'
     *     });
     * });
     * ```
     * @param array [$values]
     * @param boolean [$force]
     * @return {Object|Context
     */
    function attrs ($values = null, $force = false) {
        $attrs = $this->ctx['attrs'] || [];
        if ($values === null) {
            return $attrs;
        }

        $this->ctx['attrs'] = $force ? $this->extend($attrs, $values) : $this->extend($values, $attrs);

        return $this;
    }

    /**
     * Возвращает/устанавливает значение bem в зависимости от аргументов.
     * **force** — задать значение bem даже если оно было задано ранее.
     * Если `bem` имеет значение `false`, то для элемента не будут генерироваться BEM-классы.
     * ```javascript
     * bh.match('meta', function(ctx) {
     *     ctx.bem(false);
     * });
     * ```
     * @param boolean [$bem]
     * @param boolean [$force]
     * @return boolean|null|Context
     */
    function bem ($bem = null, $force = false) {
        if ($bem === null) {
            return isset($this->ctx['bem']) ? $this->ctx['bem'] : null;
        }

        $this->ctx['bem'] = empty($this->ctx['bem']) || $force ? $bem : $this->ctx['bem'];

        return $this;
    }

    /**
     * Возвращает/устанавливает значение `js` в зависимости от аргументов.
     * **force** — задать значение `js` даже если оно было задано ранее.
     * Значение `js` используется для инициализации блоков в браузере через `BEM.DOM.init()`.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.js(true);
     * });
     * ```
     * @param boolean|Object} [$js]
     * @param boolean [$force]
     * @return boolean|Object|Context
     */
    function js ($js = null, $force = false) {
        if ($js === null) {
            return isset($this->ctx['js']) ? $this->ctx['js'] : null;
        }

        $this->ctx['js'] = $force ?
            ($js === true ? [] : $js) :
            $js ? $this->extend($this->ctx['js'], $js) : $this->ctx['js'];

        return $this;
    }

    /**
     * Возвращает/устанавливает значение CSS-класса в зависимости от аргументов.
     * **force** — задать значение CSS-класса даже если оно было задано ранее.
     * ```javascript
     * bh.match('page', function(ctx) {
     *     ctx.cls('ua_js_no ua_css_standard');
     * });
     * ```
     * @param string [$cls]
     * @param boolean [$force]
     * @return string|Context
     */
    function cls ($cls = null, $force = false) {
        if ($cls === null) {
            return empty($this->ctx['cls']) ? null : $this->ctx['cls'];
        }

        $this->ctx['cls'] = empty($this->ctx['cls']) || $force ? $cls : $this->ctx['cls'];

        return $this;
    }

    /**
     * Возвращает/устанавливает параметр текущего BEMJSON-элемента.
     * **force** — задать значение параметра, даже если оно было задано ранее.
     * Например:
     * ```javascript
     * // Пример входного BEMJSON: { block: 'search', action: '/act' }
     * bh.match('search', function(ctx) {
     *     ctx.attr('action', ctx.param('action') || '/');
     * });
     * ```
     * @param string $key
     * @param mixed [$value]
     * @param boolean [$force]
     * @return Context|mixed
     */
    function param ($key, $value = null, $force = false) {
        if ($value === null) {
            return isset($this->ctx[$key]) ? $this->ctx[$key] : null;
        }

        $this->ctx[$key] = empty($this->ctx[$key]) || $force ? $value : $this->ctx[$key];

        return $this;
    }

    /**
     * Возвращает/устанавливает защищенное содержимое в зависимости от аргументов.
     * **force** — задать содержимое даже если оно было задано ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.content({ elem: 'control' });
     * });
     * ```
     * @param BemJson [$value]
     * @param boolean [$force]
     * @return BemJson|Context
     */
    function content ($value = null, $force = false) {
        if (func_num_args() === 0) {
            return isset($this->ctx['content']) ? $this->ctx['content'] : null;
        }

        $this->ctx['content'] = empty($this->ctx['content']) || $force ? $value : $this->ctx['content'];

        return $this;
    }

    /**
     * Возвращает/устанавливает незащищенное содержимое в зависимости от аргументов.
     * **force** — задать содержимое даже если оно было задано ранее.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     ctx.html({ elem: 'control' });
     * });
     * ```
     * @param string [$value]
     * @param boolean [$force]
     * @return string|Context
     */
    function html ($value = null, $force = false) {
        if (func_num_args() === 0) {
            return isset($this->ctx['html']) ? $this->ctx['html'] : null;
        }

        $this->ctx['html'] = empty($this->ctx['html']) || $force ? $value : $this->ctx['html'];

        return $this;
    }

    /**
     * Возвращает текущий фрагмент BEMJSON-дерева.
     * Может использоваться в связке с `return` для враппинга и подобных целей.
     * ```javascript
     * bh.match('input', function(ctx) {
     *     return {
     *         elem: 'wrapper',
     *         content: ctx.json()
     *     };
     * });
     * ```
     * @return array
     */
    function json () {
        return $this->ctx;
    }

}

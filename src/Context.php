<?php

namespace BEM;

/**
 * BH engine context
 */
class Context
{
    /**
     * Shared genId for DOM nodes
     * @var integer
     */
    public static $lastGenId = 0;

    protected $_expandoId = null;

    /**
     * @var Json
     */
    public $ctx = null;

    /**
     * @var BH
     */
    public $bh;

    public $mix;

    /**
     * Context constructor
     * @param \BEM\BH $bh parent class
     */
    public function __construct($bh)
    {
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
    public function isSimple($obj)
    {
        return is_scalar($obj) || is_null($obj);
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
    public function extend($target)
    {
        if (!$target || is_scalar($target)) {
            $target = [];
        }

        $args = func_get_args();
        $isArr = is_array($target) || $target instanceof \ArrayAccess;
        $isObj = !$isArr && is_object($target);

        foreach ($args as $obj) {
            if (!$obj || (!is_array($obj) && !is_object($obj))) {
                continue;
            }
            $obj = $obj instanceof \Iterator ? $obj : (array)$obj;
            foreach ($obj as $k => $v) {
                if ($isArr) {
                    $target[$k] = $v;
                } elseif ($isObj) {
                    $target->$k = $v;
                } else {
                    // skip
                }
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
    public function position()
    {
        $node = $this->node;
        return $node->index === 'content' ? 1 :
            isset($node->position) ? $node->position : null;
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
    public function isFirst()
    {
        $node = $this->node;
        return $node->index === 'content'
            || isset($node->position) && $node->position === 1;
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
    public function isLast()
    {
        $node = $this->node;
        return $node->index === 'content'
            || isset($node->position) && $node->position === $node->arr->_listLength;
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
    public function tParam($key, $value = null, $force = false)
    {
        $node = $this->node;

        if (func_num_args() > 1) {
            if ($force || !isset($node->tParams[$key])) {
                $node->tParams[$key] = $value;
            }
            return $this;
        }

        while ($node) {
            if (isset($node->tParams[$key])) {
                return $node->tParams[$key];
            }
            $node = $node->parentNode;
        }

        return null;
    }

    /**
     * Применяет матчинг для переданного фрагмента BEMJSON.
     * Возвращает результат преобразований.
     * @param BemJson $bemJson
     * @return array
     */
    public function process($bemJson)
    {
        $prevCtx = $this->ctx;
        $prevNode = $this->node;
        $res = $this->bh->processBemJson($bemJson, $prevCtx->block);
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
     * @return Context
     */
    public function applyBase()
    {
        $node = $this->node;
        $json = $node->json;

        $block = $json->block;
        $blockMods = $json->mods;

        $fm = $this->bh->getMatcher();
        $subRes = $fm($this, $json);
        if ($subRes !== null) {
            $this->ctx = $node->arr[$node->index] = $node->json = JsonCollection::normalize($subRes);
            $node->block = $block; // need check
            $node->mods = $blockMods;
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
    public function stop()
    {
        $this->ctx->_stop = true;
        return $this;
    }

    /**
     * Возвращает уникальный идентификатор. Может использоваться, например,
     * чтобы задать соответствие между `label` и `input`.
     * @return string
     */
    public function generateId()
    {
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
    public function mod($key, $value = null, $force = false)
    {
        $field = $this->ctx->elem ? 'elemMods' : 'mods';
        if (func_num_args() > 1) {
            $mods = $this->ctx->$field;
            $mods->$key = !key_exists($key, $mods) || $force ? $value : $mods->$key;
            return $this;
        }
        return isset($this->ctx->$field) && key_exists($key, $this->ctx->$field)
            ? $this->ctx->$field->$key : null;
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
    public function mods($values = null, $force = false)
    {
        $field = $this->ctx->elem ? 'elemMods' : 'mods';
        $mods = $this->ctx->$field;
        if ($values === null) {
            return $mods;
        }

        $this->ctx->$field = $force ?
            $this->extend($mods, $values) :
            $this->extend(is_object($values) ? $values : new Mods($values), $mods);

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
     * @param string|null [$tagName]
     * @param boolean [$force]
     * @return string|null|Context
     */
    public function tag($tagName = null, $force = false)
    {
        if ($tagName === null) {
            return $this->ctx->tag;
        }

        if ($this->ctx->tag === null || $force) {
            $this->ctx->tag = $tagName;
        }

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
    public function mix($mix = null, $force = false)
    {
        if ($mix === null) {
            return $this->ctx->mix;
        }

        if ($force || !$this->ctx->mix) {
            $this->ctx->mix = JsonCollection::normalize($mix);
            return $this;
        }

        $this->ctx->mix->append($mix);

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
    public function attr($key, $value = null, $force = false)
    {
        $attrs = $this->ctx->attrs ?: ($this->ctx->attrs = []);
        if (func_num_args() === 1) {
            return isset($attrs[$key]) ? $attrs[$key] : null;
        }

        $this->ctx->attrs[$key] = !array_key_exists($key, $attrs) || $force ? $value : $attrs[$key];

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
    public function attrs(array $values = null, $force = false)
    {
        $attrs = $this->ctx->attrs ?: [];
        if ($values === null) {
            return $attrs;
        }

        $attrs = (array)$attrs;
        $this->ctx->attrs = $force ? $values + $attrs : $attrs + $values;

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
    public function bem($bem = null, $force = false)
    {
        if ($bem === null) {
            return isset($this->ctx->bem) ? $this->ctx->bem : null;
        }

        $this->ctx->bem = empty($this->ctx->bem) || $force ? $bem : $this->ctx->bem;

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
    public function js($js = null, $force = false)
    {
        if (func_num_args() === 0) {
            return $this->ctx->js;
        }

        // this.ctx.js = force ?
        //     (js === true ? {} : js) :
        //     js ? this.extend(this.ctx.js, js) : this.ctx.js;
        $this->ctx->js = $this->ctx->js === null || $force ?
            ($js === true ? [] : $js) :
            ((is_array($this->ctx->js) ? $this->ctx->js : []) + ($js && is_array($js) ? $js : []));

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
    public function cls($cls = null, $force = false)
    {
        if ($cls === null) {
            return empty($this->ctx->cls) ? null : $this->ctx->cls;
        }

        $this->ctx->cls = empty($this->ctx->cls) || $force ? $cls : $this->ctx->cls;

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
    public function param($key, $value = null, $force = false)
    {
        // get
        if (func_num_args() === 1) {
            return key_exists($key, $this->ctx) ? $this->ctx->$key : null;
        }
        // set
        if (!key_exists($key, $this->ctx) || $force) {
            $this->ctx->$key = $value;
        }
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
    public function content($value = null, $force = false)
    {
        if (func_num_args() === 0) {
            return !is_null($this->ctx->content) ? $this->ctx->content : null;
        }

        if (is_null($this->ctx->content) || $force) {
            $this->ctx->setContent($value);
        }

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
    public function html($value = null, $force = false)
    {
        if (func_num_args() === 0) {
            return $this->ctx->html;
        }

        $this->ctx->html = is_null($this->ctx->html) || $force ? $value : $this->ctx->html;

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
     * @return Json
     */
    public function json()
    {
        return $this->ctx;
    }

    /**
     * Конвертирует входной массив в Json или JsonCollection
     * @param array $bemjson
     * @return Json
     */
    public function phpize($bemjson)
    {
        if (isList($bemjson)) {
            return JsonCollection::normalize($bemjson);
        }
        return JsonCollection::normalizeItem($bemjson);
    }

    /**
     * Array.isArray analogue
     * @param mixed $ex
     * @return boolean
     */
    public function isArray($ex)
    {
        return isList($ex);
    }
}

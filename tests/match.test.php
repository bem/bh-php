<?php

use BEM\BH;

class bhMatchTest extends PHPUnit_Framework_TestCase {

    /**
     * @before
     */
    function setupBhInstance () {
        $this->bh = new BH();
    }

    function test_it_should_not_create_invalid_matcher () {
        $this->bh->match(false, function() {});
        $this->assertEquals('', $this->bh->apply(''));
    }

    function test_it_should_allow_to_use_chaining () {
        $this->assertEquals(
            $this->bh,
            $this->bh->match('button', function() {})
        );
    }

    function test_it_should_match_on_elem_of_block_with_modifier () {
        $this->bh->match('button_disabled__control', function ($ctx) {
            $ctx->tag('input');
        });

        $this->assertEquals(
            '<div class="button button_disabled"><input class="button__control"/></div>',
            $this->bh->apply([
                'block' => 'button',
                'mods' => ['disabled' => true],
                'content' => ['elem' => 'control']
            ])
        );
    }

    function test_it_should_allow_to_use_a_few_matchers_in_one_call_n1 () {
        $this->bh->match([
            'button' => function ($ctx) {
                $ctx->tag('button');
            },
            'button_type_submit' => function ($ctx) {
                $ctx->attr('type', 'submit');
            }
        ]);

        $this->assertEquals(
            '<button class="button button_type_submit" type="submit"></button>',
            $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'submit']])
        );
    }

    function test_it_should_allow_to_use_a_few_matchers_in_one_call_n2 () {
        $this->bh->match(
            [
                'item__mark',
                'item__text'
            ],
            function ($ctx) {
                $ctx->tag('span');
            }
        );

        $this->assertEquals(
            '<div class="item">' .
                '<span class="item__mark">*</span>' .
                '<span class="item__text">foobar</span>' .
            '</div>',
            $this->bh->apply([
                'block' => 'item',
                'content' => [
                    ['elem' => 'mark', 'content' => '*'],
                    ['elem' => 'text', 'content' => 'foobar']
                ]
            ])
        );
    }

    function test_it_should_match_string_mods () {
        $this->bh->match('button_type_link', function ($ctx) {
            $ctx->tag('a');
        });
        $this->assertEquals(
            '<a class="button button_type_link"></a>',
            $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'link']])
        );
    }

    function test_it_should_match_new_mods () {
        $this->bh->match('button_disabled', function($ctx) {
            $ctx->tag('span');
        });
        $this->bh->match('button', function($ctx) {
            $ctx->mod('disabled', true);
            $ctx->applyBase();
        });
        $this->assertEquals(
            '<span class="button button_disabled"></span>',
            $this->bh->apply([ 'block' => 'button' ]));
    }

    function test_it_should_match_new_mods2 () {
        $this->bh->match('button_visible', function($ctx) {
            $ctx->tag('a');
        });
        $this->bh->match('button_disabled', function($ctx) {
            $ctx->mod('visible', true);
            $ctx->mix([ 'block' => 'clearfix' ]);
        });
        $this->bh->match('button', function($ctx) {
            $ctx->mod('disabled', true);
        });
        $this->assertEquals(
            '<a class="button button_disabled button_visible clearfix"></a>',
            $this->bh->apply([ 'block' => 'button', 'mods' => [ 'disabled' => true ] ]));
    }

    function test_it_should_not_fail_on_non_identifier_mods () {
        $this->bh->match('button_is-bem_yes__control', function ($ctx) {
            $ctx->content('Hello');
        });
        $this->assertEquals(
            '<div class="button button_is-bem_yes"><div class="button__control">Hello</div></div>',
            $this->bh->apply(['block' => 'button', 'mods' => ['is-bem' => 'yes'], 'content' => ['elem' => 'control']])
        );
    }

    function test_it_should_match_boolean_mods () {
        $this->bh->match('button_disabled', function ($ctx) {
            $ctx->attr('disabled', 'disabled');
        });
        $this->assertEquals(
            '<div class="button button_disabled" disabled="disabled"></div>',
            $this->bh->apply(['block' => 'button', 'mods' => ['disabled' => true]])
        );
    }

    function test_it_should_not_match_string_values_of_boolean_mods () {
        $this->bh->match('button_type', function ($ctx) {
            $ctx->tag('span');
        });
        $this->assertEquals(
            '<div class="button button_type_link"></div>',
            $this->bh->apply(['block' => 'button', 'mods' => ['type' => 'link']])
        );
    }

    function test_it_should_not_match_block_mods_when__elem__is_present () {
        $this->bh->match('button_disabled__control', function ($ctx) {
            $ctx->tag('span', true);
        });
        $this->bh->match('button__control_disabled', function ($ctx) {
            $ctx->tag('button', true);
        });
        $this->assertEquals(
            '<button class="button__control button__control_disabled"></button>',
            $this->bh->apply(['block' => 'button', 'elem' => 'control', 'mods' => ['disabled' => true]])
        );
    }

    function test_it_should_properly_match_inherited_block_mods () {
        $this->bh->match('button_visibility_hidden__control', function ($ctx) {
            $ctx->mod('foo', 'bar');
        });
        $this->bh->match('button_visibility_visible__control', function ($ctx) {
            $ctx->mod('foo', 'baz');
        });
        $this->bh->match('button__control_visibility_hidden', function ($ctx) {
            $ctx->tag('span');
        });
        $this->bh->match('button__control_visibility_visible', function ($ctx) {
            $ctx->tag('button');
        });
        $this->assertEquals(
            '<div class="button button_visibility_hidden">' .
            '<button class="button__control ' .
                'button__control_visibility_visible button__control_foo_bar"></button>' .
            '</div>',
            $this->bh->apply([
                'block' => 'button',
                'mods' => ['visibility' => 'hidden'],
                'content' => [
                    'elem' => 'control',
                    'mods' => ['visibility' => 'visible']
                ]
            ])
        );
    }

    function test_it_should_properly_match_elem_mods () {
        $this->bh->match('button', function ($ctx) {
            $ctx->content(['elem' => 'control']);
        });
        // Should not fail on elem mod match - #93
        $this->bh->match('button__control_disabled', function ($ctx) {
            $ctx->tag('button');
        });
        $this->assertEquals(
            '<div class="button"><div class="button__control"></div></div>',
            $this->bh->apply(['block' => 'button'])
        );
    }

    function test_it_should_recursively_call_matchers () {
        $this->bh->match('X__x', function ($ctx, $json) {
            $ctx->bem(false);
            $ctx->tag('b');
        });
        $this->bh->match('X__x', function ($ctx, $json) {
            if (!key_exists('r', $json)) {
                return;
            }
            $r = $json->r;
            if ($r === true) {
                $url = $json->url;
                return array_map(function ($v) use ($url) {
                    return [ 'elem' => 'x', 'r' => 'R' . $v ];
                }, [1, 2, 3]);
            } else {
                return [
                    '(' . $r . ')',
                    $json,
                    '(/' . $r . ')'
                ];
            }
        });
        $this->assertEquals(
            '(R1)<b></b>(/R1)(R2)<b></b>(/R2)(R3)<b></b>(/R3)',
            $this->bh->apply(['block' => 'X', 'elem' => 'x', 'r' => true]));
    }
}

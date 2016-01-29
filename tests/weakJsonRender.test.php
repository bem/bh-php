<?php

use BEM\BH;

class weakJsonRenderTest extends PHPUnit_Framework_TestCase
{
    protected static $fixture1 = <<<bemjson
({
    block : 'page'
})
bemjson;
    protected static $expected1 = <<<result
<!DOCTYPE html>
<html class="ua_js_no">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta charset="utf-8"/>
    <title></title>
    <script>
        (function(e,c){e[c]=e[c].replace(/(ua_js_)no/g,"$1yes");})(document.documentElement,"className");
    </script>
</head>
<body class="page"></body>
</html>
result;

    /**
     * @before
     */
    public function setupBhInstance()
    {
        $bh = $this->bh = new BH();

        // bem-core/common/page
        $bh->match('page', function ($ctx, $json) {
            $ctx
                ->tag('body')
                ->content([
                    $ctx->content(),
                    $json->scripts
                ], true);

            return [
                $json->doctype ?: '<!DOCTYPE html>',
                [
                    'tag' => 'html',
                    'cls' => 'ua_js_no',
                    'content' => [
                        [
                            'elem' => 'head',
                            'content' => [
                                ['tag' => 'meta', 'attrs' => ['charset' => 'utf-8']],
                                ['tag' => 'title', 'content' => $json->title],
                                ['block' => 'ua'],
                                $json->head,
                                $json->styles,
                                $json->favicon ? ['elem' => 'favicon', 'url' => $json->favicon] : '',
                            ]
                        ],
                        $json
                    ]
                ]
            ];
        });

        $bh->match('page__head', function ($ctx) {
            $ctx->bem(false)->tag('head');
        });

        $bh->match('page__meta', function ($ctx) {
            $ctx->bem(false)->tag('meta');
        });

        $bh->match('page__link', function ($ctx) {
            $ctx->bem(false)->tag('link');
        });

        $bh->match('page__favicon', function ($ctx, $json) {
            $ctx
                ->bem(false)
                ->tag('link')
                ->attr('rel', 'shortcut icon')
                ->attr('href', $json->url);
        });

        // bem-core/common/page__js
        $bh->match('page__js', function ($ctx, $json) {
            $ctx
                ->bem(false)
                ->tag('script');
            $json->url && $ctx->attr('src', $json->url);
        });

        // bem-core/common/page__css
        $bh->match('page__css', function ($ctx, $json) {
            $ctx->bem(false);

            if ($json->url) {
                $ctx
                    ->tag('link')
                    ->attr('rel', 'stylesheet')
                    ->attr('href', $json->url);
            } else {
                $ctx->tag('style');
            }
        });

        // bem-core/common/ua
        $bh->match('ua', function ($ctx) {
            $ctx
                ->bem(false)
                ->tag('script')
                ->content([
                    '(function(e,c){',
                        'e[c]=e[c].replace(/(ua_js_)no/g,"$1yes");',
                    '})(document.documentElement,"className");'
                ], true);
        });

        // bem-core/desktop/page
        $bh->match('page__head', function ($ctx, $json) {
            $ctx->content([
                $json->{'x-ua-compatible'} === false?
                    false :
                    [
                        'tag' => 'meta',
                        'attrs' => [
                            'http-equiv' => 'X-UA-Compatible',
                            'content' => $json->{'x-ua-compatible'} ?: 'IE=edge'
                        ]
                    ],
                $ctx->content()
            ], true);
        });

        // bem-core/desktop/page__css
        $bh->match('page__css', function ($ctx, $json) {
            if (!key_exists('ie', $json)) {
                return;
            }
            $ie = $json->ie;
            if ($ie === true) {
                $url = $json->url;
                return array_map(function ($v) use ($url) {
                    return [ 'elem' => 'css', 'url' => $url . '.ie' . $v . '.css', 'ie' => 'IE ' . $v ];
                }, [6, 7, 8, 9]);
            } else {
                $hideRule = !$ie?
                    ['gt IE 9', '<!-->', '<!--'] :
                    ($ie === '!IE'?
                        [$ie, '<!-->', '<!--'] :
                        [$ie, '', '']);
                return [
                    '<!--[if' . $hideRule[0] . ']>' . $hideRule[1],
                    $json,
                    $hideRule[2] . '<![endif]-->'
                ];
            }
        });
    }

    public function test_emptyPageRender()
    {
        $this->assertEquals(
            self::stripWhitespaces(self::$expected1),
            $this->bh->apply(self::$fixture1)
        );
    }


    protected function stripWhitespaces($s)
    {
        return preg_replace('@\s*\n\s*@', '', $s);
    }
}

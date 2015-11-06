<?php

namespace rockunit;


use rock\base\Alias;
use rock\csrf\CSRF;
use rock\request\Request;
use rock\url\Url;

/**
 * @group base
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'site.com';
    }

    protected function tearDown()
    {
        parent::tearDown();
        static::setUpBeforeClass();
    }

//    public function testCurrent()
//    {
//        // relative
//        $url = new Url();
//        $this->assertSame('/', $url->getRelative());
//        $this->assertSame('/', Url::set()->getRelative());
//
//        // https
//        $url = new Url();
//        $this->assertSame('https://site.com/', $url->setScheme('https')->getAbsolute());
//
//        // absolute
//        $_SERVER['HTTP_HOST'] = null;
//        $url = new Url();
//        $this->assertSame('http://site.com/', $url->getAbsolute());
//        $this->assertNull($url->getQueryParams());
//        $this->assertNull($url->getQuery());
//
//        //port
//        $_SERVER['HTTP_HOST'] = 'site.com:8080';
//        $url = new Url();
//        $this->assertSame('http://site.com:8080/', $url->getAbsolute());
//        $_SERVER['HTTP_HOST'] = null;
//
//        // add query
//        $url = new Url();
//        $this->assertSame('http://site.com/?foo=test&bar=bartest', $url->setQuery('foo=test&bar=bartest')->getAbsolute());
//
//        // set query params
//        $this->assertSame('http://site.com/?foo=testfoo', $url->addQueryParams(['page' => 1])->setQueryParams(['foo' => 'testfoo'])->getAbsolute());
//
//        // removing args
//        $url = new Url();
//        $this->assertSame('http://site.com/', $url->removeQueryParams(['page'])->getAbsolute());
//        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
//        $url = new Url();
//        $this->assertSame('http://site.com/?view=all', $url->removeQueryParams(['page'])->getAbsolute());
//
//        // removing all args
//        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
//        $url = new Url();
//        $this->assertSame('http://site.com/', $url->removeQuery()->getAbsolute());
//        $_SERVER['REQUEST_URI'] = '/';
//
//        // adding anchor
//        $url = new Url();
//        $this->assertSame('http://site.com/#name', $url->setFragment('name')->getAbsolute());
//
//        // removing anchor
//        $url = new Url();
//        $this->assertSame('http://site.com/', $url->removeFragment()->getAbsolute());
//
//        // adding postfix
//        $url = new Url();
//        $this->assertSame('http://site.com/news/', $url->setPostfixPath('news/')->getAbsolute());
//
//        // replacing URL
//        $url = new Url();
//        $this->assertSame('http://site.com/', $url->replacePath('news/', '')->getAbsolute());
//
//        // callback
//        $url = new Url();
//        $this->assertSame('http://site.com/#foo', $url->callback(function (Url $url) {
//            $url['fragment'] = 'foo';
//        })->getAbsolute());
//
//        // get host
//        $url = new Url();
//        $this->assertSame('site.com', $url['host']);
//        $this->assertSame('site.com', $url->getHost());
//
//        $url = new Url();
//        $url['user'] = 'tom';
//        $url['pass'] = '123';
//        $url['port'] = 777;
//        $url->setQueryParams(['bar' => 'baz', 'foo' => 'test']);
//        $this->assertSame('http://tom:123@site.com:777/?bar=baz&foo=test', $url->getAbsolute());
//        $this->assertSame('http', $url->getScheme());
//        $this->assertSame('tom', $url->getUser());
//        $this->assertSame('123', $url->getPass());
//        $this->assertSame(777, $url->getPort());
//        $this->assertSame(['bar' => 'baz', 'foo' => 'test'], $url->getQueryParams());
//        $this->assertSame('bar=baz&foo=test', $url->getQuery());
//
//        // build
//        $url = new Url();
//        $this->assertSame(
//            'https://site.com/parts/news/?page=1#name',
//            $url->setQueryParams(['page' => 1])
//                ->setPrefixPath('/parts')
//                ->setPostfixPath('/news/')
//                ->setFragment('name')
//                ->setScheme('https')
//                ->getAbsolute()
//        );
//
//        // build + remove args
//        $url = new Url();
//        $this->assertSame(
//            '//site.com/parts/news/#name',
//            $url
//                ->setQueryParams(['page' => 1])
//                ->setPrefixPath('/parts')
//                ->setPostfixPath('/news/')
//                ->setFragment('name')
//                ->removeQuery()
//                ->getShortAbsolute()
//        );
//
//        // build + add args
//        $url = new Url();
//        $this->assertSame(
//            '/parts/news/?view=all#name',
//            $url
//                ->setPrefixPath('/parts')
//                ->setPostfixPath('/news/')
//                ->setFragment('name')
//                ->removeQuery()
//                ->addQueryParams(['view' => 'all'])
//                ->getRelative()
//        );
//
//        // Magic data url
//        $url = new Url();
//        $this->assertNull($url['foo']);
//        $this->assertFalse(isset($url['foo']));
//        $this->assertTrue(isset($url['host']));
//        $this->assertFalse(isset($url['query']));
//        unset($url['host']);
//        $this->assertFalse(isset($url['host']));
//
//        $url = new Url(null, ['current' => 'http://test.com/']);
//        $this->assertSame('http://test.com/', $url->getAbsolute());
//    }

    public function testModifyUrl()
    {
        $url = new Url('http://site.com:8080/?page=2#name');
        $this->assertSame('/?page=2#name', $url->getRelative());
        $url = new Url('/?page=2#name');
        $this->assertSame('//site.com/?page=2#name', $url->getAbsolute());
        $url = new Url('//site.com:8080/?page=2#name');
        $this->assertSame('//site.com:8080/?page=2#name', $url->getShortAbsolute());

//        // https
//        $url = Url::set('http://site.com/?page=2#name');
//        $this->assertSame('https://site.com/?page=2#name', $url->setScheme('https')->getAbsolute());
//
//        // http
//        $url = Url::set('https://site.com/?page=2#name');
//        $this->assertSame('http://site.com/?page=2#name', $url->setScheme('http')->getAbsolute());
//
//        // removing anchor
//        $url = new Url('https://site.com:8080/?page=2#name');
//        $this->assertSame('https://site.com:8080/?page=2', $url->removeFragment()->getAbsolute());
//
//        // replacing URL
//        $url = new Url('//site.com/news/?page=2#name');
//        $this->assertSame('//site.com/?page=2#name', $url->replacePath('news/', '')->getAbsolute());
//
//        $_SERVER['HTTP_HOST'] = null;
//
//        // build + remove args
//        $url = new Url('http://site2.com/?page=2#name');
//        $this->assertSame(
//            'http://site2.com/parts/news/?view=all#name',
//            $url
//                ->setPrefixPath('/parts')
//                ->setPostfixPath('/news/')
//                ->addQueryParams(['view' => 'all'])
//                ->removeQueryParams(['page'])
//                ->getAbsolute()
//        );
//        $this->assertSame('/parts/news/', $url->getPath());
//
//        $url = Url::set('http://site2.com/?page=2#name')
//            ->setPrefixPath('/parts')
//            ->setPostfixPath('/news/')
//            ->addQueryParams(['view' => 'all'])
//            ->removeQueryParams(['page']);
//        $this->assertSame('http://site2.com/parts/news/?view=all#name', $url->getAbsolute());
//
//        // to string
//        $this->assertSame('http://site2.com/parts/news/?view=all#name', (string)$url);
    }

//    public function testToArray()
//    {
//        $url = Url::set('http://site2.com/?page=2#name')
//            ->setPrefixPath('/parts')
//            ->setPostfixPath('/news/')
//            ->addQueryParams(['view' => 'all'])
//            ->removeQueryParams(['page']);
//
//        $expected = [
//            'scheme' => 'http',
//            'host' => 'site2.com',
//            'path' => '/parts/news/',
//            'query' =>
//                [
//                    'view' => 'all',
//                ],
//            'fragment' => 'name',
//        ];
//        $this->assertEquals($expected, $url->toArray());
//    }
//
//    public function testModify()
//    {
//        $this->assertEquals('/', Url::modify('http://site.com/'));
//        $this->assertEquals('/', Url::modify(['http://site.com/']));
//        $this->assertEquals('http://site.com/', Url::modify(['http://site.com/', '@scheme' => Url::ABS]));
//        $this->assertEquals('http://site.com/', Url::modify(['http://site.com/', '@scheme' => Url::ABS]));
//        $this->assertEquals('http://site.com/?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2]));
//        $this->assertEquals('http://foo.com/?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2, '@host' => 'foo.com']));
//        $this->assertEquals('http://site.com/foo?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2, '@path' => 'foo']));
//        $this->assertEquals('http://site.com:8080/?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2, '@port' => 8080]));
//        $this->assertEquals('http://tom@site.com:8080/?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2, '@user' => 'tom', '@port' => 8080]));
//        $this->assertEquals('http://tom:testpass@site.com/?page=2', Url::modify(['http://site.com/', '@scheme' => Url::ABS, 'page' => 2, '@pass' => 'testpass', '@user' => 'tom']));
//        $this->assertEquals('http://site.com/testprefix/test/?page=2', Url::modify(['http://site.com/test/', '@scheme' => Url::ABS, 'page' => 2, '@prefixPath' => 'testprefix']));
//        $this->assertEquals('http://site.com/test/testpostfix/?page=2', Url::modify(['http://site.com/test/', '@scheme' => Url::ABS, 'page' => 2, '@postfixPath' => 'testpostfix/']));
//        $this->assertEquals('/?page=2', Url::modify(['http://site.com/', 'page' => 2]));
//
//        $this->assertEquals('/', Url::modify(['http://site.com/?foo=bar', '!foo']));
//        $this->assertEquals('/?page=2#name', Url::modify(['http://site.com/?foo=bar', '!foo', 'page' => 2, '#' => 'name']));
//        $this->assertEquals('/?page=2', Url::modify(['http://site.com/?foo=bar#name', '!foo', 'page' => 2, '!#']));
//        $this->assertEquals('/', Url::modify(['http://site.com/?foo=bar&baz=bar', '!']));
//
//        // replace placeholders
//        $this->assertEquals('http://api.site.com/items/7/', Url::modify(['http://{sub}.site.com/items/{id}/?foo=bar', '!foo', '+sub' => 'api', '+id' => 7, '@scheme' => Url::ABS]));
//        Alias::setAlias('foo', 'http://{sub}.site.com/items/{id}/', false);
//        $this->assertEquals('http://api.site.com/items/{id}/', Url::modify(['@foo', '!foo', '+sub' => 'api', '@scheme' => Url::ABS]));
//        $this->assertEquals('/items/{id}/', Url::modify(['@foo']));
//    }
//
//    public function testCurrentModify()
//    {
//        $this->assertEquals('http://site.com/', Url::modify(['@scheme' => Url::ABS]));
//        $this->assertEquals('/', Url::modify(['@scheme' => Url::REL]));
//        $this->assertEquals('/', Url::modify());
//
//        $this->assertEquals('/?page=2', Url::modify(['page' => 2]));
//        $this->assertEquals('http://site.com/?page=2', Url::modify(['@scheme' => Url::ABS, 'page' => 2]));
//        $this->assertEquals('/?page=2', Url::modify(['@scheme' => Url::REL, 'page' => 2]));
//        $this->assertEquals('/?page=2#name', Url::modify(['page' => 2, '#' => 'name']));
//
//        $this->assertEquals('/', Url::modify(['#' => '']));
//
//        // empty
//        $this->assertEquals('http://site.com/?page=2', Url::modify(['', 'page' => 2, '@scheme' => Url::ABS]));
//        $this->assertEquals('http://site.com/?page=2', Url::modify([null, '@scheme' => Url::ABS, 'page' => 2]));
//    }
//
//    public function testProtect()
//    {
//        $url = new Url('http://site2.com/?page=2#name', ['protect' => true]);
//        $url->setPrefixPath('/parts')
//            ->setPostfixPath('/news/')
//            ->setFragment('name')
//            ->addQueryParams(['view' => 'all']);
//
//        $this->assertSame(
//            'http://site2.com/parts/news/?page=2&view=all#name',
//            $url->getAbsolute()
//        );
//
//        $this->assertEquals(
//            'http://site2.com/?page=2#name',
//            Url::modify(['http://site2.com/#name', '@scheme' => Url::ABS, 'page' => 2, '@protect' => true])
//        );
//
//        $url = new Url('http://site2.com/?page=2#name', ['protect' => true, 'protectLink' => 'http://site.com/warning/']);
//        $url->setPrefixPath('/parts')
//            ->setPostfixPath('/news/')
//            ->setFragment('name')
//            ->addQueryParams(['view' => 'all']);
//
//        $this->assertSame(
//            'http://site.com/warning/?r=http://site2.com/parts/news/?page=2&view=all#name',
//            $url->getAbsolute()
//        );
//
//        $this->assertEquals(
//            'http://site.com/warning/?r=http://site2.com/?page=2#name',
//            Url::modify(['http://site2.com/#name', '@scheme' => Url::ABS, 'page' => 2, '@protect' => true, '@protectLink' => 'http://site.com/warning/'])
//        );
//
//        // sets a allowed domains
//        Alias::setAlias('test_domain', 'site2.com');
//        $url = new Url('http://site2.com/?page=2#name', ['protect' => true, 'protectLink' => 'http://site.com/warning/', 'allowedDomains' => ['@test_domain']]);
//        $url->setPrefixPath('/parts')
//            ->setPostfixPath('/news/')
//            ->setFragment('name')
//            ->addQueryParams(['view' => 'all']);
//
//        $this->assertSame(
//            'http://site2.com/parts/news/?page=2&view=all#name',
//            $url->getAbsolute()
//        );
//
//        $this->assertEquals(
//            'http://site2.com/?page=2#name',
//            Url::modify(['http://site2.com/#name', '@scheme' => Url::ABS, 'page' => 2, '@protect' => true, '@protectLink' => 'http://site.com/warning/', '@allowedDomains' => ['@test_domain']])
//        );
//    }
//
//    public function testRemoveArgs()
//    {
//        $request = new Request(['url' => '/?foo=test']);
//
//        $this->assertSame('http://site.com/?foo=test', Url::modify(['@scheme' => Url::ABS], ['request' => $request]));
//        $this->assertSame('http://site.com/', Url::modify(['!', '@scheme' => Url::ABS], ['request' => $request]));
//        $this->assertSame('http://site.com/', Url::modify(['@scheme' => Url::ABS, '!'], ['request' => $request]));
//
//        $this->assertSame('/?page=2', Url::modify(['http://site.com/?page=2', '@scheme' => Url::REL]));
//        $this->assertSame('http://site.com/', Url::modify(['http://site.com/?page=2', '@scheme' => Url::ABS, '!']));
//    }
//
//    public function testCSRF()
//    {
//        if (!class_exists('\rock\csrf\CSRF')) {
//            $this->markTestSkipped("Doesn't installed Rock CSRF.");
//        }
//        parse_str(Url::modify(['page' => 2, '@scheme' => Url::REL], ['csrf' => true]), $result);
//        $this->assertNotEmpty($result[(new CSRF())->csrfParam]);
//
//        parse_str(Url::modify(['page' => 2, '@csrf' => true]), $result);
//        $this->assertNotEmpty($result[(new CSRF())->csrfParam]);
//    }
}
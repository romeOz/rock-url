<?php

namespace rockunit;


use rock\url\Url;

/**
 * @group base
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'site.com';
    }

    protected function tearDown()
    {
        parent::tearDown();
        static::setUpBeforeClass();
    }

    public function tesCurrent()
    {
        // relative
        $url = new Url();
        $this->assertSame('/', $url->getRelative());
        $this->assertSame('/', Url::set()->getRelative());

        // http
        $url = new Url();
        $this->assertSame('http://site.com/', $url->getHttp());

        // https
        $url = new Url();
        $this->assertSame('https://site.com/', $url->getHttps());

        // absolute
        $_SERVER['HTTP_HOST'] = null;
        $url = new Url();
        $this->assertSame('http://site.com/', $url->getAbsolute());

        //port
        $_SERVER['HTTP_HOST'] = 'site.com:8080';
        $url = new Url();
        $this->assertSame('http://site.com:8080/', $url->getAbsolute());
        $_SERVER['HTTP_HOST'] = null;

        // removing args
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeArgs(['page'])->getAbsolute());
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame('http://site.com/?view=all', $url->removeArgs(['page'])->getAbsolute());

        // removing all args
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeAllArgs()->getAbsolute());
        $_SERVER['REQUEST_URI'] = '/';

        // adding anchor
        $url = new Url();
        $this->assertSame('http://site.com/#name', $url->addAnchor('name')->getAbsolute());

        // removing anchor
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeAnchor()->getAbsolute());

        // adding end path
        $url = new Url();
        $this->assertSame('http://site.com/news/', $url->addEndPath('news/')->getAbsolute());

        // replacing URL
        $url = new Url();
        $this->assertSame('http://site.com/', $url->replacePath('news/', '')->getAbsolute());

        // callback
        $url = new Url();
        $this->assertSame('http://site.com/#foo', $url->callback(function(Url $url){$url->fragment = 'foo';})->getAbsolute());

        // get host
        $url = new Url();
        $this->assertSame('site.com',$url->host);

        // get host
        $url = new Url();
        $url->user = 'tom';
        $url->pass = '123';
        $this->assertSame('http://tom:123@site.com/', $url->getAbsolute());

        // build
        $url = new Url();
        $this->assertSame(
            'https://site.com/parts/news/?page=1#name',
            $url->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->getHttps()
        );

        // build + strip_tags
        $url = new Url();
        $this->assertSame(
            '/parts/news/?page=1&view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/<b>news</b>/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->setArgs(['page' => 1])
                ->addArgs(['view'=> 'all'])
                ->getRelative()
        );

        // build + remove args
        $url = new Url();
        $this->assertSame(
            '/parts/news/#name',
            $url
                ->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->getRelative()
        );

        // build + add args
        $url = new Url();
        $this->assertSame(
            '/parts/news/?view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->addArgs(['view'=> 'all'])
                ->getRelative()
        );

        // get unknown data of url
        $this->assertNull((new Url())->foo);

        $url = new Url(null, ['current' => 'http://test.com/']);
        $this->assertSame('http://test.com/', $url->getAbsolute());
    }

    public function testModifyUrl()
    {
        // relative
        $url = new Url('http://site.com:8080/?page=2#name');
        $this->assertSame('/?page=2#name',$url->getRelative());

        // https
        $url = Url::set('http://site.com/?page=2#name');
        $this->assertSame('https://site.com/?page=2#name', $url->getHttps());

        // http
        $url = Url::set('https://site.com/?page=2#name');
        $this->assertSame('http://site.com/?page=2#name', $url->getHttp());

        // removing anchor
        $url = new Url('https://site.com:8080/?page=2#name');
        $this->assertSame('https://site.com:8080/?page=2', $url->removeAnchor()->getAbsolute());

        // replacing URL
        $url =  new Url('http://site.com/news/?page=2#name');
        $this->assertSame('http://site.com/?page=2#name', $url->replacePath('news/', '')->getAbsolute());

        // build + add args + self host
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            'http://site.com/parts/news/?page=2&view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->addArgs(['view'=> 'all'])
                ->getAbsolute(true)
        );

        $_SERVER['HTTP_HOST'] = 'site.com:8080';
        $url = new Url('http://site2.com/?page=2#name');
        $url->query = 'views=all&page=3';
        $this->assertSame(
            'http://site.com:8080/?views=all&page=3#name',
            $url->getAbsolute(true)
        );
        $_SERVER['HTTP_HOST'] = null;

        // build + remove args
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            'http://site2.com/parts/news/?view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addArgs(['view'=> 'all'])
                ->removeArgs(['page'])
                ->getAbsolute()
        );

        $url = Url::set('http://site2.com/?page=2#name')
            ->addBeginPath('/parts')
            ->addEndPath('/news/')
            ->addArgs(['view'=> 'all'])
            ->removeArgs(['page']);
        $this->assertSame('http://site2.com/parts/news/?view=all#name', $url->getAbsolute());

        // to string
        $this->assertSame('http://site2.com/parts/news/?view=all#name', (string)$url);
    }
}
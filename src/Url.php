<?php

namespace rock\url;

use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\StringHelper;
use rock\request\Request;

/**
 * Url manager.
 */
class Url implements UrlInterface, ObjectInterface, \ArrayAccess
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    /**
     * List URL-data.
     *
     * @var array
     */
    protected $data = [];
    /**
     * Dummy by URL. If URL is empty.
     *
     * @var string
     */
    public $dummy = '#';
    /**
     * Strip tags (security).
     * @var bool
     */
    public $strip = true;
    /**
     * Current URL.
     * @var string
     */
    public $current;
    /** @var  Request */
    public $request = 'request';

    /**
     * Modify URL.
     *
     * @param string|null $url URL for formatting. If URL as `NULL`, then use current (self) URL.
     * @param array $config
     */
    public function __construct($url = null, $config = [])
    {
        $this->parentConstruct($config);
        $this->request = Instance::ensure($this->request, '\rock\request\Request');

        if (!isset($url)) {
            $url = $this->currentInternal();
        }
        $this->data = array_merge(parse_url(trim($url)), $this->data);
        if (isset($this->data['query'])) {
            $this->data['query'] = $this->_queryToArray($this->data['query']);
        }
    }

    /**
     * Modify URL.
     *
     * @param string|null $url URL for modify (default: NULL)
     * @param array $config the configuration. It can be either a string representing the class name
     *                             or an array representing the object configuration.
     * @throws \rock\di\ContainerException
     * @return $this
     */
    public static function set($url = null, array $config = [])
    {
        if (class_exists('\rock\di\Container')) {
            $config['class'] = self::className();
            return Container::load($url, $config);
        }
        return new static($url, $config);
    }

    /**
     * Modify url.
     * @param string|array $modify
     * @param string $scheme
     * @param array $config
     * @return null|string
     * @throws UrlException
     */
    public static function modify($modify, $scheme = self::REL, array $config = [])
    {
        if (is_scalar($modify)) {
            return static::set($modify, $config)->get($scheme);
        }

        if (!is_array($modify)) {
            throw new UrlException('$modify must be array.');
        }
        $url = array_shift($modify);
        return static::modifyInternal(static::set($url, $config), $modify)->get($scheme);
    }

    /**
     * Modify current url.
     * @param array $modify
     * @param string $scheme
     * @param array $config
     * @return null|string
     * @throws UrlException
     */
    public static function current(array $modify = null, $scheme = self::REL, array $config = [])
    {
        if (!isset($modify)) {
            return static::set(null, $config)->get($scheme);
        }

        return static::modifyInternal(static::set(null, $config), $modify)->get($scheme);
    }

    /**
     * Set URL-args.
     *
     * @param array $args array args
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->data['query'] = $args;

        return $this;
    }

    /**
     * Adding URL-arguments.
     *
     * @param array $args arguments
     * @return $this
     */
    public function addArgs(array $args)
    {
        $this->data['query'] = array_merge(Helper::getValue($this->data['query'], []), $args);
        $this->data['query'] = array_filter($this->data['query']);
        return $this;
    }

    /**
     * Removing URL-args.
     *
     * @param array $args arguments
     * @return $this
     */
    public function removeArgs(array $args)
    {
        if (empty($this->data['query'])) {
            return $this;
        }

        $this->data['query'] = array_diff_key(
            $this->data['query'],
            array_flip($args)
        );

        return $this;
    }

    /**
     * Removing all URL-arguments.
     * @return $this
     */
    public function removeAllArgs()
    {
        $this->data['query'] = null;
        return $this;
    }

    /**
     * Adding anchor.
     *
     * @param string $anchor
     * @return $this
     */
    public function addAnchor($anchor)
    {
        $this->data['fragment'] = $anchor;

        return $this;
    }

    /**
     * Removing anchor.
     *
     * @return $this
     */
    public function removeAnchor()
    {
        $this->data['fragment'] = null;

        return $this;
    }

    /**
     * Adding string to begin of URL-path.
     *
     * @param string $value
     * @return $this
     */
    public function addBeginPath($value)
    {
        $this->data['path'] = $value . $this->data['path'];

        return $this;
    }

    /**
     * Adding string to end of URL-path.
     *
     * @param string $value
     * @return $this
     */
    public function addEndPath($value)
    {
        $this->data['path'] .= $value;

        return $this;
    }

    /**
     * Replacing path.
     *
     * @param string $search
     * @param string $replace
     * @return $this
     */
    public function replacePath($search, $replace)
    {
        $this->data['path'] = str_replace($search, $replace, $this->data['path']);
        return $this;
    }

    /**
     * Custom formatting.
     *
     * @param callable $callback
     * @return $this
     */
    public function callback(callable $callback)
    {
        call_user_func($callback, $this);
        return $this;
    }

    /**
     * Returns formatted URL.
     *
     * @param string $scheme
     * @param bool $selfHost to use current host (security).
     * @return null|string
     */
    public function get($scheme = self::REL, $selfHost = false)
    {
        $data = $this->data;
        if ($selfHost == true) {
            $data['scheme'] = $this->request->getScheme();
            $data['host'] = $this->request->getHost();
        }

        if (!isset($data['host'])) {
            $data['scheme'] = $this->request->getScheme();
            $data['host'] = $this->request->getHost();
        }

        if ($scheme == self::HTTP && isset($data['host'])) {
            $data['scheme'] = 'http';
        } elseif ($scheme == self::HTTPS && isset($data['host'])) {
            $data['scheme'] = 'https';
        } elseif ($scheme == self::ABS) {
        } else {
            unset($data['scheme'], $data['host'], $data['user'], $data['pass'], $data['port']);
        }
        return $this->strip === true ? strip_tags($this->build($data)) : $this->build($data);
    }

    /**
     * Returns absolute URL: `http://site.com`
     * @param bool $selfHost
     * @return null|string
     */
    public function getAbsolute($selfHost = false)
    {
        return $this->get(self::ABS, $selfHost);
    }

    /**
     * Returns absolute URL: `/`
     * @param bool $selfHost
     * @return null|string
     */
    public function getRelative($selfHost = false)
    {
        return $this->get(self::REL, $selfHost);
    }

    /**
     * Returns http URL: `http://site.com`
     * @param bool $selfHost
     * @return null|string
     */
    public function getHttp($selfHost = false)
    {
        return $this->get(self::HTTP, $selfHost);
    }

    /**
     * Returns https URL: `https://site.com`
     * @param bool $selfHost
     * @return null|string
     */
    public function getHttps($selfHost = false)
    {
        return $this->get(self::HTTPS, $selfHost);
    }

    /**
     * Exists data by key.
     * @param string $key key of data.
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Returns URL-data.
     * @param string $key key of data.
     * @return string|null
     */
    public function offsetGet($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Set data of URL.
     * @param string $key key of data.
     * @param $value
     */
    public function offsetSet($key, $value)
    {
        if ($key === 'query') {
            $value = $this->_queryToArray($value);
        }
        $this->data[$key] = $value;
    }

    /**
     * Remove data-URl by key.
     * @param string $key key of data.
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Returns list data of URL.
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->getAbsolute();
    }

    /**
     * Returns current url.
     * @return string
     * @throws \Exception
     */
    protected function currentInternal()
    {
        return $this->current ? Alias::getAlias($this->current) : $this->request->getAbsoluteUrl();
    }

    protected function build(array $data)
    {
        $url = StringHelper::rconcat($data['scheme'], '://');

        if (isset($data['user']) && isset($data['pass'])) {
            $url .= StringHelper::rconcat($data['user'], ':');
            $url .= StringHelper::rconcat($data['pass'], '@');
        }
        if (!empty($data['host'])) {
            $data['host'] = explode(':', $data['host']);
            if (!isset($data['host'][1])) {
                $data['host'][1] = null;
            }
            list($host, $port) = $data['host'];
            $url .= $host;
            if (isset($port)) {
                $data['port'] = $port;
            }
        }
        if (!empty($data['port'])) {
            $url .= ":{$data['port']}";
        }
        if (isset($data['path'])) {
            $url .= preg_replace(['/\/+(?!http:\/\/)/', '/\\\+/'], '/', $data['path']);
        }
        if (!empty($data['query'])) {
            if (is_string($data['query'])) {
                $data['query'] = [$data['query']];
            }
            // @see http://php.net/manual/ru/function.http-build-query.php#111819
            $url .= '?' . preg_replace('/%5B[0-9]+%5D/i', '%5B%5D', http_build_query($data['query']));
        }
        $url .= StringHelper::lconcat($data['fragment'], '#');

        return $url;
    }

    protected static function modifyInternal(Url $self, array $modify)
    {
        foreach ($modify as $key => $value) {
            if ($key === '#') {
                $self->addAnchor($value);
                continue;
            }

            if (is_int($key)) {
                if ($value === '!#') {
                    $self->removeAnchor();
                    continue;
                }

                if ($value === '!') {
                    $self->removeAllArgs();
                    continue;
                }
                if ($value[0] === '!') {
                    $self->removeArgs([mb_substr($value, 1, mb_strlen($value, 'UTF-8'), 'UTF-8')]);
                    continue;
                }
                continue;
            }

            $self->addArgs([$key => $value]);
        }
        return $self;
    }

    private function _queryToArray($query)
    {
        if (!isset($query) || is_array($query)) {
            return $query;
        }
        parse_str($query, $query);
        return $query;
    }
}
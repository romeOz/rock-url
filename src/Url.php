<?php

namespace rock\url;

use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\StringHelper;
use rock\request\Request;

/**
 * Url Builder.
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
     * Current URL.
     * @var string
     */
    public $current;
    /**
     * Adding a CSRF-token (security).
     * @var bool
     */
    public $csrf = false;
    /**
     * Request instance.
     * @var  Request
     */
    public $request = 'request';
    /**
     * CSRF instance.
     * @var \rock\csrf\CSRF
     */
    public $csrfInstance = 'csrf';

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
        $this->csrfInstance = Instance::ensure($this->csrfInstance, '\rock\csrf\CSRF', [], false);

        if (empty($url)) {
            $url = $this->currentInternal();
        } else {
            $url = Alias::getAlias($url);
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
     * @return $this
     */
    public static function set($url = null, array $config = [])
    {
        $config['class'] = static::className();
        return Instance::ensure($config, static::className(), [$url]);
    }

    /**
     * Modify url.
     * @param string|array $modify
     * @param string $scheme
     * @param array $config
     * @return null|string
     * @throws UrlException
     */
    public static function modify($modify = null, $scheme = self::REL, array $config = [])
    {
        if (!isset($modify) || is_scalar($modify)) {
            return static::set($modify, $config)->get($scheme);
        }

        if (!is_array($modify)) {
            throw new UrlException('Argument "$modify" must be array.');
        }
        $url = current($modify);
        if (is_int(key($modify)) && !empty($url) && $url[0] !== '!') {
            $url = array_shift($modify);
        } else {
            $url = null;
        }
        return static::modifyInternal(static::set($url, $config), $modify)->get($scheme);
    }

    /**
     * Sets a URL-args.
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
     * Adds a URL-arguments.
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
     * Removes a URL-args.
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
     * Removes all URL-arguments.
     * @return $this
     */
    public function removeAllArgs()
    {
        $this->data['query'] = null;
        return $this;
    }

    /**
     * Adds a anchor.
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
     * Removes a anchor.
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
     * @param string $value
     * @param string $replace
     * @return $this
     */
    public function replacePath($value, $replace)
    {
        $this->data['path'] = str_replace($value, $replace, $this->data['path']);
        return $this;
    }

    /**
     * Replacing placeholders to URL-data.
     * @param array $placeholders
     * @return static
     */
    public function replace(array $placeholders = [])
    {
        if (empty($placeholders)) {
            return $this;
        }
        $callback = function($value) use ($placeholders){
            return StringHelper::replace($value, $placeholders, false);
        };
        $this->data = ArrayHelper::map($this->data, $callback, true);
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
        return $this->build($data);
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
        if ($this->csrf && $this->csrfInstance instanceof \rock\csrf\CSRF) {
            if (empty($data['query'])) {
                $data['query'] = [];
            }
            $data['query'][$this->csrfInstance->csrfParam] = $this->csrfInstance->get();
        }
        if (!empty($data['query'])) {
            $data['query'] = preg_replace('/%5B[0-9]+%5D/i', '%5B%5D', http_build_query($data['query']));
        } else {
            unset($data['query']);
        }

        if (empty($data['fragment'])) {
            unset($data['fragment']);
        }
        return http_build_url($data);
    }

    protected static function modifyInternal(Url $self, array $modify)
    {
        $placeholders = [];
        foreach ($modify as $key => $value) {
            if ($key === '#') {
                $self->addAnchor($value);
                continue;
            }

            if (is_int($key)) {
                if (empty($value)) {
                    continue;
                }
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

            if ($key[0] === '+') {
                $placeholders[mb_substr($key, 1, mb_strlen($key, 'UTF-8'), 'UTF-8')] = $value;
                continue;
            }

            $self->addArgs([$key => $value]);
        }
        $self->replace($placeholders);

        return $self;
    }

    private function _queryToArray($query)
    {
        if (!isset($query)) {
            return $query;
        }
        if (!is_array($query)) {
            parse_str($query, $query);
        }
        return $query;
    }
}
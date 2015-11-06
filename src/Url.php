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
     * @var array
     */
    protected $data = [];
    /**
     * Use dummy, when URL is empty.
     * @var string
     */
    public $dummy = '#';
    /**
     * Enable protect mode.
     * @var bool
     */
    public $protect = false;
    /**
     * Link.
     * @var string
     */
    public $protectLink;
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
     * Allowed domains.
     * @var string[]
     */
    protected $_allowedDomains = [];

    /**
     * Modify URL.
     *
     * @param string|null $url URL for formatting. If URL as `NULL`, then use current (self) URL.
     * @param array $config
     * @throws UrlException
     * @throws \Exception
     * @throws \rock\helpers\InstanceException
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
        if (($url = parse_url(trim($url))) === false) {
            throw new UrlException('Wrong format URL.');
        }
        $this->data = array_merge($url, $this->data);
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
     * @param array $config
     * @return null|string
     * @throws UrlException
     */
    public static function modify($modify = null, array $config = [])
    {
        if (!isset($modify) || is_scalar($modify)) {
            return static::set($modify, $config)->get();
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
        $scheme = self::REL;
        if (isset($modify['@scheme'])) {
            $scheme = $modify['@scheme'];
            if ($modify['@scheme'] == self::ABS || $modify['@scheme'] == self::SHORT_ABS) {
                unset($modify['@scheme']);
            }
        }
        return static::modifyInternal(static::set($url, $config), $modify)->get($scheme);
    }

    /**
     * Sets a scheme.
     * @param string $scheme
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->data['scheme'] = $scheme;
        return $this;
    }

    /**
     * Returns a scheme.
     * @return string|null
     */
    public function getScheme()
    {
        return isset($this->data['scheme']) ? $this->data['scheme'] : null;
    }

    /**
     * Sets a host.
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->data['host'] = $host;
        return $this;
    }

    /**
     * Returns a host.
     * @return string|null
     */
    public function getHost()
    {
        return isset($this->data['host']) ? $this->data['host'] : null;
    }

    /**
     * Sets a port.
     * @param int $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->data['port'] = $port;
        return $this;
    }

    /**
     * Returns a port.
     * @return int|null
     */
    public function getPort()
    {
        return isset($this->data['port']) ? $this->data['port'] : null;
    }

    /**
     * Sets a user.
     * @param string $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->data['user'] = $user;
        return $this;
    }

    /**
     * Returns a user.
     * @return string|null
     */
    public function getUser()
    {
        return isset($this->data['user']) ? $this->data['user'] : null;
    }

    /**
     * Sets a pass.
     * @param string $pass
     * @return $this
     */
    public function setPass($pass)
    {
        $this->data['pass'] = $pass;
        return $this;
    }

    /**
     * Returns a pass.
     * @return string|null
     */
    public function getPass()
    {
        return isset($this->data['pass']) ? $this->data['pass'] : null;
    }

    /**
     * Sets a path.
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->data['path'] = $path;
        return $this;
    }

    /**
     * Returns a path.
     * @return string|null
     */
    public function getPath()
    {
        return isset($this->data['path']) ? $this->data['path'] : null;
    }

    /**
     * Adding prefix to path.
     * @param string $value
     * @return $this
     */
    public function setPrefixPath($value)
    {
        $this->data['path'] = preg_replace('~/+~', '/', $value . $this->data['path']);

        return $this;
    }

    /**
     * Adding postfix to path.
     * @param string $value
     * @return $this
     */
    public function setPostfixPath($value)
    {
        $this->data['path'] = preg_replace('~/+~', '/', $this->data['path'] . $value);

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
     * Sets a query.
     * @param string $query query.
     * @return $this
     */
    public function setQuery($query)
    {
        if (!empty($query)) {
            $this->data['query'] = $this->_queryToArray($query);
        }

        return $this;
    }

    /**
     * Returns a query.
     * @return string
     */
    public function getQuery()
    {
        if (!isset($this->data['query'])) {
            return null;
        }

        return $this->_queryToString($this->data['query']);
    }

    /**
     * Sets a query params.
     * @param array $queryParams list arguments.
     * @return $this
     */
    public function setQueryParams(array $queryParams)
    {
        $this->data['query'] = $queryParams;

        return $this;
    }

    /**
     * Adds a query params.
     * @param array $queryParams list arguments.
     * @return $this
     */
    public function addQueryParams(array $queryParams)
    {
        $this->data['query'] = array_merge(Helper::getValue($this->data['query'], []), $queryParams);
        $this->data['query'] = array_filter($this->data['query']);
        return $this;
    }

    /**
     * Returns a query params.
     * @return array|null
     */
    public function getQueryParams()
    {
        if (!isset($this->data['query'])) {
            return null;
        }

        return $this->_queryToArray($this->data['query']);
    }

    /**
     * Removes a query params.
     * @param array $queryParams arguments
     * @return $this
     */
    public function removeQueryParams(array $queryParams)
    {
        if (empty($this->data['query'])) {
            return $this;
        }

        $this->data['query'] = array_diff_key(
            $this->data['query'],
            array_flip($queryParams)
        );

        return $this;
    }

    /**
     * Removes query.
     * @return $this
     */
    public function removeQuery()
    {
        $this->data['query'] = null;
        return $this;
    }

    /**
     * Sets a fragment/anchor.
     * @param string $anchor
     * @return $this
     */
    public function setFragment($anchor)
    {
        $this->data['fragment'] = $anchor;

        return $this;
    }

    /**
     * Removes a fragment/anchor.
     * @return $this
     */
    public function removeFragment()
    {
        $this->data['fragment'] = null;

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
        $callback = function ($value) use ($placeholders) {
            return StringHelper::replace($value, $placeholders, false);
        };
        $this->data = ArrayHelper::map($this->data, $callback, true);
        return $this;
    }

    /**
     * Custom formatting.
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
     * @param string $scheme
     * @return null|string
     */
    public function get($scheme = self::REL)
    {
        $data = $this->data;
        if ($scheme == self::REL) {
            unset($data['scheme'], $data['host'], $data['user'], $data['pass'], $data['port']);
            return $this->build($data);
        }
        if (!isset($data['host'])) {
            $data['host'] = $this->request->getHost();
        }
        if (!isset($data['scheme'])) {
            $scheme = self::SHORT_ABS;
        }

        if ($scheme == self::SHORT_ABS) {
            unset($data['scheme']);
            $url = $this->build($data, '//');
        } else {
            $url = $this->build($data);
        }

        return $this->asProtect($url, $data['host']);
    }

    /**
     * Returns absolute URL: `/`.
     * @return null|string
     */
    public function getRelative()
    {
        return $this->get(self::REL);
    }

    /**
     * Returns absolute URL: `http://site.com`.
     * @return null|string
     */
    public function getAbsolute()
    {
        return $this->get(self::ABS);
    }

    /**
     * Returns absolute URL: `//site.com`.
     * @return null|string
     */
    public function getShortAbsolute()
    {
        return $this->get(self::SHORT_ABS);
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
     * Sets a URL-data by key.
     * @param string $key key of data.
     * @param $value
     */
    public function offsetSet($key, $value)
    {
        if ($key === 'query' && isset($value)) {
            $value = $this->_queryToArray($value);
        }
        $this->data[$key] = $value;
    }

    /**
     * Removes a URL-data by key.
     * @param string $key key of data.
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Returns a list data of URL.
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

    public function setAllowedDomains(array $domains)
    {
        foreach ($domains as &$domain) {
            $domain = Alias::getAlias($domain);
        }
        $this->_allowedDomains = $domains;
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

    protected function build(array $data, $prefix = null)
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
        return $prefix . http_build_url($data);
    }

    protected static function modifyInternal(Url $self, array $modify)
    {
        $placeholders = [];
        foreach ($modify as $key => $value) {
            if (is_int($key)) {
                if (empty($value)) {
                    continue;
                }
                if ($value === '!#') {
                    $self->removeFragment();
                    continue;
                }

                if ($value === '!') {
                    $self->removeQuery();
                    continue;
                }
                if ($value[0] === '!') {
                    $self->removeQueryParams([mb_substr($value, 1, mb_strlen($value, 'UTF-8'), 'UTF-8')]);
                    continue;
                }
                continue;
            }

            if ($key === '#') {
                $self->setFragment($value);
                continue;
            }

            if ($key[0] === '@') {
                $self->{substr($key, 1)} = $value;
                continue;
            }

            if ($key[0] === '+') {
                $placeholders[mb_substr($key, 1, mb_strlen($key, 'UTF-8'), 'UTF-8')] = $value;
                continue;
            }

            $self->addQueryParams([$key => $value]);
        }
        $self->replace($placeholders);

        return $self;
    }

    protected function asProtect($url, $host)
    {
        if (empty($this->_allowedDomains)) {
            if ($_host = $this->request->getHost()) {
                $this->_allowedDomains = [$_host];
            }
        }
        if ($this->protect && isset($this->protectLink) && !in_array($host, $this->_allowedDomains, true)) {
            $this->protectLink = (array)$this->protectLink;
            if (!isset($this->protectLink['@scheme'])) {
                $this->protectLink['@scheme'] = self::ABS;
            }
            return static::modify($this->protectLink) . "?r={$url}";
        }
        return $url;
    }

    private function _queryToArray($query)
    {
        if (!is_array($query)) {
            parse_str($query, $query);
        }
        return $query;
    }

    private function _queryToString($query)
    {
        if (is_array($query)) {
            $query = http_build_query($query);
        }
        return preg_replace('/%5B[0-9]+%5D/i', '%5B%5D', $query);
    }
}
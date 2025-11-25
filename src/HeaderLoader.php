<?php

namespace Laminas\Http;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use ReturnTypeWillChange;
use Traversable;

use function class_exists;
use function is_array;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function strtolower;

/**
 * Plugin Class Loader implementation for HTTP headers
 */
class HeaderLoader implements IteratorAggregate
{
    /** @var array<string, class-name> Pre-aliased Header plugins */
    protected $plugins = [
        'accept'                  => Header\Accept::class,
        'acceptcharset'           => Header\AcceptCharset::class,
        'acceptencoding'          => Header\AcceptEncoding::class,
        'acceptlanguage'          => Header\AcceptLanguage::class,
        'acceptranges'            => Header\AcceptRanges::class,
        'age'                     => Header\Age::class,
        'allow'                   => Header\Allow::class,
        'authenticationinfo'      => Header\AuthenticationInfo::class,
        'authorization'           => Header\Authorization::class,
        'cachecontrol'            => Header\CacheControl::class,
        'connection'              => Header\Connection::class,
        'contentdisposition'      => Header\ContentDisposition::class,
        'contentencoding'         => Header\ContentEncoding::class,
        'contentlanguage'         => Header\ContentLanguage::class,
        'contentlength'           => Header\ContentLength::class,
        'contentlocation'         => Header\ContentLocation::class,
        'contentmd5'              => Header\ContentMD5::class,
        'contentrange'            => Header\ContentRange::class,
        'contentsecuritypolicy'   => Header\ContentSecurityPolicy::class,
        'contenttransferencoding' => Header\ContentTransferEncoding::class,
        'contenttype'             => Header\ContentType::class,
        'cookie'                  => Header\Cookie::class,
        'date'                    => Header\Date::class,
        'etag'                    => Header\Etag::class,
        'expect'                  => Header\Expect::class,
        'expires'                 => Header\Expires::class,
        'featurepolicy'           => Header\FeaturePolicy::class,
        'from'                    => Header\From::class,
        'host'                    => Header\Host::class,
        'ifmatch'                 => Header\IfMatch::class,
        'ifmodifiedsince'         => Header\IfModifiedSince::class,
        'ifnonematch'             => Header\IfNoneMatch::class,
        'ifrange'                 => Header\IfRange::class,
        'ifunmodifiedsince'       => Header\IfUnmodifiedSince::class,
        'keepalive'               => Header\KeepAlive::class,
        'lastmodified'            => Header\LastModified::class,
        'location'                => Header\Location::class,
        'maxforwards'             => Header\MaxForwards::class,
        'origin'                  => Header\Origin::class,
        'pragma'                  => Header\Pragma::class,
        'proxyauthenticate'       => Header\ProxyAuthenticate::class,
        'proxyauthorization'      => Header\ProxyAuthorization::class,
        'range'                   => Header\Range::class,
        'referer'                 => Header\Referer::class,
        'refresh'                 => Header\Refresh::class,
        'retryafter'              => Header\RetryAfter::class,
        'server'                  => Header\Server::class,
        'setcookie'               => Header\SetCookie::class,
        'te'                      => Header\TE::class,
        'trailer'                 => Header\Trailer::class,
        'transferencoding'        => Header\TransferEncoding::class,
        'upgrade'                 => Header\Upgrade::class,
        'useragent'               => Header\UserAgent::class,
        'vary'                    => Header\Vary::class,
        'via'                     => Header\Via::class,
        'warning'                 => Header\Warning::class,
        'wwwauthenticate'         => Header\WWWAuthenticate::class,
    ];

    /** @var array<string,string> */
    protected static $staticMap = [];

    /**
     * @param null|array|Traversable $map
     */
    public function __construct($map = null)
    {
        if (! empty(static::$staticMap)) {
            $this->registerPlugins(static::$staticMap);
        }

        if ($map !== null) {
            $this->registerPlugins($map);
        }
    }

    /**
     * Register a class to a given short name
     */
    public function registerPlugin($shortName, $className): self
    {
        $this->plugins[strtolower($shortName)] = $className;
        return $this;
    }

    /**
     * Register many plugins at once
     *
     * @param string|array|Traversable $map
     */
    public function registerPlugins($map): self
    {
        if (is_string($map)) {
            if (! class_exists($map)) {
                throw new InvalidArgumentException('Map class provided is invalid');
            }
            $map = new $map();
        }
        if (is_array($map)) {
            $map = new ArrayIterator($map);
        }
        if (! $map instanceof Traversable) {
            throw new InvalidArgumentException('Map provided is invalid; must be traversable');
        }

        if ($map instanceof IteratorAggregate) {
            $map = $map->getIterator();
        }

        foreach ($map as $name => $class) {
            if (is_int($name) || is_numeric($name)) {
                if (! is_object($class) && class_exists($class)) {
                    $class = new $class();
                }

                if ($class instanceof Traversable) {
                    $this->registerPlugins($class);
                    continue;
                }
            }

            $this->registerPlugin($name, $class);
        }

        return $this;
    }

    public function isLoaded($name): bool
    {
        $lookup = strtolower($name);
        return isset($this->plugins[$lookup]);
    }

    /**
     * @return string|false
     */
    public function load($name)
    {
        if (! $this->isLoaded($name)) {
            return false;
        }
        return $this->plugins[strtolower($name)];
    }

    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->plugins);
    }
}

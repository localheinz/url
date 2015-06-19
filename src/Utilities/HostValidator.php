<?php
/**
 * This file is part of the League.url library
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/thephpleague/url/
 * @version 4.0.0
 * @package League.url
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace League\Url\Utilities;

use InvalidArgumentException;
use League\Url\Interfaces;

/**
 * A Trait to validate a Host component
 *
 * @package League.url
 * @since 4.0.0
 */
trait HostValidator
{
    /**
     * CollectionComponent delimiter
     *
     * @var string
     */
    protected static $delimiter = '.';

    /**
     * Is the Host an IPv4
     * @var bool
     */
    protected $host_as_ipv4 = false;

    /**
     * Is the Host an IPv6
     * @var bool
     */
    protected $host_as_ipv6 = false;

    /**
     * Trait to handle punycode
     */
    use Punycode;

    /**
     * {@inheritdoc}
     */
    public function isIp()
    {
        return $this->host_as_ipv4 || $this->host_as_ipv6;
    }

    /**
     * {@inheritdoc}
     */
    public function isIpv4()
    {
        return $this->host_as_ipv4;
    }

    /**
     * {@inheritdoc}
     */
    public function isIpv6()
    {
        return $this->host_as_ipv6;
    }

    /**
     * Validate a Host as an IP
     *
     * @param string $str
     *
     * @throws InvalidArgumentException if the IP based host is malformed
     *
     * @return array
     */
    protected function validateIpHost($str)
    {
        $res = $this->filterIpv6Host($str);
        if (!empty($res)) {
            $this->host_as_ipv4 = false;
            $this->host_as_ipv6 = true;
            return [$res];
        }

        if (filter_var($str, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->host_as_ipv4 = true;
            $this->host_as_ipv6 = false;
            return [$str];
        }

        $this->host_as_ipv4 = false;
        $this->host_as_ipv6 = false;
        return [];
    }

    /**
     * validate and filter a Ipv6 Hostname
     *
     * @param string $str
     *
     * @return string|false
     */
    protected function filterIpv6Host($str)
    {
        preg_match(',^([\[]?)(.*?)([\]]?)$,', $str, $matches);
        if (!in_array(strlen($matches[1].$matches[3]), [0, 2])) {
            return false;
        }

        if (!filter_var($this->validateScopeIp($matches[2]), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        return strtolower(rawurldecode($matches[2]));
    }

    public function validateScopeIp($ip)
    {
        $str = strtoupper($ip);
        if (0 !== strpos($str, 'FE80') || false === ($pos = strpos($str, '%'))) {
            return $ip;
        }
        $ipv6 = strtolower(substr($str, 0, $pos));
        $zone_id = substr($str, $pos + 1);

        if (preg_match(',[^\x20-\x7f],', $zone_id) || preg_match('/[?#@\[\]]/', $zone_id)) {
            return $ip;
        }

        return $ipv6;
    }

    /**
     * Validate a string only host
     *
     * @param string $str
     *
     * @throws InvalidArgumentException If the string failed to be a valid hostname
     *
     * @return array
     */
    protected function validateStringHost($str)
    {
        $str = $this->lower($this->setIsAbsolute($str));
        $labels = array_map(function ($value) {
            return $this->encodeLabel($value);
        }, explode(static::$delimiter, $str));

        $this->assertValidHost($labels);

        return array_map(function ($label) {
            return $this->decodeLabel($label);
        }, $labels);
    }

    /**
     * set the FQDN property
     *
     * @param string $str
     *
     * @return string
     */
    abstract protected function setIsAbsolute($str);

    /**
     * Convert to lowercase a string without modifying unicode characters
     *
     * @param string $str
     *
     * @return string
     */
    protected function lower($str)
    {
        $res = [];
        for ($i = 0, $length = mb_strlen($str, 'UTF-8'); $i < $length; $i++) {
            $char = mb_substr($str, $i, 1, 'UTF-8');
            if (ord($char) < 128) {
                $char = strtolower($char);
            }
            $res[] = $char;
        }

        return implode('', $res);
    }

    /**
     * Validate a String Label
     *
     * @param array $labels found host labels
     *
     * @throws InvalidArgumentException If the validation fails
     */
    protected function assertValidHost(array $labels)
    {
        $verifs = array_filter($labels, function ($value) {
            return !empty($value);
        });

        if ($verifs != $labels) {
            throw new InvalidArgumentException('Invalid Hostname, verify labels');
        }

        $this->isValidLength($labels);
        $this->isValidLabelsCount($labels);
        $this->isValidContent($labels);
    }

    /**
     * Validate Host label length
     *
     * @param array $data Host labels
     *
     * @throws InvalidArgumentException If the validation fails
     */
    protected function isValidLength(array $data)
    {
        $res = array_filter($data, function ($label) {
            return strlen($label) > 63;
        });

        if (!empty($res)) {
            throw new InvalidArgumentException('Invalid Hostname, verify its length');
        }
    }

    /**
     * Validated the Host Label Pattern
     *
     * @param array $data Host CollectionComponent
     *
     * @throws InvalidArgumentException If the validation fails
     */
    protected function isValidContent(array $data)
    {
        $res = preg_grep('/^[0-9a-z]([0-9a-z-]{0,61}[0-9a-z])?$/i', $data, PREG_GREP_INVERT);

        if (!empty($res)) {
            throw new InvalidArgumentException('Invalid Hostname, verify its content');
        }
    }

    /**
     * Validated the Host Label Count
     *
     * @param array $data Host CollectionComponent
     *
     * @throws InvalidArgumentException If the validation fails
     */
    abstract protected function isValidLabelsCount(array $data = []);
}
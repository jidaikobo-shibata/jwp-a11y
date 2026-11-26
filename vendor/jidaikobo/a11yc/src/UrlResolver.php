<?php

namespace Jidaikobo\A11yc;

use Jidaikobo\A11yc\Arr;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

final class UrlResolver
{
    public function hostFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $scheme = (string) Arr::get($parts, 'scheme', '');
        $host = (string) Arr::get($parts, 'host', '');
        $port = Arr::get($parts, 'port');
        if ($scheme === '' || $host === '') {
            return null;
        }

        $base = $scheme . '://' . $host;
        if ($port) {
            $base .= ':' . (string) $port;
        }

        return $base;
    }

    public function normalize(string $url, ?string $base_url = null): string
    {
        $url = trim(str_replace('&amp;', '&', $url));
        if ($url === '') {
            return '';
        }

        if (($base_url === null || $base_url === '') && defined('A11YC_URL')) {
            $base_url = $this->hostFromUrl(A11YC_URL);
        }

        try {
            $reference = new Uri($url);
            if ($base_url === null || $base_url === '') {
                return (string) $reference;
            }

            return (string) UriResolver::resolve(new Uri($base_url), $reference);
        } catch (\InvalidArgumentException $e) {
            return $url;
        }
    }
}

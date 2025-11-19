<?php

namespace Lemmon\Gitstats;

use Kirby\Http\Remote;
use Throwable;

/**
 * Fetches repository statistics from GitHub and normalizes output.
 */
class GitStats
{
    public const CACHE_NAMESPACE = 'lemmon.gitstats';
    public const CACHE_KEY_PREFIX = 'repo.';
    public const CACHE_DEFAULT_LOWER_TTL = 1440; // minutes, 24h intended refresh
    public const CACHE_DEFAULT_UPPER_TTL = 10080; // minutes, 7d hard expiry
    protected const MAX_REFRESHES_PER_REQUEST = 1;

    /**
     * Track how many entries were refreshed during the current request.
     */
    protected static int $refreshesThisRequest = 0;

    /**
     * Resolve a repository reference, fetch metadata, and return normalized stats.
     */
    public static function fetch(string $value): ?array
    {
        $repository = static::parse($value);

        if ($repository === null) {
            return null;
        }

        $cacheTtlLower = max(0, (int) \option('lemmon.gitstats.cacheTtlLower', self::CACHE_DEFAULT_LOWER_TTL));
        $cacheTtlUpper = max($cacheTtlLower, (int) \option('lemmon.gitstats.cacheTtlUpper', self::CACHE_DEFAULT_UPPER_TTL));
        $cacheKey = self::CACHE_KEY_PREFIX . $repository['slug'];
        $cacheConfig = \option('lemmon.gitstats.cache');
        $cache = \kirby()->cache(self::CACHE_NAMESPACE, is_array($cacheConfig) ? $cacheConfig : null);

        $cached = ($cacheTtlUpper !== 0) ? $cache->get($cacheKey) : null;
        $cachedData = is_array($cached) && isset($cached['fetched_at']) ? ($cached['data'] ?? null) : null;

        if ($cachedData !== null) {
            $ageMinutes = (time() - (int) $cached['fetched_at']) / 60;

            // Within desired freshness window: return immediately.
            if ($ageMinutes <= $cacheTtlLower) {
                return $cachedData;
            }
        }

        // Decide whether to refresh now (limit refreshes per request).
        $shouldRefresh = ($cachedData === null)
            || ($cacheTtlUpper === 0)
            || ($cachedData !== null && $ageMinutes >= $cacheTtlLower && self::$refreshesThisRequest < self::MAX_REFRESHES_PER_REQUEST)
            || ($cachedData !== null && $ageMinutes >= $cacheTtlUpper);

        if (!$shouldRefresh) {
            return $cachedData;
        }

        $stats = static::fetchGithub($repository);

        if ($stats !== null) {
            self::$refreshesThisRequest++;
            if ($cacheTtlUpper !== 0) {
                $cache->set($cacheKey, [
                    'fetched_at' => time(),
                    'data' => $stats,
                ], $cacheTtlUpper);
            }
            return $stats;
        }

        // Fall back to cached data when refresh fails.
        return $cachedData;
    }

    /**
     * Parse user-provided value into repository parts.
     */
    public static function parse(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('~^https?://github\.com/([^/\s]+)/([^/\s#?]+)~i', $value, $matches)) {
            $owner = $matches[1];
            $name = $matches[2];
        } elseif (preg_match('~^[\w.-]+/[\w.-]+$~', $value)) {
            [$owner, $name] = explode('/', $value, 2);
        } else {
            return null;
        }

        $owner = static::normalizePart($owner);
        $name = static::normalizePart($name);

        if ($owner === '' || $name === '') {
            return null;
        }

        return [
            'provider' => 'github',
            'owner' => $owner,
            'name' => $name,
            'slug' => $owner . '/' . $name,
        ];
    }

    /**
     * Normalize repository name/owner segments.
     */
    protected static function normalizePart(string $part): string
    {
        $part = trim($part);
        $part = preg_replace('~\.git$~i', '', $part);
        $part = trim($part, '/');
        $part = strtolower($part);

        return $part;
    }

    /**
     * Fetch data from GitHub REST API.
     */
    protected static function fetchGithub(array $repository): ?array
    {
        $endpoint = 'https://api.github.com/repos/' . $repository['slug'];

        try {
            $response = Remote::get($endpoint, [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'lemmon-gitstats',
                ],
                'timeout' => 5,
            ]);
        } catch (Throwable $exception) {
            return null;
        }

        if ($response->code() !== 200) {
            return null;
        }

        $data = $response->json();

        if (!is_array($data)) {
            return null;
        }

        return [
            'provider' => 'github',
            'slug' => $repository['slug'],
            'owner' => $repository['owner'],
            'name' => $data['name'] ?? $repository['name'],
            'full_name' => $data['full_name'] ?? $repository['slug'],
            'description' => $data['description'] ?? null,
            'url' => $data['html_url'] ?? null,
            'homepage' => $data['homepage'] ?? null,
            'stars' => $data['stargazers_count'] ?? null,
            'forks' => $data['forks_count'] ?? null,
            'watchers' => $data['subscribers_count'] ?? ($data['watchers_count'] ?? null),
            'open_issues' => $data['open_issues_count'] ?? null,
            'default_branch' => $data['default_branch'] ?? null,
            'language' => $data['language'] ?? null,
            'updated_at' => $data['pushed_at'] ?? ($data['updated_at'] ?? null),
        ];
    }
}

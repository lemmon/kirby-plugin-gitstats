# Git Stats for Kirby

Single-method helper for Kirby that exposes GitHub stats through any text field. Provide `owner/repo` or a full URL to receive stars, forks, watchers, open issues and other metadata, cached efficiently for repeated use.

## Installation

### Composer

```bash
composer require lemmon/kirby-gitstats
```

### Git Submodule

```bash
git submodule add https://github.com/lemmon/kirby-plugin-gitstats.git site/plugins/gitstats
```

### Manual

[Download the plugin](https://api.github.com/repos/lemmon/kirby-plugin-gitstats/zipball) and extract it to `/site/plugins/gitstats`.

## Usage

Add a text field to your blueprint and call the field method to fetch stats:

```yaml
fields:
    repository:
        label: Repository
        type: text
        placeholder: lemmon/validator-php
```

```php
<?php if ($stats = $page->repository()->toGitStats()): ?>
  <dl>
    <dt>Stars</dt><dd><?= $stats['stars'] ?></dd>
    <dt>Forks</dt><dd><?= $stats['forks'] ?></dd>
    <dt>Watchers</dt><dd><?= $stats['watchers'] ?></dd>
    <dt>Open issues</dt><dd><?= $stats['open_issues'] ?></dd>
    <dt>URL</dt><dd><a href="<?= $stats['url'] ?>"><?= $stats['url'] ?></a></dd>
  </dl>
<?php endif; ?>
```

Accepted field values:

-   GitHub shorthand: `lemmon/validator-php`
-   Full URL: `https://github.com/lemmon/validator-php`

The first release defaults to GitHub. Other providers may follow later.

## Returned fields

Each successful call returns an associative array similar to:

```php
[
  'provider' => 'github',
  'slug' => 'lemmon/validator-php',
  'owner' => 'lemmon',
  'name' => 'validator-php',
  'full_name' => 'lemmon/validator-php',
  'description' => 'Lightweight validator',
  'url' => 'https://github.com/lemmon/validator-php',
  'homepage' => null,
  'stars' => 123,
  'forks' => 4,
  'watchers' => 8,
  'open_issues' => 2,
  'language' => 'PHP',
  'default_branch' => 'main',
  'updated_at' => '2024-05-01T17:42:08Z',
]
```

Null is returned when the value cannot be parsed or the repository is not reachable.

## Configuration

| Option                            | Default      | Purpose                                                                                                |
| --------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------ |
| `lemmon.gitstats.cacheTtlLower`   | `1440` (24h) | Preferred refresh interval in minutes; cached data newer than this is returned without refresh.        |
| `lemmon.gitstats.cacheTtlUpper`   | `10080` (7d) | Hard cache lifetime in minutes; entries older than this are refreshed and persisted again.             |
| `lemmon.gitstats.cacheRetryDelay` | `60` (1h)    | Retry/backoff window in minutes used when GitHub refresh fails; cached data is reused until it passes. |

Example Kirby config override:

```php
return [
  'lemmon.gitstats.cacheTtlLower' => 1440,  // refresh roughly daily
  'lemmon.gitstats.cacheTtlUpper' => 20160, // keep entries up to 14 days
  'lemmon.gitstats.cacheRetryDelay' => 30,  // backoff for 30 minutes after a failed refresh
];
```

### Retry and provider backoff

-   When cached data is older than `cacheTtlLower`, a refresh is attempted.
-   If GitHub responds or times out with an error, the plugin reuses cached data, sets `next_refresh_at` for that repository, and marks GitHub as temporarily down for `cacheRetryDelay` minutes.
-   While GitHub is marked down, other repositories served from cache skip refresh attempts; they resume after the backoff window.

## Roadmap

-   [ ] Add support for other common Git providers (GitLab, Bitbucket, Gitea).
-   [ ] Introduce a CLI helper to refresh cached stats in the background.
-   [x] When refresh attempts fail after `cacheTtlLower` is met, reuse cached data and delay the next refresh (e.g., 1h or the next `cacheTtlLower` window).

## License

MIT License. See `LICENSE` for details.

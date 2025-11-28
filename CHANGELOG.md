# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

-   Add provider-level backoff and per-repo retry delays so failed refreshes reuse cached data and pause further GitHub calls.
-   Introduce `lemmon.gitstats.cacheRetryDelay` to configure retry/backoff window; documented configuration and roadmap progress.

## [0.1.0] - 2025-11-19

-   Initial release of the Kirby Git Stats plugin with `toGitStats` field method for GitHub repositories.

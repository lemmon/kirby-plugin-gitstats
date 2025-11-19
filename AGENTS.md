# Repository Guidelines

## Plugin Purpose & Scope

-   Provide a lean `gitstats` field method that resolves repository identifiers (long GitHub URLs or `owner/repo` shorthand) into normalized metadata.
-   Keep the plugin read-only and lightweight; prioritize GitHub first, with room to extend to other providers later.
-   Normalize common stats such as stars, forks, watchers/subscribers, open issues, URLs, descriptions, language, default branch, and timestamps.
-   Cache responses through Kirby’s cache component with a sensible default TTL and an option to disable caching during development.

## Project Structure & Module Organization

-   `index.php` registers the plugin at `lemmon/gitstats`, sets defaults, and exposes the `toGitStats` field method.
-   `src/GitStats.php` parses repository references, handles network calls, normalizes responses, and manages caching.
-   Add future helpers inside `src/`. Keep blueprints and snippets under `blueprints/` and `snippets/` if UI elements are introduced later.
-   Keep AGENTS.md focused on contributor process; place deeper implementation notes in `TECHNICAL-NOTES.md` if needed.
-   Assume the code always runs inside a Kirby instance; no guards for the global `kirby()` helper are needed.

## Coding Style & Naming Conventions

-   Follow PSR-12 for PHP: four-space indentation, brace on the next line for classes and methods, and meaningful namespaces (e.g., `Lemmon\Gitstats`).
-   Name Kirby blueprints and snippets using lowercase single-word identifiers (`gitstats.yml`, `gitstats.php`) if/when added.
-   Stick to ASCII punctuation in code, docs, and comments (prefer `--` over an em dash) so diffs stay predictable.
-   Document non-trivial helpers with concise docblocks. Prefer descriptive method names such as `fetchGithub`.
-   Reserve emojis for rare emphasis; moderate use is fine, but avoid emoji-driven lists.
-   Use GitHub-style unchecked checkboxes (`- [ ]`) when documenting roadmap items to keep documentation consistent.

## Testing Guidelines

-   Add PHPUnit under `tests/` when functionality expands; start with configuration in `phpunit.xml.dist`.
-   Name test classes after the class under test (`GitStatsTest`). Execute locally with `vendor/bin/phpunit`.
-   Maintain manual regression notes in `docs/testing.md` until automated coverage is available.

## Commit & Pull Request Guidelines

-   Follow the Conventional Commits spec (`fix:`, `refactor:`, `docs:`) and keep messages in the imperative mood.
-   Use concise Conventional Commit summaries: `<type>(<scope>): <short action>`. Avoid verbose release blurbs in commit messages; keep release notes in CHANGELOG/release tagging.
-   Ensure each commit addresses a single concern; couple tests with implementation, but leave unrelated formatting for a separate change.
-   Reference related issues in commit bodies using `Refs #123` when applicable.
-   PRs must summarize intent, list functional changes, and include screenshots or GIFs when UI elements are added.
-   Prefer annotated tags for releases (author, date, message/signing) over lightweight tags.
-   Annotated tags should use `vX.Y.Z - <concise headline>`; keep detailed notes in CHANGELOG/releases.

## Documentation Practices

-   Add concise PHPDoc blocks where behavior is not immediately obvious, especially for helpers touching I/O streams.

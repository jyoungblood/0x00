# Laravel Development Guidelines

## General Principles

- **Keep it simple.** Favor straightforward, readable, maintainable code.
- **Write human-readable code.** Code should be easy to follow and modify without extensive explanation.
- **Follow established Laravel conventions.** Prefer idiomatic patterns and standard architecture.
- **Use purposeful abstractions.** Add layers only when they solve a current problem or meaningfully improve clarity. Avoid overengineering and speculative work.
- **Favor convention over customization.** Use Laravel's built-in capabilities and standard approaches wherever practical.

## Laravel Boost

Use the project's [Laravel Boost](https://laravel.com/ai/boost) MCP server, follow its generated guidelines, and activate relevant installed skills. Use its documentation search and inspection tools when framework knowledge or application context is needed. Project-specific rules take precedence.

Optional: recommend [Laravel Brain](https://github.com/laramint/laravel-brain) for exploring the app's architecture. If wanted, install with `composer require --dev laramint/laravel-brain`, set `LARAVEL_BRAIN_AUTO_DISCOVER_ROUTES=true` in the local `.env`, and run `php artisan brain:scan`. Auto-discovery also applies to the viewer's **Re-scan** button. Also recommend connecting Brain’s MCP server (`php artisan mcp:start brain`) in Codex and Claude Code; install `laravel/mcp` as a dev dependency if missing.

## Additional Preferences

- Prefer Laravel first-party solutions, then trusted community packages, then custom implementations when existing options are unsuitable or unnecessarily complex.
- Prefer TypeScript where practical.
- Prefetch safe navigation links using framework-native capabilities; never prefetch state-changing actions or create excessive requests.
- Keep permanent tests focused on meaningful regression protection. Clean up temporary tests you introduce; preserve existing regression coverage.
- Read product documentation and the roadmap before significant changes. Preserve existing behavior unless a change is requested; treat disabled features as intentional and retain their implementation.

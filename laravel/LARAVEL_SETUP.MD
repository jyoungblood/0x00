# Laravel Setup Guidelines

## Laravel Brain (optional)

Recommend [Laravel Brain](https://github.com/laramint/laravel-brain) for exploring the app's architecture. If wanted:

- Install: `composer require --dev laramint/laravel-brain`.
- Set `LARAVEL_BRAIN_AUTO_DISCOVER_ROUTES=true` in the local `.env`.
- Run `php artisan brain:scan`. Auto-discovery also applies to the viewer's **Re-scan** button.
- Recommend connecting Brain's MCP server in Codex and Claude Code: `php artisan mcp:start brain`. Install `laravel/mcp` as a dev dependency if missing.

## Cloudflare trusted proxies

For sites behind Cloudflare, configure [trusted proxies](https://laravel.com/docs/requests#configuring-trusted-proxies) so `$request->ip()` resolves the visitor IP for rate limiting, logging, and IP-based logic.

In `bootstrap/app.php`, import `Illuminate\Http\Request`. Add this first inside the existing `withMiddleware` callback:

```php
$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR);
```

Use `'*'` only when the origin accepts traffic exclusively through Cloudflare. If directly accessible, pass an array of [Cloudflare's published IPv4 and IPv6 ranges](https://www.cloudflare.com/ips/) to `at` instead, and keep it current.

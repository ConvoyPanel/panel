<?php

namespace Convoy\Services\Announcements;

/**
 * Where an announcement may link to: convoypanel.com and the ConvoyPanel
 * organisation on GitHub, over HTTPS. The panel refuses any other link even in
 * a feed whose signature verifies, so a leaked signing key still can't point
 * admins at a page of someone else's choosing.
 */
final class Links
{
    public static function allowed(?string $url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 500 || preg_match('/[\s\\\\]/', $url) === 1) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false
            || ($parts['scheme'] ?? null) !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        return match ($host) {
            'convoypanel.com' => str_starts_with($url, 'https://convoypanel.com/') || $url === 'https://convoypanel.com',
            'github.com' => str_starts_with($path, '/ConvoyPanel/') && str_starts_with($url, 'https://github.com/ConvoyPanel/'),
            default => false,
        };
    }

    /**
     * The body with every Markdown link to somewhere not allowed replaced by its
     * text. The page and the mail render links and inline code only, so this
     * is the one place a body could point elsewhere.
     */
    public static function stripDisallowed(string $markdown): string
    {
        return (string) preg_replace_callback(
            '/\[([^\]\n]*)\]\(([^)\s]*)\)/',
            fn (array $match) => self::allowed($match[2]) ? $match[0] : $match[1],
            $markdown,
        );
    }
}

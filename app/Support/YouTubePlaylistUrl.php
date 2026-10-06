<?php

namespace App\Support;

class YouTubePlaylistUrl
{
    public static function isValidEmbed(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        if (! in_array($host, ['www.youtube.com', 'youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            return false;
        }

        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if (isset($query['list']) && str_starts_with($query['list'], 'PL')) {
            return true;
        }

        return str_contains($path, '/embed/videoseries');
    }

    public static function embedUrl(?string $url, bool $muted): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);

        $query['autoplay'] = '1';
        $query['mute'] = $muted ? '1' : '0';
        $query['rel'] = '0';
        $query['enablejsapi'] = '1';
        $query['origin'] = rtrim((string) config('app.url'), '/');

        if (isset($query['list'])) {
            $query['loop'] = '1';
        }

        $path = $parts['path'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return sprintf(
            '%s://%s%s%s?%s',
            $parts['scheme'],
            $parts['host'],
            $port,
            $path,
            http_build_query($query),
        );
    }
}

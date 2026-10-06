<?php

namespace App\Support;

class DisplayVideoUrl
{
    public static function embedUrl(?string $url, bool $muted): ?string
    {
        if (YouTubePlaylistUrl::isValidEmbed($url)) {
            return YouTubePlaylistUrl::embedUrl($url, $muted);
        }

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

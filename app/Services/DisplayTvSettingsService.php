<?php

namespace App\Services;

use App\DTOs\Display\SignageContentItem;
use App\Enums\MediaAssetType;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DisplayTvSettingsService
{
    public const KEY_VIDEO_ENABLED = 'display_tv_video_enabled';

    public const KEY_VIDEO_SOURCE = 'display_tv_video_source';

    public const KEY_VIDEO_URL = 'display_tv_video_url';

    public const KEY_VIDEO_MEDIA_ID = 'display_tv_video_media_id';

    public const KEY_VIDEO_MEDIA_IDS = 'display_tv_video_media_ids';

    public const KEY_RUNNING_TEXT_ENABLED = 'display_tv_running_text_enabled';

    public const KEY_RUNNING_TEXT = 'display_tv_running_text';

    public const KEY_VIDEO_MUTED = 'display_tv_video_muted';

    public const KEY_VIDEO_VOLUME = 'display_tv_video_volume';

    public const KEY_VIDEO_DUCK_VOLUME = 'display_tv_video_duck_volume';

    public function __construct(private SettingsService $settings) {}

    public function videoMuted(): bool
    {
        return $this->settings->getBool(self::KEY_VIDEO_MUTED, false);
    }

    public function videoVolume(): int
    {
        return min(100, max(0, $this->settings->getInt(
            self::KEY_VIDEO_VOLUME,
            (int) config('queue_system.display_tv_video_volume', 25),
        )));
    }

    public function videoDuckVolume(): int
    {
        return min($this->videoVolume(), max(0, $this->settings->getInt(
            self::KEY_VIDEO_DUCK_VOLUME,
            (int) config('queue_system.display_tv_video_duck_volume', 4),
        )));
    }

    public function videoEnabled(): bool
    {
        return $this->settings->getBool(self::KEY_VIDEO_ENABLED, false);
    }

    public function videoSource(): string
    {
        $source = $this->settings->getString(self::KEY_VIDEO_SOURCE, 'upload') ?? 'upload';

        return $source === 'url' ? 'youtube' : $source;
    }

    public function videoUrl(): ?string
    {
        return $this->settings->getString(self::KEY_VIDEO_URL);
    }

    /**
     * @return array<int, int>
     */
    public function videoMediaIds(): array
    {
        $stored = $this->settings->get(self::KEY_VIDEO_MEDIA_IDS);

        if (is_array($stored) && isset($stored['ids']) && is_array($stored['ids'])) {
            return collect($stored['ids'])
                ->map(fn (mixed $id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->values()
                ->all();
        }

        $legacyId = $this->settings->getInt(self::KEY_VIDEO_MEDIA_ID, 0);

        return $legacyId > 0 ? [$legacyId] : [];
    }

    /**
     * @return Collection<int, MediaAsset>
     */
    public function videoMediaAssets(): Collection
    {
        $ids = $this->videoMediaIds();

        if ($ids === []) {
            return collect();
        }

        $assets = MediaAsset::query()
            ->active()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?MediaAsset => $assets->get($id))
            ->filter()
            ->values();
    }

    public function videoMediaAsset(): ?MediaAsset
    {
        return $this->videoMediaAssets()->first();
    }

    public function runningTextEnabled(): bool
    {
        return $this->settings->getBool(self::KEY_RUNNING_TEXT_ENABLED, true);
    }

    public function runningText(): ?string
    {
        return $this->settings->getString(self::KEY_RUNNING_TEXT);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(): array
    {
        return [
            'video_enabled' => $this->videoEnabled(),
            'video_source' => $this->videoSource(),
            'video_url' => $this->videoUrl() ?? '',
            'running_text_enabled' => $this->runningTextEnabled(),
            'running_text' => $this->runningText() ?? '',
            'video_muted' => $this->videoMuted(),
            'video_volume' => $this->videoVolume(),
            'video_duck_volume' => $this->videoDuckVolume(),
            'video_media_items' => $this->videoMediaAssets()
                ->map(fn (MediaAsset $asset): array => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'url' => $asset->url(),
                    'size' => $asset->size,
                ])
                ->all(),
        ];
    }

    /**
     * @return Collection<int, SignageContentItem>
     */
    public function signageContents(): Collection
    {
        $items = collect();

        if ($this->videoEnabled()) {
            $video = $this->videoSignageItem();

            if ($video !== null) {
                $items->push($video);
            }
        }

        if ($this->runningTextEnabled()) {
            $text = trim($this->runningText() ?? '');

            if ($text !== '') {
                $items->push(new SignageContentItem(
                    id: 2,
                    type: 'running_text',
                    title: null,
                    body: $text,
                    mediaUrl: null,
                    externalUrl: null,
                    durationSeconds: null,
                ));
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $group = 'display_tv';
        $source = $data['video_source'] ?? 'upload';

        if ($source === 'url') {
            $source = 'youtube';
        }

        $this->settings->set(self::KEY_VIDEO_ENABLED, ['value' => (bool) ($data['video_enabled'] ?? false)], $group);
        $this->settings->set(self::KEY_VIDEO_SOURCE, ['value' => $source], $group);
        $this->settings->set(self::KEY_VIDEO_URL, ['value' => $data['video_url'] ?? ''], $group);
        $this->settings->set(self::KEY_RUNNING_TEXT_ENABLED, ['value' => (bool) ($data['running_text_enabled'] ?? false)], $group);
        $this->settings->set(self::KEY_RUNNING_TEXT, ['value' => $data['running_text'] ?? ''], $group);
        $this->settings->set(self::KEY_VIDEO_MUTED, ['value' => (bool) ($data['video_muted'] ?? false)], $group);
        $videoVolume = min(100, max(0, (int) ($data['video_volume'] ?? $this->videoVolume())));
        $videoDuckVolume = min($videoVolume, max(0, (int) ($data['video_duck_volume'] ?? $this->videoDuckVolume())));
        $this->settings->set(self::KEY_VIDEO_VOLUME, ['value' => $videoVolume], $group);
        $this->settings->set(self::KEY_VIDEO_DUCK_VOLUME, ['value' => $videoDuckVolume], $group);
    }

    public function saveVideoUpload(UploadedFile|TemporaryUploadedFile $videoUpload): MediaAsset
    {
        $group = 'display_tv';

        $this->settings->set(self::KEY_VIDEO_ENABLED, ['value' => true], $group);
        $this->settings->set(self::KEY_VIDEO_SOURCE, ['value' => 'upload'], $group);

        return $this->appendVideoUpload($videoUpload, $group);
    }

    public function removeVideoMedia(int $mediaId): void
    {
        $ids = $this->videoMediaIds();
        $remaining = array_values(array_filter($ids, fn (int $id): bool => $id !== $mediaId));

        $this->persistVideoMediaIds($remaining, 'display_tv');

        $asset = MediaAsset::query()->find($mediaId);

        if ($asset === null) {
            return;
        }

        if ($asset->path !== '') {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();
    }

    private function appendVideoUpload(UploadedFile|TemporaryUploadedFile $videoUpload, string $group): MediaAsset
    {
        $path = $videoUpload->store('display-tv/videos', 'public');

        $asset = MediaAsset::query()->create([
            'type' => MediaAssetType::Video,
            'name' => $videoUpload->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $videoUpload->getMimeType() ?: 'video/mp4',
            'size' => $videoUpload->getSize(),
            'is_active' => true,
        ]);

        $ids = [...$this->videoMediaIds(), $asset->id];
        $this->persistVideoMediaIds($ids, $group);

        return $asset;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function persistVideoMediaIds(array $ids, string $group): void
    {
        $this->settings->set(self::KEY_VIDEO_MEDIA_IDS, ['ids' => array_values($ids)], $group);

        if ($ids !== []) {
            $this->settings->set(self::KEY_VIDEO_MEDIA_ID, ['value' => $ids[0]], $group);
        }
    }

    private function videoSignageItem(): ?SignageContentItem
    {
        if ($this->videoSource() === 'upload') {
            $assets = $this->videoMediaAssets();

            if ($assets->isEmpty()) {
                return null;
            }

            $urls = $assets
                ->map(fn (MediaAsset $asset): ?string => $asset->url())
                ->filter()
                ->values()
                ->all();

            if ($urls === []) {
                return null;
            }

            return new SignageContentItem(
                id: 1,
                type: 'video',
                title: $assets->count() === 1 ? $assets->first()->name : 'Video Informasi',
                body: null,
                mediaUrl: $urls[0],
                externalUrl: null,
                durationSeconds: null,
                mediaUrls: $urls,
            );
        }

        $url = trim($this->videoUrl() ?? '');

        if ($url === '') {
            return null;
        }

        return new SignageContentItem(
            id: 1,
            type: 'video',
            title: 'YouTube Playlist',
            body: null,
            mediaUrl: null,
            externalUrl: $url,
            durationSeconds: null,
        );
    }
}

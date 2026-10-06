<?php

namespace App\Services;

use App\Enums\MediaAssetType;
use App\Models\MediaAsset;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SettingsService
{
    public const KEY_INSTITUTION_LOGO_MEDIA_ID = 'institution_logo_media_id';

    private const CACHE_KEY = 'queue.settings.map';

    private const LEGACY_CACHE_KEY = 'queue.settings.all';

    private const CACHE_TTL_SECONDS = 300;

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key]['value'] ?? null;

        return $value ?? $default;
    }

    public function getString(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key);

        if (is_array($value) && array_key_exists('value', $value)) {
            return is_string($value['value']) ? $value['value'] : (string) $value['value'];
        }

        return is_string($value) ? $value : $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        if (is_array($value)) {
            if (array_key_exists('seconds', $value)) {
                return (int) $value['seconds'];
            }

            if (array_key_exists('value', $value)) {
                return (int) $value['value'];
            }
        }

        return is_numeric($value) ? (int) $value : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if (is_array($value) && array_key_exists('value', $value)) {
            return filter_var($value['value'], FILTER_VALIDATE_BOOLEAN);
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => is_array($value) ? $value : ['value' => $value],
            ],
        );

        $this->forgetCache();

        return $setting;
    }

    public function setString(string $key, ?string $value, string $group = 'general'): Setting
    {
        return $this->set($key, ['value' => $value], $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $result = [];

        foreach ($this->all() as $key => $setting) {
            if (($setting['group'] ?? null) !== $group) {
                continue;
            }

            $result[$key] = $setting['value'] ?? null;
        }

        return $result;
    }

    public function institutionName(): string
    {
        return $this->getString('institution_name')
            ?? config('queue_system.institution_name', 'Layanan Publik');
    }

    public function institutionTagline(): string
    {
        return $this->getString('institution_tagline')
            ?? config('queue_system.institution_tagline', 'Sistem Antrian Digital');
    }

    public function institutionLogoMediaId(): ?int
    {
        $mediaId = $this->getInt(self::KEY_INSTITUTION_LOGO_MEDIA_ID, 0);

        return $mediaId > 0 ? $mediaId : null;
    }

    public function institutionLogoAsset(): ?MediaAsset
    {
        $mediaId = $this->institutionLogoMediaId();

        if ($mediaId === null) {
            return null;
        }

        return MediaAsset::query()
            ->active()
            ->whereKey($mediaId)
            ->where('type', MediaAssetType::Image->value)
            ->first();
    }

    public function institutionLogoUrl(): ?string
    {
        return $this->institutionLogoAsset()?->url();
    }

    public function saveInstitutionLogo(UploadedFile|TemporaryUploadedFile $logoUpload): MediaAsset
    {
        $previous = $this->institutionLogoAsset();
        $path = $logoUpload->store('institution/logos', 'public');

        $asset = MediaAsset::query()->create([
            'type' => MediaAssetType::Image,
            'name' => $logoUpload->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $logoUpload->getMimeType() ?: 'image/png',
            'size' => $logoUpload->getSize(),
            'is_active' => true,
        ]);

        $this->set(self::KEY_INSTITUTION_LOGO_MEDIA_ID, ['value' => $asset->id], 'general');

        $this->deleteLogoAsset($previous);

        return $asset;
    }

    public function removeInstitutionLogo(): void
    {
        $asset = $this->institutionLogoAsset();

        $this->set(self::KEY_INSTITUTION_LOGO_MEDIA_ID, ['value' => null], 'general');
        $this->deleteLogoAsset($asset);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LEGACY_CACHE_KEY);
    }

    /**
     * @return array<string, array{group: string, value: mixed}>
     */
    private function all(): array
    {
        /** @var array<string, array{group: string, value: mixed}> $settings */
        $settings = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return Setting::query()
                ->get(['key', 'group', 'value'])
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->key => [
                        'group' => $setting->group,
                        'value' => $setting->value,
                    ],
                ])
                ->all();
        });

        return $settings;
    }

    private function deleteLogoAsset(?MediaAsset $asset): void
    {
        if ($asset === null) {
            return;
        }

        if ($asset->path !== '') {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();
    }
}

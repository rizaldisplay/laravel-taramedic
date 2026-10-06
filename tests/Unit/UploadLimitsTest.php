<?php

use App\Support\UploadLimits;
use Tests\TestCase;

uses(TestCase::class);

it('caps display tv video limit by php ini size', function () {
    expect(UploadLimits::displayTvVideoMaxKilobytes())->toBeLessThanOrEqual(128 * 1024)
        ->and(UploadLimits::displayTvVideoMaxMegabytes())->toBeLessThanOrEqual(128);
});

it('includes common video extensions in mime rules', function () {
    expect(UploadLimits::displayTvVideoMimeRules())->toContain('mp4', 'webm', 'mov');
});

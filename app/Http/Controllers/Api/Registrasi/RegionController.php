<?php

namespace App\Http\Controllers\Api\Registrasi;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionResource;
use App\Services\RegionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegionController extends Controller
{
    public function __construct(private readonly RegionService $regions)
    {
    }

    public function provinces(): AnonymousResourceCollection
    {
        return RegionResource::collection($this->regions->provinces());
    }

    public function regencies(string $province): AnonymousResourceCollection
    {
        return RegionResource::collection($this->regions->regencies($province));
    }

    public function districts(string $regency): AnonymousResourceCollection
    {
        return RegionResource::collection($this->regions->districts($regency));
    }

    public function villages(string $district): AnonymousResourceCollection
    {
        return RegionResource::collection($this->regions->villages($district));
    }
}

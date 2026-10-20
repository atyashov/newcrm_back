<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\Clients\Models\Individual;
use App\Modules\Admin\Clients\Models\LegalEntity;
use App\Modules\Admin\Outlays\Models\Outlay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OutlayCounterpartyService
{
    public function resolve(Outlay $outlay): Model
    {
        $model = match ($outlay->partner_entity) {
            'individual', 'individuals', Individual::class => Individual::class,
            'legal_entity', 'legal-entity', 'legal_entities', LegalEntity::class => LegalEntity::class,
            default => null,
        };
        $partner = $model ? $model::find($outlay->client_id) : null;
        if (! $partner) {
            throw ValidationException::withMessages(['client_id' => 'A supported existing counterparty is required to generate this document.']);
        }

        return $partner;
    }

    public function display(Model $partner): array
    {
        return [
            'name' => $partner instanceof Individual ? $partner->name : $partner->organization_name,
            'contact_name' => $partner instanceof Individual ? $partner->name : $partner->contact_name,
            'phone' => $partner->mobile_phone ?: $partner->phone,
        ];
    }
}

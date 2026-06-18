<?php

namespace App\Modules\Admin\ClientTypes\Services;

use App\Modules\Admin\ClientTypes\Models\ClientType;
use App\Modules\Admin\ClientTypes\Requests\ClientTypesRequest;

class ClientTypesService
{
    /** TODO Сделай фильтрацию, пагинацию и сортировку! (пример Individuals) */
    public function getClientTypes(): \Illuminate\Database\Eloquent\Collection
    {
        return ClientType::all();
    }

    public function save(ClientTypesRequest $request, ClientType $clientType) : ClientType
    {
        $clientType->fill($request->only($clientType->getFillable()));
        $clientType->save();
        return $clientType;
    }
}

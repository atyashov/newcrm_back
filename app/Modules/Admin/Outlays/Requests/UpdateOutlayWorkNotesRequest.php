<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\Outlays\Models\OutlayWorkNote;

class UpdateOutlayWorkNotesRequest extends UpdateOutlayTextsRequest
{
    protected function fields(): array
    {
        return OutlayWorkNote::EDITED_FIELDS;
    }
}

<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\OutlayGroups\Models\OutlayItemComment;

class UpdateOutlayItemCommentsRequest extends UpdateOutlayTextsRequest
{
    protected function fields(): array
    {
        return OutlayItemComment::EDITED_FIELDS;
    }
}

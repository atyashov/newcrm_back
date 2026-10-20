<?php

namespace App\Modules\Pub\Outlays\Services;

use App\Modules\Admin\Outlays\Models\Outlay;

class PublicOutlayService
{
    public function data(Outlay $outlay): array
    {
        $outlay->loadMissing(['subdivision:id,title,description,logo,site_link', 'groups.groupName:id,name', 'groups.items.images']);

        return [
            ...$outlay->only(['id', 'title', 'address', 'recipient_name', 'recipient_phone', 'date_comment',
                'return_date_comment', 'self_pickup', 'self_return', 'is_rent', 'percent_discount',
                'discount_amount', 'total_cost', 'is_paid', 'prepayment', 'prepayment_amount']),
            'execution_date' => $outlay->execution_date?->format('Y-m-d H:i:s'),
            'return_date' => $outlay->return_date?->format('Y-m-d H:i:s'),
            'subdivision' => $outlay->subdivision?->only(['id', 'title', 'description', 'logo', 'logo_url', 'site_link']),
            'groups' => $outlay->groups->map(fn ($group) => [
                'id' => $group->id, 'name' => $group->groupName?->name, 'position' => $group->position,
                'items' => $group->items->map(fn ($item) => [
                    ...$item->only(['id', 'product_title_snapshot', 'is_rent', 'price', 'quantity', 'description', 'position', 'total_cost']),
                    'images' => $item->images->map(fn ($image) => $image->only(['path', 'url', 'position']))->all(),
                ])->all(),
            ])->all(),
        ];
    }
}

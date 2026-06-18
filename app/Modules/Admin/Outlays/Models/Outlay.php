<?php

namespace App\Modules\Admin\Outlays\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outlay extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'execution_date',
        'date_comment',
        'subdivision_id',
        'creator_id',
        'implementation_officer',
        'status_id',
        'client_id',
        'partner_entity',
        'payment_method_id',
        'product_groups',
        'total_cost',

    ];
}

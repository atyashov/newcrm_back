<?php

namespace App\Modules\Admin\Clients\Models;

use App\Core\Traits\HasFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\Model;

class LegalEntity extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'contact_name',
        'organization_name',
        'mobile_phone',
        'phone',
        'email',
        'social',
        'messenger',
        'personal_sale',
        'additional_phone',
        'client_type_id',
        'tin',
        'cpr',
        'bank_name',
        'bik',
        'k_s',
        'r_s',
        'legal_address',
    ];
}

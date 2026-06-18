<?php

namespace App\Modules\Admin\Clients\Models;

use App\Core\Traits\HasFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\Model;

class Individual extends Model
{
    use HasFactory,
        HasFilter;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'social',
        'messenger',
        'personal_sale',
        'passport_serial',
        'passport_number',
        'date_of_passport',
        'issued_by',
        'birthday',
        'registration',
        'address',
        'client_type_id',
    ];
}

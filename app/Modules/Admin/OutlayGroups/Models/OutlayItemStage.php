<?php

namespace App\Modules\Admin\OutlayGroups\Models;

use App\Modules\Admin\Outlays\Models\HasFieldEditors;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlayItemStage extends Model
{
    use HasFieldEditors;

    public const FLAGS = ['assembled', 'uploaded', 'retrieved', 'checked', 'lost', 'defective'];

    public const EDITED_FIELDS = self::FLAGS;

    protected $with = ['assembledEditor', 'uploadedEditor', 'retrievedEditor', 'checkedEditor', 'lostEditor', 'defectiveEditor'];

    public function assembledEditor(): BelongsTo
    {
        return $this->fieldEditor('assembled');
    }

    public function uploadedEditor(): BelongsTo
    {
        return $this->fieldEditor('uploaded');
    }

    public function retrievedEditor(): BelongsTo
    {
        return $this->fieldEditor('retrieved');
    }

    public function checkedEditor(): BelongsTo
    {
        return $this->fieldEditor('checked');
    }

    public function lostEditor(): BelongsTo
    {
        return $this->fieldEditor('lost');
    }

    public function defectiveEditor(): BelongsTo
    {
        return $this->fieldEditor('defective');
    }

    protected $primaryKey = 'outlay_item_id';

    public $incrementing = false;

    protected $guarded = ['outlay_item_id'];

    protected $attributes = [
        'assembled' => false, 'uploaded' => false, 'retrieved' => false,
        'checked' => false, 'lost' => false, 'defective' => false,
        'updated_by_id' => null,
        'created_at' => null, 'updated_at' => null,
    ];

    protected $casts = [
        'assembled' => 'boolean', 'uploaded' => 'boolean', 'retrieved' => 'boolean',
        'checked' => 'boolean', 'lost' => 'boolean', 'defective' => 'boolean',
        'created_at' => 'datetime:Y-m-d H:i:s', 'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id')->select('id', 'login', 'firstname', 'lastname');
    }
}

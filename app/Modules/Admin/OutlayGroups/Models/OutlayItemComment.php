<?php

namespace App\Modules\Admin\OutlayGroups\Models;

use App\Modules\Admin\Outlays\Models\HasFieldEditors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlayItemComment extends Model
{
    use HasFieldEditors;

    public const EDITED_FIELDS = ['assembly_comment', 'retrieve_comment'];

    protected $primaryKey = 'outlay_item_id';

    public $incrementing = false;

    protected $guarded = ['outlay_item_id'];

    protected $attributes = ['assembly_comment' => null, 'retrieve_comment' => null];

    protected $with = ['assemblyCommentEditor', 'retrieveCommentEditor'];

    public function assemblyCommentEditor(): BelongsTo
    {
        return $this->fieldEditor('assembly_comment');
    }

    public function retrieveCommentEditor(): BelongsTo
    {
        return $this->fieldEditor('retrieve_comment');
    }
}

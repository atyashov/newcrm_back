<?php

namespace App\Modules\Admin\Outlays\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutlayWorkNote extends Model
{
    use HasFieldEditors;

    public const EDITED_FIELDS = ['additional_upload_comment', 'retriever_comment', 'damage_loss_comment'];

    protected $primaryKey = 'outlay_id';

    public $incrementing = false;

    protected $guarded = ['outlay_id'];

    protected $attributes = ['additional_upload_comment' => null, 'retriever_comment' => null, 'damage_loss_comment' => null];

    protected $with = ['additionalUploadCommentEditor', 'retrieverCommentEditor', 'damageLossCommentEditor'];

    public function additionalUploadCommentEditor(): BelongsTo
    {
        return $this->fieldEditor('additional_upload_comment');
    }

    public function retrieverCommentEditor(): BelongsTo
    {
        return $this->fieldEditor('retriever_comment');
    }

    public function damageLossCommentEditor(): BelongsTo
    {
        return $this->fieldEditor('damage_loss_comment');
    }
}

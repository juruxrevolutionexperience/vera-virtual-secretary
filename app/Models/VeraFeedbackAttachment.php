<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VeraFeedbackAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vera_feedback_id',
        'path',
        'original_name',
        'size',
        'mime_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(VeraFeedback::class, 'vera_feedback_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}

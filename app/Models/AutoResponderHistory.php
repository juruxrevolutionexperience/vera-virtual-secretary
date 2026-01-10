<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoResponderHistory extends Model
{
    use HasFactory;

    protected $table = 'auto_responder_history';

    protected $fillable = [
        'auto_responder_id',
        'email_id',
        'sender_email',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Auto-responder asociado
     */
    public function autoResponder(): BelongsTo
    {
        return $this->belongsTo(AutoResponder::class);
    }

    /**
     * Email al que se respondió
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }
}

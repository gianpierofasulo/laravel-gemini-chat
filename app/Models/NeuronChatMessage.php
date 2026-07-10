<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeuronChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'thread_id',
        'role',
        'content',
        'meta',
    ];

    protected $casts = [
        'content' => 'array',
        'meta' => 'array',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'thread_id');
    }
}

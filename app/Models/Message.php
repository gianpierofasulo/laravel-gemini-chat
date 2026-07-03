<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['chat_id', 'role', 'content', 'file_path', 'file_name'];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}

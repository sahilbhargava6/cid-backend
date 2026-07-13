<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'operational_ticket_id',
        'user_id',
        'message_text',
        'attachment_path',
        'attachment_name',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        if ($this->attachment_path) {
            return url('/api/messages/' . $this->id . '/attachment');
        }
        return null;
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(OperationalTicket::class, 'operational_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

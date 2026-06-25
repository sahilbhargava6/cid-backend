<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'operational_ticket_id',
        'user_id',
        'message_text',
        'attachment_path',
        'attachment_name',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(OperationalTicket::class, 'operational_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

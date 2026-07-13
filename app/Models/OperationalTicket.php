<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalTicket extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'assigned_to_id',
        'service_type',
        'status',
        'scheduled_at',
        'payment_status',
        'price',
        'input_parameters',
        'timezone',
        'milestone',
    ];

    protected $casts = [
        'input_parameters' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

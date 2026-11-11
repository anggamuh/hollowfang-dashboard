<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HollowfangWebhook extends Model
{
    protected $fillable = ['name', 'channel_name', 'webhook_url', 'created_by'];

    protected $hidden = ['webhook_url'];

    protected function casts(): array
    {
        return ['webhook_url' => 'encrypted'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

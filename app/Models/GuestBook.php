<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'guest_name',
        'institution',
        'phone_number',
        'visit_purpose',
        'consultant_name',
        'rating',
        'feedback',
        'would_recommend',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'would_recommend' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }
}

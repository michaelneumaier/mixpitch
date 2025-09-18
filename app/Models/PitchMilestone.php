<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PitchMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pitch_id',
        'name',
        'description',
        'amount',
        'sort_order',
        'status',
        'payment_status',
        'stripe_invoice_id',
        'approved_at',
        'payment_completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'payment_completed_at' => 'datetime',
    ];

    public function pitch()
    {
        return $this->belongsTo(Pitch::class);
    }
}

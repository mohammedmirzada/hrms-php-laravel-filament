<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model {

    use HasCreatedUpdatedBy;
    
    protected $fillable = [
        'name',
        'description',
        'is_system',
        'is_paid',
        'document_id',
        'default_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_system' => 'boolean',
        'is_paid' => 'boolean',
    ];

    public function document() {
        return $this->belongsTo(Document::class);
    }

    public function defaultUnits() {
        return [
            'HOUR' => 'Hour',
            'DAY' => 'Day',
        ];
    }
    
}

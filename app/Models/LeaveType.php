<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class LeaveType extends Model {

    use HasCreatedUpdatedBy;
    use HasTranslations;

    public array $translatable = ['name', 'description'];

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

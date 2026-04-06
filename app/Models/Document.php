<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLogging;
use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Document extends Model {

    use HasActivityLogging;
    use HasCreatedUpdatedBy;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return $this->defaultLogOptions()->useLogName('employee');
    }
    
    protected $fillable = [
        'employer_id',
        'document_type',
        'file_path',
        'expiry_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function employer() {
        return $this->belongsTo(Employer::class);
    }

    public function isExpired() {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon() {
        return $this->expiry_date && $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30;
    }

    public static function getDocumentTypeOptions() {
        return [
            'Medical Certificate' => 'Medical Certificate',
            'Marriage Certificate' => 'Marriage Certificate',
            'Court Order' => 'Court Order',
            'Travel Document' => 'Travel Document',
            'Other' => 'Other'
        ];
    }

    public function getFileUrlAttribute() {
        return url('storage/' . $this->file_path);
    }
}

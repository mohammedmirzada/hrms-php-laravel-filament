<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Document extends Model {

    use HasCreatedUpdatedBy;
    
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
            'ID Card' => 'ID Card',
            'Passport' => 'Passport',
            'Driver License' => 'Driver License',
            'Work Permit' => 'Work Permit',
            'Visa' => 'Visa',
            'Contract' => 'Contract',
            'Certificate' => 'Certificate',
            'Degree' => 'Degree',
            'CV' => 'CV',
            'Other' => 'Other',
        ];
    }

    public function getFileUrlAttribute() {
        return url('storage/' . $this->file_path);
    }
}

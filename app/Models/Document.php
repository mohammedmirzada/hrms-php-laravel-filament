<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model {
    
    protected $fillable = [
        'employer_id',
        'document_type',
        'file_path',
        'expiry_date',
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

    public function getDocumentTypeOptions() {
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

<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function getSubjectNameAttribute(): ?string
    {
        if (! $this->subject) {
            return $this->subject_type ? class_basename($this->subject_type).' #'.$this->subject_id : null;
        }

        return match (true) {
            $this->subject instanceof Employer => $this->subject->getTranslation('full_name', 'en'),
            $this->subject instanceof User => $this->subject->name,
            method_exists($this->subject, 'getTranslation') => $this->subject->getTranslation('name', 'en'),
            isset($this->subject->name) => $this->subject->name,
            default => class_basename($this->subject_type).' #'.$this->subject_id,
        };
    }

    public function getCauserNameAttribute(): ?string
    {
        if (! $this->causer) {
            return null;
        }

        return match (true) {
            $this->causer instanceof User => $this->causer->name,
            $this->causer instanceof Employer => $this->causer->getTranslation('full_name', 'en'),
            default => class_basename($this->causer_type).' #'.$this->causer_id,
        };
    }
}

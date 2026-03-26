<?php

namespace App\Models;

use App\Models\Concerns\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasCreatedUpdatedBy;
}

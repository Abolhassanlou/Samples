<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    protected $table = 'qualifications';

    protected $fillable = [
        'name',
        'description',
    ];
}

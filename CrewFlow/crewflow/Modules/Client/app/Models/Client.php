<?php

namespace Modules\Client\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A Client is the end customer this company (tenant) performs work for
 * (e.g. a building needing security guards, a family needing a tutor).
 * This is entirely separate from Company (the tenant itself, in Tenancy).
 */
class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'name',
        'type', // company | individual
        'default_contact_name',
        'default_contact_phone',
        'default_address',
    ];
}

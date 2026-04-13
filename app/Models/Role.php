<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'id_role';

    public $timestamps = false;

    protected $fillable = [
        'id_role',
        'role',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'id_role');
    }
}

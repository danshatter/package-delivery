<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public const CUSTOMER = 1;
    public const DRIVER = 2;
    public const ADMINISTRATOR = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The relationship with the User model
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
}

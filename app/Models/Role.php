<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    // Relationship with User model
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    
    // Creating predefined roles
    public static function createPredefinedRoles()
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator with all privileges'],
            ['name' => 'customer', 'description' => 'Regular customer account'],
            ['name' => 'vendor', 'description' => 'Seller within the marketplace'],
        ];
        
        foreach ($roles as $role) {
            static::firstOrCreate(['name' => $role['name']], $role);
        }
    }

    
}

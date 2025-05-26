<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'google_id',
        'avatar',
        'phone_number',
        'email_verified_at',
        'address_line_1',
        'address_line_2',
        'city',
        'zip',
        'state',
        'provider',
        'verification_code'
    ];
    
    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // protected $appends = [
    //     'avatar_url',
    // ];

    // public function getAvatarUrlAttribute()
    // {
    //     return $this->avatar ?? $this->defaultAvatarUrl();
    // }

    // protected function defaultAvatarUrl(): string
    // {
    //     $name = urlencode($this->name ?? 'user');
    //     return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    // }
}
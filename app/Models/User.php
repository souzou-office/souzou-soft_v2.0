<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_reviewer', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_reviewer'       => 'boolean',
            'is_active'         => 'boolean',
        ];
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_user_id');
    }

    public function tasksDelegatedByMe(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    public function ownedMatters(): HasMany
    {
        return $this->hasMany(Matter::class, 'main_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'room_or_desk',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['roles'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get user roles as an array.
     *
     * @return array<int, string>
     */
    public function getRolesAttribute(): array
    {
        if (empty($this->attributes['role'])) {
            return ['user'];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $this->attributes['role']))));
    }

    /**
     * Set user roles from array or comma-separated string.
     *
     * @param string|array<int, string>|null $value
     */
    public function setRolesAttribute(string|array|null $value): void
    {
        if (is_array($value)) {
            $filtered = array_values(array_filter(array_map('trim', $value)));
            $this->attributes['role'] = count($filtered) > 0 ? implode(',', $filtered) : 'user';
        } elseif (is_string($value)) {
            $this->attributes['role'] = trim($value);
        } else {
            $this->attributes['role'] = 'user';
        }
    }

    /**
     * Check if user has specific role or one of multiple roles.
     *
     * @param string|array<int, string> $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $currentRoles = $this->roles;
        if (in_array('super_admin', $currentRoles, true)) {
            return true;
        }

        $check = is_array($roles) ? $roles : array_map('trim', explode(',', $roles));

        // Normalize roles with backward-compatible aliases
        $normalizedRoles = [];
        foreach ($currentRoles as $r) {
            $normalizedRoles[] = $r;
            if ($r === 'admin') {
                $normalizedRoles[] = 'admin_website';
            } elseif ($r === 'admin_website') {
                $normalizedRoles[] = 'admin';
            } elseif ($r === 'shortlink') {
                $normalizedRoles[] = 'admin_shortlink';
            } elseif ($r === 'admin_shortlink') {
                $normalizedRoles[] = 'shortlink';
            }
        }

        return count(array_intersect($normalizedRoles, $check)) > 0;
    }
}

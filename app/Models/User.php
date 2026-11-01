<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Tenant;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'password',
        'is_admin',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->tenant_id !== null) {
                return;
            }

            $tenant = Tenant::create(['name' => $user->name.' — شبكة البائع']);
            $user->forceFill([
                'tenant_id' => $tenant->id,
                'account_type' => $user->is_admin ? 'admin' : 'seller',
            ])->saveQuietly();

            if ($user->is_admin) {
                foreach (\Illuminate\Support\Facades\DB::table('system_settings')->get() as $setting) {
                    \Illuminate\Support\Facades\DB::table('tenant_settings')->updateOrInsert(
                        ['tenant_id' => $tenant->id, 'key' => $setting->key],
                        ['value' => $setting->value, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

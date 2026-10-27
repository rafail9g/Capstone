<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'alamat',
        'no_hp',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->orderBy('roles.id')
            ->withTimestamps();
    }

    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class);
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class);
    }

    public function pendaftaranMbkm(): HasMany
    {
        return $this->hasMany(PendaftaranMbkm::class);
    }

    public function getNimNipAttribute(): ?string
    {
        return $this->dosen?->nip ?? $this->mahasiswa?->nim ?? null;
    }

    public function getProgramStudiAttribute(): ?ProgramStudi
    {
        return $this->dosen?->programStudi ?? $this->mahasiswa?->programStudi ?? null;
    }

    public function getProgramStudiIdAttribute(): ?int
    {
        return $this->dosen?->program_studi_id ?? $this->mahasiswa?->program_studi_id ?? null;
    }

    public function isDosen(): bool
    {
        return $this->dosen()->exists();
    }

    public function isMahasiswa(): bool
    {
        return $this->mahasiswa()->exists();
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * Scope query untuk mencari user berdasarkan NIM, NIP, atau Email.
     */
    public function scopeWhereIdentifier(Builder $query, string $identifier): Builder
    {
        return $query->where(function (Builder $q) use ($identifier) {
            $q->whereHas('dosen', fn ($sub) => $sub->where('nip', $identifier))
                ->orWhereHas('mahasiswa', fn ($sub) => $sub->where('nim', $identifier))
                ->orWhere('email', $identifier);
        });
    }
}

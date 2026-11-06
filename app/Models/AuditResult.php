<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditResult extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['chain' => 'array'];
    }

    public const LABELS = ['ok' => 'Berhasil', 'broken' => 'HTTP 4xx', 'server_error' => 'HTTP 5xx', 'redirect_error' => 'Masalah redirect', 'connection_error' => 'Koneksi gagal', 'blocked' => 'Tujuan diblokir', 'limited' => 'Batas sumber daya', 'other' => 'Status lainnya'];

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->kind] ?? $this->kind;
    }
}

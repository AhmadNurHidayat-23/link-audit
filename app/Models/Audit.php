<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['finished_at' => 'datetime'];
    }

    public function results()
    {
        return $this->hasMany(AuditResult::class);
    }
}

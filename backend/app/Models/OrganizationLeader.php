<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationLeader extends Model
{
    protected $fillable = ['organization_id', 'position', 'full_name'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

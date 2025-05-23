<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'filename',
        'type',
        'sort_order'
    ];

    // Get the parent imageable model (user, product, etc).
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}

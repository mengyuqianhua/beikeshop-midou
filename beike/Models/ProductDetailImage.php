<?php

namespace Beike\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDetailImage extends Base
{
    use HasFactory;

    protected $fillable = ['product_id', 'image', 'crop', 'position'];

    protected $casts = [
        'crop' => 'array',
    ];
}

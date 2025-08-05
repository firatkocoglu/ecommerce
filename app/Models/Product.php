<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        "name", 
        "slug", 
        "description", 
        "price", 
        "weight",
        "status",
    ];

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function images() {
        return $this->hasMany(ProductImage::class);
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    } 

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function tags() {
        return $this->belongsToMany(Tag::class);
    }
}

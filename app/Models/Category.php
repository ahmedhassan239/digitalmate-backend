<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $fillable = ['name','slug'];
    public $translatable = [
        'name','slug'
    ];
    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }
    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

}

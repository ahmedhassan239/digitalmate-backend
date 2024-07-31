<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SubCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $table = 'sub_categories';
    protected $fillable = [
        'category_id',
        'name',
        'slug',
    ];
    public $translatable = [
        'name',
        'slug',
    ];
    /**
     * Get the category that owns the subcategory.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
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

<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    use HasFactory;
    use HasTranslations;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($blog) {
            // Detach associated files before deleting the blog
            $blog->files()->detach();
        });
    }
    // Specify the fields that are mass assignable
    protected $fillable = [
        'name',
        'slug',
        'overview',
        'description',
        'banner',
        'thumb',
        'related_blogs', // Ensure your database supports JSON columns
        'seo_title',
        'seo_keywords',
        'seo_description',
        'status',
        'featured',
        'robots',
        'category_id',
        'sub_category_id',
        'summary',
    ];
    protected $dates = ['deleted_at'];
    public $translatable = [
        'name', 'slug', 'description', 'overview',
        'seo_title', 'seo_keywords', 'seo_description','summary'
    ];

    // If you need to cast related_blogs as an array
    protected $casts = [
        'related_blogs' => 'array'
    ];

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }

    // public function service(){
    //     return $this->belongsTo(Service::class);
    // }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function getRelatedBlogsListAttribute()
    {
        if (!empty($this->related_blogs)) {
            $relatedBlogsIds = is_string($this->related_blogs) ? json_decode($this->related_blogs, true) : $this->related_blogs;

            if (is_array($relatedBlogsIds)) {
                $relatedBlogs = $this->whereIn('id', $relatedBlogsIds)->with('files')->get()
                    ->map(function ($value) {
                        $thumb = '';
                        foreach ($value->files as $file) {
                            if ($file->pivot->type == 'thumb') {
                                $thumb = $file->file_url;
                            }
                        }
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'slug' => $value->slug,
                            'description' => $value->description,
                            'thumb_alt' => $value->name,
                            'thumb' => $thumb,
                            'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', $value->created_at)->isoFormat('MMM Do YY'),
                        ];
                    });

                return $relatedBlogs;
            }
        }

        return null;
    }
}

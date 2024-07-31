<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'name', 'slug', 'description', 'overview', 'country_id','related_services','related_blogs','others',
        'seo_title', 'seo_keywords', 'seo_description', 'robots', 'status','featured','svg','icon_tag'
    ];

    protected $dates = ['deleted_at'];

    public $translatable = [
        'name', 'slug', 'description', 'overview',
        'seo_title', 'seo_keywords', 'seo_description',
    ];

    protected $casts = [
        'related_services_list' => 'array', // Cast project_ids to array
        'others' => 'array', // Cast project_ids to array
        'related_blogs_list' => 'array'
    ];

    // public function sub_specialties()
    // {
    //     return $this->hasMany(SubSpecialty::class);
    // }

    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }
    public function enquiries()
    {
        return $this->hasMany(Enquiry::class);
    }
    public function blogs() {
        return $this->hasMany(Blog::class);
    }

    public function getRelatedServicesListAttribute()
    {
        if (!empty($this->related_services)) {
            $relatedServicesIds = is_string($this->related_services) ? json_decode($this->related_services, true) : $this->related_services;

            if (is_array($relatedServicesIds)) {
                $relatedServices = $this->whereIn('id', $relatedServicesIds)->with('files')->get()
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
                        ];
                    });

                return $relatedServices;
            }
        }

        return null;
    }

    public function getRelatedBlogsListAttribute()
    {
        if (!empty($this->related_blogs)) {
            $relatedBlogsIds = is_string($this->related_blogs) ? json_decode($this->related_blogs, true) : $this->related_blogs;

            if (is_array($relatedBlogsIds)) {
                $relatedBlogs = Blog::whereIn('id', $relatedBlogsIds)->with('files')->get()
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

    public function getProcessedOthersAttribute()
    {
        $others = $this->others;
        if (is_array($others)) {
            $title = $others['title'] ?? null;
            $shortDescription = $others['short_description'] ?? null;
            $percentage = $others['per_setage'] ?? null;
            $whatWeProvide = $others['what_we_provide'] ?? [];

            // Process `what_we_provide` to make sure it's an array of arrays
            $processedWhatWeProvide = array_map(function ($item) {
                return [
                    'en' => $item['en'] ?? null,
                    'ar' => $item['ar'] ?? null,
                ];
            }, $whatWeProvide);

            return [
                'title' => $title,
                'short_description' => $shortDescription,
                'per_setage' => $percentage,
                'what_we_provide' => $processedWhatWeProvide,
            ];
        }

        return null;
    }


}

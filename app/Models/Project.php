<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Project extends Model
{
    use HasFactory;
    use HasTranslations;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'overview',
        'description',
        'client',
        'date',
        'category',
        'country',
        'service_id',
        'other_services',
        'robots',
        'featured',
        'status',
        'seo_title',
        'seo_keywords',
        'seo_description',
    ];
    protected $translatable = [
        'name',
        'slug',
        'overview',
        'description',
        'client',
        'category',
        'country',
        'other_services',
        'seo_title',
        'seo_keywords',
        'seo_description',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'other_services' => 'array',
    ];
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumb')->singleFile();
        $this->addMediaCollection('gallery');
    }
    /**
     * Get the service that owns the project.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}

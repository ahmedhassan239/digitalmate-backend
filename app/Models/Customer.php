<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Customer extends Model
{
    use HasFactory , HasTranslations;

    protected $fillable = ['title', 'project_ids','description','robots','featured','status',
    'seo_title','seo_keywords','seo_description'];

    public $translatable = [
        'name', 'slug', 'description',
        'seo_title', 'seo_keywords', 'seo_description',
    ];
    protected $casts = [
        'project_ids' => 'array', // Cast project_ids to array
    ];

    public function getProjectsAttribute()
    {
        if (!empty($this->project_ids)) {
            $projectIds = is_string($this->project_ids) ? json_decode($this->project_ids, true) : $this->project_ids;
    
            if (is_array($projectIds)) {
                $projects = Project::whereIn('id', $projectIds)->with('files')->get()
                    ->map(function ($project) {
                        $thumb = '';
                        foreach ($project->files as $file) {
                            if ($file->pivot->type == 'thumb') {
                                $thumb = $file->file_url;
                            }
                        }
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'slug' => $project->slug,
                            'thumb' => $thumb,
                            'created_at' =>$project->date,
                        ];
                    });
    
                return $projects;
            }
        }
    
        return null;
    }
    

    // protected $appends = ['projects']; // Automatically append the projects attribute
    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }
}

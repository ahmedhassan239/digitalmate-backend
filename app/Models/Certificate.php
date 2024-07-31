<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Certificate extends Model
{
    use HasFactory;
    use HasTranslations;
    protected $table = 'certificates';
    protected $fillable = ['title','description'];

    public $translatable = [
        'title','description'
    ];
    public function files()
    {
        return $this->morphToMany(File::class, 'model', 'model_has_files')->withPivot('type');
    }


}

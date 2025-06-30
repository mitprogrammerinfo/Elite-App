<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageInspectionPhoto extends Model
{
    protected $table = 'damage_inspection_photos';
    protected $fillable = ['survey_id', 'image_path', 'room_index'];
}
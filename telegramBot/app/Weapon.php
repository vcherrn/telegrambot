<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Weapon extends Model
{
    protected $table = 'weapons';
    protected $fillable = [
        'category_id', 
        'title',
        'cost',
        'description',
        'image'
    ];
}

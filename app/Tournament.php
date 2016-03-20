<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    protected $table = 'tournaments';

    protected $fillable = [
        'start',
        'end',
        'table_number',
        'number',
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function series()
    {
        return $this->hasMany('App\Series');
    }

    public function __toString()
    {
        return $this->start . ' - ' . $this->end;
    }
}
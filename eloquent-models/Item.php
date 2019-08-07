<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $timestamps = false;
    protected $table = 'items';

    public function location()
    {
        return $this->belongsTo('App\Location', 'location_id');
    }

    public function holder()
    {
        return $this->belongsTo('App\Holder', 'holder_id');
    }
}


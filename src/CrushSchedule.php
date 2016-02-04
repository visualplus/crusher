<?php

namespace Visualplus\Crusher;

use Illuminate\Database\Eloquent\Model;

class CrushSchedule extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @return mixed
     */
    public function member()
    {
        return $this->hasOne('App\Member', 'idx', 'userno');
    }
}

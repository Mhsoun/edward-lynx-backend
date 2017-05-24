<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    
    public $fillable = ['token', 'deviceId'];
    
    public $visible = ['token', 'deviceId'];

    /**
     * Returns the user that owns this device.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'userId');
    }
    
}

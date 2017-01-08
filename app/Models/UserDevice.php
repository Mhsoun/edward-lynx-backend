<?php

namespace App;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    
    /**
     * Returns the user that owns this device.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}

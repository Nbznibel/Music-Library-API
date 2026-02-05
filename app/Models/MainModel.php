<?php
/* Model <MoralPersonBankAccount> - Database Access
** Version 2024-06-10 20:50:50
** Copyright NecxTec - All rights reserved 
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class MainModel extends Model
{
    protected $appends = ['locker'];
    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
    // accessor for JSON serialization
    public function getLockerAttribute()
    {
        $locker = $this->locker()->first();

        if ($locker && auth()->check() && $locker->id !== auth()->id()) {
            return $locker;
        }

        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'admin_id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'profile_photo',
        'shop_name',
        'shop_address',
        'notify_returns',
        'notify_failed_payments',
        'notify_low_stock',
    ];
}

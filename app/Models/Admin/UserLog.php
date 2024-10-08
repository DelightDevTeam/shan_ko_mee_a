<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLog extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'lastupdate', 'ip_address', 'func_access', 'user_agent'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;
use App\Models\User;
use App\Models\Wager;
use App\Enums\TransactionName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    //  protected $fillable = [
    //     'user_id',
    //     'amount',
    //     'transaction_name',
    //     'type',
    // ];

    protected $fillable = [
    'user_id',
    'wallet_id',
    'amount',
    'transaction_name',
    'type',
    'meta',
    'uuid',
    'payable_type',
    'payable_id',
    'target_user_id',
];


    protected $casts = [
        'wallet_id' => 'int',
        'confirmed' => 'bool',
        'meta' => 'json',
        'name' => TransactionName::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wager()
    {
        return $this->belongsTo(Wager::class);
    }

    

    public function targetUser()
    {
        return $this->belongsTo(User::class);
    }

    
}

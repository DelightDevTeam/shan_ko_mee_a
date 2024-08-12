<?php  
// app/Services/WalletService.php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Enums\TransactionName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function deposit(User $user, float $amount, TransactionName $transactionName)
    {
    
         Log::info('Wallet ID in Service: ' . $user->wallet->id);

        DB::transaction(function () use ($user, $amount, $transactionName) {
            $wallet = $user->wallet;
            $wallet->balance += $amount;
            $wallet->save();

            $this->logTransaction($user, $amount, $transactionName, 'deposit');
        });
    }

    public function transfer(User $from, User $to, float $amount, TransactionName $transactionName)
    {
        DB::transaction(function () use ($from, $to, $amount, $transactionName) {
            $fromWallet = $from->wallet;
            $toWallet = $to->wallet;

            if ($fromWallet->balance < $amount) {
                throw new \Exception('Insufficient funds');
            }

            $fromWallet->balance -= $amount;
            $toWallet->balance += $amount;

            $fromWallet->save();
            $toWallet->save();

            $this->logTransaction($from, $amount, $transactionName, 'withdraw', $to);
            $this->logTransaction($to, $amount, $transactionName, 'deposit', $from);
        });
    }

    // private function logTransaction(User $user, float $amount, TransactionName $transactionName, string $type, User $targetUser = null)
    // {
    //     $meta = $type === 'withdraw'
    //         ? self::buildTransferMeta($user, $targetUser, $transactionName)
    //         : self::buildDepositMeta($user, $targetUser, $transactionName);

    //     Transaction::create([
    //         'user_id' => $user->id,
    //         'wallet_id' => $user->wallet->id,
    //         'amount' => $amount,
    //         'transaction_name' => $transactionName->value,
    //         'type' => $type,
    //         'meta' => json_encode($meta),
    //         'uuid' => Str::uuid(),
    //          //'payable_type' => 'App\Models\User', // Replace with appropriate model class
    //         //'payable_type' => User::class,
    //         'payable_type' => get_class($user),
    //         'payable_id' => $user->id, // Replace with appropriate ID
    //         'target_user_id' => $user->id
    //     ]);
    // }
    private function logTransaction(User $user, float $amount, TransactionName $transactionName, string $type, User $targetUser = null)
{
    try {
        if (!$user->wallet) {
            throw new \Exception('User does not have a wallet');
        }

        $meta = $type === 'withdraw'
            ? self::buildTransferMeta($user, $targetUser, $transactionName)
            : self::buildDepositMeta($user, $targetUser, $transactionName);

        $wallet = $user->wallet;

        Log::info('Creating transaction with data: ', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'transaction_name' => $transactionName->value,
            'type' => $type,
            'payable_type' => get_class($user),
            'payable_id' => $user->id,
            'target_user_id' => $targetUser ? $targetUser->id : null
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'transaction_name' => $transactionName->value,
            'type' => $type,
            'meta' => json_encode($meta),
            'uuid' => Str::uuid(),
            'payable_type' => get_class($user),
            'payable_id' => $user->id,
            'target_user_id' => $targetUser ? $targetUser->id : null
        ]);

        Log::info('Transaction created successfully.');

    } catch (\Exception $e) {
        Log::error('Transaction creation failed: ' . $e->getMessage());
    }
}

    // still use 

//     private function logTransaction(User $user, float $amount, TransactionName $transactionName, string $type, User $targetUser = null)
// {
//     if (!$user->wallet) {
//         throw new \Exception('User does not have a wallet');
//     }

//     $meta = $type === 'withdraw'
//         ? self::buildTransferMeta($user, $targetUser, $transactionName)
//         : self::buildDepositMeta($user, $targetUser, $transactionName);
//         $wallet = $user->wallet;

//     if ($wallet) {
//     Log::info('Service Log: Wallet ID: ' . $wallet->id);
// } else {
//     Log::error('Service log: Wallet not found for user ID: ' . $user->id);
// }

//     Transaction::create([
//         'user_id' => $user->id,
//         'wallet_id' => $wallet->id,
//         'amount' => $amount,
//         'transaction_name' => $transactionName->value,
//         'type' => $type,
//         'meta' => json_encode($meta),
//         'uuid' => Str::uuid(),
//         'payable_type' => get_class($user),
//         'payable_id' => $user->id,
//         //'target_user_id' => $user->id
//         'target_user_id' => $targetUser ? $targetUser->id : $user->id
//     ]);
// }


    public static function buildTransferMeta(User $user, User $targetUser, TransactionName $transactionName, array $meta = [])
    {
        return array_merge([
            'name' => $transactionName->value,
            'opening_balance' => $user->wallet->balance,
            'target_user_id' => $targetUser->id,
        ], $meta);
    }

    public static function buildDepositMeta(User $user, User $targetUser = null, TransactionName $transactionName, array $meta = [])
    {
        return array_merge([
            'name' => $transactionName->value,
            'opening_balance' => $user->wallet->balance,
            'target_user_id' => $targetUser ? $targetUser->id : null,
        ], $meta);
    }
}



// class WalletService
// {
//     public function deposit(User $user, float $amount, TransactionName $transactionName)
//     {
//         DB::transaction(function () use ($user, $amount, $transactionName) {
//             $wallet = $user->wallet;
//             $wallet->balance += $amount;
//             $wallet->save();

//             $this->logTransaction($user, $amount, $transactionName, 'deposit');
//         });
//     }

//     public function transfer(User $from, User $to, float $amount, TransactionName $transactionName)
//     {
//         DB::transaction(function () use ($from, $to, $amount, $transactionName) {
//             $fromWallet = $from->wallet;
//             $toWallet = $to->wallet;

//             if ($fromWallet->balance < $amount) {
//                 throw new \Exception('Insufficient funds');
//             }

//             $fromWallet->balance -= $amount;
//             $toWallet->balance += $amount;

//             $fromWallet->save();
//             $toWallet->save();

//             $this->logTransaction($from, $amount, $transactionName, 'withdraw');
//             $this->logTransaction($to, $amount, $transactionName, 'deposit');
//         });
//     }

//     private function logTransaction(User $user, float $amount, TransactionName $transactionName, string $type)
//     {
//         Transaction::create([
//             'user_id' => $user->id,
//             'wallet_id' => $user->wallet->id,
//             'amount' => $amount,
//             'transaction_name' => $transactionName,
//             'type' => $type,
//             'meta' => json_encode(['target_user_id' => $user->id]),
//             'uuid' => \Illuminate\Support\Str::uuid()
//         ]);
//     }
// }
// class WalletService
// {
//      public function deposit(User $user, float $amount, TransactionName $transactionName)
//     {
//         DB::transaction(function () use ($user, $amount, $transactionName) {
//             $wallet = $user->wallet;
//             $wallet->balance += $amount;
//             $wallet->save();

//             // Log the transaction (assuming you have a transactions table)
//             $this->logTransaction($user, $amount, $transactionName->value, 'credit');
//         });
//     }

//     public function transfer(User $from, User $to, float $amount, TransactionName $transactionName)
//     {
//         DB::transaction(function () use ($from, $to, $amount, $transactionName) {
//             $fromWallet = $from->wallet;
//             $toWallet = $to->wallet;

//             if ($fromWallet->balance < $amount) {
//                 throw new \Exception('Insufficient funds');
//             }

//             $fromWallet->balance -= $amount;
//             $toWallet->balance += $amount;

//             $fromWallet->save();
//             $toWallet->save();

//             // Log the transactions
//             $this->logTransaction($from, $amount, $transactionName->value, 'debit');
//             $this->logTransaction($to, $amount, $transactionName->value, 'credit');
//         });
//     }

//     private function logTransaction(User $user, float $amount, string $transactionName, string $type)
//     {
//         Transaction::create([
//             'user_id' => $user->id,
//             'amount' => $amount,
//             'transaction_name' => $transactionName,
//             'type' => $type,
//         ]);
//     }
// }

// class WalletService
// {
//     public function deposit(User $user, float $amount, string $transactionName)
//     {
//         DB::transaction(function () use ($user, $amount, $transactionName) {
//             $wallet = $user->wallet;
//             $wallet->balance += $amount;
//             $wallet->save();

//             // Log the transaction (assuming you have a transactions table)
//             $this->logTransaction($user, $amount, $transactionName, 'credit');
//         });
//     }

//     public function transfer(User $from, User $to, float $amount, string $transactionName)
//     {
//         DB::transaction(function () use ($from, $to, $amount, $transactionName) {
//             $fromWallet = $from->wallet;
//             $toWallet = $to->wallet;

//             if ($fromWallet->balance < $amount) {
//                 throw new \Exception('Insufficient funds');
//             }

//             $fromWallet->balance -= $amount;
//             $toWallet->balance += $amount;

//             $fromWallet->save();
//             $toWallet->save();

//             // Log the transactions
//             $this->logTransaction($from, $amount, $transactionName, 'debit');
//             $this->logTransaction($to, $amount, $transactionName, 'credit');
//         });
//     }

//     private function logTransaction(User $user, float $amount, string $transactionName, string $type)
//     {
//         // Implement your transaction logging logic here
//     }
// }

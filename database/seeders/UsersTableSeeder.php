<?php

// database/seeders/UsersTableSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserType;
use App\Models\Admin\Wallet;
use App\Enums\TransactionName;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    
    public function run(): void
    {
        $admin = $this->createUser(UserType::Admin, 'Owner', 'shan', '09123456789');
        (new WalletService)->deposit($admin, 10 * 100_000, TransactionName::CapitalDeposit);

        $agent_1 = $this->createUser(UserType::Agent, 'Agent 1', 'A898737', '09112345674', $admin->id);
        (new WalletService)->transfer($admin, $agent_1, 5 * 100_000, TransactionName::CreditTransfer);

        $player_1 = $this->createUser(UserType::Player, 'Player 1', 'P111111', '09111111111', $agent_1->id);
        (new WalletService)->transfer($agent_1, $player_1, 30000, TransactionName::CreditTransfer);

        $player_2 = $this->createUser(UserType::Player, 'Player 2', 'P222222', '09222222222', $agent_1->id);
        (new WalletService)->transfer($agent_1, $player_2, 30000, TransactionName::CreditTransfer);
    }

    private function createUser(UserType $type, string $name, string $user_name, string $phone, int $parent_id = null, string $referral_code = null): User
    {
        $user = User::create([
            'name' => $name,
            'user_name' => $user_name,
            'phone' => $phone,
            'password' => Hash::make('delightmyanmar'),
            'agent_id' => $parent_id,
            'status' => 1,
            'is_changed_password' => 1,
            'type' => $type->value
        ]);

        // Create a wallet for the user
        Wallet::create(['user_id' => $user->id]);

        return $user;
    }
    // public function run(): void
    // {
    //     $admin = $this->createUser(UserType::Admin, 'Owner', 'superbet77', '09123456789');
    //     (new WalletService)->deposit($admin, 10 * 100_000, TransactionName::CapitalDeposit);

    //     $agent_1 = $this->createUser(UserType::Agent, 'Agent 1', 'A898737', '09112345674', $admin->id);
    //     (new WalletService)->transfer($admin, $agent_1, 5 * 100_000, TransactionName::CreditTransfer);

    //     $player_1 = $this->createUser(UserType::Player, 'Player 1', 'P111111', '09111111111', $agent_1->id);
    //     (new WalletService)->transfer($agent_1, $player_1, 30000, TransactionName::CreditTransfer);

    //      $player_2 = $this->createUser(UserType::Player, 'Player 2', 'P222222', '09222222222', $agent_1->id);
    //     (new WalletService)->transfer($agent_1, $player_2, 30000, TransactionName::CreditTransfer);
    // }

    // private function createUser(UserType $type, string $name, string $user_name, string $phone, int $parent_id = null, string $referral_code = null): User
    // {
    //     $user = User::create([
    //         'name' => $name,
    //         'user_name' => $user_name,
    //         'phone' => $phone,
    //         'password' => Hash::make('delightmyanmar'),
    //         'agent_id' => $parent_id,
    //         'status' => 1,
    //         'is_changed_password' => 1,
    //         'type' => $type->value
    //         //'referral_code' => $referral_code
    //     ]);

    //     // Create a wallet for the user
    //     Wallet::create(['user_id' => $user->id]);

    //     return $user;
    // }
}

<?php

namespace App\Http\Controllers\Admin\Agent;

use Exception;
use App\Models\User;
use App\Enums\UserType;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\TransactionName;
use App\Enums\TransactionType;
use App\Services\WalletService;
use App\Models\Admin\TransferLog;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AgentRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\TransferLogRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private const AGENT_ROLE = 2;

   public function index()
{
    abort_if(
        Gate::denies('agent_index'),
        Response::HTTP_FORBIDDEN,
        '403 Forbidden |You cannot Access this page because you do not have permission'
    );

    // Eager load the wallet relationship
    $users = User::with(['roles', 'wallet'])
        ->whereHas('roles', function ($query) {
            $query->where('role_id', self::AGENT_ROLE);
        })
        ->where('agent_id', auth()->id())
        ->orderBy('id', 'desc')
        ->get();

    return view('admin.agent.index', compact('users'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(
            Gate::denies('agent_create'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $agent_name = $this->generateRandomString();

        return view('admin.agent.create', compact('agent_name'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AgentRequest $request)
    {
        abort_if(
            Gate::denies('agent_store'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $master = Auth::user();
        $inputs = $request->validated();
        if (isset($inputs['amount']) && $inputs['amount'] > $master->balanceFloat) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient balance for transfer.',
            ]);
        }
        $transfer_amount = $inputs['amount'];
        $userPrepare = array_merge(
            $inputs,
            [
                'password' => Hash::make($inputs['password']),
                'agent_id' => Auth()->user()->id,
                'type' => UserType::Admin,
            ]
        );

        $agent = User::create($userPrepare);
        $agent->roles()->sync(self::AGENT_ROLE);

        if (isset($inputs['amount'])) {
            app(WalletService::class)->transfer($master, $agent, $inputs['amount'], TransactionName::CreditTransfer);
        }

        return redirect()->back()
            ->with('success', 'Agent created successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name)
            ->with('amount', $transfer_amount);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort_if(
            Gate::denies('agent_show'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user_detail = User::find($id);

        return view('admin.agent.show', compact('user_detail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort_if(
            Gate::denies('agent_edit') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);

        return view('admin.agent.edit', compact('agent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort_if(
            Gate::denies('agent_update') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $request->validate([
            'name' => 'required|min:3|unique:users,name,'.$id,
            'player_name' => 'required|string',
            'phone' => ['nullable', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'unique:users,phone,'.$id],
        ]);

        $user = User::find($id);
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'player_name' => $request->player_name,
        ]);

        return redirect()->back()
            ->with('success', 'Agent Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function getCashIn(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);

        return view('admin.agent.cash_in', compact('agent'));
    }

    public function getCashOut(string $id)
    {
        abort_if(
            Gate::denies('make_transfer'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        // Assuming $id is the user ID
        $agent = User::findOrFail($id);

        return view('admin.agent.cash_out', compact('agent'));
    }

    // public function makeCashIn(TransferLogRequest $request, $id)
    // {
    //     abort_if(
    //         Gate::denies('make_transfer') || !$this->ifChildOfParent(request()->user()->id, $id),
    //         Response::HTTP_FORBIDDEN,
    //         '403 Forbidden |You cannot Access this page because you do not have permission'
    //     );

    //     try {
    //         $inputs = $request->validated();
    //         $agent = User::findOrFail($id);
    //         $admin = Auth::user();
    //         $cashIn = $inputs['amount'];
    //         if ($cashIn > $admin->balanceFloat) {
    //             throw new \Exception('You do not have enough balance to transfer!');
    //         }

    //         // Transfer money
    //         app(WalletService::class)->transfer($admin, $agent, $cashIn, TransactionName::CreditTransfer, TransactionType::DEPOSIT());

    //         return redirect()->back()->with('success', 'Money fill request submitted successfully!');
    //     } catch (Exception $e) {
    //         session()->flash('error', $e->getMessage());
    //         return redirect()->back()->with('error', $e->getMessage());
    //     }
    // }

    public function makeCashIn(TransferLogRequest $request, $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();
            $agent = User::findOrFail($id);
            $admin = Auth::user();
            $cashIn = $inputs['amount'];

            Log::info('Agent ID: ' . $agent->id);
             $wallet = $agent->wallet;

        if ($wallet) {
            Log::info('Wallet ID: ' . $wallet->id);
        } else {
            Log::error('Wallet not found for user ID: ' . $agent->id);
            // Handle missing wallet (e.g., create a new wallet)
        }
             if ($cashIn > $admin->wallet->balance) {
            throw new \Exception('You do not have enough balance to transfer!');
        }

            // Transfer money
            app(WalletService::class)->transfer($admin, $agent, $request->validated('amount'), TransactionName::CreditTransfer);
             // Create transaction log for admin (withdraw)
        //$this->logTransaction($admin, $cashIn, TransactionName::CreditTransfer, 'withdraw', $agent);

        // Create transaction log for agent (deposit)
       // $this->logTransaction($agent, $cashIn, TransactionName::CreditTransfer, 'deposit', $admin);


            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

//     public function makeCashIn(TransferLogRequest $request, $id)
// {
//     abort_if(
//         Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
//         Response::HTTP_FORBIDDEN,
//         '403 Forbidden |You cannot  Access this page because you do not have permission'
//     );

//     try {
//         $inputs = $request->validated();
//         $agent = User::findOrFail($id);
//         $admin = Auth::user();
//         $cashIn = $inputs['amount'];

//         $wallet = $agent->wallet;

// if ($wallet) {
//     Log::info('Wallet ID: ' . $wallet->id);
// } else {
//     Log::error('Wallet not found for user ID: ' . $agent->id);
// }


//         // Create a wallet for the new agent if it doesn't already exist
//         if (!$agent->wallet) {
//             $agent->wallet()->create(['balance' => 0]);
//             // Refresh the agent to ensure the wallet is available
//             $agent->refresh();
//         }

//         if ($cashIn > $admin->wallet->balance) {
//             throw new \Exception('You do not have enough balance to transfer!');
//         }

//         // Transfer money
//         app(WalletService::class)->transfer($admin, $agent, $request->validated('amount'), TransactionName::CreditTransfer);

//         return redirect()->back()->with('success', 'Money fill request submitted successfully!');
//     } catch (Exception $e) {
//         session()->flash('error', $e->getMessage());

//         return redirect()->back()->with('error', $e->getMessage());
//     }
// }


    public function makeCashOut(TransferLogRequest $request, string $id)
    {

        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        try {
            $inputs = $request->validated();

            $agent = User::findOrFail($id);
            $admin = Auth::user();
            $cashOut = $inputs['amount'];

            if ($cashOut > $agent->wallet->balance) {

                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            // Transfer money
            app(WalletService::class)->transfer($agent, $admin, $request->validated('amount'), TransactionName::DebitTransfer);

            return redirect()->back()->with('success', 'Money fill request submitted successfully!');
        } catch (Exception $e) {

            session()->flash('error', $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Money fill request submitted successfully!');
    }

    //    public function makeCashOut(TransferLogRequest $request, string $id)
    // {
    //     abort_if(
    //         Gate::denies('make_transfer') || !$this->ifChildOfParent(request()->user()->id, $id),
    //         Response::HTTP_FORBIDDEN,
    //         '403 Forbidden | You cannot access this page because you do not have permission'
    //     );

    //     try {
    //         $inputs = $request->validated();

    //         $agent = User::findOrFail($id);
    //         $admin = Auth::user();
    //         $cashOut = $inputs['amount'];

    //         if ($cashOut > $agent->balanceFloat) {
    //             return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
    //         }

    //         // Perform the transfer using WalletService
    //         app(WalletService::class)->transfer(
    //             $agent,
    //             $admin,
    //             $cashOut, // Use the validated amount from the request
    //             TransactionName::DebitTransfer,
    //             TransactionType::WITHDRAW() // Assuming a default transaction type
    //         );

    //         return redirect()->back()->with('success', 'Money fill request submitted successfully!');
    //     } catch (Exception $e) {
    //         // Handle exceptions
    //         session()->flash('error', $e->getMessage());
    //         return redirect()->back()->with('error', $e->getMessage());
    //     }
    // }

    public function getTransferDetail($id)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );
        $transfer_detail = TransferLog::where('from_user_id', $id)
            ->orWhere('to_user_id', $id)
            ->get();

        return view('admin.agent.transfer_detail', compact('transfer_detail'));
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'A'.$randomNumber;
    }

    public function banAgent($id)
    {
        abort_if(
            ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $user = User::find($id);
        $user->update(['status' => $user->status == 1 ? 2 : 1]);
        if (Auth::check() && Auth::id() == $id) {
            Auth::logout();
        }

        return redirect()->back()->with(
            'success',
            'User '.($user->status == 1 ? 'activated' : 'banned').' successfully'
        );
    }

    public function getChangePassword($id)
    {
        abort_if(
            Gate::denies('agent_change_password_access') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $agent = User::find($id);

        return view('admin.agent.change_password', compact('agent'));
    }

    public function makeChangePassword($id, Request $request)
    {
        abort_if(
            Gate::denies('agent_change_password_access') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden |You cannot  Access this page because you do not have permission'
        );

        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $agent = User::find($id);
        $agent->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Agent Change Password successfully')
            ->with('password', $request->password)
            ->with('username', $agent->user_name);
    }

    private function logTransaction(User $user, float $amount, TransactionName $transactionName, string $type, User $targetUser = null)
{
    if (!$user->wallet) {
        throw new \Exception('User does not have a wallet');
    }

    $meta = $type === 'withdraw'
        ? self::buildTransferMeta($user, $targetUser, $transactionName)
        : self::buildDepositMeta($user, $targetUser, $transactionName);
        $wallet = $user->wallet;

    if ($wallet) {
    Log::info('Service Log: Wallet ID: ' . $wallet->id);
} else {
    Log::error('Service log: Wallet not found for user ID: ' . $user->id);
}

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
        'target_user_id' => $user->id
    ]);
}


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

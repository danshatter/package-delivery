<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Http, Cache};
use App\Rules\ValidAccountNumber;
use App\Services\Settings\Application;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the accounts of the authenticated user
     */
    public function index()
    {
        $accounts = auth()->user()->accounts()->get();

        // Get the banks
        $banks = $this->getBanks();

        // Add the bank to each account
        $accounts->each(fn($account) => $account->setAttribute('bank', $this->retrieveBankById($banks, $account->bank_id)));

        return $this->sendSuccess('Request successful', $accounts);
    }

    /**
     * Get an account belonging to the authenticated user
     */
    public function show($accountId)
    {
        $account = auth()->user()->accounts()->find($accountId);

        if (is_null($account)) {
            return $this->sendErrorMessage('Account not found', 404);
        }

        return $this->sendSuccess('Request successful', $account);
    }

    /**
     * Add an account to the authenticated user
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'account_number' => ['required', 'numeric', new ValidAccountNumber],
            'account_name' => ['required'],
            'bank_code' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Set the application settings
        app()->make(Application::class)->set();
        
        $response = Http::withToken(config('services.paystack.secret_key'))
                        ->get('https://api.paystack.co/bank/resolve', compact('account_number', 'bank_code'));

        // The data from the response
        $data = $response->json();

        // Check if there was an error
        if ($response->failed()) {
            // Check if there is a status from paystack. This will always have a message key
            if (isset($data['message'])) {
                return $this->sendErrorMessage('Account number could not be resolved. Might not exist', $response->status());
            }

            return $this->sendErrorMessage('Account validation failed. Please try again', 503);
        }
        
        // The name on the account gotten back
        $nameOnAccount = trim(data_get($data, 'data.account_name'));

        // Validate that the name on the account is the same as the one provided
        if (strtolower($account_name) !== strtolower($nameOnAccount)) {
            return $this->sendErrorMessage('Account name given do not match the owner of the account', 403);
        }

        // Create the account
        $account = auth()->user()->accounts()->create([
            'account_number' => $account_number,
            'account_name' => $account_name,
            'bank_code' => $bank_code,
            'bank_id' => data_get($data, 'data.bank_id')
        ]);

        return $this->sendSuccess('Account created successfully', null, 201);
    }

    /**
     * Delete an account of the authenticated user
     */
    public function destroy($accountId)
    {
        $account = auth()->user()->accounts()->find($accountId);

        if (is_null($account)) {
            return $this->sendErrorMessage('Account not found', 404);
        }

        $account->delete();

        return $this->sendSuccess('Account deleted successfully');
    }

    /**
     * Verify the account details from an account number
     */
    public function verify()
    {
        $validator = validator()->make(request()->all(), [
            'account_number' => ['required', 'numeric'],
            'bank_code' => ['required', 'numeric']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        // Set the application settings
        app()->make(Application::class)->set();

        $response = Http::withToken(config('services.paystack.secret_key'))
                        ->get('https://api.paystack.co/bank/resolve', compact('account_number', 'bank_code'));

        // The data from the response
        $data = $response->json();

        // Check if there was an error
        if ($response->failed()) {
            // Check if there is a status from paystack. This will always have a message key
            if (isset($data['message'])) {
                return $this->sendErrorMessage('Account number could not be resolved. Might not exist', $response->status());
            }

            return $this->sendErrorMessage('Account details fetching failed. Please try again', 503);
        }

        return $this->sendSuccess('Request successful', data_get($data, 'data'));
    }

    /**
     * Get all banks
     */
    public function banks()
    {
        $banks = $this->getBanks();

        // If there was an error fetching the banks
        if (is_null($banks)) {
            return $this->sendErrorMessage('Banks fetching failed. Please try again', 503);
        }

        return $this->sendSuccess('Request successful', $banks);
    }

    /**
     * Get a bank
     */
    public function bank($bankId)
    {
        $banks = $this->getBanks();

        if (is_null($banks)) {
            return $this->sendErrorMessage('Bank fetching failed. Please try again', 503);
        }

        // Get the bank by the ID from the returned data set
        $bank = $this->retrieveBankById($banks, $bankId);

        // Check if a bank with that ID exists
        if (is_null($bank)) {
            return $this->sendErrorMessage('Bank not found', 404);
        }

        return $this->sendSuccess('Request successful', $bank);
    }

    /**
     * Retrieve a back by ID
     */
    private function retrieveBankById($banks, $bankId)
    {
        return collect($banks)->firstWhere('id', $bankId);
    }

    /**
     * Get the list of banks and the data we need
     */
    private function getBanks()
    {
        // Check if the cache has banks
        if (Cache::has('banks')) {
            return Cache::get('banks');
        }

        // Set the application settings
        app()->make(Application::class)->set();

        // Make the request to fetch the banks
        $response = Http::withToken(config('services.paystack.secret_key'))
                        ->get('https://api.paystack.co/bank', [
                            'country' => 'nigeria'
                        ]);

        // Check if there was an error
        if ($response->failed()) {
            // Delete the items from the cache. This is not need but is here just in case
            Cache::delete('banks');

            // There was an error while fetching the banks so we return null so we can retrieve the banks again
            return null;
        }

        // The banks and the data we need
        $banks = $response->collect('data')->unique('name')->map(fn($bank) => [
            'id' => data_get($bank, 'id'),
            'name' => data_get($bank, 'name'),
            'code' => data_get($bank, 'code')
        ]);

        // Store the data in the cache
        Cache::set('banks', $banks, 3000);

        return $banks;
    }

}

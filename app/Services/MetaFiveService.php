<?php

namespace App\Services;

use App\Services\Data\CreateTradingAccount;
use App\Services\Data\CreateTradingUser;
use App\Services\Data\UpdateTradingAccount;
use App\Services\Data\UpdateTradingUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User as UserModel;

class MetaFiveService {
    private string $port = "8443";
    private string $login = "10012";
    private string $password = "Test1234.";
    private string $baseURL = "http://202.190.52.130:5000/api";
    private string $token = "6f0d6f97-3042-4389-9655-9bc321f3fc1e";
    private string $environmentName = "live";

    public function getConnectionStatus()
    {
        try {
            return Http::acceptJson()->timeout(10)->get($this->baseURL . "/connect_status")->json();
        } catch (ConnectionException $exception) {
            // Handle the connection timeout error
            // For example, returning an empty array as a default response
            return [];
        }
    }

    public function getUser($meta_login)
    {
        return Http::acceptJson()->get($this->baseURL . "/trade_acc/{$meta_login}")->json();
    }

    public function getUserInfo($tradingAccounts): void
    {
        foreach ($tradingAccounts as $row) {
            $data = $this->getUser($row->meta_login);
            if($data) {
                (new UpdateTradingAccount)->execute($row->meta_login, $data);
                (new UpdateTradingUser)->execute($row->meta_login, $data);
            }
        }
    }

    public function createUser(UserModel $user, $group, $leverage)
    {
        $accountResponse = Http::acceptJson()->post($this->baseURL . "/create_user", [
            'name' => $user->name,
            'group' => $group,
            'leverage' => $leverage,
        ]);
        $accountResponse = $accountResponse->json();

        (new CreateTradingAccount)->execute($user, $accountResponse);
        (new CreateTradingUser)->execute($user, $accountResponse);
        return $accountResponse;
    }

    public function changePassword($meta_login, $type, $password)
    {
        $passwordResponse = Http::acceptJson()->patch($this->baseURL . "/change_password", [
            'login' => $meta_login,
            'type' => $type,
            'password' => $password,
        ]);
        $passwordResponse = $passwordResponse->json();

        Log::debug($passwordResponse);

        return $passwordResponse;
    }

}

class passwordType
{
    const MAIN = 0;
    const INVESTOR = 1;
}
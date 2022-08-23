<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProvideCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->stateless()->user();
        } catch (Exception $e) {
            return redirect()->back();
        }

        # find or create user and send params user get from socialite and provider
        $authUser = $this->findOrCreateUser($user, $provider);

        # Login user
        Auth()->login($authUser, true);

        # Redirect after login
        return redirect()->route('dashboard');
    }

    public function findOrCreateUser($socialUser, $provider)
    {
        # Get Social Account
        $socialAccount = SocialAccount::where('provider_id', $socialUser->getId())
            ->where('provider_name', $provider)
            ->first();

        # Jika Ada Akun
        if ($socialAccount) {
            return $socialAccount->user;
        }
        # Jika tidak ada
        else {
            # User berdasarkan email
            $user = User::where('email', $socialUser->getEmail())->first();

            # Jika tidak ada user
            if (!$user) {
                # Buat user baru
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                ]);
            }

            # Buat sosial akun baru
            $user->socialAccounts()->create([
                'provider_id' => $socialUser->getId(),
                'provider_name' => $provider
            ]);

            return $user;
        }
    }
}

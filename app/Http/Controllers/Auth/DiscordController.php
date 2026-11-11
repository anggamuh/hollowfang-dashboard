<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class DiscordController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->put('discord_oauth_intent', 'login');
        return Socialite::driver('discord')->scopes(['identify'])->redirect();
    }

    public function connect(Request $request): RedirectResponse
    {
        $request->session()->put('discord_oauth_intent', 'connect');
        $request->session()->put('discord_oauth_user_id', $request->user()->getKey());
        return Socialite::driver('discord')->scopes(['identify'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $intent = $request->session()->pull('discord_oauth_intent');
        $connectingUserId = $request->session()->pull('discord_oauth_user_id');
        if (! in_array($intent, ['login', 'connect'], true)) {
            return redirect('/login')->withErrors(['discord' => 'Sesi Discord tidak valid.']);
        }
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (Throwable $e) {
            report($e);
            return redirect('/login')->withErrors(['discord' => 'Login Discord gagal.']);
        }
        $discordId = (string) $discordUser->getId();
        $discordName = $discordUser->getNickname() ?: $discordUser->getName() ?: 'Discord User';
        $discordAvatar = $discordUser->getAvatar();
        if ($intent === 'connect') {
            if (! Auth::check() || (string) Auth::id() !== (string) $connectingUserId) {
                abort(403);
            }
            $existing = User::query()->where('discord_id', $discordId)
                ->where('id', '!=', Auth::id())->exists();
            if ($existing) {
                return redirect('/control')->withErrors(['discord' => 'Discord sudah terhubung ke akun lain.']);
            }
            $user = $request->user();
            $user->discord_id = $discordId;
            $user->discord_username = $discordName;
            $user->discord_avatar = $discordAvatar;
            $user->save();
            return redirect('/control')->with('success', 'Discord berhasil dihubungkan!');
        }
        $user = User::query()->where('discord_id', $discordId)->first();
        if (! $user) {
            return redirect('/login')->withErrors(['discord' => 'Akun Discord belum memiliki akses.']);
        }
        $user->discord_username = $discordName;
        $user->discord_avatar = $discordAvatar;
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended('/control');
    }
}

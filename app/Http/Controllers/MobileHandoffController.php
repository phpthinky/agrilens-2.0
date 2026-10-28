<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Lands the field app's in-app browser on a sample's result page.
 *
 * The app authenticates with a Sanctum token, which the web pages don't
 * accept, so Api\ColorimetricController::resultLink() issues a signed URL
 * carrying a single-use nonce. Opening it starts a normal web session for
 * the same user and redirects to the sample page.
 */
class MobileHandoffController extends Controller
{
    public function __invoke(Request $request, SoilSample $sample)
    {
        $userId = Cache::pull('mobile-handoff:' . $request->query('nonce'));
        $user   = $userId ? User::find($userId) : null;

        abort_unless($user, 403, 'This link has expired. Tap Analyze in the app again.');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('samples.show', $sample);
    }
}

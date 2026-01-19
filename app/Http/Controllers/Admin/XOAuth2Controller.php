<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XOAuth2Controller extends Controller
{
    /**
     * Redirect to X OAuth 2.0 authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('twitter.client_id');
        $redirectUri = route('admin.x-oauth2.callback');
        $scope = 'tweet.read tweet.write users.read offline.access';
        $state = bin2hex(random_bytes(16));
        $codeVerifier = bin2hex(random_bytes(64));
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $codeVerifier, true)),
            '='
        ), '+/', '-_');

        // Store state and code_verifier in session
        $request->session()->put('x_oauth2_state', $state);
        $request->session()->put('x_oauth2_code_verifier', $codeVerifier);

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        // Log the redirect URI for debugging
        Log::info('X OAuth 2.0 redirect', [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ]);

        $authorizeUrl = 'https://x.com/i/oauth2/authorize?'.http_build_query($params);

        return redirect($authorizeUrl);
    }

    /**
     * Handle OAuth 2.0 callback from X.
     */
    public function callback(Request $request): RedirectResponse
    {
        $stateReturned = $request->query('state');
        $code = $request->query('code');
        $error = $request->query('error');

        // Handle errors
        if ($error) {
            Log::error('X OAuth 2.0 error', ['error' => $error]);

            return redirect()->route('admin.x-settings.index')
                ->with('error', 'X OAuth 2.0 authorization failed: '.$error);
        }

        // Validate state and code
        if (! $stateReturned || ! $code) {
            return redirect()->route('admin.x-settings.index')
                ->with('error', 'Invalid OAuth 2.0 callback: missing state or code');
        }

        $storedState = $request->session()->get('x_oauth2_state');
        if ($stateReturned !== $storedState) {
            return redirect()->route('admin.x-settings.index')
                ->with('error', 'Invalid OAuth 2.0 state: state mismatch');
        }

        $codeVerifier = $request->session()->get('x_oauth2_code_verifier');
        if (! $codeVerifier) {
            return redirect()->route('admin.x-settings.index')
                ->with('error', 'Missing OAuth 2.0 code verifier');
        }

        // Clear session values
        $request->session()->forget(['x_oauth2_state', 'x_oauth2_code_verifier']);

        // Exchange authorization code for access token
        try {
            $response = Http::asForm()->withBasicAuth(
                config('twitter.client_id'),
                config('twitter.client_secret')
            )->post('https://api.x.com/2/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('admin.x-oauth2.callback'),
                'code_verifier' => $codeVerifier,
            ]);

            if ($response->failed()) {
                Log::error('X OAuth 2.0 token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return redirect()->route('admin.x-settings.index')
                    ->with('error', 'Failed to exchange authorization code for access token');
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;
            $refreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? null;
            $scope = $data['scope'] ?? null;

            if (! $accessToken) {
                return redirect()->route('admin.x-settings.index')
                    ->with('error', 'Failed to get access token from X API response');
            }

            // Store tokens in database (single record for admin account)
            DB::table('x_oauth2_tokens')->truncate(); // Clear old tokens
            DB::table('x_oauth2_tokens')->insert([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                'token_type' => $data['token_type'] ?? 'Bearer',
                'scope' => $scope,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('admin.x-settings.index')
                ->with('success', 'X OAuth 2.0 authorization successful! You can now post tweets.');
        } catch (\Exception $e) {
            Log::error('X OAuth 2.0 token exchange exception', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.x-settings.index')
                ->with('error', 'OAuth 2.0 token exchange failed: '.$e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class XSettingsController extends Controller
{
    /**
     * Display X Settings page with connection status.
     */
    public function index(Request $request): Response
    {
        $token = DB::table('x_oauth2_tokens')
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->orderBy('created_at', 'desc')
            ->first();

        Log::debug('X Settings connection check', [
            'token_found' => !is_null($token),
            'token_expires_at' => $token?->expires_at,
            'now' => now(),
        ]);

        $connectionStatus = [
            'connected' => false,
            'username' => null,
            'name' => null,
            'connected_at' => null,
            'expires_at' => null,
        ];

        if ($token) {
            $connectionStatus['connected'] = true;
            $connectionStatus['connected_at'] = $token->created_at;
            $connectionStatus['expires_at'] = $token->expires_at;

            // Try to fetch account info from X API
            try {
                $userInfo = $this->getAuthenticatedUserInfo($token->access_token);
                if ($userInfo) {
                    $connectionStatus['username'] = $userInfo['username'] ?? null;
                    $connectionStatus['name'] = $userInfo['name'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch X account info', [
                    'error' => $e->getMessage(),
                ]);
                // Continue without username - token might be expired
            }
        }

        return Inertia::render('Admin/XSettings/Index', [
            'connectionStatus' => $connectionStatus,
        ]);
    }

    /**
     * Disconnect X account by removing OAuth tokens.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        DB::table('x_oauth2_tokens')->truncate();

        return redirect()->route('admin.x-settings.index')
            ->with('success', 'X account disconnected successfully.');
    }

    /**
     * Get authenticated user info from X API.
     */
    protected function getAuthenticatedUserInfo(string $accessToken): ?array
    {
        try {
            $client = new GuzzleClient([
                'base_uri' => 'https://api.x.com/2/',
            ]);

            $response = $client->get('users/me', [
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer '.$accessToken,
                ],
                RequestOptions::QUERY => [
                    'user.fields' => 'id,username,name',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'] ?? null;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

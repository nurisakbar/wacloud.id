<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiUsageService;
use Illuminate\Http\Request;

class AccountApiController extends Controller
{
    protected ApiUsageService $usageService;

    public function __construct(ApiUsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Get account information with quota details
     */
    public function show(Request $request)
    {
        $startTime = microtime(true);
        
        $user = $request->user;
        $quota = $user->getQuota();
        $subscription = $user->activeSubscription;
        $subscriptionPlan = $user->subscriptionPlan;
        
        // Get statistics
        $totalMessages = $user->messages()->count();
        $totalSessions = $user->whatsappSessions()->count();
        $connectedSessionsQuery = $user->whatsappSessions()->where('status', 'connected');
        $connectedSessionsCount = (clone $connectedSessionsQuery)->count();
        
        // Get active devices list
        $devices = $connectedSessionsQuery->get()->map(function ($device) {
            return [
                'device_id' => $device->session_id,
                'name' => $device->session_name,
            ];
        });

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'created_at' => $user->created_at->toIso8601String(),
                ],
                'quota' => [
                    'balance' => (float) $quota->balance,
                    'text_quota' => (int) $quota->text_quota,
                    'multimedia_quota' => (int) $quota->multimedia_quota,
                    'free_text_quota' => (int) $quota->free_text_quota,
                    'total_text_quota' => (int) ($quota->text_quota + $quota->free_text_quota),
                ],
                'subscription' => $subscription ? [
                    'plan_name' => $subscriptionPlan->name ?? null,
                    'plan_id' => $subscriptionPlan->id ?? null,
                    'status' => $subscription->status,
                    'expires_at' => $subscription->expires_at ? $subscription->expires_at->toIso8601String() : null,
                ] : null,
                'statistics' => [
                    'total_messages' => $totalMessages,
                    'total_sessions' => $totalSessions,
                    'connected_sessions' => $connectedSessionsCount,
                ],
                'devices' => $devices,
            ],
        ]);
    }

    /**
     * Get usage statistics
     */
    public function usage(Request $request)
    {
        $startTime = microtime(true);
        
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $stats = $this->usageService->getUsageStats(
            $request->user->id,
            $startDate,
            $endDate
        );

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}



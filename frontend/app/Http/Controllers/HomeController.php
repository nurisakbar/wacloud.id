<?php

namespace App\Http\Controllers;

use App\Models\QuotaUsageLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        // Redirect admin to admin dashboard
        if (in_array($user->role, ['admin', 'super_admin'])) {
            return redirect()->route('admin.dashboard.index');
        }

        $metrics = [
            'totalSessions' => $this->countUserRecords('whatsapp_sessions', $user->id),
            'activeSessions' => $this->countUserRecords('whatsapp_sessions', $user->id, ['status' => 'connected']),
            'messagesSentToday' => $this->countMessages($user->id, 'outgoing'),
            'messagesReceivedToday' => $this->countMessages($user->id, 'incoming'),
        ];

        // Get user quota
        $quota = $user->getQuota();
        
        // Get message statistics for current month
        $messageStats = $this->getMessageStatsForCurrentMonth($user->id);
        
        // Get quota usage statistics for current month (daily breakdown)
        $quotaUsageStats = $this->getQuotaUsageStatsForCurrentMonth($user->id);
        
        // Ensure quotaUsageStats has all required keys
        if (empty($quotaUsageStats)) {
            $quotaUsageStats = [
                'labels' => [],
                'text_quota_data' => [],
                'multimedia_quota_data' => [],
                'balance_data' => [],
                'total_text_quota' => 0,
                'total_multimedia_quota' => 0,
                'total_balance' => 0,
            ];
        }

        return view('home', [
            'metrics' => $metrics,
            'quota' => $quota,
            'messageStats' => $messageStats,
            'quotaUsageStats' => $quotaUsageStats,
        ]);
    }

    protected function countUserRecords(string $table, string $userId, array $additionalWhere = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->where('user_id', $userId);

        foreach ($additionalWhere as $column => $value) {
            $query->where($column, $value);
        }

        return (int) $query->count();
    }

    protected function countMessages(string $userId, string $direction): int
    {
        if (! Schema::hasTable('messages')) {
            return 0;
        }

        return (int) DB::table('messages')
            ->where('user_id', $userId)
            ->where('direction', $direction)
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Get message statistics for current month (daily breakdown)
     */
    protected function getMessageStatsForCurrentMonth(string $userId): array
    {
        if (! Schema::hasTable('messages')) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        // Get all days from start of month to today
        $days = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate->lte($today)) {
            $days[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Get message counts per day
        $messageCounts = DB::table('messages')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->where('direction', 'outgoing')
            ->whereBetween('created_at', [$startOfMonth, $today->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Format labels and data
        $labels = [];
        $data = [];

        foreach ($days as $day) {
            $date = Carbon::parse($day);
            $labels[] = $date->format('d M');
            $data[] = $messageCounts[$day] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
        ];
    }

    /**
     * Get quota usage statistics for current month (daily breakdown)
     */
    protected function getQuotaUsageStatsForCurrentMonth(string $userId): array
    {
        if (! Schema::hasTable('quota_usage_logs')) {
            return [
                'labels' => [],
                'text_quota_data' => [],
                'multimedia_quota_data' => [],
                'balance_data' => [],
            ];
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        // Get all days from start of month to today
        $days = [];
        $currentDate = $startOfMonth->copy();
        while ($currentDate->lte($today)) {
            $days[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Get quota usage per day grouped by quota type
        $usageLogs = QuotaUsageLog::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfMonth, $today->endOfDay()])
            ->select(
                DB::raw('DATE(created_at) as date'),
                'quota_type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date', 'quota_type')
            ->orderBy('date')
            ->get();

        // Organize data by date and quota type
        $usageByDate = [];
        foreach ($usageLogs as $log) {
            $date = $log->date;
            if (!isset($usageByDate[$date])) {
                $usageByDate[$date] = [
                    'text_quota' => 0,
                    'multimedia_quota' => 0,
                    'balance' => 0,
                ];
            }
            $usageByDate[$date][$log->quota_type] = (float) $log->total;
        }

        // Format labels and data
        $labels = [];
        $textQuotaData = [];
        $multimediaQuotaData = [];
        $balanceData = [];

        foreach ($days as $day) {
            $date = Carbon::parse($day);
            $labels[] = $date->format('d M');
            
            $dayUsage = $usageByDate[$day] ?? [
                'text_quota' => 0,
                'multimedia_quota' => 0,
                'balance' => 0,
            ];
            
            $textQuotaData[] = (int) $dayUsage['text_quota'];
            $multimediaQuotaData[] = (int) $dayUsage['multimedia_quota'];
            $balanceData[] = (float) $dayUsage['balance'];
        }

        return [
            'labels' => $labels,
            'text_quota_data' => $textQuotaData,
            'multimedia_quota_data' => $multimediaQuotaData,
            'balance_data' => $balanceData,
            'total_text_quota' => array_sum($textQuotaData),
            'total_multimedia_quota' => array_sum($multimediaQuotaData),
            'total_balance' => array_sum($balanceData),
        ];
    }
}

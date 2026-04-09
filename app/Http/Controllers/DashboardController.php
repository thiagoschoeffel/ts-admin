<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * @return \Inertia\Response
     */
    public function __invoke(Request $request): Response
    {
        // Determine sales period (default: now to 15 days ago)
        $now = Carbon::now();
        $defaultEnd = $now->copy();
        $defaultStart = $now->copy()->subDays(15);

        $startParam = $request->query('sales_from');
        $endParam = $request->query('sales_to');

        $start = $startParam ? Carbon::parse($startParam) : $defaultStart;
        $end = $endParam ? Carbon::parse($endParam) : $defaultEnd;

        // Normalize order and clamp max range to 15 days
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $maxEnd = $start->copy()->addDays(15);
        if ($end->gt($maxEnd)) {
            $end = $maxEnd;
        }

        // Get sales data for the requested (clamped) period
        $salesData = $this->getSalesDataForPeriod($start, $end);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'clients' => Client::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'leads' => Lead::count(),
                'opportunities' => Opportunity::count(),
            ],
            'salesChart' => $salesData,
            'filters' => [
                'sales_from' => $start->format('Y-m-d H:i'),
                'sales_to' => $end->format('Y-m-d H:i'),
            ],
            'funnelData' => $this->getFunnelData(),
        ]);
    }

    private function getSalesDataForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $sales = Order::select(
            DB::raw('DATE(ordered_at) as date'),
            DB::raw('SUM(total) as total_sales')
        )
            ->whereBetween('ordered_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $categories = [];
        $data = [];

        // Iterate over each day in the period, inclusive
        $cursor = $startDate->copy()->startOfDay();
        $last = $endDate->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $dateKey = $cursor->format('Y-m-d');
            $categories[] = $cursor->format('d/m');
            $data[] = (float) ($sales[$dateKey]->total_sales ?? 0);
            $cursor->addDay();
        }

        return [
            'categories' => $categories,
            'data' => $data,
        ];
    }

    private function getFunnelData(): array
    {
        // Leads (total)
        $leadsTotal = Lead::count();

        // Leads Qualificados
        $leadsQualified = Lead::where('status', 'qualified')->count();

        // Oportunidades (abertas - ainda no pipeline)
        $opportunitiesOpen = Opportunity::whereNotIn('stage', ['won', 'lost'])->count();

        // Oportunidades Vencidas (ganhas)
        $opportunitiesWon = Opportunity::where('stage', 'won')->count();

        return [
            'labels' => ['Leads', 'Leads Qualificados', 'Oportunidades', 'Oportunidades Vencidas'],
            'data' => [$leadsTotal, $leadsQualified, $opportunitiesOpen, $opportunitiesWon],
        ];
    }
}

<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Crm\Entities\Lead;

class LeadExportController extends Controller
{
    public function __construct()
    {
        $this->applyPermissions(
            'leads',
            [],
            [
                'csv' => 'export',
            ]
        );
    }

    public function csv(Request $request)
    {
        $leads = Lead::where('trader_id', auth()->id())
            ->active()
            ->filter()
            ->orderByDesc('last_activity_at')
            ->get();

        $filename = 'leads-export-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Phone', 'Email', 'Source', 'Status', 'Lost Reason', 'Created At', 'Last Activity']);
            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->phone,
                    $lead->email,
                    $lead->source?->value,
                    $lead->status?->value,
                    $lead->lost_reason,
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->last_activity_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\DailyReportNotification;
use App\Services\ReportService;
use Illuminate\Console\Command;

class SendDailyReportCommand extends Command
{
    protected $signature = 'inventory:daily-report';

    protected $description = 'Send yesterday sales, purchases, profit, and low-stock summary to active administrators and managers.';

    public function handle(
        ReportService $reportService
    ): int {
        $date = now()
            ->subDay()
            ->startOfDay();

        $sales = $reportService->salesReport(
            $date->copy(),
            $date->copy()
        );

        $purchases = $reportService->purchasesReport(
            $date->copy(),
            $date->copy()
        );

        $profit = $reportService->profitReport(
            $date->copy(),
            $date->copy()
        );

        $stock = $reportService->stockReport();

        $report = [
            'date' => $date->toDateString(),

            'sales_total' => (int) $sales['total'],

            'sales_count' => (int) $sales['count'],

            'purchases_total' => (int) $purchases['total'],

            'purchases_count' => (int) $purchases['count'],

            'profit' => (int) $profit['profit'],

            'low_stock_count' => (int) $stock['low_stock_count'],
        ];

        $recipients = User::query()
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'role',
                [
                    Role::Admin->value,
                    Role::Manager->value,
                ]
            )
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn(
                'No active administrators or managers found.'
            );

            return self::SUCCESS;
        }

        $notificationsSent = 0;

        foreach ($recipients as $recipient) {
            if (
                $this->alreadyReceivedReport(
                    $recipient,
                    $report['date']
                )
            ) {
                continue;
            }

            $recipient->notify(
                new DailyReportNotification(
                    $report
                )
            );

            $notificationsSent++;
        }

        $this->info(
            sprintf(
                'Daily report for %s sent to %d recipients.',

                $report['date'],

                $notificationsSent
            )
        );

        return self::SUCCESS;
    }

    private function alreadyReceivedReport(
        User $recipient,
        string $date
    ): bool {
        return $recipient
            ->notifications()
            ->where(
                'type',
                DailyReportNotification::class
            )
            ->get()
            ->contains(
                fn ($notification): bool => (
                    $notification->data['date'] ?? null
                ) === $date
            );
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DailyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $report
    ) {}

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }

    public function toArray(
        object $notifiable
    ): array {
        return [
            'type' => 'daily_report',

            'date' => $this->report['date'],

            'sales_total' => $this->report['sales_total'],

            'sales_count' => $this->report['sales_count'],

            'purchases_total' => $this->report['purchases_total'],

            'purchases_count' => $this->report['purchases_count'],

            'profit' => $this->report['profit'],

            'low_stock_count' => $this->report['low_stock_count'],

            'message' => sprintf(
                'Daily report for %s: sales %s, purchases %s, profit %s, low-stock products %d.',

                $this->report['date'],

                $this->formatMoney(
                    $this->report['sales_total']
                ),

                $this->formatMoney(
                    $this->report['purchases_total']
                ),

                $this->formatMoney(
                    $this->report['profit']
                ),

                $this->report['low_stock_count']
            ),
        ];
    }

    private function formatMoney(
        int $amount
    ): string {
        $negative = $amount < 0;

        $amount = abs(
            $amount
        );

        return sprintf(
            '%s%d.%02d',

            $negative ? '-' : '',

            intdiv(
                $amount,
                100
            ),

            $amount % 100
        );
    }
}

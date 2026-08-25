<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function export(
        string $filename,
        array $headers,
        iterable $rows
    ): StreamedResponse {
        return response()->streamDownload(
            function () use (
                $headers,
                $rows
            ): void {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                if ($handle === false) {
                    throw new RuntimeException(
                        'Unable to open CSV output stream.'
                    );
                }

                try {
                    fwrite(
                        $handle,
                        "\xEF\xBB\xBF"
                    );

                    fputcsv(
                        $handle,
                        $this->sanitizeRow($headers)
                    );

                    foreach ($rows as $row) {
                        fputcsv(
                            $handle,
                            $this->sanitizeRow(
                                (array) $row
                            )
                        );
                    }
                } finally {
                    fclose(
                        $handle
                    );
                }
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',

                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]
        );
    }

    private function sanitizeRow(
        array $row
    ): array {
        return array_map(
            function (
                mixed $value
            ): mixed {
                if ($value === null) {
                    return '';
                }

                if (! is_string($value)) {
                    return $value;
                }

                if (
                    preg_match(
                        '/^[=+\-@\t\r]/',
                        $value
                    ) === 1
                ) {
                    return "'".$value;
                }

                return $value;
            },
            $row
        );
    }
}

<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductCsvImportService
{
    private const HEADERS = [
        'sku',
        'name',
        'category',
        'cost_price',
        'sell_price',
        'reorder_level',
        'is_active',
    ];

    public function import(
        UploadedFile $file
    ): array {
        $rows = $this->readRows(
            $file
        );

        $validatedRows = $this->validateRows(
            $rows
        );

        return DB::transaction(
            function () use ($validatedRows): array {
                $created = 0;

                $updated = 0;

                foreach ($validatedRows as $row) {
                    $category = Category::query()
                        ->where(
                            'name',
                            $row['category']
                        )
                        ->firstOrFail();

                    $product = Product::query()
                        ->where(
                            'sku',
                            $row['sku']
                        )
                        ->first();

                    $attributes = [
                        'name' => $row['name'],

                        'category_id' => $category->id,

                        'cost_price' => $row['cost_price'],

                        'sell_price' => $row['sell_price'],

                        'reorder_level' => (int) $row['reorder_level'],

                        'is_active' => $this->parseBoolean(
                            $row['is_active']
                        ),
                    ];

                    if ($product !== null) {
                        $product->update(
                            $attributes
                        );

                        $updated++;

                        continue;
                    }

                    Product::query()->create([
                        'sku' => $row['sku'],

                        ...$attributes,
                    ]);

                    $created++;
                }

                return [
                    'created' => $created,

                    'updated' => $updated,

                    'total' => count(
                        $validatedRows
                    ),
                ];
            }
        );
    }

    private function readRows(
        UploadedFile $file
    ): array {
        $path = $file->getRealPath();

        if ($path === false) {
            $this->fail(
                'The CSV file could not be read.'
            );
        }

        $handle = fopen(
            $path,
            'rb'
        );

        if ($handle === false) {
            $this->fail(
                'The CSV file could not be opened.'
            );
        }

        try {
            $headers = fgetcsv(
                $handle
            );

            if ($headers === false) {
                $this->fail(
                    'The CSV file is empty.'
                );
            }

            $headers = array_map(
                fn ($header): string => trim(
                    (string) $header
                ),
                $headers
            );

            if (isset($headers[0])) {
                $headers[0] = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    $headers[0]
                );
            }

            $this->validateHeaders(
                $headers
            );

            $rows = [];

            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    $this->fail(
                        sprintf(
                            'Row %d has an invalid number of columns.',
                            $line
                        )
                    );
                }

                $row = array_combine(
                    $headers,
                    array_map(
                        fn ($value): string => trim(
                            (string) $value
                        ),
                        $values
                    )
                );

                if ($row === false) {
                    $this->fail(
                        sprintf(
                            'Row %d could not be read.',
                            $line
                        )
                    );
                }

                $rows[] = [
                    'line' => $line,

                    'data' => $row,
                ];
            }

            if ($rows === []) {
                $this->fail(
                    'The CSV file does not contain any products.'
                );
            }

            return $rows;
        } finally {
            fclose(
                $handle
            );
        }
    }

    private function validateHeaders(
        array $headers
    ): void {
        $missingHeaders = array_diff(
            self::HEADERS,
            $headers
        );

        if ($missingHeaders !== []) {
            $this->fail(
                sprintf(
                    'The CSV file is missing required columns: %s.',
                    implode(
                        ', ',
                        $missingHeaders
                    )
                )
            );
        }

        $unexpectedHeaders = array_diff(
            $headers,
            self::HEADERS
        );

        if ($unexpectedHeaders !== []) {
            $this->fail(
                sprintf(
                    'The CSV file contains unsupported columns: %s.',
                    implode(
                        ', ',
                        $unexpectedHeaders
                    )
                )
            );
        }

        if (count($headers) !== count(array_unique($headers))) {
            $this->fail(
                'The CSV file contains duplicate column names.'
            );
        }
    }

    private function validateRows(
        array $rows
    ): array {
        $errors = [];

        $validated = [];

        $seenSkus = [];

        $categories = Category::query()
            ->pluck('name')
            ->all();

        foreach ($rows as $row) {
            $line = $row['line'];

            $data = $row['data'];

            $validator = Validator::make(
                $data,
                [
                    'sku' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'name' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'category' => [
                        'required',
                        'string',
                    ],

                    'cost_price' => [
                        'required',
                        'regex:/^\d+(?:\.\d{1,2})?$/',
                    ],

                    'sell_price' => [
                        'required',
                        'regex:/^\d+(?:\.\d{1,2})?$/',
                    ],

                    'reorder_level' => [
                        'required',
                        'integer',
                        'min:0',
                    ],

                    'is_active' => [
                        'required',
                        'in:0,1,true,false,yes,no',
                    ],
                ]
            );

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = sprintf(
                        'Row %d: %s',
                        $line,
                        $message
                    );
                }

                continue;
            }

            if (! in_array(
                $data['category'],
                $categories,
                true
            )) {
                $errors[] = sprintf(
                    'Row %d: Category "%s" does not exist.',
                    $line,
                    $data['category']
                );
            }

            if (isset($seenSkus[$data['sku']])) {
                $errors[] = sprintf(
                    'Row %d: SKU "%s" is duplicated in the CSV file.',
                    $line,
                    $data['sku']
                );
            }

            $seenSkus[$data['sku']] = true;

            if (
                $this->moneyToCents(
                    $data['sell_price']
                )
                < $this->moneyToCents(
                    $data['cost_price']
                )
            ) {
                $errors[] = sprintf(
                    'Row %d: Selling price cannot be lower than cost price.',
                    $line
                );
            }

            $validated[] = $data;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }

        return $validated;
    }

    private function isEmptyRow(
        array $values
    ): bool {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseBoolean(
        string $value
    ): bool {
        return in_array(
            strtolower($value),
            [
                '1',
                'true',
                'yes',
            ],
            true
        );
    }

    private function moneyToCents(
        string $value
    ): int {
        [$whole, $fraction] = array_pad(
            explode(
                '.',
                $value,
                2
            ),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad(
                $fraction,
                2,
                '0'
            );
    }

    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'file' => $message,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(
        Request $request
    ): View {
        abort_unless(
            $request->user()->role === Role::Admin,
            403
        );

        $settings = [
            'company_name' => Setting::get(
                'company_name',
                'Sales Inventory'
            ),

            'company_phone' => Setting::get(
                'company_phone',
                ''
            ),

            'company_address' => Setting::get(
                'company_address',
                ''
            ),

            'company_logo' => Setting::get(
                'company_logo',
                ''
            ),

            'currency_symbol' => Setting::get(
                'currency_symbol',
                'EGP'
            ),

            'tax_rate' => Setting::get(
                'tax_rate',
                '0'
            ),

            'low_stock_threshold' => Setting::get(
                'low_stock_threshold',
                '5'
            ),

            'invoice_prefix' => Setting::get(
                'invoice_prefix',
                'INV'
            ),

            'purchase_invoice_prefix' => Setting::get(
                'purchase_invoice_prefix',
                'PUR'
            ),
        ];

        return view(
            'settings.edit',
            compact(
                'settings'
            )
        );
    }

    public function update(
        UpdateSettingsRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $oldLogo = Setting::get(
            'company_logo'
        );

        $newLogo = null;

        if ($request->hasFile('company_logo')) {
            $newLogo = $request
                ->file('company_logo')
                ->store(
                    'company',
                    'public'
                );
        }

        try {
            DB::transaction(
                function () use (
                    $data,
                    $newLogo
                ): void {
                    foreach (
                        [
                            'company_name',
                            'company_phone',
                            'company_address',
                            'currency_symbol',
                            'tax_rate',
                            'low_stock_threshold',
                            'invoice_prefix',
                            'purchase_invoice_prefix',
                        ] as $key
                    ) {
                        Setting::set(
                            $key,
                            $data[$key] ?? null
                        );
                    }

                    if ($newLogo !== null) {
                        Setting::set(
                            'company_logo',
                            $newLogo
                        );
                    }
                }
            );
        } catch (\Throwable $exception) {
            if ($newLogo !== null) {
                Storage::disk('public')->delete(
                    $newLogo
                );
            }

            throw $exception;
        }

        if (
            $newLogo !== null
            && filled($oldLogo)
            && $oldLogo !== $newLogo
        ) {
            Storage::disk('public')->delete(
                $oldLogo
            );
        }

        return redirect()
            ->route(
                'settings.edit'
            )
            ->with(
                'success',
                'Settings updated successfully.'
            );
    }
}

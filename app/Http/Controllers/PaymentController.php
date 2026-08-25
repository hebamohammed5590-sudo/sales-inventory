<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function store(
        StorePaymentRequest $request,
        string $type,
        Invoice $invoice,
        PaymentService $paymentService
    ): RedirectResponse {
        $invoiceType = InvoiceType::tryFrom($type);

        abort_if(
            $invoiceType === null,
            404
        );

        abort_unless(
            $invoice->type === $invoiceType,
            404
        );

        $paymentService->create(
            invoice: $invoice,
            user: $request->user(),
            data: $request->validated()
        );

        return redirect()
            ->route(
                'invoices.show',
                [
                    'type' => $invoiceType->value,
                    'invoice' => $invoice,
                ]
            )
            ->with(
                'success',
                'Payment recorded successfully.'
            );
    }
}

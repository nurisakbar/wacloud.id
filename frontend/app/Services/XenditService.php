<?php

namespace App\Services;

use Xendit\Invoice;
use Xendit\Xendit;
use Illuminate\Support\Facades\Log;

class XenditService
{
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));
        Xendit::setApiKey($this->secretKey);
    }

    /**
     * Create invoice for quota purchase
     */
    public function createInvoice(array $params): array
    {
        try {
            $invoiceParams = [
                'external_id' => $params['external_id'],
                'amount' => $params['amount'],
                'payer_email' => $params['payer_email'],
                'description' => $params['description'],
                'invoice_duration' => 86400, // 24 hours
                'success_redirect_url' => $params['success_url'] ?? route('quota.index'),
                'failure_redirect_url' => $params['failure_url'] ?? route('quota.index'),
            ];

            // Add customer info if provided
            if (isset($params['customer'])) {
                $invoiceParams['customer'] = $params['customer'];
            }

            // Add items if provided
            if (isset($params['items'])) {
                $invoiceParams['items'] = $params['items'];
            }

            $invoice = Invoice::create($invoiceParams);

            Log::info('Xendit invoice created', [
                'external_id' => $params['external_id'],
                'invoice_id' => $invoice['id'],
                'amount' => $params['amount'],
            ]);

            return [
                'success' => true,
                'invoice' => $invoice,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit invoice creation failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice by ID
     */
    public function getInvoice(string $invoiceId): array
    {
        try {
            $invoice = Invoice::retrieve($invoiceId);

            return [
                'success' => true,
                'invoice' => $invoice,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit get invoice failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature (if needed)
     */
    public function verifyWebhook(string $signature, string $payload): bool
    {
        // Xendit webhook verification
        // You can implement signature verification here if needed
        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create payment link (alternative to invoice)
     */
    public function createPaymentLink(array $params): array
    {
        try {
            $paymentLinkParams = [
                'reference_id' => $params['external_id'],
                'amount' => $params['amount'],
                'currency' => 'IDR',
                'description' => $params['description'],
                'success_redirect_url' => $params['success_url'] ?? route('quota.index'),
                'failure_redirect_url' => $params['failure_url'] ?? route('quota.index'),
            ];

            // Add customer info if provided
            if (isset($params['customer'])) {
                $paymentLinkParams['customer'] = $params['customer'];
            }

            // Add items if provided
            if (isset($params['items'])) {
                $paymentLinkParams['items'] = $params['items'];
            }

            // Use Xendit Payment Links API
            $paymentLink = \Xendit\PaymentLinks::create($paymentLinkParams);

            Log::info('Xendit payment link created', [
                'reference_id' => $params['external_id'],
                'payment_link_id' => $paymentLink['id'],
                'amount' => $params['amount'],
            ]);

            return [
                'success' => true,
                'payment_link' => $paymentLink,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit payment link creation failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment link by ID
     */
    public function getPaymentLink(string $paymentLinkId): array
    {
        try {
            $paymentLink = \Xendit\PaymentLinks::retrieve($paymentLinkId);

            return [
                'success' => true,
                'payment_link' => $paymentLink,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit get payment link failed', [
                'payment_link_id' => $paymentLinkId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}


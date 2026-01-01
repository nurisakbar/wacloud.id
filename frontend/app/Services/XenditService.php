<?php

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\Invoice as InvoiceModel;
use Illuminate\Support\Facades\Log;

class XenditService
{
    protected string $secretKey;
    protected InvoiceApi $invoiceApi;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key', env('XENDIT_SECRET_KEY'));
        
        // Set API key using Configuration
        Configuration::setXenditKey($this->secretKey);
        
        // Initialize Invoice API
        $this->invoiceApi = new InvoiceApi();
    }

    /**
     * Create invoice for quota purchase
     */
    public function createInvoice(array $params): array
    {
        try {
            // Prepare invoice request data
            $requestData = [
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
                $requestData['customer'] = $params['customer'];
            }

            // Add items if provided
            if (isset($params['items'])) {
                $requestData['items'] = $params['items'];
            }

            // Create invoice request object
            $createInvoiceRequest = new CreateInvoiceRequest($requestData);

            // Create invoice using API
            $invoice = $this->invoiceApi->createInvoice($createInvoiceRequest);

            Log::info('Xendit invoice created', [
                'external_id' => $params['external_id'],
                'invoice_id' => $invoice->getId(),
                'amount' => $params['amount'],
            ]);

            // Convert to array format for backward compatibility
            $invoiceArray = [
                'id' => $invoice->getId(),
                'external_id' => $invoice->getExternalId(),
                'amount' => $invoice->getAmount(),
                'status' => $invoice->getStatus(),
                'invoice_url' => $invoice->getInvoiceUrl(),
                'payer_email' => $invoice->getPayerEmail(),
                'description' => $invoice->getDescription(),
            ];

            return [
                'success' => true,
                'invoice' => $invoiceArray,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit invoice creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $invoice = $this->invoiceApi->getInvoiceById($invoiceId);

            // Convert to array format for backward compatibility
            $invoiceArray = [
                'id' => $invoice->getId(),
                'external_id' => $invoice->getExternalId(),
                'amount' => $invoice->getAmount(),
                'status' => $invoice->getStatus(),
                'invoice_url' => $invoice->getInvoiceUrl(),
                'payer_email' => $invoice->getPayerEmail(),
                'description' => $invoice->getDescription(),
            ];

            return [
                'success' => true,
                'invoice' => $invoiceArray,
            ];
        } catch (\Exception $e) {
            Log::error('Xendit get invoice failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Note: Payment Links API may not be available in SDK v7.0.0
     * Using invoice as alternative
     */
    public function createPaymentLink(array $params): array
    {
        // For now, use invoice creation as alternative
        return $this->createInvoice($params);
    }

    /**
     * Get payment link by ID
     * Note: Payment Links API may not be available in SDK v7.0.0
     */
    public function getPaymentLink(string $paymentLinkId): array
    {
        // For now, treat as invoice ID
        return $this->getInvoice($paymentLinkId);
    }
}


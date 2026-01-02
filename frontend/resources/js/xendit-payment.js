/**
 * Xendit Payment Integration
 * Handles payment flow for Xendit integration
 */

class XenditPayment {
    constructor() {
        this.init();
    }

    init() {
        // Handle payment method selection
        this.handlePaymentMethodChange();
        
        // Handle form submission for Xendit payment
        this.handleFormSubmission();
    }

    /**
     * Handle payment method change
     */
    handlePaymentMethodChange() {
        const paymentMethodSelect = document.getElementById('payment_method');
        if (paymentMethodSelect) {
            paymentMethodSelect.addEventListener('change', (e) => {
                const method = e.target.value;
                const paymentReferenceField = document.getElementById('payment-reference-field');
                
                if (method === 'manual' && paymentReferenceField) {
                    paymentReferenceField.style.display = 'block';
                } else if (paymentReferenceField) {
                    paymentReferenceField.style.display = 'none';
                }
            });
        }
    }

    /**
     * Handle form submission for Xendit payment
     */
    handleFormSubmission() {
        const purchaseForm = document.getElementById('purchase-form');
        if (!purchaseForm) return;

        purchaseForm.addEventListener('submit', async (e) => {
            const paymentMethod = document.getElementById('payment_method')?.value;
            
            if (paymentMethod === 'xendit') {
                e.preventDefault();
                await this.processXenditPayment(purchaseForm);
            }
        });
    }

    /**
     * Process Xendit payment
     */
    async processXenditPayment(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        try {
            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Get form data
            const formData = new FormData(form);
            
            // First, submit the purchase form to create the purchase
            const purchaseResponse = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!purchaseResponse.ok) {
                throw new Error('Failed to create purchase');
            }

            const purchaseResult = await purchaseResponse.json();
            
            // If purchase was created successfully, create invoice
            if (purchaseResult.success && purchaseResult.purchase_id) {
                await this.createInvoiceAndRedirect(purchaseResult.purchase_id);
            } else if (purchaseResult.redirect) {
                // If redirect URL is provided (from Xendit invoice)
                window.location.href = purchaseResult.redirect;
            } else {
                throw new Error(purchaseResult.error || 'Failed to process payment');
            }
        } catch (error) {
            console.error('Payment error:', error);
            this.showError(error.message || 'Failed to process payment. Please try again.');
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }

    /**
     * Create invoice and redirect to payment page
     */
    async createInvoiceAndRedirect(purchaseId) {
        try {
            const response = await fetch('/payment/create-invoice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    purchase_id: purchaseId,
                }),
            });

            const result = await response.json();

            if (result.success && result.invoice_url) {
                // Show loading modal
                this.showPaymentModal();
                
                // Redirect to Xendit payment page
                window.location.href = result.invoice_url;
            } else {
                throw new Error(result.error || 'Failed to create payment invoice');
            }
        } catch (error) {
            console.error('Invoice creation error:', error);
            throw error;
        }
    }

    /**
     * Show payment modal
     */
    showPaymentModal() {
        // Create modal if it doesn't exist
        let modal = document.getElementById('xendit-payment-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'xendit-payment-modal';
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <h5>Redirecting to Payment...</h5>
                            <p class="text-muted">You will be redirected to Xendit payment page</p>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        // Show modal using Bootstrap
        if (typeof bootstrap !== 'undefined') {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else if (typeof $ !== 'undefined') {
            $(modal).modal('show');
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert-danger');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create error alert
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="fas fa-exclamation-circle"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

        // Insert at the top of the form
        const form = document.getElementById('purchase-form');
        if (form) {
            form.insertBefore(alert, form.firstChild);
        }
    }

    /**
     * Check payment status (for status page)
     */
    static async checkPaymentStatus(purchaseId) {
        try {
            const response = await fetch(`/payment/${purchaseId}/status`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            return await response.json();
        } catch (error) {
            console.error('Error checking payment status:', error);
            return { success: false, error: error.message };
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new XenditPayment();
    });
} else {
    new XenditPayment();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = XenditPayment;
}




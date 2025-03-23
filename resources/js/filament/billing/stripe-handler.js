// Initialize Stripe handler
document.addEventListener('DOMContentLoaded', function () {
    // Check if Stripe is loaded and if we're on a page with Stripe elements
    if (typeof Stripe !== 'undefined' && document.querySelector('#card-element')) {
        initializeStripe();
    }
});

window.initializeStripe = function () {
    // Get the publishable key from meta tag
    const stripeKey = document.querySelector('meta[name="stripe-key"]').getAttribute('content');
    const stripe = Stripe(stripeKey);

    // Get the client secret from the data attribute
    const clientSecret = document.querySelector('#payment-form').dataset.secret;

    // Create an instance of Elements
    const elements = stripe.elements();

    // Style the Element
    const style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Create an instance of the card Element
    const card = elements.create('card', { style: style });

    // Add an instance of the card Element into the `card-element` div
    card.mount('#card-element');

    // Handle real-time validation errors from the card Element.
    card.addEventListener('change', function (event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.classList.remove('hidden');
        } else {
            displayError.textContent = '';
            displayError.classList.add('hidden');
        }
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        // Disable the submit button to prevent repeated clicks
        document.getElementById('card-button').disabled = true;
        document.getElementById('card-button').classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('card-button').textContent = 'Processing...';

        stripe.confirmCardSetup(clientSecret, {
            payment_method: {
                card: card,
                billing_details: {
                    name: document.getElementById('card-holder-name').value
                }
            }
        }).then(function (result) {
            if (result.error) {
                // Show error to your customer
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                errorElement.classList.remove('hidden');

                // Re-enable the submit button
                document.getElementById('card-button').disabled = false;
                document.getElementById('card-button').classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('card-button').textContent = 'Update Payment Method';
            } else {
                // Send the payment method ID to the server
                const paymentMethodId = result.setupIntent.payment_method;
                const component = Livewire.find(document.getElementById('payment-form').closest('[wire\\:id]').getAttribute('wire:id'));

                component.updatePaymentMethod(paymentMethodId);

                // Reset the form
                form.reset();
                card.clear();

                // Re-enable the submit button
                document.getElementById('card-button').disabled = false;
                document.getElementById('card-button').classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('card-button').textContent = 'Update Payment Method';
            }
        });
    });
}; 
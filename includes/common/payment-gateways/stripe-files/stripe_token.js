(function($){
  let stripe;
  let elements;
  let card;

  function initStripe() {
    const $target = $('#stripe-card-element');

    if ($target.length === 0) return;
    if ($target.data('stripe-initialized')) return;

    $target.data('stripe-initialized', true);

    stripe = Stripe(mp_stripe_vars.publishable_key);
    elements = stripe.elements();
    card = elements.create('card', {
  hidePostalCode: true,
  style: {
    base: {
      color: '#32325d',
      fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
      fontSize: '16px',
      '::placeholder': {
        color: '#aab7c4'
      }
    },
    invalid: {
      color: '#e3342f',
      iconColor: '#e3342f'
    }
  }
});
    card.mount('#stripe-card-element');

    card.on('change', function(event) {
      const displayError = $('#card-errors');
      displayError.text(event.error ? event.error.message : '');
    });

    $('#mp-checkout-form').off('submit.stripe').on('submit.stripe', function(e){
      e.preventDefault();

      stripe.createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
          name: $('#mp-stripe-name').val() || 'Unbekannt'
        }
      }).then(function(result){
        if (result.error) {
          $('#card-errors').text(result.error.message);
        } else {
          $('#payment_method_id').val(result.paymentMethod.id);
          $('#mp-checkout-form')[0].submit();
        }
      });
    });
  }

  $(document).ready(initStripe);

  $(document).ajaxComplete(function() {
    // Nur neu initialisieren, wenn Stripe-Element vorhanden und noch nicht initialisiert
    if ($('#stripe-card-element').length && !$('#stripe-card-element').data('stripe-initialized')) {
      initStripe();
    }
  });
})(jQuery);


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
    card = elements.create('card', { hidePostalCode: true, style: {/*...*/} });
    card.mount('#stripe-card-element');

    card.on('change', function(event) {
      $('#card-errors').text(event.error ? event.error.message : '');
    });

    $('#mp-checkout-form').off('submit.stripe').on('submit.stripe', function(e){
      e.preventDefault();
      $('#card-errors').text('');

      stripe.createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
          name: $('#mp-stripe-name').val() || 'Unbekannt'
        }
      }).then(function(result){
        if(result.error){
          $('#card-errors').text(result.error.message);
          return;
        }
        // PaymentMethod erfolgreich
        let paymentMethodId = result.paymentMethod.id;
        $('#payment_method_id').val(paymentMethodId);

        // Ajax an Server mit PaymentMethodId senden
        $.ajax({
          url: $('#mp-checkout-form').attr('action'),
          method: 'POST',
          data: $('#mp-checkout-form').serialize(),
          dataType: 'json'
        }).done(function(response){
          if(response.result === 'requires_action'){
            // handle 3D Secure
            stripe.handleCardAction(response.payment_intent_client_secret).then(function(handleResult){
              if(handleResult.error){
                $('#card-errors').text(handleResult.error.message);
              } else {
                // 3D Secure erfolgreich, nochmal AJAX mit payment_intent_id
                $.ajax({
                  url: $('#mp-checkout-form').attr('action'),
                  method: 'POST',
                  data: $.extend({}, $('#mp-checkout-form').serializeArray(), {
                    payment_intent_id: handleResult.paymentIntent.id,
                    payment_method_id: handleResult.paymentIntent.payment_method
                  }),
                  dataType: 'json'
                }).done(function(response){
                  if(response.result === 'success'){
                    window.location.href = response.redirect;
                  } else {
                    $('#card-errors').text(response.message || 'Zahlung fehlgeschlagen nach 3D Secure');
                  }
                }).fail(function(){
                  $('#card-errors').text('Serverfehler nach 3D Secure');
                });
              }
            });
          } else if(response.result === 'success'){
            // Zahlung abgeschlossen, redirect
            window.location.href = response.redirect;
          } else {
            // Fehler anzeigen
            $('#card-errors').text(response.message || 'Zahlung fehlgeschlagen');
          }
        }).fail(function(){
          $('#card-errors').text('Serverfehler, versuch es später nochmal');
        });
      });
    });
  }

  $(document).ready(initStripe);
  $(document).ajaxComplete(function(){
    if($('#stripe-card-element').length && !$('#stripe-card-element').data('stripe-initialized')){
      initStripe();
    }
  });
})(jQuery);




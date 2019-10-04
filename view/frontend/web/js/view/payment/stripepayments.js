define(['uiComponent', 'Magento_Checkout/js/model/payment/renderer-list'], function(Component, rendererList){
	'use strict';

	rendererList.push(
		{type: 'stripe', component: 'Jeff_Stripe/js/view/payment/method-renderer/stripemethod'}
	);

	return Component.extend({});
});
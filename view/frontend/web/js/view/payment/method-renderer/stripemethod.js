define([
	'underscore',
	'Magento_Checkout/js/view/payment/default',
	'Magento_Payment/js/model/credit-card-validation/credit-card-data',
	'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
	'mage/translate'
	], function(_, Component, creditCardData, cardNumberValidator, $t){
		'use strict';

		return Component.extend({
			defaults: {
				template: 'Jeff_Stripe/payment/stripe.html',
				creditCardType: '',
				creditCardExpYear: '',
				creditCardExpMonth: '',
				creditCardNumber: '',
				creditCardSsStartMonth: '',
				creditCardSsStartYear: '',
				creditCardVerificationNumber: '',
				selectedCardType: null
			},

			initObservable: function() {
				this._super()
					.observe([
						'creditCardType',
						'creditCardExpYear',
						'creditCardExpMonth',
						'creditCardNumber',
						'creditCardVerificationNumber',
						'creditCardSsStartYear',
						'creditCardSsStartMonth',
						'selectedCardType'
					]);
				return this;
			},

			getCode: function () {
            	return 'stripe';
        	},

			initialize: function(){
				var self = this;
				this._super();

				//set credit card number to credit card data object
				this.creditCardNumber.subscribe(function(value){
					var result;
					self.selectedCardType(null);

					if(value == '' || value == null){
						return false;
					}

					result = cardNumberValidator(value);

					if(!result.isPotentaillyValid && !result.isValid){
						return false;
					}

					if(result.card !== null) {
						self.selectedCardType(result.card.type);
					}

					if(result.isValid) {
						creditCardData.creditCardNumber = value;
					}

					self.creditCardType(result.card.type);
				});

				//set expiration year to credit card data object
				this.creditCardExpYear.subscribe(function(value){
					creditCardData.creditCardExpYear = value;
				});

				this.creditCardExpMonth.subscribe(function(value){
					creditCardData.creditCardExpMonth = value;
				});

				this.creditCardVerificationNumber.subscribe(function(value){
					creditCardData.cvvCode = value;
				});
			},

			isActive: function() {
				return true;
			},

			getCcAvailableTypes: function() { //getCcAvailableTypes
				return window.checkoutConfig.payment.ccform.availableTypes['stripe'];
			},

			getCcMonths: function() {
				return window.checkoutConfig.payment.ccform.months['stripe'];
			},

			getCcYears: function() {
				return window.checkoutConfig.payment.ccform.years['stripe'];
			},

			hasVerification: function() {
				return window.checkoutConfig.payment.ccform.hasVerification['stripe'];
			},

			getCcAvailableTypesValues: function() {
				return _.map(this.getCcAvailableTypes(), function(value, key){
					return {
						'value' : key,
						'type' : value
					}
				});
			},

			getCcMonthsValues: function() {
				return _.map(this.getCcMonths(), function(value, key){
					return {
						'value': key,
						'month': value
					}
				});
			},

			getCcYearsValues: function() {
				return _.map(this.getCcYears(), function(value, key){
					return {
						'value': key,
						'year': value
					}
				});
			},
		});
});
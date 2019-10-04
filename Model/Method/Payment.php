<?php
namespace Jeff\Stripe\Model\Method;

class Payment extends \Magento\Payment\Model\Method\Cc 
{
	const METHOD_CODE = 'stripe';
    protected $_code                     = self::METHOD_CODE;
 
    protected $_stripe;
 
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_minOrderTotal = 0;
    protected $_supportedCurrencyCodes = array('USD','GBP','EUR');
 
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Stripe\Stripe $stripe,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
 
        $this->_code = 'stripe';
        $this->_stripe = $stripe;
        $this->_stripe->setApiKey($this->getConfigData('api_key'));
 
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
 
 
    }
 
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }
 
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
 {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
        try{
            $charge = \Stripe\Charge::create(array(
                'amount' => $amount*100,
                'currency' => strtolower($order->getBaseCurrencyCode()),
                'card'      => array(
                    'number' => $payment->getCcNumber(),
                    'exp_month' => sprintf('%02d',$payment->getCcExpMonth()),
                    'exp_year' => $payment->getCcExpYear(),
                    'cvc' => $payment->getCcCid(),
                    'name' => $billing->getName(),
                    'address_line1' => $billing->getStreet(1),
                    'address_line2' => $billing->getStreet(2),
                    'address_zip' => $billing->getPostcode(),
                    'address_state' => $billing->getRegion(),
                    'address_country' => $billing->getCountry(),
                ),
                'description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail())
            ));
 
            $payment->setTransactionId($charge->id)->setIsTransactionClosed(0);
 
            return $this;
 
        }catch (\Exception $e){
            $this->debugData(['exception' => $e->getMessage()]);
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }
    }
 
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
 
        try {
            \Stripe\Charge::retrieve($transactionId)->refund();
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }
 
        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);
 
        return $this;
    }
 
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null){
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        if($quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }
        return $this->getConfigData('api_key', ($quote ? $quote->getStoreId() : null))
        && parent::isAvailable($quote);
    }
}

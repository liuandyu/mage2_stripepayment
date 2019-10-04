<?php
namespace Jeff\Stripe\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Source;

class StripeConfigProvider implements ConfigProviderInterface
{
	public function __construct(
		\Magento\Payment\Model\CcConfig $ccConfig,
		Source $assetSource
	){
		$this->ccConfig = $ccConfig;
		$this->assetSource = $assetSource;
	}

	protected $_methodCode = 'stripe';

	/**
	 * {@inheritdoc}
	 */
	public function getConfig()
	{
		return [
			'payment' => [
				'ccform' => [
					'availableTypes' => [$this->_methodCode => $this->ccConfig->getCcAvailableTypes()],
					'months' => [$this->_methodCode => $this->ccConfig->getCcMonths()],
					'years' => [$this->_methodCode => $this->ccConfig->getCcYears()],
					'hasVerification' => $this->ccConfig->hasVerification(),
				],
			]
		];
	}
}
<?php

/*
 * Developer: Juan Carlos Ludeña
 * Github: https://github.com/jludena
 */

namespace Culqi\Native\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Payment extends AbstractMethod
{
    const CODE = 'culqi_pay';

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_logData;
    protected $_minAmount;
    protected $_maxAmount;
    protected $_privateKey;
    protected $_publicKey;
    protected $_supportedCurrencyCodes = array('USD', 'PEN');
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc', 'source_id'];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Culqi\Native\Model\LogData $logData,
        \Magento\Framework\App\Filesystem\DirectoryList $dir,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
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
            null,
            null,
            $data
        );

        $this->_logData = $logData;
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->_privateKey = $encryptor->decrypt($this->getConfigData('private_key'));
        $this->_publicKey = $encryptor->decrypt($this->getConfigData('public_key'));
    }

    /**
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();

        if (!$payment->hasAdditionalInformation('token')) {
            $this->_logger->error('Payment tokenizer error');
            throw new \Magento\Framework\Validator\Exception(__('Payment tokenizer error.'));
        }

        $requestData = [
            'amount' => $amount * 100,
            'capture' => true,
            'currency_code' => strtoupper($order->getBaseCurrencyCode()),
            'description' => 'Venta ' . date('Y-m-d H:i:s'),
            'email' => $billing->getEmail(),
            'installments' => 0,
            'antifraud_details' => [
                'address' => $billing->getStreetLine(1) . ' ' . $billing->getStreetLine(2),
                'address_city' => $billing->getCity(),
                'country_code' => $billing->getCountryId(),
                'first_name' => $billing->getFirstname(),
                'last_name' => $billing->getLastname(),
                'phone_number' => $billing->getTelephone(),
            ],
            'source_id' => $payment->getAdditionalInformation('token'),
        ];

        try {
            $objCulqi = new \Culqi\Culqi(['api_key' => $this->_privateKey]);
            $charge = $objCulqi->Charges->create($requestData);

            $payment->setTransactionId($charge->id)->setIsTransactionClosed(0);

        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->error($this->_logData->prepareData(
                $requestData, $this->getDebugReplacePrivateDataKeys()
            ));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }

        return $this;
    }

    /**
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();

        try {
            $requestData = [
                'amount' => $amount * 100,
                'charge_id' => $transactionId,
                'reason' => 'solicitud_comprador'
            ];

            $objCulqi = new \Culqi\Culqi(['api_key' => $this->_privateKey]);
            $objCulqi->Refunds->create($requestData);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            $this->_logger->error($this->_logData->prepareData(
                $requestData, $this->getDebugReplacePrivateDataKeys()
            ));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }

        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);

        return $this;
    }

    /**
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        if (!$this->getConfigData('public_key') || !$this->getConfigData('private_key')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }
}
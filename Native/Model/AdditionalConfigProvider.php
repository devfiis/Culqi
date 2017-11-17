<?php

/*
 * Developer: Juan Carlos Ludeña
 * Github: https://github.com/jludena
 */

namespace Culqi\Native\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Payment\Gateway\Config\Config $config
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Payment\Gateway\Config\Config $config,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    )
    {
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Payment::CODE => [
                    'isActive' => $this->config->getValue('active'),
                    'title' => $this->config->getValue('title'),
                    'publicKey' => $this->encryptor->decrypt($this->config->getValue('public_key')),
                ]
            ]
        ];
    }
}
<?php

/*
 * Developer: Juan Carlos Ludeña
 * Github: https://github.com/jludena
 */

namespace Culqi\Native\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'DI'];
    }
}

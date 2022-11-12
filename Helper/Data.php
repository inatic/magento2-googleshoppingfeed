<?php

namespace Inatic\GoogleShoppingFeed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    private const CONFIG_PATH = 'inaticfeeds/googleshopping/';

    public function getConfig($configNode)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH.$configNode,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}

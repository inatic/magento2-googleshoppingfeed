<?php

namespace Inatic\GoogleShoppingFeed\Controller\Index;

use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\ActionInterface;
use Inatic\GoogleShoppingFeed\Model\XmlFeed;
use Inatic\GoogleShoppingFeed\Helper\Data;

class Index implements ActionInterface
{

    /**
     * Result Forward Factory
     *
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    private $resultForwardFactory;

    /**
     * XmlFeed Model
     *
     * @var \Inatic\GoogleShoppingFeed\Model\XmlFeed
     */
    protected $xmlFeed;

    /**
     * General Helper
     *
     * @var \Inatic\GoogleShoppingFeed\Helper\Data
     */
    private $helper;

    public function __construct(
        ForwardFactory $resultForwardFactory,
        RawFactory $resultRawFactory,
        XmlFeed $xmlFeed,
        Data $helper
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->xmlFeed = $xmlFeed;
        $this->helper = $helper;
    }

    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultRaw = $this->resultRawFactory->create();

        if (!empty($this->helper->getConfig('enabled'))) {
            $resultRaw->setHeader('Content-Type', 'text/xml');
            $resultRaw->setContents($this->xmlFeed->getFeedFile());
            return $resultRaw;
        }
        return $resultForward->forward('noroute');
    }
}


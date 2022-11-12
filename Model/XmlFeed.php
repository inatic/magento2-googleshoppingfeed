<?php

namespace Inatic\GoogleShoppingFeed\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Inatic\GoogleShoppingFeed\Helper\Data;
use Inatic\GoogleShoppingFeed\Helper\Products;

class XmlFeed
{

    /**
     * Category Collection
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    private $categoryCollection;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * General Helper
     *
     * @var \Inatic\GoogleShoppingFeed\Helper\Data
     */
    private $helper;

    /**
     * Product Helper
     *
     * @var \Inatic\GoogleShoppingFeed\Helper\Products
     */
    private $productFeedHelper;

    public function __construct(
        Data $helper,
        Products $productFeedHelper,
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollection,
        TaxCalculationInterface $taxCalculation,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->productFeedHelper = $productFeedHelper;
        $this->storeManager = $storeManager;
        $this->categoryCollection = $categoryCollection;
        $this->taxCalculation = $taxCalculation;
        $this->scopeConfig = $scopeConfig;
    }

    public function getFeedFile(): string
    {
        $xml = '';

        $fileName = "googleshoppingfeed.xml";
        if (file_exists($fileName)){
            $xml = file_get_contents($fileName); //phpcs:ignore
        }
        // comment out for testing
        if (strlen($xml) < 500) {
            $xml = $this->getFeed();
        }
        return $xml;
    }

    public function getFeed(): string
    {
        $xml = $this->getXmlHeader();
        $xml .= $this->getProductsXml();
        $xml .= $this->getXmlFooter();

        return $xml;
    }

    public function getXmlHeader(): string
    {
        header("Content-Type: application/xml; charset=utf-8"); //phpcs:ignore

        $xml =  '<?xml version="1.0"?>';
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
        $xml .= '<channel>';
        $xml .= '<title>'.$this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE).' Product Feed' . '</title>';
        $xml .= '<link>'.$this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB,true).'</link>';
        $xml .= '<description>Product Feed for Google Shopping</description>';

        return $xml;
    }

    public function getProductsXml(): string
    {
        $productCollection = $this->productFeedHelper->getFilteredProducts();
        $xml = "";

        foreach ($productCollection as $product) {
            if ($this->isValidProduct($product)) {
                $xml .= "<item>".$this->buildProductXml($product)."</item>";
            }
        }

        return $xml;
    }

    public function getXmlFooter(): string
    {
        return  '</channel></rss>';
    }

    private function isValidProduct($product): bool
    {
        if ($product->getImage() === "no_selection"
            || (string) $product->getImage() === ""
            || $product->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE
        ) {
            return false;
        }

        return true;
    }

    public function buildProductXml($product): string
    {
        $storeId = 1;

        # Prepare values
        $base_url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA,true);
        $image_link = $base_url . 'catalog/product' . $product->getImage();

        ## Price calculation
        if ($taxAttribute = $product->getTaxClassId()) {
            $productRateId = (int) $taxAttribute;
        }
        $rate = $this->taxCalculation->getCalculatedRate($productRateId);
        if ((int) $this->scopeConfig->getValue('tax/calculation/price_includes_tax', ScopeInterface::SCOPE_STORE) === 1) {
            // Product price in catalog is including tax
            $regularPriceExcludingTax = $product->getPriceInfo()->getPrice('regular_price')->getValue() / (1 + ($rate / 100));
            $specialPriceExcludingTax = $product->getPriceInfo()->getPrice('final_price')->getValue() / (1 + ($rate / 100));
        } else {
            // Product price in catalog is excluding tax
            $regularPriceExcludingTax = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $specialPriceExcludingTax = $product->getPriceInfo()->getPrice('final_price')->getValue();
        }
        $regularPrice = $regularPriceExcludingTax * (1 + $rate / 100);
        $specialPrice = $specialPriceExcludingTax * (1 + $rate / 100);
        $currencySymbol = $this->productFeedHelper->getCurrentCurrencySymbol();

        # Required attributes
        $xml = '';
        $xml .= $this->createNode("id", $product->getId());
        $xml .= $this->createNode("title", $product->getName(), true);
        $xml .= $this->createNode("description", $this->fixDescription($product->getDescription()), true);
        $xml .= $this->createNode("link", $product->getProductUrl());
        $xml .= $this->createNode("image_link", $image_link);
        $xml .= $this->createNode("availability", ( $product->isSaleable() ) ? 'in stock' : 'out of stock');
        $xml .= $this->createNode("price",number_format($regularPrice,2,'.','').' '.$currencySymbol);
        $xml .= $this->createNode("brand", $product->getAttributeText('merk'));
        if (!empty($product->getData("ean"))) {
            $xml .= $this->createNode("gtin", $product->getData("ean"));
        }
        if (!empty($product->getData("mpn"))) { 
            $xml .= $this->createNode("mpn", $product->getData("mpn"));
        }
        $xml .= $this->createNode("condition", "new");

        # Optional attributes
        if (($specialPrice < $regularPrice) && !empty($specialPrice)) {
            $xml .= $this->createNode("sale_price",number_format($specialPrice,2,'.','').' '.$currencySymbol);
        }
        $xml .= $this->createNode("custom_label_0", $this->getProductCategories($product), true);
        $xml .= $this->createNode("product_type", $this->getProductCategories($product), true);
        $xml .= $this->createNode("color", ucfirst($product->getAttributeText('kleur')));
        if (!empty($product->getAttributeText("google_product_category"))) {
            $xml .= $this->createNode("google_product_category",$product->getAttributeText("google_product_category"));
        } elseif (!empty($this->helper->getConfig("default_google_product_category"))) {
            $xml .= $this->createNode("google_product_category",$this->helper->getConfig('default_google_product_category'));
        }
        $images = $product->getMediaGalleryImages()->getItems();
        array_shift($images);
        if ($images){
            foreach ($images as $image){
                $xml .= $this->createNode("additional_image_link", $image['url']);
            }
        }
    
        return $xml;
    }

    public function fixDescription($data): string
    {
        $description = $data;
        $encode = mb_detect_encoding($data);
        $encode = str_replace( "\r", "", $encode);
        return mb_convert_encoding($description, 'UTF-8', $encode);
    }

    public function createNode(string $nodeName, string $value, bool $cData = false): string
    {
        if (empty($value) || empty($nodeName)) {
            return false;
        }

        $cDataStart = "";
        $cDataEnd = "";

        if ($cData === true) {
            $cDataStart = "<![CDATA[";
            $cDataEnd = "]]>";
        }

        return "<".$nodeName.">".$cDataStart.$value.$cDataEnd."</".$nodeName.">";
    }

    public function getFilteredCollection(array $categoryIds)
    {
        $collection = $this->categoryCollection->create();
        return $collection
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'entity_id',
                ['in' => $categoryIds]
            )
            ->setOrder('level', 'ASC')
            ->load();
    }

    private function getProductCategories($product): string
    {
        $categoryIds = $product->getCategoryIds();
        $categoryCollection = $this->getFilteredCollection($categoryIds);
        $fullcategory = "";
        $i = 0;
        foreach ($categoryCollection as $category) {
            $i++;
            if ($i !== (int) $categoryCollection->getSize()) {
                $fullcategory .= $category->getData('name') . ' > ';
            } else {
                $fullcategory .= $category->getData('name');
            }
        }
        return $fullcategory;
    }
}

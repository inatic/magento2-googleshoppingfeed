*Disclaimer:* this module is based on the work of [adampmoss/MageFoxGoogleShopping](https://github.com/adampmoss/MageFoxGoogleShopping) and [Quazz/MageFoxGoogleShopping](https://github.com/Quazz/MageFoxGoogleShopping). 

The repository contains a Magento 2 module for generating a Google Shopping feed in XML format. It can be installed as follows:

```
cd app/code
# Clone the module
git clone https://github.com/inatic/magento2-googleshoppingfeed Inatic/GoogleShoppingFeed
# Upgrade the magento installation
$ bin/magento setup:upgrade
# Clean cache and generated code
$ bin/magento cache:clean
```

For this module to function (as it is configured here), you will need to create a few custom attributes. The first of following attributes is used in `Helper/Products.php` so only the products having `add_to_googleshopping_feed` set to **Yes** are added to the feed.

| Attribute                     | Type
|-------------------------------|----------------
| add_to_googleshopping_feed    | Yes/No
| brand (*merk* in our case)    | Text
| gtin                          | Text
| mpn                           | Text
| condition                     | Text
| color (*kleur* in our case)   | Text
| google_product_category       | Text

You can use other names than those specified above (as we use `kleur` and `merk`), just make sure you set the correct names of custom attributes in the `Model/XmlFeed.php` file.

```
$xml .= $this->createNode("brand", $product->getAttributeText('merk'));
$xml .= $this->createNode("gtin", $product->getData("ean"));
$xml .= $this->createNode("mpn", $product->getData("mpn"));
$xml .= $this->createNode("condition", "new");
$xml .= $this->createNode("color", ucfirst($product->getAttributeText('kleur')));
$xml .= $this->createNode("google_product_category",$product->getAttributeText("google_product_category"));
```

Configuration options for the module can be accessed from the admin panel at **Stores | Configuration | Marketing | Feeds**. To access the feed itself, go to : www.website.com/inaticgoogleshoppingfeed/

# Example XML file

For information on the type of content that is expected in a Google Shopping feed, you can download an example XML file by going to [this Google Merchant Center help page](https://support.google.com/merchants/answer/160589) and clicking the **Download RSS 2.0 example** button. A trimmed-down version of the content of this example can be found on the same page and looks somewhat like the following:

```googleshoppingfeedexample.xml
<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>Example - Google Store</title>
<link>https://store.google.com</link>
<description>This is an example of a basic RSS 2.0 document containing a single item</description>
<item>
<g:id>TV_123456</g:id>
<g:title>Google Chromecast with Google TV</g:title>
<g:description>Chromecast with Google TV brings you the entertainment you love, in up to 4K HDR</g:description>
<g:link>https://store.google.com/product/chromecast_google_tv</g:link> <g:image_link>https://images.example.com/TV_123456.png</g:image_link>
<g:condition>new</g:condition>
<g:availability>in stock</g:availability>
<g:price>49.99 USD</g:price>
<g:shipping>

<g:country>US</g:country>
<g:service>Standard</g:service>
<g:price>7.99 USD</g:price>

</g:shipping>
<g:gtin>123456789123</g:gtin>
<g:brand>Google</g:brand>

</item>
</channel>
</rss>
```

# Product attributes

An overview of required and optional attributes for a Google Shopping feed can be found [here](https://support.google.com/merchants/answer/9199328) and [here](https://support.google.com/merchants/answer/7052112) on Google's developer website. A default Magento 2 installation provides some attributes for use in a Google Shopping feed (`id`, `title`, `description`...) out of the box, while other attributes can be created in the website's admin panel by going to **Stores | Attributes | Product**.

## Required attributes

Going by [this Google Merchant Center article](https://support.google.com/merchants/answer/7052112), each product should at minimum provide the attributes in below table. Most of them have a corresponding attribute in a default Magento installation, but some (like `condition` and `brand`) need to be added manually. You can edit the `Model/XmlFeed.php` file to use attribute values already present in your Magento installation - we for example set `condition` to `new` for all products and already have a `brand` attribute, though it was named differently. 

| Google Shopping   | Magento           | Comment
|-------------------|-------------------|----------------    
| id                | id                |
| title             | name              |
| description       | description       |
| link              | product_url       |
| image_link        | image             |
| availability      | isSaleable()      | 
| price             | regular_price     |
| brand             | brand		        | create
| gtin              | gtin              | create
| mpn               | mpn               | create, for all products without gtin
| condition         | new               | create, required for used or refurbished products

## Shipping costs

The feed specification provides and attribute (`shipping`) for setting the shipping costs on each individual product. This however can also be configured at the level of your Google Merchant account, and this probably makes more sense than repeating similar information for all products in the feed. The `shipping` attribute therefore is not included in the feed generated by this module.

## Optional attributes

A range of optional attributes can furthermore be added to a product. Details of these attributes can be found in [the same Google Merchant Center article](https://support.google.com/merchants/answer/7052112). Again, some corresponding Magento attributes might need to be added from the website's admin panel. The following is just a short selection of available optional attributes, a full list can be found on Google's support website.

| Google Shopping                   | Magento                   | Comment
|-----------------------------------|---------------------------|-------------------
| additional_image_link             |							|
| sale_price                        | final_price               |
| google_product_category           | google_product_category   | create
| product_type                      | category					| 
| color                             | color                     | create
| material                          | material                  | create
| size                              | size                      | create
| custom_label_0                    | categories                |
| custom_label_[1-4]                |							|
| custom_number_[0-4]               |							|
| rich_text_description             |							|
| video                             |							|

## Add to Facebook feed

The `add_to_googleshopping_feed` attribute mentioned above is used by the website administrator to specify which products need to be added to the feed. This is an attribute you add by going to **Stores | Attributes | Product** and has `Yes/No` as its possible values. This attribute is used in `Helper/Products.php` to filter the product collection from which the feed is generated in `Model/XmlFeed.php`.

```Helper/Products.php
public function getFilteredProducts()
{
    $collection = $this->productCollectionFactory->create();
    $collection->addAttributeToSelect('*');
    $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
    $collection->addAttributeToFilter('visibility', ['eq' => Visibility::VISIBILITY_BOTH]);
    $collection->addAttributeToFilter('add_to_googleshopping_feed', true);
    $collection->addStoreFilter(1);
    $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());

    return $collection;
}
```

# How the module works

The following is a short description of how this module works. The module only contains a few files, and part are just there to add it to the Magento installation and are common to all modules, being:

| File                  | Use
|-----------------------|----------------------------------------------
| registration.php		| responsible for registering the namespace of the module with the system
| etc/module.xml		| contains the name and possible other details on the module
| composer.json			| provides information on the module as well as its requirements and dependencies, for a `composer` installation

## Route to this module

The module generates an XML file containing information on the products you would like to synchronize with your Google Merchants account. When Google requests the XML feed from your website, Magento's frontend needs to to know this particular request needs to be routed to this module. By default the URL of the request for the XML feed is `yoursite.com/inaticgoogleshoppingfeed`, though you could easily change this into something else. Directing the request on the frontend to a particular module is done in `etc/frontend/routes.xml`, and it is the `frontName` in below configuration that determines the `path` of the URL.

```etc/frontend/routes.xml
<router id="standard">
    <route id="inaticgoogleshoppingfeed" frontName="inaticgoogleshoppingfeed">
        <module name="Inatic_GoogleShoppingFeed"/>
    </route>
</router>
```

## Process the request

Once the request arrives at the module, a controller at `Controller/Index/Index.php` takes care of further processing. The controller prepares a response, sets a header to specify that the content being returned is XML data, and gets that XML content from the `xmlFeed` object in `Model/XmlFeed.php`. As you can see, this piece of code checks if the configuration of the module has set the feed to `enabled`. The configuration in question can be found at **Stores | Configuration | Marketing | Feeds**.

```Controller/Index/Index.php
if (!empty($this->helper->getConfig('enabled'))) {
    $resultRaw->setHeader('Content-Type', 'text/xml');
    $resultRaw->setContents($this->xmlFeed->getFeedFile());
    return $resultRaw;
}
```

## Fetch product data

A *helper* object creates a collection of the products that are added to the feed. It filters them based on the store they belong to, their status (enabled or not) and visibility, and the value of their `add_to_googleshopping_feed` attribute (which was created above). The `store` table in the database can tell you the `store_id` for each of the stores in your installation. Although this is not done here, a different XML file could easily be generated for each store by filtering on `store_id`. The class used to generate product collections can be found under `vendor/magento/module-catalog/Model/ResourceModel/Product`.

```Helper/Products.php
public function getFilteredProducts()
{
    $collection = $this->productCollectionFactory->create();
    $collection->addAttributeToSelect('*');
    $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
    $collection->addAttributeToFilter('visibility', ['eq' => Visibility::VISIBILITY_BOTH]);
    $collection->addAttributeToFilter('add_to_googleshopping_feed', true);
    $collection->addStoreFilter(1);
    $collection->setVisibility($this->productVisibility->getVisibleInSiteIds());

    return $collection;
}
```

## Creating XML data

Each XML text file starts with a header and ends with a footer. Data for each of the products in the feed is placed between header and footer in `<item></item>` tags.

```
<?xml version="1.0"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
<channel>
<title>Product Feed</title>
<link>https://mystore.com/</link>
<description>Product Feed for Facebook</description>

<item>...</item>
<item>...</item>

</channel>
</rss>
```

### Loop over product collection

The code then goes through each product in the collection created in `Helper/Products.php`, skipping over products that have no image or are not visible. Other criteria for filtering products at this stage can easily be added to the `isValidProduct()` function.

```
foreach ($productCollection as $product) {
    if ($this->isValidProduct($product)) {
        $xml .= "<item>".$this->buildProductXml($product)."</item>";
    }
}
```

### Product data
The `buildProductXml` function takes care of fetching the relevant feed data for each of the products and formats this data according to Google requirements. All product data is accessible from the `$product` object, either by a convenience method like `getName()` or by use of generic methods like `getData() or `getAttributeText()`.

```
$product->getName();
$product->getProductUrl();
$product->getDescription();
$product->getImage();
$product->getCondition();
$product->getData('ean');
$product->getAttributeText('google_product_category');
```

## Cron

A cron job takes care of generating the Google Shopping feed on a daily basis. This is configured in `/etc/crontab.xml` and the file in this case is set to be generated every day at 40 minutes past midnight. As you can see, the object being instantiated is `Inatic\FeedFacebook\Cron\GenerateFeed` and the `execute` method is called on the object. Latter method executes `xmlFeed->getFeed()` to get the XML feed data and saves it to the `pub` directory in the Magento installation.

```
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Cron:etc/crontab.xsd">
    <group id="default">
        <job name="inatic_googleshipping_xml" instance="Inatic\GoogleShippingFeed\Cron\GenerateFeed" method="execute">
            <schedule>30 0 * * *</schedule>
        </job>
    </group>
</config>
```

The time for a cron job to run is set as follows:

```
*    *    *    *    *  command to be executed
┬    ┬    ┬    ┬    ┬
│    │    │    │    │
│    │    │    │    │
│    │    │    │    └───── day of week (0 - 7) (0 or 7 are Sunday, or use names)
│    │    │    └────────── month (1 - 12)
│    │    └─────────────── day of month (1 - 31)
│    └──────────────────── hour (0 - 23)
└───────────────────────── min (0 - 59)
```


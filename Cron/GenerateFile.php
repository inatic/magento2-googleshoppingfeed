<?php
namespace Inatic\GoogleShoppingFeed\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Inatic\GoogleShoppingFeed\Model\XmlFeed;

class GenerateFile
{
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        Filesystem $filesystem,
        XmlFeed $xmlFeed
    ) {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->xmlFeed = $xmlFeed;
    }

   /**
    * Generate XML feed on cron schedule
    *
    * @return void
    */
    public function execute(): void
    {
        $fileDirectoryPath = $this->filesystem->getDirectoryWrite(DirectoryList::PUB);
        $fileName = 'googleshoppingfeed.xml';
        try {
            $xmldata = $this->xmlFeed->getFeed();
            if (strlen($xmldata) > 500) {
                $fileDirectoryPath->writeFile($fileName, $xmldata);
            } else {
                $this->logger->error('Google Shopping Feed XML Data not generated correctly');
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}

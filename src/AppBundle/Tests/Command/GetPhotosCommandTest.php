<?php

use AppBundle\Command\GetPhotosCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class GetPhotosCommandTest extends KernelTestCase
{

    private $path_to_appbundle;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->path_to_appbundle = static::$kernel->getContainer()
                                                ->get('kernel')
                                                ->locateResource('@AppBundle/')
        ;
    }

    public function testExecute()
    {
        $application = new Application();
        $application->add(new GetPhotosCommand());

        $command = $application->find('get:photos');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            'productUrl'         => 'stuff.com',
        ));

        // $this->assertRegExp('/scraping photos from/', $commandTester->getDisplay());
        $this->assertStringStartsWith('scraping photos from stuff.com', $commandTester->getDisplay());

        // ...
    }

    public function testSetFeedbackUrl()
    {
        //FEEDBACK URL = http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=
        //PRODUCT URL = http://www.aliexpress.com/item//32251240493.html  
        //CACHED PRODUCT URL = path/to/file/ProductUrl-32251240493.html
        $getphotoscommand = new GetPhotosCommand();

        
        $productUrl  = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html';
        $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';
        $message = 'The scraper did not properly extract the URL for the feedback page from the cached copy of a HTML page http://www.aliexpress.com/item//32251240493.html';

        $this->assertEquals($feedbackUrl, $getphotoscommand->setFeedbackUrl($productUrl), $message);
        // $this->assertEquals('FEEDBACK URL', $getphotoscommand->setFeedbackUrl('CACHED PRODUCT URL'));
    }
}
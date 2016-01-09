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
            'productUrl'         => $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html',
        ));

        $prefix = 'scraping photos from /Users/ruthfombrun/galirie/src/AppBundle/Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html';
        // $message = 'Command description doesn\'t contain the right product URL from the CLI input arg';
        
        $this->assertStringStartsWith($prefix, $commandTester->getDisplay());

    }

    public function testGetFeedbackUrl()
    {
        $getphotoscommand = new GetPhotosCommand();

        $productUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html';
        $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';
        // $message = 'The scraper did not properly extract the URL for the feedback page from the cached copy of a HTML page';

        $this->assertEquals($feedbackUrl, $getphotoscommand->getFeedbackUrl($productUrl));
        
    }

    public function testgetNumReviewPages()
    {
        $getphotoscommand = new GetPhotosCommand();

        $feedbackUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/FeedbackUrl&page=1';
        $numReviewPages = 141;
        // $message = 'The scraper did not properly extract the URL for the feedback page from the cached copy of a HTML page http://www.aliexpress.com/item//32251240493.html';

        $this->assertEquals($numReviewPages, $getphotoscommand->getNumReviewPages($feedbackUrl));
        
    }

    public function testgetImgUrls()
    {
        $getphotoscommand = new GetPhotosCommand();
        $feedbackUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/FeedbackUrl';
        $numReviewPages = 141;
        // $message = 'The scraper did not properly copy the URLs for all 5 images on the 1st 3 pages of reviews';
        
        $userImgUrls = array(
                        'http://g01.a.alicdn.com/kf/UT8y9GmXBNXXXagOFbX2.jpg',
                        'http://g04.a.alicdn.com/kf/UT8XW9nXydXXXagOFbX1.jpg',
                        'http://g02.a.alicdn.com/kf/UT8dtadXBXaXXagOFbXE.jpg',
                        'http://g04.a.alicdn.com/kf/UT8gvafXp4XXXagOFbXj.jpg',
                        'http://g04.a.alicdn.com/kf/UT8k1ydXy0aXXagOFbXL.jpg',
                    );
        
        $this->assertEquals($userImgUrls, $getphotoscommand->getImgUrls($feedbackUrl, $numReviewPages));
    }

}
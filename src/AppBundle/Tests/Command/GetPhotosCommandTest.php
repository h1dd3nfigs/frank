<?php

use AppBundle\Command\GetPhotosCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class GetPhotosCommandTest extends KernelTestCase
{

    private $path_to_appbundle;
    private $use_cached_urls = true; // run test using live aliexpress URLs or cached URLs


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
            'aliProductUrl' => '32322377471',
        ));

        $prefix = 'the aliProductUrl is ';
        // $prefix = 'start scraping photos from /Users/ruthfombrun/galirie/src/AppBundle/Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html';
        // $message = 'Command description doesn\'t contain the right product URL from the CLI input arg';
        
        $this->assertStringStartsWith($prefix, $commandTester->getDisplay());
        // SHOULD THIS BE MORE OF A FUNCTIONAL TEST BECAUSE I'M UNSURE OF HOW TO ASSERT THAT ALL OF THIS HAPPENED
        /*
        * 1-Query Product table and return productUrl for all products missing photos that haven't been checked in a week
        * 2-For each product, call other methods here to end up with multi-level array of photos
        * 3-Get CDN's url for each photo 
        * 4-Insert url into the aws_img_url column of the Photo table
        */
    }

    public function testGetFeedbackUrl()
    {
        $getphotoscommand = new GetPhotosCommand();

        if($this->use_cached_urls){

            $productUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/ProductUrl-32251240493.html';

        } else {

            $productUrl ='http://www.aliexpress.com/item/Navy-Lace-Satin-Patchwork-Party-Maxi-Dress-LC6809/32251240493.html';
        }

        $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';
        $message = 'The scraper did not properly extract the URL for the feedback page from the product page';
        $this->assertEquals($feedbackUrl, $getphotoscommand->getFeedbackUrl($productUrl, $this->use_cached_urls));
        
    }

    public function testgetNumReviewPages()
    {
        $getphotoscommand = new GetPhotosCommand();

        if($this->use_cached_urls){
    
            $feedbackUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/FeedbackUrl&page=1';

        } else {

            $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';

        }

        $numReviewPages = 141;
        $message = 'The scraper did not properly extract the total num of review pages from the the feedback page';

        $this->assertEquals($numReviewPages, $getphotoscommand->getNumReviewPages($feedbackUrl,$this->use_cached_urls));
        
    }

    // public function testgetImgUrls()
    // {
    //     $getphotoscommand = new GetPhotosCommand();
    //     $feedbackUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/FeedbackUrl';
    //     $numReviewPages = 141;
    //     // $message = 'The scraper did not properly copy the URLs for all 5 images on the 1st 3 pages of reviews';
        
    //     $userImgUrls = array(
    //                     'http://g01.a.alicdn.com/kf/UT8y9GmXBNXXXagOFbX2.jpg',
    //                     'http://g04.a.alicdn.com/kf/UT8XW9nXydXXXagOFbX1.jpg',
    //                     'http://g02.a.alicdn.com/kf/UT8dtadXBXaXXagOFbXE.jpg',
    //                     'http://g04.a.alicdn.com/kf/UT8gvafXp4XXXagOFbXj.jpg',
    //                     'http://g04.a.alicdn.com/kf/UT8k1ydXy0aXXagOFbXL.jpg',
    //                 );
        
    //     $this->assertEquals($userImgUrls, $getphotoscommand->getImgUrls($feedbackUrl, $numReviewPages));
    // }

    // public function testgetAliProductIdFromFeedbackUrl()
    // {
    //     $getphotoscommand = new GetPhotosCommand();
    //     $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';
    //     $aliProductId = '32251240493';
    //     $message = 'Didn\'t properly extract the aliProductId from the feedback url';

    //     $this->assertEquals($aliProductId, $getphotoscommand->getProductIdFromUrl($feedbackUrl));


    // }

    // public function testgetNumReviews()
    // {
    //     $getphotoscommand = new GetPhotosCommand();
    //     // $feedbackUrl = $this->path_to_appbundle.'Command/Cached-Urls-for-Testing-Commands/FeedbackUrl&page=1';
    //     $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';
    //     $numReviews = 1403;
    //     // $message = 'Didn\'t properly extract the aliProductId from the feedback url';

    //     $this->assertEquals($numReviews, $getphotoscommand->getNumReviews($feedbackUrl));


    // }

}
<?php 
namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
// use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Product;
use AppBundle\Entity\Photo;
use Aws\S3\S3Client; 

include('aws-settings.php');

class GetPhotosCommand extends DoctrineCommand
{

    protected function configure()
    {
        $this
            ->setName('get:photos')
            ->setDescription('Scrape photos of users wearing products bought from aliexpress.com')
            ->addArgument(
                'aliProductUrl',
                InputArgument::OPTIONAL,
                'Provide the url for the product you\'d like see user photos of.'
            )
        ;
        

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // blue lace dress
        // $productUrl ='http://www.aliexpress.com/item/Navy-Lace-Satin-Patchwork-Party-Maxi-Dress-LC6809/32251240493.html';
        // $feedbackUrl = 'http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32251240493&ownerMemberId=200009299&companyId=200001989&memberType=seller&startValidDate=';

        // skinny white pants
        $productUrl = 'http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html';
        // $feedbackUrl = 'feedback.aliexpress.com/display/productEvaluation.htm?productId=32322377471&ownerMemberId=220754190&companyId=230737239&memberType=seller&startValidDate=';
        $feedbackUrl = $this->getFeedbackUrl($productUrl, false);
        $htmlStringFeedbackUrlPics = $this->getFeedbackUrlWithOnlyPics($feedbackUrl);

        echo "\n\nnum review pages with pics only: ";
        echo $numReviewPages = $this->getNumReviewPages($htmlStringFeedbackUrlPics);
        
        echo "\n\nnum reviews with pics: ";
        echo $numReviewsWithPics = $this->getNumReviews($htmlStringFeedbackUrlPics);
        
        echo "\n\ndate of the latest review: ";
        echo $this->getDateOfLatestReviewOnAli($htmlStringFeedbackUrlPics);

        $userImgs = $this->getImgUrls($feedbackUrl, $numReviewPages);        
        echo "\n\n the 1st 3 pages of img urls contain: ".count($userImgs);

        $this->downloadAllUserImgs($userImgs, $feedbackUrl);

        echo "\n\nwhere are you\n\n";
        return;

        $aliProductUrl = $input->getArgument('aliProductUrl');
        $output->writeln("the aliProductUrl is $aliProductUrl \n\n");

        $kernel = $this->getContainer()->get('kernel');
        $path = $kernel->locateResource('@AppBundle/Command/');

        $feedbackUrl = $path.'/Cached-Urls-for-Testing-Commands/FeedbackUrl';

        $this->getNumReviewPages($feedbackUrl, true);



        // $feedbackUrl    = $this->getFeedbackUrl($productUrl);
        // $output->writeln("the ali feedback url is $feedbackUrl \n\n");

// $numReviewPages = $this->getNumReviewPages('http://feedback.aliexpress.com/display/productEvaluation.htm?productId=32322377471&ownerMemberId=220754190&companyId=230737239&memberType=seller&startValidDate=');
// echo $numReviewPages;
        // $product = $this->getContainer()
        //                     ->get('doctrine')
        //                     ->getRepository('AppBundle:Product')
        //                     ->findOneBy(
        //                         array('ali_product_id'=> $aliProductId),
        //                         array('id'=>'DESC')
        //                     )
        //                 ;
        // $dateOfLatestReviewOnAli = $this->getDateOfLatestReviewOnAli();  
        
        // if ($product){
        //     // still need to crawl to get the $dateOfLatestReviewOnAli param for needsCrawl()
        //     if(!$product->needsCrawl(strtotime('01 Jan 2016 19:19'))){

        //         $output->writeln("No need to crawl aliexpress for product $ali_product_id because no new reviews were added since last crawl on".date('d M Y H:i', $product->getDateLastCrawled())."\n\n");

        //         return ;
        //     }
        // }

        // $output->writeln('start scraping photos for ali product id'.$aliProductId."\n\n");

        // $numReviewPages = $this->getNumReviewPages($feedbackUrl);
        // $userImgs       = $this->getImgUrls($feedbackUrl, $numReviewPages);
        // $this->downloadAllUserImgs($userImgs);
        
        return;
    }

    /*
    * Get product URL from user input then use it to get url for feedback <iframe>
    */
     
    public function getFeedbackUrl($productUrl, $use_cached_urls = true)
    {
        $dom_product = new \DOMDocument();

        if($use_cached_urls){
           
            @$dom_product->loadHTMLFile($productUrl);

        } else {
    
            $html_string = $this->getWebpage($productUrl);
            @$dom_product->loadHTML($html_string);

        }
 
        $domxpath = new \DOMXPath($dom_product);
        $tagName = 'div';
        $attrName = 'id';

        $feedback_div_nodelist = $domxpath->query("//$tagName" . '[@' . $attrName . "='feedback']/iframe");

        foreach ($feedback_div_nodelist as $n) {
             $feedbackUrls[] = $n->getAttribute( 'thesrc' );
        }

        if($feedbackUrls)
            return $feedbackUrl = $feedbackUrls[0] ;

        return null;
    }

    /*
    * Check the "With Pictures" box to only display reviews with photos attached 
    * Sorted by date DESC
    * From feedback_url page, 
    * Submit the <form id="l-refresh-form" action={feedbackUrl}> with the input below set to true instead of false the way iframe initially loads
    *  <input type="hidden" id="withPictures" name="withPictures" value="true"/>
    */
    public function getFeedbackUrlWithOnlyPics($feedbackUrl){

        $fields = array(
            'evaSortValue' => 'sortlarest@feedback',
            'withPictures' => 'true',
            
         );

        return $html_string = $this->getWebpage($feedbackUrl, $fields);
    }

    
    /*
    * Get total num of pages of feedback that have pics
    * v1: from <iframe> feedback_url, grab value in <div class="pos-right">, within 2nd <a> tag
    * v2: from feedback_url with pics, grab value in <div class="ui-pagination ..."><label class="ui-label">1/{numReviewPages} 
    */

    public function getNumReviewPages($html_string)
    {
        $dom_feedback = new \DOMDocument();    
        @$dom_feedback->loadHTML($html_string);

        $domxpath = new \DOMXPath($dom_feedback);
        $review_page_nodelist = $domxpath->query("//div[contains(concat(' ',@class,' '),' ui-pagination ')]/label[@class='ui-label']");

        foreach ($review_page_nodelist as $n) {
            $text = $n->nodeValue;
            $denom_pos = strpos($text, '/');
            $numReviewPages = intval(substr($text, $denom_pos + 1));
        }

        return $numReviewPages;
    }
 
    /*
    * Get total number of reviews uploaded that have pics
    * Example: from <div class="f-filter-list"><em>{numReviews}</em> 
    */
    public function getNumReviews($html_string)
    {
        $dom_feedback = new \DOMDocument();
        @$dom_feedback->loadHTML($html_string);
        $domxpath = new \DOMXPath($dom_feedback);

        $tagName = 'div';
        $attrName = 'class';
        $review_page_nodelist = $domxpath->query("//div[@class='f-filter-list']/label[1]");
        
        foreach ($review_page_nodelist as $n) {
            $text = $n->nodeValue;
        }

        $start_quote_pos = strpos($text, '(');
        $end_quote_pos = strpos($text, ')');

        $numReviews = intval(substr($text, $start_quote_pos + 1, $end_quote_pos - $start_quote_pos -1 ));

        return $numReviews;
    }

    /*
    * Get upload date of the most recent review
    * by grabbing the 1st <dd class="r-time">
    */
    public function getDateOfLatestReviewOnAli($html_string)
    {
        $dom_feedback = new \DOMDocument();
        @$dom_feedback->loadHTML($html_string);
        $domxpath = new \DOMXPath($dom_feedback);

        $results = $domxpath->query("//dd[@class='r-time']");

        foreach ($results as $result) {
           $dateOfLatestReview = $result->nodeValue;
            break;
        }


        return $dateOfLatestReview; //strtotime($dateOfLatestReview);
    }


    /*
    * Loop through get_img_urls for each page of feedback
    * Grab any user-uploaded pics from <li class="pic-view-item" data-src="{imgUrl}">
    */

    public function getImgUrls($feedbackUrl, $numReviewPages)
    {        
        // for ($i=1; $i <= $numReviewPages ; $i++) { 
        for ($i=1; $i <= 3 ; $i++) { 
             $fields = array(
                        'evaSortValue' => 'sortlarest@feedback',
                        'withPictures' => 'true',
                        'page'         => $i,
                        
                     );

            $html_string = $this->getWebpage($feedbackUrl, $fields);

            $domname = 'dom_feedback'.$i;

            $$domname = new \DOMDocument();
            @$$domname->loadHTML($html_string);

            $domxpathname = 'domxpath'.$i;
            $$domxpathname = new \DOMXPath($$domname);
            $results = $$domxpathname->query("//li[@class='pic-view-item']");

            foreach ($results as $node) {
                $userImg = $node->getAttribute( 'data-src' );
                $userImgs[] = $userImg;
            }

            /*
            * UNCOMMENT DELAY BEFORE RUNNING SCRAPER AGAINST LIVE SITE FOR MULTIPLE PRODUCTS
            */
            // $delay = 4; //in seconds, to rate limit my requests
            // echo "Delaying for $delay seconds between each feedback page's request\n\n";
            // sleep($delay);

        }

        return $userImgs ;
    }

    public function getAliProductIdFromFeedbackUrl($feedbackUrl)
    {
        parse_str( parse_url( $feedbackUrl, PHP_URL_QUERY));

        return $productId;

    }

    public function createAliProductUrl($aliProductId)
    {
        $aliProductUrl = 'http://www.aliexpress.com/item//'.$aliProductId.'.html';

        return $aliProductUrl;

    }


    public function downloadAllUserImgs($userImgs, $feedbackUrl)
    {

        $ali_product_id = $this->getAliProductIdFromFeedbackUrl($feedbackUrl);

        /*
        * Use entity manager to locate the row in Product table associated with these user photos
        */ 
        try {
            
            $product = $this->getContainer()
                            ->get('doctrine')
                            ->getRepository('AppBundle:Product')
                            ->findOneBy(
                                array('ali_product_id'=> $ali_product_id),
                                array('id'=>'DESC')
                            )
                        ;
            
            if (!$product) 
                throw new Exception('No product found in db for ali_product_id '.$ali_product_id);
                
            $kernel = $this->getContainer()->get('kernel');
            $path = $kernel->locateResource('@AppBundle/Command/');

            $s3Client = new S3Client(array(
                'credentials' => array(
                    'key'    => AWS_ACCESS_KEY_ID,
                    'secret' => AWS_SECRET_ACCESS_KEY,
                ),
                'region'  => 'us-west-2',
                'version' => '2006-03-01',
                'http'    => [
                    'verify' => $path.CA_BUNDLE_FILENAME,
                ]
            ));

            $context = stream_context_create(array(
                's3' => array(
                    'ACL' => 'public-read'
                )
            ));
           
            // Register the s3 stream wrapper from an S3Client object
            $s3Client->registerStreamWrapper();
            
            $bucket = 'test-galirie';
            
            $key_prefix = $ali_product_id; 
            
            foreach ($userImgs as $i => $aliImgUrl) { 
          
                /*
                * Download the image into s3 bucket
                */

                $key = "$ali_product_id-img$i.jpg";
                // $key = "$this->ali_product_id-img0.jpg";

                $data = array(
                            'body'=>file_get_contents($aliImgUrl),
                            'contentType'=>'image/jpeg', // always jpg?
                           );
                
                // Only upload to the s3 bucket if the object isn't already there

                if(!file_exists('s3://'.$bucket.'/'.$key_prefix.'/'.$key)){

                    $result = file_put_contents('s3://'.$bucket.'/'.$key_prefix.'/'.$key, 
                                $data, 0 , $context);
                    
                    if($result){
                        echo "Done downloading : $key into s3 bucket: $bucket directory \n\n";
                    } else {
                        echo "An error occurred while trying to upload $key into  s3 bucket $bucket\n\n";  
                        continue;
                    }

                } else { 
                    echo "The photo $key is already in the s3 bucket $bucket.\n\n";
                }


                
                $aws_img_url = $s3Client->getObjectUrl($bucket, $key_prefix.'/'.$key);

                /*
                * Insert the aws_img_url along with other photo info into Photo table
                */

                //IF THE PHOTO'S ALREADY IN THE TABLE (that ali_img_url is already there), then don't replace it, move onto next img
                $photo = new Photo();
                
                $photo
                        ->setProduct($product)
                        ->setRating(0)
                        ->setAliHelpfulCount(0)
                        ->setAwsImgUrl($aws_img_url)
                        ->setAliImgUrl($aliImgUrl)
                        ->setAliUploadDate(0)
                ;

                $em = $this->getEntityManager('default');
                $em->persist($photo);
                $em->flush();
                // break;
            }

        } catch (Exception $e) {
          
            echo 'Caught exception: ',  $e->getMessage(), "\n";

        }

        

        return;
    }   

    function getWebpage($url, $fields = null)
    {
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HEADER, true);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Frank/0.1 http://frank.elxr.it/bot.html');

        // send HTTP POST with parameters
        if($fields){
            $fields_string = '';

            //url-ify the data for the POST
            foreach($fields as $key=>$value) { 
                $fields_string .= $key.'='.$value.'&'; 
            }
            $fields_string = rtrim($fields_string, '&');

            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        }


        ob_start();

        curl_exec ($ch);
        curl_close ($ch);
        $string = ob_get_contents();

        ob_end_clean();
        
        return $string;     
    }
    


}
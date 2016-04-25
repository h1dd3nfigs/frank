<?php 
namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Product;
use AppBundle\Entity\Photo;
use Aws\S3\S3Client; 

include('aws-settings.php');

// ???????????????
// IF THIS COMMAND IS CALLED BY get:products command, either
// 1) RETURN data that should be inserted into db for this product, like numReviews (with pics only), date of latest review, date last crawled, etc
//  2) FROM WITHIN THIS COMMAND, insert aforementioned data into the db's product table
// ???????????????

class GetPhotosCommand extends DoctrineCommand
{
    /*
    * The product for which we're seeking user-uploaded photos
    */ 
    // protected $product;

    protected function configure()
    {
        $this
            ->setName('get:photos')
            ->setDescription('For the given AliExpress product URL, scrape photos of users wearing the item')
            ->addArgument(
                'aliProductUrl',
                InputArgument::REQUIRED,
                'Provide the url for the product whose user photos you\'d like to see.'
            )
            ->addArgument(
                'aliProductId',
                InputArgument::REQUIRED,
                'Provide the aliproductId for the product whose user photos you\'d like to see.'
            )
        ;
        

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $aliProductUrl = $input->getArgument('aliProductUrl');
        $aliProductId = $input->getArgument('aliProductId');        
        $needsScrape = true;

        // Does this ali_product_id exist in our Product table? 
        $product = $this->getContainer()
                            ->get('doctrine')
                            ->getRepository('AppBundle:Product')
                            ->findOneBy(
                                array('ali_product_id'=> $aliProductId)
                            );

        // If yes, then compare what our db thinks is the latest review against the most recent review uploaded to the ali site
        if ($product){
            $needsScrape = $product->needsScrape($dateOfLatestReviewOnAli)? true : false;

        //IF THE PRODUCT DOESN'T EXIST IN DB, CAN'T INSERT SCRAPED PHOTOS INTO DB B/C OF RELATIONSHIP OF PHOTO TO PRODUCT TABLE
        } else {
            throw new \InvalidArgumentException('Will not scrape because no product was found in db for ali_product_id '.$aliProductId);

        }

        if(!$needsScrape){
            $output->writeln("No new reviews with pics to scrape from aliexpress for product $aliProductId because no new reviews were added since last crawl on".date('d M Y H:i', $product->getDateLastCrawled())."\n\n");
            return;
        }

        // Proceed with scraping

        $feedbackUrl = $this->getFeedbackUrl($aliProductUrl);     

        $feedbackHtmlString = $this->getFeedbackHtmlString($feedbackUrl);

        echo "\n\nnum review pages with pics only: ";
        echo $numReviewPages = $this->getNumReviewPages($feedbackHtmlString);

        echo "\n\nnum reviews with pics: ";
        echo $numReviewsWithPics = $this->getNumReviews($feedbackHtmlString);
        
        echo "\n\ndate of the latest review: ";
        echo $dateOfLatestReviewOnAli = $this->getDateOfLatestReviewOnAli($feedbackHtmlString);

        
        $feedbackData = $this->getFeedbackData($feedbackUrl);//, $numReviewPages);
        
        // Is it better to pass $feedbackData array by reference instead of re-assigning array to the var???
        $feedbackData = $this->getAliHelpfulCounts($aliProductId, $feedbackData);
        
        // Download all userImgs into S3 buckets
        $output->writeln('start scraping photos for ali product id'.$aliProductId."\n\n");
        $this->downloadAllUserImgs($feedbackData, $product);  

        return;
    }

    /*
    * Get product URL from user input then use it to get url for feedback <iframe>
    */
     
    public function getFeedbackUrl($productUrl)
    {
        $dom_product = new \DOMDocument();
        $html_string = $this->getWebpage($productUrl);
        @$dom_product->loadHTML($html_string);
        $domxpath = new \DOMXPath($dom_product);
        $feedback_div_nodelist = $domxpath->query("//div[@id='feedback']/iframe");

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
    public function getFeedbackHtmlString($feedbackUrl){

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


        return strtotime($dateOfLatestReview);
    }

     /*
    * Get all the feedback_ids, upload_dates, and urls for user images on each page of reviews
    * 
    */

    public function getFeedbackData($feedbackUrl, $numReviewPages = 3)
    {
        $feedbackData = array();

        // Iterate through each page of reviews
        for ($i=1; $i <= $numReviewPages ; $i++) { 
             $fields = array(
                        'evaSortValue' => 'sortlarest@feedback',
                        'withPictures' => 'true',
                        'page'         => $i,
                        
                     );

            // Load the html doc for the i-th page of reviews into a string
            $html_string = $this->getWebpage($feedbackUrl, $fields);

            $domname = 'dom_feedback'.$i;

            $$domname = new \DOMDocument();
            @$$domname->loadHTML($html_string);

            $domxpathname = 'domxpath'.$i;
            $$domxpathname = new \DOMXPath($$domname);

            // Locate the parent div for each review on this pege
            $review_page_nodelist = $$domxpathname->query("//div[@class='fb-main']");

            // Iterate through each review's parent div to get the feedback_id, upload_date and imgs
            foreach ($review_page_nodelist as $n) {
                
                // Get the feedback_id, unique to each review
                $input = $$domxpathname->query($n->getNodePath()."/div[@class='f-rate-info']/input[@class='feedback-id']");            
                foreach ($input as $node) {
                    $ali_feedback_id = $node->getAttribute( 'value' );
                }

                // Get the date this review was posted
                $date = $$domxpathname->query($n->getNodePath()."/div[@class='f-content']/dl[@class='buyer-review']/dd[@class='r-time']");
                foreach ($date as $node) {
                    $ali_upload_date = $node->nodeValue;
                }

                // Get the user-uploaded images
                $userImgs = array();
                $imgs = $$domxpathname->query($n->getNodePath()."/div[@class='f-content']/dl[@class='buyer-review']/dd[@class='r-photo-list']/ul[@class='util-clearfix']/li[@class='pic-view-item']");
                foreach ($imgs as $node) {
                    $userImg = $node->getAttribute( 'data-src' );
                    $userImgs[] = $userImg;
                }
                
                // Load the elements of each review into this output array
                $feedbackData[$ali_feedback_id] = array(
                                                    'ali_upload_date'   => strtotime($ali_upload_date),
                                                    'ali_helpful_count' => 0,
                                                    'userImgs'          => $userImgs,
                                                );
            }
        }
            /*
            * UNCOMMENT DELAY BEFORE RUNNING SCRAPER AGAINST LIVE SITE FOR MULTIPLE PRODUCTS
            */
            $delay = 5; //in seconds, to rate limit my requests
            echo "Delaying for $delay seconds between each feedback page's request\n\n";
            sleep($delay);

        return $feedbackData;
    }


    // public function getAliProductIdFromFeedbackUrl($feedbackUrl)
    // {
    //     parse_str( parse_url( $feedbackUrl, PHP_URL_QUERY));

    //     return $productId;

    // }


    public function getAliHelpfulCounts($aliProductId, $feedbackData)
    {
        // Ajax Service can only take 10 feedback_ids at a time
        $ali_feedback_ids = array_chunk(array_keys($feedbackData), 10);

        foreach ($ali_feedback_ids as $ids) {

            $url = 'http://feedback.aliexpress.com/display/DiggShowAjaxService.htm';
            $fields = array(
                    'evaluation_ids'=> implode(',', $ids),
                    'product_id'    => $aliProductId,
                    'from'          => 'detail',
                    'random'        => rand(0,5)/10
                );

            $json = $this->getWebpage($url, $fields);
            $helpful_counts = json_decode($json,true);

            foreach($helpful_counts['result'] as $id => $data){
                $feedbackData[$id]['ali_helpful_count'] = $data['useful']; 
            }
        }
        return $feedbackData;
    }

    public function downloadAllUserImgs($feedbackData, Product $product)
    {

        // $aliProductId = $this->getAliProductIdFromFeedbackUrl($feedbackUrl);

        /*
        * Use entity manager to locate the row in Product table associated with these user photos
        */ 
        try {
            
            // $product = $this->getContainer()
            //                 ->get('doctrine')
            //                 ->getRepository('AppBundle:Product')
            //                 ->findOneBy(
            //                     array('ali_product_id'=> $aliProductId),
            //                     array('id'=>'DESC')
            //                 )
            //             ;
            
            if (!$product) 
                throw new \InvalidArgumentException('No product found in db for the product object passed into downloadAllUserImgs() method');
            
            $aliProductId = $product->getAliProductId();

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
            
            $key_prefix = $aliProductId; 
            
            $i = 0;

            foreach ($feedbackData as $feedback_id => $data) {
                
                $ali_upload_date = $data['ali_upload_date'];
                $ali_helpful_count = $data['ali_helpful_count'];
                $userImgs = $data['userImgs'];

                foreach ($userImgs as $n => $aliImgUrl) { 
              
                    /*
                    * Download the image into s3 bucket
                    */

                    $key = "$aliProductId-img$i.jpg";

                    /*
                    * UNCOMMENT DELAY BEFORE RUNNING SCRAPER AGAINST LIVE SITE FOR MULTIPLE PRODUCTS
                    */
                    $delay = 5; //in seconds, to rate limit my requests
                    echo "Delaying for $delay seconds between downloading each user img\n\n";
                    sleep($delay);

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
                            $i++;
                        } else {

                            // Throw an exception and log the error
                            echo "An error occurred while trying to upload $key into  s3 bucket $bucket\n\n";  
                            $i++;
                            continue;
                        }

                    } else { 
                        echo "The photo $key is already in the s3 bucket $bucket.\n\n";
                        $i++;
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
                            ->setAliHelpfulCount($ali_helpful_count)
                            ->setAwsImgUrl($aws_img_url)
                            ->setAliImgUrl($aliImgUrl)
                            ->setAliUploadDate($ali_upload_date)
                    ;

                    $em = $this->getEntityManager('default');
                    $em->persist($photo);
                    $em->flush();
                    // break;
                   
                }
            }
        } catch (\InvalidArgumentException $e) {
          
            echo $e->getMessage();

        }

        

        return;
    }   

    function getWebpage($url, $fields = null)
    {
        $ch = curl_init();

        curl_setopt ($ch, CURLOPT_URL, $url);
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
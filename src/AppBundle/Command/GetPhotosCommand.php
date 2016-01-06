<?php 
namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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

    private $product_url = '';
    private $ali_product_id = '';
    private $feedback_url = '';
    private $user_imgs = array();

    protected function configure()
    {
        $this
            ->setName('get:photos')
            ->setDescription('Scrape photos of users wearing products bought from aliexpress.com')
            ->addArgument(
                'productUrl',
                InputArgument::OPTIONAL,
                'Provide the url for the product you\'d like see user photos of.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->product_url = $input->getArgument('productUrl');
        $output->writeln('scraping photos from '.$this->product_url."\n\n");
        return;
        /*
        * 1-Query Product table and return productUrl for all products missing photos that haven't been checked in a week
        * 2-For each product, call other methods here to end up with multi-level array of photos
        * 3-Get CDN's url for each photo 
        * 4-Insert url into the aws_img_url column of the Photo table
        */


        // $kernel = $this->getContainer()->get('kernel');
        // $path = $kernel->locateResource('@AppBundle/Command/');

        // $s3Client = new S3Client(array(
        //     'credentials' => array(
        //         'key'    => AWS_ACCESS_KEY_ID,
        //         'secret' => AWS_SECRET_ACCESS_KEY,
        //     ),
        //     'region'  => 'us-west-2',
        //     'version' => '2006-03-01',
        //     'http'    => [
        //         'verify' => $path.CA_BUNDLE_FILENAME,
        //     ]
        // ));

        // Register the s3 stream wrapper from an S3Client object
        // $s3Client->registerStreamWrapper();

        /*
        * Get an object from its s3 bucket
        */

        // $response = file_get_contents('s3://test-galirie/testfile.txt');

        /*
        * Put an object into a s3 bucket
        */

        // $dir = $path."userImgs/aliproductId-32251240493/";

        // $context = stream_context_create(array(
        //     's3' => array(
        //         'ACL' => 'public-read'
        //     )
        // ));

        // $data = array(
        //             'body'=>file_get_contents($dir.'32251240493-img1.jpg'),
        //             'contentType'=>'image/jpeg',
        //            );

        // $result = file_put_contents('s3://test-galirie/32251240493-img1.jpg', 
        //                             $data, 0 , $context);


        /*
        * Get the web-accessible URL for the Amazon S3 object 
        */
        
        // $aws_img_url = $s3Client->getObjectUrl('test-galirie','32251240493-img1.jpg');

       

        $this->downloadAllUserImgs();
        return;
    }

    /*
    * Get product URL from user input then use it to get url for feedback <iframe>
    */
     
    // private function setFeedbackUrl()
    public function setFeedbackUrl($productUrl)
    {
        $dom_product = new \DOMDocument();
        // @$dom_product->loadHTMLFile($this->product_url);
        @$dom_product->loadHTMLFile($productUrl);
        
        $feedback_urls = array();

        foreach ($dom_product->getElementsByTagName('iframe') as $node) {
            $feedback_urls[] = $node->getAttribute( 'thesrc' );
        }
        if($feedback_urls)
            return $feedbackUrl = $feedback_urls[0] ;

        return null;
        // return $this->feedback_url = $feedback_urls[0] ;
    }

    private function getFeedbackUrl()
    {
        if($this->feedback_url !== ''){
            return $this->feedback_url ;
    
        }       
        return $this->setFeedbackUrl();
    }
    
    /*
    * Get total num of pages of feedback 
    * Example: from <iframe> feedback_url, grab value in <div class="pos-right">, within 2nd <a> tag
    */

    private function getNumReviewPages()
    {
        $feedback_url = $this->getFeedbackUrl();

        $tagName = 'div';
        $attrName = 'class';

        $dom_feedback = new \DOMDocument();
        @$dom_feedback->loadHTMLFile($feedback_url."&page=1");
        $domxpath = new \DOMXPath($dom_feedback);


        $review_page_nodelist = $domxpath->query("//$tagName" . '[@' . $attrName . "='pos-right']/a[2]");


        foreach ($review_page_nodelist as $n) {
            $total_num_review_pages = $n->nodeValue;
        }

        return $total_num_review_pages;
    }
    
    /*
    * Loop through get_img_urls for each page of feedback
    * Grab any user-uploaded pics
    */

    private function getImgUrls()
    {

        $total_pages = $this->getNumReviewPages();
        echo "Looking for pics on $total_pages pages\n\n";

        // for ($i=1; $i <= $total_pages ; $i++) { 
        for ($i=1; $i <= 2 ; $i++) { 
            // $i = 2;
            echo "i is now $i\n\n";
            $domname = 'dom_feedback'.$i;

            $$domname = new \DOMDocument();
            @$$domname->loadHTMLFile($this->feedback_url."&page=$i");

            $tagName = 'div';
            $attrName = 'class';
            $attrValue = 'pic-mark';
            $childTagName = 'img';

            $domxpathname = 'domxpath'.$i;
            $$domxpathname = new \DOMXPath($$domname);
            $filtered = $$domxpathname->query("//$tagName" . '[@' . $attrName . "='$attrValue']/$childTagName");

            foreach ($filtered as $node) {
                $this->user_imgs[] = $node->getAttribute( 'src' );
            }

            $delay = 4; //in seconds, to rate limit my requests
            echo "Delaying for $delay seconds between each feedback page's request\n\n";
            sleep($delay);

        }

        return $this->user_imgs ;
    }

    private function getUserImgHtml()
    {
        $review_img_urls = $this->getImgUrls();

        $user_img_html = '';
        foreach ($review_img_urls as $url) {
            $user_img_html .="<img src='$url'><br>";
        }
        
        return $user_img_html;
    }

    private function downloadAllUserImgs()
    {
        $this->getImgUrls();
        parse_str( parse_url( $this->getFeedbackUrl(), PHP_URL_QUERY));
        $this->ali_product_id = $productId ;

        /*
        * Use entity manager to locate the row in Product table associated with these user photos
        */ 
        $product = $this->getContainer()
                        ->get('doctrine')
                        ->getRepository('AppBundle:Product')
                        ->findOneBy(
                            array('ali_product_id'=> $this->ali_product_id),
                            array('id'=>'DESC')
                        )
                    ;

        // if (!is_dir($dir))
        //     mkdir($dir, 0777, true);

        echo "We found ".count($this->user_imgs)."user-uploaded pics.\n\n";

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
        
        $key_prefix = $this->ali_product_id; 
        
        foreach ($this->user_imgs as $i => $aliImgUrl) { 
      
            /*
            * Download the image into s3 bucket
            */

            $key = "$this->ali_product_id-img$i.jpg";
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
            // $photo_name ='photo'.$i;
            // $$photo_name = new Photo();
            $photo = new Photo();
            
            $photo
                    ->setProduct($product)
                    ->setRating(0)
                    ->setAliHelpfulCount(0)
                    ->setAwsImgUrl($aws_img_url)
                    ->setAliImgUrl($aliImgUrl)
                    // ->setAliImgUrl($this->user_imgs[0])
            ;

            $em = $this->getEntityManager('default');
            $em->persist($photo);
            $em->flush();
            // break;
        }

        return;
    }   


    


}
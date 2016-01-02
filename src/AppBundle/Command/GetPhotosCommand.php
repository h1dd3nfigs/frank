<?php 
namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Product;
use AppBundle\Entity\Photos;
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
        /*
        * 1-Query Product table and return productUrl for all products missing photos that haven't been checked in a week
        * 2-For each product, call other methods here to end up with multi-level array of photos
        * 3-Get CDN's url for each photo 
        * 4-Insert url into the aws_img_url column of the Photo table
        */


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

       
       // // In case I need presignedUrl to upload objects to my s3 bucket
       // $cmd = $s3Client->getCommand('GetObject', [
       //      'Bucket' => 'test-galirie',
       //      'Key'    => 'testfile.txt'
       //  ]);


       //  $request = $s3Client->createPresignedRequest($cmd, '+20 minutes');

       //  // Get the actual presigned-url
       //  $presignedUrl = (string) $request->getUri();
        // return;

        // Register the stream wrapper from an S3Client object
        $s3Client->registerStreamWrapper();

        // $response = file_get_contents('s3://test-galirie/testfile.txt');


        $dir = $path."userImgs/aliproductId-32251240493/";

        $context = stream_context_create(array(
            's3' => array(
                'ACL' => 'public-read'
            )
        ));

        $data = array(
                    'body'=>file_get_contents($dir.'32251240493-img1.jpg'),
                    'contentType'=>'image/jpeg',
                   );

        $result = file_put_contents('s3://test-galirie/32251240493-img1.jpg', 
                                    $data, 0 , $context);

var_dump($result);
return;
        // $this->downloadAllUserImgs();

    }

    /*
    * Get product URL from user input then use it to get url for feedback <iframe>
    */
     
    private function setFeedbackUrl()
    {
        $dom_product = new \DOMDocument();
        @$dom_product->loadHTMLFile($this->product_url);

        foreach ($dom_product->getElementsByTagName('iframe') as $node) {
            $feedback_urls[] = $node->getAttribute( 'thesrc' );
        }

        return $this->feedback_url = $feedback_urls[0] ;
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
            echo 'i is now '.$i;
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

            echo "Delaying for 3 seconds between each feedback page's request\n\n";
            sleep(3);

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

        $kernel = $this->getContainer()->get('kernel');
        $path = $kernel->locateResource('@AppBundle/Command/');

        $dir = $path."userImgs/aliproductId-$this->ali_product_id/";
    
        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        echo "We found ".count($this->user_imgs)."user-uploaded pics.\n\n";

        for ($i=0; $i < count($this->user_imgs); $i++) { 
    
            $filename = "$this->ali_product_id-img$i.jpg";

            // Download the image to AWS S3 bucket
            if(copy($this->user_imgs[$i], $dir.$filename))
                echo "Done downloading : $filename into $dir directory \n\n";

        }

        return true;
    }   


    


}
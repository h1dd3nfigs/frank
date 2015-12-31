<?php 
namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;
//use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Product;
use AppBundle\Entity\Category;

include('settings.php');

class GetProductsCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('get:products')
            ->setDescription('Get products from AliExpress API')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        /*
        * Parameters for api call 
        */
        
        $fields_to_return = array(
                            'totalResults',
                            'productId',
                            'productUrl',
                            'productTitle',
                            'salePrice',
                            'volume',
                            '30daysCommission',             
                        );

        $options = array(
                        'categoryId'        => 3,
                        'packageType'       =>'piece',
                        'pageSize'          => 40,
                        'volumeFrom'        => 100,
                        'originalPriceFrom' => 11.00,
                        'pageNo'            => 1,
                        'sort'              => 'commissionRateDown',//'volumeDown',
                        'fields'            => implode(',', $fields_to_return),
                    );

        $apiName = 'api.listPromotionProduct';
        
        /*
        * Make api call to get list of products 
        */

        $requestUrl = $this->buildRequestUrl($apiName, $options);

        $jsonFeed1 = $this->sendRequest($requestUrl);

        $feedArray = json_decode($jsonFeed1, true);

        $totalResults = $feedArray['result']['totalResults'];

        $totalPages = ceil($totalResults/$options['pageSize']);
        
        $products = $feedArray['result']['products'];

        echo 'products array size is '.count($products)."\n\n";

        if($totalPages > 1) {
            //call api for every subsequent page and append the returned results to the same feedFile as the 1st page of results that got returned
            echo "we're gonna cycle through $totalPages pages of api results.\n\n"; 
            // get results for each page and append onto string $jsonFeed
            for ($i=2; $i <= $totalPages ; $i++) { 
            // for ($i=2; $i <= 3 ; $i++) { // cap it at 3 for now assuming we don't want more than 120 products per category intially

                $options['pageNo'] = $i;
                $requestUrl = $this->buildRequestUrl($apiName, $options);
                $jsonFeed2 = $this->sendRequest($requestUrl); //what's a practical max limit to json string length? should I prepare for that by setting a cap to my $i???
                // echo $jsonFeed."\n";
                // continue;
                $feedArray2 = json_decode($jsonFeed2, true);
                // $r = array_merge($feedArray['result']['products'], $feedArray2['result']['products']);
                $products = array_merge($products, $feedArray2['result']['products']);
                echo 'products array size is '.count($products)."\n\n";
            }
        }


        /*
        * Make api call to get affiliate url for each product
        */

        $fields_to_return1 = array(
                        'totalResults',
                        'trackingId',
                        'publisherId',
                        'url',
                        'promotionUrl',
                        );

        $productUrls = $this->array_value_recursive('productUrl', $products);


        $options1 = array(
            //'productId'       => '1750047098',
            'trackingId'    => TRACKING_ID,
            'fields'            => implode(',', $fields_to_return1),
            'urls'          => implode(',', $productUrls),
            
        );


        $apiName1 = 'api.getPromotionLinks';

        $requestUrl1 = $this->buildRequestUrl($apiName1, $options1);

        $jsonFeed3 = $this->sendRequest($requestUrl1);
        $affiliateUrls = json_decode($jsonFeed3, true)['result']['promotionUrls'];

        /*
        * call getPromotionLinks api for 50 urls at a time because of api limit
        */


        // Code here




        /*
        * Store product feed in db 
        */

        // $category = new Category();
        // $category->setName('Apparel');

        $category = $this->getContainer()->get('doctrine')
                                        ->getRepository('AppBundle:Category')
                                        ->find(1);

        foreach ($products as $key => $p) {
           
            $product = new Product();
            $product
                    ->setCategory($category)
                    ->setAliProductId($p['productId'])
                    ->setAliProductTitle($p['productTitle'])
                    ->setAliProductUrl($p['productUrl'])
                    ->setAliSalePrice($p['salePrice'])
                    ->setAli30DaysCommission($p['30daysCommission'])
                    ->setAliVolume($p['volume'])
                    ->setAliCategoryId($options['categoryId'])
                    ->setAliAffiliateUrl($affiliateUrls[$key]['promotionUrl'])
            ;

            $em = $this->getEntityManager('default');
            $em->persist($product);
            $em->persist($category);
            $em->flush();

            // $output->writeln($p['productTitle']);
        }
        echo 'just sent '.count($products).' products to the db'."\n\n";
    }

    private function buildRequestUrl($apiName, $options = array(), $productId = '', $urls = array())
    {
        $data = array(
                    'domain'    =>'http://gw.api.alibaba.com/openapi',
                    'format'    =>'param2',
                    'version'   => 2,
                    'namespace' =>'portals.open',
            );

        $hostname = implode('/', $data);
        $endpoint = $apiName.'/'.API_KEY.'?'.urldecode(http_build_query($options));

        return $requestUrl = $hostname.'/'.$endpoint;
    }

    private function sendRequest($requestUrl)
    {
        $buzz = $this->getContainer()->get('buzz');
        
        $response = $buzz->get($requestUrl);

        return $feed = $response->getContent();
    }

    private function array_value_recursive($key, array $arr){
        
        $val = array();
        array_walk_recursive($arr, function($v, $k) use($key, &$val){
            if($k == $key) array_push($val, $v);
        });
        
        return count($val) > 1 ? $val : array_pop($val);
    }

}
<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class LuckyController extends Controller
{
    /**
     * @Route("/lucky/number/{count}")
     */
    public function numberAction($count=2)
    {
        $numbers = array();
        
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = rand(0, 100);
        }

        $numbersList = implode(', ', $numbers);
        // return new Response(
        //     '<html><body>Lucky numbers: '.$numbersList.'</body></html>'
        // );

        $html = $this->render(
            'lucky/number.html.twig',
            array('luckyNumberList' => $numbersList)
        );
        return new Response($html);


    }
    
    /**
    * @Route("/api/lucky/number")
    */
    public function apiNumberAction()
    {
        $data = array(
                'lucky_number' => rand(0, 100),
        );
        
        return new JsonResponse($data);
    }
}

<?php

namespace FARSymfony2UploadBundle\Controller;

use FARSymfony2UploadBundle\BlueImp\UploadHandler;
use FARSymfony2UploadBundle\FARSymfony2UploadBundle;
use FARSymfony2UploadBundle\Lib\FileUploadWrapper;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/upload")
     */
    public function uploadAction()
    {
        //$upload = new FileUploadWrapper();

        $upload = new UploadHandler();
        $upload->
    }
}

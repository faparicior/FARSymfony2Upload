<?php

namespace FARSymfony2UploadBundle\Controller;

use FARSymfony2UploadBundle\Lib\FARSymfony2UploadLib;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends FOSRestController
{
    /**
     * @Route("/upload/{id_session}")
     * @Method("POST")
     *
     * @param Request $request
     * @param string $id_session
     *
     * @return JsonResponse
     */
    public function uploadAction(Request $request, $id_session)
    {

        $FARUpload = new FARSymfony2UploadLib($this->container, $request);
        $response = $FARUpload->processUpload($id_session);

        return new JsonResponse(
            array('files' => $response['data']),
            200,
            $response['headers']
        );
    }

    /**
     * @Route("/tmp/{php_session}/{id_session}/{image}_{action}")
     * @Method({"POST", "DELETE"})
     *
     * @param Request $request
     * @param string $php_session
     * @param string $id_session
     * @param string $image
     * @param string $action
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $php_session, $id_session, $image, $action)
    {
        $response = array();
        // TODO: Manejar el error en caso de no encontrar respuesta de borrado satisfactoria.
        $FARUpload = new FARSymfony2UploadLib($this->container, $request);
        if ($FARUpload->evalDelete($id_session, $php_session, $image, $action)) {
            $response = $FARUpload->processDelete($id_session, $php_session, $image);
        }

        return new JsonResponse(array('files' => $response));
    }

}

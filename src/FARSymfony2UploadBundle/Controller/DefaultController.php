<?php

namespace FARSymfony2UploadBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends FOSRestController
{
    /**
     * @Route("/upload/{id_session}")
     * @Method("POST")
     *
     * @param string $id_session
     *
     * @return JsonResponse
     */
    public function uploadAction($id_session)
    {

        $FARUpload = $this->get('far_symfony2_upload_bundle.far_symfony2_upload_lib.service');
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
     * @param string $php_session
     * @param string $id_session
     * @param string $image
     * @param string $action
     *
     * @return JsonResponse
     */
    public function deleteAction($php_session, $id_session, $image, $action)
    {
        $response = array();
        // TODO: Manejar el error en caso de no encontrar, respuesta de borrado satisfactoria.
        $FARUpload = $this->get('far_symfony2_upload_bundle.far_symfony2_upload_lib.service');
        if ($FARUpload->evalDelete($action)) {
            $response = $FARUpload->processDelete($id_session, $php_session, $image);
        }

        return new JsonResponse(array('files' => $response));
    }

    /**
     * @Route("/save/{id_session}")
     * @Method("POST")
     *
     * @param string $id_session
     *
     * @return JsonResponse
     */
    public function saveAction($id_session)
    {
        $FARUpload = $this->get('far_symfony2_upload_bundle.far_symfony2_upload_lib.service');
        $response = $FARUpload->saveUpload($id_session);
    }
}

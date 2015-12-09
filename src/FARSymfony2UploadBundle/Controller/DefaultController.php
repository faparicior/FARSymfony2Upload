<?php

namespace FARSymfony2UploadBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Oneup\FlysystemBundle\OneupFlysystemBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Session\Session;

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
        /* @var FileBag $filebag */
        foreach ($request->files as $filebag) {
            /* @var UploadedFile $file */
            foreach ($filebag as $file) {
                $properties = $this->getFileProperties($file, $id_session);
                // TODO: Validar archivo
                $file->move($properties['temp_dir'], $properties['name_uid']);
                // TODO: Gestionar nombres
                $this->createThumbnail($properties);
                // TODO: Responder con json
                $response = $this->getjQueryUploadResponse($properties, $request);
                return new JsonResponse(array('files' => $response));
            }
        }
        return new JsonResponse(array('files' => ''));
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
        $response = '';

        if ($action == 'DELETE') {
            $path = './tmp/'.$php_session.'/'.$id_session.'/';
            $response = $this->deleteFile($path, $image);
        }

        return new JsonResponse(array('files' => $response));
    }

    /**
     * @param $path
     * @param $image
     *
     * @return string
     */
    private function deleteFile($path, $image)
    {

        // TODO: Borrar thumbnails
        $filesystem = $this
                      ->container
                      ->get('oneup_flysystem.mount_manager')
                      ->getFilesystem('local_filesystem');

        $filesystem->delete($path.$image);
        $response[0][$image] = true;

        return $response;
    }

    /**
     * @param array $properties
     * @param Request $request
     * @return array
     */
    private function getJQueryUploadResponse($properties, $request)
    {
        $response = array();
        $response[0]['url'] = $request->getBaseUrl().'/tmp/'.
                              $properties['session'].'/'.
                              $properties['id_session'].'/'.
                              $properties['name_uid'];
        $response[0]['thumbnailUrl'] = $request->getBaseUrl().'/tmp/'.
                                       $properties['session'].'/'.
                                       $properties['id_session'].'/'.
                                       $properties['thumbnail_name'];
        $response[0]['name'] = $properties['name'];
        $response[0]['type'] = $properties['mimetype'];
        $response[0]['size'] = $properties['size'];
        $response[0]['deleteUrl'] = $response[0]['url'].'_DELETE';
        $response[0]['deleteType'] = 'DELETE';

        return $response;
    }

    /**
     * @param UploadedFile $file
     * @param string $id_session
     * @return array()
     */
    private function getFileProperties($file, $id_session)
    {
        $session = new Session();
        $parameters = $this->getParameter('far_upload_bundle');
        $properties = array();

        $properties['original_name'] = $file->getClientOriginalName();
        $properties['extension'] = $file->guessExtension();
        $original_name = pathinfo($properties['original_name']);
        $properties['name'] = $original_name['filename'];
        $properties['name_uid'] = $properties['original_name'];
        $properties['thumbnail_name'] = $properties['name'].'_'.
                                        $parameters['thumbnail_size'].'.'.
                                        $properties['extension'];
        $properties['size'] = $file->getClientSize();
        $properties['maxfilesize'] = $file->getMaxFilesize();
        $properties['mimetype'] = $file->getMimeType();
        $properties['session'] = $session->getId();
        $properties['id_session'] = $id_session;
        $properties['temp_dir'] = $parameters['temp_path'].'/'.
                                  $session->getId().'/'.
                                  $properties['id_session'];

        return $properties;
    }

    /**
     * @param $properties
     */
    private function createThumbnail($properties)
    {
        $parameters = $this->getParameter('far_upload_bundle');
        switch ($parameters['thumbnail_driver']) {
            case 'gd':
                $imagine = new \Imagine\Gd\Imagine();
                break;
            case 'gmagik':
                $imagine = new \Imagine\Gmagick\Imagine();
                break;
            default:
                $imagine = new \Imagine\Imagick\Imagine();
        }

        $thumbnail_size = explode('x', $parameters['thumbnail_size']);

        $size = new \Imagine\Image\Box($thumbnail_size[0], $thumbnail_size[1]);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $imagine->open($properties['temp_dir'].'/'.$properties['name_uid'])
                ->thumbnail($size, $mode)
                ->save($properties['temp_dir'].'/'.$properties['thumbnail_name']);
    }
}

<?php

namespace FARSymfony2UploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
{
    /**
     * @Route("/upload/{id_session}")
     *
     * @param Request $request
     * @param string $id_session
     *
     * @return JsonResponse
     */
    public function uploadAction(Request $request, $id_session)
    {
        // TODO: Gestionar si es un POST con DELETE Y BORRAR si procede
        if ($request->getMethod() == 'POST') {
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
                    $response = $this->getjQueryUploadResponse($properties, $request, $id_session);
                    return new JsonResponse(array('files' => $response));
                }
            }
        }
        return new JsonResponse(array('files' => ''));
    }
    /**
     * @Route("/tmp/{php_session}/{id_session}/{image}")
     *
     * @param Request $request
     * @param string $php_session
     * @param string $id_session
     * @param string $image
     *
     * @return JsonResponse
     */

    public function deleteAction(Request $request, $php_session, $id_session, $image)
    {
        // TODO: Gestionar si es un POST con DELETE Y BORRAR si procede
        if ($request->getMethod() == 'DELETE') {
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
                    $response = $this->getjQueryUploadResponse($properties, $request, $id_session);
                    return new JsonResponse(array('files' => $response));
                }
            }
        }
        return new JsonResponse(array('files' => ''));
    }



    /*
    {
        "files":
        [
            {
                "url":"http://jquery-file-upload.appspot.com/image%2Fpng/550487655/Captura%20de%20pantalla%20de%202015-12-04%2010%3A03%3A35.png",
                "thumbnailUrl":"http://jquery-file-upload.appspot.com/image%2Fpng/550487655/Captura%20de%20pantalla%20de%202015-12-04%2010%3A03%3A35.png.80x80.png",
                "name":"Captura de pantalla de 2015-12-04 10:03:35.png",
                "type":"image/png",
                "size":21921,
                "deleteUrl":"http://jquery-file-upload.appspot.com/image%2Fpng/550487655/Captura%20de%20pantalla%20de%202015-12-04%2010%3A03%3A35.png",
                "deleteType":"DELETE"
            }
        ]
    }
*/


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
        $response[0]['deleteUrl'] = $response[0]['url'];
        $response[0]['deleteType'] = 'DELETE';

        return $response;
    }

    /**
     * @param UploadedFile $file
     * @return array()
     */
    private function getFileProperties($file, $id_session)
    {
        $session = new Session();
        $properties = array();
        $parameters = $this->getParameter('far_upload_bundle');

        $properties['original_name'] = $file->getClientOriginalName();
        $properties['extension'] = $file->guessExtension();
        $original_name = pathinfo($properties['original_name']);
        $properties['name'] = $original_name['filename'];
        $properties['name_uid'] = uniqid().'.'.$properties['extension'];
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

    private function createThumbnail($properties)
    {
        $parameters = $this->getParameter('far_upload_bundle');
        $thumbnail_size = explode('x', $parameters['thumbnail_size']);

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

        $size    = new \Imagine\Image\Box($thumbnail_size[0], $thumbnail_size[1]);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        // TODO: Gestionar nombre
        $imagine->open($properties['temp_dir'].'/'.$properties['name_uid'])
                ->thumbnail($size, $mode)
                ->save($properties['temp_dir'].'/'.$properties['thumbnail_name']);
    }
}

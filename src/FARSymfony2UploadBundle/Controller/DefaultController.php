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
     * @Route("/upload")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAction(Request $request)
    {
        // TODO: Gestionar si es un POST con DELETE Y BORRAR si procede
        /* @var FileBag $filebag */
        foreach ($request->files as $filebag) {
            /* @var UploadedFile $file */
            foreach ($filebag as $file) {
                $properties = $this->getFileProperties($file);
                // TODO: Validar archivo
                $file->move($properties['temp_dir'], $properties['name_uid']);
                // TODO: Gestionar nombres
                $this->createThumbnail($properties);
                // TODO: Responder con json
                $response = $this->getjQueryUploadResponse($properties, $request);
                return new JsonResponse(array('files' => $response));
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
            }
        }
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
                              $properties['name_uid'];
        $response[0]['thumbnailUrl'] = $request->getBaseUrl().'/tmp/'.
                                       $properties['session'].'/'.
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
    private function getFileProperties($file)
    {
        $session = new Session();
        $properties = array();
        $parameters = $this->getParameter('far_upload_bundle');

        $properties['original_name'] = $file->getClientOriginalName();
        $properties['extension'] = $file->guessExtension();
        $properties['name'] = $file->getBasename();
        $properties['name_uid'] = uniqid().'.'.$properties['extension'];
        $properties['thumbnail_name'] = $properties['name'].'_'.
                                        $parameters['thumbnail_size'].'.'.
                                        $properties['extension'];
        $properties['size'] = $file->getClientSize();
        $properties['maxfilesize'] = $file->getMaxFilesize();
        $properties['mimetype'] = $file->getMimeType();
        $properties['session'] = $session->getId();
        $properties['temp_dir'] = $parameters['temp_path'].'/'.$session->getId();

        return $properties;
    }

    private function createThumbnail($properties)
    {
        $parameters = $this->getParameter('far_upload_bundle');
        $thumbnail_size = explode('x', $parameters['thumbnail_size']);

        // TODO: Usar según driver en configuración
        $imagine = new \Imagine\Gd\Imagine();
     //   $imagine = new \Imagine\Gmagick\Imagine();
     //   $imagine = new \Imagine\Imagick\Imagine();
        $size    = new \Imagine\Image\Box($thumbnail_size[0], $thumbnail_size[1]);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        // TODO: Gestionar nombre
        $imagine->open($properties['temp_dir'].'/'.$properties['name_uid'])
                ->thumbnail($size, $mode)
                ->save($properties['temp_dir'].'/'.$properties['thumbnail_name']);


    }
}

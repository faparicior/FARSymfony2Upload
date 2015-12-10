<?php

namespace FARSymfony2UploadBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
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
                $validFile = $this->validateFile($properties);
                if ($validFile[0] == true) {
                    $file->move($properties['temp_dir'], $properties['name_uid']);
                    // TODO: Gestionar nombres
                    $this->createThumbnail($properties);
                }
                $response = $this->getjQueryUploadResponse($properties, $request, $validFile);
                return new JsonResponse(
                    array('files' => $response),
                    200,
                    $this->getHeadersJSON($request)
                );
            }
        }
        return new JsonResponse(array('files' => ''));
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
        $response = '';

        // TODO: Manejar las dos peticiones
        if ($request->getMethod() === 'POST' &&
            $request->request->get('_method') == 'DELETE') {

        }

        if ($action == 'DELETE') {
            $path = './tmp/'.$php_session.'/'.$id_session.'/';
            $response = $this->deleteFile($path, $image);
        }

        return new JsonResponse(array('files' => $response));
    }

    /**
     * @param array $properties
     *
     * @return array()
     */
    private function validateFile($properties)
    {
        $parameters = $this->getParameter('far_upload_bundle');
        $result = array(true, 'Always fine');

        if (!$this->validateFileSize($properties, $parameters)) {
            $result = array(false, 'File size exceed maximum allowed');
        } else {
            if (!$this->validateFileExtension($properties, $parameters)) {
                $result = array(false, 'File type not allowed');
            }
        }
        if (!$this->validateUploadMaxFiles($properties, $parameters)) {
            $result = array(false, 'Too much files for upload. Limit is '.$parameters['max_files_upload'].' files');
        }

        return $result;
    }

    /**
     * @param array $properties
     * @param array $parameters
     *
     * @return bool
     */
    private function validateFileSize($properties, $parameters)
    {
        if ($properties['size'] > $parameters['max_file_size']) {
            return false;
        }
        return true;
    }

    /**
     * @param array $properties
     * @param array $parameters
     *
     * @return bool
     */
    private function validateFileExtension($properties, $parameters)
    {

        if (array_search($properties['extension'], $parameters['file_extensions_allowed'])) {
            return true;
        }
        return false;
    }

    /**
     * @param array $properties
     * @param array $parameters
     *
     * @return bool
     */
    private function validateUploadMaxFiles($properties, $parameters)
    {
        $filesystem = $this
            ->container
            ->get('oneup_flysystem.mount_manager')
            ->getFilesystem('local_filesystem');

        $contents = $filesystem->listContents('/tmp/'.$properties['session'].'/'.$properties['id_session']);

        /* max_files_upload * 2 because the thumbnails */
        if (count($contents) < $parameters['max_files_upload']*2) {
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     *
     * @return array $header
     */
    private function getHeadersJSON($request)
    {
        $server_accept = $request->server->get('HTTP_ACCEPT');

        if ($server_accept && strpos($server_accept, 'application/json') !== false) {
            $type_header = 'application/json';
        } else {
            $type_header = 'text/plain';
        }
        return array(
            'Vary' => 'Accept',
            'Content-type' => $type_header,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Content-Disposition' => 'inline; filename="files.json"',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'OPTIONS, HEAD, GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'X-File-Name, X-File-Type, X-File-Size',
        );
    }

    /**
     * @param $path
     * @param $image
     *
     * @return string
     */
    private function deleteFile($path, $image)
    {
        // TODO: Borrar miniaturas PS
        $filesystem = $this
                      ->container
                      ->get('oneup_flysystem.mount_manager')
                      ->getFilesystem('local_filesystem');

        $filesystem->delete($path.$image);
        $filesystem->delete($path.$this->getFileNameOrThumbnail($image, true));
        $response[0][$image] = true;

        return $response;
    }

    /**
     * @param string $filename
     * @param string $thumbnail
     *
     * @return string
     */
    private function getFileNameOrThumbnail($filename, $thumbnail)
    {
        $parameters = $this->getParameter('far_upload_bundle');

        $original_name = pathinfo($filename);
        $name = $original_name['filename'];
        $extension = $original_name['extension'];

        if ($thumbnail) {
            return $name.'_'.$parameters['thumbnail_size'].'.'.$extension;
        } else {
            return $name;
        }
    }

    /**
     * @param array $properties
     * @param Request $request
     * @param array $validFile
     *
     * @return array()
     */
    private function getJQueryUploadResponse($properties, $request, $validFile)
    {
        $response[0]['name'] = $properties['name'];
        $response[0]['size'] = $properties['size'];
        if ($validFile[0]) {
            $response[0]['url'] = $request->getBaseUrl().'/tmp/'.
                $properties['session'].'/'.
                $properties['id_session'].'/'.
                $properties['name_uid'];
            $response[0]['thumbnailUrl'] = $request->getBaseUrl().'/tmp/'.
                $properties['session'].'/'.
                $properties['id_session'].'/'.
                $properties['thumbnail_name'];
            $response[0]['deleteUrl'] = $response[0]['url'].'_DELETE';
            $response[0]['deleteType'] = 'DELETE';
            $response[0]['type'] = $properties['mimetype'];
        } else {
            $response[0]['error'] = $validFile[1];
        }

        return $response;
    }

    /**
     * @param UploadedFile $file
     * @param string $id_session
     *
     * @return array()
     */
    private function getFileProperties($file, $id_session)
    {
        $session = new Session();
        $parameters = $this->getParameter('far_upload_bundle');
        $properties = array();

        $properties['original_name'] = $file->getClientOriginalName();
        $properties['extension'] = $file->guessExtension();

        $properties['name'] = $this->getFileNameOrThumbnail($properties['original_name'], false);
        $properties['name_uid'] = $properties['original_name'];
        $properties['thumbnail_name'] = $this->getFileNameOrThumbnail($properties['original_name'], true);
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
        // TODO: Generar miniaturas PS
        $parameters = $this->getParameter('far_upload_bundle');

        $thumbnail_size = explode('x', $parameters['thumbnail_size']);
        $imagine = $this->getImagineEngine($parameters);

        $size = new \Imagine\Image\Box($thumbnail_size[0], $thumbnail_size[1]);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $imagine->open($properties['temp_dir'].'/'.$properties['name_uid'])
                ->thumbnail($size, $mode)
                ->save($properties['temp_dir'].'/'.$properties['thumbnail_name']);
    }

    /**
     * @param $parameters
     *
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|\Imagine\Imagick\Imagine
     */
    private function getImagineEngine($parameters)
    {
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

        return $imagine;
    }
}

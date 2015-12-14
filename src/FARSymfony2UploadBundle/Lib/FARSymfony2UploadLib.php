<?php

namespace FARSymfony2UploadBundle\Lib;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class FARSymfony2UploadLib
{
    protected $options;
    protected $session;
    private $id_session;
    private $parameters;
    private $container;
    private $request;

    public function __construct(Container $container, Request $request, $options = null)
    {
        $this->session = new Session();
        $this->container = $container;
        $this->request = $request;
        $this->options = $options;
        $this->parameters = $this->container->getParameter('far_upload_bundle');
    }

    /**
     * @param $id_session
     *
     * @return mixed
     */
    public function processUpload($id_session)
    {
        $this->id_session = $id_session;

        $response['data'] = array('files' => '');
        /* @var FileBag $filebag */
        foreach ($this->request->files as $filebag) {
            /* @var UploadedFile $file */
            foreach ($filebag as $file) {
                $properties = $this->getFileProperties($file);
                $validFile = $this->validateFile($properties);
                if ($validFile[0] == true) {
                    $file->move($properties['temp_dir'], $properties['name_uid']);
                    $this->createThumbnail($properties);
                }
                $response['data'] = $this->getjQueryUploadResponse($properties, $this->request, $validFile);
            }
        }

        $response['headers'] = $this->getHeadersJSON($this->request);

        return $response;
    }

    /**
     * @param $id_session
     * @param $php_session
     * @param $image
     * @param $action
     *
     * @return bool
     */
    public function evalDelete($id_session, $php_session, $image, $action)
    {
        if (($this->request->getMethod() === 'POST' && $this->request->request->get('_method') == 'DELETE') ||
            ($this->request->getMethod() === 'DELETE' && $action == 'DELETE')
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $id_session
     * @param $php_session
     * @param $image
     *
     * @return array()
     */
    public function processDelete($id_session, $php_session, $image)
    {
        $path = $this->parameters['temp_path'].'/'.$php_session.'/'.$id_session.'/';
        $response = $this->deleteFile($path, $image);

        return $response;
    }

    /**
     * @param UploadedFile $file
     *
     * @return array()
     */
    private function getFileProperties($file)
    {
        $properties = array();

        $properties['original_name'] = $file->getClientOriginalName();
        $properties['extension'] = $file->guessExtension();

        $properties['name'] = $this->getFileNameOrThumbnail($properties['original_name'], false);
        $properties['name_uid'] = $properties['original_name'];
        $properties['thumbnail_name'] = $this->getFileNameOrThumbnail($properties['original_name'], true);
        $properties['size'] = $file->getClientSize();
        $properties['maxfilesize'] = $file->getMaxFilesize();
        $properties['mimetype'] = $file->getMimeType();
        $properties['session'] = $this->session->getId();
        $properties['id_session'] = $this->id_session;
        $properties['temp_dir'] = $this->parameters['temp_path'].'/'.
            $this->session->getId().'/'.
            $properties['id_session'];

        return $properties;
    }

    /**
     * @param array $properties
     *
     * @return array()
     */
    private function validateFile($properties)
    {
        $result = array(true, 'Always fine');

        if (!$this->validateFileSize($properties)) {
            $result = array(false, 'File size exceed maximum allowed');
        } else {
            if (!$this->validateFileExtension($properties)) {
                $result = array(false, 'File type not allowed');
            }
        }
        if (!$this->validateUploadMaxFiles($properties)) {
            $result = array(false, 'Too much files for upload. Limit is '.
                                   $this->parameters['max_files_upload'].' files');
        }

        return $result;
    }

    /**
     * @param array $properties
     *
     * @return bool
     */
    private function validateFileSize($properties)
    {
        if ($properties['size'] > $this->parameters['max_file_size']) {
            return false;
        }
        return true;
    }

    /**
     * @param array $properties
     *
     * @return bool
     */
    private function validateFileExtension($properties)
    {

        if (array_search($properties['extension'], $this->parameters['file_extensions_allowed'])) {
            return true;
        }
        return false;
    }

    /**
     * @param array $properties
     *
     * @return bool
     */
    private function validateUploadMaxFiles($properties)
    {
        $finder = new Finder();
        $countFiles = $finder->files()
            ->in($this->parameters['temp_path'].'/'.
                        $properties['session'].'/'.
                        $properties['id_session'])
            ->count();

        /* max_files_upload * 2 because the thumbnails */
        if ($countFiles < $this->parameters['max_files_upload']*2) {
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
     * @param $file
     *
     * @return string
     */
    private function deleteFile($path, $file)
    {
        // TODO: Borrar miniaturas PS
        $filesystem = new Filesystem();
        $fileTemp = $path.$file;
        $thumbnail = $path.$this->getFileNameOrThumbnail($file, true);

        if ($filesystem->exists($fileTemp)) {
            $filesystem->remove($fileTemp);
        }
        if ($filesystem->exists($thumbnail)) {
            $filesystem->remove($thumbnail);
        }
        $response[0][$fileTemp] = true;

        return $response;
    }

    // TODO: Implementar el traspaso de ficheros
    private function syncFiles()
    {
        $filesystem = $this
            ->container
            ->get('oneup_flysystem.mount_manager')
            ->getFilesystem('local_filesystem');

//        $filesystem->delete($path.$image);
//        $filesystem->delete($path.$this->getFileNameOrThumbnail($image, true));
    }

    /**
     * @param string $filename
     * @param string $thumbnail
     *
     * @return string
     */
    private function getFileNameOrThumbnail($filename, $thumbnail)
    {
        $original_name = pathinfo($filename);
        $name = $original_name['filename'];
        $extension = $original_name['extension'];

        if ($thumbnail) {
            return $name.'_'.$this->parameters['thumbnail_size'].'.'.$extension;
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
     * @param $properties
     */
    private function createThumbnail($properties)
    {
        // TODO: Generar miniaturas PS
        $thumbnail_size = explode('x', $this->parameters['thumbnail_size']);
        $imagine = $this->getImagineEngine();

        $size = new \Imagine\Image\Box($thumbnail_size[0], $thumbnail_size[1]);
        $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET;

        $imagine->open($properties['temp_dir'].'/'.$properties['name_uid'])
            ->thumbnail($size, $mode)
            ->save($properties['temp_dir'].'/'.$properties['thumbnail_name']);
    }

    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|\Imagine\Imagick\Imagine
     */
    private function getImagineEngine()
    {
        switch ($this->parameters['thumbnail_driver']) {
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

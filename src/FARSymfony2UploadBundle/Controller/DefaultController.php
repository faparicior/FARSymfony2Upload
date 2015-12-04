<?php

namespace FARSymfony2UploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\Filesystem\Filesystem;

class DefaultController extends Controller
{
    /**
     * @Route("/upload")
     *
     * @param Request $request
     */
    public function uploadAction(Request $request)
    {
        /* @var FileBag $filebag */
        foreach ($request->files as $filebag) {
            $dir_session = uniqid();
            foreach ($filebag as $file) {
                // TODO: Recoger características del archivo
                /* @var UploadedFile $file */
                // $file = $file[0];
                $name = uniqid();
                $extension = $file->guessExtension();
                $temp_path = $this->getParameter('far_upload_bundle')['temp_path'];

                // TODO: Validar archivo
                // TODO: Mover el archivo
                $file->move($temp_path.'/'.$dir_session, $name . '.' . $extension);
                // TODO: Guardarlo en directorio nuevo de sesión de usuario
                // TODO: Responder con json
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
}

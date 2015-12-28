FARFileUploader
===============

This bundle adds symfony2 integration to the [BlueImp/Jquery Upload Plugin](https://blueimp.github.io/jQuery-File-Upload/)

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require faparicior/far-symfony2-jquery-upload "~1"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new FARSymfony2UploadBundle\FARSymfony2UploadBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configure the Bundle
----------------------------

1) Add this lines in the `app/config/routing.yml`

```yaml
far_symfony2_upload:
    resource: "@FARSymfony2UploadBundle/Controller/"
    type:     annotation
    prefix:   "/farupload"
```

2) Add the config necessary for the bundle in `app/config/config.yml`

```yaml
far_symfony2_upload:
    temp_path: "%kernel.root_dir%/../web/tmp"
    thumbnail_directory_prefix: "thumbnails"
    thumbnail_driver: "gd"
    thumbnail_size: "80x80"
    max_file_size: 100000
    max_files_upload: 2
    file_extensions_allowed: ["jpg", "png", "gif"]
    local_filesystem: "local_filesystem"
    remote_filesystem: "remote_filesystem"
```

3) Add the config necessary for the OneUpFlySystem bundle in `app/config/config.yml`

```yaml
oneup_flysystem:
    adapters:
        local_adapter:
            local:
                directory: %kernel.root_dir%/../web/tmp
                writeFlags: ~
                linkHandling: ~
        remote_adapter:
            local:
                directory: %kernel.root_dir%/../web/images
                writeFlags: ~
                linkHandling: ~
    filesystems:
        my_filesystem:
            adapter: local_adapter
            mount: local_filesystem
        remote_filesystem:
            adapter: remote_adapter
            mount: remote_filesystem
```

4) You can see the new routes added to your development.

```bash
$ php app/console debug:router

farsymfony2upload_default_upload POST        ANY    ANY  /farupload/upload/{id_session}
farsymfony2upload_default_delete POST|DELETE ANY    ANY  /farupload/tmp/{php_session}/{id_session}/{image}_{action}
farsymfony2upload_default_save   POST        ANY    ANY  /farupload/save/{id_session}
```

Usage
=====

In the upload javascript action
-------------------------------

Your javascript development needs an UID that identifies the upload. The bundle uses 
the php_session and the UID to generate the temporary directory structure.

In the save symfony2 action
---------------------------

This is te example of a save action for symfony2.

With the php_session and the UID that stores the `id_session` variable, you can get 
the files involved in the upload.

The `getListFilesLocal` function returns an array of files like this:

TODO: Print the array

The `setListFilesPathRemote` function returns an array of files that 
establishes origin and destination to filesystems:

TODO: Print the array

The `syncFilesLocalRemote` copy files from temporary to definitive storage.
This function returns an array with the results. Include properties that  
informs the duplicated or rewrited files.
 
TODO: Print the array

The `deleteFilesLocal` clean the temporary files. And returns an array with the results.

TODO: Print the array


```php
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
    $php_session = $this->get('session')->getId();
    $FARUpload = $this->get('far_symfony2_upload_bundle.far_symfony2_upload_lib.service');

    $files = $FARUpload->getListFilesLocal($php_session, $id_session);
    $files = $FARUpload->setListFilesPathRemote($files, $id_session);

    $files = $FARUpload->syncFilesLocalRemote($files, true);
    $files = $FARUpload->deleteFilesLocal($files);

    return new JsonResponse(array('files' => $files));
}
```

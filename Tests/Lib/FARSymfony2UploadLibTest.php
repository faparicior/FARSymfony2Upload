<?php

namespace faparicior\FARSymfony2UploadBundle\Tests\Lib;

use \Mockery as m;
use faparicior\FARSymfony2UploadBundle\Lib\FARSymfony2UploadLib;

class FARSymfony2UploadBundle extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSomething()
    {
        $far_symfony_upload = new FARSymfony2UploadLib();

    }
}

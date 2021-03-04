<?php
/*************************************************************************************/
/*      This file is part of the module CanonicalUrl                                 */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace CanonicalUrl\Tests;

use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\EventListener\CanonicalUrlListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CanonicalUrlTest
 * @package CanonicalUrl\Tests
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class CanonicalUrlTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        /*$config = $this->getMock('Thelia\Model\ConfigQuery');

        $config->expects($this->any())
            ->method('read')
            ->with('allow_slash_ended_uri')
            ->will($this->returnValue(true));*/
    }

    public function testRemoveFileIndex()
    {
        $this->performList('http://myhost.com/test', [
            'http://myhost.com/index.php/test',
            'http://myhost.com/index.php/test/',
            'http://myhost.com/index.php/test?page=22&list=1',
            'http://myhost.com/index.php/test/?page=22&list=1'
        ]);
    }

    public function testRemoveFileIndexDev()
    {
        $this->performList('http://myhost.com/test', [
            'http://myhost.com/index_dev.php/test',
            'http://myhost.com/index_dev.php/test/',
            'http://myhost.com/index_dev.php/test?page=22&list=1',
            'http://myhost.com/index_dev.php/test/?page=22&list=1'
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/index_dev.php'
        ));
    }

    public function testHTTPWithSubDomain()
    {
        $this->performList('http://mysubdomain.myhost.com/test', [
            'http://mysubdomain.myhost.com/index.php/test/?page=22&list=1'
        ]);
    }

    public function testHTTPS()
    {
        $this->performList('https://myhost.com/test', [
            'https://myhost.com/index.php/test/?page=22&list=1'
        ]);
    }

    public function testHTTPSWithSubDomain()
    {
        $this->performList('https://mysubdomain.myhost.com/test', [
            'https://mysubdomain.myhost.com/index.php/test/?page=22&list=1'
        ]);
    }

    public function testHTTPWithSubdirectory()
    {
        $this->performList('http://myhost.com/web/test', [
            'http://myhost.com/web/index.php/test',
            'http://myhost.com/web/index.php/test/',
            'http://myhost.com/web/index.php/test?page=22&list=1',
            'http://myhost.com/web/index.php/test?page=22&list=1/'
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));

        $this->performList('http://myhost.com/web/test', [
            'http://myhost.com/web/index_dev.php/test',
            'http://myhost.com/web/index_dev.php/test/',
            'http://myhost.com/web/index_dev.php/test?page=22&list=1',
            'http://myhost.com/web/index_dev.php/test?page=22&list=1/'
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/web/index_dev.php'
        ));
    }

    public function testHTTPWithMultipleSubdirectory()
    {
        $this->performList('http://myhost.com/web/web2/web3/test', [
            'http://myhost.com/web/web2/web3/index.php/test/?page=22&list=1'
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index.php',
            '/web/web2/web3/index.php'
        ));

        $this->performList('http://myhost.com/web/web2/web3/test', [
            'http://myhost.com/web/web2/web3/index_dev.php/test/?page=22&list=1'
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index_dev.php',
            '/web/web2/web3/index_dev.php'
        ));
    }

    public function testHTTPSWithSubdirectory()
    {
        $this->performList('https://myhost.com/web/test', [
            'https://myhost.com/web/index.php/test/?page=22&list=1'
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));
    }

    public function testHTTPSWithMultipleSubdirectory()
    {
        $this->performList('https://myhost.com/web/web2/web3/test', [
            'https://myhost.com/web/web2/web3/index.php/test/?page=22&list=1'
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index.php',
            '/web/web2/web3/index.php'
        ));
    }

    public function testWithNoPath()
    {
        $this->performList('http://myhost.com/?list=22&page=1', [
            'http://myhost.com?list=22&page=1',
            'http://myhost.com/?list=22&page=1',
            'http://myhost.com/index.php?list=22&page=1',
            'http://myhost.com/index.php/?list=22&page=1'
        ]);

        $this->performList('http://myhost.com/?list=22&page=1', [
            'http://myhost.com/index_dev.php?list=22&page=1',
            'http://myhost.com/index_dev.php/?list=22&page=1'
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/index_dev.php'
        ));
    }

    public function testWithNoPathAndMultipleSubdirectory()
    {
        $this->performList('http://myhost.com/web/?list=22&page=1', [
            'http://myhost.com/web/index.php?list=22&page=1',
            'http://myhost.com/web/?list=22&page=1',
            'http://myhost.com/web/index.php?list=22&page=1',
            'http://myhost.com/web/index.php/?list=22&page=1'
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));

        $this->performList('http://myhost.com/web/?list=22&page=1', [
            'http://myhost.com/web/index_dev.php?list=22&page=1',
            'http://myhost.com/web/index_dev.php/?list=22&page=1'
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/web/index_dev.php'
        ));
    }
    public function testWithNotRewrittenUrl()
    {
        $this->performList('http://myhost.com/web/?view=category&lang=fr_FR&category_id=48', [
            'http://myhost.com/web/index.php?view=category&lang=fr_FR&category_id=48',
            'http://myhost.com/web/?lang=fr_FR&view=category&category_id=48',
            'http://myhost.com/web/index.php?&category_id=48&lang=fr_FR&view=category',
            'http://myhost.com/web/index.php/?category_id=48&view=category&lang=fr_FR'
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));
    }

    public function testOverrideCanonicalEvent()
    {
        $canonicalUrlListener = new CanonicalUrlListener(Request::create('https://myhost.com/test'));

        $event = new CanonicalUrlEvent();

        // override canonical
        $canonical = 'http://myscanonical.com';
        $event->setUrl($canonical);

        $canonicalUrlListener->generateUrlCanonical($event);

        $this->assertEquals($canonical, $event->getUrl());
    }

    /**
     * @param string $scriptFileName
     * @param string $scriptName
     * @return array
     */
    protected function fakeServer(
        $scriptFileName = '/var/www/web/index.php',
        $scriptName = '/index.php'
    ) {
        return [
            'SCRIPT_FILENAME' => $scriptFileName,
            'SCRIPT_NAME' => $scriptName
        ];
    }

    /**
     * @param string $canonicalExpected canonical expected
     * @param array $list array of uri
     * @param array $server
     */
    protected function performList($canonicalExpected, array $list, array $server = [])
    {
        if (empty($server)) {
            $server = $this->fakeServer();
        }

        foreach ($list as $uri) {
            $canonicalUrlListener = new CanonicalUrlListener(
                Request::create($uri, 'GET', [], [], [], $server)
            );

            $event = new CanonicalUrlEvent();

            $canonicalUrlListener->generateUrlCanonical($event);

            $this->assertEquals($canonicalExpected, $event->getUrl());
        }
    }
}

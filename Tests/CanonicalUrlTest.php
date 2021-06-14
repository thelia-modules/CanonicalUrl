<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CanonicalUrl\Tests;

use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\EventListener\CanonicalUrlListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CanonicalUrlTest.
 *
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class CanonicalUrlTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp(): void
    {
        /*$config = $this->getMock('Thelia\Model\ConfigQuery');

        $config->expects($this->any())
            ->method('read')
            ->with('allow_slash_ended_uri')
            ->will($this->returnValue(true));*/
    }

    public function testRemoveFileIndex(): void
    {
        $this->performList('http://myhost.com/test', [
            'http://myhost.com/index.php/test',
            'http://myhost.com/index.php/test/',
            'http://myhost.com/index.php/test?page=22&list=1',
            'http://myhost.com/index.php/test/?page=22&list=1',
        ]);
    }

    public function testRemoveFileIndexDev(): void
    {
        $this->performList('http://myhost.com/test', [
            'http://myhost.com/index_dev.php/test',
            'http://myhost.com/index_dev.php/test/',
            'http://myhost.com/index_dev.php/test?page=22&list=1',
            'http://myhost.com/index_dev.php/test/?page=22&list=1',
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/index_dev.php'
        ));
    }

    public function testHTTPWithSubDomain(): void
    {
        $this->performList('http://mysubdomain.myhost.com/test', [
            'http://mysubdomain.myhost.com/index.php/test/?page=22&list=1',
        ]);
    }

    public function testHTTPS(): void
    {
        $this->performList('https://myhost.com/test', [
            'https://myhost.com/index.php/test/?page=22&list=1',
        ]);
    }

    public function testHTTPSWithSubDomain(): void
    {
        $this->performList('https://mysubdomain.myhost.com/test', [
            'https://mysubdomain.myhost.com/index.php/test/?page=22&list=1',
        ]);
    }

    public function testHTTPWithSubdirectory(): void
    {
        $this->performList('http://myhost.com/web/test', [
            'http://myhost.com/web/index.php/test',
            'http://myhost.com/web/index.php/test/',
            'http://myhost.com/web/index.php/test?page=22&list=1',
            'http://myhost.com/web/index.php/test?page=22&list=1/',
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));

        $this->performList('http://myhost.com/web/test', [
            'http://myhost.com/web/index_dev.php/test',
            'http://myhost.com/web/index_dev.php/test/',
            'http://myhost.com/web/index_dev.php/test?page=22&list=1',
            'http://myhost.com/web/index_dev.php/test?page=22&list=1/',
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/web/index_dev.php'
        ));
    }

    public function testHTTPWithMultipleSubdirectory(): void
    {
        $this->performList('http://myhost.com/web/web2/web3/test', [
            'http://myhost.com/web/web2/web3/index.php/test/?page=22&list=1',
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index.php',
            '/web/web2/web3/index.php'
        ));

        $this->performList('http://myhost.com/web/web2/web3/test', [
            'http://myhost.com/web/web2/web3/index_dev.php/test/?page=22&list=1',
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index_dev.php',
            '/web/web2/web3/index_dev.php'
        ));
    }

    public function testHTTPSWithSubdirectory(): void
    {
        $this->performList('https://myhost.com/web/test', [
            'https://myhost.com/web/index.php/test/?page=22&list=1',
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));
    }

    public function testHTTPSWithMultipleSubdirectory(): void
    {
        $this->performList('https://myhost.com/web/web2/web3/test', [
            'https://myhost.com/web/web2/web3/index.php/test/?page=22&list=1',
        ], $this->fakeServer(
            '/var/www/web/web2/web3/index.php',
            '/web/web2/web3/index.php'
        ));
    }

    public function testWithNoPath(): void
    {
        $this->performList('http://myhost.com/?list=22&page=1', [
            'http://myhost.com?list=22&page=1',
            'http://myhost.com/?list=22&page=1',
            'http://myhost.com/index.php?list=22&page=1',
            'http://myhost.com/index.php/?list=22&page=1',
        ]);

        $this->performList('http://myhost.com/?list=22&page=1', [
            'http://myhost.com/index_dev.php?list=22&page=1',
            'http://myhost.com/index_dev.php/?list=22&page=1',
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/index_dev.php'
        ));
    }

    public function testWithNoPathAndMultipleSubdirectory(): void
    {
        $this->performList('http://myhost.com/web/?list=22&page=1', [
            'http://myhost.com/web/index.php?list=22&page=1',
            'http://myhost.com/web/?list=22&page=1',
            'http://myhost.com/web/index.php?list=22&page=1',
            'http://myhost.com/web/index.php/?list=22&page=1',
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));

        $this->performList('http://myhost.com/web/?list=22&page=1', [
            'http://myhost.com/web/index_dev.php?list=22&page=1',
            'http://myhost.com/web/index_dev.php/?list=22&page=1',
        ], $this->fakeServer(
            '/var/www/web/index_dev.php',
            '/web/index_dev.php'
        ));
    }

    public function testWithNotRewrittenUrl(): void
    {
        $this->performList('http://myhost.com/web/?view=category&lang=fr_FR&category_id=48', [
            'http://myhost.com/web/index.php?view=category&lang=fr_FR&category_id=48',
            'http://myhost.com/web/?lang=fr_FR&view=category&category_id=48',
            'http://myhost.com/web/index.php?&category_id=48&lang=fr_FR&view=category',
            'http://myhost.com/web/index.php/?category_id=48&view=category&lang=fr_FR',
        ], $this->fakeServer(
            '/var/www/web/index.php',
            '/web/index.php'
        ));
    }

    public function testOverrideCanonicalEvent(): void
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
     *
     * @return array
     */
    protected function fakeServer(
        $scriptFileName = '/var/www/web/index.php',
        $scriptName = '/index.php'
    ) {
        return [
            'SCRIPT_FILENAME' => $scriptFileName,
            'SCRIPT_NAME' => $scriptName,
        ];
    }

    /**
     * @param string $canonicalExpected canonical expected
     * @param array  $list              array of uri
     */
    protected function performList($canonicalExpected, array $list, array $server = []): void
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

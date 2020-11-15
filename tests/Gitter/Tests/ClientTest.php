<?php

namespace Gitter\Tests;

use Gitter\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ClientTest extends TestCase
{
    public static $tmpdir;
    protected $client;

    public static function setUpBeforeClass()
    {
        if (getenv('TMP')) {
            self::$tmpdir = getenv('TMP');
        } elseif (getenv('TMPDIR')) {
            self::$tmpdir = getenv('TMPDIR');
        } else {
            self::$tmpdir = '/tmp';
        }

        self::$tmpdir .= '/gitlist_' . md5(time() . mt_rand());

        $fs = new Filesystem();
        $fs->mkdir(self::$tmpdir);

        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }

    public function setUp()
    {
        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $path = getenv('GIT_CLIENT') ?: null;
        $this->client = new Client($path);
    }

    public function testIsNotAbleToGetUnexistingRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->client->getRepository(self::$tmpdir . '/testrepo');
    }

    public function testIsParsingGitVersion()
    {
        $version = $this->client->getVersion();
        $this->assertNotEmpty($version);
    }

    public function testIsCreatingRepository()
    {
        $repository = $this->client->createRepository(self::$tmpdir . '/testrepo');
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir . '/testrepo/.git/description');
        $this->assertRegExp('/nothing to commit/', $repository->getClient()->run($repository, 'status'));
    }

    public function testIsCreatingBareRepository()
    {
        $repository = $this->client->createRepository(self::$tmpdir . '/testbare', true);
        $this->assertInstanceOf('Gitter\Repository', $repository);
    }

    public function testIsNotAbleToCreateRepositoryDueToExistingOne()
    {
        $this->expectException(\RuntimeException::class);
        $this->client->createRepository(self::$tmpdir . '/testrepo');
    }

    public function testIsNotOpeningHiddenRepositories()
    {
        $this->expectException(\RuntimeException::class);
        $this->client->getRepository(self::$tmpdir . '/hiddenrepo');
    }

    public function testIsCatchingGitCommandErrors()
    {
        $this->expectException(\RuntimeException::class);
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->getClient()->run($repository, 'wrong');
    }
}

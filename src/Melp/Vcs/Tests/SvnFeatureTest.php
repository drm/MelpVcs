<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */

namespace Melp\Vcs\Tests;

/**
 * @group functional
 */
class SvnFeatureTest extends \PHPUnit_Framework_TestCase
{
    protected $repository;
    protected $url;

    function setUp() {
        $this->repository = tempnam('./tmp/', 'svn-test');
        unlink($this->repository);
        $this->url = 'file://' . $this->repository;
        shell_exec('svnadmin create ' . escapeshellarg($this->repository));
        shell_exec('svn mkdir               \
            ' . $this->url . '/trunk        \
            ' . $this->url . '/branches     \
            ' . $this->url . '/tags         \
            -m"Repository setup" 2>&1');
        shell_exec('svn mkdir -m"Repository setup" 2>&1');
        shell_exec('svn mkdir ' . $this->url . '/tags       -m"Repository setup" 2>&1');
        $this->url .= '/trunk';
    }


    function tearDown() {
//        echo 'Repos root is: ' . $this->repository;
        shell_exec('rm -rf ' . escapeshellarg($this->repository));
    }

    /**
     * @test
     */
    function functional() {
        $client1 = new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter());
        $client1->init($this->url);
        $client1->pull();

        $data1 = 'Hello';
        $client1->put('foo/bar.txt', $data1, 'Hello hello');
        $client1->push();
        $this->assertEquals($data1, $client1->get('foo/bar.txt'));

        $client2 = new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter());
        $client2->init($this->url);
        $this->assertEquals($data1, $client2->get('foo/bar.txt'));

        $data2 = 'Goodbye';
        $client2->put('foo/bar.txt', $data2, "You say hello, I say goodbye");

        $this->assertEquals($data1, $client1->get('foo/bar.txt'));
        $client2->push();

        $client1->pull();
        $this->assertEquals($data2, $client1->get('foo/bar.txt'));

        $client1->rm('foo/bar.txt', 'Delete this');
        $client1->push();

        $client2->pull();
        $this->assertTrue(!$client2->get('foo/bar.txt'));

        $client2->branch('qux');

        $client1->checkout('qux');
        $client1->put("foo/bar/baz.txt", "Waaa", "Created baz.txt");
        $client1->push();

        $client2->pull();
        $this->assertEquals('Waaa', $client2->get("foo/bar/baz.txt"));
        $client2->checkout(null);
        $this->assertEquals(null, $client2->get("foo/bar/baz.txt"));
        $client2->checkout('qux');
        $this->assertEquals('Waaa', $client2->get("foo/bar/baz.txt"));
    }
}
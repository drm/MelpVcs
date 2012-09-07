<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Tests;

/**
 * @group functional
 */
class SvnFeatureTest extends AbstractFeatureTest
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

//    /**
//     * @test
//     * @dataProvider implementations
//     */
//    function functional($impl1, $impl2) {
//        $refl1 = new \ReflectionClass('\Melp\Vcs\\' . $impl1);
//        $refl2 = new \ReflectionClass('\Melp\Vcs\\' . $impl2);
//
//        $client1 = $refl1->newInstance(new \Melp\Vcs\Svn\CliAdapter());
//        $client1->init($this->url);
//        $client1->pull();
//
//        $data1 = 'Hello';
//        $client1->put('foo/bar.txt', $data1, 'Hello hello');
//        $client1->push();
//        $this->assertTrue($client1->has('foo/bar.txt', 'file'));
//        $this->assertEquals($data1, $client1->get('foo/bar.txt'));
//
//        $client2 = $refl2->newInstance(new \Melp\Vcs\Svn\CliAdapter());
//        $client2->init($this->url);
//        $this->assertTrue($client2->has('foo/bar.txt', 'file'));
//        $this->assertEquals($data1, $client2->get('foo/bar.txt'));
//
//        $data2 = 'Goodbye';
//        $client2->put('foo/bar.txt', $data2, "You say hello, I say goodbye");
//
//        if ($impl1 == 'Svn') {
//            // local svn checkout should not have been changed yet
//            $this->assertEquals($data1, $client1->get('foo/bar.txt'));
//        }
//        $client2->push();
//
//        $client1->pull();
//        $this->assertEquals($data2, $client1->get('foo/bar.txt'));
//
//        $client1->rm('foo/bar.txt', 'Delete this');
//        $client1->push();
//
//        $client2->pull();
//        $this->assertTrue(!$client2->get('foo/bar.txt'));
//
//        $client2->branch('qux');
//
//        $client1->checkout('qux');
//        $client1->put("foo/bar/baz.txt", "Waaa", "Created baz.txt");
//        $client1->push();
//
//        $client2->pull();
//        $this->assertEquals('Waaa', $client2->get("foo/bar/baz.txt"));
//        $client2->checkout(null);
//        $this->assertEquals(null, $client2->get("foo/bar/baz.txt"));
//        $client2->checkout('qux');
//        $this->assertEquals('Waaa', $client2->get("foo/bar/baz.txt"));
//    }


    function implementations() {
        return array(
            array(
                new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter()),
                new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter())
            ),
            array(
                new \Melp\Vcs\RemoteSvn(new \Melp\Vcs\Svn\CliAdapter()),
                new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter())
            ),
            array(
                new \Melp\Vcs\Svn(new \Melp\Vcs\Svn\CliAdapter()),
                new \Melp\Vcs\RemoteSvn(new \Melp\Vcs\Svn\CliAdapter())
            ),
            array(
                new \Melp\Vcs\RemoteSvn(new \Melp\Vcs\Svn\CliAdapter()),
                new \Melp\Vcs\RemoteSvn(new \Melp\Vcs\Svn\CliAdapter())
            )
        );
    }
}
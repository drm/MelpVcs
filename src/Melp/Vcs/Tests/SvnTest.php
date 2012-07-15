<?php
namespace Melp\Vcs\Tests;


class SvnTest extends \PHPUnit_Framework_TestCase
{
    protected $repository;
    protected $url;

    function setUp() {
        $this->repository = tempnam('./tmp/', 'svn-test');
        unlink($this->repository);
        $this->url = 'file://' . $this->repository;
        shell_exec('svnadmin create ' . escapeshellarg($this->repository));
    }


    function tearDown() {
//        echo 'Repos root is: ' . $this->repository;
        shell_exec('rm -rf ' . escapeshellarg($this->repository));
    }

    /**
     * @test
     */
    function functional() {
        $client1 = new \Melp\Vcs\Svn();
        $client1->init($this->url);
        $client1->pull();

        $data1 = 'Hello';
        $client1->put('foo/bar.txt', $data1, 'Hello hello');
        $client1->push();
        $this->assertEquals($data1, $client1->get('foo/bar.txt'));

        $client2 = new \Melp\Vcs\Svn();
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
        $this->assertFalse(is_file($client2->local('foo/bar.txt')));
    }
}
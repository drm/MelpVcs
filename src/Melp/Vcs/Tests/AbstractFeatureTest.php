<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Tests;

abstract class AbstractFeatureTest extends \PHPUnit_Framework_TestCase
{
    abstract function implementations();



    /**
     * @test
     * @dataProvider implementations
     */
    function functional($client1, $client2) {
        $client1->init($this->url);
        $client1->pull();

        $data1 = 'Hello';
        $client1->put('foo/bar.txt', $data1, 'Hello hello');
        $client1->push();
        $this->assertTrue($client1->has('foo/bar.txt', 'file'));
        $this->assertEquals($data1, $client1->get('foo/bar.txt'));

        $client2->init($this->url);
        $this->assertTrue($client2->has('foo/bar.txt', 'file'));
        $this->assertEquals($data1, $client2->get('foo/bar.txt'));

        $data2 = 'Goodbye';
        $client2->put('foo/bar.txt', $data2, "You say hello, I say goodbye");

//        if ($impl1 == 'Svn') {
//            // local svn checkout should not have been changed yet
//            $this->assertEquals($data1, $client1->get('foo/bar.txt'));
//        }
        $client2->push();

        $client1->pull();
        $this->assertEquals($data2, $client1->get('foo/bar.txt'));

        $client1->rm('foo/bar.txt', 'Delete this');
        $client1->push();

        $client2->pull();
        $this->assertTrue(!$client2->get('foo/bar.txt'));

        $client2->branch('qux');
        $client2->push();

        $client1->pull();
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
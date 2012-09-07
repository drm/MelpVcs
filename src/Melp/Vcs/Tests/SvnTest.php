<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Tests;

use Melp\Vcs\Svn;

class MockCommandFailedException extends \Exception implements \Melp\Vcs\Svn\CommandFailedException {
}


/**
 * @property \Melp\Vcs\Svn $svn
 * @property \Melp\Vcs\Svn\AdapterInterface $adapter
 * @covers \Melp\Vcs\Svn
 */
class SvnTest extends \PHPUnit_Framework_TestCase {
    protected function setUp()
    {
        $this->adapter = $this->getMock('Melp\Vcs\Svn\AdapterInterface');
        $this->svn = new Svn($this->adapter);
        $this->svn->init('bogus://example.org/trunk');
    }

    function testInitWillDoCheckout()
    {
        $url = 'bogus://example.org/';
        $this->adapter->expects($this->once())->method('init')->with($url);
        $this->adapter->expects($this->once())->method('exec')->with('update');
        $this->svn->init($url);
    }


    function testRm()
    {
        $this->adapter->expects($this->once())->method('exec')->with('rm', 'a/b/c');
        $this->svn->rm('a/b/c', 'rm commit message');
    }


    function testBranchWithoutSwitch()
    {
        $this->adapter->expects($this->once())->method('exec')->with('cp', 'bogus://example.org/trunk', 'bogus://example.org/branches/foo');
        $this->svn->branch('foo', false);
    }


    function testBranchWithSwitch()
    {
        $this->adapter->expects($this->at(0))->method('exec')->with('cp', 'bogus://example.org/trunk', 'bogus://example.org/branches/foo');
        $this->adapter->expects($this->at(1))->method('exec')->with('switch', 'bogus://example.org/branches/foo');

        $this->svn->branch('foo', true);
    }


    function testCheckout()
    {
        $this->adapter->expects($this->at(0))->method('exec')->with('switch', 'bogus://example.org/branches/foo');
        $this->adapter->expects($this->at(1))->method('exec')->with('switch', 'bogus://example.org/trunk');
        $this->svn->checkout('foo');
        $this->svn->checkout(null);
    }


    function testGetBranchUrl()
    {
        $this->svn->init('bogus://example.org/trunk');
        $this->assertEquals('bogus://example.org/branches/foo', $this->svn->getBranchUrl('foo'));

        $this->svn->init('bogus://example.org/branches/bar');
        $this->assertEquals('bogus://example.org/branches/foo', $this->svn->getBranchUrl('foo'));

        $this->svn->init('bogus://example.org/tags/v2.1');
        $this->assertEquals('bogus://example.org/branches/foo', $this->svn->getBranchUrl('foo'));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    function testGetBranchUrlWillThrowExceptionOnUnRecognizedUrlStructure()
    {
        $this->svn->init('bogus://example.org/master');
        $this->svn->getBranchUrl('foo');
    }


    function testGetTagUrl()
    {
        $this->svn->init('bogus://example.org/trunk');
        $this->assertEquals('bogus://example.org/tags/foo', $this->svn->getTagUrl('foo'));
        $this->svn->init('bogus://example.org/branches/bar');
        $this->assertEquals('bogus://example.org/tags/foo', $this->svn->getTagUrl('foo'));
        $this->svn->init('bogus://example.org/tags/v2.1');
        $this->assertEquals('bogus://example.org/tags/foo', $this->svn->getTagUrl('foo'));
    }


    /**
     * @expectedException \UnexpectedValueException
     */
    function testGetTagUrlWillThrowExceptionOnUnRecognizedUrlStructure()
    {
        $this->svn->init('bogus://example.org/master');
        $this->svn->getTagUrl('foo');
    }



    function testGetTrunkUrl()
    {
        $this->svn->init('bogus://example.org/trunk');
        $this->assertEquals('bogus://example.org/trunk', $this->svn->getTrunkUrl());
        $this->svn->init('bogus://example.org/branches/bar');
        $this->assertEquals('bogus://example.org/trunk', $this->svn->getTrunkUrl());
        $this->svn->init('bogus://example.org/tags/v2.1');
        $this->assertEquals('bogus://example.org/trunk', $this->svn->getTrunkUrl());
    }

    function getTrunkUrl()
    {
        return $this->getPseudoRoot() . '/trunk';
    }


    /**
     * @expectedException \UnexpectedValueException
     */
    function testGetTrunkUrlWillThrowExceptionOnUnRecognizedUrlStructure()
    {
        $this->svn->init('bogus://example.org/master');
        $this->svn->getTagUrl('foo');
    }


    function testTag()
    {
        $this->adapter->expects($this->once())->method('exec')
            ->with(
                'cp',
                'bogus://example.org/trunk',
                'bogus://example.org/tags/foo',
                '--message',
                'Tagged bogus://example.org/trunk as bogus://example.org/tags/foo'
            );
        $this->svn->tag('foo');
    }


    function testGet()
    {
        $this->svn->init('bogus://example.org');
        $this->adapter->expects($this->once())->method('exec')->with('cat', 'foo/bar/baz.txt');
        $this->svn->get('foo/bar/baz.txt');
    }


    function testLs()
    {
        $this->adapter->expects($this->once())->method('exec')->with('ls', '--xml')->will($this->returnValue(
            <<<EOXML
<?xml version="1.0"?>
<lists>
<list path="/trunk">
    <entry kind="dir"><name>somedir</name><commit revision="123"><author>gerard</author><date>2012-05-29T16:43:43.722286Z</date></commit></entry>
    <entry kind="dir"><name>some-other-dir</name><commit revision="456"><author>gerard</author><date>2012-07-17T17:02:18.934071Z</date></commit></entry>
    <entry kind="file"><name>index.html</name><size>3743</size><commit revision="266"><author>gerard</author><date>2010-06-08T15:44:18.749707Z</date></commit></entry>
    <entry kind="file"><name>srv.php</name><size>1478</size><commit revision="257"><author>gerard</author><date>2010-03-29T15:52:56.586593Z</date></commit></entry>
</list>
</lists>
EOXML
        ));

        $results = $this->svn->ls();
        $this->assertEquals(array('somedir', 'some-other-dir', 'index.html', 'srv.php'), array_keys($results));
        $this->assertEquals(123, $results['somedir']['commit']);
        $this->assertEquals('gerard', $results['some-other-dir']['author']);
        $this->assertEquals('2010-06-08', $results['index.html']['date']->format('Y-m-d'));
    }


    function testPut()
    {
        $this->adapter->expects($this->once())->method('create')->with('some/file/name', 'The secret to creativity is knowing how to hide your sources.');
        $this->adapter->expects($this->at(0))->method('exec')->with('info', '--xml', 'some/file')->will($this->returnValue(
            <<<EOXML
<info>
<entry kind="dir" path="foo" revision="10192">
<url>bogus://example/foo</url>
<repository>
<root>bogus://example/</root>
<uuid>0331243b-1238-4123-b12a-884a21b21342</uuid>
</repository>
<commit revision="10192">
<author>gerard</author>
<date>2012-07-17T17:02:18.934071Z</date>
</commit>
</entry>
</info>
EOXML
        ));
        $this->adapter->expects($this->at(2))->method('exec')->with('add', 'some/file/name')->will($this->returnValue(false));
        $this->svn->put('some/file/name', 'The secret to creativity is knowing how to hide your sources.', 'some commit message');
    }


    function testPutWillCreateDirIfNotExists()
    {
        $this->adapter->expects($this->once())->method('create')->with('some/file/name', 'The secret to creativity is knowing how to hide your sources.');
        $this->adapter->expects($this->at(0))->method('exec')->with('info', '--xml', 'some/file')->will($this->throwException(new MockCommandFailedException()));
        $this->adapter->expects($this->at(1))->method('exec')->with('mkdir', 'some/file');
//        $this->adapter->expects($this->at(2))->method('exec')->with('add', 'some/file/name')->will($this->returnValue(false));
        $this->svn->put('some/file/name', 'The secret to creativity is knowing how to hide your sources.', 'some commit message');
    }


    function testMkdir()
    {
        $this->adapter->expects($this->once())->method('exec')->with('mkdir', '1/2/3');
        $this->svn->mkdir('1/2/3');
    }


    function testPush()
    {
        $this->adapter->expects($this->once())->method('exec')->with('commit', '--message', '');
        $this->svn->push();
    }


    function testPull()
    {
        $this->adapter->expects($this->once())->method('exec')->with('update');
        $this->svn->pull();
    }


    function testLog() {
        $this->adapter->expects($this->once())->method('exec')->with('log', '--xml', 'filename', '--limit', 10)->will($this->returnValue(
            <<<EOXML
<?xml version="1.0"?>
<log>
    <logentry revision="10185"><author>gerard</author><date>2012-07-17T14:46:06.829645Z</date><msg>foo</msg></logentry>
    <logentry revision="10184"><author>gerard</author><date>2012-07-17T14:31:11.780124Z</date><msg>bar</msg></logentry>
    <logentry revision="10183"><author>gerard</author><date>2012-07-17T14:30:26.494507Z</date><msg>baz</msg></logentry>
</log>
EOXML
        ));
        $logs = $this->svn->log('filename', 10);
        $this->assertEquals(10185, $logs[0]['commit']);
        $this->assertEquals('gerard', $logs[1]['author']);
        $this->assertEquals('baz', $logs[2]['message']);
    }


    function testLogWithoutLimit() {
        $this->adapter->expects($this->once())->method('exec')->with('log', '--xml', 'filename')->will($this->returnValue(null));
        $this->svn->log('filename', null);
    }
}
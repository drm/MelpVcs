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
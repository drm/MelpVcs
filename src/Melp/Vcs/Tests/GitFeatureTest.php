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
class GitFeatureTest extends AbstractFeatureTest
{
    protected $repository;
    protected $url;

    function setUp() {
        $this->repository = tempnam('./tmp/', 'git-test');
        unlink($this->repository);
        $this->url = $this->repository;
        shell_exec('git init --bare ' . escapeshellarg($this->repository));

        // initialize the repository with an empty file so a master branch is available
        shell_exec(
            'cd /tmp && \
                git clone ' . escapeshellarg($this->repository) . ' ' . escapeshellarg($this->repository) . '-clone &&
                cd ' . escapeshellarg($this->repository) . '-clone && \
                touch .gitinit && \
                git add .gitinit && \
                git commit -m"init" .gitinit && \
                git push origin master && \
                rm -rf ' . escapeshellarg($this->repository) . '-clone'
        );
    }


    function tearDown() {
        shell_exec('rm -rf ' . escapeshellarg($this->repository));
    }


    function implementations() {
        return array(
            array(
                new \Melp\Vcs\Git(new \Melp\Vcs\Git\CliAdapter()),
                new \Melp\Vcs\Git(new \Melp\Vcs\Git\CliAdapter())
            )
        );
    }
}
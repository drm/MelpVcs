<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs;

/**
 * Implementation of the SVN client utilizing as little local file storage as possible.
 *
 * This implementation is the fastest to use. The trade-off is that most actions result in direct commits in the
 * remote.
 */
class RemoteSvn extends SvnAbstract
{
    /**
     * Remove a file from the repository.
     *
     * @param string $path
     * @param string $message
     * @return void
     */
    function rm($path, $message)
    {
        $this->svn('rm', $this->absUrl($path), '--message', $message);
    }


    /**
     * Initialize with the remote url.
     *
     * @param $remote
     * @return void
     */
    function init($remote)
    {
        $this->remote = $remote;
    }


    /**
     * Create a branch.
     *
     * @param string $name
     * @param bool $switch
     * @param string $msg
     * @return void
     */
    function branch($name, $switch = true, $msg = 'Branched %s to %s')
    {
        $branchUrl = $this->getBranchUrl($name);
        $this->svn('cp', $this->remote, $branchUrl, '--message', $msg);
        if ($switch) {
            $this->remote = $branchUrl;
        }
    }


    /**
     * Create a tag.
     *
     * @param string $name
     * @param string $msg
     */
    function tag($name, $msg = "Tagged %s as %s")
    {
        $this->svn('cp', $this->remote, $this->getTagUrl($name), '--message', $msg);
    }


    /**
     * Check if a path exists in the repository.
     *
     * @param string $path
     * @param string $type
     * @return bool
     */
    function has($path, $type)
    {
        try {
            $info = simplexml_load_string($this->adapter->exec('info', '--xml', $this->absUrl($path)));
            return (string)($info->entry[0]['kind']) == $type;
        } catch (Svn\CommandFailedException $e) {
            return false;
        }
    }


    /**
     * Get file contents from the repository.
     *
     * Returns null if not found.
     *
     * @param $path
     * @return mixed
     */
    function get($path)
    {
        try {
            return $this->svn('cat', $this->absUrl($path));
        } catch(Svn\CommandFailedException $e) {
            return null;
        }
    }


    /**
     * Return a string.
     *
     * @param string $path
     * @return array
     */
    function ls($path = '')
    {
        $response = simplexml_load_string($this->svn('ls', '--xml', $this->absUrl($path)));
        return $this->parseLs($response);
    }


    /**
     * Create or overwrite
     *
     * @param string $path
     * @param string $content
     * @param string $message
     * @return void
     */
    function put($path, $content, $message)
    {
        // we'll need a temporary working copy for this, since SVN does not support putting remote files directly.
        $adapter = clone $this->adapter;
        if (!$this->has(dirname($path), 'dir')) {
            $this->svn('mkdir', $this->absUrl(dirname($path)), '--parents', '--message', $message);
        }
        $svn = new Svn($adapter);
        $svn->init($this->absUrl(dirname($path)));
        $svn->put(basename($path), $content, $message);
        $svn->push();
        $adapter->cleanup();
    }


    /**
     * Is not implemented, since it has no meaning when working remotely.
     *
     * @return void
     */
    function push()
    {
        // noop, we're working remotely
    }

    /**
     * Is not implemented, since it has no meaning when working remotely.
     *
     * @return void
     */
    function pull()
    {
        // noop, we're working remotely
    }


    /**
     * Switches the remote url to the specified branch.
     *
     * @param string $branch
     * @return void
     */
    function checkout($branch)
    {
        if (is_null($branch)) {
            $this->remote = $this->getTrunkUrl();
        } else {
            $this->remote = $this->getBranchUrl($branch);
        }
    }


    /**
     * Get a log from the remote for the specified path.
     *
     * @param string $path
     * @param int $limit
     * @return array
     */
    function log($path, $limit = 10)
    {
        $ret = array();
        if ($limit) {
            $data = $this->svn('log', '--xml', $this->absUrl($path), '--limit', $limit);
        } else {
            $data = $this->svn('log', '--xml', $this->absUrl($path));
        }
        if ($data) {
            $response = simplexml_load_string($data);
            $ret = $this->parseLog($response);
        }
        return $ret;
    }


    /**
     * Internal helper; maps a path to the remote url.
     *
     * @param string $path
     * @return string
     */
    protected function absUrl($path)
    {
        return rtrim($this->remote, '/') . '/' . ltrim($path, '/');
    }
}
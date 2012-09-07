<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs;

/**
 * Local SVN implementation.
 */
class Svn extends SvnAbstract implements ClientInterface
{
    /**
     * Contains a queue of messages to be used in the next commit (push).
     *
     * @var array
     */
    protected $messages = array();


    /**
     * Remove a path from the local working copy and queues the message for the next commit.
     *
     * @param $path
     * @param $message
     * @return void
     */
    function rm($path, $message)
    {
        $this->svn('rm', $path);
        $this->messages[]= $message;
    }


    /**
     * Initializes the remote url and instructs the adapter implementation to checkout the working copy.
     *
     * @param $remote
     * @return void
     */
    function init($remote)
    {
        $this->remote = $remote;
        $this->adapter->init($remote);
        $this->pull();
    }


    /**
     * Creates a remote branch copy of the current remote url.
     *
     * Note that changed files aren't committed first, they are kept as local changes.
     *
     * @param string $name
     * @param bool $switch
     * @param string $msg
     * @return void
     */
    function branch($name, $switch = true, $msg = 'Branched %s to %s')
    {
        $branch = $this->getBranchUrl($name);
        $this->svn('cp', $this->remote, $branch, '--message', sprintf($msg, $this->remote, $branch));
        if ($switch) {
            $this->checkout($name);
        }
    }


    /**
     * Switches to the specified branch.
     *
     * Passing null will switch to the trunk.
     *
     * @param $branch
     * @return void
     */
    function checkout($branch)
    {
        if ($branch === null) {
            $remoteBranch = $this->getTrunkUrl();
        } else {
            $remoteBranch = $this->getBranchUrl($branch);
        }
        $this->svn('switch', $remoteBranch);
    }


    /**
     * @param $name
     * @param string $msg
     *
     * TODO probably tag from local revision number in stead of remote url, or check local working copy state.
     */
    function tag($name, $msg = "Tagged %s as %s")
    {
        $this->svn('cp', $this->remote, $this->getTagUrl($name), '--message', sprintf($msg, $this->remote, $this->getTagUrl($name)));
    }

    /**
     * Returns the contents of the specified working copy path.
     *
     * @param string $path
     * @return mixed|null
     */
    function get($path)
    {
        try {
            return $this->svn('cat', $path);
        } catch(Svn\CommandFailedException $e) {
            return null;
        }
    }


    /**
     * List files in the local working copy path.
     *
     * Uses subversion to list the files, not the file system.
     *
     * @param string $path
     * @return array
     */
    function ls($path = '')
    {
        $response = simplexml_load_string($this->svn('ls', '--xml', $path));
        return $this->parseLs($response);
    }


    /**
     * Creates or overwrites the local path with the passed contents.
     *
     * @param string $path
     * @param string $content
     * @param string $message
     * @return void
     */
    function put($path, $content, $message)
    {
        $dir = dirname($path);
        if (!$this->has($dir, 'dir')) {
            $this->mkdir($dir);
        }
        $this->adapter->create($path, $content);
        $this->svn('add', $path);
        $this->messages[]= $message;
    }


    /**
     * Checks if the passed path name exists.
     *
     * @param string $path
     * @param string $type
     * @return bool
     */
    function has($path, $type) {
        try {
            $info = simplexml_load_string($this->adapter->exec('info', '--xml', $path));
            return (string)($info->entry[0]['kind']) == $type;
        } catch (Svn\CommandFailedException $e) {
            return false;
        }
    }


    /**
     * Creates a dir in the working copy.
     *
     * Does a recursive mkdir, so deep paths are supported.
     *
     * @param $dir
     * @return void
     */
    function mkdir($dir)
    {
        $this->svn('mkdir', $dir, '--parents');
    }


    /**
     * Commits the working copy state to the remote, and passes the message queue as a commit message, separated by
     * newlines.
     *
     * @return void
     */
    function push()
    {
        $this->svn('commit', '--message', implode("\n", $this->messages));
    }


    /**
     * Updates the local working copy.
     */
    function pull()
    {
        $this->svn('update', '--set-depth', 'infinity');
    }


    /**
     * Returns a log for the passed path name.
     *
     * @param string $path
     * @param int $limit
     * @return array
     */
    function log($path, $limit = 10)
    {
        $ret = array();
        if ($limit) {
            $data = $this->svn('log', '--xml', $path, '--limit', $limit);
        } else {
            $data = $this->svn('log', '--xml', $path);
        }
        if ($data) {
            $response = simplexml_load_string($data);
            $ret = $this->parseLog($response);
        }
        return $ret;
    }
}




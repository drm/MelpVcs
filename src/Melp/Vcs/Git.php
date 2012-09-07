<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */
namespace Melp\Vcs;

/**
 *
 */
class Git implements ClientInterface
{
    function __construct(\Melp\Vcs\Git\CliAdapter $adapter)
    {
        $this->adapter = $adapter;
        $this->branch = null;
    }


    /**
     * Initialize the client with the passed remote url.
     *
     * @param $remote
     */
    function init($remote)
    {
        $this->adapter->init($remote);
    }

    /**
     * Create a branch of the initialized copy.
     *
     * @param string $name
     * @param bool $switch
     */
    function branch($name, $switch = true)
    {
        $this->adapter->exec('branch', $name);
        if ($switch) {
            $this->adapter->exec('checkout', $name);
            $this->branch = $name;
        }
    }

    /**
     * Checkout a branch with the passed name
     *
     * @param string $branch
     */
    function checkout($branch)
    {
        $this->adapter->exec('pull', 'origin', $branch ?: 'master');
        $this->adapter->exec('checkout', $branch ?: 'master');
        $this->branch = $branch;
    }

    /**
     * Create a tag with the passed name
     *
     * @param string $name
     */
    function tag($name)
    {
        $this->adapter->exec('tag', $branch);
    }

    /**
     * Checks if the file with the passed name exists.
     *
     * @param string $path
     * @param string $type
     */
    function has($path, $type)
    {
        $fn = (in_array($type, array('dir', 'file')) ? 'is_' . $type : null);
        return $fn($this->adapter->local($path));
    }

    /**
     * Get the contents of the passed file name.
     *
     * @param string $path
     */
    function get($path)
    {
        $local = $this->adapter->local($path);
        clearstatcache(true, $local);
        if (is_file($local)) {
            return file_get_contents($local);
        }
        return null;
    }

    /**
     * List the files inside the passed file name.
     *
     * @param string $path
     */
    function ls($path = '')
    {
        $ret = $this->adapter->exec('status', '-uall', $path, '--porcelain');
        return $this->parsePorcelain($ret, $path);
    }

    /**
     * Remove a file name from the checkout
     *
     * @param string $path
     * @param string $message
     */
    function rm($path, $message)
    {
        $this->adapter->remove($path);
        $this->adapter->exec('commit', $path, '--message', $message);
    }

    /**
     * Create or overwrite a file with the passed contents.
     *
     * @param string $path
     * @param string $content
     * @param string $message
     */
    function put($path, $content, $message)
    {
        $this->adapter->create($path, $content);
        $this->adapter->exec('add', $this->adapter->local($path));
        $this->adapter->exec('commit', '--message', $message, $this->adapter->local($path));
    }

    /**
     * Push the changes to the remote.
     */
    function push()
    {
        if ($this->branch) {
            $this->adapter->exec('push', 'origin', $this->branch);
        } else {
            $this->adapter->exec('push');
        }
    }

    /**
     * Pull changes from the remote.
     */
    function pull()
    {
        if ($this->branch) {
            $this->adapter->exec('pull', 'origin', $this->branch);
        } else {
            $this->adapter->exec('pull');
        }
    }

    /**
     * Get the log of a path
     *
     * @param string $path
     * @param int $limit
     */
    function log($path, $limit = 10)
    {
        // TODO: Implement log() method.
    }

    /**
     * Get a commit message from the VCS's log
     *
     * @param string $commit
     * @param null $path
     */
    function getCommit($commit, $path = null)
    {
        // TODO: Implement getCommit() method.
    }


    function parsePorcelain($list, $parent = '')
    {
        $ret = array();
        $pattern = '!^(.)(.) ' . preg_quote($parent, '!') . '([^/]+)!';

        foreach (explode(PHP_EOL, $list) as $entry) {
            if (preg_match($pattern, $entry, $m)) {
                list($a, $m, $file) = $m;

                $ret[$file]= array(
                    'type' => 'foo',
                    'commit' => 'bar',
                    'author' => 'baz',
                    'date' => 'bat'
                );
            }
        }

        return $ret;
    }
}
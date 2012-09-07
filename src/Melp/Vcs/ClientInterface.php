<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs;

/**
 * The interface for a typical version control client.
 *
 * The terminology of git is used.
 */
interface ClientInterface {
    /**
     * Initialize the client with the passed remote url.
     *
     * @param $remote
     */
    function init($remote);


    /**
     * Create a branch of the initialized copy.
     *
     * @param string $name
     * @param bool $switch
     */
    function branch($name, $switch = true);


    /**
     * Checkout a branch with the passed name
     *
     * @param string $branch
     */
    function checkout($branch);


    /**
     * Create a tag with the passed name
     *
     * @param string $name
     */
    function tag($name);


    /**
     * Checks if the file with the passed name exists.
     *
     * @param string $path
     * @param string $type
     */
    function has($path, $type);


    /**
     * Get the contents of the passed file name.
     *
     * @param string $path
     */
    function get($path);


    /**
     * List the files inside the passed file name.
     *
     * @param string $path
     */
    function ls($path = '');


    /**
     * Remove a file name from the checkout
     *
     * @param string $path
     * @param string $message
     */
    function rm($path, $message);


    /**
     * Create or overwrite a file with the passed contents.
     *
     * @param string $path
     * @param string $content
     * @param string $message
     */
    function put($path, $content, $message);


    /**
     * Push the changes to the remote.
     */
    function push();


    /**
     * Pull changes from the remote.
     */
    function pull();


    /**
     * Get the log of a path
     *
     * @param string $path
     * @param int $limit
     */
    function log($path, $limit = 10);


    /**
     * Get a commit message from the VCS's log
     *
     * @param string $commit
     * @param null $path
     */
    function getCommit($commit, $path = null);
}
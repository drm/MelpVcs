<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Svn;

/**
 * Provides a common interface for adapters that implement SVN functionality
 */
interface AdapterInterface {
    /**
     * Excutes an SVN command with the given parameters.
     *
     * Example: $svn->exec('commit', 'dir/file.txt', '--message', 'w00t');
     *
     * @param string $command
     * @param null $args
     */
    function exec($command, $args = null);


    /**
     * Create a file in the working copy.
     *
     * @param string $file
     * @param string $contents
     */
    function create($file, $contents);


    /**
     * Initialize (setup) the repository connection with the passed URL as the remote SVN.
     *
     * @param $remote
     */
    function init($remote);


    /**
     * Destroy the working copy (if any)
     *
     * @abstract
     */
    function cleanup();
}
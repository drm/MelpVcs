<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */


namespace Melp\Vcs\Svn;

interface AdapterInterface {
    function exec($command, $args = null);
    function create($file, $contents);
    function init($remote);
    function cleanup();
}
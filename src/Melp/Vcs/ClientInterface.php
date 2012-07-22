<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */
namespace Melp\Vcs;

interface ClientInterface {
    function init($remote);
    function branch($name, $switch = true);
    function checkout($branch);
    function tag($name);
    function has($path, $type);
    function get($path);
    function ls($path = '');
    function rm($path, $message);
    function put($path, $content, $message);
    function push();
    function pull();
    function log($path);
    function getCommit($commit, $path = null);
}
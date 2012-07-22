<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */

namespace Melp\Vcs;

class RemoteSvn extends SvnAbstract
{
    protected $remote = null;

    function __construct(Svn\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }


    function rm($path, $message)
    {
        $this->svn('rm', $this->absUrl($path), '--message', $message);
    }

    function init($remote)
    {
        $this->remote = $remote;
    }


    function branch($name, $switch = true, $msg = 'Branched %s to %s')
    {
        $branchUrl = $this->getBranchUrl($name);
        $this->svn('cp', $this->remote, $branchUrl, '--message', $msg);
        if ($switch) {
            $this->remote = $branchUrl;
        }
    }


    function tag($name, $msg = "Tagged %s as %s")
    {
        $this->svn('cp', $this->remote, $this->getTagUrl($name), '--message', $msg);
    }


    function get($path)
    {
        try {
            return $this->svn('cat', $this->absUrl($path));
        } catch(Svn\CommandFailedException $e) {
            return null;
        }
    }

    function ls($path = '')
    {
        $response = simplexml_load_string($this->svn('ls', '--xml', $path));
        return $this->parseLs($response);
    }

    function put($path, $content, $message)
    {
        $adapter = clone $this->adapter;
        $svn = new Svn($adapter);
        $svn->init($this->absUrl(dirname($path)));
        $svn->put(basename($path), $content, $message);
        $svn->push();
        $adapter->cleanup();
    }

    function push()
    {
        // noop, we're working remotely
    }

    function pull()
    {
        // noop, we're working remotely
    }

    function checkout($branch)
    {
        if (is_null($branch)) {
            $this->remote = $this->getTrunkUrl();
        } else {
            $this->remote = $this->getBranchUrl($branch);
        }
    }

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


    protected function absUrl($path)
    {
        return rtrim($this->remote, '/') . '/' . ltrim($path, '/');
    }

}
<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */

namespace Melp\Vcs;

class Svn implements ClientInterface
{
    protected $remote;
    protected $messages = array();


    function __construct(Svn\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    function rm($path, $message)
    {
        $this->svn('rm', $path);
        $this->messages[]= $message;
    }


    function init($remote)
    {
        $this->remote = $remote;
        $this->adapter->init($remote);
        $this->pull();
    }

    function branch($name, $switch = true, $msg = 'Branched %s to %s')
    {
        $branch = $this->getBranchUrl($name);
        $this->svn('cp', $this->remote, $branch, '--message', sprintf($msg, $this->remote, $branch));
        if ($switch) {
            $this->checkout($name);
        }
    }


    function checkout($branch)
    {
        if ($branch === null) {
            $remoteBranch = $this->getTrunkUrl();
        } else {
            $remoteBranch = $this->getBranchUrl($branch);
        }
        $this->svn('switch', $remoteBranch);
    }


    function getBranchUrl($name)
    {
        return $this->getPseudoRoot() . '/branches/' . $name;
    }


    function getTagUrl($name)
    {
        return $this->getPseudoRoot() . '/tags/' . $name;
    }


    function getTrunkUrl()
    {
        return $this->getPseudoRoot() . '/trunk';
    }

    static function splitUrl($url, $part = null)
    {
        if (!preg_match('~(.*)((branches|tags)/[^/]+|trunk)/?$~', $url, $m)) {
            throw new \UnexpectedValueException("Can not find pseudo root for url {$url}");
        }
        $ret = array(rtrim($m[1], '/'), $m[2]);
        if (null !== $part) {
            $ret = $ret[$part];
        }
        return $ret;
    }

    private function getPseudoRoot()
    {
        return self::splitUrl($this->remote, 0);
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


    function get($path)
    {
        try {
            return $this->svn('cat', $path);
        } catch(Svn\CommandFailedException $e) {
            return null;
        }
    }

    function ls($path = '')
    {
        $ret = array();
        foreach (simplexml_load_string($this->svn('ls', '--xml', $path))->list as $list) {
            foreach ($list as $entry) {
                $ret[(string)$entry->name]= array(
                    'type' => (string)$entry['kind'],
                    'commit' => (string)$entry->commit['revision'],
                    'author' => (string)$entry->commit->author,
                    'date' => new \DateTime((string)$entry->commit->date)
                );
            }
        }
        return $ret;
    }

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


    function has($path, $type) {
        try {
            $info = simplexml_load_string($this->adapter->exec('info', '--xml', $path));
            return (string)($info->entry[0]['kind']) == $type;
        } catch (Svn\CommandFailedException $e) {
            return false;
        }
    }


    function mkdir($dir)
    {
        $this->svn('mkdir', $dir);
    }


    function push()
    {
        $this->svn('commit', '--message', implode("\n", $this->messages));
    }


    function pull()
    {
        $this->svn('update');
    }


    function log($path, $limit = 10)
    {
        $ret = array();
        if ($limit) {
            $data = $this->svn('log', '--xml', $path, '--limit', $limit);
        } else {
            $data = $this->svn('log', '--xml', $path);
        }
        if ($data) {
            foreach (simplexml_load_string($data)->logentry as $entry) {
                $ret[]= array(
                    'commit' => (string)$entry['revision'],
                    'author' => (string)$entry->author,
                    'date' => new \DateTime((string)$entry->date),
                    'message' => (string)$entry->msg
                );
            }
        }
        return $ret;
    }

//
//    function getCommit($commit) {
//        if ($entry = simplexml_load_string($this->adapter->exec('log', '-c' . $commit))) {
//            return array(
//                'commit' => $entry['revision'],
//                'author' => $entry->author,
//                'date' => new \DateTime((string)$entry->date),
//                'message' => $entry->msg
//            );
//        }
//        return null;
//    }



    protected function svn() {
        return call_user_func_array(
            array($this->adapter, 'exec'),
            func_get_args()
        );
    }
}
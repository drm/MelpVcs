<?php

namespace Melp\Vcs;

abstract class SvnAbstract implements ClientInterface
{
    protected $remote;

    function __construct(Svn\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    function getCommit($commit, $path = null) {
        $entry = simplexml_load_string($this->adapter->exec('log', '-c' . $commit, '--xml', $this->remote . '/' . $path));
        if ($entry = $entry->logentry) {
            return array(
                'commit' => (string)$entry['revision'],
                'author' => (string)$entry->author,
                'date' => new \DateTime((string)$entry->date),
                'message' => (string)$entry->msg
            );
        }
        return null;
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


    private function getPseudoRoot()
    {
        return self::splitUrl($this->remote, 0);
    }


    protected function svn()
    {
        return call_user_func_array(
            array($this->adapter, 'exec'),
            func_get_args()
        );
    }


    public function parseLs($response)
    {
        $ret = array();
        foreach ($response->list as $list) {
            foreach ($list as $entry) {
                $ret[(string)$entry->name] = array(
                    'type' => (string)$entry['kind'],
                    'commit' => (string)$entry->commit['revision'],
                    'author' => (string)$entry->commit->author,
                    'date' => new \DateTime((string)$entry->commit->date)
                );
            }
        }
        return $ret;
    }

    public function parseLog($response)
    {
        $ret = array();
        foreach ($response->logentry as $entry) {
            $ret[] = array(
                'commit' => (string)$entry['revision'],
                'author' => (string)$entry->author,
                'date' => new \DateTime((string)$entry->date),
                'message' => (string)$entry->msg
            );
        }
        return $ret;
    }
}
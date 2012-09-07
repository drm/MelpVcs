<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs;

/**
 * Common base class for the SVN implementations.
 */
abstract class SvnAbstract implements ClientInterface
{
    /**
     * Remote SVN url.
     *
     * @var string
     */
    protected $remote;

    /**
     * Initializes the interface with the passed adapter implementation to use for SVN communication
     *
     * @param Svn\AdapterInterface $adapter
     */
    function __construct(Svn\AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }


    /**
     * Returns commit details for the passed revision number.
     *
     * @param string $commit
     * @param string $path
     * @return mixed
     */
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


    /**
     * Helper function to split an SVN URL into the pseudo root and trunk, branch, or tag.
     *
     * Example:
     * svn://host/path/trunk will split into ['svn://host/path', 'trunk']
     * svn://host/path/branches/ticket123 will split into ['svn://host/path', 'branches/ticket123']
     *
     * The part parameter is a utility parameter to directly return the specified element in the array.
     *
     * @param string $url
     * @param null $part
     * @param int $part
     * @return mixed
     */
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


    /**
     * Returns the branch url for the specified branch name.
     *
     * @param string $name
     * @return string
     */
    function getBranchUrl($name)
    {
        return $this->getPseudoRoot() . '/branches/' . $name;
    }


    /**
     * Returns the tag url for the specified tag name.
     *
     * @param $name
     * @return string
     */
    function getTagUrl($name)
    {
        return $this->getPseudoRoot() . '/tags/' . $name;
    }


    /**
     * Returns the trunk url
     *
     * @return string
     */
    function getTrunkUrl()
    {
        return $this->getPseudoRoot() . '/trunk';
    }


    /**
     * Returns the pseudo root of the repository; i.e. the part before 'branches', 'trunk' or 'tags'.
     *
     * @return mixed
     */
    private function getPseudoRoot()
    {
        return self::splitUrl($this->remote, 0);
    }


    /**
     * Helper method to pass the svn command to the adapter.
     *
     * @return mixed
     */
    protected function svn()
    {
        return call_user_func_array(
            array($this->adapter, 'exec'),
            func_get_args()
        );
    }


    /**
     * Parses an XML response of the SVN client into a list of directory entries.
     *
     * @param \SimpleXMLElement $response
     * @return array
     */
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


    /**
     * Parses a log into a list of commits.
     *
     * @param \SimpleXMLElement $response
     * @return array
     */
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
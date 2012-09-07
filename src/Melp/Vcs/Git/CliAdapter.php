<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Git;

use \Symfony\Component\Process\Process;
use \Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * This adapter implements the GIT interface using a command line GIT client.
 */
class CliAdapter /*implements \Melp\Vcs\Git\AdapterInterface*/
{
    /**
     * Points to the Git binary on the local file system.
     *
     * @var string
     */
    public static $binary = '/usr/bin/git';

    /**
     * Global arguments added to all commands.
     *
     * @var array
     */
    protected $globalArgs = array();


    /**
     * Initialize the adapter with a local checkout root and a callback that will generate a name for the
     * local working copy.
     *
     * The arguments default to the system temp directory and a random name prefixed with 'melp_svn_'.
     *
     * @param string $checkoutRoot
     * @param string $workingCopyCallback
     */
    function __construct($checkoutRoot = null, $workingCopyCallback = null)
    {
        if (is_null($workingCopyCallback)) {
            $workingCopyCallback = function() {
                return 'melp_vcs_' . rand();
            };
        }
        $this->wd = $checkoutRoot ? : sys_get_temp_dir() . '/' . call_user_func($workingCopyCallback);
    }


//    /**
//     * Adds a username parameter to the global arguments.
//     *
//     * @param string $username
//     */
//    function setUsername($username)
//    {
//        $this->globalArgs[] = '--username';
//        $this->globalArgs[] = $username;
//    }
//
//
//    /**
//     * Adds a password parameter to the global arguments.
//     *
//     * @param string $password
//     */
//    function setPassword($password)
//    {
//        $this->globalArgs[] = '--password';
//        $this->globalArgs[] = $password;
//    }


    /**
     * Performs an svn command and passes it to an svn process.
     *
     * @param string $cmd
     * @param mixed $args
     * @return string
     * @throws CliAdapterException|\RuntimeException
     */
    function exec($cmd, $args = null)
    {
        $args = func_get_args();
        $commandLine = sprintf(
            '%s %s',
            escapeshellcmd(self::$binary),
            escapeshellarg(array_shift($args)) // command name
        );
        foreach ($args as $arg) {
            $commandLine .= ' ' . escapeshellarg($arg);
        }
        foreach ($this->globalArgs as $arg) {
            $commandLine .= ' ' . escapeshellcmd($arg);
        }
        $p = new Process($commandLine, $this->wd);
        if ($p->run()) {
            throw new CliAdapterException($p);
        } elseif ($message = $p->getErrorOutput()) {
            // TODO find out which messages would really constitute errors.
//            if (strpos($message, 'warning') === false) {
//                throw new \RuntimeException($message);
//            }
        }
        return $p->getOutput();
    }

    /**
     * Creates a filename in the working copy.
     *
     * @param string $file
     * @param string $contents
     */
    function create($file, $contents)
    {
        $this->_sane($file);
        $local = $this->local($file);
        if (!is_dir($dir = dirname($local))) {
            mkdir($dir, 0777 | umask(), true);
        }
        file_put_contents($local, $contents);
        $this->exec('add', $local);
    }


    function remove($file) {
        $local = $this->local($file);
        unlink($local);
        $this->exec('add', $local);
    }

    public function local($file)
    {
        return $this->wd . '/' . $file;
    }


    function get($file) {
        $this->_sane($file);
        $local = $this->local($file);
        return file_get_contents($local);
    }


    /**
     * Initialize the checkout directory
     *
     * @param $remote
     */
    function init($remote)
    {
        if (!is_dir($this->wd)) {
            $this->exec('clone', $remote, $this->wd);
        }
    }


    /**
     * Clear out the local checkout.
     */
    function cleanup()
    {
        if (is_dir($this->wd)) {
            shell_exec('rm -rf ' . $this->wd);
        }
    }


    /**
     * Does a sanity check on filenames.
     *
     * @param $file
     * @throws \RuntimeException
     */
    function _sane($file)
    {
        if (!preg_match('#..|~|^#', $file)) {
            throw new \RuntimeException("{$file} contains possibly insafe characters");
        }
    }


    /**
     * Cleans up on destruct.
     */
    function __destruct()
    {
        $this->cleanup();
    }
}
<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Svn;

use \Symfony\Component\Process\Process;
use \Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * This adapter implements the SVN interface using a command line SVN client.
 */
class CliAdapter implements \Melp\Vcs\Svn\AdapterInterface
{
    /**
     * Points to the SVN binary on the local file system.
     *
     * @var string
     */
    public static $binary = '/usr/bin/svn';

    /**
     * Global arguments added to all commands.
     *
     * @var array
     */
    protected $globalArgs = array(
        '--non-interactive'
    );


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
                return 'melp_svn_' . rand();
            };
        }
        $this->wd = $checkoutRoot ? : sys_get_temp_dir() . '/' . call_user_func($workingCopyCallback);
    }


    /**
     * Adds a username parameter to the global arguments.
     *
     * @param string $username
     */
    function setUsername($username)
    {
        $this->globalArgs[] = '--username';
        $this->globalArgs[] = $username;
    }


    /**
     * Adds a password parameter to the global arguments.
     *
     * @param string $password
     */
    function setPassword($password)
    {
        $this->globalArgs[] = '--password';
        $this->globalArgs[] = $password;
    }


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
            if (strpos($message, 'warning') === false) {
                throw new \RuntimeException($message);
            }
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
        if (!is_dir($dir = dirname($this->wd . '/' . $file))) {
            mkdir($dir, null, true);
        }
        file_put_contents($this->wd . '/' . $file, $contents);
    }


    /**
     * Initialize the checkout directory
     *
     * @param $remote
     */
    function init($remote)
    {
        if (!is_dir($this->wd)) {
            $this->exec('checkout', $remote, $this->wd, '--depth', 'immediates');
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
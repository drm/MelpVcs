<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */

namespace Melp\Vcs\Svn;

use \Symfony\Component\Process\Process;
use \Symfony\Component\Process\Exception\ProcessFailedException;

class CliAdapter implements \Melp\Vcs\Svn\AdapterInterface
{
    public static $binary = '/usr/bin/svn';

    protected $globalArgs = array(
        '--non-interactive'
    );

    function __construct($checkoutRoot = null, $workingCopyCallback = null)
    {
        if (is_null($workingCopyCallback)) {
            $workingCopyCallback = function() {
                return 'melp_svn_' . rand();
            };
        }
        $this->wd = $checkoutRoot ? : sys_get_temp_dir() . '/' . call_user_func($workingCopyCallback);
    }


    function setUsername($username)
    {
        $this->globalArgs[] = '--username';
        $this->globalArgs[] = $username;
    }


    function setPassword($password)
    {
        $this->globalArgs[] = '--password';
        $this->globalArgs[] = $password;
    }


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


    function create($file, $contents)
    {
        $this->_sane($file);
        if (!is_dir($dir = dirname($this->wd . '/' . $file))) {
            mkdir($dir, null, true);
        }
        file_put_contents($this->wd . '/' . $file, $contents);
    }


    function isVersioned($file)
    {
        var_dump($this->exec('info', $file));
    }


    function init($remote)
    {
        if (!is_dir($this->wd)) {
            $this->exec('checkout', $remote, $this->wd, '--depth', 'immediates');
        }
    }


    function cleanup()
    {
        if (is_dir($this->wd)) {
            shell_exec('rm -rf ' . $this->wd);
        }
    }


    function _sane($file)
    {
        if (!preg_match('#..|~|^#', $file)) {
            throw new \RuntimeException("{$file} contains possibly insafe characters");
        }
    }


    function __destruct()
    {
        $this->cleanup();
    }
}
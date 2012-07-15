<?php
namespace Melp\Vcs;

use \Symfony\Component\Process\Process;

class Svn implements ClientInterface
{
    public static $binary = '/usr/bin/svn';

    protected $username;
    protected $password;
    protected $remote;
    protected $messages = array();
    protected $globalOpts;

    function __construct($checkoutRoot = null, $workingCopyCallback = 'rand')
    {
        $this->wd = $checkoutRoot ?: sys_get_temp_dir() . '/' . call_user_func($workingCopyCallback);
    }


    function setUsername($username)
    {
        $this->globalOpts[]= array('--username', $username);
    }


    function setPassword($password)
    {
        $this->globalOpts[]= array('--password', $password);
    }

    function rm($path, $message)
    {
        $this->svn('rm', $this->local($path));
        $this->messages[]= $message;
    }


    function init($remote)
    {
        $this->remote = $remote;
        $this->pull();
    }

    function branch($name)
    {
        throw new \RuntimeException("Not implemented");
    }

    function tag($name)
    {
        throw new \RuntimeException("Not implemented");
    }

    function get($path)
    {
        return file_get_contents($this->local($path));
    }

    function ls($path)
    {
        return $this->svn('ls', $this->local($path));
    }

    function put($path, $content, $message)
    {
        if (!is_dir($this->local(dirname($path)))) {
            $this->mkdir($this->local(dirname($path)));
        }
        file_put_contents($this->local($path), $content);
        $this->svn('add', $this->local($path));
        $this->messages[]= $message;
    }


    function mkdir($dir)
    {
        $this->svn('mkdir', $dir);
    }


    function push()
    {
        $this->svn('commit', $this->local(), '--message', implode("\n", $this->messages));
    }


    function pull()
    {
        if (!is_dir($this->wd)) {
            $this->svn('checkout', $this->remote, $this->wd);
        } else {
            $this->svn('update', $this->wd);
        }
    }

    function __destruct()
    {
        if (is_dir($this->remote)) {
            shell_exec('rm -rf ' . $this->remote);
        }
    }


    function log($path)
    {
        return $this->svn('log', $this->local($path));
    }


    function local($path = '')
    {
        return $this->wd . ($path ? '/' . $path : '');
    }


    function remote($path = '')
    {
        return $this->remote . ($path ? '/' . $path : '');
    }



    protected function svn($cmd) {
        $args = func_get_args();
        $commandLine = sprintf(
            '%s %s',
            escapeshellcmd(self::$binary),
            escapeshellarg(array_shift($args)) // command name
        );
        foreach ($args as $arg) {
            $commandLine .= ' ' . escapeshellarg($arg);
        }
        $p = new Process($commandLine);
        if ($p->run(function($type, $data) {
            var_dump($data);
        })) {
            throw new \Symfony\Component\Process\Exception\ProcessFailedException($p);
        }
        return $p->getOutput();
    }
}
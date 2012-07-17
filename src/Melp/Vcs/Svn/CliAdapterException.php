<?php
/**
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright Gerard van Helden
 */


namespace Melp\Vcs\Svn;

use \Symfony\Component\Process\Exception\ProcessFailedException;

class CliAdapterException extends \Symfony\Component\Process\Exception\ProcessFailedException implements CommandFailedException {

}
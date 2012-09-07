<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace Melp\Vcs\Svn;

use \Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Adapter combining the CommandFailedException interface and ProcessFailedException
 */
class CliAdapterException extends \Symfony\Component\Process\Exception\ProcessFailedException
    implements CommandFailedException {
}
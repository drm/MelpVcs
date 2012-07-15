<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gerard
 * Date: 7/15/12
 * Time: 5:07 PM
 * To change this template use File | Settings | File Templates.
 */

interface LogMessageInterface {
    function getUser();
    function getComment();
    function getCommitId();
}
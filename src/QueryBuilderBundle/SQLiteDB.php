<?php
/**
 * Created by PhpStorm.
 * User: canngo
 * Date: 8/19/18
 * Time: 1:39 PM
 */

namespace QueryBuilderBundle;


class SQLiteDB extends \SQLite3
{
    function __construct($dbPath)
    {
        $this->open($dbPath);
    }

}
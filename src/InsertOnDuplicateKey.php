<?php

namespace Yadakhov;

use Illuminate\Support\Facades\DB;

trait InsertOnDuplicateKey
{
    /**
     * Static function for getting table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        $class = get_called_class();

        return (new $class())->getTable();
    }

    /**
     * Static function for getting the primary key.
     *
     * @return string
     */
    public static function getPrimaryKey()
    {
        $class = get_called_class();

        return (new $class())->getKeyName();
    }

    /**
     * Insert using mysql on duplicate key update.
     * @link http://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html
     *
     * @param array $data Associative array. Must have the id column.
     *
     * @return bool
     */
    public static function insertOnDuplicateKey(array $data)
    {
        if (empty($data)) {
            return false;
        }

        // Check to make sure $data contains the primary key
        $primaryKey = static::getPrimaryKey();
        $hasKey = false;

        list($first) = $data;

        if (!is_array($first)) {
            throw new \InvalidArgumentException('Not an associative array.');
        }

        foreach (array_keys($first) as $key) {
            if ($key === $primaryKey) {
                $hasKey = true;
                break;
            }
        }

        if ($hasKey === false) {
            throw new \InvalidArgumentException('Missing primary key in the data: ' . $primaryKey);
        }

        $sql = static::buildSql($data);

        $data = static::inLineArray($data);

        return DB::statement($sql, $data);
    }


    /**
     * Build the question mark placeholder.  Helper function for insertOnDuplicateKeyUpdate().
     * Helper function for insertOnDuplicateKeyUpdate().
     *
     * @param $data
     *
     * @return string
     */
    protected static function buildPlaceHolder($data)
    {
        $lines = [];
        foreach ($data as $row) {
            $count = count($row);
            $questions = [];
            for ($i = 0; $i < $count; ++$i) {
                $questions[] = '?';
            }
            $lines[] = '(' . implode(',', $questions) . ')';
        }

        return implode(', ', $lines);
    }

    /**
     * Build a value list.
     *
     * @param array $data
     *
     * @return string
     */
    protected static function getColumnList(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Empty data.');
        }

        list($first) = $data;

        if (!is_array($first)) {
            throw new \InvalidArgumentException('Not an associative array.');
        }

        return '`' . implode('`,`', array_keys($first)) . '`';
    }

    /**
     * Build a value list.
     *
     * @param array $data
     *
     * @return string
     */
    protected static function buildValuesList(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Empty data.');
        }

        list($first) = $data;

        if (!is_array($first)) {
            throw new \InvalidArgumentException('Not an associative array.');
        }

        $out = [];

        foreach (array_keys($first) as $key) {
            $out[] = sprintf('`%s` = VALUES(`%s`)', $key, $key);
        }

        return implode(', ', $out);
    }

    /**
     * Inline a multiple dimension array.  Helper function for insertOnDuplicateKeyUpdate().
     *
     * @param $data
     *
     * @return array
     */
    protected static function inLineArray(array $data)
    {
        $out = [];

        foreach ($data as $row) {
            foreach ($row as $item) {
                $out[] = $item;
            }
        }

        return $out;
    }

    protected static function buildSql(array $data)
    {
        $sql  = 'INSERT INTO `' .  static::getTableName() . '`(' . static::getColumnList($data) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildPlaceHolder($data) . PHP_EOL;
        $sql .= 'ON DUPLICATE KEY UPDATE ' . static::buildValuesList($data);

        return $sql;
    }
}

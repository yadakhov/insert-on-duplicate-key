<?php

namespace Yadakhov;

use Illuminate\Support\Facades\DB;

trait InsertOnDuplicateKey
{
    /**
     * Insert using mysql ON DUPLICATE KEY UPDATE.
     * @link http://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html
     *
     * Example:  $data = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Mike'],
     * ];
     *
     * @param array $data is an array of array.
     * @param array $updateColumns NULL or empty[] means update all columns
     *
     * @return bool
     */
    public static function insertOnDuplicateKey(array $data, array $updateColumns = null)
    {
        if (empty($data)) {
            return false;
        }

        // Case where $data is not an array of arrays.
        if (!isset($data[0])) {
            $data = [$data];
        }

        static::checkPrimaryKeyExists($data);

        $sql = static::buildInsertOnDuplicateSql($data, $updateColumns);

        $data = static::inLineArray($data);

        return DB::statement($sql, $data);
    }

    /**
     * Insert using mysql INSERT IGNORE INTO.
     *
     * @param array $data
     *
     * @return bool
     */
    public static function insertIgnore(array $data)
    {
        if (empty($data)) {
            return false;
        }

        // Case where $data is not an array of arrays.
        if (!isset($data[0])) {
            $data = [$data];
        }

        static::checkPrimaryKeyExists($data);

        $sql = static::buildInsertIgnoreSql($data);

        $data = static::inLineArray($data);

        return DB::statement($sql, $data);
    }

    /**
     * Insert using mysql REPLACE INTO.
     *
     * @param array $data
     *
     * @return bool
     */
    public static function replace(array $data)
    {
        if (empty($data)) {
            return false;
        }

        // Case where $data is not an array of arrays.
        if (!isset($data[0])) {
            $data = [$data];
        }

        static::checkPrimaryKeyExists($data);

        $sql = static::buildReplaceSql($data);

        $data = static::inLineArray($data);

        return DB::statement($sql, $data);
    }

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
     * Build the question mark placeholder.  Helper function for insertOnDuplicateKeyUpdate().
     * Helper function for insertOnDuplicateKeyUpdate().
     *
     * @param $data
     *
     * @return string
     */
    protected static function buildQuestionMarks($data)
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
     * Get the first row of the $data array.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected static function getFirstRow(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Empty data.');
        }

        list($first) = $data;

        if (!is_array($first)) {
            throw new \InvalidArgumentException('$data is not an array of array.');
        }

        return $first;
    }

    /**
     * Check to make sure the first row as the primary key.
     * Every row needs to have the primary key but we will only check the first row for efficiency.
     *
     * @param array $data
     */
    protected static function checkPrimaryKeyExists(array $data)
    {
        // Check to make sure $data contains the primary key
        $primaryKey = static::getPrimaryKey();
        $hasKey = false;

        $first = static::getFirstRow($data);

        foreach (array_keys($first) as $key) {
            if ($key === $primaryKey) {
                $hasKey = true;
                break;
            }
        }

        if ($hasKey === false) {
            throw new \InvalidArgumentException(sprintf('Missing primary key %s.', $primaryKey));
        }
    }

    /**
     * Build a value list.
     *
     * @param array $first
     *
     * @return string
     */
    protected static function getColumnList(array $first)
    {
        if (empty($first)) {
            throw new \InvalidArgumentException('Empty array.');
        }

        return '`' . implode('`,`', array_keys($first)) . '`';
    }

    /**
     * Build a value list.
     *
     * @param array $first
     *
     * @return string
     */
    protected static function buildValuesList(array $first)
    {
        $out = [];

        foreach (array_keys($first) as $key) {
            $out[] = sprintf('`%s` = VALUES(`%s`)', $key, $key);
        }

        return implode(', ', $out);
    }

    /**
     * Inline a multiple dimensions array.
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

    /**
     * Build the INSERT ON DUPLICATE KEY sql statement.
     *
     * @param array $data
     * @param array $updateColumns
     *
     * @return string
     */
    protected static function buildInsertOnDuplicateSql(array $data, array $updateColumns = null)
    {
        $first = static::getFirstRow($data);

        $sql  = 'INSERT INTO `' .  static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildQuestionMarks($data) . PHP_EOL;
        $sql .= 'ON DUPLICATE KEY UPDATE ';

        if (empty($updateColumns)) {
            $sql .= static::buildValuesList($first);
        } else {
            $sql .= static::buildValuesList(array_combine($updateColumns, $updateColumns));
        }

        return $sql;
    }

    /**
     * Build the INSERT IGNORE sql statement.
     *
     * @param array $data
     *
     * @return string
     */
    protected static function buildInsertIgnoreSql(array $data)
    {
        $first = static::getFirstRow($data);

        $sql  = 'INSERT IGNORE INTO `' .  static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildQuestionMarks($data);

        return $sql;
    }

    /**
     * Build REPLACE sql statement.
     *
     * @param array $data
     *
     * @return string
     */
    protected static function buildReplaceSql(array $data)
    {
        $first = static::getFirstRow($data);

        $sql  = 'REPLACE INTO `' .  static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildQuestionMarks($data);

        return $sql;
    }
}

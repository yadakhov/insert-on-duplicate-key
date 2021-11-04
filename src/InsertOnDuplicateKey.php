<?php

namespace Yadakhov;

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
     * @param array $updateColumns NULL or [] means update all columns
     * @param array $dontEscapeColumns [] esapces all columns, or keys of which columns not to bind
     *
     * @return int 0 if row is not changed, 1 if row is inserted, 2 if row is updated
     */
    public static function insertOnDuplicateKey(array $data, array $updateColumns = null, array $dontEscapeColumns = [])
    {
        if (empty($data)) {
            return false;
        }

        // Case where $data is not an array of arrays.
        if (!isset($data[0])) {
            $data = [$data];
        }

        $sql = static::buildInsertOnDuplicateSql($data, $updateColumns, $dontEscapeColumns);

        $data = static::inLineArray($data, $dontEscapeColumns);

        return self::getModelConnectionName()->affectingStatement($sql, $data);
    }

    /**
     * Insert using mysql INSERT IGNORE INTO.
     *
     * @param array $data
     *
     * @return int 0 if row is ignored, 1 if row is inserted
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

        $sql = static::buildInsertIgnoreSql($data);

        $data = static::inLineArray($data);

        return self::getModelConnectionName()->affectingStatement($sql, $data);
    }

    /**
     * Insert using mysql REPLACE INTO.
     *
     * @param array $data
     *
     * @return int 1 if row is inserted without replacements, greater than 1 if rows were replaced
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

        $sql = static::buildReplaceSql($data);

        $data = static::inLineArray($data);

        return self::getModelConnectionName()->affectingStatement($sql, $data);
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
    * Static function for getting connection name
    *
    * @return string
    */
    public static function getModelConnectionName()
    {
        $class = get_called_class();

        return (new $class())->getConnection();
    }

    /**
     * Get the table prefix.
     *
     * @return string
     */
    public static function getTablePrefix()
    {
        return self::getModelConnectionName()->getTablePrefix();
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
    protected static function buildQuestionMarks($data, $dontEscapeColumns = [])
    {
        $lines = [];
        foreach ($data as $row) {
            $questions = [];
            foreach ($row as $key => $value) {
                if (in_array($key, $dontEscapeColumns)) {
                    $questions[] = $value;
                } else {
                    $questions[] = '?';
                }
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
     * @param array $updatedColumns
     *
     * @return string
     */
    protected static function buildValuesList(array $updatedColumns)
    {
        $out = [];

        foreach ($updatedColumns as $key => $value) {
            if (is_numeric($key)) {
                $out[] = sprintf('`%s` = VALUES(`%s`)', $value, $value);
            } else {
                $out[] = sprintf('%s = %s', $key, $value);
            }
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
    protected static function inLineArray(array $data, array $dontEscapeColumns = [])
    {
        $dataBindings = [];
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                if (in_array($key, $dontEscapeColumns)) {
                    continue;
                } else {
                    $dataBindings[] = $value;
                }
            }
        }

        return $dataBindings;
    }

    /**
     * Build the INSERT ON DUPLICATE KEY sql statement.
     *
     * @param array $data
     * @param array $updateColumns
     * @param array $dontEscapeColumns
     *
     * @return string
     */
    protected static function buildInsertOnDuplicateSql(array $data, array $updateColumns = null, array $dontEscapeColumns = [])
    {
        $first = static::getFirstRow($data);

        $sql  = 'INSERT INTO `' . static::getTablePrefix() . static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildQuestionMarks($data, $dontEscapeColumns) . PHP_EOL;

        $connection = config('database.default');
        if ($connection !== 'mysql') {
            return $sql;
        }

        $sql .= 'ON DUPLICATE KEY UPDATE ';

        if (empty($updateColumns)) {
            $sql .= static::buildValuesList(array_keys($first));
        } else {
            $sql .= static::buildValuesList($updateColumns);
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

        $sql  = 'INSERT IGNORE INTO `' . static::getTablePrefix() . static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
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

        $sql  = 'REPLACE INTO `' . static::getTablePrefix() . static::getTableName() . '`(' . static::getColumnList($first) . ') VALUES' . PHP_EOL;
        $sql .=  static::buildQuestionMarks($data);

        return $sql;
    }
}

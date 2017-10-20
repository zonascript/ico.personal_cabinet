<?php

use Xcart\App\Main\Xcart;

/**
 * @param $query
 *
 * @return \Doctrine\DBAL\Driver\Statement|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function db_query($query)
{
    return Xcart::app()->db->getConnection()->executeQuery($query);
}

/**
 * @param $query        "UPDATE table_test set val1 = :val1, va2 = :val2 where id = :id"
 * @param array $params ['val1' => $val1, 'val2' => $val2, 'id' => $id]
 * @param array $types  \PDO Param type [\PDO::PARAM_NULL, \PDO::PARAM_SRT, \PDO::PARAM_INT]
 *
 * @return \Doctrine\DBAL\Driver\Statement|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function db_query_param($query, array $params, array $types = [])
{
    list($query, $params) = db_prepare_params($query, $params);

    return Xcart::app()->db->getConnection()->executeQuery($query, $params, $types);
}

function db_result(\Doctrine\DBAL\Driver\Statement $result, $offset)
{
    return db_fetch_field($result, $offset);
}

function db_fetch_row(\Doctrine\DBAL\Driver\Statement $result)
{
    return $result->fetch(PDO::FETCH_NUM);
}

function db_fetch_array(\Doctrine\DBAL\Driver\Statement $result, $flag = null)
{
    return $result->fetch(PDO::FETCH_ASSOC);
}

function db_fetch_field(\Doctrine\DBAL\Driver\Statement $result, $num = 0)
{
    return $result->fetchColumn($num);
}

function db_free_result(\Doctrine\DBAL\Driver\Statement $result)
{
    if ($result && $result instanceof \Doctrine\DBAL\Driver\Statement) {
        $result->closeCursor();
    }
}

function db_num_rows(\Doctrine\DBAL\Driver\Statement $result)
{
    return $result->rowCount();
}

function db_num_fields(\Doctrine\DBAL\Driver\Statement $result)
{
    return $result->columnCount();
}

function db_insert_id()
{
    return Xcart::app()->db->getConnection()->lastInsertId();
}

function db_affected_rows(\Doctrine\DBAL\Driver\Statement $result)
{
    return $result->rowCount();
}

function db_mysql_get_server_info()
{
    return Xcart::app()->db->getConnection()->getWrappedConnection()->getServerVersion();
}

function db_prepare_params($query, array $params = [])
{
    if (!empty($params)) {
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                $t_keys = [];
                unset($params[$key]);
                foreach ($param as $p => $value) {
                    $k = $key . '___' . $p;

                    $params[$k] = $value;
                    $t_keys[] = ':' . $k;
                }

                $keys = implode(', ', $t_keys);
                $query = str_replace(':' . $key, $keys, $query);
            }
        }
    }

    return [$query, $params];
}

function db_error_generic($query, $query_error, $msg)
{
    global $debug_mode, $config, $xcart_dir;

    $email = false;

    if (@$config["Email_Note"]["admin_sqlerror_notify"] == "Y") {
        $email = [$config["Company"]["site_administrator"]];
    }

    if ($debug_mode == 1 || $debug_mode == 3) {
        echo "<b><font COLOR=DARKRED>INVALID SQL: </font></b>" . htmlspecialchars($query_error) . "<br />";
        echo "<b><font COLOR=DARKRED>SQL QUERY FAILURE:</font></b>" . htmlspecialchars($query) . "<br />";
        flush();
    }

    $do_log = ($debug_mode == 2 || $debug_mode == 3);

    if ($email !== false || $do_log) {
        @require_once $xcart_dir . "/include/logging.php";
        x_log_add('SQL', $msg, true, 1, $email, !$do_log);
    }
}

function db_prepare_query($query, $params)
{
    static $prepared = [];

    if (!empty($prepared[$query])) {
        $info = $prepared[$query];
        $tokens = $info['tokens'];
    }
    else {
        $tokens = preg_split('/((?<!\\\)\?)/S', $query, -1, PREG_SPLIT_DELIM_CAPTURE);

        $count = 0;
        foreach ($tokens as $k => $v) {
            if ($v === '?') $count++;
        }

        $info = [
            'tokens' => $tokens,
            'param_count' => $count,
        ];
        $prepared[$query] = $info;
    }

    if (count($params) != $info['param_count']) {
        return [
            'info' => 'mismatch',
            'expected' => $info['param_count'],
            'actual' => count($params),
        ];
    }

    $pos = 0;
    foreach ($tokens as $k => $val) {
        if ($val !== '?') continue;

        if (!isset($params[$pos])) {
            return [
                'info' => 'missing',
                'param' => $pos,
                'expected' => $info['param_count'],
                'actual' => count($params),
            ];
        }

        $val = $params[$pos];
        if (is_array($val)) {
            $val = func_array_map('addslashes', $val);
            $val = implode("','", $val);
        }
        else {
            $val = addslashes($val);
        }

        $tokens[$k] = "'" . $val . "'";
        $pos++;
    }

    return implode('', $tokens);
}

/**
 *
 * New DB API: Executing parameterized queries
 * Example1:
 *   $query = "SELECT * FROM table WHERE field1=? AND field2=? AND field3='\\?'"
 *   $params = array (val1, val2)
 *   query to execute:
 *      "SELECT * FROM table WHERE field1='val1' AND field2='val2' AND field3='\\?'"
 * Example2:
 *   $query = "SELECT * FROM table WHERE field1=? AND field2 IN (?)"
 *   $params = array (val1, array(val2,val3))
 *   query to execute:
 *      "SELECT * FROM table WHERE field1='val1' AND field2 IN ('val2','val3')"
 *
 * Warning:
 *  1) all parameters must not be escaped with addslashes()
 *  2) non-parameter symbols '?' must be escaped with a '\'
 *
 * @param $query
 * @param array $params
 *
 * @return \Doctrine\DBAL\Driver\Statement|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function db_exec($query, $params = [])
{
    if (!is_array($params)) {
        $params = [$params];
    }

    return db_query(db_prepare_query($query, $params));
}

/**
 * @param $query
 * @param array $params
 * @param array $types
 *
 * @return \Doctrine\DBAL\Driver\Statement|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function db_exec_param($query, array $params, array $types = [])
{
    return db_query_param($query, $params, $types);
}

/**
 * Execute mysql query and store result into associative array with
 * column names as keys
 *
 * @param $query
 *
 * @return array|bool
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function func_query($query)
{
    $result = false;

    if ($p_result = db_query($query)) {
        while ($arr = db_fetch_array($p_result)) {
            $result[] = $arr;
        }
        db_free_result($p_result);
    }

    return $result;
}

/**
 * @param $query        "SELECT table_test where id = :id"
 * @param array $params ['id' => $id]
 * @param array $types  \PDO Param type [\PDO::PARAM_NULL, \PDO::PARAM_SRT, \PDO::PARAM_INT]
 *
 * @return array|bool
 * @throws \Doctrine\DBAL\DBALException
 */
function func_query_param($query, array $params, array $types = [])
{
    $result = false;

    if ($p_result = db_query_param($query, $params, $types)) {
        while ($arr = db_fetch_array($p_result)) {
            $result[] = $arr;
        }
        db_free_result($p_result);
    }

    return $result;
}

/**
 *  Execute mysql query and store result into associative array with
 * column names as keys and then return first element of this array
 * If array is empty return array().
 *
 * @param $query
 *
 * @return array|mixed
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function func_query_first($query)
{
    if ($p_result = db_query($query)) {
        $result = db_fetch_array($p_result);
        db_free_result($p_result);
    }

    return is_array($result) ? $result : [];
}

/**
 * @param $query
 * @param array $param
 * @param array $types
 *
 * @return array|mixed
 * @throws \Doctrine\DBAL\DBALException
 *
 */
function func_query_first_param($query, array $param, array $types = [])
{
    $result = [];

    if ($p_result = db_query_param($query, $param, $types)) {
        $result = db_fetch_array($p_result);
        db_free_result($p_result);
    }

    return is_array($result) ? $result : [];
}

/**
 * @param $query
 *
 * @return bool|mixed
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated use func_query_first_cell_param
 */
function func_query_first_cell($query)
{
    if ($p_result = db_query($query)) {
        $result = db_fetch_row($p_result);
        db_free_result($p_result);
    }

    return is_array($result) ? $result[0] : false;
}

/**
 * @param $query
 * @param array $params
 * @param array $types
 *
 * @return bool|mixed
 * @throws \Doctrine\DBAL\DBALException
 */
function func_query_first_cell_param($query, array $params, array $types = [])
{
    if ($p_result = db_query_param($query, $params, $types)) {
        $result = db_fetch_row($p_result);
        db_free_result($p_result);
    }

    return is_array($result) ? $result[0] : false;
}

/**
 * @param $query
 * @param int $column
 *
 * @return array
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated use func_query_column_param
 */
function func_query_column($query, $column = 0)
{
    $result = [];

    $fetch_func = is_int($column) ? 'db_fetch_row' : 'db_fetch_array';

    if ($p_result = db_query($query)) {
        while ($row = $fetch_func($p_result)) {
            $result[] = $row[$column];
        }

        db_free_result($p_result);
    }

    return $result;
}

/**
 * @param $query
 * @param array $params
 * @param array $types
 * @param int $column
 *
 * @return array
 * @throws \Doctrine\DBAL\DBALException
 */
function func_query_column_param($query, array $params, array $types = [], $column = 0)
{
    $result = [];

    $fetch_func = is_int($column) ? 'db_fetch_row' : 'db_fetch_array';

    if ($p_result = db_query_param($query, $params, $types)) {
        while ($row = $fetch_func($p_result)) {
            $result[] = $row[$column];
        }

        db_free_result($p_result);
    }

    return $result;
}

/**
 * Insert array data to table
 *
 * @param $tbl
 * @param $arr
 * @param bool $is_replace
 * @param bool $is_ignore
 *
 * @return bool|string
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function func_array2insert($tbl, $arr, $is_replace = false, $is_ignore = false)
{
    global $sql_tbl;

    if (empty($tbl) || empty($arr) || !is_array($arr)) {
        return false;
    }

    if (!empty($sql_tbl[$tbl])) {
        $tbl = $sql_tbl[$tbl];
    }

    if ($is_replace) {
        $query = "REPLACE";
    }
    else {
        $query = "INSERT";
    }

    if ($is_ignore) {
        $query = "INSERT IGNORE";
    }

    if (!func_check_tbl_fields($tbl, array_keys($arr))) {
        return null;
    }

    $arr_keys = array_keys($arr);
    foreach ($arr_keys as $k => $v) {
        if (!preg_match("/^`.*`$/", $v)) $arr_keys[$k] = "`$v`";
    }

    $arr_values = array_values($arr);
    foreach ($arr_values as $k => $v) {
        if ((!preg_match("/^'.*'$/", $v)) && (!preg_match('/^".*"$/', $v))) $arr_values[$k] = "'$v'";
    }

    $query .= " INTO $tbl (" . implode(", ", $arr_keys) . ") VALUES (" . implode(", ", $arr_values) . ")";

    $r = db_query($query);
    if ($r) {
        return db_insert_id();
    }

    return false;
}

/**
 * Insert array data to table
 *
 * @param string $tbl Table name
 * @param array $data Array to insert date
 * @param bool $is_replace
 * @param bool $is_ignore
 *
 * @return bool|null|string
 * @throws \Doctrine\DBAL\DBALException
 */
function func_array2insert_new($tbl, array $data, $is_replace = false, $is_ignore = false)
{
    global $sql_tbl;

    if (empty($data) || empty($data) || !is_array($data)) {
        return false;
    }

    if (!empty($sql_tbl[$tbl])) {
        $tbl = $sql_tbl[$tbl];
    }

    if ($is_replace) {
        $query = "REPLACE";
    }
    else {
        $query = "INSERT";
    }

    if ($is_ignore) {
        $query = "INSERT IGNORE";
    }

    if (!func_check_tbl_fields($tbl, array_keys($data))) {
        return null;
    }

//    $data = func_init_default_values($tbl, $data);

    $connection = \Xcart\Connection::getInstance();

    $columnList = [];
    $paramPlaceholders = [];
    $paramValues = [];
    $paramTypes = [];

    foreach ($data as $columnName => $value) {
        $columnList[] = $columnName;
        $paramPlaceholders[] = '?';
        $paramValues[] = (empty($value) && $value !== 0) ? '' : $value;
        $paramTypes[] = func_get_sql_type($value);
    }

    $sql = $query . ' INTO ' . $tbl . ' (' . implode(', ', $columnList) . ')' .
           ' VALUES (' . implode(', ', $paramPlaceholders) . ')';

//    $r = $connection->executeUpdate($sql, $paramValues);
    $r = $connection->executeUpdate($sql, $paramValues, $paramTypes);

    if ($r) {
        return $connection->lastInsertId();
    }

    return null;
}

/**
 * Update array data to table + where statament
 *
 * @param $tbl
 * @param $arr
 * @param string $where
 *
 * @return bool|\Doctrine\DBAL\Driver\Statement|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function func_array2update($tbl, $arr, $where = '')
{
    global $sql_tbl;

    if (empty($tbl) || empty($arr) || !is_array($arr)) {
        return false;
    }

    if ($sql_tbl[$tbl]) {
        $tbl = $sql_tbl[$tbl];
    }

    $r = [];
    foreach ($arr as $k => $v) {
        if (!preg_match("/^`.*`$/", $k)) $k = "`$k`";
        if ((!preg_match("/^'.*'$/", $v)) && (!preg_match('/^".*"$/', $v))) $v = "'$v'";
        $r[] = "$k=$v";
    }

    func_check_tbl_fields($tbl, array_keys($arr));

    $query = "UPDATE $tbl SET " . implode(", ", $r) . ($where ? " WHERE " . $where : "");

    return db_query($query);
}

/**
 * Update array data to table + where statament
 *
 * @param string $tbl         Table name
 * @param array $data         Data to update
 * @param string|array $where string statimern or associative array - implement as col AND col
 *
 * @return bool|int|mixed|null
 * @throws \Doctrine\DBAL\DBALException
 */
function func_array2update_new($tbl, $data, $where = '')
{
    global $sql_tbl;

    if (empty($data) || empty($data) || !is_array($data)) {
        return false;
    }

    if ($sql_tbl[$tbl]) {
        $tbl = $sql_tbl[$tbl];
    }

    if (!func_check_tbl_fields($tbl, array_keys($data))) {
        return null;
    }

    $columnList = [];
    $set = [];
    $criteria = [];
    $paramValues = [];
    $paramTypes = [];

    foreach ($data as $columnName => $value) {
        $columnList[] = $columnName;
        $set[] = $columnName . ' = ?';
        $paramValues[] = $value;
        $paramTypes[] = func_get_sql_type($value);
    }

    if (!empty($where) && is_array($where)) {
        foreach ($where as $columnName => $value) {
            $columnList[] = $columnName;
            $criteria[] = $columnName . ' = ?';
            $paramValues[] = (empty($value) && $value !== 0) ? '' : $value;
            $paramTypes[] = func_get_sql_type($value);
        }

        $sql = 'UPDATE ' . $tbl . ' SET ' . implode(', ', $set)
               . ' WHERE ' . implode(' AND ', $criteria);
    }
    else {
        $sql = 'UPDATE ' . $tbl . ' SET ' . implode(', ', $set)
               . ($where ? " WHERE " . $where : "");
    }

//    return Xcart::app()->db->getConnection()->executeUpdate($sql, $paramValues);
    return Xcart::app()->db->getConnection()->executeUpdate($sql, $paramValues, $paramTypes);
}

function func_get_sql_type($value)
{
    if (is_null($value)) {
        return \PDO::PARAM_NULL;
    }

    if (is_numeric($value)) {
        return \PDO::PARAM_INT;
    }

    return \PDO::PARAM_STR;
}

/**
 * @param $query
 * @param bool $column
 * @param bool $is_multirow
 * @param bool $only_first
 *
 * @return array
 * @throws \Doctrine\DBAL\DBALException
 * @deprecated
 */
function func_query_hash($query, $column = false, $is_multirow = true, $only_first = false)
{
    $result = [];
    $is_multicolumn = false;
    $is = null;

    if ($p_result = db_query($query)) {
        if ($column === false) {

            # Get first field name
            $c = db_fetch_field($p_result);
            $column = $c->name;
        }
        elseif (is_array($column)) {
            if (count($column) == 1) {
                $column = current($column);
            }
            else {
                $is_multicolumn = true;
            }
        }

        while ($row = db_fetch_array($p_result)) {

            # Get key(s) column value and remove this column from row
            if ($is_multicolumn) {

                $keys = [];
                foreach ($column as $c) {
                    $keys[] = $row[$c];
                    func_unset($row, $c);
                }
                $keys = implode('"]["', $keys);
            }
            else {
                $key = $row[$column];
                func_unset($row, $column);
            }

            if ($only_first) {
                $row = array_shift($row);
            }

            if ($is_multicolumn) {

                # If keys count > 1
                if ($is_multirow) {
                    eval('$result["' . $keys . '"][] = $row;');
                }
                else {
                    eval('$is = isset($result["' . $keys . '"]);');
                    if (!$is) {
                        eval('$result["' . $keys . '"] = $row;');
                    }
                }
            }
            elseif ($is_multirow) {
                $result[$key][] = $row;
            }
            elseif (!isset($result[$key])) {
                $result[$key] = $row;
            }
        }

        db_free_result($p_result);
    }

    return $result;
}

#
# Generate unique id
#  $type - one character
# Currently used types:
#  U - for users (anonymous)
#
function func_genid($type = 'U')
{
    global $sql_tbl;

    db_query_param("INSERT INTO $sql_tbl[counters] (type) VALUES (:type)", ['type' => $type]);
    $value = db_insert_id();

    if ($value < 1) {
        trigger_error("Cannot generate unique id", E_USER_ERROR);
    }

    db_query_param("DELETE FROM $sql_tbl[counters] WHERE type = :type AND value < :value", ['type' => $type, 'value' => $value]);

    return $value;
}

#
# Generate SQL-query relations
#
function func_generate_joins($joins, $parent = false)
{
    $str = '';

    foreach ($joins as $jname => $j) {
        if ((!empty($parent) && $parent != $j['parent']) || (empty($parent) && !empty($j['parent']))) {
            continue;
        }

        $str .= func_build_join($jname, $j);
        unset($joins[$jname]);

        list($js, $tmp) = func_generate_joins($joins, (empty($j['tblname']) ? $jname : $j['tblname']));
        $str .= $tmp;
        $keys = array_diff(array_keys($joins), array_keys($js));
        if (!empty($keys)) {
            foreach ($joins as $k => $v) {
                if (in_array($k, $keys)) {
                    unset($joins[$k]);
                }
            }
        }
    }

    if (empty($parent) && !empty($joins)) {
        foreach ($joins as $jname => $j) {
            $str .= func_build_join($jname, $j);
        }
        unset($joins);
    }

    if ($parent === false) {
        return $str;
    }
    else {
        return [$joins, $str];
    }
}

#
# Get [LEFT | INNER] JOIN string
#
function func_build_join($jname, $join)
{
    global $sql_tbl;

    $str = " " . ($join['is_inner'] ? "INNER" : "LEFT") . " JOIN ";
    if (!empty($join['tblname'])) {
        $str .= $sql_tbl[$join['tblname']] . " as " . $jname;
    }
    else {
        $str .= $sql_tbl[$jname];
    }
    $str .= " ON " . $join['on'];

    return $str;
}

/**
 * Check table fields names
 *
 * @param $tbl Table name
 * @param $fields
 *
 * @return bool
 * @throws \Doctrine\DBAL\DBALException
 */
function func_check_tbl_fields($tbl, $fields)
{
    static $storage = [];
    global $sql_tbl;

    if (empty($fields)) {
        return false;
    }

    if (!is_array($fields) && !empty($fields)) {
        trigger_error("ERR:77In function array2update|array2insert parameter `fields` passed, not an array", E_USER_ERROR);

        $fields = [$fields];
    }

    if (!is_array($tbl)) {
        $tbls = [$tbl];
    }
    else {
        $tbls = $tbl;
    }

    $fields_orig = [];
    foreach ($tbls as $t) {
        if (isset($sql_tbl[$t])) {
            $t = $sql_tbl[$t];
        }

        if (!isset($storage[$t])) {
            $storage[$t] = array_map(function ($field) {
                /** @var \Doctrine\DBAL\Schema\Column $field */
                return $field->getName();
            }, Xcart::app()->db->getConnection()->getSchemaManager()->listTableColumns($t)
            );

            if (empty($storage[$t])) {
                trigger_error("ERR:78 Table `{$t}` maybe not exist ", E_USER_ERROR);

                return false;
            }
        }

        $fields_orig = func_array_merge($fields_orig, $storage[$t]);
    }

    $fields_orig = array_unique($fields_orig);
    $res = array_diff($fields, $fields_orig);

    if (!empty($res)) {
        trigger_error("ERR:79 In table `{$tbl}` not exist field[s]: " . implode(', ', $res), E_USER_ERROR);

        return false;
    }

    return true;
}

function func_init_default_values($tbl, $data)
{
    static $storage_fields = [];
    global $sql_tbl;

    if (isset($sql_tbl[$tbl])) {
        $tbl = $sql_tbl[$tbl];
    }

    if (!isset($storage_fields[$tbl])) {
        $storage_fields[$tbl] = Xcart::app()->db->getConnection()->getSchemaManager()->listTableColumns($tbl);

        if (empty($storage_fields[$tbl])) {
            return false;
        }
    }

    if (!empty($storage_fields[$tbl])) {
        /** @var \Doctrine\DBAL\Schema\Column $field */
        foreach ($storage_fields[$tbl] as $field) {
            if (!isset($data[$field->getName()]) || (is_null($data[$field->getName()]) && $field->getNotnull())) {

                if (!$field->getAutoincrement()) {
                    if ($field->getDefault()) {
                        $data[$field->getName()] = $field->getDefault();
                    }
                    elseif ($field->getDefault() === 0) {
                        $data[$field->getName()] = 0;
                    }
                    elseif ($field->getDefault() === '') {
                        $data[$field->getName()] = '';
                    }
                }
            }
        }
    }

    return $data;
}

function func_get_column_from_array($col, $arr)
{
    if (empty($arr) || !is_array($arr) || empty($col)) {
        return false;
    }

    $result = [];

    foreach ($arr as $k => $a) {
        if (isset($a[$col])) {
            $result[$k] = $a[$col];
        }
    }

    return $result;
}

function func_get_first_last_name($first_name)
{
    $first_name = trim($first_name);
    $name_arr = explode(" ", $first_name);
    $name_arr_count = count($name_arr);

    $last_name = "";
    if ($name_arr_count > 1) {
        $last_name = array_pop($name_arr);
        unset($name_arr[$name_arr_count]);
        $first_name = implode(" ", $name_arr);
    }

    $new_first_last_name["first_name"] = $first_name;
    $new_first_last_name["last_name"] = $last_name;

    return $new_first_last_name;
}

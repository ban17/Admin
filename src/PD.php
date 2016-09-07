<?php

/**
 * Class PD
 * 
 */
class PD
{

    /**
     * Connect with database
     *
     * @param $server
     * @param $user
     * @param $pass
     * @param string $db
     *
     * @param array $params
     *
     * @return mixed
     */
    public static function connect($server, $user, $pass, $db, $params = [], $driver = 'mysql', $conn_string = '')
    {
        global $conn;
        if ($driver == 'mysql' && count($params) == 0) {
            $params = [
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_WARNING
            ];
        }
        try {
            if ($conn_string == '') {
                $conn_string = "$driver:host=$server;dbname=$db;charset=utf8;";
            }
            $conn = new PDO($conn_string, $user, $pass, $params);
        } catch (Exception $e) {
            error_log("Error in connectiong with database: $server:$user:****:$db" . $e->getMessage());
            die( "Error in connectiong with database!!!" );// Stopping execution
        }

        return $conn;
    }


    /**
     * Return one field from database
     *
     * @param $query string
     * @param $params array
     * @param string $default_value
     *
     * @param int $big_query
     *
     * @return mixed
     */
    public static function field($query, $params = [], $default_value = '', $big_query = 0)
    {
        $tmp = PD::select($query, $params, $big_query);
        foreach ($tmp as $k => $v) {
            foreach ($v as $v2) {
                return $v2;
            }
        }

        return $default_value;
    }

    /**
     * Executing SELECT sql queries
     *
     * @param $sql
     * @param array $params
     * @param int $big_query
     *
     * @return mixed
     */
    public static function select($sql, $params = [], $big_query = 0)
    {
        return PD::q($sql, $params, 'select', $big_query);
    }

    /**
     * You can use this method for any SQL query
     *
     * @param $sql
     * @param array $params
     * @param string $return_type
     * @param int $big_query - Used to say that we know that this query returns a lof of results
     * @param int $fetch_mode
     *
     * @return mixed
     */
    public static function q(
        $sql,
        $params = [],
        $return_type = 'select',
        $big_query = 0,
        $fetch_mode = PDO::FETCH_ASSOC
    ) {
        global $number_of_queries;
        #global $queries_array;
        global $conn;

        $id_user = isset( $_SESSION['id_user'] ) ? $_SESSION['id_user'] : 0;
        $number_of_queries ++;
        $time_start = microtime(true);

        $tmp = false;
        if ($conn) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $tmp = $stmt->execute($params);
                if ($return_type == 'select_row') {
                    $result = $stmt->fetch($fetch_mode);
                } elseif ($return_type == 'update' || $return_type == 'delete') {
                    $result = $stmt->rowCount();
                } elseif ($return_type == 'insert') {
                    $result = $conn->lastInsertId();
                } elseif ($return_type == 'exec') {
                    $result = $stmt->fetchAll($fetch_mode);
                } else {
                    $result = $stmt->fetchAll($fetch_mode);
                }
            } else {
                $result = false;
            }
        } else {
            die( "Check your db connection" );
        }

        $dbb              = debug_backtrace();
        $len              = count($dbb) - 1;
        $line             = $dbb[$len]['line'];
        $file             = $dbb[$len]['file'];
        $now              = date("Y-m-d H:i:s");
        $file_name_append = date('Y-m');
        $day_append       = date('-d');
        $para             = count($params) > 0 ? "\t" . str_replace("\t", " ", json_encode($params)) : "\t";
        $tip              = strtoupper($return_type);
        $sql              = str_replace(["\n", "\t"], [' ', ' '], $sql);// formatiramo ga za prikaz u logovima
        #$queries_array[]      = $sql;

        /**
         * If there is error we are logging sql query
         */
        if ( ! $tmp) {
            $error_niz = $conn->errorInfo();
            $error     = $error_niz[2];

            file_put_contents(SQL_LOGS . "errors_$file_name_append.tsv",
                "------\nVreme:$now\nUpit: $sql{$para}\nError: $error\nFile: $file\nLine: $line\n----\n",
                FILE_APPEND);
            error_log("SQL error: $error Time: $now File: $file Line: $line\n Sql: $sql{$para}");
            if (defined('DIE_ON_MYSQL_ERROR') && DIE_ON_MYSQL_ERROR == 1) {
                die();
            }
        } else {
            $sql_without_formatting = str_replace(["\n", '    ', "\t", '  '], ['', ' ', ' ', ' '], $sql);
            $execution_time    = number_format(round(( microtime(true) - $time_start ) * 1000, 3), 3);
            $log_string           = "$now\t$execution_time\t$line\t$file\t$sql_without_formatting{$para}\t$tip\n";
            file_put_contents(SQL_LOGS . "$file_name_append{$day_append}.tsv", $log_string, FILE_APPEND);

            $class = '';
            if ($execution_time > 100) {
                $class = 'very_bad';
                $color = 'red';
                file_put_contents(SQL_LOGS . "long_$file_name_append.tsv", $log_string, FILE_APPEND);
            } elseif ($execution_time > 10) {
                $color = 'darkorange';
                $class = 'bad';
            } elseif ($execution_time > 1) {
                $color = 'orange';
            } else {
                $color = 'green';
            }
            $_SESSION['queries'][] = "<span style='color:$color' class='$class'>{$execution_time}ms</span></td><td><pre>$sql</pre>";
            #$_SESSION['queries_array'][] = array($execution_time,$sql);

            // Koristimo ako hoćemo da logujemo koji korisnik je pokrenuo ovaj upit
            if (LOG_USER && $id_user > 0) {
                $user_dir = SQL_LOGS . "users/$id_user";
                if ( ! file_exists($user_dir)) {
                    mkdir(SQL_LOGS . "users/$id_user", 0755);
                }
                if ( ! defined('USER_LOG_FILE') || USER_LOG_FILE == '') {
                    $user_log_file = $file_name_append;
                    file_put_contents("$user_dir/$user_log_file.tsv", $log_string, FILE_APPEND);
                }
            }
        }

        if (is_array($result)) {
            $broj_rezultata = count($result);
            if ($broj_rezultata > 1500 && $broj_rezultata > $big_query) {
                error_log("SQL with number of results bigger from limit\t$now\t$sql\t$broj_rezultata\t$big_query");
            }
        }

        return $result;
    }

    /**
     *
     *
     * @param $sql
     * @param string $column
     * @param array $params
     * @param $big_query
     *
     * @return mixed
     */
    public static function indexed($sql, $column = 'id', $params = [], $big_query = 0)
    {
        $return = [];
        $tmp    = PD::select($sql, $params, $big_query);
        foreach ($tmp as $r) {
            $return[$r[$column]] = $r;
        }

        return $return;
    }

    /**
     * Return sql result in form of paired results , first columns is key and second is value
     *
     * @param $sql
     * @param string $first_column
     * @param string $second_column
     * @param array $params
     * @param int $big_query
     *
     * @return mixed
     */
    public static function pairs($sql, $first_column = '', $second_column = '', $params = [], $big_query = 0)
    {
        try {

            // todo - if second param not exist get it with regex
            if ($second_column == '' || $first_column == '') {
                preg_match_all("`SELECT\s*(?P<first>[^,]+)\s*,\s*(?P<second>[^,\s]+)(\s|,)`Umxis", $sql, $m);
                if ($first_column == '') {
                    $first_column = trim($m['first'][0]);
                }
                if ($second_column == '') {
                    $second_column = trim($m['second'][0]);
                }
                if ( ! isset( $m['second'][0] )) {
                    throw new Exception("You must select fields from database in format 'SELECT first_field, second_field FROM ...'");
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $return = [];
        $tmp    = PD::select($sql, $params, $big_query);
        foreach ($tmp as $r) {
            $return[$r[$first_column]] = $r[$second_column];
        }

        return $return;
    }

    /**
     *
     *
     * @param $sql
     * @param string $column
     * @param array $params
     * @param $big_query
     *
     * @return mixed
     */
    public static function grouped($sql, $column = 'id', $params = [], $big_query = 0)
    {
        $return = [];
        $tmp    = PD::select($sql, $params, $big_query);
        foreach ($tmp as $r) {
            $return[$r[$column]][] = $r;
        }

        return $return;
    }


    /**
     * Return one row from database
     *
     * @param $sql
     * @param array $params
     * @param int $big_query
     *
     * @return array
     */
    public static function row($sql, $params = [], $big_query = 0)
    {

        $tmp = PD::select($sql, $params, $big_query);
        foreach ($tmp as $k => $v) {
            return $v;
        }

        return [];
    }

    /**
     * Calling UPDATE queries
     *
     * @param $sql
     * @param array $params
     *
     * @return mixed
     */
    public static function update($sql, $params = [])
    {
        return PD::q($sql, $params, 'update');
    }

    /**
     * Calling INSERT queries
     *
     * @param $sql
     * @param array $params
     *
     * @return int - insert id of last column
     */
    public static function insert($sql, $params = [])
    {
        return PD::q($sql, $params, 'insert');
    }

    /**
     * Calling DELETE queries
     *
     * @param $sql
     * @param array $params
     *
     * @return mixed
     */
    public static function delete($sql, $params = [])
    {
        return PD::q($sql, $params, 'delete');
    }

    /**
     * Call queries which aren't UPDATE, SELECT, INSERT, DELETE
     *
     * @param $sql
     * @param array $params
     *
     * @param int $big_query
     *
     * @return mixed
     */
    public static function exec($sql, $params = [], $big_query = 0)
    {
        return PD::q($sql, $params, 'exec', $big_query);
    }

    /**
     * Alias for PD::q method
     *
     * @param $sql
     * @param array $params
     *
     * @param string $return_type
     * @param int $big_query
     *
     * @return mixed
     */
    public static function query($sql, $params = [], $return_type = 'select', $big_query = 0)
    {
        return PD::q($sql, $params, $return_type, $big_query);
    }
}


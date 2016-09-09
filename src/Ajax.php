<?php


/**
 * Ajax calls class
 *
 * Example usage
 * <code><pre>
 * class AjaxCms extends Ajax{
 *      // just give to method same name as "a" param from your post call, you need to always include action param called "a"
 *     function delete_page($r){
 *       $return=PD::query("DELETE FROM pages WHERE id='$r[id]'");
 *       return array('return'=>$return);
 *     }
 * }
 * new AjaxCms();
 * </pre></code>
 */
class Ajax
{
    public
        $action,
        $r,
        $time_start,        // used for calculating executing time
        $login_enabled,     // explicit including and excluding of logging indepedent of rest of system
        $log_file = '',     // if we want different log file than default
        $file,
        $log_params = true, // logging of params in ajax call
        $xhr = false,       // is cross domain connecting allowed
        $jsonp = false;     // is it jsonp call


    /**
     * Ajax constructor.
     */
    function __construct()
    {
        $this->file          = debug_backtrace();
        $this->login_enabled = true;
        $this->time_start    = microtime(true);
        $this->action        = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
        if (strpos($_SERVER['REQUEST_URI'], '.json?callback=') > 0) {
            $tmp          = explode('.json?callback=', $_SERVER['REQUEST_URI']);
            $tmp2         = explode('&', $tmp[1]);
            $this->action = $tmp2[0];
            $this->jsonp  = true;
        }
        $this->r = $_REQUEST;
        if (method_exists($this, $this->action)) {
            $tmpx = call_user_func(array($this, $this->action), $this->r);

            $a = @json_encode($tmpx);
            if ($this->jsonp) {
                if ( ! $this->xhr) {
                    header('HTTP/1.0 404 Not Found');
                    echo $this->error("XHR is not supported on this url!!");
                } else {
                    header('Content-Type: application/json');
                    echo "$this->action($a)";
                }
            } else {
                echo $a;
            }
        } else {
            header('HTTP/1.0 404 Not Found');
            echo $this->error("Method  $this->action not exists!!");
        }
    }

    /**
     *
     *
     * @param string $e String which function return
     *
     * @return string
     */
    function error($e = '')
    {
        if ($e == '') {
            $e = 'Unknown error!!';
        }

        return json_encode(array('error' => $e));
    }

    /**
     * We are using destructor for logging if needed
     */
    function __destruct()
    {
        if ($this->login_enabled) {
            $params = '';
            if ($this->log_params) {
                $tmp = array_merge($_GET, $_POST);
                if (count($tmp) > 1) {
                    $params = "\t";
                }
                foreach ($tmp as $k => $v) {
                    if ( ! is_array($v)) {
                        if (strlen($v) > 200) {
                            $v = substr($v, 0, 200);
                        }// if data we sending are huge
                        if ($k != 'a') {
                            $params .= "&$k=$v";
                        }
                    } else {
                        if ($k != 'a') {
                            $params .= "&$k=" . serialize($v);
                        }
                    }
                }
            }
            $execution_time = round((microtime(true) - $this->time_start) * 1000, 2);
            $append         = (isset($this->file[1]['file'])) ? "::" .
                                                                $this->file[1]['file'] . ":" . $this->file[1]['line'] : "";
            if ($this->log_file == '') {
                $this->log_file = 'ajax-' . date('Y.m.d') . '.txt';
            }
            if (filesize($this->log_file) < 10) {
                file_put_contents($this->log_file,
                    "Date\tVreme (ms)\taction\tFajl\tLinija\tAppend params\tUrl\n", FILE_APPEND);
            }
            file_put_contents($this->log_file,
                date("Y-m-d H:i:s") . "\t$execution_time\t$this->action\t" .
                $this->file[0]['file'] . "\t" . $this->file[0]['line'] . "\t$append{$params}\n", FILE_APPEND);
        }
    }
}


<?php


    declare(strict_types = 1);

    namespace Palvoelgyi\Dbconnect;

use Palvoelgyi\Helper\Helper;

class Globalobjects 
    {

        static private	$instance   = NULL;
        private			$_db        = NULL;

        public function __construct(){
            $this->_db      = new DBConnect('localhost','root','','test');
        }

        /**
         * @return Globalobjects
         */
        static public function getInstance()
        {
            if (NULL === self::$instance) { self::$instance = new self; }
            return self::$instance;
        }

        /**
         * @return DBConnect
         */
        static public function getDBConnect(){
            if (NULL === self::$instance){ self::$instance = new self;  }
            return self::$instance->_db;
        }

        public function __set($name,$value){return NULL;}
    }

    class Globalobjects2 {

        static private	$instance   = NULL;
        private			$_db        = NULL;

        private function __construct(){
            $this->_db      = new DBConnect('localhost','web24398854','tdf6hzAu','usr_web24398854_2');
        }

        /**
         * @return Globalobjects
         */
        static public function getInstance()
        {
            if (NULL === self::$instance) { self::$instance = new self; }
            return self::$instance;
        }

        /**
         * @return DBConnect
         */
        static public function getDBConnect(){
            if (NULL === self::$instance){ self::$instance = new self;  }
            return self::$instance->_db;
        }

        public function __set($name,$value){return NULL;}
    }


    class Validator{
        public static function validateDate($date, $format = 'Y-m-d H:i:s'){
            $d = \DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        }

        public static function getDate($datum){

            $datum  = explode(".", $datum);

            if(
                isset($datum[0],$datum[1],$datum[2]) AND
                checkdate((int)$datum[1],(int)$datum[0],(int)$datum[2])
            ){
                $date   = $datum[2]."-". $datum[1]."-".$datum[0];
            }
            return $date;
        }

        public static function getSQLDate($datum){

            $date   = NULL;
            $datum  = explode(".", $datum);

            if(isset($datum[0],$datum[1],$datum[2]) AND
                checkdate((int)$datum[1],(int)$datum[0],(int)$datum[2])
            ){
                $date   = $datum[2]."-". $datum[1]."-".$datum[0];
            }
            return $date;
        }

        public static function getDaysHours($hours=NULL){
            if(!is_null($hours) AND is_numeric($hours)){
                $days = 0;
                if($hours>24){
                    $days = floor($hours / 24);  //ganze Tage berechnen
                    $hours -= ($days*24); //ganze Tage abziehen
                    return $days.' Tage '.$hours.' Std.';
                }
                if($hours>1){
                    return $hours.' Std.';
                }

            }
            return $hours;
        }
    }

    /*
    * $time = new Timer();
    * $time->start();
    * $time->stop();
    * $time->getTime();
    */
    class Timer{
        private $_db    = NULL,
            $start  = 0,
            $stop   = 0,
            $zeit   = 0,
            $mstart = 0,
            $mstop  = 0,
            $mzeit  = 0;

        public function __construct(){
            $this->_db = Globalobjects::getDBConnect();
        }

        public function start(){
            $this->start = time();
            $this->mstart= microtime(true);
        }

        public function stop(){
            $this->stop = time();
            $this->mstop= microtime(true);

        }

        public function getTime(){

            $this->zeit = $this->stop - $this->start;
            $date = new \DateTime();
            $date->setTimestamp($this->start);
            $this->start = $date->format('H:i:s');

            $date->setTimestamp($this->stop);
            $this->stop = $date->format('H:i:s');
            $this->mzeit = number_format((($this->mstop-$this->mstart)*1000),2);
            $this->_db->xDebug(
                "<br><br>Start: ".$this->start.
                "<br>Ende: ".$this->stop.
                "<br>Zeit: ".$this->zeit. " (".number_format(((float)$this->mzeit),2)." Millisekunden)"
            );
        }
    }

    class csv{
        public static function get($name='data'){
            header('Content-Description: File Transfer');
            header('Content-Type: text/comma-separated-values');
            header("Content-Type: text/html; charset=utf-8");
            header('Content-Disposition: attachment; filename="'.$name.'.csv"');
            header('Cache-Control: post-check=0, pre-check=0');
            header('Content-Transfer-Encoding: binary');
        }

        public static function convertToWindowsCharset($string) {
            $charset =  mb_detect_encoding(
                $string,
                "UTF-8, ISO-8859-1, ISO-8859-15",
                true
            );

            $string =  mb_convert_encoding($string, "Windows-1252", $charset);
            return $string;
        }
    }
    class DBConnect {
        private	$_tables,
            $_arrquery          = array('config'=>array(),'columninfo'=>array()),
            $_timestampsaction  = array(),
            $_session,
            $_cache,
            $_language_name,
            $_develop,
            $_crypt_type        = 'AES',
            $_crypt_cipher      = '3rpR,hlbG!krWgb',
            $_crypt_columns     = array(),
            $_database,
            $database,
            $databaseName
        ;

        public	$_dbconnect;

        /**
         * Erstellt Datenbankverbindung und Tabellen Informationen
         * @param string $host
         * @param string $user
         * @param string $password
         * @param string $database
         * @param string(de|en)			$language default de
         * @param string(utf8|latin1)	$charset default utf8
         * @return DBConnect
         */
        public function __construct($host,$user,$password,$database,$language='de',$charset = 'utf8') {
            $this->_session = &$_SESSION;

            $this->_database = $database;
            if (isset($this->_session['develop'])){$this->_develop=$this->_session['develop'];}
            else{$this->_develop=0;}

            $this->_language_name   = $language;

            $this->_dbconnect       = new \mysqli($host,$user,$password,$database);

            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
            }

            $this->_dbconnect->set_charset($charset);

            if (is_object($this->_cache)) {
                $this->_tables = $this->_cache->get('dbtables-'.$database);
            } else {
                if (isset($this->_session['dbtableinfo'][$database])) {
                    $this->_tables = $this->_session['dbtableinfo'][$database];
                }else{
                    $this->_tables = NULL;
                }
            }

            if (!$this->_tables){
                $q = 'SHOW TABLES';
                $this->Debug($q,'Tabellen Abfrage');
                $res = $this->_dbconnect->query($q);
                for ($x=0;$x<$res->num_rows;$x++) {
                    $row = $res->fetch_row();
                    $this->_tables[$row[0]] = array();
                }

                $res->free();

                if(!empty($this->_tables)){

                    foreach ($this->_tables as $key => $value) {
                        $fe= array();
                        $q = 'SHOW COLUMNS FROM '.$key;
                        $this->Debug($q,'Column Abfrage');
                        $res = $this->_dbconnect->query($q);
                        $primary_key = array();
                        for ($x=0;$x<$res->num_rows;$x++) {
                            $row = $res->fetch_array(MYSQLI_ASSOC);
                            $name = $row['Field'];
                            $FieldPos = strpos($row['Type'],'(');

                            $fe[$name]['name']      = $name;
                            $fe[$name]['type']      = strtok($row['Type'],'(');
                            $fe[$name]['len']       = substr($row['Type'],($FieldPos+1),-1);
                            $fe[$name]['null']      = $row['Null'];
                            $fe[$name]['key']       = $row['Key'];
                            $fe[$name]['default']   = $row['Default'];
                            $fe[$name]['extra']     = $row['Extra'];
                            if ($row['Key'] == 'PRI') {
                                $primary_key[$name]=$name;
                            }
                            switch ($fe[$name]['type']) {
                                case 'varchar':
                                    $fe[$name]['db_validate'] = array('max'=>$fe[$name]['len']);
                                    break;
                                case 'enum':		$opt =  explode('\',\'', substr($fe[$name]['len'],1,-1));
                                    foreach ($opt as $optv) {
                                        $fe[$name]['options'][$optv] = $optv;
                                    }
                                    $fe[$name]['db_validate'] = array('NotEmpty');
                                    break;

                                case 'tinyint':
                                case 'int':			$fe[$name]['db_validate'] = array('Int');
                                    break;

                                case 'decimal':
                                case 'float':		$fe[$name]['db_validate'] = array('Float');
                                    break;
                            }}
                        // $res->free();

                        $this->_tables[$key]['columns'] = $fe;
                        $this->_tables[$key]['primary'] = $primary_key;
                    }
                    if (is_object($this->_cache)) {
                        $this->_cache->set('dbtables-'.$database,$this->_tables,86400);
                    } else {
                        $this->_session['dbtableinfo'][$database] = $this->_tables;
                    }

                }

            }
            return $this;
        }

        /**
         * Direkte SQL Anfrage mit Result als Rückgabe
         * @param string (SQL) $query
         * @return result
         */
        public function db_query($query) {
            $this->Debug($query);
            return $this->_dbconnect->query($query);
        }

        /**
         * Abfrage nach Tabellen Informationen
         * @param string array $StrTable
         * @param array $ArrayColumn
         * @return array
         */
        public function get_table_info($StrTable,$ArrayColumn=NULL) {
            $ArrayReturn = NULL;
            if (!is_array($StrTable)) {
                $tmpTable = explode(',', $StrTable);
                $StrTable = array();
                foreach ($tmpTable as $value) {
                    $StrTable[] = trim($value);
                }}
            foreach ($StrTable as $value) {
                if (isset($this->_tables[$value])) {
                    if (is_array($ArrayColumn)) {
                        foreach ($ArrayColumn as $v) {
                            if (isset($this->_tables[$value]['columns'][$v])) {
                                $ArrayReturn[$value][$v] = $this->_tables[$value]['columns'][$v];
                            }}
                    } else {
                        if(!is_null($ArrayColumn)){
                            $ArrayReturn[$value] = $this->_tables[$value]['columns'][$ArrayColumn];
                        }else{
                            $ArrayReturn[$value] = $this->_tables[$value]['columns'];
                        }}}}
            return $ArrayReturn;
        }

        public function getDatabaseName(){
            $res = $this->query('SELECT DATABASE() AS DB');
            $res = $res['result']['0']['DB'];
            $this->databaseName = $res;
            return $res;
        }

        public function getTables($prefix=NULL,$not=NULL,$key=NULL){

            $tables = array();

            if(is_null($this->databaseName)) {
                $this->databaseName = $this->getDatabaseName();
            }

            $whereString = NULL;

            if(!is_null($prefix)){
                $whereString = ' WHERE `Tables_in_'.$this->databaseName.'` LIKE \''.$prefix.'%\'';
            }


            # NOT REGEXP '^TS|^SLG-|^SLZ-|^sw-payment|^GS-';

            if(!is_null($not)){

                if(!is_null($prefix)){ $and = ' AND '; }else{ $and = ' WHERE '; }

                $whereString.= $and.'`Tables_in_'.$this->databaseName.'` NOT REGEXP \''.$not.'\'';
            }

            $result = $this->query("SHOW TABLES FROM ".$this->databaseName.$whereString);

            while ( $row = $result -> fetch_array(MYSQLI_NUM) ) {

                if(!is_null($key)){

                    $tables[$row[0]] = $row[0];

                }else{
                    array_push($tables,$row[0]);
                }

            }

            return $tables;
        }

        /**
         * Bereitet Tabellenfelder für die Nutzung<br>
         * in DBConnect, Formular und Grid vor.
         * @param string $table
         * @param string array $column
         * @return array
         */
        public function columnHelper($table,$column) {
            $table = trim($table);
            $column = $this->setarray($column);
            $tableinfo = false;
            $ret = array();

            if (isset($this->_tables[$table]['columns'])) {
                $tableinfo = $this->_tables[$table]['columns'];
            }

            $selectall=FALSE;
            if (isset($column[0])) {
                if ($column[0]=='*') {
                    $selectall = TRUE;
                }}

            if (!$selectall) {
                foreach ($column as $k => $v) {
                    $coloptions = array();
                    $as = false;
                    if (!is_array($v)) {
                        $colname = $v;
                    } else {
                        $colname = $k;
                    }

                    $p = strtolower($colname);
                    $h = strpos($p, ' as ');
                    if ($h) {
                        $col = trim(substr($colname,0,$h));
                        $as = trim(substr($colname,($h+4)));
                        $colname = $col;
                    }
                    if ($tableinfo) {
                        if (isset($tableinfo[$colname])) {
                            $coloptions = $tableinfo[$colname];
                        }}

                    if (is_array($v)) {
                        $coloptions = array_merge($coloptions,$v);
                    }

                    if (isset($coloptions['as'])) {
                        $as = $coloptions['as'];
                    }

                    if ($as) {
                        if (!isset($coloptions['as'])) {
                            $coloptions['as']	= $as;
                        }
                    } else {
                        $as = $colname;
                    }

                    if (isset($coloptions['type'])) {
                        switch ($coloptions['type']) {
                            case 'timestamp':
                            case 'date':		$this->_timestampsaction[$table][] = $as;
                                break;
                        }}

                    if (!isset($coloptions['label'])) {
                        $coloptions['label'] = $as;
                    }

                    $coloptions['table']	= $table;

                    if (!isset($coloptions['name'])) {
                        $coloptions['name'] = $colname;
                    }
                    if (isset($coloptions['split'])) {
                        $this->_arrquery['actions']['split'][$as] = $coloptions['split'];
                    }
                    if (isset($coloptions['phpfunctions'])) {
                        foreach ($coloptions['phpfunctions'] as $phpfunction => $vars) {
                            $coloptions['phpfunctions'][$phpfunction] = $this->setarray($vars);
                        }}
                    $ret[$as] = $coloptions;
                }
            } elseif ($tableinfo) {
                foreach ($tableinfo as $v) {
                    $v['label'] = $v['name'];
                    $ret[$v['name']] = $v;
                    $ret[$v['name']]['table'] = $table;
                }}
            $this->_arrquery['columninfo']=array_merge($this->_arrquery['columninfo'],$ret);
            return $ret;
        }

        /**
         * Table und Column für den Query
         * @param string $table
         * @param string|array $column
         * @return DBConnect
         */
        public function table($table,$column=array()) {
            $table = trim($table);
            $as = NULL;
            if(strpos($table, ' ')){
                $arr = explode(' ', $table);
                $table = array_shift($arr);
                $as = array_pop($arr);
            }
            $column = $this->setarray($column);
            if(empty($column)){$column[0]='*';}
            $config = array(
                'sqltype'=>'table',
                'table'=>$table,
                'column'=>$this->columnHelper($table, $column),
            );
            empty($as)OR$config['as']=$as;
            array_push($this->_arrquery['config'],$config);

            return $this;
        }

        /**
         * Left Join Anfrage erstellen
         * @param string $table
         * @param string $joinon
         * @param string|array $column
         * @return DBConnect
         */
        public function joinleft($table,$joinon,$column=array()) {
            $column = $this->setarray($column);
            $table = trim($table);
            $joinon = trim($joinon);
            if(strpos($table, ' ')){
                $arr = explode(' ', $table);
                $table = array_shift($arr);
                $as = array_pop($arr);
            }
            $column = $this->setarray($column);
            $config = array(
                'sqltype'=>'joinleft',
                'table'=>$table,
                'joinon'=>$joinon,
                'column'=>$this->columnHelper($table, $column),
            );
            empty($as)OR$config['as']=$as;
            array_push($this->_arrquery['config'],$config);

            return $this;
        }

        /**
         * Right Join Anfrage erstellen
         * @param string $table
         * @param string $joinon
         * @param string|array $column
         * @return DBConnect
         */
        public function joinright($table,$joinon,$column=array()) {
            $column = $this->setarray($column);
            $table = trim($table);
            $joinon = trim($joinon);
            if(strpos($table, ' ')){
                $arr = explode(' ', $table);
                $table = array_shift($arr);
                $as = array_pop($arr);
            }
            $column = $this->setarray($column);
            $config = array(
                'sqltype'=>'joinright',
                'table'=>$table,
                'joinon'=>$joinon,
                'column'=>$this->columnHelper($table, $column),
            );
            empty($as)OR$config['as']=$as;
            array_push($this->_arrquery['config'],$config);

            return $this;
        }

        /**
         * Join Anfrage erstellen
         * @param string $table
         * @param string $joinon
         * @param string|array $column
         * @return DBConnect
         */
        public function join($table,$joinon,$column=array()) {
            $column = $this->setarray($column);
            $table = trim($table);
            $joinon = trim($joinon);
            if(strpos($table, ' ')){
                $arr = explode(' ', $table);
                $table = array_shift($arr);
                $as = array_pop($arr);
            }
            if(count($column)==0){$column=array('*');}

            $config = array(
                'sqltype'=>'join',
                'table'=>$table,
                'joinon'=>$joinon,
                'column'=>$this->columnHelper($table, $column),
            );
            empty($as)OR$config['as']=$as;
            array_push($this->_arrquery['config'],$config);

            return $this;
        }

        /**
         * Eingabe der AND Where Bedingungen
         * @param mix $where
         * @return DBConnect
         */
        public function where($where,$value=NULL) {

            if($where == ''){ return $this; }

            if (is_array($where)) {
                if (!isset($this->_arrquery['where'])) {
                    $this->_arrquery['where'][0] = $where;
                } else {
                    $key = count($this->_arrquery['where'])-1;
                    $this->_arrquery['where'][$key] = $where;
                }
            } else {
                if(!is_null($value)){
                    $arrwhere = explode('?',(string)$where);

                    if(!is_array($value)){$value = array($value);}

                    if(count($arrwhere)-1 != count($value)){
                        echo	'<h1>SQL Fehler</h1>'.
                            '<h2>Prepare Statement verursacht Fehler</h2>'.
                            '<p>'.$where.'</p>'.
                            '<pre>'.
                            print_r($value).
                            '</pre>';
                        '<h2>Debug</h2>'.
                        '<pre>';
                        debug_print_backtrace();
                        echo	'</pre>';
                        exit();
                    }
                    $where = '';
                    foreach ($arrwhere as $k => $v){
                        if(isset($value[$k])){
                            $where.= $v.'"'.$this->cleanInsert($value[$k]).'" ';
                        }else{
                            $where .= $v.' ';
                        }}
                    $where = trim($where);
                }
                if (!isset($this->_arrquery['where'])) {
                    $this->_arrquery['where'][0][0] = $where;
                } else {
                    $key = count($this->_arrquery['where'])-1;
                    $this->_arrquery['where'][$key][] = $where;
                }}
            return $this;
        }

        /**
         * Eingabe der OR Where Bedingungen
         * @param mix $where
         * @return DBConnect
         */
        public function orwhere($where,$value=NULL) {
            if (is_array($where)) {
                if (!isset($this->_arrquery['where'])) {
                    $this->_arrquery['where'][0] = $where;
                } else {
                    $key = count($this->_arrquery['where']);
                    $this->_arrquery['where'][$key] = $where;
                }
            } else {
                if(!is_null($value)){
                    $arrwhere = explode('?',(string)$where);

                    if(!is_array($value)){$value = array($value);}

                    if(count($arrwhere)-1 != count($value)){
                        echo	'<h1>SQL Fehler</h1>'.
                            '<h2>Prepare Statement verursacht Fehler</h2>'.
                            '<p>'.$where.'</p>'.
                            '<pre>'.
                            print_r($value).
                            '</pre>';
                        '<h2>Debug</h2>'.
                        '<pre>';
                        debug_print_backtrace();
                        echo	'</pre>';
                        exit();
                    }
                    $where = '';
                    foreach ($arrwhere as $k => $v){
                        if(isset($value[$k])){
                            $where.= $v.'"'.$this->cleanInsert($value[$k]).'" ';
                        }else{
                            $where .= $v.' ';
                        }
                    }
                    $where = trim($where);
                }
                if (!isset($this->_arrquery['where'])) {
                    $this->_arrquery['where'][0][0] = $where;
                } else {
                    $key = count($this->_arrquery['where']);
                    $this->_arrquery['where'][$key][0] = $where;
                }}
            return $this;
        }

        /**
         * Eingabe eines WHERE arrays
         * @param array $where
         * @return DBConnect
         */
        public function setwhere($where) {
            $this->_arrquery['where'] = $where;
            return $this;
        }

        /*
        * @return DBConnect
        */
        public function setwheresearch($where) {
            if(!is_array($where)){$where = array($where);}
            foreach ($where as $key => $val){
                $key = count($this->_arrquery['where'])-1;
                if(is_array($val)){ $this->setwheresearch($val);}else{
                    array_push($this->_arrquery['where'][$key], $val);
                }}
            return $this;
        }

        private function getwhere($arrWhere) {
            foreach ($arrWhere as $key => $value) {
                $arrWhere[$key] = implode(') AND (', $value);
            }
            return '(('.implode(')) OR ((', $arrWhere).'))';
        }

        /**
         * Stellt den Querystring bereit um auf das Result
         * mit weiteren Bedingungen zugreifen zu können.
         * @param String $query
         * @return DBConnect
         */
        public function subquery($query){
            $this->_arrquery['subquery']=$query;
            return $this;
        }

        /**
         * Eingabe der Group by Bedingung
         * @param string $group
         * @return DBConnect
         */
        public function group($group) {
            $group = $this->setarray($group);
            $this->_arrquery['group'] = $group;
            return $this;
        }

        /**
         * Eingabe der Order Bedingung
         * @param string|array $order
         * @return DBConnect
         */
        public function order($order) {
            $order = $this->setarray($order);
            $this->_arrquery['order'] = $order;
            return $this;
        }

        /**
         * Eingabe der Where Bedingung
         * @param String $Limit
         * @return DBConnect
         */
        public function limit($limit) {
            $limit = $this->setarray($limit);
            $this->_arrquery['limit'] = $limit;
            return $this;
        }

        /**
         * An- oder Abwahl einer DISTINCT Anweisung
         * für ein SELECT Statement.
         * @param boolean $bool
         * @return DBConnect
         */
        public function distinct($bool=TRUE){
            $this->_arrquery['distinct']=$bool;
            return $this;
        }

        /**
         * Function is required to clean an SQL-Insert Parameter. It escapes the following characteres \x00, \, ', ", to avoid e.g. SQL-Injections
         *
         * @param string $strDirty
         * @return string $strClean
         */
        public function cleanInsert($strDirty) {
            if(is_object($strDirty)){
                debug_print_backtrace();
                exit;
            }

            $strDirty = trim($strDirty);
            $strClean = $this->_dbconnect->real_escape_string($strDirty);
            return $strClean;
        }

        protected function gettable($arrtable) {
            $arr = array();
            foreach ($arrtable as $k => $v) {
                array_push($arr, $k);
            }
            return $arr;
        }

        /**
         * Initialisiert die Aufteilung von Timestamp und Date
         * @param bool $Bool
         * @return DBConnect
         */
        public function splittimestamp($Bool=true) { $this->_arrquery['splittimestamp'] = $Bool;return $this; }

        /**
         * Teilt Timestamp und Date in Array
         * @param string $StrTimestamp
         * @return array
         */
        private function build_timestamp_array($StrTimestamp) {
            $Array['timestamp'] = $StrTimestamp;
            $Array['day'] = substr($StrTimestamp,8,2);
            $Array['month'] = substr($StrTimestamp,5,2);
            $Array['year'] = substr($StrTimestamp,0,4);
            $Array['hour'] = substr($StrTimestamp,11,2);
            $Array['minute'] = substr($StrTimestamp,14,2);
            $Array['second'] = substr($StrTimestamp,17,2);

            switch ($this->_language_name) {
                default:	if ($Array['year'] != '0000') {
                    $Array['date'] = $Array['day'].".".$Array['month'].".".$Array['year'];
                    $Array['time'] = $Array['hour'].":".$Array['minute'].":".$Array['second'];
                } else {
                    $Array['date'] = NULL;
                    $Array['time'] = NULL;
                }
                    break;
            }
            return $Array;
        }

        /**
         * Verbindet Timestamp und Date zu String
         * @param array $ArrayTimestamp
         * @return string
         */
        private function build_timestamp_str($ArrayTimestamp) {
            if(isset($ArrayTimestamp['year']) AND isset($ArrayTimestamp['month']) AND isset($ArrayTimestamp['day'])){
                if (!isset($ArrayTimestamp['hour']))	{ $ArrayTimestamp['hour'] 	= '00'; }
                if (!isset($ArrayTimestamp['minute']))	{ $ArrayTimestamp['minute'] = '00'; }
                if (!isset($ArrayTimestamp['second']))	{ $ArrayTimestamp['second'] = '00'; }
                $StrTimestamp = $ArrayTimestamp['year'].'-'.$ArrayTimestamp['month'].'-'.$ArrayTimestamp['day'].' '.$ArrayTimestamp['hour'].':'.$ArrayTimestamp['minute'].':'.$ArrayTimestamp['second'];
                return $StrTimestamp;
            }else{
                return NULL;
            }
        }

        /**
         * Sucht nach Timestamp und Date eines<br>
         * Results-Array und wandelt diese als um.
         * @param array $row
         * @return array
         */
        private function findtimestamp($row) {
            if (isset($this->_arrquery['arraytimestampcolumn'])) {
                foreach ($this->_arrquery['arraytimestampcolumn'] as $value) {
                    foreach ($value as $c) {
                        if (isset($row[$c])) {
                            $row[$c] = $this->build_timestamp_array($row[$c]);
                        }}}}
            return $row;
        }

        public function clearTables(){

            $q = 'SHOW TABLES';
            $this->Debug($q,'Tabellen Abfrage');
            $res = $this->_dbconnect->query($q);
            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_row();
                $this->_tables[$row[0]] = array();
            }

            $res->free();

            if(!empty($this->_tables)){

                foreach ($this->_tables as $key => $value) {
                    $fe= array();
                    $q = 'SHOW COLUMNS FROM '.$key;
                    $this->Debug($q,'Column Abfrage');
                    $res = $this->_dbconnect->query($q);
                    $primary_key = array();
                    for ($x=0;$x<$res->num_rows;$x++) {
                        $row = $res->fetch_array(MYSQLI_ASSOC);
                        $name = $row['Field'];
                        $FieldPos = strpos($row['Type'],'(');

                        $fe[$name]['name']      = $name;
                        $fe[$name]['type']      = strtok($row['Type'],'(');
                        $fe[$name]['len']       = substr($row['Type'],($FieldPos+1),-1);
                        $fe[$name]['null']      = $row['Null'];
                        $fe[$name]['key']       = $row['Key'];
                        $fe[$name]['default']   = $row['Default'];
                        $fe[$name]['extra']     = $row['Extra'];
                        if ($row['Key'] == 'PRI') {
                            $primary_key[$name]=$name;
                        }
                        switch ($fe[$name]['type']) {
                            case 'varchar':
                                $fe[$name]['db_validate'] = array('max'=>$fe[$name]['len']);
                                break;
                            case 'enum':		$opt =  explode('\',\'', substr($fe[$name]['len'],1,-1));
                                foreach ($opt as $optv) {
                                    $fe[$name]['options'][$optv] = $optv;
                                }
                                $fe[$name]['db_validate'] = array('NotEmpty');
                                break;

                            case 'tinyint':
                            case 'int':			$fe[$name]['db_validate'] = array('Int');
                                break;

                            case 'decimal':
                            case 'float':		$fe[$name]['db_validate'] = array('Float');
                                break;
                        }}
                    $res->free();

                    $this->_tables[$key]['columns'] = $fe;
                    $this->_tables[$key]['primary'] = $primary_key;
                }
                if (is_object($this->_cache)) {
                    $this->_cache->set('dbtables-'.$this->_database,$this->_tables,86400);
                } else {
                    $this->_session['dbtableinfo'][$this->_database] = $this->_tables;
                }

            }

            return $this;
        }














        /**
         * Initialisiert ein Seitenzähler array
         * @param int $page_current
         * @param int $offset
         * @param string $countercolumn
         * @return DBConnect
         */
        public function pager($page_current,$offset,$countercolumn=false,$page_from=NULL) {
            $IntLimitStartwert = $page_current * $offset - $offset;
            $this->_arrquery['pager']['limit'] = $offset;
            $this->_arrquery['pager']['CounterColumn'] = $countercolumn;
            $this->_arrquery['pager']['offset'] = $IntLimitStartwert;
            $this->_arrquery['pager']['page_current'] = $page_current;
            $this->_arrquery['pager']['page_from'] = $page_from;
            return $this;
        }

        /**
         * Erzeugt ein Seitenzähler array
         */
        private function pagerbuild() {
            $this->limit($this->_arrquery['pager']['offset'].",".$this->_arrquery['pager']['limit']);
        }

        /**
         * Anfrage an Datenbank mit nur einem<br>
         * Rückgabewert
         * @param string $query
         * @return string
         */
        private function select_one($query) {
            if (!isset($this->_arrquery['limit'])) { $query .= " LIMIT 1"; }
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            if ($res->num_rows > 0) {
                $ret = $res->fetch_row();
                $ret = $ret[0];
                if ($this->is_serialized($ret)){unserialize($ret);}
            } else {
                $ret = false;
            }
            $this->cleanQuery();
            $res->free();
            return $ret[0];
        }

        /**
         * Anfrage an Datenbank mit nur einer<br>
         * RÃükgabezeile inklusive db_info
         * @param string $query
         * @return array + array[db_info]
         */
        private function select_first($query) {
            if (!isset($this->_arrquery['limit'])) { $query .= " LIMIT 1"; }
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);

            $ret = array();
            if ($res->num_rows>0) {
                $ret = $res->fetch_array(MYSQLI_ASSOC);
                $ret = $this->findtimestamp($ret);
            }
            foreach ($ret as $k => $v) {
                if ($this->is_serialized($v)){
                    $ret[$k] = unserialize($v);
                }}
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            $res->free();
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit nur einer<br>
         * Rückgabezeile exklusive db_info
         * @param string $query
         * @return array
         */
        private function select_row($query) {
            if (!isset($this->_arrquery['limit'])) { $query .= " LIMIT 1"; }
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);

            $ret = array();
            if ($res->num_rows>0) {
                $ret = $res->fetch_array(MYSQLI_ASSOC);
                foreach ($ret as $k => $v) {
                    if ($this->is_serialized($v)){
                        $ret[$k] = unserialize($v);
                    }}
                $ret = $this->findtimestamp($ret);
            }
            $res->free();
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit Rückgabe eines<br>
         * nach $retkey Assoziierten Arrays
         * @param string $query
         * @param string $retkey
         * @return array[result] array[db_info]
         */
        private function select_key($query,$retkey) {
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            $ret['result'] = array();
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            if (isset($this->_arrquery['columninfo'])) {
                $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            }
            $keyname = false;
            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_array(MYSQLI_ASSOC);
                foreach ($row as $k => $v) {
                    if ($this->is_serialized($v)){
                        $row[$k] = unserialize($v);
                    }}
                if (!$keyname) {
                    if (!isset($row[$retkey])) {
                        $keyname = 'Nummeric';
                    } else {
                        $keyname = $retkey;
                    }}
                $row = $this->sqlactions($row);
                $row = $this->findtimestamp($row);
                if ($keyname == 'Nummeric') {
                    $ret['result'][] = $row;
                } else {
                    $ret['result'][$row[$keyname]] = $row;
                }
            }
            $res->free();
            if(isset($this->_arrquery['pager'])){
                $ret = $this->FOUND_ROWS($ret);
            }
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit Rückgabe eines<br>
         * nach $retkey Assoziierten Arrays
         * @param string $query
         * @param string $retkey
         * @return array[result] array[db_info]
         */
        private function select_keyvalue($query,$retkey) {
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            $ret['result'] = array();
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            if (isset($this->_arrquery['columninfo'])) {
                $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            }
            $keyname = false;
            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_array(MYSQLI_ASSOC);
                $keyname = 'Nummeric';
                if($retkey AND isset($row[$retkey])){
                    $keyname=$row[$retkey];
                    unset($row[$retkey]);
                }
                foreach ($row as $k => $v) {
                    if ($this->is_serialized($v)){
                        $row[$k] = unserialize($v);
                    }}
                $row = $this->sqlactions($row);
                $row = $this->findtimestamp($row);
                if ($keyname == 'Nummeric') {
                    $ret['result'][] = implode(' ', $row);
                } else {
                    $ret['result'][$keyname] = implode(' ', $row);
                }
            }
            $res->free();
            if(isset($this->_arrquery['pager'])){
                $ret = $this->FOUND_ROWS($ret);
            }
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit Rückgabe eines<br>
         * nach $retkey Assoziierten Arrays
         * @param string $query
         * @param string $retkey
         * @return array[result] array[db_info]
         */
        private function select_multikey($query,$retkey) {
            $retkey = $this->setarray($retkey);
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            $ret['result'] = array();
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            $retname = false;
            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_array(MYSQLI_ASSOC);
                foreach ($row as $k => $v) {
                    if ($this->is_serialized($v)){
                        $row[$k] = unserialize($v);
                    }}
                if (!$retname) {
                    foreach ($retkey as $k => $v) {
                        if (!isset($row[$v])){
                            unset($retkey[$k]);
                        } else {
                            $retname = true;
                        }}}
                $row = $this->findtimestamp($row);
                if (count($retkey)>0) {
                    $multisort = $this->select_multikey_helper($row, $retkey, $ret['result']);
                    $ret['result'] = $multisort;
                } else {
                    $ret['result'][] = $row;
                }
            }
            $res->free();
            $this->cleanQuery();
            return $ret;
        }

        private function select_multikey_helper($row,$retkey, $ret) {
            if (count($retkey)>0) {
                $key = array_shift($retkey);
                if (!isset($ret[$row[$key]])) {
                    $ret[$row[$key]] = array();
                }
                $ret[$row[$key]] = $this->select_multikey_helper($row, $retkey,$ret[$row[$key]]);
                return $ret;
            } else {
                array_push($ret, $row);
                return $ret;
            }
        }

        /**
         * Anfrage an Datenbank mit Rückgabe eines<br>
         * numerisch Assoziierten Arrays
         * @param string $query
         * @return array[result] array[db_info]
         */
        private function select_array($query) {
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            $ret['result'] = array();
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            if (isset($this->_arrquery['columninfo'])) {
                $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            }
            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_array(MYSQLI_ASSOC);
                foreach ($row as $k => $v) {
                    if ($this->is_serialized($v)){
                        $row[$k] = unserialize($v);
                    }}
                $row = $this->sqlactions($row);
                $row = $this->findtimestamp($row);
                $ret['result'][] = $row;
            }
            $res->free();
            if(isset($this->_arrquery['pager'])){
                $ret = $this->FOUND_ROWS($ret);
            }
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit Rückgabe der<br>
         * mysqli Instanz
         * @param string $query
         * @return array[result] array[db_info]
         */
        private function select_object($query) {
            $this->Debug($query);
            $ret = $this->_dbconnect->query($query);
            $this->sqlerror($ret, $query);
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Anfrage an Datenbank mit Rückgabe eines<br>
         * Arrays geteilt in Tabellennamen
         * @param string $query
         * @return array[result] array[db_info]
         */
        private function select_table($query) {
            $this->Debug($query);
            $res = $this->_dbconnect->query($query);
            $this->sqlerror($res, $query);
            $ret['result'] = array();
            $ret['db_info']['count'] = $res->num_rows;
            $ret['db_info']['query'] = $query;
            $ret['db_info']['columns'] = $this->_arrquery['columninfo'];

            for ($x=0;$x<$res->num_rows;$x++) {
                $row = $res->fetch_array(MYSQLI_ASSOC);
                foreach ($row as $k => $v) {
                    if ($this->is_serialized($v)){
                        $row[$k] = unserialize($v);
                    }}
                $row = $this->findtimestamp($row);
                foreach ($this->_arrquery['columninfo'] as $k => $v) {
                    if (key_exists($k, $row)) {
                        if (!isset($ret['result'][$k])) {
                            $ret['result'][$k] = $this->_arrquery['columninfo'][$k];
                        }
                        $value = $row[$k];
                        if (isset($ret['result'][$k]['phpfunctions'])) {
                            $value = $this->ValueAction($ret['result'][$k]['phpfunctions'], $value);
                        }
                        if (isset($ret['result'][$k]['split'])) {
                            $value = $this->ValueAction('split', array($ret['result'][$k]['split'],$value));
                            $ret['result'][$k]['value'] = $value;
                        } else {
                            if (isset($ret['result'][$k]['value'])) {
                                if (is_array($ret['result'][$k]['value']) AND !is_array($value)) {
                                    $ret['result'][$k]['value'][$value] = $value;
                                } else {
                                    if ($ret['result'][$k]['value']!=$value) {
                                        $arr = array(
                                            $ret['result'][$k]['value'] => $ret['result'][$k]['value'],
                                            $value						=> $value,
                                        );
                                        $ret['result'][$k]['value'] = $arr;
                                    }}
                            } else {
                                $ret['result'][$k]['value'] = $value;
                            }}}}}
            $res->free();
            if(isset($this->_arrquery['pager'])){
                $ret = $this->FOUND_ROWS($ret);
            }
            $this->cleanQuery();
            return $ret;
        }

        /**
         * Simuliert Anfrage an Datenbank mit RÃ¼ckgabe eines<br>
         * Arrays geteilt in Tabellennamen
         * @param string $query
         * @return array[result] array[db_info]
         */
        private function select_table_info($query) {
            if (!isset($this->_arrquery['limit'])) { $query .= "LIMIT 1"; }
            $ret['result'] = array();
            $ret['db_info']['query'] = $query;
            $ret['db_info']['columns'] = $this->_arrquery['columninfo'];
            foreach ($this->_arrquery['columninfo'] as $k => $v) {
                $ret['result'][$k] = $this->_arrquery['columninfo'][$k];
                if (isset($this->_tables[$v['table']]['columns'][$v['name']])) {
                    $value = $this->_tables[$v['table']]['columns'][$v['name']]['default'];
                    if ($this->is_serialized($value)){
                        $value = unserialize($value);
                    }
                    if (isset($ret['result'][$k]['split'])) {
                        $value = $this->ValueAction('split', array($ret['result'][$k]['split'],$value));
                    }
                    $ret['result'][$k]['value'] = $value;
                }}
            if(isset($this->_arrquery['pager'])){
                $ret = $this->FOUND_ROWS($ret);
            }
            $this->cleanQuery();
            return $ret;
        }

        private function sqlactions($row) {
            if (isset($this->_arrquery['actions'])) {
                foreach ($this->_arrquery['actions'] as $action => $fields) {
                    switch ($action) {
                        case 'split':	foreach ($fields as $k => $v) {
                            if (isset($row[$k])) {
                                if (!empty($row[$k])) {
                                    $row[$k] = explode($v, $row[$k]);
                                } else {
                                    $row[$k] = array();
                                }}}
                            break;
                        default:		echo $action;
                            if (function_exists($action)) {
                                foreach ($fields as $k => $v) {
                                    if (isset($row[$k])) {
                                        if (!empty($row[$k])) {
                                            $row[$k] = $action($row[$k]);
                                        } else {
                                            $row[$k] = array();
                                        }}}}
                            break;
                    }}}
            return $row;
        }

        protected function ValueAction($action,$option) {
            switch ($action) {
                case 'split':	$ret = array();
                    if (is_string($option[1])) {
                        $ret = explode($option[0], $option[1]);
                        foreach ($ret as $k => $v) {
                            $ret[$k] = trim($v);
                        }
                    } else if (is_array($option[1])) {
                        $ret = implode($option[0], $option[1]);
                    }
                    break;
                default:		if (is_array($action)) {
                    foreach ($action as $function => $args) {
                        if (function_exists($function)) {
                            $args = $this->ValueActionHelper($args,$option);
                            $ret = call_user_func_array($function,$args);
                        } else {
                            $ret = $option;
                        }}}}
            return $ret;
        }

        protected function ValueActionHelper($args,$value) {
            if (is_array($args)) {
                foreach ($args as $k => $v) {
                    $args[$k] = $this->ValueActionHelper($v, $value);
                }
            } else {
                $args = str_replace('{{value}}', $value, $args);
            }
            return $args;
        }

        /**
         * Gibt die SQL Fehlermeldung aus wenn der
         * Requerst nicht erfolgreich war.
         * @param result $res
         * @param string $query
         */
        private function sqlerror($res,$query) {
            if (!$res AND $this->_develop){echo	'<h1>SQL Fehler</h1>'.
                '<p>'.$this->_dbconnect->error.'</p>'.
                '<h2>Query</h2>'.
                '<p>'.$query.'</p>'.
                '<h2>Debug</h2>'.
                '<pre>';
                debug_print_backtrace();
                print_r($this->getArrQuery());
                echo			'</pre>';
                exit;}
        }

        /**
         * Erzeugt SELECT string
         * @param array $arrWerte
         * @return string
         */
        private function select_build($arrWerte) {
            $arrquery = array('SELECT');
            if (isset($arrWerte['pager'])) {
                array_push($arrquery, 'SQL_CALC_FOUND_ROWS');
            }
            if(isset($this->_arrquery['distinct']) AND $this->_arrquery['distinct']){
                array_push($arrquery, 'DISTINCT');
            }
            if(isset($this->_arrquery['subquery'])){
                array_push($arrquery, '* FROM ('.$this->_arrquery['subquery'].') AS r');

                /* WHERE */
                if (isset($arrWerte['where'])) {
                    array_push($arrquery, 'WHERE');
                    array_push($arrquery, $this->getwhere($arrWerte['where']));
                }

                /* GROUP */
                if (isset($arrWerte['group'])) {
                    array_push($arrquery, 'GROUP BY');
                    array_push($arrquery, implode(',', $arrWerte['group']));
                }

                /* ORDER BY */
                if (isset($arrWerte['order'])) {
                    array_push($arrquery, 'ORDER BY');
                    array_push($arrquery, implode(',', $arrWerte['order']));
                }

                /* LIMIT */
                if (isset($arrWerte['limit'])) {
                    array_push($arrquery, ' LIMIT');
                    array_push($arrquery, implode(',', $arrWerte['limit']));
                }
            }else{
                $arrtable = array();
                $arrcolumn = array();
                $arrjoin = array();
                $x=1;
                foreach ($arrWerte['config'] as $config){
                    $table = $config['table'];
                    $ta = NULL;
                    if(isset($config['as'])){
                        $table = $config['as'];
                        $ta = ' '.$table;
                    }
                    switch ($config['sqltype']){
                        case 'table':		array_push($arrtable, $config['table'].$ta);
                            break;

                        case 'join':		array_push($arrjoin, 'JOIN `'.$config['table'].'`'.$ta.' ON ('.$config['joinon'].')');
                            break;

                        case 'joinleft':	array_push($arrjoin, 'LEFT JOIN `'.$config['table'].'`'.$ta.' ON ('.$config['joinon'].')');
                            break;

                        case 'joinright':	array_push($arrjoin, 'RIGHT JOIN `'.$config['table'].'`'.$ta.' ON ('.$config['joinon'].')');
                            break;
                    }

                    if(!empty($config['column'])){
                        foreach ($config['column'] as $c){

                            if (isset($c['table']) AND isset($this->_tables[$c['table']]['columns'][$c['name']])) {
                                //if (isset($c['table'])) {
                                if (isset($c['command'])) {
                                    if (preg_match('#(.*)\((.*)\)#', $c['command'])) {
                                        $str = $c['command'];
                                        if (!isset($c['as'])) {$c['as']=$c['name'];}
                                    }else{
                                        $str=strtoupper($c['command']).'(`'.$table.'`.`'.$c['name'].'`)';
                                    }
                                } else {
                                    if(isset($this->_crypt_columns[$c['table']][$c['name']])){
                                        switch (strtolower($this->_crypt_type)){
                                            default:	$str='AES_DECRYPT(`'.$table.'`.`'.$c['name'].'`,"'.$this->_crypt_cipher.'")';
                                                break;
                                        }
                                        if(!isset($c['as'])){
                                            $c['as']=$c['name'];
                                        }
                                    }else{
                                        $str='`'.$table.'`.`'.$c['name'].'`';
                                    }}

                                if (isset($c['as'])){
                                    $str.=' AS '.$c['as'];
                                    $this->_arrquery['columninfo'][$c['as']]=$c;
                                }else{
                                    $this->_arrquery['columninfo'][$c['name']]=$c;
                                }
                                array_push($arrcolumn, $str);
                            }else{
                                if (isset($c['command'])) {
                                    if (preg_match('#(.*)\((.*)\)#', $c['command'])) {
                                        $str = $c['command'];
                                        if (!isset($c['as'])) {
                                            $c['as'] = $c['name'];
                                        }
                                    }else{
                                        #####################################################################
                                        ##              15.05.2015                                         ##
                                        ## z.Bb: SELECT SUM(id * weight) AS total FROM io_productimport    ##
                                        #####################################################################
                                        // $str = strtoupper($c['command']).'(`'.$table.'`.`'.$c['name'].'`)';
                                        $str = strtoupper($c['command']).'('.$c['name'].')';
                                    }
                                    if (isset($c['as'])){
                                        $str .= ' AS '.$c['as'];
                                        $this->_arrquery['columninfo'][$c['as']]=$c;
                                    }else{
                                        $this->_arrquery['columninfo'][$c['name']]=$c;
                                    }
                                    array_push($arrcolumn, $str);
                                }}}}}

                /* Spalten */
                array_push($arrquery, implode(',', $arrcolumn));

                array_push($arrquery, 'FROM');
                /* Tables */
                array_push($arrquery, implode(',', $arrtable));

                /* JOIN */
                empty($arrjoin) OR array_push($arrquery, implode(' ', $arrjoin));

                /* WHERE */
                if (isset($arrWerte['where'])) {
                    array_push($arrquery, 'WHERE');
                    array_push($arrquery, $this->getwhere($arrWerte['where']));
                }

                /* GROUP */
                if (isset($arrWerte['group'])) {
                    array_push($arrquery, 'GROUP BY');
                    array_push($arrquery, implode(',', $arrWerte['group']));
                }

                /* ORDER BY */
                if (isset($arrWerte['order'])) {
                    array_push($arrquery, 'ORDER BY');
                    array_push($arrquery, implode(',', $arrWerte['order']));
                }

                /* LIMIT */
                if (isset($arrWerte['limit'])) {
                    array_push($arrquery, ' LIMIT');
                    array_push($arrquery, implode(',', $arrWerte['limit']));
                }}
            return implode(' ', $arrquery);
        }

        private function FOUND_ROWS($ret){
            $res = $this->_dbconnect->query('SELECT FOUND_ROWS()');
            $res = $res->fetch_array();
            $ret['db_info']['data_sum']		= $res[0];
            $ret['db_info']['limit']		= $this->_arrquery['pager']['limit'];
            $ret['db_info']['offset']		= $this->_arrquery['pager']['offset'];
            $ret['db_info']['page_current']	= $this->_arrquery['pager']['page_current'];
            $ret['db_info']['page_count']	= ceil($ret['db_info']['data_sum'] / $ret['db_info']['limit']);
            if ($ret['db_info']['page_current']<2) {
                $ret['db_info']['page_prev'] = false;
            } else {
                $ret['db_info']['page_prev'] = $ret['db_info']['page_current']-1;
            }
            if ($ret['db_info']['page_current']<$ret['db_info']['page_count']) {
                $ret['db_info']['page_next']	= $ret['db_info']['page_current']+1;
            }else{
                $ret['db_info']['page_next']	= FALSE;
            }
            return $ret;
        }

        public function toString($command='select'){
            $ret = NULL;
            switch (strtolower($command)){
                case 'select':
                    if (isset($this->_arrquery['pager'])) {
                        $this->limit($this->_arrquery['pager']['offset'].",".$this->_arrquery['pager']['limit']);
                    }
                    $ret = $this->select_build($this->_arrquery);
                    $this->_arrquery['query']=$ret;
                    break;
            }
            return $ret;
        }
        /****************************** Befehle *******************************/
        /**
         * Select Anfrage wird an Server gestellt
         *
         * @param String $ReturnFormat
         * @param String $Returnkey
         * @return abhaengig vom ReturnFormat:
         * array	= Array(beginnend bei 0)
         * string	= String(generierter Query String)
         * one		= String(einzelnes Ergebnis)
         * first 	= Array(erster Datensatz)
         * key		= Array[$Returnkey](Datensatz)
         *
         */
        public function select($ReturnFormat="array",$Returnkey=false) {
            if (!isset($this->_arrquery['query'])) {
                if (isset($this->_arrquery['pager'])) {
                    if (is_null($this->_arrquery['pager']['page_from'])) {
                        $offset = $this->_arrquery['pager']['offset'];
                        $limit = $this->_arrquery['pager']['limit'];
                    }else{
                        $int = $this->_arrquery['pager']['page_current'] - $this->_arrquery['pager']['page_from'] + 1;
                        $offset = $this->_arrquery['pager']['page_from'] * $this->_arrquery['pager']['limit'] - $this->_arrquery['pager']['limit'];
                        $limit = $int * $this->_arrquery['pager']['limit'];
                    }
                    $this->limit($offset.",".$limit);
                }
                $query = $this->select_build($this->_arrquery);
            }else{
                $query = $this->_arrquery['query'];
            }

            switch ($ReturnFormat) {
                case 'string':		$this->cleanQuery();
                    return $query;
                    break;

                case 'one':			return $this->select_one($query);
                    break;

                case 'first':		return $this->select_first($query);
                    break;

                case 'row':			return $this->select_row($query);
                    break;

                case 'key':			return $this->select_key($query, $Returnkey);
                    break;

                case 'keyvalue':	return $this->select_keyvalue($query, $Returnkey);
                    break;

                case 'keyvalueres':	$res =  $this->select_keyvalue($query, $Returnkey); return $res['result'];
                    break;

                case 'multikey':	return $this->select_multikey($query, $Returnkey);
                    break;

                case 'array':		return $this->select_array($query);
                    break;

                case 'result':		$res =  $this->select_array($query); return $res['result'];
                    break;

                case 'db_info':		$res =  $this->select_array($query); return $res['db_info'];
                    break;

                case 'table':		return $this->select_table($query);
                    break;

                case 'table_info':	return $this->select_table_info($query);
                    break;
                case 'object':
                    /*
                    * @return mysqli
                    */
                    return $this->select_object($query);
                    break;
            }}

        private function findInsertColumn($row,$map){
            $diff = array_diff(array_keys($row),$this->_map);
            foreach ($diff as $k){
                if (isset($this->_arrquery['columninfo'][$k])) {
                    $this->_map[$k] = $k;
                }}
        }

        private function insertValue($value,$column,$config){
            $ret = NULL;
            switch ($column['type']){
                case 'timestamp':
                case 'date':		if(is_array($value)) {
                    $ret = '"'.$this->build_timestamp_str($value).'"';
                }else{
                    if($column['type']=='timestamp' AND preg_match('/\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}/', $value)) {
                        $ret = '"'.$value.'"';
                    }elseif($column['type']=='date' AND preg_match('/\d{4}\-\d{2}\-\d{2}/', $value)) {
                        $ret = '"'.$value.'"';
                    }else{
                        if(
                            is_null($value) OR
                            (string)$value==='NULL' OR
                            (string)$value==''
                        ){
                            if ($column['null']=='YES'){
                                $ret = 'NULL';
                            }else{
                                $ret = '"0000-00-00 00:00:00"';
                            }
                        }else{
                            $ctst = strtoupper($value);
                            if(substr($ctst,0,3)=='NOW'){
                                $ret='NOW()';
                            }else{
                                switch ($this->_language_name) {
                                    default:
                                        $datum = new \DateTime($value);
                                        $date = explode('.',$datum->format('d.m.Y'));
                                        $date['day']    = $date[0];
                                        $date['month']  = $date[1];
                                        $date['year']   = $date[2];
                                        $ret = '"'.$this->build_timestamp_str($date).'"';
                                        break;
                                }}}}}
                    break;

                default:			if(isset($column['split'])){
                    $value = implode($column['split'],$value);
                }
                    if(is_array($value) OR is_object($value)){
                        $value = serialize($value);
                    }
                    if(
                        (
                            is_null($value) OR
                            (string)$value==='NULL' OR
                            (string)$value==''
                        )
                        AND $column['null']=='YES'
                    ){
                        $ret = 'NULL';
                    }else{
                        $value = $this->cleanInsert($value);
                        if(isset($column['db_command'])){
                            $ret = strtoupper($column['db_command']).'("'.$value.'")';
                        }else{
                            if(isset($this->_crypt_columns[$config['table']][$column['name']])){
                                switch (strtolower($this->_crypt_type)){
                                    default:	$ret = 'AES_ENCRYPT("'.$value.'","'.$this->_crypt_cipher.'")';
                                        break;
                                }
                            }else{
                                $ret = '"'.$value.'"';
                            }}}
                    break;
            }
            return $ret;
        }

        /**
         * Insert Befehl an Datenbank senden
         * @param Array $ArrayInsert
         * @return	$ReturnFormat insert_id int
         * 			$ReturnFormat string string
         */
        public function insert($ArrayInsert,$ReturnFormat=NULL,$multiinsert=FALSE) {
            !is_null($ReturnFormat) OR $ReturnFormat='insert_id';
            $config = current($this->_arrquery['config']);
            $table = $config['table'];

            $this->_map = array();
            $arrValues = array();
            if($multiinsert){
                array_walk($ArrayInsert, array($this,'findInsertColumn'));
                foreach ($ArrayInsert as $v){
                    $arrRow = array();
                    foreach ($this->_map as $c){
                        if(isset($v[$c])){
                            $arrRow[$c]=$this->insertValue($v[$c], $this->_arrquery['columninfo'][$c],$config);
                        }else{
                            if($this->_arrquery['columninfo'][$c]['null']=='YES'){
                                $arrRow[$c]='NULL';
                            }else{
                                $arrRow[$c]='';
                            }
                        }
                    }
                    array_push($arrValues, implode(',', $arrRow));
                }
            }else{
                foreach ($ArrayInsert as $k => $v) {
                    if (isset($this->_arrquery['columninfo'][$k])) {
                        $this->_map[$k] = $k;
                    }
                }
                foreach ($this->_map as $c){
                    $arrRow[$c]=$this->insertValue($ArrayInsert[$c], $this->_arrquery['columninfo'][$c], $config);
                }
                array_push($arrValues, implode(',', $arrRow));
            }
            $arrSpalten = '`'.implode('`,`', $this->_map).'`';
            $arrValues = '('.implode('),(', $arrValues).')';
            $StrQuery = 'INSERT INTO '.$config['table'].' ('.$arrSpalten.') VALUES '.$arrValues;
            unset($this->_map);
            switch ($ReturnFormat) {
                case "insert_id":	$this->Debug($StrQuery);
                    $this->_dbconnect->query($StrQuery);
                    $this->cleanQuery();
                    return $this->_dbconnect->insert_id;
                    break;
                case "string":		$this->cleanQuery();
                    return $StrQuery;
                    break;
            }}

        /**
         * Insert on duplicat Update Befehl an Datenbank senden
         * @param Array $ArrayInsert
         * @return	$ReturnFormat insert_id int
         * 			$ReturnFormat string string
         */
        public function save($ArrayInsert,$ReturnFormat=NULL,$exclude=array()) {
            $ReturnFormat OR $ReturnFormat = 'insert_id';
            $config = current($this->_arrquery['config']);
            $primary = $this->_tables[$config['table']]['primary'];

            foreach ($primary as $v){
                if (!isset($ArrayInsert[$v]) AND !is_null($ArrayInsert[$v])){
                    die('Bitte alle Primärschlüssel für den Befehl save Übergeben.');
                }}

            $map = array();
            foreach ($ArrayInsert as $k => $v) {
                if (isset($this->_arrquery['columninfo'][$k])) {
                    $c = $this->_arrquery['columninfo'][$k];
                    $c['value']=$v;
                    $map[$c['name']] = $c;
                }}
            $arrSpalten = array();
            $arrValues = array();
            foreach ($map as $key => $value) {
                $arrSpalten[] = $value['name'];
                switch ($value['type']) {
                    case 'timestamp':
                    case 'date':		if (is_array($value['value'])) {
                        $value['value'] = $this->build_timestamp_str($value['value']);
                        $arrValues[] = '"'.$value['value'].'"';
                    } else {
                        if ($value['type']=='timestamp' AND preg_match('/\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}/', $value['value'])) {
                            $arrValues[] = '"'.$value['value'].'"';
                        } else if ($value['type']=='date' AND preg_match('/\d{4}\-\d{2}\-\d{2}/', $value['value'])) {
                            $arrValues[] = '"'.$value['value'].'"';
                        } else {
                            if (
                                (
                                    is_null($value['value']) OR
                                    (string)$value['value']==='NULL' OR
                                    (string)$value['value']==''
                                )
                                AND $value['null']=='YES'
                            ) {
                                $arrValues[] = $value['value'];
                            }else{
                                $ctst = strtoupper($value['value']);
                                if (substr($ctst,0,3)=='NOW') {
                                    $value['value'] = 'NOW()';
                                    $arrValues[] = 'NOW()';
                                } else {
                                    switch ($this->_language_name) {
                                        default:	$datum = explode('.',$value['value']);
                                            $datum['day'] = $datum[0];
                                            $datum['month'] = $datum[1];
                                            $datum['year'] = $datum[2];
                                            $value['value'] = $this->build_timestamp_str($datum);
                                            break;
                                    }
                                    $arrValues[] = '"'.$value['value'].'"';
                                }}}}
                        break;
                    default:			if (isset($value['split'])) {
                        $value['value'] = implode($value['split'],$value['value']);
                    }
                        if (is_array($value['value']) OR is_object($value['value'])) {
                            $value['value'] = serialize($value['value']);
                        }
                        if (
                            (
                                is_null($value['value']) OR
                                (string)$value['value']==='NULL' OR
                                (string)$value['value']==''
                            )
                            AND $value['null']=='YES'
                        ) {
                            $arrValues[] = 'NULL';
                        }else{
                            $value['value'] = $this->cleanInsert($value['value']);
                            if (isset($value['db_command'])) {
                                $arrValues[] = strtoupper($value['db_command']).'("'.$value['value'].'")';
                            } else {
                                if(isset($this->_crypt_columns[$config['table']][$value['name']])){
                                    switch (strtolower($this->_crypt_type)){
                                        default:	$arrValues[] = 'AES_ENCRYPT("'.$value['value'].'","'.$this->_crypt_cipher.'")';
                                            break;
                                    }
                                }else{
                                    $arrValues[] = '"'.$value['value'].'"';
                                }}}
                        break;
                }}
            $StrQuery = 'INSERT INTO '.$config['table'].' (`'.implode('`,`', $arrSpalten).'`) VALUES ('.implode(',', $arrValues).') ON DUPLICATE KEY UPDATE ';
            $update = array();
            foreach ($arrSpalten as $k => $v){
                if (!in_array($v, $primary) AND !in_array($v, $exclude)) {
                    array_push($update, $v.'='.$arrValues[$k]);
                }}
            $StrQuery .= implode(', ', $update);
            switch ($ReturnFormat) {
                case "insert_id":	$this->Debug($StrQuery);
                    $this->_dbconnect->query($StrQuery);
                    $this->cleanQuery();
                    return $this->_dbconnect->insert_id;
                    break;
                case "string":		$this->cleanQuery();
                    return $StrQuery;
                    break;
            }
        }

        /**
         * Update Befehl an Datenbank senden
         * @param Array $ArrayInsert
         * @return	$ReturnFormat erfolg bool
         * 			$ReturnFormat string string
         */
        public function update($ArrayUpdate,$ReturnFormat="bool") {
            $config = current($this->_arrquery['config']);
            $map = array();
            foreach ($ArrayUpdate as $k => $v) {
                if (isset($this->_arrquery['columninfo'][$k])) {
                    $map[$k] = $this->_arrquery['columninfo'][$k];
                    $map[$k]['value'] = $v;
                }}
            $arrUpdate = array();
            foreach ($map as $key => $value) {
                if ($value['key'] == 'PRI') {
                    $value['value'] = $this->cleanInsert($value['value']);
                    $where = "WHERE `".$value['name']."`='".$value['value']."'";
                } else {
                    switch ($value['type']) {
                        case 'timestamp':
                        case 'date':		if (is_array($value['value'])) {
                            $value['value'] = $this->build_timestamp_str($value['value']);
                            $arrUpdate[] = '`'.$value['name'].'`="'.$value['value'].'"';
                        } else {
                            if ($value['type']=='timestamp' AND preg_match('/\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}/', $value['value'])) {
                                $arrUpdate[] = '`'.$value['name'].'`="'.$value['value'].'"';
                            } else if ($value['type']=='date' AND preg_match('/\d{4}\-\d{2}\-\d{2}/', $value['value'])) {
                                $arrUpdate[] = '`'.$value['name'].'`="'.$value['value'].'"';
                            } else {
                                if (
                                    (
                                        is_null($value['value']) OR
                                        (string)$value['value']==='NULL' OR
                                        (string)$value['value']==''
                                    )
                                    AND $value['null']=='YES'
                                ) {
                                    $arrUpdate[] = '`'.$value['name'].'`=NULL';
                                }else{
                                    $ctst = strtoupper($value['value']);
                                    if (substr($ctst,0,3)=='NOW') {
                                        $value['value'] = 'NOW()';
                                        $arrUpdate[] = '`'.$value['name'].'`=NOW()';
                                    } else {
                                        switch ($this->_language_name) {
                                            default:	$datum = explode('.',$value['value']);
                                                $datum['day'] = $datum[0];
                                                $datum['month'] = $datum[1];
                                                $datum['year'] = $datum[2];
                                                $value['value'] = $this->build_timestamp_str($datum);
                                                break;
                                        }
                                        $arrUpdate[] = '`'.$value['name'].'`="'.$value['value'].'"';
                                    }}}}
                            break;
                        default:			if (isset($value['split'])) {
                            $value['value'] = implode($value['split'], $value['value']);
                        }
                            if (is_array($value['value']) OR is_object($value['value'])) {
                                $value['value'] = serialize($value['value']);
                            }
                            if (
                                (
                                    is_null($value['value']) OR
                                    (string)$value['value']==='NULL' OR
                                    (string)$value['value']==''
                                )
                                AND $value['null']=='YES'
                            ){
                                $arrUpdate[] = '`'.$value['name'].'`=NULL';
                            }else{
                                $value['value'] = $this->cleanInsert($value['value']);
                                if (isset($value['db_command'])) {
                                    $arrUpdate[] = '`'.$value['name'].'`='.strtoupper($value['db_command']).'("'.$value['value'].'")';
                                } else {
                                    if(isset($this->_crypt_columns[$config['table']][$value['name']])){
                                        switch (strtolower($this->_crypt_type)){
                                            default:
                                                $arrUpdate[] = '`'.$value['name'].'`=AES_ENCRYPT("'.$value['value'].'","'.$this->_crypt_cipher.'")';
                                                break;
                                        }
                                    }else{
                                        $arrUpdate[] = '`'.$value['name'].'`="'.$value['value'].'"';
                                    }}}
                            break;
                    }}}
            $arrUpdate = implode(',', $arrUpdate).' ';
            if (isset($this->_arrquery['where'])) {
                $where = 'WHERE '.$this->getwhere($this->_arrquery['where']);
            }
            if (isset($where)) {
                $StrQuery = "UPDATE ".$config['table']." SET ".$arrUpdate.$where;
                $this->cleanQuery();
                switch ($ReturnFormat) {
                    case "bool":
                        $this->Debug($StrQuery);
                        if ($this->_dbconnect->query($StrQuery)) {
                            return true;
                        } else {
                            return false;
                        }
                        break;

                    case 'affected_rows':
                        $this->Debug($StrQuery);
                        if ($this->_dbconnect->query($StrQuery)) {
                            return $this->_dbconnect->affected_rows;
                        } else {
                            return false;
                        }
                        break;

                    case "string":
                        return $StrQuery;
                        break;
                }
            } else {
                $this->cleanQuery();
                return "Bitte Where Bedingung angeben!";
            }}

        /**
         * Delete Befehl an Datenbank senden
         * @param string (array,key) $ReturnFormat
         * @param string $Returnkey
         * @param bool $Testmode
         * @return	$ReturnFormat array array[result] array[db_info]
         * 			$ReturnFormat key array[result] array[db_info]
         */
        public function delete($ReturnFormat="array",$Returnkey=false,$Testmode=false) {
            if (isset($this->_arrquery['where'])) {
                $config = $this->_arrquery['config'];
                $table = array();
                foreach ($config as $c){
                    array_push($table, $c['table']);
                }
                switch ($ReturnFormat) {
                    case 'string':	$StrQuery =	'DELETE FROM '.implode(',',$table).' '.
                        'WHERE '.$this->getwhere($this->_arrquery['where']);
                        $this->cleanQuery();
                        return $StrQuery;
                        break;

                    default:		$tmparrquery = $this->_arrquery;
                        $ReturnArray = $this->select($ReturnFormat,$Returnkey);
                        if ($ReturnArray['db_info']['count'] > 0 AND $Testmode == false) {
                            $StrQuery =	'DELETE FROM '.implode(',',$table).' '.
                                'WHERE '.$this->getwhere($tmparrquery['where']);
                            $this->Debug($StrQuery);
                            $this->_dbconnect->query($StrQuery);
                            $ReturnArray['db_info']['query'] = $StrQuery;
                            $this->cleanQuery();
                        }
                        return $ReturnArray;
                        break;
                }
            } else {
                $this->cleanQuery();
                return "Bitte Where Bedingung angeben!";
            }}

        public function multiquery($arr) {
            foreach ($arr as $v) {
                $this->Debug($v);
                $this->_dbconnect->query($v);
            }
            $this->cleanQuery();
        }

        /**
         * Direkte SQL Anfrage mit vordefinierter RÃ¼ckgabe
         * @param string $StrQuery
         * @param string (key,array) $ReturnFormat
         * @param string (tabellenspalte) $Returnkey
         * @return	$ReturnFormat array array[result] array[db_info]
         * 			$ReturnFormat key array[result] array[db_info]
         */
        public function query($StrQuery,$ReturnFormat="array",$Returnkey=false) {
            if(preg_match('#(^SELECT)#i', $StrQuery)) {
                switch ($ReturnFormat) {
                    case 'key':	return $this->select_key($StrQuery, $Returnkey);
                        break;

                    case 'array':return $this->select_array($StrQuery);
                        break;
                    case 'object':return $this->db_query($StrQuery);
                        break;
                    case 'string':		$this->cleanQuery();
                        return $StrQuery;
                        break;
                }
            }elseif(preg_match('#(^INSERT)#i', $StrQuery)) {
                $this->_dbconnect->query($StrQuery);
                return $this->_dbconnect->insert_id;
            }elseif(preg_match('#(^UPDATE)|(^DELETE)|(^REPLACE)#i', $StrQuery)) {
                $this->_dbconnect->query($StrQuery);
                return $this->_dbconnect->affected_rows;
            }else{
                return $this->_dbconnect->query($StrQuery);
            }}

        /**
         * Leert den aktuellen Request
         * @return DBConnect
         */
        public function cleanQuery() {
            $this->_arrquery = array('config'=>array(),'columninfo'=>array());
            return $this;
        }

        /**
         * Getter und Setter
         */
        public function getArrQuery($key=NULL){
            $ret = NULL;
            if (!is_null($key)) {
                if (is_array($key)){
                    foreach ($key as $k => $v){
                        if (isset($this->_arrquery[$v])) {
                            $ret[$v]=$this->_arrquery[$v];
                        }}
                } else {
                    if (isset($this->_arrquery[$key])) {
                        $ret=$this->_arrquery[$key];
                    }}
            }else{
                $ret = $this->_arrquery;
            }
            return $ret;
        }
        /**
         * @param array $_ArrQuery
         * @return DBConnect
         */
        public function setArrQuery($_ArrQuery){$this->_arrquery=$_ArrQuery;return $this;}

        public function format_date($value,$key=NULL){
            $date = $this->build_timestamp_array($value);
            $ret = NULL;
            if ($key) {if (isset($date[$key])) {$ret = $date[$key];}
            }else{$ret = $date;}
            return $ret;
        }

        /**
         * Prueft einen String und erzeugt daraus<br>
         * ein array
         * @param mix $var
         */
        protected static function setarray($var) {
            if (!is_array($var)) {
                $var = explode(',', (string)$var);
            }
            foreach ($var as $k => $v) {
                if (is_string($v)) {
                    $var[$k] = trim($v);
                } else {
                    $var[$k] = $v;
                }}
            return $var;
        }

        /**
         * Ausgabe der Debug wird vorbereitet.
         * @param mixed $var
         */
        public function Debug($var,$label=NULL) {
            if ($this->_develop) {
                $input = NULL;
                if (!isset($this->_session['debug'])){$this->_session['debug']=array();}
                $b = debug_backtrace();
                if (isset($b[2]['file']) AND isset($b[2]['line'])) {
                    $input = '<br />Datei: ' . $b[2]['file'] . '<br />Zeile: ' . $b[2]['line'] . '<br />';
                }
                if (isset($b[3]['class'])){$input .= 'Class: '.$b[3]['class'].'<br />';}
                if (isset($b[3]['function'])){
                    $input .= 'Function: '.$b[3]['function'].'<br />';
                    $label = $b[3]['function'].'()';
                }
                $input .= $var;
                $arr = array(
                    'label'	=> $label,
                    'var'	=> $input,
                );
                array_push($this->_session['debug'], $arr);
            }}

        /**
         * Prueft einen String ob er serializiert<br>
         * wurde
         * @param string $string
         * @return bool
         */
        public function is_serialized( $string ) {
            if (!is_string( $string )){return false;}
            $data = trim( $string );
            if ('N;'==$string){return true;}
            if ( !preg_match('/^([adObis]):/',$data,$badions)){return false;}
            switch ($badions[1]) {
                case 'a':
                case 'O':
                case 's':
                    if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s",$data)){return true;}
                    break;
                case 'b':
                case 'i':
                case 'd':
                    if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/",$data)){return true;}
                    break;
            }
            return false;
        }
        /**
         * @return the $_language_name
         */
        public function getLanguage_name() {
            return $this->_language_name;
        }

        /**
         * @param string $_language_name
         * @return DBConnect
         */
        public function setLanguage_name($_language_name) {
            $this->_language_name = $_language_name;
            return $this;
        }

        /**
         * @param bool $_language_name
         * @return DBConnect
         */
        public function setDevelop($_develop) {
            $this->_develop = $_develop;
            return $this;
        }

        /**
         * Generiert ein SQL Statement zur Erstellung eines Tables<br><br>
         * <b>FÃ¼r das Array sind folgende Knotenpunkte nÃ¶tig:</b><br>
         * $table = array(<br>
         * &nbsp;&nbsp;name =&gt; String Name der Tabelle<br>
         * &nbsp;&nbsp;engine =&gt; String Engine der Tabelle (default: MyISAM)<br>
         * &nbsp;&nbsp;charset =&gt; String Charset der Tabelle (default: latin1)<br>
         * &nbsp;&nbsp;primary =&gt; Array Primary Key der Tabelle<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;key =&gt; Array Keys der Tabelle<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;columns =&gt; array(<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;'type' =&gt; String zB. int,varchar,text<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;'len' =&gt; String,Int LÃ¤nge von Feld oder bei Enum zB. "'_self','_blank'"<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;'null' =&gt; String Optional 'YES' oder 'NO',<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;'default =&gt; String Optional Startwert des Feldes<br>
         * &nbsp;&nbsp;&nbsp;&nbsp;'extra' =&gt; String Optional zB. 'auto_increment'<br>
         * &nbsp;&nbsp;)<br>
         * )<br>
         * @param array $table
         * @return string
         */
        public function createTable($table){
            // Basics
            if (!isset($table['name'])) {
                echo '<div class="error">Create Table kann nicht erstellt werden weil der Knotenpunkt name fehlt!</div>';
                exit;
            }
            if (!isset($table['engine'])) {$table['engine'] = 'MyISAM';}
            if (!isset($table['charset'])) {$table['charset'] = 'latin1';}

            // Columns
            foreach ($table['columns'] as $column => $v){
                switch ($v['type']){
                    case 'int':	if (isset($v['default'])) {
                        if (empty($v['default'])) {
                            $v['default'] = 0;
                        }}
                        if (isset($v['extra'])) {
                            if (!empty($v['extra'])) {
                                switch ($v['extra']){
                                    case 'auto_increment':	unset($v['default']);
                                        break;
                                }}}
                    default:	$str = '`'.$column.'` '.$v['type'];
                        if (isset($v['len'])) {
                            if (!empty($v['len'])) {
                                $str .= '('.$v['len'].')';
                            }}
                        if (isset($v['null'])) {
                            switch ($v['null']){
                                case 'YES': $str .= ' default NULL';
                                    break;
                                default:	$str .= ' NOT NULL';
                                    if (isset($v['default'])) {
                                        $str .= ' default \''.$v['default'].'\'';
                                    }
                                    break;
                            }}
                        if (isset($v['extra'])) {
                            if (!empty($v['extra'])) {
                                switch ($v['extra']){
                                    case 'auto_increment':	$str .= ' auto_increment';
                                        break;
                                }}}
                        $table['columns'][$column] = $str;
                        break;
                }}
            $ret =	'CREATE TABLE IF NOT EXISTS `'.$table['name'].'` ('.
                implode(',', $table['columns']);
            if (isset($table['primary'])) {
                $table['primary'] = self::setarray($table['primary']);
                $ret .=	', PRIMARY KEY  (`'.implode('`,`', $table['primary']).'`)';
            }
            if (isset($table['key'])) {
                $table['key'] = self::setarray($table['key']);
                $ret .= ', KEY `'.$table['key'][0].'`  (`'.implode('`,`', $table['key']).'`)';
            }
            $ret .= ') ENGINE='.$table['engine'].' DEFAULT CHARSET='.$table['charset'].';';
            return $ret;
        }

        /**
         * Erstellt ein INSERT Statement unabhängig der Tabellenprüfung<br>
         * @param string $table
         * @param array $values
         * @return string
         */
        public function createInsert($table,$values){

            $columns = $value = array();

            foreach ($values as $k => $v){
                array_push($columns, '`'.$k.'`');
                array_push($value, "'".$this->cleanInsert($v)."'");
            }
            return 'INSERT INTO `'.$table.'` ('.implode(',', $columns).') VALUES ('.implode(',', $value).')';
        }
        /**
         * @return the $_crypt_type
         */
        public function getCrypt_type() {
            return $this->_crypt_type;
        }

        /**
         * @param string (AES) $_crypt_type
         * @return DBConnect
         */
        public function setCrypt_type($_crypt_type) {
            $this->_crypt_type = $_crypt_type;
            return $this;
        }

        /**
         * @return the $_crypt_cipher
         */
        public function getCrypt_cipher() {
            return $this->_crypt_cipher;
        }

        /**
         * @param string $_crypt_cipher
         * @return DBConnect
         */
        public function setCrypt_cipher($_crypt_cipher) {
            $this->_crypt_cipher = str_replace('"', '\"', $_crypt_cipher);
            return $this;
        }

        /**
         * @param string $table
         * @param string $column
         * @return DBConnect
         */
        public function setCrypt_column($table,$column){
            $this->_crypt_columns[$table][$column]=$column;
            return $this;
        }

        public function getAi($table){
            $ret = NULL;
            if($this->get_table_info($table)){
                $q = 'SHOW TABLE STATUS FROM '.$this->_database.' LIKE "'.$table.'"';
                $ai = $this->query($q);
                $row = $ai->fetch_array(MYSQLI_ASSOC);
                if($row){
                    $ret = $row['Auto_increment'];
                }}
            return $ret;
        }
        public static function xDebug($pmixValue, $strDesc=null, $boolDump=false) {
            $b = debug_backtrace();
            echo '<pre style="text-align:left;font-family:courier;background-color:#cccccc; padding:10px; margin:10px; border:3px ridge #980000; position: relative; z-index:999;">';
            if(!isset($b[1]['file'])){$b[1]['file']=NULL;$b[1]['line']=NULL;}
            echo	'Aufruf:'."\n".
                'Datei: '.$b[0]['file']."\n".
                'Zeile: '.$b[0]['line']."\n\n".
                'Backtrace:'."\n".
                'Datei: '.$b[1]['file']."\n".
                'Zeile: '.$b[1]['line']."\n\n".
                '';
            if($strDesc != null || $strDesc != ''){
                echo '<b>'.$strDesc.'</b><br />';
            }
            $strVarType = gettype($pmixValue);

            if($pmixValue){
                echo 'Vartype: '.$strVarType.'<br />';
            }

            if(is_array($pmixValue)){
                echo "Arraysize: ". count($pmixValue).'<br />';
            }
            echo 'Inhalt: ';

            if($boolDump=="UserClass"){
                foreach (get_declared_classes() as $val){
                    $reflect = new \ReflectionClass($val);
                    if($reflect->isInternal()){
                        //
                    }else{
                        echo '<h2 style="cursor: pointer;border: 1px solid grey; background-color: #FFC2C2; width: 250px; margin-top: 5px; display: table;">Class '.$val.'()</h2>
                            <div style="position: relative; top: -40px;">';
                        foreach($reflect->getMethods() as $reflectmethod) {
                            echo '<p style="margin-left: 20px;">'.$reflectmethod->getName().'()</p>';
                        }
                        echo '</div>';
                    }
                }
            }else
                if($boolDump){
                    var_dump($pmixValue);
                }else{
                    if($strVarType == 'boolean'){
                        if($pmixValue===true){
                            echo 'true';
                        }else{
                            echo 'false';
                        }
                    }else{
                        print_r($pmixValue);
                    }
                }
            echo '</pre>';
        }
    }












function xDebug($pmixValue, $strDesc=null, $boolDump=false) {
$b = debug_backtrace();
echo '<pre style="text-align:left;font-family:courier;background-color:#cccccc; padding:10px; margin:10px; border:3px ridge #980000; position: relative; z-index:999;">';
        if(!isset($b[1]['file'])){$b[1]['file']=NULL;$b[1]['line']=NULL;}
        echo	'Aufruf:'."\n".
            'Datei: '.$b[0]['file']."\n".
            'Zeile: '.$b[0]['line']."\n\n".
            'Backtrace:'."\n".
            'Datei: '.$b[1]['file']."\n".
            'Zeile: '.$b[1]['line']."\n\n".
            '';
        if($strDesc != null || $strDesc != ''){
            echo '<b>'.$strDesc.'</b><br />';
        }
        $strVarType = gettype($pmixValue);

        if($pmixValue){
            echo 'Vartype: '.$strVarType.'<br />';
        }

        if(is_array($pmixValue)){
            echo "Arraysize: ". count($pmixValue).'<br />';
        }
        echo 'Inhalt: ';

        if($boolDump=="UserClass"){
            foreach (get_declared_classes() as $val){
                $reflect = new \ReflectionClass($val);
                if($reflect->isInternal()){
                    //
                }else{
                    echo '<h2 style="cursor: pointer;border: 1px solid grey; background-color: #FFC2C2; width: 250px; margin-top: 5px; display: table;">Class '.$val.'()</h2>
						<div style="position: relative; top: -40px;">';
                            foreach($reflect->getMethods() as $reflectmethod) {
                            echo '<p style="margin-left: 20px;">'.$reflectmethod->getName().'()</p>';
                            }
                            echo '</div>';
                }
            }
        }else
            if($boolDump){
                var_dump($pmixValue);
            }else{
                if($strVarType == 'boolean'){
                    if($pmixValue===true){
                        echo 'true';
                    }else{
                        echo 'false';
                    }
                }else{
                    print_r($pmixValue);
                }
            }
        echo '</pre>';
}
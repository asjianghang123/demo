<?php

/**
 * FlowQueryController.php
 *
 * @category QueryAnalysis
 * @package  App\Http\Controllers\QueryAnalysis
 * @author   ericsson <genius@ericsson.com>
 * @license  MIT License
 * @link     https://laravel.com/docs/5.4/controllers
 */
namespace App\Http\Controllers\QueryAnalysis;

use App\Http\Controllers\Common\DataBaseConnection;
use App\Http\Requests;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use PDO;
use Cache;
use App\Models\Mongs\SiteLte;
use App\Models\Mongs\Databaseconns;
use App\Models\Mongs\Users;
use App\Models\Mongs\Template;
use App\Models\Mongs\KpiTempCommon;
use App\Models\Mongs\Kpiformula;

/**
 * 流控指标查询
 * Class FlowQueryController
 *
 * @category QueryAnalysis
 * @package  App\Http\Controllers\QueryAnalysis
 * @author   ericsson <genius@ericsson.com>
 * @license  MIT License
 * @link     https://laravel.com/docs/5.4/controllers
 */
class FlowQueryController extends GetTreeData
{


    /**
     * 获得流控指标查询视图
     *
     * @return mixed
     */
    public function init()
    {
        return view('QueryAnalysis.FlowQuery');

    }//end init()


    /**
     * 上传小区列表文件
     *
     * @return void
     */
    /*public function uploadFile()
    {
        $filename = $_FILES['fileImport']['tmp_name'];
        if (empty($filename)) {
            echo '请选择要导入的文件！';
            exit;
        }

        if (file_exists("common/files/".$_FILES['fileImport']['name'])) {
            unlink("common/files/".$_FILES['fileImport']['name']);
        }

        move_uploaded_file($filename, "common/files/".$_FILES['fileImport']['name']);

        setlocale(LC_ALL, null);
        $files = file("common/files/".$_FILES['fileImport']['name']);
        foreach ($files as $txt) {
            print_r($txt);
        }

    }*///end uploadFile()

        /**
     * 更新Baseline模板
     *
     * @return void
     */
    public function uploadFlowQueryFile()
    {
        $dbc = new DataBaseConnection();
        $db  = $dbc->getDB('mongs', 'mongs');

        $filename=$_FILES['fileImports']['tmp_name'];
        if (empty($filename)) {
            echo 'emptyError';
            exit;
        }
        if (file_exists("common/files/".$_FILES['fileImports']['name'])) {
            unlink("common/files/".$_FILES['fileImports']['name']);
        }
        move_uploaded_file($filename, "common/files/".$_FILES['fileImports']['name']);
        setlocale(LC_ALL, null);


        $handle = fopen("common/files/".$_FILES['fileImports']['name'], 'r');
        $result = $this->inputCsv($handle);

        $len_result = count($result);
        if ($len_result == 0||$len_result==1) {
            echo 'lenError';
            exit;
        }
        $filename = "common/files/".$_FILES['fileImports']['name'];

        
        $data_values = '';
       

        try {
            for ($i = 1; $i < $len_result; $i++) {
            $type         = $result[$i][1];
            $templateName = $result[$i][2];
            $kpiName      = $result[$i][3];
            $kpiFormula   = $result[$i][4];
            $kpiPrecision = $result[$i][5];
            $data_values .= "(NULL,'$type','$templateName','$kpiName','$kpiFormula','$kpiPrecision'),";
            }
        } catch (Exception $e){
            echo 'dataError';
            exit;
        }
        KpiTempCommon::query()->delete();
        $data_values = mb_convert_encoding(substr($data_values, 0, -1), 'UTF-8', array('utf-8', 'gbk', 'latin1', 'big5'));
        // 解析文件编码是UTF-8无需转码
        fclose($handle);
        // 关闭指针
        $sql="insert into kpiTemplateCommon values $data_values";
        $query = $db -> exec($sql);
       
        if ($query) {
            echo "true";
        } else {
            echo 'false';
        }

    }//end uploadFile()

        /**
     * 读取CSV文件
     *
     * @param mixed $handle CSV文件句柄
     *
     * @return string array
     */
    protected function inputCsv($handle)
    {
        $out = array();
        $n   = 0;
        while ($data = fgetcsv($handle, 10000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
                $out[$n][$i] = $data[$i];
            }

            $n++;
        }

        return $out;

    }//end inputCsv()
    /**
     * 获得模板列表
     *
     * @return string
     */
    public function getTreeData()
    {   
        $type   = KpiTempCommon::distinct('type')->get(['type'])->toArray();
        $item   = array();
        $itArr  = array();
        $array  = array();
        foreach ($type as $value) {
            $templateNames=KpiTempCommon::where('type', $value['type'])->orderBy('templateName')->groupBy('templateName')->get();
              foreach ($templateNames as $templateName) {
                array_push($array, array("text" => $templateName->templateName, "id" => $templateName->id,"dataSource"=>$templateName->dataSource));
            }
            $items["text"]  = $value['type'];
            $items["nodes"] = $array;
            $array        = array();
            array_push($itArr, $items);
        }
        return response()->json($itArr);

    }//end getTreeData()


    /**
     * 检索指定模板
     *
     * @return mixed
     */
    public function searchLTETreeData()
    {
        $inputData  = Input::get('inputData');
        $inputData  = "%".$inputData."%";

        $type   = KpiTempCommon::distinct('type')->get(['type'])->toArray();
        $item   = array();
        $itArr  = array();
        $array  = array();
        foreach ($type as $value) {
            $templateNames=KpiTempCommon::where('type', $value['type'])->where('templateName', 'like', $inputData)->orderBy('templateName')->groupBy('templateName')->get();
              foreach ($templateNames as $templateName) {
                array_push($array, array("text" => $templateName->templateName, "id" => $templateName->id));
            }
            $items["text"]  = $value['type'];
            $items["nodes"] = $array;
            $array        = array();
            array_push($itArr, $items);
        }
        return response()->json($itArr);
    }//end searchLTETreeData()


    /**
     * 获得城市列表
     *
     * @return string
     */
    public function getAllCity()
    {
        $cityClass = new DataBaseConnection();
        return $cityClass->getCityOptions();

    }//end getAllCity()


    /**
     * 获得子网集合
     *
     * @return mixed
     */
    public function getFormatAllSubNetwork()
    {
        $citys  = Input::get('citys');
        $format = Input::get('format');
        $items  = array();
        foreach ($citys as $city) {
            $databaseConns = Databaseconns::where('cityChinese', $city)->get();
            foreach ($databaseConns as $databaseConn) {
                if ($format == 'TDD') {
                    $subStr = $databaseConn->subNetwork;
                } else if ($format == 'FDD') {
                    $subStr = $databaseConn->subNetworkFdd;
                } else if ($format=='NBIOT') {
                    $subStr = $databaseConn->subNetworkNbiot;
                }
                // print_r($subStr);
                if ($subStr!=NUll) {            
                    $subArr = explode(',', $subStr);
                    foreach ($subArr as $sub) {
                        $city = '{"text":"'.$sub.'","value":"'.$sub.'"}';
                        array_push($items, $city);
                    }
                }
            }
        }

        return response()->json($items);

    }//end getFormatAllSubNetwork()


    /**
     * 获得子网集合
     *
     * @return mixed
     */
    public function getAllSubNetwork()
    {
        //ORM修改
        $citys  = Input::get('citys');
        $format = Input::get('format');
        $items  = array();
        foreach ($citys as $city) {
            $databaseConns = Databaseconns::where('cityChinese', '=', $city)->get();
            foreach ($databaseConns as $databaseConn) {
                if ($format == 'TDD') {
                    $subStr = $databaseConn->subNetwork;
                } else if ($format == 'FDD') {
                    $subStr = $databaseConn->subNetworkFdd;
                } else if ($format=='NBIOT') {
                    $subStr = $databaseConn->subNetworkNbiot;
                }
                if ($subStr!=NULL) { 
                    $subArr = explode(',', $subStr);
                    foreach ($subArr as $sub) {
                        $city = '{"text":"'.$sub.'","value":"'.$sub.'"}';
                        array_push($items, $city);
                    }
                }
            }
        }

        return response()->json($items);

    }//end getAllSubNetwork()


    /**
     * 指标查询
     *
     * @return void
     */
    public function templateQuery()
    {
        $template    = Input::get('template');
        $locationDim = Input::get('locationDim');
        $timeDim     = Input::get('timeDim');
        $startTime   = Input::get('startTime');
        $endTime     = Input::get('endTime');
        $city        = Input::get('city');
        $subNetwork  = Input::get('subNet');
        $format      = Input::get('format');
        $checkStyle  = Input::get('style');
        $parent      = Input::get('parent');
        $result      = array();

        $LoadCounters = new LoadCounters();
        if ($format == 'FDD') {
            $counters = $LoadCounters->loadCountersFDD();
        } else {
            $counters = $LoadCounters->loadCounters();
        }

        $aggTypes = $LoadCounters->loadAggTypes();

        $citysChinese = json_decode($city);

        // 限制导出一百万条除数$numOf_1000000
        $numOf_1000000 = count($citysChinese);
        foreach ($citysChinese as $numCity) {
            if ($numCity == '常州' || $numCity == '南通') {
                $numOf_1000000 = ($numOf_1000000 + 1);
            }
        }

        $cityPY = new DataBaseConnection();
        $citys  = $cityPY->getConnCity($citysChinese);

        $LTEQuery = new LTEQuery();
        $location = $LTEQuery->parseLocation($city, $subNetwork);

        $dbc = new DataBaseConnection();
        $db  = $dbc->getDB('mongs', 'mongs');
        // if ($parent == "通用模板") {
        //     $parent = "admin";
        // } else if ($parent == "系统模板") {
        //     $parent = "system";
        // } else {
        //     $row = Users::where('name',$parent)->first();
        //     if ($row) {
        //         $parent = $row->user;
        //     } 
        // }

        $kpis = $LTEQuery->getKpis($db, $template, $parent);
        // print_r($kpis);
        // return;
        $items = array();

        $time_monitor    = "\r\n查询用户：".Auth::user()->user." 查询时间：".date('Y-m-d H:i:s', (time() + 8 * 3600))." 查询内容：模板-".$template." 区域维度-".$locationDim." 时间维度-".$timeDim." 起始日期-".$startTime." 结束日期-".$endTime;
        $timeSearchStart = strtotime("now");

        $filename = Input::get('template');
        $filename = "common/files/".$filename.date('YmdHis', (time() + 8 * 3600)).uniqid().".csv";
        $filename = preg_replace('/[\\(\\)]/', '-', $filename);
        foreach ($citys as $city) {
            $sql      = "select host,port,dbName,userName,password from databaseconn where connName='".$city."'";
            $res      = $db->query($sql);
            $row      = $res->fetch();
            $host     = $row["host"];
            $port     = $row["port"];
            $dbName   = $row["dbName"];
            $userName = $row["userName"];
            $password = $row["password"];
            $subNets  = "";

            if ($locationDim !== "city") {
                if ($format == 'TDD'||$format=='NBIOT') {
                    $subNets = $LTEQuery->getSubNetsFromLoc($location, $city);
                } else if ($format == 'FDD') {
                    $subNets = $LTEQuery->getSubNetsFDD($db, $city);
                }
            } else {
                if ($format == 'FDD') {
                    $subNets = $LTEQuery->getSubNetsFDD($db, $city);
                } else {
                    $subNets = $LTEQuery->getSubNetsFromLoc($location, $city);
                }
            }

            $pmDbDSN         = $host.":".$port.";dbname=".$dbName;
            // print_r($pmDbDSN);
            // var_dump($userName);
            // var_dump($password);
            // exit;
            $result['error'] = '';
            try {
                $pmDB = sybase_connect($pmDbDSN, $userName, $password);

                // echo '/////////////';
                if ($pmDB == null) {
                    throw new Exception("Sybase服务器连接失败！");
                }
            } catch (Exception $e) {
                $result['error'] = 'Caught exception: Sybase服务器连接失败！';
                // echo '213';
                return json_encode($result);
            }
            // exit;
            $resultText = "";
            $local_flag = $LTEQuery->isLocalQuery($checkStyle);
            if ($local_flag) {
                if ($city == 'changzhou1' || $city == 'nantong1') {
                    continue;
                }
            }

            $queryResult = $LTEQuery->queryTemplate($db, $pmDB, $counters, $timeDim, $locationDim, $startTime, $endTime, $city, $subNets, $resultText, $aggTypes, $format, $local_flag, $filename, $numOf_1000000);
            if (!is_array($queryResult) && (strpos($queryResult, "Caught exception:") !== false)) {
                $result['error'] = $queryResult;
                echo json_encode($result);
                return;
            }

            if ($queryResult == 'NOTFINDLINE') {
                $result['error'] = 'NOTFINDLINE';
                echo json_encode($result);
                return;
            }

            if ($queryResult['state'] == 'overflow') {
                $result['state'] = 'overflow';
            } else {
                $result['state'] = 'normal';
            }

            foreach ($queryResult['rows'] as $qr) {
                array_push($items, $qr);
            }

            $result['text'] = $resultText.implode(',', $kpis['names']);
            unset($pmDB);
        }//end foreach

        $result['total']  = count($items);
        $result['rows']   = $items;
        $result['result'] = 'true';

        $filenameNew        = $this->resultToCSV2($result, $filename, $time_monitor, $timeSearchStart);
        $result['filename'] = $filenameNew;
        if (count($items) > 1000) {
            $result['rows'] = array_slice($items, 0, 1000);
        }
        echo json_encode($result);

    }//end templateQuery()


    /**
     * 写入CSV
     *
     * @param array  $result          查询结果
     * @param string $filename        CSV文件名
     * @param string $time_monitor    请求开始时间
     * @param string $timeSearchStart 查询开始时间
     *
     * @return mixed|string
     */
    protected function resultToCSV2($result, $filename, $time_monitor, $timeSearchStart)
    {
        $filenames  = Input::get('template');
        $filenames  = "common/files/".$filenames.date('YmdHis', (time() + 8 * 3600)).uniqid().".csv";
        $filenames  = preg_replace('/[\\(\\)]/', '-', $filenames);
        $csvContent = mb_convert_encoding($result['text']."\n", 'gbk', 'utf-8');
        $locationDim = Input::get('locationDim');
        if ($locationDim == 'cell' || $locationDim == 'erbs') {
            $resultText = explode(',', $result['text']);
            // array_shift($resultText);
            // array_shift($resultText);
            $name = implode(',', $resultText);
            $csvContent = mb_convert_encoding($name."\n", 'gbk', 'utf-8');
        } else {
            $csvContent = mb_convert_encoding($result['text']."\n", 'gbk', 'utf-8');
        }

        $fp         = fopen($filenames, "a");
        fwrite($fp, $csvContent);
        $handle = fopen($filename, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                $buffer = mb_convert_encoding($buffer, 'gbk', 'utf-8');
                fwrite($fp, $buffer);
            }

            fclose($handle);
        }

        fclose($fp);

        $monitorTime      = (strtotime("now") - $timeSearchStart);
        $filename_monitor = "common/txt/monitor_LTE.txt";
        // 检测LTE查询
        $fp_monitor = fopen($filename_monitor, "a");
        if (filesize($filenames) >= 1000000000) {
            // 1G WARNING
            $time_monitor = $time_monitor." 文件名：".$filenames." (WARNING)文件大小：".filesize($filenames)."字节 耗时：".$monitorTime."s";
        } else {
            $time_monitor = $time_monitor." 文件名：".$filenames." 文件大小：".filesize($filenames)."字节 耗时：".$monitorTime."s";
        }

        clearstatcache();
        // 清除缓存
        fwrite($fp_monitor, $time_monitor);
        fclose($fp_monitor);

        unlink($filename);
        return $filenames;

    }//end resultToCSV2()

    /**
     * 获得日期列表
     *
     * @return array
     */
    public function LTETime()
    {
        $dbc    = new DataBaseConnection();
        $db     = $dbc->getDB('mongs', 'CountersBackup_changzhou');
        $table  = 'DC_E_ERBS_EUTRANCELLRELATION_HOUR';
        $result = array();
        $sql    = "select distinct date_id from $table";
        $rs     = $db->query($sql, PDO::FETCH_ASSOC);
        $test   = [];
        if ($rs) {
            $rows = $rs->fetchall();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $arr = explode(' ', $row['date_id']);
                    if ($arr[0] == '0000-00-00') {
                        continue;
                    }

                    array_push($test, $arr[0]);
                }

                return $test;
            } else {
                $result['error'] = 'error';
                return $result;
            }
        } else {
            $result['error'] = 'error';
            return $result;
        }//end if

    }//end LTETime()


}//end class


/**
 * 加载计数器集合
 * Class LoadCounters
 *
 * @category QueryAnalysis
 * @package  App\Http\Controllers\QueryAnalysis
 * @author   ericsson <genius@ericsson.com>
 * @license  MIT License
 * @link     https://laravel.com/docs/5.4/controllers
 */
class LoadCounters
{


    /**
     * 获得AVG聚合类型的计数器集合
     *
     * @return array
     */
    public function loadAggTypes()
    {
        $aggTypeDefs = file("common/txt/AggTypeDefine.txt");
        $aggTypes    = array();

        foreach ($aggTypeDefs as $aggTypeDef) {
            $aggType = explode("=", $aggTypeDef);
            $aggTypes[$aggType[0]] = $aggType[1];
        }

        return $aggTypes;

    }//end loadAggTypes()


    /**
     * 获得计数器集合
     *
     * @return array
     */
    public function loadCounters()
    {
        if (file_exists("common/txt/Counters.txt")) {
            $result = $this->loadCountersFromFile();
        } else {
            $result = $this->loadCountersFromDB();
        }

        return $result;

    }//end loadCounters()


    /**
     * 通过文件加载计数器集合
     *
     * @return array
     */
    protected function loadCountersFromFile()
    {
        $result = array();
        $lines  = file("common/txt/Counters.txt");
        foreach ($lines as $line) {
            $pair = explode("=", $line);
            if (input::get('format')=="NBIOT") {
                if (trim($pair[1])=='DC_E_ERBS_NBIOTCELL_V'||trim($pair[1])=='DC_E_ERBS_NBIOTCELL') {
                    $result[$pair[0]] = $pair[1];                
                }//获取NBIOT集合
            } else {
                if (trim($pair[1])!='DC_E_ERBS_NBIOTCELL_V'&&trim($pair[1])!='DC_E_ERBS_NBIOTCELL') {
                    $result[$pair[0]] = $pair[1];                
                }
            }
      
        }
        return $result;

    }//end loadCountersFromFile()


    /**
     * 通过ENIQ加载计数器集合
     *
     * @return array
     */
    protected function loadCountersFromDB()
    {
        $host   = '10.40.57.148';
        $port   = '2640';
        $dbName = 'dwhdb';
        $dsn    = "dblib:host=".$host.":".$port.";".((float)phpversion()>7.0?'dbName':'dbname')."=".$dbName;
        $db     = new PDO($dsn, 'dcbo', 'dcbo');
        $tables = file("common/txt/Tables.txt");
        $result = array();
        $out    = "";
        foreach ($tables as $table) {
            $table = trim($table);
            if ($table == "") {
                continue;
            }

            $table = substr($table, 3);
            $sql   = "select a.name as Field from dbo.syscolumns a, dbo.sysobjects b where a.id=b.id and b.name='$table"."_day'";
            $res   = $db->query($sql, PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                if (stripos($row['Field'], "pm") === false) {
                    continue;
                }

                $result[strtolower($row['Field'])] = $table;
                $out = $out.strtolower($row['Field'])."=".$table."\n";
            }
        }

        file_put_contents("common/txt/Counters.txt", $out);
        return $result;

    }//end loadCountersFromDB()


    /**
     * 加载FDD计数器集合
     *
     * @return array
     */
    public function loadCountersFDD()
    {
        if (file_exists("common/txt/Counters_FDD.txt")) {
            $result = $this->loadCountersFromFileFDD();
        } else {
            $result = $this->loadCountersFromDBFDD();
        }

        return $result;

    }//end loadCounters_FDD()


    /**
     * 通过文件获得FDD计数器集合
     *
     * @return array
     */
    protected function loadCountersFromFileFDD()
    {
        $result = array();
        $lines  = file("common/txt/Counters_FDD.txt");
        foreach ($lines as $line) {
            $pair = explode("=", $line);
            $result[$pair[0]] = $pair[1];
        }

        return $result;

    }//end loadCountersFromFile_FDD()


    /**
     * 通过数据库获得FDD计数器集合
     *
     * @return array
     */
    protected function loadCountersFromDBFDD()
    {
        $host   = '10.40.57.148';
        $port   = '2640';
        $dbName = 'dwhdb';
        $dsn    = "dblib:host=".$host.":".$port.";".((float)phpversion()>7.0?'dbName':'dbname')."=".$dbName;
        $db     = new PDO($dsn, 'dcbo', 'dcbo');
        $tables = file("common/txt/Tables_FDD.txt");
        $result = array();
        $out    = "";
        foreach ($tables as $table) {
            $table = trim($table);
            if ($table == "") {
                continue;
            }

            $table = substr($table, 3);
            $sql   = "select a.name as Field from dbo.syscolumns a, dbo.sysobjects b where a.id=b.id and b.name='$table"."_day'";
            $res   = $db->query($sql, PDO::FETCH_ASSOC);
            foreach ($res as $row) {
                if (stripos($row['Field'], "pm") === false) {
                    continue;
                }

                $result[strtolower($row['Field'])] = $table;
                $out = $out.strtolower($row['Field'])."=".$table."\n";
            }
        }

        file_put_contents("common/txt/Counters_FDD.txt", $out);
        return $result;

    }//end loadCountersFromDB_FDD()


}//end class


/**
 * Class LTEQuery
 *
 * @category QueryAnalysis
 * @package  App\Http\Controllers\QueryAnalysis
 * @author   ericsson <genius@ericsson.com>
 * @license  MIT License
 * @link     https://laravel.com/docs/5.4/controllers
 */
class LTEQuery
{


    /**
     * 模板查询
     *
     * @param mixed  $localDB       本地数据库连接句柄
     * @param mixed  $pmDB          SYBASE数据库连接句柄
     * @param array  $counters      计数器集合
     * @param string $timeDim       时间维度
     * @param string $locationDim   地域维度
     * @param string $startTime     开始时间
     * @param string $endTime       结束时间
     * @param string $city          城市
     * @param array  $subNets       子网集合
     * @param string $resultText    查询结果表头
     * @param array  $aggTypes      聚合类型映射
     * @param string $format        模式 TDD|FDD
     * @param string $local_flag    本地查询FLAG
     * @param string $filename      导入文件名
     * @param int    $numOf_1000000 ENIQ个数
     * @param string $parent        用户名
     *
     * @return array|string
     */
    public function queryTemplate($localDB, $pmDB, $counters, $timeDim, $locationDim, $startTime,
        $endTime, $city, $subNets, &$resultText, $aggTypes, $format, $local_flag, $filename, $numOf_1000000
    ) {

        $result         = array();
        $templateName   = Input::get('template');
        $type           = Input::get('parent');
        $kpis           = $this->getKpis($localDB, $templateName, $type);
        $result['text'] = implode(',', $kpis['names']);
        $sql            = $this->createSQL($localDB, $pmDB, $kpis['kpiformula'], $counters, $timeDim, $locationDim, $startTime, $endTime, $city, $subNets, $resultText, $aggTypes, $local_flag, $kpis['kpiPrecision']);
        // print_r($sql);return;
        // Cache::store('file')->flush();
        // cache::flush();
        try {
            if ($local_flag) {
                $fp  = fopen($filename, "a");
                $dbc = new DataBaseConnection();
                $db  = $dbc->getDB('mongs', "CountersBackup_".$city);
                if ($db->errorInfo()[0] != '00000') {
                    throw new Exception('sql语句存在问题');
                }

                $res    = $db->query($sql);
                $rowArr = [];
                $i      = 0;
                $flag   = 0;
                while ($rows = $res->fetch(PDO::FETCH_ASSOC)) {
                    if ($i < 500) {
                        array_push($rowArr, $rows);
                        fputcsv($fp, $rows);
                    } else if ($i >= 500 && $i <= (intval(1000000 / $numOf_1000000))) {
                        fputcsv($fp, $rows);
                    } else {
                        $flag = 1;
                    }

                    $i++;
                }

                $result['state'] = 'normal';
                if ($flag == 1) {
                    $result['state'] = 'overflow';
                }

                $result['rows'] = $rowArr;
                unset($rowArr);
                unset($rows);
                fclose($fp);
                return $result;
            }//end if

            if ($format == "TDD"||$format=="NBIOT") {
                $fp = fopen($filename, "a");
                // print_r($sql);
                try {
                    $res = sybase_unbuffered_query($sql, $pmDB, false);
                } catch (Exception $e) {
                    throw new Exception('sql语句存在问题');
                }

                $rowArr = [];
                $i      = 0;
                $flag   = 0;
                $db = new DataBaseConnection();
                $dbc = $db->getDB('mongs', 'mongs');
                while ($rows = sybase_fetch_assoc($res)) {
                    $i++;
                    //cell
                    $locationDim = Input::get('locationDim');
                    $cn = '';
                    $cluster = '';
                    $cacheValue = '';
                    $cacheValueErbs = '';
                    $siteType = '';
                    $siteNameChinese = '';
                    if ($locationDim == 'cell') {
                        $cell = $rows['location'];

                        if (Cache::store('file')->has($cell)&&input::get('format')!='NBIOT') {
                            $cacheValue = Cache::store('file')->get($cell);
                        } else {
                            $cacheCn = '';
                            $cacheCluster = '';
                            $cacheSiteType = '';
                            $cacheSiteNameChinese = '';
                            $sql = "SELECT cellNameChinese,cluster,siteType,siteNameChinese FROM siteLte WHERE cellName='$cell'";
                            $rs = $dbc->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($rs)==0) {
                                $cacheCn = '无数据';
                                $cacheCluster = '无数据';
                                $cacheSiteType = '无数据';
                                $cacheSiteNameChinese = '无数据';
                            } else {
                                if (count($rs[0])==0) {
                                    $cacheCn = '无数据';
                                    $cacheCluster = '无数据';
                                    $cacheSiteType = '无数据';
                                    $cacheSiteNameChinese = '无数据';
                                } else {
                                    $cacheCn = $rs[0]['cellNameChinese'];
                                    $cacheCluster = $rs[0]['cluster'];
                                    $cacheSiteType = $rs[0]['siteType'];
                                    $cacheSiteNameChinese = $rs[0]['siteNameChinese'];
                                }
                            }
                            $cacheValue = $cacheCn . ',' . $cacheCluster . ',' . $cacheSiteType . ',' . $cacheSiteNameChinese;
                            Cache::store('file')->forever($cell, $cacheValue);
                        }

                        $cn = explode(',', $cacheValue)[0];
                        $cluster = explode(',', $cacheValue)[1];
                        $siteType = explode(',', $cacheValue)[2];
                        $siteNameChinese = explode(',', $cacheValue)[3];
                    } elseif ($locationDim == 'erbs') {
                        $erbs = $rows['location'];
                        if (Cache::store('file')->has($erbs)) {
                            $cacheValueErbs = Cache::store('file')->get($erbs);
                        } else {
                            //$cacheCn = '';
                            $cacheCluster = '';
                            $cacheSiteType = '';
                            $cacheSiteNameChinese = '';
                            $sql = "SELECT cluster,siteType,siteNameChinese FROM siteLte WHERE siteName='$erbs'";
                            $rs = $dbc->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($rs)==0) {
                                // $cacheCn = '无数据';
                                $cacheCluster = '无数据';
                                $cacheSiteType = '无数据';
                                $cacheSiteNameChinese = '无数据';
                            } else {
                                if (count($rs[0])==0) {
                                    // $cacheCn = '无数据';
                                    $cacheCluster = '无数据';
                                    $cacheSiteType = '无数据';
                                    $cacheSiteNameChinese = '无数据';
                                } else {
                                    // $cacheCn = $rs[0]['cellNameChinese'];
                                    $cacheCluster = $rs[0]['cluster'];
                                    $cacheSiteType = $rs[0]['siteType'];
                                    $cacheSiteNameChinese = $rs[0]['siteNameChinese'];
                                }
                            }
                            $cacheValueErbs = $cacheCluster . ',' . $cacheSiteType . ',' . $cacheSiteNameChinese;          
                            Cache::store('file')->forever($erbs, $cacheValueErbs);         
                        }
                        $cluster = explode(',', $cacheValueErbs)[0];
                        $siteType = explode(',', $cacheValueErbs)[1];
                        $siteNameChinese = explode(',', $cacheValueErbs)[2];
                    }
                    if ($i < 100) {
                        $r = [];
                        if ($locationDim == 'cell' && $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            array_splice($rows, 8, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'cell' && ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            array_splice($rows, 9, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'cell' && $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            array_splice($rows, 10, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else {
                            array_push($rowArr, $rows);
                        }
                        
                        // fputcsv($fp, $r);
                        // array_push($rowArr, $rows);
                        fputcsv($fp, $rows);
                    } else if ($i >= 100 && $i <= (intval(1000000 / $numOf_1000000))) {
                        if ($locationDim == 'cell'&& $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            array_splice($rows, 8, 0, $cn);
                        } else if ($locationDim == 'cell'&& ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            array_splice($rows, 9, 0, $cn);
                        } else if ($locationDim == 'cell'&& $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            array_splice($rows, 10, 0, $cn);
                        } else if ($locationDim == 'erbs'&& $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                        } else if ($locationDim == 'erbs'&& ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                        } else if ($locationDim == 'erbs'&& $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                        }
                        
                        fputcsv($fp, $rows);
                    } else {
                        $flag = 1;
                    }
                }

                $result['state'] = 'normal';
                if ($flag == 1) {
                    $result['state'] = 'overflow';
                }

                $result['rows'] = $rowArr;
                // print_r($result);
                unset($rowArr);
                unset($rows);
                fclose($fp);
            } else if ($format == "FDD") {
                $sql = str_replace("TDD", "FDD", $sql);
                $fp  = fopen($filename, "a");
                try {
                    $res = sybase_unbuffered_query($sql, $pmDB, false);
                } catch (Exception $e) {
                    throw new Exception('sql语句存在问题');
                }

                $rowArr = [];
                $i      = 0;
                $flag   = 0;
                $db = new DataBaseConnection();
                $dbc = $db->getDB('mongs', 'mongs');
                while ($rows = sybase_fetch_assoc($res)) {
                    //cell
                    $locationDim = Input::get('locationDim');
                    // $cn = '';
                    $cn = '';
                    $cluster = '';
                    $cacheValue = '';
                    $cacheValueErbs = '';
                    $siteType = '';
                    $siteNameChinese = '';
                    if ($locationDim == 'cell') {
                        $cell = $rows['location'];
                        if (Cache::store('file')->has($cell)) {
                            $cacheValue = Cache::store('file')->get($cell);
                        } else {
                            $cacheCn = '';
                            $cacheCluster = '';
                            $sql = "SELECT cellNameChinese,cluster FROM siteLte WHERE cellName='$cell'";
                            $rs = $dbc->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($rs)==0) {
                                $cacheCn = '无数据';
                                $cacheCluster = '无数据';
                            } else {
                                if (count($rs[0])==0) {
                                    $cacheCn = '无数据';
                                    $cacheCluster = '无数据';
                                } else {
                                    $cacheCn = $rs[0]['cellNameChinese'];
                                    $cacheCluster = $rs[0]['cluster'];
                                }
                            }
                            $cacheValue = $cacheCn . ',' . $cacheCluster;
                            Cache::store('file')->forever($cell, $cacheValue);
                        }
                        $cn = explode(',', $cacheValue)[0];
                        $cluster = explode(',', $cacheValue)[1];
                    } elseif ($locationDim == 'erbs') {
                        $erbs = $rows['location'];
                        if (Cache::store('file')->has($erbs)) {
                            $cacheValueErbs = Cache::store('file')->get($erbs);
                        } else {
                            //$cacheCn = '';
                            $cacheCluster = '';
                            $cacheSiteType = '';
                            $cacheSiteNameChinese = '';
                            $sql = "SELECT cluster,siteType,siteNameChinese FROM siteLte WHERE siteName='$erbs'";
                            $rs = $dbc->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($rs)==0) {
                                // $cacheCn = '无数据';
                                $cacheCluster = '无数据';
                                $cacheSiteType = '无数据';
                                $cacheSiteNameChinese = '无数据';
                            } else {
                                if (count($rs[0])==0) {
                                    // $cacheCn = '无数据';
                                    $cacheCluster = '无数据';
                                    $cacheSiteType = '无数据';
                                    $cacheSiteNameChinese = '无数据';
                                } else {
                                    // $cacheCn = $rs[0]['cellNameChinese'];
                                    $cacheCluster = $rs[0]['cluster'];
                                    $cacheSiteType = $rs[0]['siteType'];
                                    $cacheSiteNameChinese = $rs[0]['siteNameChinese'];
                                }
                            }
                            $cacheValueErbs = $cacheCluster . ',' . $cacheSiteType . ',' . $cacheSiteNameChinese;          
                            Cache::store('file')->forever($erbs, $cacheValueErbs);         
                        }
                        $cluster = explode(',', $cacheValueErbs)[0];
                        $siteType = explode(',', $cacheValueErbs)[1];
                        $siteNameChinese = explode(',', $cacheValueErbs)[2];
                    }
                    if ($i < 100) {
                        $r = [];
                        if ($locationDim == 'cell' && $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            array_splice($rows, 8, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'cell' && ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            array_splice($rows, 9, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'cell' && $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            array_splice($rows, 10, 0, $cn);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else if ($locationDim == 'erbs' && $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            foreach ($rows as $key => $value) {
                                $r[] = $value; 
                            }
                            array_push($rowArr, $r);
                        } else {
                            array_push($rowArr, $rows);
                        }
                        
                        // fputcsv($fp, $r);
                        // array_push($rowArr, $rows);
                        fputcsv($fp, $rows);
                    } else if ($i >= 100 && $i <= (intval(1000000 / $numOf_1000000))) {
                        if ($locationDim == 'cell'&& $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                            array_splice($rows, 8, 0, $cn);
                        } else if ($locationDim == 'cell'&& ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                            array_splice($rows, 9, 0, $cn);
                        } else if ($locationDim == 'cell'&& $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                            array_splice($rows, 10, 0, $cn);
                        } else if ($locationDim == 'erbs'&& $timeDim == 'day') {
                            array_splice($rows, 3, 0, $cluster);
                            array_splice($rows, 5, 0, $siteType);
                            array_splice($rows, 6, 0, $siteNameChinese);
                        } else if ($locationDim == 'erbs'&& ($timeDim == 'hour' || $timeDim == 'hourgroup')) {
                            array_splice($rows, 4, 0, $cluster);
                            array_splice($rows, 6, 0, $siteType);
                            array_splice($rows, 7, 0, $siteNameChinese);
                        } else if ($locationDim == 'erbs'&& $timeDim == 'quarter') {
                            array_splice($rows, 5, 0, $cluster);
                            array_splice($rows, 7, 0, $siteType);
                            array_splice($rows, 8, 0, $siteNameChinese);
                        }
                        
                        
                        fputcsv($fp, $rows);
                    } else {
                        $flag = 1;
                    }

                    $i++;
                }

                $result['state'] = 'normal';
                if ($flag == 1) {
                    $result['state'] = 'overflow';
                }

                $result['rows'] = $rowArr;
                unset($rowArr);
                unset($rows);
                fclose($fp);
            }//end if
            return $result;
        } catch (Exception $e) {
            return 'Caught exception: '.$e->getMessage();
        }//end try

    }//end queryTemplate()


    /**
     * 获得指标信息集合
     *
     * @param mixed  $localDB      MYSQL数据库连接句柄
     * @param string $templateName 模板名
     *
     * @return array
     */
    public function getKpis($localDB, $templateName,$type)
    {    

        $queryKpiset  = "select kpiName,kpiformula,kpiPrecision from kpiTemplateCommon where templateName='$templateName' and type='$type'";
        $res          = $localDB->query($queryKpiset);
        $kpis         = $res->fetchAll(PDO::FETCH_ASSOC);
        foreach ($kpis as $key => $value) {
            $result['names'][] = $value['kpiName'];
            $result['kpiformula'][]   = $value['kpiformula'];
            $result['kpiPrecision'][]=$value['kpiPrecision'];   
            # code...
        }
        return $result;

    }//end getKpis()


    /**
     * 创建SQL语句
     *
     * @param mixed  $localDB     MYSQL数据库连接句柄
     * @param mixed  $pmDB        SYBASE数据库连接句柄
     * @param array  $kpiName     指标集合
     * @param array  $counters    计数器集合
     * @param string $timeDim     时间维度
     * @param string $locationDim 地域维度
     * @param string $startTime   起始时间
     * @param string $endTime     结束时间
     * @param string $city        城市
     * @param array  $subNetwork  子网集合
     * @param string $resultText  查询结果表头
     * @param array  $aggTypes    聚合类型
     * @param string $local_flag  本地查询FLAG
     *
     * @return mixed|string
     */
    protected function createSQL($localDB, $pmDB, $kpiName, $counters, $timeDim, $locationDim,
        $startTime, $endTime, $city, $subNetwork, &$resultText, $aggTypes, $local_flag,$kpiPrecision
    ) {
        $kpiset        = "(".implode(',', $kpiName).")";
        $subNetwork1   = Input::get('subNet');      
        $location    = str_replace("[", "", $subNetwork1);
        $location    = str_replace("]", "", $location);
        $subNetwork2    = str_replace('"', "", $location);
        $location      = "('".str_replace(",", "','", $subNetwork2)."')";
        $kpis          = "";
        // $kpiNameStr    = $kpiName.',';
        // $queryFormula  = "select kpiName,kpiFormula,kpiPrecision,instr('$kpiNameStr',CONCAT(id,',')) as sort from kpiformula where id in ".$kpiset." order by sort";
        $index         = 0;
        $selectSQL     = "select";
        $counterMap    = array();
        $nosum_map     = array();
        $pattern_nosum = "/(max|min|avg)\((.*)\)/";
        $matches       = array();
        // foreach ($localDB->query($queryFormula) as $row) {
        //     $kpi = $row['kpiFormula'];
        //     $this->parserKPI($kpi, $counters, $counterMap, $nosum_map);
        //     if (preg_match($pattern_nosum, $kpi, $matches)) {
        //         $kpi = $nosum_map[$kpi];
        //     }
        //     $formula = "cast(".$kpi." as decimal(18,".$row['kpiPrecision']."))";
        //     $kpis    = $kpis.$formula." as kpi".$index.",";
        //     $index++;
        // }
        foreach ($kpiName as $key => $value) {
           $kpi = $value;
           $this->parserKPI($kpi, $counters, $counterMap, $nosum_map);
            if (preg_match($pattern_nosum, $kpi, $matches)) {
                $kpi = $nosum_map[$kpi];
            }
            $formula = "cast(".$kpi." as decimal(18,".$kpiPrecision[$key]."))";
            $kpis    = $kpis.$formula." as kpi".$index.",";
            $index++;
        }
        
        $kpis    = substr($kpis, 0, (strlen($kpis) - 1));
 
        $citySQL = $this->getCitySQL();
        if ($local_flag) {
            if ($locationDim == 'city') {
                $citySQL = 'city';
            } else {
                $citySQL = 'subNetwork';
            }
        }

        $time_id = "date_id";

        if ($local_flag) {
            if ($locationDim == 'city') {
                $whereSQL = " where $time_id>='$startTime' and $time_id<='$endTime'";
            } else {
                $whereSQL = " where $time_id>='$startTime' and $time_id<='$endTime' and $citySQL in $location";
            }
        } else {
            $whereSQL = " where $time_id>='$startTime' and $time_id<='$endTime' and $citySQL in $location";
        }

        $aggGroupSQL  = "";
        $aggSelectSQL = "";
        $aggOrderSQL  = "";

        if ($timeDim == "daygroup") {
            $selectSQL    = $selectSQL." 'ALLDAY' as day,";
            $aggGroupSQL  = "group by DAY,";
            $aggSelectSQL = "select AGG_TABLE0.day";
            $resultText   = $resultText."day,";
        } else if ($timeDim == "day") {
            if ($local_flag) {
                $selectSQL    = $selectSQL." date_id as day,";
                $aggSelectSQL = "select AGG_TABLE0.day";
                $resultText   = $resultText."day,";
            } else {
                $selectSQL    = $selectSQL." convert(char(10),date_id) as day,";
                $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.cellNum";
                $resultText   = $resultText."day,cellNum,";
            }

            $aggGroupSQL = "group by date_id,";
            $aggOrderSQL = "order by AGG_TABLE0.day";
        } else if ($timeDim == "hour") {
            if ($local_flag) {
                $selectSQL    = $selectSQL." date_id as day,hour_id as hour,";
                $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.hour";
                $resultText   = $resultText."day,hour,";
            } else {
                $selectSQL    = $selectSQL." convert(char(10),date_id) as day,hour_id as hour,";
                $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.hour,AGG_TABLE0.cellNum";
                $resultText   = $resultText."day,hour,cellNum,";
            }

            $aggGroupSQL = "group by date_id,hour_id,";
            $aggOrderSQL = "order by AGG_TABLE0.day,AGG_TABLE0.hour";
        } else if ($timeDim == "quarter") {
            $selectSQL    = $selectSQL." convert(char,date_id) as day,hour_id as hour,min_id as minute,";
            $aggGroupSQL  = "group by date_id,hour_id,min_id,";
            $aggOrderSQL  = "order by AGG_TABLE0.day,AGG_TABLE0.hour,AGG_TABLE0.minute";
            $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.hour,AGG_TABLE0.minute,AGG_TABLE0.cellNum";
            $resultText   = $resultText."day,hour,minute,cellNum,";
        } else if ($timeDim == "hourgroup") {
            $hourcollection = Input::get('hour');
            if ($hourcollection == 'null') {
                $hourcollection = 'AllHour';
            } else {
                $hourcollection = ltrim($hourcollection, '["');
                $hourcollection = rtrim($hourcollection, '"]');
                $hourcollection = implode(',', explode('","', $hourcollection));
            }

            if ($local_flag) {
                $selectSQL    = $selectSQL." date_id as day,\"$hourcollection\" as hour,";
                $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.hour";
                $resultText   = $resultText."day,hour,";
            } else {
                $selectSQL    = $selectSQL." convert(char(10),date_id) as day,\"$hourcollection\" as hour,";
                $aggSelectSQL = "select AGG_TABLE0.day,AGG_TABLE0.hour,AGG_TABLE0.cellNum";
                $resultText   = $resultText."day,hour,cellNum,";
            }

            $aggGroupSQL = "group by date_id,hour,";
            $aggOrderSQL = "order by AGG_TABLE0.day,AGG_TABLE0.hour";
        }//end if

        if ($locationDim == "city") {
            $selectSQL    = $selectSQL." '$city' as location,";
            $aggGroupSQL  = $aggGroupSQL."location";
            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.location,";
            $resultText   = $resultText."location,";
        } else if ($locationDim == "subNetworkGroup") {
            $selectSQL    = $selectSQL."\"$subNetwork\" as location,";
            $aggGroupSQL  = $aggGroupSQL."location";
            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.location,";
            $resultText   = $resultText."location,";
        } else if ($locationDim == "subNetwork") {
            $selectSQL    = $selectSQL."$citySQL as location,";
            $aggGroupSQL  = $aggGroupSQL."location";
            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.location,";
            $resultText   = $resultText."location,";
        } else if ($locationDim == "erbs") {
            $selectSQL    = $selectSQL."$citySQL as subNet,erbs as location,";
            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.subNet,AGG_TABLE0.location,";
            if ($local_flag) {
                $aggGroupSQL = $aggGroupSQL."subNetwork,location";
            } else {
                $aggGroupSQL = $aggGroupSQL."SN,location";
            }
            $resultText = $resultText."subNet,cluster,location,siteType,sitenamechinese,";
            // $resultText = $resultText."subNet,location,";
        } else if ($locationDim == "erbsGroup") {
            $inputErbs = Input::get('erbs');
            $erbs      = isset($inputErbs) ? $inputErbs : "";
            if ($erbs == "") {
                $selectSQL    = $selectSQL." 'ALLErbs' as location,";
            } else {
                $selectSQL    = $selectSQL."'". $erbs ."'as location,";
            }  
            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.location,";
            if ($local_flag) {
                $aggGroupSQL = substr($aggGroupSQL, 0, strlen($aggGroupSQL)-1);
            } else {
                $aggGroupSQL = substr($aggGroupSQL, 0, strlen($aggGroupSQL)-1);
            }

            $resultText = $resultText."location,";
        } else if ($locationDim == "cell") {

            if ($local_flag) {
                $selectSQL = $selectSQL."$citySQL as subNet,erbs as site,cell as location,";
            } else {
                if (input::get('format')=='NBIOT') {
                $selectSQL = $selectSQL."$citySQL as subNet,substring(substring(SN,charindex (',', substring(SN, 32, 25)) + 32),11,25) as site, substring(NbIotCell,6) as location,";
                } else {
                $selectSQL = $selectSQL."$citySQL as subNet,substring(substring(SN,charindex (',', substring(SN, 32, 25)) + 32),11,25) as site,EutranCellTDD as location,";
                   }
            }

            $aggSelectSQL = $aggSelectSQL.",AGG_TABLE0.subNet,AGG_TABLE0.site,AGG_TABLE0.location,";
            if ($local_flag) {
                

                    $aggGroupSQL = $aggGroupSQL."subNetwork,location";
               
            } else {
                    $aggGroupSQL = $aggGroupSQL."SN,location";
                
            }

            if ($timeDim == 'daygroup') {
                $resultText = $resultText."subNet,site,location,";
            } else {
                $resultText = $resultText."subNet,cluster,site,siteType,sitenamechinese,location,cellNameChinese,";
            }
            // $resultText = $resultText."subNet,site,location,";
        } else if ($locationDim == "cellGroup") {
            $aggSelectSQL = $aggSelectSQL.",";
            $aggGroupSQL  = substr($aggGroupSQL, 0, (strlen($aggGroupSQL) - 1));
        }//end if
        $inputErbs = Input::get('erbs');
        $erbs      = isset($inputErbs) ? $inputErbs : "";
        if ($erbs != "" && ($locationDim == "erbs" || $locationDim == "erbsGroup")) {
            $erbs     = "('".str_replace(",", "','", $erbs)."')";
            $whereSQL = $whereSQL." and erbs in ".$erbs;
        }

        $inputCell = Input::get('cell');
        $cell      = isset($inputCell) ? $inputCell : "";
        if ($cell != "" && ($locationDim == "cell" || $locationDim == "cellGroup")) {
            $cell = "('".str_replace(",", "','", $cell)."')";
            if ($local_flag||input::get('format')=='NBIOT') {
                $whereSQL = $whereSQL." and cell in ".$cell;
            } else {
                $whereSQL = $whereSQL." and EutranCellTDD in ".$cell;
            }
        }

        $inputHour = Input::get('hour');
        $inputHour = ltrim($inputHour, '["');
        $inputHour = rtrim($inputHour, '"]');
        $inputHour = implode(",", explode('","', $inputHour));
        $hour      = isset($inputHour) ? $inputHour : "";
        if ($hour != 'null' && ($timeDim == "hour" || $timeDim == "quarter" || $timeDim == "hourgroup")) {
            $hour     = "(".$hour.")";
            $whereSQL = $whereSQL." and hour_id in ".$hour;
        }

        $inputMinute = Input::get('minute');
        $inputMinute = ltrim($inputMinute, '["');
        $inputMinute = rtrim($inputMinute, '"]');
        $inputMinute = implode(",", explode('","', $inputMinute));
        $min         = isset($inputMinute) ? $inputMinute : "";
        if ($min != 'null' && $timeDim == "quarter") {
            $min      = "(".$min.")";
            $whereSQL = $whereSQL." and min_id in ".$min;
        }

        $templateNameCheck = Input::get('template');
       
        $tables            = array_keys(array_count_values($counterMap));
        if (count($tables) == 1) {
            $currTable = $tables[0];

            if (trim(substr($currTable, 0, (strlen($currTable) - 4))) == "DC_E_ERBS_EUTRANCELLRELATION" && $templateNameCheck != '切换成功率(不含邻区对)') {
                    $aggSelectSQL = $aggSelectSQL."AGG_TABLE0.relation,AGG_TABLE0.eufdd,";
                    $selectSQL    = $selectSQL."EUtranCellRelation as relation,EUtranCell".input::get('format')." as eufdd,";
                    // $aggGroupSQL  = $aggGroupSQL.",eufdd,relation";
                    $aggGroupSQL  = $aggGroupSQL.",eufdd,relation";
                    $resultText   = $resultText."EUtranCellRelation,EUtranCell".input::get('format').",";
                
            } else if (trim(substr($currTable, 0, (strlen($currTable) - 4))) == "DC_E_ERBS_GERANCELLRELATION" && $templateNameCheck != '2G邻区切换统计-不含GeranCellRelation') {
                    $aggSelectSQL = $aggSelectSQL."AGG_TABLE0.relation,AGG_TABLE0.eufdd,";
                    $selectSQL    = $selectSQL."GeranCellRelation as relation,EUtranCell".input::get('format')." as eufdd,";
                    $aggGroupSQL  = $aggGroupSQL.",eufdd,relation";
                    $resultText   = $resultText."GeranCellRelation,EUtranCell".input::get('format').",";
                
            }

            //添加邻区
            
        }

        $format = Input::get('format');

        $aggSelectSQL = $aggSelectSQL.$kpis;
        $tempTableSQL = "";
        $index        = 0;
        foreach ($tables as $table) {
            $countersForQuery = array_keys($counterMap, $table);
            $tableSQL         = $this->createTempTable($locationDim, $selectSQL, $whereSQL, $table, $countersForQuery, $aggGroupSQL, $aggTypes, $local_flag, $nosum_map, $counterMap, $format);
            $tableSQL         = $tableSQL."as AGG_TABLE$index ";
            if ($index == 0) {
                if ($index != (sizeof($tables) - 1)) {
                    $tableSQL = $tableSQL." left join";
                }
            } else {
                if ($timeDim == "daygroup") {
                    $tableSQL = $tableSQL."on AGG_TABLE0.DAY = AGG_TABLE$index.DAY";
                } else if ($timeDim == "day" || $timeDim == 'daygroup') {
                    $tableSQL = $tableSQL."on AGG_TABLE0.day = AGG_TABLE$index.day";
                } else if ($timeDim == "hour") {
                    $tableSQL = $tableSQL."on AGG_TABLE0.day = AGG_TABLE$index.day and AGG_TABLE0.hour = AGG_TABLE$index.hour";
                } else if ($timeDim == "hourgroup") {
                    $tableSQL = $tableSQL."on AGG_TABLE0.day = AGG_TABLE$index.day and AGG_TABLE0.hour = AGG_TABLE$index.hour";
                } else if ($timeDim == "quarter") {
                    $tableSQL = $tableSQL."on AGG_TABLE0.day = AGG_TABLE$index.day and AGG_TABLE0.hour = AGG_TABLE$index.hour and AGG_TABLE0.minute = AGG_TABLE$index.minute";
                }

                if ($locationDim == "cellGroup" || $timeDim == "daygroup"  || $locationDim == "erbsGroup") {
                    $tableSQL = $tableSQL;
                } else {
                    $tableSQL = $tableSQL." and AGG_TABLE0.location = AGG_TABLE$index.location";
                }

                if ($index != (sizeof($tables) - 1)) {
                    $tableSQL = $tableSQL." left join ";
                }
            }//end if
            $tempTableSQL = $tempTableSQL.$tableSQL;
            $index++;
        }//end foreach

        $sql = $aggSelectSQL." from ".$tempTableSQL." $aggOrderSQL";
        $sql = str_replace("\n", "", $sql);
        return $sql;

    }//end createSQL()


    /**
     * 解析KPI
     *
     * @param string $kpi        KPI公式
     * @param array  $counters   计数器集合
     * @param array  $counterMap 计数器表名映射
     * @param array  $nosum_map  非SUM指标集合
     *
     * @return string
     */
    protected function parserKPI($kpi, $counters, &$counterMap, &$nosum_map)
    {
        //$kpi是指标公式
        $pattern       = "/[\(\)\+\*-\/]/";
        $columns       = preg_split($pattern, $kpi);
        $pattern_nosum = "/(max|min|avg)\((.*)\)/";
        $matches       = array();
        foreach ($columns as $column) {
            $column      = trim($column);
            $counterName = $column;
            if (stripos($counterName, "pm") === false) {
                continue;
            }

            if (stripos($counterName, "_") !== false) {
                $elements    = explode("_", $counterName);
                $counterName = $elements[0];
            }
            if (array_key_exists(strtolower($counterName), $counters)) {
                $table = $counters[strtolower($counterName)];
            } else {
                return strtolower($counterName);
            }

            $timeDim = Input::get('timeDim');
            if (input::get('format')=='NBIOT') {
                 $table   = trim($table)."_RAW";
            } else {
            $table   = ($timeDim == "day") ? trim($table)."_day" : trim($table)."_raw";
            }
            if (preg_match($pattern_nosum, $kpi, $matches)) {
            
                $counterMap[$kpi] = $table;

                $data = str_replace($matches[0], "agg".count($nosum_map), $kpi);
                $nosum_map[$kpi]  = $data;
                // $nosum_map[$kpi]  = "agg".count($nosum_map);
                // print_r($counterMap);
                break;
            }


            if (!array_key_exists($column, $counterMap)) {
                $counterMap[$column] = $table;
            }
        }//end foreach

    }//end parserKPI()


    /**
     * 子网提取SQL
     *
     * @return string
     */
    protected function getCitySQL()
    {
        return "substring(SN,charindex('=',substring(SN,32,25))+32,charindex(',',substring(SN,32,25))-charindex('=',substring(SN,32,25))-1)";

    }//end getCitySQL()


    /**
     * 创建查询子表
     *
     * @param string $locationDim 地域维度
     * @param string $selectSQL   SELECT字串
     * @param string $whereSQL    WHERE字串
     * @param string $tableName   表名
     * @param array  $counters    计数器集
     * @param string $groupSQL    GROUP字串
     * @param array  $aggTypes    聚合类型
     * @param string $local_flag  本地查询FLAG
     * @param array  $nosum_map   非SUM指标集合
     * @param array  $counterMap  计数器表名映射
     * @param string $format      模式 TDD|FDD
     *
     * @return string
     */
    function createTempTable($locationDim, $selectSQL, $whereSQL, $tableName, $counters,
        $groupSQL, $aggTypes, $local_flag, $nosum_map, $counterMap, $format
    ) {
        $tables = array_keys(array_count_values($counterMap));
        $flag   = 'true';
        $flag1  = 'true';
        foreach ($tables as $table) {
            if (trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_CPP_GIGABITETHERNET' || trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_CPP_PLUGINUNIT_V') {
                $flag = 'false';
            }

            if (trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_ERBS_SECTORCARRIER' || trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_CPP_PROCESSORLOAD_V' || trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_ERBS_ENODEBFUNCTION' || trim(substr($table, 0, (strlen($table) - 4))) == 'DC_E_ERBS_BBPROCESSINGRESOURCE') {
                $flag1 = 'false';
            }
        }

        if (!$local_flag) {
            if ($format == 'TDD') {
                if ($flag == 'false') {
                    $selectSQL .= "COUNT(DISTINCT(ERBS)) AS cellNum,";
                } else {
                    if ($flag1 == 'false') {
                        $selectSQL .= "COUNT(DISTINCT(ERBS)) AS cellNum,";
                    } else {
                        $selectSQL .= "COUNT(DISTINCT(EutranCellTDD)) AS cellNum,";
                    }
                }
            } else if ($format == 'FDD'||$format=='NBIOT') {
                $selectSQL .= "COUNT(DISTINCT(ERBS)) AS cellNum,";
            }
        }


        $pattern_nosum = "/(max|min|avg)\((.*)\)/";
        $i=0;
        foreach ($counters as $counter) {
            $counter     = trim($counter);
            $counterName = $counter;

            if (preg_match($pattern_nosum, $counter, $match)) {
                // $counterName = $nosum_map[$counter];
                $minmaxavg = $match[1];
                $pmCounter = $match[2];
                if (stripos($pmCounter, "_") !== false) {
                    $elements = explode("_", $pmCounter);
                    $name     = $elements[0];
                    $index    = $elements[1];
                    $counter  = $this->convertInternalCounter_minmaxavg($minmaxavg, $name, $index);
                    // str_replace(search, replace, subject)
                    $selectSQL = $selectSQL.$counter." as agg".$i++.",";
                    // print_r($selectSQL);
                } else {
                    $counterName = $nosum_map[$counter];
                    $selectSQL = $selectSQL.$counter." as '$counterName',";
                }
                
            } else if (stripos($counter, "_") !== false) {
                $elements = explode("_", $counter);
                $name     = $elements[0];
                $index    = $elements[1];
                $counter  = $this->convertInternalCounter($name, $index);
                $selectSQL = $selectSQL.$counter." as '$counterName',";
            } else {
                $aggType = $this->getAggType($aggTypes, $counter);
                $counter = "$aggType($counter)";
                $selectSQL = $selectSQL.$counter." as '$counterName',";
            }

            // $selectSQL = $selectSQL.$counter." as '$counterName',";
        }

        $selectSQL = substr($selectSQL, 0, (strlen($selectSQL) - 1));
        if (!$local_flag) {
            return "($selectSQL from dc.$tableName $whereSQL $groupSQL)";
        } else {
            $tableName = substr("$tableName", 0, strripos("$tableName", "_"));
            if ($locationDim == 'city') {
                $tableName = trim($tableName)."_HOUR_CITY";
            } else if ($locationDim == 'subNetwork' || $locationDim == 'subNetworkGroup') {
                $tableName = trim($tableName)."_HOUR_SUBNET";
            } else if ($locationDim == 'erbs') {
                $tableName = trim($tableName)."_HOUR_ERBS";
            } else {
                $tableName = trim($tableName)."_HOUR";
            }

            return "($selectSQL from $tableName $whereSQL $groupSQL)";
        }

    }//end createTempTable()


    /**
     * 转换内部计数器
     *
     * @param string $counterName 计数器名
     * @param string $index       向量值
     *
     * @return mixed
     */
    protected function convertInternalCounter($counterName, $index)
    {
        $SQL = "sum(case DCVECTOR_INDEX when $index then $counterName else 0 end)";
        return str_replace("\n", "", $SQL);

    }//end convertInternalCounter()

    /**
     * 转换内部计数器
     *
     * @param string $counterName 计数器名
     * @param string $index       向量值
     *
     * @return mixed
     */
    protected function convertInternalCounter_minmaxavg($minmaxavg, $counterName, $index)
    {
        $SQL = $minmaxavg."(case DCVECTOR_INDEX when $index then $counterName else 0 end)";
        return str_replace("\n", "", $SQL);

    }//end convertInternalCounter()


    /**
     * 获得聚合类型
     *
     * @param array  $aggTypes    聚合类型集合
     * @param string $counterName 计数器名
     *
     * @return string
     */
    protected function getAggType($aggTypes, $counterName)
    {
        if (!array_key_exists($counterName, $aggTypes)) {
            return "sum";
        }

        return trim($aggTypes[$counterName]);

    }//end getAggType()


    /**
     * 是否本地查询
     *
     * @param string $checkStyle 本地查询FLAG
     *
     * @return bool
     */
    public function isLocalQuery($checkStyle)
    {
        if ($checkStyle == 'online') {
            return false;
        } else if ($checkStyle == 'local') {
            return true;
        }

    }//end is_local_query()


    /**
     * 获得本地查询计数器集
     *
     * @return array
     */
    public function getLocalCounters()
    {
        $tables = file("common/txt/LocalTables.txt");
        $result = array();
        $dbn    = new DataBaseConnection();
        $conn   = $dbn->getConnDB('mongs');
        if (!$conn) {
            die('Could not connect: '.mysql_error());
        }

        $db = "CountersBackup_changzhou";
        mysql_select_db($db, $conn);
        foreach ($tables as $table) {
            $table = trim($table);
            $sql   = "desc $table";
            $res   = mysql_query($sql);
            while ($item = mysql_fetch_assoc($res)) {
                if (strpos($item['Field'], 'pm') !== false) {
                    $result[] = $item['Field'];
                }
            }
        }

        return $result;

    }//end getLocalCounters()


    /**
     * 获得子网集合
     *
     * @param mixed  $db   数据库连接句柄
     * @param string $city 城市名
     *
     * @return mixed
     */
    public function getSubNets($db, $city)
    {
        $SQL     = "select subNetwork from databaseconn where connName = '$city'";
        $res     = $db->query($SQL);
        $row     = $res->fetch();
        $subNets = $row['subNetwork'];
        return $subNets;

    }//end getSubNets()


    /**
     * 获得FDD子网
     *
     * @param mixed  $db   数据库连接句柄
     * @param string $city 城市
     *
     * @return string
     */
    public function getSubNetsFDD($db, $city)
    {
        $sql         = "select cityChinese from databaseconn where connName = '$city' group by cityChinese";
        $row         = $db->query($sql)->fetch();
        $cityChinese = $row['cityChinese'];
        $sql         = "select subNetworkFdd from databaseconn where cityChinese='$cityChinese'";
        $res         = $db->query($sql, PDO::FETCH_ASSOC);
        $rows        = $res->fetchAll();
        $subNets     = '';
        foreach ($rows as $row) {
            $subNets .= $row['subNetworkFdd'].',';
        }

        $subNets = substr($subNets, 0, (strlen($subNets) - 1));
        return $subNets;

    }//end getSubNetsFDD()


    /**
     * 获得子网集合
     *
     * @param string $location 地域维度
     * @param string $city     城市
     *
     * @return string
     */
    public function getSubNetsFromLoc($location, $city)
    {
        $subNets = "";
        foreach ($location as $loc) {
            if ($loc['connName'] == $city || $city == substr($loc['connName'], 0, (strlen($loc['connName']) - 1))) {
                $subNets .= $loc['citys'].',';
            }
        }

        $subNets = substr($subNets, 0, (strlen($subNets) - 1));
        return $subNets;

    }//end getSubNetsFromLoc()


    /**
     * 解析location
     *
     * @param string $city       城市信息
     * @param string $subNetwork 子网信息
     *
     * @return array
     */
    public function parseLocation($city, $subNetwork)
    {
        $citysChinese = json_decode($city);
        $cityPY       = new DataBaseConnection();
        $citys        = $cityPY->getConnCity($citysChinese);

        $subNetworks = json_decode($subNetwork, true);
        $result      = array();
        $item        = array();
        foreach ($citys as $city) {
            $item['connName'] = $city;
            $databaseConns    = DB::table('databaseconn')->where('connName', '=', $city)->get();
            $subStr           = $databaseConns[0]->subNetwork;
            $subArr           = explode(',', $subStr);
            $newSubNetworks   = '';
            foreach ($subArr as $sub) {
                foreach ($subNetworks as $newSubNetwork) {
                    if ($sub == $newSubNetwork) {
                        $newSubNetworks = $newSubNetworks.$sub.',';
                    }
                }
            }

            $newSubNetworks = substr($newSubNetworks, 0, (strlen($newSubNetworks) - 1));
            $item['citys']  = $newSubNetworks;
            array_push($result, $item);
        }

        return $result;

    }//end parseLocation()


}//end class

<?php

// use session to store credentials and auth hash
session_start();

require 'analytics.class.php';
include 'Includes/FusionCharts.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'], $_POST['password'])){

    // set username & password
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['password'] = $_POST['password'];

    header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']));    
    exit;
}

if (isset($_SESSION['username'], $_SESSION['password'])){
    
    // construct the class
    $oAnalytics = new analytics($_SESSION['username'], $_SESSION['password']);
    
    // get an array with profiles (profileId => profileName)
    $aProfiles = $oAnalytics->getProfileList();  
      
    $aProfileKeys = array_keys($aProfiles);
    // set the profile tot the first account
    $oAnalytics->setProfileById($aProfileKeys[1]);
    $startDate = '01';
    $startMonth = '10';
    $startYear = '2010';
    $endDate = '31';
    $endMonth = '01';
    $endYear = '2011';
}

if (isset($_POST['profileId'])){
    // change profileId
    $oAnalytics->setProfileById($_POST['profileId']);     
}
if (isset($_POST['startdate'])){
    $startDate = $_POST['startdate'];
}
if (isset($_POST['startmonth'])){
    $startMonth = $_POST['startmonth'];
}
if (isset($_POST['startyear'])){
    $startYear = $_POST['startyear'];
}
if (isset($_POST['enddate'])){
    $endDate = $_POST['enddate'];
}
if (isset($_POST['endmonth'])){
    $endMonth = $_POST['endmonth'];
}
if (isset($_POST['endyear'])){
    $endYear = $_POST['endyear'];     
}
$startPeriod = $startYear.'-'.$startMonth.'-'.$startDate;
$endPeriod = $endYear.'-'.$endMonth.'-'.$endDate;
$oAnalytics->setDateRange($startPeriod, $endPeriod);

/**
* Function called for displaying FusionCharts
* 
* @param array $aData
*/
function graph($aData, $graphMetric){
      
    $iMax = max($aData);
    if ($iMax == 0){
        echo 'No data';
        return;
    }
    //create XML <charts> element
    $fusionXML = "<chart caption='Google Analytics Chart' xAxisName='Time Period' yAxisName='Count' formatNumberScale='0'>";
    foreach ($aData as $arSubData => $sValue)
      $fusionXML .= "<set label='" . $arSubData . "' value='" . $sValue . "' />";
    //Close <chart> element
    $fusionXML .= "</chart>";
    //Create the chart - Column 3D Chart with data contained in fusionXML
    echo renderChart("FusionCharts/Column3D.swf", "", $fusionXML, $graphMetric, 600, 300, false, true);
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
        <title>Google Analytics with Fusion Charts</title>
        <script language="javascript" src="FusionCharts/FusionCharts.js"></script>
        <style type="text/css">
            body{font: 11px Arial, Helvetica, sans-serif}
            form{overflow:hidden}
            table{border:0;border-collapse:collapse;width:600px;}
            td{padding:5px;border-right:1px solid #ccc;}
            .bar{height:10px;background:#f00;}
        </style>
    </head>
    <body>
        <form method="post" action="analytics_data.php">
            <label for="profileId">Profile</label>
            <select id="profileId" name="profileId">
            <?php
            foreach ($aProfiles as $sKey => $sValue){
                echo '<option value="' . $sKey . '">' . $sValue . '</option>';
            }
            ?>
            </select>
            <label for="period">Period</label>
            <select name="startdate" id="startdate">
            <option SELECTED value="01">1</option>
            <option value="02">2</option>
            <option value="03">3</option>
            <option value="04">4</option>
            <option value="05">5</option>
            <option value="06">6</option>
            <option value="07">7</option>
            <option value="08">8</option>
            <option value="09">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
            <option value="13">13</option>
            <option value="14">14</option>
            <option value="15">15</option>
            <option value="16">16</option>
            <option value="17">17</option>
            <option value="18">18</option>
            <option value="19">19</option>
            <option value="20">20</option>
            <option value="21">21</option>
            <option value="22">22</option>
            <option value="23">23</option>
            <option value="24">24</option>
            <option value="25">25</option>
            <option value="26">26</option>
            <option value="27">27</option>
            <option value="28">28</option>
            <option value="29">29</option>
            <option value="30">30</option>
            <option value="31">31</option>
            </select>
            <select name="startmonth" id="startmonth">
            <option value="01">Jan</option>
            <option value="02">Feb</option>
            <option value="03">Mar</option>
            <option value="04">Apr</option>
            <option value="05">May</option>
            <option value="06">Jun</option>
            <option value="07">Jul</option>
            <option value="08">Aug</option>
            <option value="09">Sep</option>
            <option SELECTED value="10">Oct</option>
            <option value="11">Nov</option>
            <option value="12">Dec</option>
            </select>
            <select name="startyear" id="startyear">
            <option value="2001">2001</option>
            <option value="2002">2002</option>
            <option value="2003">2003</option>
            <option value="2004">2004</option>
            <option value="2005">2005</option>
            <option value="2006">2006</option>
            <option value="2007">2007</option>
            <option value="2008">2008</option>
            <option value="2009">2009</option>
            <option SELECTED value="2010">2010</option>
            <option value="2011">2011</option>
            </select>
            <?php echo ' - to - '; ?>
            <select name="enddate" id="enddate">
            <option value="01">1</option>
            <option value="02">2</option>
            <option value="03">3</option>
            <option value="04">4</option>
            <option value="05">5</option>
            <option value="06">6</option>
            <option value="07">7</option>
            <option value="08">8</option>
            <option value="09">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
            <option value="13">13</option>
            <option value="14">14</option>
            <option value="15">15</option>
            <option value="16">16</option>
            <option value="17">17</option>
            <option value="18">18</option>
            <option value="19">19</option>
            <option value="20">20</option>
            <option value="21">21</option>
            <option value="22">22</option>
            <option value="23">23</option>
            <option value="24">24</option>
            <option value="25">25</option>
            <option value="26">26</option>
            <option value="27">27</option>
            <option value="28">28</option>
            <option value="29">29</option>
            <option value="30">30</option>
            <option SELECTED value="31">31</option>
            </select>
            <select name="endmonth" id="endmonth">
            <option SELECTED value="01">Jan</option>
            <option value="02">Feb</option>
            <option value="03">Mar</option>
            <option value="04">Apr</option>
            <option value="05">May</option>
            <option value="06">Jun</option>
            <option value="07">Jul</option>
            <option value="08">Aug</option>
            <option value="09">Sep</option>
            <option value="10">Oct</option>
            <option value="11">Nov</option>
            <option value="12">Dec</option>
            </select>
            <select name="endyear" id="endyear">
            <option value="2001">2001</option>
            <option value="2002">2002</option>
            <option value="2003">2003</option>
            <option value="2004">2004</option>
            <option value="2005">2005</option>
            <option value="2006">2006</option>
            <option value="2007">2007</option>
            <option value="2008">2008</option>
            <option value="2009">2009</option>
            <option value="2010">2010</option>
            <option SELECTED value="2011">2011</option>
            </select>
            <input type="submit" id="submit" value="Submit">
            <a href="./">Log out</a>
        </form>
        
        <h2>Visitors:</h2>
        <?php graph($oAnalytics->getVisitors(), 'Visitors'); ?>
        <h2>Pageviews:</h2>
        <?php graph($oAnalytics->getPageviews(), 'Pageviews'); ?>
        
        <h2>Bounces:</h2>
        <?php graph($oAnalytics->getBounces(), 'Bounces'); ?>
        
        <h2>Visits per Hour:</h2>
        <?php graph($oAnalytics->getVisitsPerHour(), 'Visits per hour'); ?>
        
        <h2>Browsers:</h2>
        <?php graph($oAnalytics->getBrowsers(), 'Browser'); ?>
        
        <h2>Referrers:</h2>
        <?php graph($oAnalytics->getReferrers(), 'Referrers'); ?>
        <p>Linux Mint 10 x86_64; Linux 2.6.35; Lighttpd 1.4.28; PHP 5.3.5; PostgreSQL 9.0.2</p>
    </body>
</html>

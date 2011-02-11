<?php

/**
 * Google Analytics PHP API
 * 
 * This class can be used to retrieve data from the Google Analytics API with PHP
 * It fetches data as array for use in applications or scripts
 *  
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 *  Credits: Alex C.
 * 
 */

  class analytics{
    
    private $_sUser;
    private $_sPass;
    private $_sAuth;
    private $_sProfileId;
    
    private $_sStartDate;
    private $_sEndDate;
    
    private $_bUseCache;
    private $_iCacheAge;
    
    /**
    * public constructor
    * 
    * @param string $sUser
    * @param string $sPass
    * @return analytics
    */
    public function __construct($sUser, $sPass){
        $this->_sUser = $sUser;
        $this->_sPass = $sPass;
        
        $this->_bUseCache = false;
        
        $this->auth();
    }
    
    
    /**
    * Google Authentification, returns session when set
    */
    private function auth(){
        
        if (isset($_SESSION['auth'])){
            $this->_sAuth = $_SESSION['auth'];
            return;
        }
        
        $aPost = array ( 'accountType'   => 'GOOGLE', 
                         'Email'         => $this->_sUser,
                         'Passwd'        => $this->_sPass,
                         'service'       => 'analytics',
                         'source'        => '');
                         
        $sResponse = $this->getUrl('https://www.google.com/accounts/ClientLogin', $aPost);
    
        $_SESSION['auth'] = '';
        if (strpos($sResponse, "\n") !== false){
            $aResponse = explode("\n", $sResponse);
            foreach ($aResponse as $sResponse){
                if (substr($sResponse, 0, 4) == 'Auth'){
                    $_SESSION['auth'] = trim(substr($sResponse, 5));
                }
            }
        }
        if ($_SESSION['auth'] == ''){
            unset($_SESSION['auth']);
            throw new Exception('Retrieving Auth hash failed!');
        }
        $this->_sAuth = $_SESSION['auth']; 
    }
    
    /**
    * Use caching (bool)
    * Whether or not to store GA data in a session for a given period
    * 
    * @param bool $bCaching (true/false)
    * @param int $iCacheAge seconds (default: 10 minutes)
    */
    public function useCache($bCaching = true, $iCacheAge = 600){
        $this->_bUseCache = $bCaching;
        $this->_iCacheAge = $iCacheAge;
        if ($bCaching && !isset($_SESSION['cache'])){
            $_SESSION['cache'] = array();     
        }
    }
    
    
    /**
    * Get GA XML with auth key
    * 
    * @param string $sUrl
    * @return string XML
    */
    private function getXml($sUrl){
        
        return $this->getUrl($sUrl, array(), array('Authorization: GoogleLogin auth=' . $this->_sAuth));
    }
    
    
    /**
    * Sets GA Profile ID  (Example: ga:12345)
    */        
    public function setProfileById($sProfileId){
        
            $this->_sProfileId = $sProfileId; 
    }
    
    /**
    * Sets Profile ID by a given accountname
    * 
    */
    public function setProfileByName($sAccountName){
        
        if (isset($_SESSION['profile'])){
            $this->_sProfileId = $_SESSION['profile'];
            return;
        }
        
        $this->_sProfileId = '';
        $sXml = $this->getXml('https://www.google.com/analytics/feeds/accounts/default');
        $aAccounts = $this->parseAccountList($sXml);
            
        foreach($aAccounts as $aAccount){
            if (isset($aAccount['accountName']) && $aAccount['accountName'] == $sAccountName){
                if (isset($aAccount['tableId'])){
                    $this->_sProfileId =  $aAccount['tableId'];
                }
            }    
        }
        if ($this->_sProfileId == ''){
            throw new Exception('No profile ID found!');
        }
        
        $_SESSION['profile'] = $this->_sProfileId;
    }
    
    /**
    * Returns an array with profileID => accountName
    * 
    */
    public function getProfileList(){
        
        $sXml = $this->getXml('https://www.google.com/analytics/feeds/accounts/default');
        $aAccounts = $this->parseAccountList($sXml);
        $aReturn = array();
        foreach($aAccounts as $aAccount){ 
            $aReturn[$aAccount['tableId']] =  $aAccount['title'];
        }       
        return $aReturn;
    }
    
    /**
    * get resulsts from cache if set and not older then cacheAge
    * 
    * @param string $sKey
    * @return mixed cached data
    */
    private function getCache($sKey){
        
        if ($this->_bUseCache === false){
            return false;
        }
        
        if (!isset($_SESSION['cache'][$this->_sProfileId])){
            $_SESSION['cache'][$this->_sProfileId] = array();
        }  
        if (isset($_SESSION['cache'][$this->_sProfileId][$sKey])){
            if (time() - $_SESSION['cache'][$this->_sProfileId][$sKey]['time'] < $this->_iCacheAge){
                return $_SESSION['cache'][$this->_sProfileId][$sKey]['data'];
            } 
        }
        return false;
    }
    
    /**
    * Cache data in session
    * 
    * @param string $sKey
    * @param mixed $mData Te cachen data
    */
    private function setCache($sKey, $mData){
        
        if ($this->_bUseCache === false){
            return false;
        }
        
        if (!isset($_SESSION['cache'][$this->_sProfileId])){
            $_SESSION['cache'][$this->_sProfileId] = array();
        }  
        $_SESSION['cache'][$this->_sProfileId][$sKey] = array(  'time'  => time(),
                                                                'data'  => $mData);
    }
    
    /**
    * Parses GA XML to an array (dimension => metric)
    * 
     * @param array  $aProperties  (GA properties: metrics & dimensions)
    * 
    * @return array result
    */
    public function getData($aProperties = array()){
        $aParams = array();
        foreach($aProperties as $sKey => $sProperty){
            $aParams[] = $sKey . '=' . $sProperty;
        }
        
        $sUrl = 'https://www.google.com/analytics/feeds/data?ids=' . $this->_sProfileId . 
                                                        '&start-date=' . $this->_sStartDate . 
                                                        '&end-date=' . $this->_sEndDate . '&' . 
                                                        implode('&', $aParams);
        $aCache = $this->getCache($sUrl);
        if ($aCache !== false){
            return $aCache;
        }
        
        $sXml = $this->getXml($sUrl);
        
        $aResult = array();
        
        $oDoc = new DOMDocument();
        $oDoc->loadXML($sXml);
        $oEntries = $oDoc->getElementsByTagName('entry');
        foreach($oEntries as $oEntry){
            $oTitle = $oEntry->getElementsByTagName('title');
            $sTitle = $oTitle->item(0)->nodeValue;
            
            $oMetric = $oEntry->getElementsByTagName('metric'); 
            
            // Fix the array key when multiple dimensions are given
            if (strpos($sTitle, ' | ') !== false && strpos($aProperties['dimensions'], ',') !== false){
                
                $aDimensions = explode(',', $aProperties['dimensions']);
                $aDimensions[] = '|';
                $aDimensions[] = '=';
                $sTitle = preg_replace('/\s\s+/', ' ', trim(str_replace($aDimensions, '', $sTitle)));  
                
            }
            $sTitle = str_replace($aProperties['dimensions'] . '=', '', $sTitle);
            
            $aResult[$sTitle] = $oMetric->item(0)->getAttribute('value');
        }
        // cache the results (if caching is true)
        $this->setCache($sUrl, $aResult);
        
        return $aResult;
    }
    
    /**
    * Parse XML from account list
    * 
    * @param string $sXml
    */
    private function parseAccountList($sXml){
        
        $oDoc = new DOMDocument();
        $oDoc->loadXML($sXml);
        $oEntries = $oDoc->getElementsByTagName('entry');
        $i = 0;
        $aProfiles = array();
        foreach($oEntries as $oEntry){
            
            $aProfiles[$i] = array();

            $oTitle = $oEntry->getElementsByTagName('title');
            $aProfiles[$i]["title"] = $oTitle->item(0)->nodeValue;

            $oEntryId = $oEntry->getElementsByTagName('id');
            $aProfiles[$i]["entryid"] = $oEntryId->item(0)->nodeValue;

            $oProperties = $oEntry->getElementsByTagName('property');
            foreach($oProperties as $oProperty){
                if (strcmp($oProperty->getAttribute('name'), 'ga:accountId') == 0){
                    $aProfiles[$i]["accountId"] = $oProperty->getAttribute('value');
                }    
                if (strcmp($oProperty->getAttribute('name'), 'ga:accountName') == 0){
                    $aProfiles[$i]["accountName"] = $oProperty->getAttribute('value');
                }
                if (strcmp($oProperty->getAttribute('name'), 'ga:profileId') == 0){
                    $aProfiles[$i]["profileId"] = $oProperty->getAttribute('value');
                }
                if (strcmp($oProperty->getAttribute('name'), 'ga:webPropertyId') == 0){
                    $aProfiles[$i]["webPropertyId"] = $oProperty->getAttribute('value');
                }
            }

            $oTableId = $oEntry->getElementsByTagName('tableId');
            $aProfiles[$i]["tableId"] = $oTableId->item(0)->nodeValue;

            $i++;
        }
        return $aProfiles;
    }
    
    /**
    * Get data from given URL
    * Uses Curl if installed, falls back to file_get_contents if not
    * 
    * @param string $sUrl
    * @param array $aPost
    * @param array $aHeader
    * @return string Response
    */
    private function getUrl($sUrl, $aPost = array(), $aHeader = array()){
        
        
        if (count($aPost) > 0){
            // build POST query
            $sMethod = 'POST'; 
            $sPost = http_build_query($aPost);    
            $aHeader[] = 'Content-type: application/x-www-form-urlencoded';
            $aHeader[] = 'Content-Length: ' . strlen($sPost);
            $sContent = $aPost;
        } else {
            $sMethod = 'GET';
            $sContent = null;
        }
        
        if (function_exists('curl_init')){

            // If Curl is installed, use it!
            $rRequest = curl_init();
            curl_setopt($rRequest, CURLOPT_URL, $sUrl);
            curl_setopt($rRequest, CURLOPT_RETURNTRANSFER, 1);
            
            if ($sMethod == 'POST'){
                curl_setopt($rRequest, CURLOPT_POST, 1); 
                curl_setopt($rRequest, CURLOPT_POSTFIELDS, $aPost); 
            } else {
                curl_setopt($rRequest, CURLOPT_HTTPHEADER, $aHeader);
            }
            
            $sOutput = curl_exec($rRequest);
            if ($sOutput === false){
                throw new Exception('Curl error (' . curl_error($rRequest) . ')');    
            }
            
            $aInfo = curl_getinfo($rRequest);
            
            if ($aInfo['http_code'] != 200){
                // not a valid response from GA
                if ($aInfo['http_code'] == 400){
                    throw new Exception('Bad request (' . $aInfo['http_code'] . ') url: ' . $sUrl);     
                }
                if ($aInfo['http_code'] == 403){
                    throw new Exception('Access denied (' . $aInfo['http_code'] . ') url: ' . $sUrl);     
                }
                throw new Exception('Not a valid response (' . $aInfo['http_code'] . ') url: ' . $sUrl);
            }
            
            curl_close($rRequest);
                        
        } else {
            // Curl is not installed, use file_get_contents
            
            // create headers and post
            $aContext = array('http' => array ( 'method' => $sMethod,
                                                'header'=> implode("\r\n", $aHeader) . "\r\n",
                                                'content' => $sContent));
            $rContext = stream_context_create($aContext);

            $sOutput = @file_get_contents($sUrl, 0, $rContext);
            if (strpos($http_response_header[0], '200') === false){
                // not a valid response from GA   
                throw new Exception('Not a valid response (' . $http_response_header[0] . ') url: ' . $sUrl);       
            }
        }
        return $sOutput;
    }   
    
    /**
    * Sets the date range for GA data
    * 
    * @param string $sStartDate (YYYY-MM-DD)
    * @param string $sEndDate   (YYYY-MM-DD)
    */
    public function setDateRange($sStartDate, $sEndDate){
        
        $this->_sStartDate = $sStartDate; 
        $this->_sEndDate   = $sEndDate;
        
    }
    
    /**
    * Sets de data range to a given month
    * 
    * @param int $iMonth
    * @param int $iYear
    */
    public function setMonth($iMonth, $iYear){  
        
        $this->_sStartDate = date('Y-m-d', strtotime($iYear . '-' . $iMonth . '-01')); 
        $this->_sEndDate   = date('Y-m-d', strtotime($iYear . '-' . $iMonth . '-' . date('t', strtotime($iYear . '-' . $iMonth . '-01'))));
    }
    
    /**
    * Get visitors for given period
    * 
    */
    public function getVisitors(){
                                
        return $this->getData(array( 'dimensions' => 'ga:day,ga:month,ga:year',
                                     'metrics'    => 'ga:visits',
                                     'sort'       => 'ga:month,ga:year'));
    }
    
    /**
    * Get pageviews for given period
    * 
    */    
    public function getPageviews(){
                                
        return $this->getData(array( 'dimensions' => 'ga:day,ga:month,ga:year',
                                     'metrics'    => 'ga:pageviews',
                                     'sort'       => 'ga:month,ga:year'));
    }
    
    /**
    * Get visitors per hour for given period
    * 
    */    
    public function getVisitsPerHour(){
        
        return $this->getData(array( 'dimensions' => 'ga:hour',
                                     'metrics'    => 'ga:visits',
                                     'sort'       => 'ga:hour'));
    }
    
    /**
    * Get Browsers for given period
    * 
    */    
    public function getBrowsers(){
        
        $aData = $this->getData(array(  'dimensions' => 'ga:browser,ga:browserVersion,ga:operatingSystem',
                                        'metrics'    => 'ga:visits',
                                        'sort'       => 'ga:visits'));             
        arsort($aData);
        return $aData;                                                                                                                                                                           
    }
    /**
    * Get referrers for given period
    * 
    */    
    public function getReferrers(){
        
        $aData = $this->getData(array(   'dimensions' => 'ga:source',
                                         'metrics'    => 'ga:visits',
                                         'sort'       => 'ga:source'));
    
        // sort descending by number of visits 
        arsort($aData);
        return $aData; 
    }
    /**
    * Get bounces for given period
    * 
    */    
    public function getBounces(){
        
        $aData = $this->getData(array(   'dimensions' => 'ga:pageTitle',
                                         'metrics'    => 'ga:bounces',
                                         'sort'       => 'ga:pageTitle'));
    
        // sort descending by number of visits 
        arsort($aData);
        return $aData; 
    }
}
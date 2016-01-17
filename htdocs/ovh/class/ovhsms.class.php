<?php
/* Copyright (C) 2007-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Jean-François FERRY <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * https://www.ovh.com/fr/soapi-to-apiv6-migration/
 */

/**
 *      \file       ovh/class/ovhsms.class.php
 *      \ingroup    ovh
 *      \brief      This file allow to send sms with an OVH account
 */
require_once(NUSOAP_PATH.'/nusoap.php');

require __DIR__ . '/../includes/autoload.php';
use \Ovh\Api;


/**
 *		Use an OVH account to send SMS with Dolibarr
 */
class OvhSms  extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='ovhsms';			//!< Id that identify managed object

	var $id;
	var $account;
	var $fk_soc;
	var $expe;
	var $dest;
	var $message;
	var $validity;
	var $class;
	var $deferred;
	var $priority;
	
	var $soap;         // Old API
	var $conn;         // New API
    var $endpoint;

	/**
     *	Constructor
     *
     * 	@param	DoliDB	$db		Database handler
     */
	function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		// Réglages par défaut
		$this->validity = 24*60;  // 24 hours. the maximum time -in minute(s)- before the message is dropped, defaut is 48 hours
		$this->class = '2';       // the sms class: flash(0),phone display(1),SIM(2),toolkit(3)
		$this->deferred = '60';   // the time -in minute(s)- to wait before sending the message, default is 0
		$this->priority = '3';    // the priority of the message (0 to 3), default is 3
		// Set the WebService URL
		dol_syslog(get_class($this)."::OvhSms URL=".$conf->global->OVHSMS_SOAPURL);
		if (empty($conf->global->OVH_NEWAPI))
		{
		    if (! empty($conf->global->OVHSMS_SOAPURL))
    		{
        		require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
        		$params=getSoapParams();
        		ini_set('default_socket_timeout', $params['response_timeout']);
    
    		    //if ($params['proxy_use']) print $langs->trans("TryToUseProxy").': '.$params['proxy_host'].':'.$params['proxy_port'].($params['proxy_login']?(' - '.$params['proxy_login'].':'.$params['proxy_password']):'').'<br>';
    			//print 'URL: '.$WS_DOL_URL.'<br>';
    			//print $langs->trans("ConnectionTimeout").': '.$params['connection_timeout'].'<br>';
    			//print $langs->trans("ResponseTimeout").': '.$params['response_timeout'].'<br>';
    
    			$err=error_reporting();
    			error_reporting(E_ALL);     // Enable all errors
    
    			try {
    				$this->soap = new SoapClient($conf->global->OVHSMS_SOAPURL,$params);
    
    				$language = "en";
    				$multisession = false;
    
    				$this->session = $this->soap->login($conf->global->OVHSMS_NICK, $conf->global->OVHSMS_PASS,$language,$multisession);
    				//if ($this->session) print '<div class="ok">'.$langs->trans("OvhSmsLoginSuccessFull").'</div><br>';
    				//else print '<div class="error">Error login did not return a session id</div><br>';
    				$this->soapDebug();
    
    				// We save known SMS account
    				$this->account = empty($conf->global->OVHSMS_ACCOUNT)?'ErrorNotDefined':$conf->global->OVHSMS_ACCOUNT;
    
    				return 1;
    
    			}
    			catch(SoapFault $se) {
    				error_reporting($err);     // Restore default errors
    				dol_syslog(get_class($this).'::SoapFault: '.$se, LOG_ERR);
    				//var_dump('eeeeeeee');exit;
    				return 0;
    			}
    			catch (Exception $ex) {
    				error_reporting($err);     // Restore default errors
    				dol_syslog(get_class($this).'::SoapFault: '.$ex, LOG_ERR);
    				//var_dump('eeeeeeee');exit;
    				return 0;
    			}
    			catch (Error $e) {
    				error_reporting($err);     // Restore default errors
    				dol_syslog(get_class($this).'::SoapFault: '.$e, LOG_ERR);
    				//var_dump('eeeeeeee');exit;
    				return 0;
    			}
    			error_reporting($err);     // Restore default errors
    
    			return 1;
    		}
    		else return 0;
		}
		else
		{
		    $endpoint = empty($conf->global->OVH_ENDPOINT)?'ovh-eu':$conf->global->OVH_ENDPOINT;
		    $this->endpoint = $endpoint;
		    
        	require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
        	$params=getSoapParams();
        	ini_set('default_socket_timeout', $params['response_timeout']);
        	 
		    try
		    {
		        // Get servers list
		        $this->conn = new Api($conf->global->OVHAPPKEY, $conf->global->OVHAPPSECRET, $endpoint, $conf->global->OVHCONSUMERKEY);

    			// We save known SMS account
    			$this->account = empty($conf->global->OVHSMS_ACCOUNT)?'ErrorNotDefined':$conf->global->OVHSMS_ACCOUNT;
		    }
		    catch(Exception $e)
		    {
		        $this->error=$e->getMessage();
		        setEventMessages($this->error, null, 'errors');
		        return 0;
		    }
		    
		    return 1;
	    }
	}

	/**
	 * Logout
	 *
	 * @return	void
	 */
	function logout()
	{
	    global $conf;
	    
		if (empty($conf->global->OVH_NEWAPI)) $this->soap->logout($this->session);
		return 1;
	}


	/**
	 * Send SMS
	 *
	 * @return	int     <=0 if error, >0 if OK
	 */
	function SmsSend()
	{
	    global $conf;
	    
		try
		{
		    if (empty($conf->global->OVH_NEWAPI))
		    {
    		    // print "$this->session, $this->account, $this->expe, $this->dest, $this->message, $this->validity, $this->class, $this->deferred, $this->priority";
    			$resultsend = $this->soap->telephonySmsSend($this->session, $this->account, $this->expe, $this->dest, $this->message, $this->validity, $this->class, $this->deferred, $this->priority, 2, 'Dolibarr');
    			$this->soapDebug();
    			return $resultsend;
		    }
		    else
		    {
		        $priority=$this->priority;    // high
		        if ($priority == '0') $priority='high';
		        if ($priority == '1') $priority='medium';
		        if ($priority == '2') $priority='low';
		        if ($priority == '3') $priority='veryLow';
		        
		        $smsclass = $this->class;
		        if ($smsclass == 0) $smsclass='flash';
		        if ($smsclass == 1) $smsclass='phoneDisplay';
		        if ($smsclass == 2) $smsclass='sim';
		        if ($smsclass == 3) $smsclass='toolkit';
		        
		        $content = (object) array(
		            "differedPeriod" => $this->deferred,  // time in minutes
		            "charset"=> "UTF-8",
		            "class"=> $smsclass,           // "phoneDisplay",
		            "coding"=> "7bit",
		            "message"=> $this->message,
		            "noStopClause"=> false,
		            "priority"=> $priority,
		            "receivers"=> [ $this->dest ],   // [ "+3360000000" ]
		            "sender"=> $this->expe,
		            "senderForResponse"=> false,
		            "validityPeriod"=> $this->validity    // 28800
                );
		        //var_dump($content);exit;
		        try
		        {
		            //var_dump($content);
    		        $resultPostJob = $this->conn->post('/sms/'. $this->account . '/jobs/', $content);
    		        /*$resultPostJob = Array
    		        (
    		            [totalCreditsRemoved] => 1
    		            [invalidReceivers] => Array
    		            (
    		                )
    		        
    		            [ids] => Array
    		            (
    		                [0] => 26929925
    		                )
    		        
    		            [validReceivers] => Array
    		            (
    		                [0] => +3366204XXXX
    		                )
    		        
    		            )*/
    		        //var_dump($resultPostJob);
    		        if ($resultPostJob['totalCreditsRemoved'] > 0) return 1;
    		        else return -1;
		        }
		        catch(Exception $e)
		        {
		            $this->error=$e->getMessage();
		            return -1;		            
		        }
		    }
		}
		catch(SoapFault $fault)
		{
			$errmsg="Error ".$fault->faultstring;
			dol_syslog(get_class($this)."::SmsSend ".$errmsg, LOG_ERR);
			$this->error.=($this->error?', '.$errmsg:$errmsg);
		}
		return -1;
	}

	/**
	 * Show HTML select box to select account
	 *
	 * @return	void
	 */
	function printListAccount()
	{
		$resultaccount = $this->getSmsListAccount();
		print '<select name="ovh_account" id="ovh_account">';
		foreach ($resultaccount as $accountlisted) {
			print '<option value="'.$accountlisted.'">'.$accountlisted.'</option>';
		}
		print '</select>';
	}

	/**
	 * Return list of SMSAccounts
	 *
	 * @return	array
	 */
	function getSmsListAccount()
	{
	    global $conf;
	    
		try {
		    if (empty($conf->global->OVH_NEWAPI))
		    {
    		    $returnList = $this->soap->telephonySmsAccountList($this->session);
    			$this->soapDebug();
    			return $returnList;
		    }
		    else
		    {
		        //var_dump($this->conn);
		        $resultinfo = $this->conn->get('/sms');
		        $resultinfo = dol_json_decode(dol_json_encode($resultinfo), true);
		        return $resultinfo;
		    }
		}
		catch(SoapFault $fault) {
			$errmsg="Error ".$fault->faultstring;
			dol_syslog(get_class($this)."::SmsHistory ".$errmsg, LOG_ERR);
			$this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1;
		}
	}

	/**
	 * Return Credit
	 *
	 * @return	array
	 */
	function CreditLeft()
	{
	    global $conf;
	    
		try {
		    if (empty($conf->global->OVH_NEWAPI))
		    {
    		    $returnList = $this->soap->telephonySmsCreditLeft($this->session, $this->account);
    			$this->soapDebug();
    			return $returnList;
		    }
		    else
		    {
		        //var_dump($this->conn);
		        $resultinfo = $this->conn->get('/sms/'.$this->account);
		        $resultinfo = dol_json_decode(dol_json_encode($resultinfo), false);
		        return $resultinfo->creditsLeft;		        
		    }
		}
		catch(SoapFault $fault) {
			$errmsg="Error ".$fault->faultstring;
			dol_syslog(get_class($this)."::SmsHistory ".$errmsg, LOG_ERR);
			$this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1;
		}
	}

	/**
	 * Return History
	 *
	 * @return	array
	 */
	function SmsHistory()
	{
	    global $conf;
	    
		try {
		    if (empty($conf->global->OVH_NEWAPI))
		    {
    		    $returnList = $this->soap->telephonySmsHistory($this->session, $this->account, "");
    			$this->soapDebug();
    			return $returnList;
		    }
		    else
		    {
		        $resultinfo = $this->conn->get('/sms/'.$this->account.'/outgoing');
		        $resultinfo = dol_json_decode(dol_json_encode($resultinfo), true);
		        return $resultinfo;		        
		    }
		}
		catch(SoapFault $fault) {
			$errmsg="Error ".$fault->faultstring;
			dol_syslog(get_class($this)."::SmsHistory ".$errmsg, LOG_ERR);
			$this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1;
		}
		return -1;
	}

	/**
	 * Return list of possible SMS senders
	 *
	 * @return array|int	                    <0 if KO, array with list of available senders if OK
	 */
	function SmsSenderList()
	{
	    global $conf;
	    
		try {
		    if (empty($conf->global->OVH_NEWAPI))
		    {
    		    $telephonySmsSenderList = $this->soap->telephonySmsSenderList($this->session, $this->account);
    			$this->soapDebug();
    			return $telephonySmsSenderList;
		    }
		    else
		    {
		        $resultinfo = $this->conn->get('/sms/'.$this->account.'/senders');
		        //var_dump($resultinfo);
		        $i=0;
		        $senderlist=array();
		        foreach($resultinfo as $key => $val)
		        {
                    $senderlist[$i] = new stdClass();
                    $senderlist[$i]->number=$val;
                    $i++;
		        }
		        return $senderlist;		        
		    }
		}
		catch(SoapFault $fault) {
			$errmsg="Error ".$fault->faultstring;
			dol_syslog(get_class($this)."::SmsSenderList ".$errmsg, LOG_ERR);
			$this->error.=($this->error?', '.$errmsg:$errmsg);
			return -1;
		}
		return -1;
	}


	/**
	 * Call soapDebug method to output traces
	 *
	 * @return	void
	 */
	function soapDebug()
	{
		if (method_exists($this->soap,'__getLastRequestHeaders')) dol_syslog(get_class($this).'::OvhSms REQUEST HEADER: ' . $this->soap->__getLastRequestHeaders(), LOG_DEBUG, 0, '_ovhsms');
		if (method_exists($this->soap,'__getLastRequest')) dol_syslog(get_class($this).'::OvhSms REQUEST: ' . $this->soap->__getLastRequest(), LOG_DEBUG, 0, '_ovhsms');

		if (method_exists($this->soap,'__getLastResponseHeaders')) dol_syslog(get_class($this).'::OvhSms RESPONSE HEADER: ' . $this->soap->__getLastResponseHeaders(), LOG_DEBUG, 0, '_ovhsms');
		if (method_exists($this->soap,'__getLastResponse')) dol_syslog(get_class($this).'::OvhSms RESPONSE: ' . $this->soap->__getLastResponse(), LOG_DEBUG, 0, '_ovhsms');
	}
}

<?php
require_once(dirname(__FILE__) . "./config.php");


class GoogleAlert
{

	// Currently not supported links
	// protected $LOGIN_URL = "https://accounts.google.com/ServiceLogin?continue=https%3A%2F%2Faccounts.google.com%2FManageAccount&rip=1&nojavascript=1";
	// protected $LOGIN_PASSWORD_URL = "https://accounts.google.com/signin/challenge/sl/password";
	// protected $ALERTS_MODIFY_URL = 'https://www.google.com/alerts/modify?x=';

	// supported links
	protected $ALERTS_URL = "https://www.google.com/alerts";
	protected $ALERTS_FEED = "https://www.google.de/alerts/feeds/";
	protected $ALERTS_CREATE_URL = "https://www.google.com/alerts/create?x=";
	protected $ALERTS_DELETE_URL = "https://www.google.com/alerts/delete?x=";
	protected $ch;

	function __construct()
	{
		$this->ch = curl_init();
	}

	function decodeCookies()
	{
		return base64_decode(GOOGLE_ALERTS_AUTHSTRING);
	}

	function getParams($query, $createid, $lang = 'en', $frequency = 'happens', $type = 'all', $quantity = 'best', $dest = 'feed')
	{
		$et = '2';
		$e = '';
		$t = '7'; //all
		$f = '[],1'; //when happens
		$l = '3'; //only best

		if ($quantity != 'best') $l = 2;
		// if ($dest=='email') {
		//   $e=$this->user;
		//   $et='1';
		// }

		switch ($type) {
			case 'news':
				$t = '1';
				break;
			case 'blogs':
				$t = '4';
				break;
			case 'videos':
				$t = '9';
				break;
			case 'forums':
				$t = '8';
				break;
			case 'books':
				$t = '22';
				break;
		}

		switch ($frequency) {
			case 'day':
				$f = '[null,null,18],2';
				break;
			case 'week':
				$f = '[null,null,18,2],3';
				break;
		}

		return 'params=[null,[null,null,null,[null,"' . $query . '","com",[null,"' . $lang . '","DE"],null,null,null,0,1],null,' . $l . ',[[null,' . $et . ',"' . $e . '",' . $f . ',"de-DE",null,null,null,null,null,"0",null,null,"' . $createid . '"]],null,[' . $t . '],null]]';
	}


	function create($query, $lang = 'en', $frequency = 'happens', $type = 'all', $quantity = 'best', $dest = 'feed')
	{

		$htmlBody = $this->getHTMLBody();
		$state = $this->getWindowState($htmlBody);

		$createid = $this->getCreateID($state);
		$x = $this->getX($state);

		$params = $this->getParams($query, $createid, $lang, $frequency = 'happens', $type = 'all', $quantity = 'best', $dest = 'feed');

		$cookieAr = $this->decodeCookies();
		curl_setopt($this->ch, CURLOPT_URL, $this->ALERTS_CREATE_URL . $x);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
			"Cookie: SID=" . json_decode($cookieAr)[0]->value . ";
			HSID=" . json_decode($cookieAr)[1]->value . ";
			SSID=" .  json_decode($cookieAr)[2]->value . ";"
		));
		curl_setopt($this->ch, CURLOPT_VERBOSE, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);

		$res = curl_exec($this->ch);
		$feed = $this->getFeed($res);
		$googleid = $this->getDeleteId($res);

		return array(
			"rss" => $feed,
			"googleid" => $googleid
		);
	}

	function delete($googleid)
	{
		$htmlBody = $this->getHTMLBody();
		$state = $this->getWindowState($htmlBody);
		$x = $this->getX($state);

		$cookieAr = $this->decodeCookies();
		curl_setopt($this->ch, CURLOPT_URL, $this->ALERTS_DELETE_URL . $x);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
			"Cookie: SID=" . json_decode($cookieAr)[0]->value . ";
			HSID=" . json_decode($cookieAr)[1]->value . ";
			SSID=" .  json_decode($cookieAr)[2]->value . ";"
		));
		curl_setopt($this->ch, CURLOPT_VERBOSE, true);
		$params = 'params=[null,"' . $googleid . '"]';
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);

		$res = curl_exec($this->ch);
	}

	function getHTMLBody()
	{
		$cookieAr = $this->decodeCookies();

		curl_setopt($this->ch, CURLOPT_URL, $this->ALERTS_URL); //"https://www.google.com/alerts");
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
			"Cookie: SID=" . json_decode($cookieAr)[0]->value . ";
			HSID=" . json_decode($cookieAr)[1]->value . ";
			SSID=" .  json_decode($cookieAr)[2]->value . ";"
		));
		curl_setopt($this->ch, CURLOPT_VERBOSE, true);

		return curl_exec($this->ch);
	}


	function getX($state)
	{
		return json_decode($state)[sizeof(json_decode($state)) - 4];
	}

	function getCreateID($state)
	{
		return json_decode($state)[1][5][0][13];
	}

	function getFeed($responseBody)
	{
		$userID = $this->getUserID($responseBody);
		$alertID = $this->getAlertID($responseBody);
		if (isset($userID) && isset($alertID)) return ($this->ALERTS_FEED . $userID . "/" . $alertID);
		else return null;
	}

	function getUserID($responseBody)
	{
		preg_match('/\/alerts\/feeds\/(\d+)\//', $responseBody, $matches);
		if (isset($matches[1])) return $matches[1];
		else return null;
	}

	function getAlertID($responseBody)
	{
		preg_match('/\/alerts\/feeds\/' . $this->getUserID($responseBody) . '\/(\d+)/', $responseBody, $matches);
		if (isset($matches[1])) return $matches[1];
		else return null;

		// if (isset(json_decode($responseBody)[4][0][3][6][0][11])) return json_decode($responseBody)[4][0][3][6][0][11];
		// else return null;
	}

	function getWindowState($body)
	{

		preg_match_all('#<script(.*?)</script>#is', $body, $scripts);
		foreach ($scripts as $key => $script) {
			foreach ($script as $key => $match) {
				if (strpos($match, 'window.STATE', 5)) {
					$rest = substr($match, 31);
					$res = substr($rest, 18);
					$state = substr($res, 0,  strlen($res) - 6);
				}
			}
		}

		return $state;
	}

	function getDeleteId($responseBody)
	{
		// echo json_decode($responseBody)[4][0][1];

		preg_match('/data-id=\\\\"([^"]*)\\\\"/', $responseBody, $matches);
		if (isset($matches[1])) return $matches[1];
		else return null;

		// if (isset(json_decode($responseBody)[4][0][1])) return json_decode($responseBody)[4][0][1];
		// else return null;
	}
}

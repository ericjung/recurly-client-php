<?php

/**
 * @category   Recurly
 * @package    Recurly_Client_PHP
 * @copyright  Copyright (c) 2009 {@link http://recurly.com Recurly, Inc.}
 */
class RecurlyAccount
{
	var $account_code;
	var $username;
	var $email;
	var $first_name;
	var $last_name;
	var $company_name;
	
	function RecurlyAccount($account_code = null, $username = null, $email = null, $first_name = null, $last_name = null, $company_name = null)
	{
		$this->account_code = $account_code;
		$this->username = $username;
		$this->email = $email;
		$this->first_name = $first_name;
		$this->last_name = $last_name;
		$this->company_name = $company_name;
	}
	
	public static function getAccount($accountCode)
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode);
		$result = RecurlyClient::__sendRequest($uri, 'GET');
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'account', 'RecurlyAccount');
		} else if ($result->code == '404') {
			return null;
		} else {
			throw new RecurlyException("Could not get account info for {$accountCode}: {$result->response} -- ({$result->code})");
		}
	}
	
	public function create()
	{
		$uri = RecurlyClient::PATH_ACCOUNTS;
		$data = $this->getXml();
		$result = RecurlyClient::__sendRequest($uri, 'POST', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'account', 'RecurlyAccount');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not create an account for {$this->account_code}: {$result->response} -- ({$result->code}) ");
		}
	}
	
	public function update()
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account_code);
		$data = $this->getXml();
		$result = RecurlyClient::__sendRequest($uri, 'PUT', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'account', 'RecurlyAccount');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not update the account for {$this->account_code}: {$result->response} ({$result->code})");
		}
	}
	
	public static function closeAccount($accountCode)
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($accountCode);
		$result = RecurlyClient::__sendRequest($uri, 'DELETE');
		if (preg_match("/^2..$/", $result->code)) {
			return true;
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not close the account for {$accountCode}: {$result->response} ({$result->code})");
		}
	}
	
	public function creditAccount($amount, $description = '')
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account_code) . RecurlyClient::PATH_ACCOUNT_CREDITS;
		$credit = new RecurlyAccountCredit($amount, $description);
		$data = $credit->getXml();
		$result = RecurlyClient::__sendRequest($uri, 'POST', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'credit', 'RecurlyAccountCredit');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not create a credit for {$this->account_code}: {$result->response} ({$result->code})");
		}
	}
	
	public function chargeAccount($amount, $description = '')
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account_code) . RecurlyClient::PATH_ACCOUNT_CHARGES;
		$credit = new RecurlyAccountCharge($amount, $description);
		$data = $credit->getXml();
		$result = RecurlyClient::__sendRequest($uri, 'POST', $data);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'charge', 'RecurlyAccountCharge');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not create a charge for {$this->account_code}: {$result->response} ({$result->code})");
		}
	}
	
	public function listCredits()
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account_code) . RecurlyClient::PATH_ACCOUNT_CREDITS;
		$result = RecurlyClient::__sendRequest($uri);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'credit', 'RecurlyAccountCredit');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not list credits for account {$this->account_code}: {$result->response} ({$result->code})");
		}
	}
	
	public function listCharges()
	{
		$uri = RecurlyClient::PATH_ACCOUNTS . urlencode($this->account_code) . RecurlyClient::PATH_ACCOUNT_CHARGES;
		$result = RecurlyClient::__sendRequest($uri);
		if (preg_match("/^2..$/", $result->code)) {
			return RecurlyClient::__parse_xml($result->response, 'charge', 'RecurlyAccountCharge');
		} else if (strpos($result->response, '<errors>') > 0 && $result->code == 422) {
			throw new RecurlyValidationException($result->code, $result->response);
		} else {
			throw new RecurlyException("Could not list charges for account {$this->account_code}: {$result->response} ({$result->code})");
		}
	}
	
	public function getXml()
	{
		$doc = new DOMDocument("1.0");
		$this->populateXmlDoc($doc, $doc);
		return $doc->saveXML();
	}
	
	public function populateXmlDoc(&$doc, &$root)
	{
		$account = $root->appendChild($doc->createElement("account"));
		$account->appendChild($doc->createElement("account_code", $this->account_code));
		$account->appendChild($doc->createElement("username", $this->username));
		$account->appendChild($doc->createElement("email", $this->email));
		$account->appendChild($doc->createElement("first_name", $this->first_name));
		$account->appendChild($doc->createElement("last_name", $this->last_name));
		$account->appendChild($doc->createElement("company_name", $this->company_name));
		return $account;
	}
}

class RecurlyAccountCredit
{
	var $amount_in_cents;
	var $description;
	
	public function RecurlyAccountCredit($amount = 0, $description = null)
	{
		$this->amount_in_cents = intval($amount * 100);
		$this->description = $description;
	}
	
	/* Normalize the amount to a positive float amount */
	public function amount()
	{
		return abs($this->amount_in_cents / 100.0);
	}
	
	public function getXml()
	{
		$amount_in_cents = 
		$doc = new DOMDocument("1.0");
		$root = $doc->appendChild($doc->createElement("credit"));
		$root->appendChild($doc->createElement("amount_in_cents", $this->amount_in_cents));
		$root->appendChild($doc->createElement("description", $this->description));
		return $doc->saveXML();
	}
}

class RecurlyAccountCharge
{
	var $amount_in_cents;
	var $description;
	
	public function RecurlyAccountCharge($amount = 0, $description = null)
	{
		$this->amount_in_cents = intval($amount * 100);
		$this->description = $description;
	}
	
	/* Normalize the amount to a positive float amount */
	public function amount()
	{
		return abs($this->amount_in_cents / 100.0);
	}
	
	public function getXml()
	{
		$amount_in_cents = 
		$doc = new DOMDocument("1.0");
		$root = $doc->appendChild($doc->createElement("charge"));
		$root->appendChild($doc->createElement("amount_in_cents", $this->amount_in_cents));
		$root->appendChild($doc->createElement("description", $this->description));
		return $doc->saveXML();
	}
}
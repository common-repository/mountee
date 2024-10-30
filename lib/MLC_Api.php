<?php

class MLC_Api
{
	private static $_api_url = 'https://hellomountee.com/api/v1/';

	public static function check_license($license)
	{
		$response = self::_query('check_wp_license', array('purchase_code' => $license));

		if ($response === NULL)
		{
			return NULL;
		}
		if (isset($response->success) && $response->success === TRUE)
		{
			return TRUE;
		}

		return FALSE;
	}

	private static function _query($endpoint, $parameters = array())
	{
		$ch = curl_init(self::$_api_url.$endpoint.'/');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mountee License Checker');
		curl_setopt($ch, CURLOPT_REFERER, get_site_url());
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
		curl_setopt($ch, CURLOPT_POST, 1);

		$response = curl_exec($ch);
		// print_r($response);

		curl_close($ch);

		$response = json_decode($response);
		if (is_object($response))
		{
			return $response;
		}
		else
		{
			return NULL;
		}
	}
}
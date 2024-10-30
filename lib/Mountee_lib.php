<?php

class Mountee_lib
{
	public static $dum_file_id = 'mt-dum';

	public function check_license()
	{
		$mountee_license = get_option('mountee_license');
		if ($mountee_license)
		{
			$cached_result = get_transient('Mountee_MLC_result');
			if ($cached_result)
			{
				$cached_content = unserialize($cached_result);
				// If we're still using the same license
				//   if not, we'll need to try and see if the new license is valid
				if ($cached_content['license'] == $mountee_license)
				{
					if ($cached_content['valid'] == 'y')
					{
						return TRUE;
					}
					else if ($cached_content['valid'] == 'n')
					{
						return FALSE;
					}
				}
			}

			$result = MLC_Api::check_license($mountee_license);
			if ($result)
			{
				$cache_ttl = (60*60*24*30);
				$cached_result = 'y';
			}
			else
			{
				$cache_ttl = (60*60*24);
				$cached_result = 'n';
			}
			$cached_str = serialize(array('license' => $mountee_license, 'valid' => $cached_result));
			set_transient('Mountee_MLC_result', $cached_str, $cache_ttl );
			return $result;
		}
		return FALSE;
	}

	public static function get_license_dummy_file()
	{
		return array(
			'id'	=> Mountee_lib::$dum_file_id,
			'name'	=> 'AN-UNLICENSED-DEMO.txt',
			'type'	=> '',
			'data'	=> base64_encode("Hello, you!

Thank you for trying Mountee 3. This site is currently running in demo mode without a license.

To purchase a license for this site, please go to

https://hellomountee.com/

and click on the link to purchase an ExpressionEngine license.

Your support is invaluable, and by that, I mean, valuable.

All of us here at Hop Studios thank you very much for it.

TTFN
Travis Smith
President, Hop Studios")
		);
	}
}
<?php

/**
 * Hestia Control Panel Password Driver
 *
 * @version 1.0
 * @author HestiaCP <info@hestiacp.com>
 */
class rcube_hestia_password
{
	public function save($curpass, $passwd)
	{
		$rcmail = rcmail::get_instance();
		$hestia_host = $rcmail->config->get("password_hestia_host");

		if (empty($hestia_host)) {
			$hestia_host = "localhost";
		}

		$hestia_port = $rcmail->config->get("password_hestia_port");
		if (empty($hestia_port)) {
			$hestia_port = "8083";
		}

		$postvars = [
			"email" => $_SESSION["username"],
			"password" => $curpass,
			"new" => $passwd,
			// The client, so its other devices get the mail password grace; the vhost sets X-Real-IP.
			"ip" => rcube_utils::remote_addr(),
		];
		$url = "https://{$hestia_host}:{$hestia_port}/reset/mail/";
		$ch = curl_init();
		if (
			false ===
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => http_build_query($postvars),
				CURLOPT_USERAGENT => "Hestia Control Panel Password Driver",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
			])
		) {
			// should never happen
			throw new Exception("curl_setopt_array() failed: " . curl_error($ch));
		}
		$result = curl_exec($ch);
		if (curl_errno($ch) !== CURLE_OK) {
			throw new Exception("curl_exec() failed: " . curl_error($ch));
		}
		curl_close($ch);
		// The exact answer: an "ok" anywhere, a cookie header included, took a silent refusal for success.
		if (trim($result) === "==ok==") {
			return PASSWORD_SUCCESS;
		} else {
			return PASSWORD_ERROR;
		}
	}
}

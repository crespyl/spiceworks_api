<?php

/*
	SPICEWORKS UNOFFICIAL EXTERNAL API
	This is a small script to authenticate a user to Spiceworks and then fetch some
	JSON API data from their Internal JSON API

	Caution: This may break in the future if Spiceworks changes the way they authenticate.

	Version: 2

	Copyright (c) 2014, Ambassador Enterprises http://ambassador-enterprises.com/

	Original Version Copyright (c) 2012, Media Realm http://mediarealm.com.au/
	Based on source from https://github.com/anthonyeden/spiceworks_api

	All rights reserved.

	------------------------------------------------------------------------------------------

	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this list
	  of conditions and the following disclaimer.

	* Redistributions in binary form must reproduce the above copyright notice, this list
	  of conditions and the following disclaimer in the documentation and/or other materials
	  provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
	IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
	INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
	OF SUCH DAMAGE.

	------------------------------------------------------------------------------------------

*/
//----------------------

class Spiceworks {

	// Unicode has a 'Pick' in misc symbols for some reason, spiceworks uses "UPWARDS ANCORA"
	// (calling it a pickaxe, confusing things further) as a kind of nonce, as far as I can
	// tell.  The login POST var `_pickaxe` MUST be this exact char in order to login successfully
	const PICKAXE = '\u2E15';
	const DEBUG = true;

	protected $url_root;
	protected $cookie_file;

	protected $user_email;
	protected $user_password;

	protected $debug_log;

	protected $logged_in;

	public function __construct($url, $userEmail, $userPassword, $cookie_file, $nossl = false) {
		// url should have a trailing slash
		$this->url_root    = $url;
		$this->cookie_file = $cookie_file;

		$this->user_email    = $userEmail;
		$this->user_password = $userPassword;

		$this->logged_in = false;

		if (self::DEBUG) {
			$this->debug_log = fopen('php://temp', 'rw+');
		}

		//We need to initiate a session and get the authenticity_token from the logon page before we can actually login.
		$curl = curl_init($this->url_root . 'login');

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_COOKIESESSION, true);        // start with a blank session (no cookies)
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);     // save cookies to the cookiejar

		if($nossl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}

		if(self::DEBUG) {
			curl_setopt($curl, CURLOPT_VERBOSE, true);
			curl_setopt($curl, CURLOPT_STDERR, $this->debug_log);
		}

		$response = curl_exec($curl);
		curl_close($curl);

		$dom = new DOMDocument();
		// suppress invalid html errors
		libxml_use_internal_errors(true);

		// load result into a DomDocument for querying auth token
		$result = $dom->loadHTML($response);

		// select the value attribute of the input element named 'authenticity_token'
		$finder = new DOMXPath($dom);
		$attr = $finder->query('//input[@name="authenticity_token"]/@value')->item(0);
		$token = $attr->value;

		if(self::DEBUG) {
			fwrite($this->debug_log, "\nReceived Authenticity Token: " . $token . "\n");;
			fwrite($this->debug_log, "Saved Cookiejar File:\n");
			fwrite($this->debug_log, file_get_contents($this->cookie_file));
			fwrite($this->debug_log, "\n\n");
		}

		// Now that we have the authenticity_token, plain (logged-out) session cookie (in the cookiejar), 'pickaxe', and user
		// details, we can prepare our POST request to perform the actual login

		$fields_string = http_build_query(array('authenticity_token' => $token,
		                                        '_pickaxe' => self::PICKAXE,
		                                        'pro_user' => array('email' => $this->user_email,
		                                                            'password' => $this->user_password))
		                                  );

		if(self::DEBUG) {
			fwrite($this->debug_log, "Prepared Query String: " . $fields_string . "\n");
		}

		/* The original version of this script posted directly
		 * to the login url, and passed the vars in the request
		 * body using CURLOPT_POSTFIELDS * Unfortunately, I
		 * couldn't get that to work in my configuration (might be
		 * SW changing things, or possibly related to my trying to
		 * login * with a "reports" user), so after logging browser
		 * traffic during SW login, appending the params as a
		 * query string seems to be the correct way to do things.
		 */
		$curl = curl_init($this->url_root . 'login?' . $fields_string);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);

		// read our (logged-out) session cookies from the previous request
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);

		// store our (if all goes well) tasty new logged in session cookies
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);

		// although this is a POST request this time, vars are already attached to the url
		curl_setopt($curl, CURLOPT_POST, true);

		// Explicitly set POSTFILEDS to 0, since we aren't sending anything in the request body
		curl_setopt($curl, CURLOPT_POSTFIELDS, 0);

		if($nossl) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}

		if(self::DEBUG) {
			curl_setopt($curl, CURLOPT_VERBOSE, true);
			curl_setopt($curl, CURLOPT_STDERR, $this->debug_log);
		}

		$response = curl_exec($curl);

		// if this is anything other than 302 something has probably gone wrong
		$status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		// *NOTE* cURL won't save cookies to the jar until curl_close
		curl_close($curl);

		if(self::DEBUG) {
			fwrite($this->debug_log, "\n");
			fwrite($this->debug_log, "Got status code: " . $status . "\n\n");
			fwrite($this->debug_log, "Response:\n\n");
			fwrite($this->debug_log, $response);
			fwrite($this->debug_log, "\n\n");
			fwrite($this->debug_log, "Saved Cookiejar File:\n");
			fwrite($this->debug_log, file_get_contents($this->cookie_file));
			fwrite($this->debug_log, "\n\n");
		}

		if($status === 302) {
			// all has gone well and we now have valid session cookies in the jar
			fwrite($this->debug_log, "\nLogged in successfully!\n\n");
			$this->logged_in = true;
		}
	}

	public function getLog() {
		rewind($this->debug_log);
		return stream_get_contents($this->debug_log);
	}

	public function getURL($url) {
		if(! $this->logged_in) {
			return false;
		}

		$curl = curl_init($this->url_root . $url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

		if(self::DEBUG) {
			curl_setopt($curl, CURLOPT_VERBOSE, true);
			curl_setopt($curl, CURLOPT_STDERR, $this->debug_log);
		}

		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}
}

?>
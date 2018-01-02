<?php 
Class Lib_outlook
{
    private $clientId = "4cdd090c-7154-49e9-ae49-574e135f0e0d";
    private $clientSecret = "EUR8BL8nejRuSThUYgKmhwp";
    private $authority = "https://login.microsoftonline.com";
    private $authorizeUrl = '/common/oauth2/v2.0/authorize?client_id=%1$s&redirect_uri=%2$s&response_type=code&scope=%3$s';
    private $tokenUrl = "/common/oauth2/v2.0/token";
    var $outlookApiUrl = "https://outlook.office.com/api/v2.0";
    private $scopes = array("openid", 
                            "https://outlook.office.com/mail.read",
                            "https://outlook.office.com/contacts.read");
    public $redirectUri = '';
    public function __construct() {
        $this->redirectUri = 'https://localhost.d4f.com.br/api/usuario/importContactsOutlook';
    }

    public function getLoginUrl() {
        $scopestr = implode(" ", $this->scopes);

        $loginUrl = $this->authority.sprintf($this->authorizeUrl, $this->clientId, urlencode($this->redirectUri), urlencode($scopestr));

        error_log("Generated login URL: ".$loginUrl);
        return $loginUrl;
	}

    public function getFriends() 
    {
        $token = $this->getToken('authorization_code', $_GET['code']);
        if(isset($token['access_token'])){
            $_SESSION['outlook_access_token'] = $token['access_token'];
        }

        $user = $this->getUser($_SESSION['outlook_access_token']);
        $contacts = $this->getContacts($_SESSION['outlook_access_token'], $user['EmailAddress']);
        $friends = array();
        foreach ($contacts['value'] as $item) {
            if(isset($item['EmailAddresses'][0])){
                $friends[] = array('nome' => $item['GivenName'], 'email' => $item['EmailAddresses'][0]['Address']);
            }
        }
        return $friends;
    }

	public function getToken($grantType, $code) {
		$parameter_name = $grantType;
		if (strcmp($parameter_name, 'authorization_code') == 0) {
			$parameter_name = 'code';
		}

		// Build the form data to post to the OAuth2 token endpoint
		$token_request_data = array(
			"grant_type" => $grantType,
			$parameter_name => $code,
			"redirect_uri" => $this->redirectUri,
			"scope" => implode(" ", $this->scopes),
			"client_id" => $this->clientId,
			"client_secret" => $this->clientSecret
		);

		// Calling http_build_query is important to get the data
		// formatted as expected.
		$token_request_body = http_build_query($token_request_data);
		error_log("Request body: ".$token_request_body);

		$curl = curl_init($this->authority.$this->tokenUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $token_request_body);

		$response = curl_exec($curl);
		error_log("curl_exec done.");

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		error_log("Request returned status ".$httpCode);
		if ($httpCode >= 400) {
			return array('errorNumber' => $httpCode,
				'error' => 'Token request returned HTTP error '.$httpCode);
		}

		// Check error
		$curl_errno = curl_errno($curl);
		$curl_err = curl_error($curl);
		if ($curl_errno) {
			$msg = $curl_errno.": ".$curl_err;
			error_log("CURL returned an error: ".$msg);
			return array('errorNumber' => $curl_errno,
				'error' => $msg);
		}

		curl_close($curl);

		// The response is a JSON payload, so decode it into
		// an array.
		$json_vals = json_decode($response, true);
		error_log("TOKEN RESPONSE:");
		foreach ($json_vals as $key=>$value) {
			error_log("  ".$key.": ".$value);
		}

		return $json_vals;
	}

    public function getUser($access_token) 
    {
		$getUserParameters = array (
			// Only return the user's display name and email address
			"\$select" => "EmailAddress"
		);

		$getUserUrl = $this->outlookApiUrl."/Me?".http_build_query($getUserParameters);

		return $this->makeApiCall($access_token, "", "GET", $getUserUrl);
	}

    public function getContacts($access_token, $user_email) 
    {
		$getContactsParameters = array (
			"\$select" => "GivenName,EmailAddresses",
			"\$orderby" => "GivenName",
			"\$top" => "10000"
		);

		$getContactsUrl = $this->outlookApiUrl."/Me/Contacts?".http_build_query($getContactsParameters);

		return $this->makeApiCall($access_token, $user_email, "GET", $getContactsUrl);
	}

	public function makeApiCall($access_token, $user_email, $method, $url, $payload = NULL) {
		// Generate the list of headers to always send.
		$headers = array(
			"User-Agent: php-tutorial/1.0",         // Sending a User-Agent header is a best practice.
			"Authorization: Bearer ".$access_token, // Always need our auth token!
			"Accept: application/json",             // Always accept JSON response.
			"client-request-id: ".self::makeGuid(), // Stamp each new request with a new GUID.
			"return-client-request-id: true",       // Tell the server to include our request-id GUID in the response.
			"X-AnchorMailbox: ".$user_email         // Provider user's email to optimize routing of API call
		);

		$curl = curl_init($url);

		switch(strtoupper($method)) {
		case "GET":
			// Nothing to do, GET is the default and needs no
			// extra headers.
			error_log("Doing GET");
			break;
		case "POST":
			error_log("Doing POST");
			// Add a Content-Type header (IMPORTANT!)
			$headers[] = "Content-Type: application/json";
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
			break;
		case "PATCH":
			error_log("Doing PATCH");
			// Add a Content-Type header (IMPORTANT!)
			$headers[] = "Content-Type: application/json";
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
			break;
		case "DELETE":
			error_log("Doing DELETE");
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			break;
		default:
			error_log("INVALID METHOD: ".$method);
			exit;
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($curl);
		error_log("curl_exec done.");

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		error_log("Request returned status ".$httpCode);

		if ($httpCode >= 400) {
			return array('errorNumber' => $httpCode,
				'error' => 'Request returned HTTP error '.$httpCode);
		}

		$curl_errno = curl_errno($curl);
		$curl_err = curl_error($curl);

		if ($curl_errno) {
			$msg = $curl_errno.": ".$curl_err;
			error_log("CURL returned an error: ".$msg);
			curl_close($curl);
			return array('errorNumber' => $curl_errno,
				'error' => $msg);
		}
		else {
			error_log("Response: ".$response);
			curl_close($curl);
			return json_decode($response, true);
		}
	}

	// This function generates a random GUID.
	public static function makeGuid(){
		if (function_exists('com_create_guid')) {
			error_log("Using 'com_create_guid'.");
			return strtolower(trim(com_create_guid(), '{}'));
		}
		else {
			error_log("Using custom GUID code.");
			$charid = strtolower(md5(uniqid(rand(), true)));
			$hyphen = chr(45);
			$uuid = substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid, 12, 4).$hyphen
				.substr($charid, 16, 4).$hyphen
				.substr($charid, 20, 12);

			return $uuid;
		}
	}
}

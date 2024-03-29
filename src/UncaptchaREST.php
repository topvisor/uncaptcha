<?php

namespace Topvisor\Uncaptcha;

trait UncaptchaREST{

	private $timeout = 20;
	private $curlResponse = NULL;
	private $curlErrorMessage = NULL;
	private $result = NULL; // result object, see genResult()

	function setTimeout(int $timeout): void{
		$this->timeout = $timeout;
	}

	function getResult(): ?\stdClass{
		return $this->result;
	}

	// set $this->result
	// on success return $this->result->response or $this->result->status
	// on error return NULL
	protected function call(string $methodName, array $post = []){
		if(!$this->host) throw new \Exception('Please, set host');

		$url = "$this->scheme://$this->host";
		$headers = [];

		if($this->getHostIp()){
			$url = "$this->scheme://".$this->getHostIp();
			$headers[] = "Host: $this->host";
		}

		if($this->v == 2) $headers[] = 'Content-Type: application/json; charset=utf-8';

		$this->prepareRequest($methodName, $url, $post);

		if($this->v == 1) $postFormatted = http_build_query($post);
		if($this->v == 2) $postFormatted = json_encode($post, JSON_PRETTY_PRINT);

		$this->debugLog("================= $url =================", 2);
		$this->debugLog($postFormatted, 2);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFormatted);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if($this->getHostIp()) curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$this->curlResponse = curl_exec($ch);

		$this->curlErrorMessage = curl_error($ch);
		if($this->curlErrorMessage){
			$this->curlErrorMessage .= ' ('.curl_errno($ch).')';

			$this->debugLog("- Curl: $this->curlErrorMessage");
		}

		$this->debugLog("'$this->curlResponse'", 2);

		$this->result = $this->genResult($this->curlResponse, $methodName);
		if($this->result->errorId){
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$this->setErrorMessage('- '.$this->result->errorDescription.' ('.$this->result->errorCode.')'.' ['.$this->result->errorId.'], http code: '.$httpCode);
		}

		curl_close($ch);

		$this->debugLog('=================', 2);
		$this->debugLog('', 2);

		if($this->result->errorId) return NULL;

		if($this->result->response) return $this->result->response;
		if($this->result->status) return $this->result->status;

		return NULL;
	}

	private function prepareRequest($methodName, &$_url, &$_post){
		if($this->v == 1){
			switch($methodName){
				case 'createTask':
					$_url .= '/in.php?';

					$_post = $_post['task'];

					break;
				case 'getTaskResult':
					$_url .= '/res.php?action=get';

					$_post['id'] = $_post['taskId'];
					unset($_post['taskId']);

					$_url .= '&'.http_build_query($_post);

					break;
				default:
					$methodName = strtolower($methodName);

					$_url .= "/res.php?action=$methodName";
			}

			$_url .= "&key=$this->clientKey&json=1";
		}else{
			$_url .= "/$methodName";

			$_post['clientKey'] = $this->clientKey;
		}

		if($this->host == 'rucaptcha.com'){
			if(isset($_post['cookies'])) $_post['cookies'] = preg_replace('~(\w+)=([^;]+)(; ?)?~', '$1:$2;', $_post['cookies']);
		}

		if(isset($_post['cookies'])) $_post['cookies'] = trim($_post['cookies'], '; ');
	}

	private function genResult(string $response, string $methodName): \stdClass{
		$result = json_decode($response);

		if(!$result or !is_object($result)) $result = new \stdClass();

		if(!isset($result->response)) $result->response = NULL;
		if(!isset($result->status)) $result->status = NULL;
		if(!isset($result->cookies)) $result->cookies = NULL;

		if(!isset($result->errorId)) $result->errorId = NULL;
		if(!isset($result->errorCode)) $result->errorCode = NULL;
		if(!isset($result->errorDescription)) $result->errorDescription = NULL;

		if($this->v == 1){
			$result->response = $result->request??NULL; // похоже на опечатку в v=1
			unset($result->request);

			// если json=1 не поддерживается, то вернется plain text в формате "result" или "status|result"
			if(!$result->response and !$result->errorId){
				if(count(explode('|', $response)) == 1){
					$result->response = $response;

					if($result->response == 'CAPCHA_NOT_READY'){
						$result->status = 0;
						$result->response = $response;
					}

					if($result->response == 'OK_REPORT_RECORDED'){
						$result->status = 1;
						$result->response = $response;
					}
				}

				if(count(explode('|', $response)) >= 2){
					$result->status = (explode('|', $response)[0] == 'OK')?1:0;
					$result->response = explode('|', $response)[1];
				}
			}

			if($result->response){
				if(strpos($result->response, 'ERROR_') !== false){
					$result->errorId = 1;
					$result->errorCode = $result->response;
					$result->errorDescription = $result->response;

					$result->response = NULL;
				}

				if(isset($result->error_text)) $result->errorDescription = $result->error_text;
			}

			if(!$result->errorId and isset($result->error_text)){
				$result->errorId = 1;
				$result->errorCode = $result->response;
				$result->errorDescription = $result->error_text;
			}
		}

		if($this->v == 2){
			$result->response = NULL;

			if(isset($result->taskId)) $result->response = $result->taskId;
			if(isset($result->balance)) $result->response = $result->balance;

			if(isset($result->solution)){
				$result->response = $result->solution;

				if(isset($result->solution->text)) $result->response = $result->solution->text;
				if(isset($result->solution->gRecaptchaResponse)) $result->response = $result->solution->gRecaptchaResponse;
				if(isset($result->solution->token)) $result->response = $result->solution->token;

				if(isset($result->solution->cookies)) $result->cookies = $result->solution->cookies;
			}

			if(strpos($methodName, 'getcmstatus') === 0) $result->response = json_decode($response);
		}

		if(!$result->response and !$result->status and !$result->errorId){
			$result->errorId = 1;
			$result->errorCode = 'ERROR_UNKNOWN';
			$result->errorDescription = '';

			$result->errorDescription = @iconv('utf-8', 'utf-8', $response);
			if(!$result->errorDescription) $result->errorDescription = @iconv('windows-1251', 'utf-8', $response);
			if(!$result->errorDescription and $this->curlErrorMessage){
				$result->errorDescription = $this->curlErrorMessage;
				$result->errorCode = 'ERROR_CURL';
			}
			if(!$result->errorDescription) $result->errorDescription = 'Empty document';
		}

		return $result;
	}

}

<?php

namespace Topvisor\Uncaptcha;

trait UncaptchaREST{

	private $timeout = 10;
	private $curlResponse = NULL;
	private $curlErrorMessage = NULL;
	private $curlResult = NULL; // формат объекта см. в genResult()

	function setTimeout(int $timeout): void{
		$this->timeout = $timeout;
	}

	private function call(string $methodName, array $post = []): ?\stdClass{
		if(!$this->host) throw new Exception('Please, set host');

		$url = "$this->scheme://$this->host";

		if($this->v == 1){
			switch($methodName){
				case 'getTest':
					$url .= 'res.php';
				case 'createTask':
					$url .= '/in.php';

					break;
				case 'getTaskResult':
					$url .= 'res.php?action=get';

					break;

					break;
				default:
					$url .= "res.php?action=$methodName";
			}

			$post['key'] = $this->clientKey;
		}else{
			$url .= "/$methodName";

			$post['clientKey'] = $this->clientKey;
		}

		$json = json_encode($post, JSON_PRETTY_PRINT);

		$this->debugMessage('');
		$this->debugMessage("================= $url =================");
		$this->debugMessage($json);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POST, $json);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$headers = [
			'Content-Type: application/json; charset=utf-8',
			'Accept: application/json',
			'Upgrade-Insecure-Requests: 1',
			'Content-Length: '.strlen($json)
		];
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

		$this->curlResponse = curl_exec($ch);

		$this->curlErrorMessage = curl_error($ch);
		if($this->curlErrorMessage){
			$this->curlErrorMessage .= ' ('.curl_errno($ch).')';

			$this->debugMessage($this->curlErrorMessage);
		}

		$this->debugMessage("'$this->curlResponse'");

		$this->curlResult = $this->genResult($this->curlResponse);
		if($this->curlResult->errorId){
			$this->setErrorMessage($this->curlResult->errorDescription.' ('.$this->curlResult->errorCode.')'.' ['.$this->curlResult->errorId.']');
		}

		curl_close($ch);

		$this->debugMessage('=================');

		if($this->curlResult->errorId) return NULL;

		return $this->curlResult;
	}

	private function genResult(string $response): \stdClass{
		$result = json_decode($response);

		if(!$result) $result = new \stdClass();

		if(!isset($result->response)) $result->response = NULL;
		if(!isset($result->status)) $result->status = NULL;
		if(!isset($result->cookies)) $result->cookies = NULL;

		if(!isset($result->errorId)) $result->errorId = NULL;
		if(!isset($result->errorCode)) $result->errorCode = NULL;
		if(!isset($result->errorDescription)) $result->errorDescription = NULL;

		if($this->v == 1){
			$result->response = $result->request??NULL; // похоже на опечатку в v=1

			if($result->response){
				if(strpos($result->response, 'ERROR_') !== false){
					$result->errorId = 1;
					$result->errorCode = $result->response;
					$result->errorDescription = '';

					$result->response = NULL;
				}

				###if(isset($result->error_text)) $result->errorDescription = $result->error_text
			}

			// если json=1 не поддерживается, то вернется plain text в формате status|result
			if(!$result->response and !$result->errorId){
				if(!$response) $response = '|';

				if(count(explode('|', $response)) == 2){
					$result->status = (explode('|', $response)[0] == 'OK')?'ready':'processing';
					$result->response = explode('|', $response)[1];
				}
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
		}

		if(!$result->response and !$result->errorId){
			$result->errorId = 1;
			$result->errorCode = 'ERROR_UNKNOWN';
			$result->errorDescription = '';

			$result->errorDescription = @iconv('utf-8', 'utf-8', $response);
			if(!$result->errorDescription) $result->errorDescription = @iconv('windows-1251', 'utf-8', $response);
			if(!$result->errorDescription) $result->errorDescription = $this->curlErrorMessage;
			if(!$result->errorDescription) $result->errorDescription = 'Empty document';
		}

		return $result;
	}

}

<?php

// Поддерживает работу с сервисами следующих вид капч:
// - ReCaptcha V2
// - ReCaptcha V2 Invisible
// - ReCaptcha V3
// - FunCaptcha
// - GeeTest
// - hCaptcha
// - Custom - Возможность работать с другими капчами сервисов, через произвольную установку параметров запросов: см. setPost()

namespace Topvisor\Uncaptcha;

class Uncaptcha{

	use UncaptchaREST;
	use UncaptchaDebug;

	const V = '1.3.9';

	protected $referalId = NULL;
	private $scheme = 'http';
	protected $host = '';
	private $clientKey = NULL;
	protected $v = NULL;
	protected $proxy = [
		'type' => 'http',
		'server' => NULL,
		'port' => NULL,
		'login' => NULL,
		'password' => NULL
	];
	protected $userAgent = NULL;
	protected $cookies = NULL;
	protected $createTaskPost = [];
	private $taskTimeout = 240;
	protected $taskId = NULL;
	private $taskElapsed = 0;
	private $errorMessage = '';
	private $isCLI = NULL;

	function __construct(){
		$this->isCLI = function_exists('cli_set_process_title');

		$this->setDebugFormat($this->isCLI?0:1);
	}

	function setReferalId(int $referalId): void{
		$this->referalId = $referalId;
	}

	function setUseHTTPS(bool $useHTTPS = true): void{
		$this->scheme = $useHTTPS?'https':'http';
	}

	function setHost(string $host){
		$this->host = $host;

		switch($host){
			case 'rucaptcha.com':
				if(!$this->referalId) $this->referalId = 2708; // referer author
				$this->v = 1;

				break;
			case 'api.anti-captcha.com':
				$this->v = 2;

				break;
		}
	}

	// v1 - API style: simplesite.com/in.php / simplesite.com/res.php?action=%methodName%
	// v2 - API style: api.simplesite.com/%methodName%
	function setV(int $v): void{
		$this->v = $v;
	}

	function setKey(string $clientKey): void{
		$this->clientKey = $clientKey;
	}

	function setCreateTaskPost(array $createTaskPost): void{
		$this->createTaskPost = $createTaskPost;
	}

	function setTaskTimeout(int $timeout): void{
		$this->taskTimeout = $timeout;
	}

	function getTaskid(): ?string{
		return $this->taskId;
	}

	function getTaskElapsed(): int{
		return $this->taskElapsed;
	}

	function getErrorMessage(): string{
		return $this->errorMessage;
	}

	protected function setErrorMessage(string $message): void{
		$this->errorMessage = $message;

		$this->debugLog($message);
	}

	// return result object with additional property "taskId"
	// see result object in genResult() fore more information
	function resolve(): ?string{
		$label = $this->genDebugLabel();

		$this->debugLog('<b>Captcha resolving</b>');
		$this->debugLog("===== $label =====");
		$this->debugLog("- $this->host");
		$this->debugLog('');

		$ok = $this->createTask();

		if($ok){
			$this->debugLog('- task id: '.$this->taskId);
			$this->debugLog('');

			$ok = $this->checkResult();
		}

		$this->debugLog('');
		$this->debugLog('- elapsed: '.$this->taskElapsed);

		if($ok){
			$responseForLog = $this->getResult()->response;
			if($this->debugFormat == 1 and strlen($responseForLog) > 40) $responseForLog = '<i title="'.$responseForLog.'">hoverMe</i>';
			$this->debugLog("- response: $responseForLog");
		}else{
			$this->debugLog('- fail');
		}

		$this->debugLog("===== /$label =====");

		if(!$ok) return NULL;

		return $this->getResult()->response;
	}

	function getBalance(): ?float{
		$this->debugLog('<b>Get balance</b>');

		$response = $this->call('getBalance');
		$this->debugLog("- response: $response");

		return $response;
	}

	function getAppStats(): ?\stdClass{
		$this->debugLog('<b>Get app stats</b>');

		return $this->call('getAppStats');
	}

	function getCMStatus(): ?\stdClass{
		$this->debugLog('<b>Get smc stats</b>');

		return $this->call("getcmstatus?key=$this->clientKey");
	}

	function reportBad(): ?bool{
		if(!$this->taskId) return $this->setErrorMessage('Task does not exists');

		$label = $this->genDebugLabel();

		if($this->v == 1){
			$this->debugLog("<b>Captcha reportBad</b>: $label / $this->taskId");

			return (bool)$this->call("reportbad&id=$this->taskId");
		}

		// processing v2 must do in Module Class
		if($this->v == 2) return (bool)$this->debugLog("<b>Captcha reportBad</b>: $label / $this->taskId (idle command)");
	}

	function reportGood(): ?bool{
		if(!$this->taskId) return (bool)$this->setErrorMessage('Task does not exists');

		$label = $this->genDebugLabel();

		if($this->v == 1){
			$this->debugLog("<b>Captcha reportGood</b>: $label / $this->taskId");

			return (bool)$this->call("reportgood&id=$this->taskId");
		}

		// processing v2 must do in module Class
		if($this->v == 2) return (bool)$this->debugLog("<b>Captcha reportGood</b>: $label / $this->taskId (idle command)");
	}

	private function createTask(): bool{
		$this->taskId = 0;
		$this->taskElapsed = 0;

		$this->debugLog('<b>Create task</b>', 2);
		$this->taskId = (int)$this->call('createTask', ['task' => $this->genCreateTaskPost()]);

		return (bool)$this->taskId;
	}

	protected function genCreateTaskPost(array $post = []): array{
		if($this->v == 1){
			if($this->proxy['server']){
				$post['proxytype'] = $this->proxy['type'];
				$post['proxy'] = '';
				if($this->proxy['login']){
					$post['proxy'] .= $this->proxy['login'];
					if($this->proxy['password']) $post['proxy'] .= ':'.$this->proxy['password'];
					$post['proxy'] .= '@';
				}
				$post['proxy'] .= $this->proxy['server'];
				$post['proxy'] .= ':'.$this->proxy['port'];
			}

			if($this->userAgent) $post['UserAgent'] = $this->userAgent;
			if($this->referalId) $post['soft_id'] = $this->referalId;
		}

		if($this->v == 2){
			if($this->proxy['server']){
				$post['proxyType'] = $this->proxy['type'];
				if($this->proxy['login']) $post['proxyLogin'] = $this->proxy['login'];
				if($this->proxy['password']) $post['proxyPassword'] = $this->proxy['password'];
				if($this->proxy['server']) $post['proxyAddress'] = $this->proxy['server'];
				if($this->proxy['port']) $post['proxyPort'] = $this->proxy['port'];
			}

			if($this->userAgent) $post['userAgent'] = $this->userAgent;
		}

		if($this->cookies) $post['cookies'] = $this->cookies;

		foreach($this->createTaskPost as $name => $value) $post[$name] = $value;

		return $post;
	}

	private function checkResult(): bool{
		if($this->taskElapsed == 0){
			$this->debugLog('- wait 3 seconds');

			sleep(3);
			$this->taskElapsed += 3;
		}

		$timeStart = time();

		$this->debugLog('<b>Check task status'.($this->taskElapsed?' (repeat)':'').'</b>', 2);
		$response = $this->call('getTaskResult', ['taskId' => $this->taskId]);
		if(!$response) return false;

		$timeElapsed = time() - $timeStart;
		$this->taskElapsed += $timeElapsed;

		if($timeElapsed > 1) $this->debugLog("- wait connection $timeElapsed seconds");

		$result = $this->getResult();
		switch($result->status){
			case '0':
			case 'processing':
				if($timeElapsed < 3){
					$this->debugLog('- wait 3 seconds');

					sleep(3);
					$this->taskElapsed += 3;
				}
				if($this->taskElapsed > $this->taskTimeout){
					$this->setErrorMessage("Timeout exceeded: $this->taskElapsed secs");

					return false;
				}

				return $this->checkResult();

			case '1':
			case 'ready':

				return true;
			default:
				$this->setErrorMessage('Expected task status: "processing" (0) or "ready" (1): '.$result->status);

				return false;
		}
	}

}

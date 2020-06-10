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

	
	const V = '1.3.2';

	private $referalId = NULL;
	private $scheme = 'https';
	private $host = '';
	private $clientKey = NULL;
	private $v = NULL;
	private $post = [];
	private $proxy = [
		'type' => 'http',
		'server' => NULL,
		'port' => NULL,
		'login' => NULL,
		'password' => NULL
	];
	private $userAgent = NULL;
	private $cookies = NULL;
	private $taskId = 0;
	private $taskTimeout = 240;
	private $taskTimeoutElapsed = 0;
	private $errorMessage = '';

	function setReferalId(int $referalId): void{
		$this->referalId = $referalId;
	}

	function setUseHTTPS(bool $useHTTPS = true): void{
		$this->scheme = $useHTTPS?'https':'http';
	}

	function setHost($host){
		$this->host = $host;

		switch($host){
			case 'rucaptcha.com':
				$this->referalId = 2708; // referer автора этой библиотеки

				break;
			case 'api.anti-captcha.com':
				$v = 2;

				break;
		}
	}

	// v1 - симуляция API вида simplesite.com/in.php / simplesite.com/res.php
	// v2 - симуляция API вида api.simplesite.com
	function setV(int $v): void{
		$this->v = $v;
	}

	function setKey(string $clientKey): void{
		$this->clientKey = $clientKey;
	}

	function setCookies(string $cookies): void{
		if($this->v == 2) throw new Exception('This function not allowed for this driver');

		$this->cookies = $cookies;
	}

	function setPost(array $post): void{
		$this->post = $post;
	}

	function setTaskTimeout(int $timeout): void{
		$this->taskTimeout = $timeout;
	}

	protected function setErrorMessage(string $message): void{
		$this->errorMessage = $message;

		$this->debugMessage($message);
	}

	function createTask(): bool{
		$this->taskId = 0;
		$this->taskTimeoutElapsed = 0;

		$result = $this->call('createTask', ['task' => $this->genTaskPost()]);
		if(!$result) return false;

		if(!isset($result->taskId)){
			$this->setErrorMessage('Expected taskId in response');
			return false;
		}

		$this->taskId = $result->taskId;

		return true;
	}

	function waitForResult(): bool{
		if($this->taskTimeoutElapsed > $this->taskTimeout){
			$this->setErrorMessage("Timeout exceeded: $this->taskTimeoutElapsed secs");

			return false;
		}

		$timeStart = time();

		if($this->taskTimeoutElapsed == 0) sleep(3);

		$this->debugMessage('Check task status');
		$result = $this->call('getTaskResult', ['taskId' => $this->taskId]);
		if(!$result) return false;

		$timeElapsed = time() - $timeStart;

		switch($result->status){
			case 'processing':
				if($timeElapsed < 3){
					$this->debugMessage('waiting 3 seconds');

					sleep(3);
					$timeElapsed += 3;
				}

				$this->taskTimeoutElapsed += $timeElapsed;

				return $this->waitForResult();

			case 'ready':
				$this->debugMessage('Task complete');

				return true;
			default:
				$this->setErrorMessage('Expected task status: "processing" or "ready": '.$result->status);

				return false;
		}
	}

	function getBalance(): ?float{
		$result = $this->call('getBalance');
		if(!$result) return $result;

		return $result->response;
	}

	function getAppStats(): ?stdClass{
		return $this->call('getAppStats');
	}

	function getCMStatus(): ?stdClass{
		return $this->call("getcmstatus?key=$this->clientKey");
	}

	function reportBad(){
		if(!$this->taskid) throw new Exception('Task does not exists');

		if($this->v == 1) $this->call('reportbad', ['id' => $this->taskid]);

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) $this->debugMessage("reportGood: $this->taskid (idle command)");
	}

	function reportGood(){
		if(!$this->taskid) throw new Exception('Task does not exists');

		if($this->v == 1) $this->call('reportgood', ['id' => $this->taskid]);

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) $this->debugMessage("reportGood: $this->taskid (idle command)");
	}

	protected function genTaskPost(array $post = []): array{
		if($this->v == 1){
			if($this->proxy['server']){
				$post['proxytype'] = $this->proxy['type'];
				$postdata['proxy'] = '';
				if($this->proxy['login']){
					$postdata['proxy'] .= $this->proxy['login'];
					if($this->proxy['password']) $postdata['proxy'] .= ':'.$this->proxy['password'];
					$postdata['proxy'] .= '@';
				}
				$postdata['proxy'] .= $this->proxy['server'];
				$postdata['proxy'] .= ':'.$this->proxy['port'];
			}

			if($this->userAgent) $post['UserAgent'] = $this->userAgent;

			$postdata['json'] = 1;
			$postdata['soft_id'] = $this->referalId;
		}

		if($this->v == 2){
			 $post['proxyType'] = $this->proxy['type'];
			if($this->proxy['login']) $post['proxyLogin'] = $this->proxy['login'];
			if($this->proxy['password']) $post['proxyPassword'] = $this->proxy['password'];
			if($this->proxy['server']) $post['proxyAddress'] = $this->proxy['server'];
			if($this->proxy['port']) $post['proxyPort'] = $this->proxy['port'];

			if($this->userAgent) $post['userAgent'] = $this->userAgent;
		}

		if($this->cookies) $post['cookies'] = $this->cookies;

		foreach($this->post as $name => $value) $this->post[$name] = $value;

		return $post;
	}

}

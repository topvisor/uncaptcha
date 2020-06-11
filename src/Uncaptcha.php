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

	protected $referalId = NULL;
	protected $scheme = 'http';
	protected $host = '';
	protected $clientKey = NULL;
	protected $v = NULL;
	protected $post = [];
	protected $proxy = [
		'type' => 'http',
		'server' => NULL,
		'port' => NULL,
		'login' => NULL,
		'password' => NULL
	];
	protected $userAgent = NULL;
	protected $cookies = NULL;
	protected $taskId = 51781204846;
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
				$this->v = 1;

				break;
			case 'api.anti-captcha.com':
				$this->v = 2;

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

	function setPost(array $post): void{
		$this->post = $post;
	}

	function setTaskTimeout(int $timeout): void{
		$this->taskTimeout = $timeout;
	}

	function getErrorMessage(): string{
		return $this->errorMessage;
	}

	protected function setErrorMessage(string $message): void{
		$this->errorMessage = $message;

		$this->debugMessage($message);
	}

	function createTask(): bool{
		$this->taskId = 0;
		$this->taskTimeoutElapsed = 0;

		$this->debugMessage('<b>Create task</b>');
		$result = $this->call('createTask', ['task' => $this->genTaskPost()]);
		if(!$result) return false;

		if(!$result->response){
			$this->setErrorMessage('Expected response');
			return false;
		}

		$this->taskId = $result->response;

		return true;
	}

	function waitForResult(): ?\stdClass{
		$timeStart = time();

		if($this->taskTimeoutElapsed == 0) sleep(3);

		$this->debugMessage('<b>Check task status'.($this->taskTimeoutElapsed?' (repeat)':'').'</b>');
		$result = $this->call('getTaskResult', ['taskId' => $this->taskId]);
		if(!$result) return $result;

		$timeElapsed = time() - $timeStart;

		switch($result->status){
			case '0':
			case 'processing':
				if($timeElapsed < 3){
					$this->debugMessage('Waiting 3 seconds');

					sleep(3);
					$timeElapsed += 3;
				}

				$this->taskTimeoutElapsed += $timeElapsed;

				if($this->taskTimeoutElapsed > $this->taskTimeout){
					$this->setErrorMessage("Timeout exceeded: $this->taskTimeoutElapsed secs");

					return NULL;
				}

				return $this->waitForResult();

			case '1':
			case 'ready':
				$this->debugMessage('Task complete');

				return $result;
			default:
				$this->setErrorMessage('Expected task status: "processing" (0) or "ready" (1): '.$result->status);

				return NULL;
		}
	}

	function getBalance(): ?float{
		$this->debugMessage('<b>Get balance</b>');

		$result = $this->call('getBalance');
		if(!$result) return $result;

		return $result->response;
	}

	function getAppStats(): ?\stdClass{
		$this->debugMessage('<b>Get app stats</b>');

		return $this->call('getAppStats');
	}

	function getCMStatus(): ?\stdClass{
		$this->debugMessage('<b>Get smc stats</b>');

		return $this->call("getcmstatus?key=$this->clientKey");
	}

	function reportBad(){
		if(!$this->taskId) return $this->setErrorMessage('Task does not exists');

		if($this->v == 1) return $this->call('reportbad', ['id' => $this->taskId]);

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) $this->debugMessage("reportBad: $this->taskId (idle command)");
	}

	function reportGood(){
		if(!$this->taskId) return $this->setErrorMessage('Task does not exists');

		if($this->v == 1) return $this->call('reportgood', ['id' => $this->taskId]);

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) $this->debugMessage("reportGood: $this->taskId (idle command)");
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

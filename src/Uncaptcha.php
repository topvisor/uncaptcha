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
	protected $taskId = NULL;
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

	function getTaskid(): ?string{
		return $this->taskId;
	}

	// return result object with additional property "taskId"
	// view result object in genResult()
	function resolve(): ?string{
		$ok = $this->createTask();
		if(!$ok) return NULL;

		$ok = $this->checkResult();
		if(!$ok) return NULL;

		return $this->getResult()->response;
	}

	function getBalance(): ?float{
		$this->debugMessage('<b>Get balance</b>');

		return $this->call('getBalance');
	}

	function getAppStats(): ?\stdClass{
		$this->debugMessage('<b>Get app stats</b>');

		return $this->call('getAppStats');
	}

	function getCMStatus(): ?\stdClass{
		$this->debugMessage('<b>Get smc stats</b>');

		return $this->call("getcmstatus?key=$this->clientKey");
	}

	function reportBad(): ?bool{
		if(!$this->taskId) return $this->setErrorMessage('Task does not exists');

		if($this->v == 1){
			$this->debugMessage("reportBad: $this->taskId");

			return (bool)$this->call("reportbad&id=$this->taskId");
		}

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) return (bool)$this->debugMessage("reportBad: $this->taskId (idle command)");
	}

	function reportGood(): ?bool{
		if(!$this->taskId) return (bool)$this->setErrorMessage('Task does not exists');

		if($this->v == 1){
			$this->debugMessage("reportGood: $this->taskId");

			return (bool)$this->call("reportgood&id=$this->taskId");
		}

		// обработка v2 должна происходить внутри соответствующих классов
		if($this->v == 2) return (bool)$this->debugMessage("reportGood: $this->taskId (idle command)");
	}

	private function createTask(): bool{
		$this->taskId = 0;
		$this->taskTimeoutElapsed = 0;

		$this->debugMessage('<b>Create task</b>');
		$this->taskId = (int)$this->call('createTask', ['task' => $this->genCreateTaskPost()]);

		return (bool)$this->taskId;
	}

	protected function genCreateTaskPost(array $post = []): array{
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

	private function checkResult(): bool{
		$timeStart = time();

		if($this->taskTimeoutElapsed == 0) sleep(3);

		$this->debugMessage('<b>Check task status'.($this->taskTimeoutElapsed?' (repeat)':'').'</b>');
		$response = $this->call('getTaskResult', ['taskId' => $this->taskId]);
		if(!$response) return false;

		$timeElapsed = time() - $timeStart;

		$result = $this->getResult();
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

					return false;
				}

				return $this->checkResult();

			case '1':
			case 'ready':
				$this->debugMessage('<b>Task complete:</b> '.$result->response);

				return true;
			default:
				$this->setErrorMessage('Expected task status: "processing" (0) or "ready" (1): '.$result->status);

				return false;
		}
	}

}

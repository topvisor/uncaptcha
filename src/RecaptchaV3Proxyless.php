<?php

namespace Topvisor\Uncaptcha;

class RecaptchaV3Proxyless extends Uncaptcha{

	protected $websiteUrl;
	protected $websiteKey;
	protected $pageAction;
	protected $minScore;

	function genCreateTaskPost(array $post = []): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'userrecaptcha',
					'v' => 3,
					'pageurl' => $this->websiteUrl,
					'googlekey' => $this->websiteKey,
					'action' => $this->pageAction
				];

				if($this->minScore) $post['min_score'] = $this->minScore;

				break;

			case 2:
				$post = [
					'type' => 'RecaptchaV3TaskProxyless',
					'websiteURL' => $this->websiteUrl,
					'websiteKey' => $this->websiteKey,
					'pageAction' => $this->pageAction
				];

				if($this->minScore) $post['minScore'] = $this->minScore;

				if(get_class($this) != 'RecaptchaV3Proxyless') $post['type'] = 'RecaptchaV3Task';

				break;
		}

		return parent::genCreateTaskPost($post);
	}

	function setWebsiteURL(string $value): void{
		$this->websiteUrl = $value;
	}

	function setWebsiteKey(string $value): void{
		$this->websiteKey = $value;
	}

	function setPageAction(string $value): void{
		$this->pageAction = $value;
	}

	function setMinScore(float $value): void{
		$this->minScore = $value;
	}

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

}

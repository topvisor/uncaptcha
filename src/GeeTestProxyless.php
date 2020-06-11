<?php

namespace Topvisor\Uncaptcha;

class GeeTestProxyless extends Uncaptcha{

	protected $websiteUrl;
	protected $websiteKey;
	protected $websiteChallenge;
	protected $geetestApiServerSubdomain;

	function genCreateTaskPost(array $post = []): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'geetest',
					'pageurl' => $this->websiteUrl,
					'gt' => $this->websiteKey,
					'challenge' => $this->websiteChallenge,
					'api_server' => $this->geetestApiServerSubdomain
				];

				foreach($this->data as $name => $value) $post["data[$name]"] = $value;

				break;

			case 2:
				$post = [
					'type' => 'GeeTestTaskProxyless',
					'websiteURL' => $this->websiteUrl,
					'gt' => $this->websiteKey,
					'challenge' => $this->websiteChallenge,
					'geetestApiServerSubdomain' => $this->geetestApiServerSubdomain
				];

				if(get_class($this) == 'GeeTest') $post['type'] = 'GeeTestTask';

				break;
		}

		return parent::genCreateTaskPost($post);
	}

	function setWebsiteURL(string $websiteUrl): void{
		$this->websiteUrl = $websiteUrl;
	}

	function setGTKey(string $websiteKey): void{
		$this->websiteKey = $websiteKey;
	}

	function setChallenge(string $websiteChallenge): void{
		$this->websiteChallenge = $websiteChallenge;
	}

	function setAPISubdomain(string $geetestApiServerSubdomain): void{
		$this->geetestApiServerSubdomain = $geetestApiServerSubdomain;
	}

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

}

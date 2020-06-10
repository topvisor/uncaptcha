<?php

namespace Topvisor\Uncaptcha;

class ReCaptchaV2Proxyless extends Uncaptcha{

	protected $websiteUrl;
	protected $websiteKey;
	protected $websiteS;
	protected $isInvisible = false;

	function genTaskPost(array $post = []): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'userrecaptcha',
					'pageurl' => $this->websiteUrl,
					'googlekey' => $this->websiteKey,
					'datas' => $this->websiteS,
					'invisible' => (int)$this->invisible
				];

				break;

			case 2:
				$post = [
					'type' => 'NoCaptchaTaskProxyless',
					'websiteURL' => $this->websiteUrl,
					'websiteKey' => $this->websiteKey,
//					'websiteSToken' => $this->websiteS,
					'recaptchaDataSValue' => $this->websiteS,
					'isInvisible' => $this->invisible
				];

				if(get_class($this) == 'ReCaptchaV2') $post['type'] = 'NoCaptchaTask';

				break;
		}

		return parent::genTaskPost($post);
	}

	function setWebsiteURL(string $websiteUrl): void{
		$this->websiteUrl = $websiteUrl;
	}

	function setWebsiteKey(string $websiteKey): void{
		$this->websiteKey = $websiteKey;
	}

	// alias setWebsiteSToken
	// alias setRecaptchaDataSValue
	function setWebsiteS(string $websiteS): void{
		$this->websiteS = $websiteS;
	}

	function setIsInviible(bool $isInviible): void{
		$this->isInviible = $isInviible;
	}

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

	function reportBad(){
		if($this->v == 1) return parent::reportBad();

		if($this->v == 2){
			if(!$this->taskid) throw new Exception('Task does not exists');

			return $this->call('reportIncorrectRecaptcha', ['taskId' => $this->taskid]);
		}
	}

}

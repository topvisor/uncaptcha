<?php

namespace Topvisor\Uncaptcha;

class HCaptchaProxyless extends Uncaptcha{

	protected $websiteUrl;
	protected $websiteKey;

	function genTaskPost(): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'hcaptcha',
					'pageurl' => $this->websiteUrl,
					'sitekey' => $this->websiteKey
				];

				break;

			case 2:
				$post = [
					'type' => 'HCaptchaTaskProxyless',
					'websiteURL' => $this->websiteUrl,
					'websiteKey' => $this->websiteKey
				];

				if(get_class($this) == 'HCaptcha') $post['type'] = 'HCaptchaTask';

				break;
		}

		return parent::genTaskPost($post);
	}

	function setWebsiteURL($websiteUrl): void{
		$this->websiteUrl = $websiteUrl;
	}

	function setWebsiteKey($websiteKey): void{
		$this->websiteKey = $websiteKey;
	}

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

}

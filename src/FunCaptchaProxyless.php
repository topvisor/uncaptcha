<?php

namespace Topvisor\Uncaptcha;

class FunCaptchaProxyless extends Uncaptcha{

	protected $websiteUrl;
	protected $websitePublicKey;
	protected $websiteSUrl;
	protected $jsSubdomain;
	protected $data = [];

	function genCreateTaskPost(array $post = []): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'funcaptcha',
					'pageurl' => $this->websiteUrl,
					'publickey' => $this->websitePublicKey,
					'surl' => $this->websiteSUrl
				];

				foreach($this->data as $name => $value) $post["data[$name]"] = $value;

				break;

			case 2:
				$post = [
					'type' => 'FunCaptchaTaskProxyless',
					'websiteURL' => $this->websiteUrl,
					'websitePublicKey' => $this->websitePublicKey,
					'funcaptchaApiJSSubdomain' => $this->jsSubdomain,
					'data' => json_encode($this->data)
				];

				if(get_class($this) == 'FunCaptcha') $post['type'] = 'FunCaptchaTask';

				break;
		}

		return parent::genCreateTaskPost($post);
	}

	function setWebsiteURL(string $websiteUrl): void{
		$this->websiteUrl = $websiteUrl;
	}

	function setWebsitePublicKey(string $websitePublicKey): void{
		$this->websitePublicKey = $websitePublicKey;
	}

	function setWebsiteSUrl(string $websiteSUrl): void{
		$this->websiteSUrl = $websiteSUrl;
	}

	function setJSSubdomain(string $jsSubdomain): void{
		$this->jsSubdomain = $jsSubdomain;
	}

	function setData(string $name, string $value): void{
		$this->data[$name] = $value;
	}

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

}

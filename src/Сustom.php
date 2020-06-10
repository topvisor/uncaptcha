<?php

namespace Topvisor\Uncaptcha;

class Custom extends FunCaptchaProxyless{

	use ProxySupportTrait;

	function setUserAgent(string $userAgent): void{
		$this->userAgent = $userAgent;
	}

	function setCookies(string $cookies): void{
		$this->cookies = $cookies;
	}

}

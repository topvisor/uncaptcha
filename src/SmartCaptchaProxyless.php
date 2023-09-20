<?php

namespace Topvisor\Uncaptcha;

class SmartCaptchaProxyless extends Uncaptcha
{
	protected $websiteUrl;
	protected $websiteKey;
	protected $body;
	protected $userAgent;

	function genCreateTaskPost(array $post = []): array
	{
		return [
			'type' => 'YandexSmartCaptchaTaskProxyless',
			'websiteURL' => $this->websiteUrl,
			'websiteKey' => $this->websiteKey,
			'htmlPageBase64' => base64_encode($this->body),
			'userAgent' => $this->userAgent,
		];
	}

	function setWebsiteUrl(string $websiteUrl): void
	{
		$this->websiteUrl = $websiteUrl;
	}

	function setWebsiteKey(string $websiteKey): void
	{
		$this->websiteKey = $websiteKey;
	}

	function setBody(string $body): void
	{
		$this->body = $body;
	}

	function setUserAgent(string $userAgent): void
	{
		$this->userAgent = $userAgent;
	}
}

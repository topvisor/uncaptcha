<?php

namespace Topvisor\Uncaptcha;

class GeeTest extends GeeTestProxyless{

	use ProxySupportTrait;

	function genTaskPost(): array{
		$post = [
			'type' => 'GeeTestTask',
			'websiteURL' => $this->websiteUrl,
			'geetestApiServerSubdomain' => $this->geetestApiServerSubdomain,
			'gt' => $this->websiteKey,
			'challenge' => $this->websiteChallenge
		];

		return parent::genTaskPost($post);
	}

}

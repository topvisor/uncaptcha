<?php

namespace Topvisor\Uncaptcha;

class GeeTest extends GeeTestProxyless{

	use ProxySupportTrait;

	function genCreateTaskPost(array $post = []): array{
		$post = [
			'type' => 'GeeTestTask',
			'websiteURL' => $this->websiteUrl,
			'geetestApiServerSubdomain' => $this->geetestApiServerSubdomain,
			'gt' => $this->websiteKey,
			'challenge' => $this->websiteChallenge
		];

		return parent::genCreateTaskPost($post);
	}

}

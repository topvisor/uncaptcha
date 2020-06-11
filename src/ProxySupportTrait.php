<?php

namespace Topvisor\Uncaptcha;

trait ProxySupportTrait{

	function setProxyType(string $type): void{
		if(!in_array($type, ['http', 'https', 'socks4', 'socks5'])) throw new \Exception("Incorrect proxy type: $type");

		$this->proxy['type'] = $type;
	}

	function setProxyAddress(string $server): void{
		$this->proxy['server'] = $server;
	}

	function setProxyPort(int $port): void{
		$this->proxy['port'] = $port;
	}

	function setProxyLogin(string $login): void{
		$this->proxy['login'] = $login;
	}

	function setProxyPassword(string $password): void{
		$this->proxy['password'] = $password;
	}

}

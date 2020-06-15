<?php

namespace Topvisor\Uncaptcha;

trait UncaptchaDebug{

	private $debugLevel = 0; // 0 - without log, 1 - short log, 2 - detailed log
	private $debugFormat = NULL; // 0 - text, 1 - html
	private $debugLabel = '';
	private $debugLog = [];

	function setDebugLevel(int $debugLevel): void{
		$this->debugLevel = $debugLevel;
	}

	function setDebugFormat(int $debugFormat): void{
		$this->debugFormat = $debugFormat;
	}

	function setDebugLabel(string $debugLabel): void{
		$this->debugLabel = $debugLabel;
	}

	function clearDebugLog(): array{
		return $this->debugLog = [];
	}

	function getDebugLog(): array{
		return $this->debugLog;
	}

	private function genDebugLabel(): string{
		$label = get_class($this);
		$label = explode('\\', $label);
		$label = $label[count($label) - 1];
		$label = preg_replace('~[a-z]~', '', $label);

		if($this->debugLabel) $label = $this->debugLabel.'_'.$label;
		if($this->proxy['server']) $label .= ' (P)';

		return $label;
	}

	function debugLog(string $message, int $debugLevel = 1): void{
		$message = str_replace($this->clientKey, '**********', $message);
		if($this->proxy['password']) $message = str_replace($this->proxy['password'], '**********', $message);

		if($this->debugLevel < $debugLevel) return;

		if($this->debugFormat == 0){
			$message = preg_replace('~<br\s?/?>~', "\n", $message);
			$message = strip_tags($message);
		}

		$this->debugLog[] = $message;

		if($this->debugFormat == 0){
			if(strpos($message, "\n") !== false) $message = "<pre>$message</pre>";
		}

		echo "<pre>$message \n</pre>";
	}

}

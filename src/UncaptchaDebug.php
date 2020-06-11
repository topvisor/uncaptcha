<?php

namespace Topvisor\Uncaptcha;

trait UncaptchaDebug{

	private $debugLevel = 0; // 0 - without log, 1 - short log, 2 - full log
	private $debugLabel = '';
	private $debugLog = [];

	function setDebugLevel(int $debugLevel): void{
		$this->debugLevel = $debugLevel;
	}

	function setDebugLabel(string $debugLabel): void{
		$this->debugLabel = $debugLabel;
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

		$message .= "\n";
		$message = preg_replace('~<br\s?/?>~', "\n", $message);

		if($this->isCLI){
			$message = strip_tags($message);
		}else{
			$message = "<pre>$message</pre>";
		}

		echo $message;

		$this->debugLog[] = $message;
	}

	function getDebugLog(): array{
		return $this->debugLog;
	}

}

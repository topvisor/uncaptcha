<?php

namespace Topvisor\Uncaptcha;

trait UncaptchaDebug{

	private $debugEnabled = false;
	private $debugIsCLI = NULL;
	private $debugMessages = [];

	function setDebugEnabled(bool $debugEnabled = true): void{
		$this->debugIsCLI = function_exists('cli_set_process_title');

		$this->debugEnabled = $debugEnabled;
	}

	function debugMessage(string $message): void{
		if(!$this->debugEnabled) return;

		if($this->debugIsCLI){
			$message = preg_replace('~<br\s?/?>~', "\n", $message);
			$message = strip_tags($message);
		}

		$this->messages[] = $message;
	}

	function getDebugMessages(): array{
		return $this->messages;
	}

}

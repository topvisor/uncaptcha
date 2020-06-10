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

		$message .= "\n";
		$message = preg_replace('~<br\s?/?>~', "\n", $message);

		if($this->debugIsCLI){
			$message = strip_tags($message);
		}else{
			$message = "<pre>$message</pre>";
		}

		echo $message;

		$this->messages[] = $message;
	}

	function getDebugMessages(): array{
		return $this->messages;
	}

}

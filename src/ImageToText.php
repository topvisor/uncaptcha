<?php

namespace Topvisor\Uncaptcha;

class ImageToText extends Uncaptcha{

	private $body;
	private $phrase = false;
	private $case = false;
	private $numeric = 0;
	private $math = false;
	private $minLength = 0;
	private $maxLength = 0;
	private $language = 0;

	function genCreateTaskPost(array $post = []): array{
		switch($this->v){
			case 1:
				$post = [
					'method' => 'base64',
					'body' => $this->body,
					'phrase' => (int)$this->phrase,
					'regsense' => (int)$this->case,
					'numeric' => $this->numeric,
					'calc' => (int)$this->math,
					'min_len' => $this->minLength,
					'max_len' => $this->maxLength,
					'language' => $this->language
				];

				break;

			case 2:
				$post = [
					'type' => 'ImageToTextTask',
					'body' => $this->body,
					'phrase' => $this->phrase,
					'case' => $this->case,
					'numeric' => $this->numeric,
					'math' => $this->math,
					'minLength' => $this->minLength,
					'maxLength' => $this->maxLength
				];

				break;
		}

		$this->debugLog('<img src="data:image/jpeg;base64,'.$this->body.'">');

		return parent::genCreateTaskPost($post);
	}

	function setBody(string $base64Data): void{
		$this->body = $base64Data;
	}

	function setBodyFromFile(string $fileName): bool{
		$this->body = base64_encode(file_get_contents($fileName));

		if(strlen($this->body) < 100){
			$this->setErrorMessage("File $fileName too small");

			return false;
		}

		return (bool)$this->body;
	}

	function setPhraseFlag(bool $phrase): void{
		$this->phrase = $phrase;
	}

	function setCaseFlag(bool $case): void{
		$this->case = $case;
	}

	function setNumericFlag(int $numeric): void{
		$this->numeric = $numeric;
	}

	function setMathFlag(bool $value): void{
		$this->math = $value;
	}

	function setMinLengthFlag(int $minLength): void{
		$this->minLength = $minLength;
	}

	function setMaxLengthFlag(int $maxLength): void{
		$this->maxLength = $maxLength;
	}

	function setLanguage(int $language): void{
		$this->language = $language;
	}

	function reportBad(): ?bool{
		if(
			$this->v == 1 or
			$this->host != 'api.anti-captcha.com'
		) return parent::reportBad();

		if($this->v == 2){
			if(!$this->taskId) return $this->setErrorMessage('Task does not exists');

			$this->debugLog("reportBad: $this->taskId");

			return (bool)$this->call('reportIncorrectImageCaptcha', ['taskId' => $this->taskId]);
		}
	}

}

<?php

header('content-type: application/json');
header('powered-by: slmatthew');

class API {
	public const API_ERROR_UNKNOWN_METHOD = 1;
	public const API_ERROR_MISSING_PARAMS = 2;
	public const API_ERROR_ACCESS_DENIED = 3;

	private $methods = [];
	private $secureKey = '';

	public function setSecureKey(string $key) {
		$this->secureKey = $key;
	}

	public function addMethod(string $name, array $params, callable $handler, string $version = '1.0') {
		$this->methods[$version][$name] = ['params' => $params, 'handler' => $handler];
	}

	public function checkMethod(string $name, string $version) {
		if($this->secureKey && (!isset($_REQUEST['key']) || $_REQUEST['key'] != $this->secureKey)) $this->wrapError(self::API_ERROR_ACCESS_DENIED, 'access denied');

		if(isset($this->methods[$version][$name])) {
			$method_params = $this->methods[$version][$name]['params'];
			if($this->checkRequiredParams($method_params, true) === true) {
				return $this->runMethod($name, $version);
			}
		} elseif(isset($this->methods['default'][$name])) {
			$method_params = $this->methods['default'][$name]['params'];
			if($this->checkRequiredParams($method_params, true) === true) {
				return $this->runMethod($name, 'default');
			}
		} else $this->wrapError(self::API_ERROR_UNKNOWN_METHOD, 'unknown method');
	}

	public function runMethod(string $name, string $version) { return $this->methods[$version][$name]['handler'](); }

	public function getMethod() { return isset($_REQUEST['method']) ? $_REQUEST['method'] : 'default'; }
	public function getVersion() { return isset($_REQUEST['v']) ? $_REQUEST['v'] : '1.0'; }

	public function checkRequiredParams(array $params, bool $wrap_error = true) {
		foreach($params as $i => $key) {
			if(isset($_REQUEST[$key])) continue;

			if($wrap_error) {
				$this->wrapError(self::API_ERROR_MISSING_PARAMS, $key.' is a required parameter');
			} else {
				return $key;
			}
		}

		return true;
	}

	public function wrapResult(array $data) {
		echo json_encode(['ok' => true, 'result' => $data], JSON_UNESCAPED_UNICODE);

		exit();
	}

	public function wrapError(int $code, string $message, array $params = []) {
		$params['code'] = $code;
		$params['msg'] = $message;

		echo json_encode(['ok' => false, 'error' => $params], JSON_UNESCAPED_UNICODE);

		exit();
	}

	public function onData() { $this->checkMethod($this->getMethod(), $this->getVersion()); }
}

class APIDocs {
	private $docs = [];
	private $isMarkdown = false;

	public function markdown(bool $status) {
		$this->isMarkdown = $status;
	}

	public function genDocs() {
		//$path = __DIR__.'/apidocs/';
		if($this->isMarkdown) {
			foreach($this->docs as $version => $methods) {
				$vDocs = [];
				$vDocs[] = "# Документация по версии {$version}";
				foreach($methods as $method => $mDocs) {
					$mDocs['desc'] = str_replace(['{b}', '{/b}', '{i}', '{/i}'], ['**', '**', '*', '*'], $mDocs['desc']);

					$vDocs[] = "## {$method}";
					$vDocs[] = $mDocs['desc'];

					if(!empty($mDocs['params'])) {
						$vDocs[] = "\n| Параметр | Тип | Описание |";
						$vDocs[] = "|----------|-----|----------|";
						foreach($mDocs['params'] as $_ => $pDoc) {
							$pDoc['desc'] = str_replace(['{b}', '{/b}', '{i}', '{/i}'], ['**', '**', '*', '*'], $pDoc['desc']);
							$vDocs[] = "| {$pDoc['name']} | {$pDoc['type']} | {$pDoc['desc']} |";
						}
					}

					$vDocs[] = "\n```{$mDocs['example']['lang']}";
					$vDocs[] = $mDocs['example']['code'];
					$vDocs[] = "```";
				}

				file_put_contents("docs{$version}.md", implode("\n", $vDocs));
			}
		} else {
			foreach($this->docs as $version => $methods) {
				$vDocs = [];

				$vDocs[] = "<html>";
				$vDocs[] = "<head>";
				$vDocs[] = "\t\t<title>Документация</title>";
				$vDocs[] = "</head>";
				$vDocs[] = "<body>";

				$vDocs[] = "\t<h1>Документация по версии {$version}</h1>";

				foreach($methods as $method => $mDocs) {
					$mDocs['desc'] = str_replace(['{b}', '{/b}', '{i}', '{/i}'], ['<b>', '</b>', '<i>', '</i>'], $mDocs['desc']);

					$vDocs[] = "\t<h2>{$method}</h2>";
					$vDocs[] = "\t<p>{$mDocs['desc']}</p>";

					if(!empty($mDocs['params'])) {
						$vDocs[] = "\t<table>";
						$vDocs[] = "\t\t<thead>";
						$vDocs[] = "\t\t\t<tr>";
						$vDocs[] = "\t\t\t\t<th>Параметр</th>";
						$vDocs[] = "\t\t\t\t<th>Тип</th>";
						$vDocs[] = "\t\t\t\t<th>Описание</th>";
						$vDocs[] = "\t\t\t</tr>";
						$vDocs[] = "\t\t</thead>";
						$vDocs[] = "\t\t<tbody>";
						foreach($mDocs['params'] as $_ => $pDoc) {
							$pDoc['desc'] = str_replace(['{b}', '{/b}', '{i}', '{/i}'], ['<b>', '</b>', '<i>', '</i>'], $pDoc['desc']);

							$vDocs[] = "\t\t\t<tr>";
							$vDocs[] = "\t\t\t\t<td>{$pDoc['name']}</td>";
							$vDocs[] = "\t\t\t\t<td>{$pDoc['type']}</td>";
							$vDocs[] = "\t\t\t\t<td>{$pDoc['desc']}</td>";
							$vDocs[] = "\t\t\t</tr>";
						}
						$vDocs[] = "\t\t</tbody>";
						$vDocs[] = "\t</table>";
					}

					$vDocs[] = "\t<pre>";
					$vDocs[] = $mDocs['example']['code'];
					$vDocs[] = "\t</pre>";
				}

				$vDocs[] = "</body>";

				file_put_contents("docs{$version}.html", implode("\n", $vDocs));
			}
		}
	}

	public function addDocs(string $name, string $description = '', array $paramsDesc = [], array $example = [], string $version = '1.0') {
		$this->docs[$version][$name] = [
			'desc' => $description,
			'params' => $paramsDesc,
			'example' => $example
		];
	}
}

?>
<?php namespace Model\Email;

use Model\Core\Module_Config;

class Config extends Module_Config
{
	/**
	 * @throws \Model\Core\Exception
	 */
	protected function assetsList()
	{
		$this->addAsset('config', 'config.php', function () {
			return "<?php
\$config = [
	'from_name' => APP_NAME,
	'from_mail' => '',
	'smtp' => false,
	'port' => 25,
	'header' => '<div style=\"width: 800px; margin: auto\"><p style=\"text-align: center\"><img src=\"https://" . $_SERVER['HTTP_HOST'] . PATH . "app/assets/img/logo.png\" alt=\"\" /></p>',
	'footer' => '</div>',
	'debug' => false,
	'username' => null,
	'password' => null,
	'encryption' => null,
	'charset' => 'UTF-8',
];
";
		});
	}

	public function getConfigData(): ?array
	{
		return [];
	}
}

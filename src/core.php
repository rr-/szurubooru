<?php
$startTime = microtime(true);
define('DS', DIRECTORY_SEPARATOR);

final class Core
{
	private static $context;
	private static $config;
	private static $router;
	private static $database;
	private static $rootDir;

	static function init()
	{
		self::$rootDir = __DIR__ . DS . '..' . DS;
		chdir(self::$rootDir);
		date_default_timezone_set('UTC');
		setlocale(LC_CTYPE, 'en_US.UTF-8');
		ini_set('memory_limit', '128M');

		require_once self::$rootDir . 'lib' . DS . 'TextCaseConverter' . DS . 'TextCaseConverter.php';
		require_once self::$rootDir . 'lib' . DS . 'php-markdown' . DS . 'Michelf' . DS . 'Markdown.php';
		require_once self::$rootDir . 'lib' . DS . 'php-markdown' . DS . 'Michelf' . DS . 'MarkdownExtra.php';
		require_once self::$rootDir . 'lib' . DS . 'chibi-core' . DS . 'include.php';
		require_once self::$rootDir . 'lib' . DS . 'chibi-sql' . DS . 'include.php';
		\Chibi\AutoLoader::registerFilesystem(__DIR__);

		self::$router = new Router();
	}

	static function getRouter()
	{
		return self::$router;
	}

	static function getConfig()
	{
		return self::$config;
	}

	static function getContext()
	{
		return self::$context;
	}

	static function getDatabase()
	{
		return self::$database;
	}

	static function prepareConfig($testEnvironment)
	{
		$configPaths = [];
		if (!$testEnvironment)
		{
			$configPaths []= self::$rootDir . DS . 'data' . DS . 'config.ini';
			$configPaths []= self::$rootDir . DS . 'data' . DS . 'local.ini';
		}
		else
		{
			$configPaths []= self::$rootDir . DS . 'tests' . DS . 'config.ini';
		}

		self::$config = new \Chibi\Config();
		foreach ($configPaths as $path)
			if (file_exists($path))
				self::$config->loadIni($path);
		self::$config->rootDir = self::$rootDir;
	}

	static function prepareContext()
	{
		global $startTime;
		self::$context = new StdClass;
		self::$context->startTime = $startTime;
	}

	static function prepareDatabase()
	{
		$config = self::getConfig();
		self::$database = new \Chibi\Db\Database(
			$config->main->dbDriver,
			TextHelper::absolutePath($config->main->dbLocation),
			isset($config->main->dbUser) ? $config->main->dbUser : null,
			isset($config->main->dbPass) ? $config->main->dbPass : null);
		\Chibi\Sql\Config::setDriver(self::$database->getDriver());
	}

	static function prepareEnvironment()
	{
		self::prepareContext();

		$config = self::getConfig();

		TransferHelper::createDirectory($config->main->filesPath);
		TransferHelper::createDirectory($config->main->thumbnailsPath);
		TransferHelper::createDirectory($config->main->avatarsPath);

		//extension sanity checks
		$requiredExtensions = ['pdo', 'pdo_' . $config->main->dbDriver, 'openssl', 'fileinfo'];
		foreach ($requiredExtensions as $ext)
			if (!extension_loaded($ext))
				die('PHP extension "' . $ext . '" must be enabled to continue.' . PHP_EOL);

		Access::init();
		Logger::init();
		Mailer::init();
		PropertyModel::init();
	}

	static function getDbVersion()
	{
		try
		{
			$dbVersion = PropertyModel::get(PropertyModel::DbVersion);
		}
		catch (Exception $e)
		{
			return [null, null];
		}
		if (strpos($dbVersion, '.') !== false)
		{
			list ($dbVersionMajor, $dbVersionMinor) = explode('.', $dbVersion);
		}
		elseif ($dbVersion)
		{
			$dbVersionMajor = $dbVersion;
			$dbVersionMinor = null;
		}
		else
		{
			$dbVersionMajor = 0;
			$dbVersionMinor = 0;
		}
		return [$dbVersionMajor, $dbVersionMinor];
	}

	static function upgradeDatabase()
	{
		$config = self::getConfig();
		$upgradesPath = TextHelper::absolutePath($config->rootDir
			. DS . 'src' . DS . 'Upgrades' . DS . $config->main->dbDriver);

		$upgrades = glob($upgradesPath . DS . '*.sql');
		natcasesort($upgrades);

		foreach ($upgrades as $upgradePath)
		{
			preg_match('/(\d+)\.sql/', $upgradePath, $matches);
			$upgradeVersionMajor = intval($matches[1]);

			list ($dbVersionMajor, $dbVersionMinor) = self::getDbVersion();

			if (($upgradeVersionMajor > $dbVersionMajor)
				or ($upgradeVersionMajor == $dbVersionMajor and $dbVersionMinor !== null))
			{
				printf('%s: executing' . PHP_EOL, $upgradePath);
				$upgradeSql = file_get_contents($upgradePath);
				$upgradeSql = preg_replace('/^[ \t]+(.*);/m', '\0--', $upgradeSql);
				$queries = preg_split('/;\s*[\r\n]+/s', $upgradeSql);
				$queries = array_map('trim', $queries);
				$queries = array_filter($queries);
				$upgradeVersionMinor = 0;
				foreach ($queries as $query)
				{
					$query = preg_replace('/\s*--(.*?)$/m', '', $query);
					++ $upgradeVersionMinor;
					if ($upgradeVersionMinor > $dbVersionMinor)
					{
						try
						{
							Core::getDatabase()->executeUnprepared(new \Chibi\Sql\RawStatement($query));
						}
						catch (Exception $e)
						{
							echo $e . PHP_EOL;
							echo $query . PHP_EOL;
							die;
						}
						PropertyModel::set(PropertyModel::DbVersion, $upgradeVersionMajor . '.' . $upgradeVersionMinor);
					}
				}
				PropertyModel::set(PropertyModel::DbVersion, $upgradeVersionMajor);
			}
			else
			{
				printf('%s: no need to execute' . PHP_EOL, $upgradePath);
			}
		}

		list ($dbVersionMajor, $dbVersionMinor) = self::getDbVersion();
		printf('Database version: %d.%d' . PHP_EOL, $dbVersionMajor, $dbVersionMinor);
	}
}

Core::init();
Core::prepareConfig(false);
Core::prepareDatabase();
Core::prepareEnvironment(false);

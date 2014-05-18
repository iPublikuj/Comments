<?php
/**
 * CommentsExtension.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	DI
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments\DI;

use Nette;
use Nette\DI\Compiler;
use Nette\DI\Configurator;
use Nette\PhpGenerator as Code;
use Nette\Utils;

use Kdyby\Doctrine\DI\IDatabaseTypeProvider;
use Kdyby\Translation\DI\ITranslationProvider;

if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']);
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

class CommentsExtension extends Nette\DI\CompilerExtension implements ITranslationProvider, IDatabaseTypeProvider
{
	/**
	 * @var array
	 */
	private $defaults = array(
		'antispam'	=> array(
			'enable'	=> FALSE,
			'services'	=> array(),
		),
		'social'	=> array(		// Social networks settings
			'facebook'	=> FALSE,	// Facebook via Kdyby\Facebook
			'twitter'	=> FALSE,	// Not implemented yet
			'google'	=> FALSE,	// Facebook via Kdyby\Google
			'github'	=> FALSE,	// Facebook via Kdyby\Github
		),
		'displaying'	=> array(
			'avatar'	=> TRUE,	// Show or hide avatar
			'ordering'	=> 'DESC',	// Set comments ordering (ASC or DESC)
			'maxDepth'	=> 5		// Maximum comments depth
		),
		'posting'		=> array(
			'enabled'				=> TRUE,	// Enable or disable adding posts
			'registeredOnly'		=> FALSE,	// Enable posting comments only for registered users (system or social if enabled)
			'timeBetweenUserPosts'	=> 120,		// Define time between two posts of one author in sec.
			'approving'				=> 0,		// 0 - manual, 1 - auto, 2 - after first manual automatically
			'blacklist'				=> '',		// String of blacklisted words, names, IPs, etc.
			'require'				=> array(
				'name'	=> TRUE,	// Set if author name is required
				'email'	=> TRUE,	// Set if author email is required
			),
			'notification'			=> array(
				'moderator'		=> FALSE,		// Notify moderators when new post is added
				'moderators'	=> array(),		// List of moderators emails
				'watchers'		=> FALSE,		// Enable notifications emails for watchers
			)
		)
	);

	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		Utils\Validators::assert($config['posting']['blacklist'], 'string', 'Blacklist');
		if (!in_array($config['displaying']['ordering'], $allowed = array('ASC', 'DESC'))) {
			throw new Utils\AssertionException("Key displaying/ordering is expected to be one of [" . implode(', ', $allowed) . "], but '" . $config['displaying']['ordering'] . "' was given.");
		}
		if (!in_array($config['posting']['approving'], $allowed = array(0, 1, 2))) {
			throw new Utils\AssertionException("Key posting/approving is expected to be one of [" . implode(', ', $allowed) . "], but '" . $config['posting']['approving'] . "' was given.");
		}

		// Extension configuration
		$configuration = $builder->addDefinition($this->prefix('config'))
			->setClass('IPub\Comments\Configuration')
			->setArguments(array(
				$config['posting'],
				$config['displaying']
			));

		// Set social networks
		foreach ($config['social'] as $network=>$status) {
			$configuration->addSetup('$service->setSocialNetwork(?, ?)', array($network, $status));
		}

		// Session storage
		$builder->addDefinition($this->prefix('session'))
			->setClass('IPub\Comments\SessionStorage');

		// Data provider
		$builder->addDefinition($this->prefix('dataProvider'))
			->setClass('IPub\Comments\DataProviders\DataProvider');

		// Define extension factory
		$builder->addDefinition($this->prefix('factory'))
			->setClass('IPub\Comments\CommentsFactory')
			->setInject(TRUE);

		// Define components
		$builder->addDefinition($this->prefix('comments'))
			->setClass('IPub\Comments\Components\Comments')
			->setImplement('IPub\Comments\Components\IComments')
			->addTag('cms.components');

		// Define events
		$builder->addDefinition($this->prefix('listeners.authorsListener'))
			->setClass('IPub\Comments\Events\AuthorsListener')
			->addTag('kdyby.subscriber');

		// Register template helpers
		$builder->addDefinition($this->prefix('helpers'))
			->setClass('IPub\Comments\Templating\Helpers');
	}

	/**
	 * @param Code\ClassType $class
	 */
	public function afterCompile(Code\ClassType $class)
	{
		parent::afterCompile($class);

		$initialize = $class->methods['initialize'];
	}

	/**
	 * @param \Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'comments')
	{
		$config->onCompile[] = function (Configurator $config, Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new CommentsExtension());
		};
	}

	/**
	 * Returns array of typeName => typeClass.
	 *
	 * @return array
	 */
	function getDatabaseTypes()
	{
		return array(
			'commentStatus'	=> 'IPub\Comments\Types\CommentStatusType',
			'authorType'	=> 'IPub\Comments\Types\AuthorTypeType',
		);
	}

	/**
	 * Return array of directories, that contain resources for translator.
	 *
	 * @return string[]
	 */
	function getTranslationResources()
	{
		return array(
			__DIR__ . '/../Translations'
		);
	}
}
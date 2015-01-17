<?php
/**
 * AuthorsListener.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Events
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments\Events;

use Nette;
use Nette\Application;
use Nette\Caching;
use Nette\Security;

use Kdyby\Events;

use IPub\Comments\CommentsFactory;
use IPub\Comments\Configuration;
use IPub\Comments\Entities;
use IPub\Comments\Users;
use IPub\Comments\SessionStorage;

use Kdyby\Facebook;
use Kdyby\Google;
use Kdyby\Github;

use IPub\Gravatar\Gravatar;

class AuthorsListener extends Nette\Object implements Events\Subscriber
{
	/**
	 * @var CommentsFactory
	 */
	private $commentsFactory;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @var SessionStorage
	 */
	private $session;

	/**
	 * @var Caching\Cache
	 */
	protected $cache;

	/**
	 * @var Security\User
	 */
	protected $user;

	/**
	 * @var Facebook\Facebook
	 */
	protected $facebook;

	/**
	 * @var Google\Google
	 */
	protected $google;

	/**
	 * @var Github\Client
	 */
	protected $github;

	/**
	 * @var Gravatar
	 */
	protected $gravatar;

	/**
	 * Register events
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			//'Nette\\Application\\Application::onPresenter',
			'Nette\\Application\\Application::onResponse'
		);
	}

	/**
	 * @param Security\User $user
	 * @param CommentsFactory $commentsFactory
	 * @param Configuration $configuration
	 * @param SessionStorage $sessionStorage
	 * @param Caching\IStorage $cacheStorage
	 * @param Facebook\Facebook $facebook
	 * @param Google\Google $google
	 * @param Github\Client $github
	 * @param Gravatar $gravatar
	 */
	public function __construct(
		Security\User $user,
		CommentsFactory $commentsFactory,
		Configuration $configuration,
		SessionStorage $sessionStorage,
		Caching\IStorage $cacheStorage,
		Facebook\Facebook $facebook = NULL,
		Google\Google $google = NULL,
		Github\Client $github = NULL,
		Gravatar $gravatar = NULL
	) {
		$this->user = $user;

		// Extension services
		$this->commentsFactory	= $commentsFactory;
		$this->configuration	= $configuration;
		$this->session			= $sessionStorage;
		$this->cache			= new Caching\Cache($cacheStorage, 'IPub.Comments');

		// Social networks
		$this->facebook	= $facebook;
		$this->google	= $google;
		$this->github	= $github;

		// Additional extensions
		$this->gravatar	= $gravatar;
	}

	/**
	 * @param Application\Application $application
	 * @param Application\Request $request
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function onPresenter(Application\Application $application, Application\UI\Presenter $presenter)
	{
		return;
		// Get login (app users always win)
		$login = $this->session->login;

		// Check if user is logged in system
		if ($this->user && $this->user->isLoggedIn()) {
			try {
				// Try to get name from identity
				$name = $this->user->getIdentity()->getName();

			} catch (\Exception $e) {
				throw new Nette\InvalidArgumentException('In user identity is missing method getName() which should return user full name.');
			}

			try {
				// Try to get user email
				$email = $this->user->getIdentity()->getEmail();

			} catch (\Exception $e) {
				// Email will not be available
				$email = NULL;
			}

			try {
				// Try to get user website
				$website = $this->user->getIdentity()->getWebsite();

			} catch (\Exception $e) {
				// Website will not be available
				$website = NULL;
			}

			// Comment author
			$author = new Users\System($name, $email, $website, $this->user->getId());

		// Or check if user is logged in via facebook
		} else if (
			$this->facebook &&
			$login == Entities\IAuthor::USER_TYPE_FACEBOOK &&
			isset($this->session->accessToken) && $this->session->accessToken &&
			$this->facebook->setAccessToken($this->session->accessToken)
		) {
			try {
				// Get user details
				$me = $this->facebook->api('/me');

				// Comment author
				$author = new Users\Facebook($me->name, $me->email, NULL, $me->id);
				$author
					->setAccessToken($this->facebook->getAccessToken());

			} catch (Facebook\FacebookApiException $e) {
				// Github oauth was not successful
				$author = new Users\Guest();
			}

		// Or check if user is logged in via twitter
		} else if ($login == Entities\IAuthor::USER_TYPE_TWITTER
			&& ($connection = TwitterHelper::client($this->container->configuration->getVariable('twitter_consumer_key'), $this->container->configuration->getVariable('twitter_consumer_secret')))
			&& ($content = $connection->get('account/verify_credentials'))
			&& isset($content->screen_name)
			&& isset($content->id)
		) {
			// Comment author
			$author = new Users\Twitter($content->screen_name, NULL, NULL, $content->id);

		// Or check if user is logged in via google
		} else if (
			$this->google &&
			$login == Entities\IAuthor::USER_TYPE_GOOGLE &&
			isset($this->session->accessToken) && $this->session->accessToken &&
			$this->google->setAccessToken($this->session->accessToken)
		) {
			try {
				// Get user details
				$me = $this->google->getProfile();

				// Comment author
				$author = new Users\Google($me->getName(), $me->getEmail(), NULL, $me->getId());
				$author
					->setAccessToken($this->google->getAccessToken());

			} catch (\Google_Exception $e) {
				// Google oauth was not successful
				$author = new Users\Guest();
			}

		// Or check if user is logged in via github
		} else if (
			$this->github &&
			$login == Entities\IAuthor::USER_TYPE_GITHUB &&
			isset($this->session->accessToken) && $this->session->accessToken &&
			$this->github->setAccessToken($this->session->accessToken)
		) {
			try {
				// Get user details
				$me = $this->github->api('/user');

				// Comment author
				$author = new Users\Github($me->name, $me->email, NULL, $me->login);
				$author
					->setAccessToken($this->github->getAccessToken());

			} catch (Github\ApiException $e) {
				// Github oauth was not successful
				$author = new Users\Guest();
			}

		// Use guest comments user
		} else {
			// Comment author
			$author = new Users\Guest($this->session->author, $this->session->email, $this->session->website, $this->session->id);
		}

		$author
			->setCache($this->cache)
			->setGravatar($this->gravatar);

		// Store author to service
		$this->commentsFactory->setAuthor($author);
	}

	/**
	 * @param Application\Application $sender
	 * @param Application\IResponse $response
	 */
	public function onResponse(Application\Application $sender, Application\IResponse $response)
	{
		// Check if active author is set
		if ($this->commentsFactory->getAuthor() && $this->commentsFactory->getAuthor() instanceof Users\IUser) {
			// Store user info to the cookie
			$this->session->login = $this->commentsFactory->getAuthor()->getType();

			// Save active author data to the session
			$this->saveAuthorDetails(
				$this->commentsFactory->getAuthor()->getId(),
				$this->commentsFactory->getAuthor()->getName(),
				$this->commentsFactory->getAuthor()->getEmail(),
				$this->commentsFactory->getAuthor()->getWebsite()
			);
		}
	}

	/**
	 * Save id, name, email, url into session
	 *
	 * @param $id
	 * @param $author
	 * @param $email
	 * @param $url
	 */
	protected function saveAuthorDetails($id, $author, $email, $url)
	{
		// Set cookies
		foreach (compact('id', 'author', 'email', 'url') as $key=>$value) {
			$this->session->{$key} = $value;
		}
	}
}
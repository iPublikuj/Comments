<?php
/**
 * SessionStorage.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	common
 * @since		5.0
 *
 * @date		06.05.14
 */

namespace IPub\Comments;

use Nette;
use Nette\Diagnostics\Debugger;

class SessionStorage extends Nette\Object
{
	/**
	 * @var \Nette\Http\SessionSection
	 */
	protected $session;

	/**
	 * @var array
	 */
	protected static $supportedKeys = array('login', 'id', 'author', 'email', 'website', 'accessToken');

	/**
	 * @param \Nette\Http\Session $session
	 * @param Configuration $config
	 */
	public function __construct(Nette\Http\Session $session, Configuration $config)
	{
		$this->session = $session->getSection('Comments');
	}

	/**
	 * Stores the given ($key, $value) pair, so that future calls to
	 * getPersistentData($key) return $value. This call may be in another request.
	 *
	 * Provides the implementations of the inherited abstract
	 * methods.  The implementation uses PHP sessions to maintain
	 * a store for authorization codes, user ids, CSRF states, and
	 * access tokens.
	 */
	public function set($key, $value)
	{
		if (!in_array($key, self::$supportedKeys)) {
			return;
		}

		$this->session->$key = $value;
	}

	/**
	 * Get the data for $key, persisted by BaseFacebook::setPersistentData()
	 *
	 * @param string $key The key of the data to retrieve
	 * @param mixed $default The default value to return if $key is not found
	 *
	 * @return mixed
	 */
	public function get($key, $default = FALSE)
	{
		if (!in_array($key, self::$supportedKeys)) {
			return FALSE;
		}

		return isset($this->session->$key) ? $this->session->$key : $default;
	}

	/**
	 * Clear the data with $key from the persistent storage
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function clear($key)
	{
		if (!in_array($key, self::$supportedKeys)) {
			return;
		}

		unset($this->session->$key);
	}

	/**
	 * Clear all data from the persistent storage
	 *
	 * @return void
	 */
	public function clearAll()
	{
		$this->session->remove();
	}

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function &__get($name)
	{
		$value = $this->get($name);

		return $value;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		if (!in_array($name, self::$supportedKeys)) {
			return FALSE;
		}

		return isset($this->session->$name);
	}

	/**
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->clear($name);
	}
}
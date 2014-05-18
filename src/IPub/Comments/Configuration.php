<?php
/**
 * Configuration.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	common
 * @since		5.0
 *
 * @date		08.05.14
 */

namespace IPub\Comments;

use Nette\ArrayHash;
use Nette\Diagnostics\Debugger;
use Nette\Http\Url;
use Nette\Object;

class Configuration extends Object
{
	/**
	 * @var array
	 */
	public $posting;

	/**
	 * @var array
	 */
	public $displaying;

	/**
	 * @var ArrayHash
	 */
	protected $socialConnections;

	/**
	 * @param array $posting
	 * @param array $displaying
	 */
	public function __construct(array $posting, array $displaying)
	{
		$this->posting				= $posting instanceof ArrayHash ?: (new ArrayHash())->from($posting);
		$this->displaying			= $displaying instanceof ArrayHash ?: (new ArrayHash())->from($displaying);
		$this->socialConnections	= new ArrayHash();
	}

	/**
	 * @param string $network
	 *
	 * @return $this
	 */
	public function enableSocialNetwork($network)
	{
		$this->socialConnections->{$network} = TRUE;

		return $this;
	}

	/**
	 * @param string $network
	 *
	 * @return $this
	 */
	public function disableSocialNetwork($network)
	{
		$this->socialConnections->{$network} = FALSE;

		return $this;
	}

	/**
	 * @param $network
	 * @param $status
	 *
	 * @return $this
	 */
	public function setSocialNetwork($network, $status)
	{
		$this->socialConnections->{$network} = (bool) $status;

		return $this;
	}

	/**
	 * @param string $network
	 *
	 * @return bool
	 */
	public function isSocialNetworkEnabled($network)
	{
		return $this->socialConnections->{$network} ? TRUE :FALSE;
	}

	/**
	 * @return ArrayHash
	 */
	public function getSocialNetworks()
	{
		return $this->socialConnections;
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
}

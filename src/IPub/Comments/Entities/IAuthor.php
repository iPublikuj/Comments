<?php
/**
 * IAuthor.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Entities
 * @since		5.0
 *
 * @date		10.05.14
 */

namespace IPub\Comments\Entities;

use Nette;

interface IAuthor
{
	/**
	 * Define user types
	 */
	const USER_TYPE_GUEST		= 'guest';
	const USER_TYPE_SYSTEM		= 'system';
	const USER_TYPE_FACEBOOK	= 'facebook';
	const USER_TYPE_TWITTER		= 'twitter';
	const USER_TYPE_GOOGLE		= 'google';
	const USER_TYPE_GITHUB		= 'github';

	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName($name);

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @param string $email
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function setEmail($email);

	/**
	 * @return string
	 */
	public function getEmail();

	/**
	 * @param string $website
	 *
	 * @return $this
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function setWebsite($website);

	/**
	 * @return string
	 */
	public function getWebsite();

	/**
	 * @param string $ipAddress
	 *
	 * @return $this
	 */
	public function setIpAddress($ipAddress);

	/**
	 * @return string
	 */
	public function getIpAddress();
}
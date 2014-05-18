<?php
/**
 * IUser.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Users
 * @since		5.0
 *
 * @date		02.05.14
 */

namespace IPub\Comments\Users;

use Nette;

interface IUser
{
	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @return bool
	 */
	public function isGuest();

	/**
	 * @return bool
	 */
	public function isSiteAdmin();

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function setId($id);

	/**
	 * @return string
	 */
	public function getId();

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
	 * @param int $size
	 *
	 * @return string
	 */
	public function getAvatar($size);
}
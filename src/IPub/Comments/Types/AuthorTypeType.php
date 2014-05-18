<?php
/**
 * AuthorTypeType.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:Comments!
 * @subpackage	Types
 * @since		5.0
 *
 * @date		10.05.14
 */

namespace IPub\Comments\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use Kdyby\Doctrine\Types\Enum;

use IPub\Comments\Entities;

class AuthorTypeType extends Enum
{
	/**
	 * @var string
	 */
	protected $name = 'authorType';

	/**
	 * @var array
	 */
	protected $values = array(
		Entities\IAuthor::USER_TYPE_SYSTEM,
		Entities\IAuthor::USER_TYPE_FACEBOOK,
		Entities\IAuthor::USER_TYPE_TWITTER,
		Entities\IAuthor::USER_TYPE_GOOGLE,
		Entities\IAuthor::USER_TYPE_GITHUB
	);

	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		$values = array_map(function($val) {
			return "'" . $val . "'";
		}, $this->values);

		return "ENUM(" . implode(", ", $values) . ") COMMENT '(DC2Type:" . $this->name . ")'";
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
}
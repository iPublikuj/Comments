<?php
/**
 * CommentStatusType.php
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

class CommentStatusType extends Enum
{
	/**
	 * @var string
	 */
	protected $name = 'commentStatus';

	/**
	 * @var array
	 */
	protected $values = array(
		Entities\IComment::STATE_UNAPPROVED,
		Entities\IComment::STATE_APPROVED,
		Entities\IComment::STATE_DECLINED,
		Entities\IComment::STATE_SPAM
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
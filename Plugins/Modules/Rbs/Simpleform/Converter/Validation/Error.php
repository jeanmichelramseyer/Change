<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Converter\Validation;

/**
 * @name \Rbs\Simpleform\Converter\Validation\Error
 */
class Error
{
	/**
	 * @var string[]
	 */
	protected $messages;

	/**
	 * @var \Rbs\Simpleform\Field\FieldInterface
	 */
	protected $field;

	/**
	 * @param string[] $messages
	 * @param \Rbs\Simpleform\Field\FieldInterface $field
	 */
	public function __construct(array $messages = array(), \Rbs\Simpleform\Field\FieldInterface $field = null)
	{
		$this->messages = $messages;
		$this->field = $field;
	}

	/**
	 * @param string[] $messages
	 * @return $this
	 */
	public function setMessages($messages)
	{
		$this->messages = $messages;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * @param \Rbs\Simpleform\Field\FieldInterface $field
	 * @return $this
	 */
	public function setField($field)
	{
		$this->field = $field;
		return $this;
	}

	/**
	 * @return \Rbs\Simpleform\Field\FieldInterface
	 */
	public function getField()
	{
		return $this->field;
	}
}
<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Events;

use Change\User\UserInterface;
use Rbs\User\Documents\User;

/**
* @name \Rbs\User\Events\AuthenticatedUser
*/
class AuthenticatedUser implements UserInterface
{
	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var AuthenticatedGroup[]
	 */
	protected $groups;

	/**
	 * @param User $user
	 */
	function __construct(User $user)
	{
		$this->user = $user;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->user->getId();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->user->getLabel();
	}

	/**
	 * @return \Change\User\GroupInterface[]
	 */
	public function getGroups()
	{
		if ($this->groups === null)
		{
			$this->groups = array();
			foreach ($this->user->getGroups() as $group)
			{
				$this->groups[] = new AuthenticatedGroup($group);
			}
		}
		return $this->groups;
	}

	/**
	 * @return boolean
	 */
	public function authenticated()
	{
		return true;
	}
}
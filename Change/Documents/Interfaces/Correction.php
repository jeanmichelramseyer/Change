<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Interfaces;

/**
 * @name \Change\Documents\Interfaces\Correction
 * @method integer getId()
 * @method \Change\Documents\AbstractModel getDocumentModel()
 */
interface Correction
{
	/**
	 * @return boolean
	 */
	public function useCorrection();

	/**
	 * @return boolean
	 */
	public function hasCorrection();

	/**
	 * @return \Change\Documents\Correction|null
	 */
	public function getCurrentCorrection();

	/**
	 * @return boolean
	 * @throws \InvalidArgumentException
	 */
	public function mergeCurrentCorrection();


	public function updateMergedDocument();
}
<?php
namespace Change\Transaction;

/**
 * @name \Change\Transaction\TransactionManager
 */
class TransactionManager extends \Exception
{
	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var integer
	 */
	protected $count = 0;

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * @param \Change\Db\DbProvider $provider
	 */
	public function __construct(\Change\Db\DbProvider $provider)
	{
		$this->setDbProvider($provider);
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function setDbProvider(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	public function getDbProvider()
	{
		return $this->dbProvider;
	}

	/**
	 * @return boolean
	 */
	public function started()
	{
		return $this->count > 0;
	}

	/**
	 * @return boolean
	 */
	public function isDirty()
	{
		return $this->dirty;
	}

	/**
	 * @return integer
	 */
	public function count()
	{
		return $this->count;
	}

	public function begin()
	{
		$this->checkDirty();
		$this->count++;
		if ($this->count == 1)
		{
			$this->getDbProvider()->beginTransaction();
		}
	}

	public function commit()
	{
		$this->checkDirty();
		if ($this->count <= 0)
		{
			throw new \LogicException('Commit bad transaction count (' . $this->count . ')', 121000);
		}
		if ($this->count == 1)
		{
			$this->getDbProvider()->commit();
		}
		$this->count--;
	}

	/**
	 * @param \Exception $e
	 * @throws \LogicException
	 * @throws \Change\Transaction\RollbackException
	 * @return \Exception
	 */
	public function rollBack(\Exception $e = null)
	{
		if ($this->count == 0)
		{
			throw new \LogicException('Rollback bad transaction count', 121001);
		}
		$this->count--;

		if (!$this->dirty)
		{
			$this->dirty = true;
		}
		if ($this->count == 0)
		{
			$this->dirty = false;
			$this->getDbProvider()->rollBack();
		}
		else
		{
			if (!($e instanceof RollbackException))
			{
				$e = new RollbackException($e);
			}
			throw $e;
		}

		return ($e instanceof RollbackException) ? $e->getPrevious() : $e;
	}

	/**
	 * @throws \LogicException
	 */
	protected final function checkDirty()
	{
		if ($this->dirty)
		{
			throw new \LogicException('Transaction is dirty', 121002);
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'Transaction count: ' . $this->count . ' dirty: ' . ($this->dirty ? 'true' : 'false');
	}
}

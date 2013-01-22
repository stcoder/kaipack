<?php

namespace Model\Kaipack;

use Kaipack\Component\Database\TableAbstract;

class Module extends TableAbstract
{
	protected $_name = 'modules';

	/**
	 * @return \Zend\Db\ResultSet\ResultSet
	 */
	public function getActivatedModules()
	{
		$query = array('activated' => 'on');

		$rowset = $this->select(function($select) use ($query) {
			$select->columns(['name', 'activated']);
			$select->where($query);
		});

		return $rowset;
	}
}
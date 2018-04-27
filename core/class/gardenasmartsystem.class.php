<?php

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../3rdparty/gardena.class.inc.php';

class gardenasmartsystem extends eqLogic {

  /*************** Attributs ***************/
  private static $_gardena = null;

  /************* Static methods ************/
	public static function getGardena() {
		$user = config::byKey('username',__CLASS__);
		$password = config::byKey('password',__CLASS__);
		log::add(__CLASS__, 'info', "Collecting data from Gardena servers...");
		$gardena = new gardena($user, $password);
		log::add(__CLASS__, 'info', "Collecting data from Gardena servers. Done");
		return $gardena;
	}

  public static function postSave() {
	  gardenasmartsystem::detectMower();
  }
	
	public static function detectMower() {
		log::add(__CLASS__, 'debug', "detectMower");
		$gardena = self::getGardena();
		//log::add(__CLASS__, 'debug', var_export($gardena, true));
		log::add(__CLASS__, 'debug', gardena::CATEGORY_MOWER);
		$mower = $gardena->getFirstDeviceOfCategory(gardena::CATEGORY_MOWER);
		if (!is_object($mower)) {
			return;
		}
		log::add(__CLASS__, 'debug', "Found a mower named [{$mower->name}]");
		//log::add(__CLASS__, 'debug', json_encode($mower));
		
		$eqLogic = gardenasmartsystem::byLogicalId($mower->id, __CLASS__);
		if (!is_object($eqLogic)) {
				$eqLogic = new self();
				$eqLogic->setLogicalId($mower->id);
				$eqLogic->setName($mower->name);
				$eqLogic->setConfiguration('category',gardena::CATEGORY_MOWER);
				$eqLogic->setEqType_name(__CLASS__);
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
        $eqLogic->save();
			}
		log::add(__CLASS__, 'debug', "After byLogicalId");
      
      
		$status = $eqLogic->getCmd(null, 'status');
		if (!is_object($status)) {
			$status = new gardenasmartsystemCmd();
			$status->setLogicalId('status');
			$status->setIsVisible(1);
			$status->setName(__('Etat', __FILE__));
		}
		//$status->setConfiguration('request', '/site/#siteId#/security');
		//$status->setConfiguration('response', 'statusLabel');
		$status->setEventOnly(1);
		$status->setConfiguration('onlyChangeEvent',1);
		$status->setType('info');
		$status->setSubType('string');
		$status->setIsHistorized(1);
		$status->setDisplay('generic_type','STATUS');
		$status->setEqLogic_id($eqLogic->getId());
		$status->save();
		log::add(__CLASS__, 'debug', "After status->save");
      
	}
	
  /**************** Methods ****************/
  public static function cron() {
      //log::add(__CLASS__, 'debug', "cron");
      gardenasmartsystem::updateMowers();
  }
  
  public static function updateMowers() {
	$equipments = eqLogic::byType(__CLASS__, true);
	if (count($equipments) > 0) {
		// Get last data from Gardena API
		$gardena = self::getGardena();
		foreach ( $equipments as $equipment) {
			if ($equipment->getConfiguration('category')==gardena::CATEGORY_MOWER) {
				$mower = $gardena->getDeviceById($equipment->getLogicalId());
				if (is_object($mower)) {
					// Get mower state
					$status = $gardena->getMowerState($mower);
					log::add(__CLASS__, 'debug', "Updating " . $equipment->getName() . " mower state [$status]");
					$equipment->checkAndUpdateCmd('status', $status);
				}
				
			}
		  }
	}
  }

  /********** Getters and setters **********/

}

class gardenasmartsystemCmd extends cmd {

  /*************** Attributs ***************/

  /************* Static methods ************/

  /**************** Methods ****************/

  public function execute($_options = array()) {

  }

  /********** Getters and setters **********/

}

<?php

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../3rdparty/gardena.class.inc.php';

class gardenasmartsystem extends eqLogic {

  /*************** Attributs ***************/
  private static $_gardena = null;

  /************* Static methods ************/
	public static function getGardena() {
    $gardena = NULL;
		$user = config::byKey('username',__CLASS__);
		$password = config::byKey('password',__CLASS__);
    if ( !empty($user) && !empty($password)){
      log::add(__CLASS__, 'info', "Collecting data from Gardena servers...");
      $gardena = new gardena($user, $password);
      log::add(__CLASS__, 'info', "Collecting data from Gardena servers. Done");
    } else {
      log::add(__CLASS__, 'warning', "Configuration incomplete, cannot retrieve information from server");
    }
		return $gardena;
	}

  public static function postSave() {
	  gardenasmartsystem::detectMower();
  }
	
	public static function detectMower() {
		log::add(__CLASS__, 'debug', "detectMower");
		$gardena = self::getGardena();
    
    if (!is_object($gardena))
      return;
    
    foreach ($gardena->getDevicesOfCategory(gardena::CATEGORY_MOWER) as $mower) {
       log::add(__CLASS__, 'debug', "Checking mower ". $mower->name);

      $eqLogic = gardenasmartsystem::byLogicalId($mower->id, __CLASS__);
      // Create or update equipment
      if (!is_object($eqLogic)) {
          log::add(__CLASS__, 'debug', "Found a new mower named [{$mower->name}]");
          $eqLogic = new self();
          $eqLogic->setLogicalId($mower->id);
          $eqLogic->setName($mower->name);
          $eqLogic->setConfiguration('category',gardena::CATEGORY_MOWER);
          $eqLogic->setEqType_name(__CLASS__);
          $eqLogic->setIsVisible(1);
          $eqLogic->setIsEnable(1);
          $eqLogic->save();
      }
      
      log::add(__CLASS__, 'debug', "Checking mower commands for ". $mower->name);
      foreach( $eqLogic->getDefaultCommands() as $id => $data){
            log::add(__CLASS__, 'debug', "Checking mower command " . $id . " for " . $mower->name);
            list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue, $visible) = $data;
            $cmd = $eqLogic->getCmd(null, $id);
            if ( ! is_object($cmd) ) {
                $cmd = new gardenasmartsystemCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($eqLogic->getId());
                $cmd->setType($type);
                $cmd->setSubType($subtype);
                $cmd->setLogicalId($id);
                if ( !empty($listValue) )
                {
                    $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);
                $cmd->save();
            }
            else
            {
                if (empty($cmd->getType())) $cmd->setType($type);
                if (empty($cmd->getSubType())) $cmd->setSubType($subtype);
                if (empty($cmd->getDisplay('invertBinary'))) $cmd->setDisplay('invertBinary',$invertBinary);
                if (empty($cmd->getDisplay('generic_type'))) $cmd->setDisplay('generic_type', $generic_type);
                if (empty($cmd->getDisplay('dashboard'))) $cmd->setTemplate('dashboard', $template_dashboard);
                if (empty($cmd->getDisplay('mobile'))) $cmd->setTemplate('mobile', $template_mobile);
                if ( $listValue != "" ) $cmd->setConfiguration('listValue', $listValue);
                $cmd->save();
            }
      }      
    }
	}
  
  private function getDefaultCommands() {
        return array(   
              "batteryLevel" => array(__('Batterie', __FILE__), 'info', 'numeric', "%", 0, "GENERIC_INFO", '', 'badge', '',1),
              "status" => array(__('Etat', __FILE__), 'info', 'string', "", 0, "GENERIC_INFO", 'badge', 'badge', '',1),
              "command" => array(__('Commande', __FILE__), 'action', 'select', "", 0, "GENERIC_ACTION", '', '', 
                'CMD_MOWER_PARK_UNTIL_NEXT_TIMER|'.__('Stationner jusqu\'au prochain démarrage',__FILE__).
                ';CMD_MOWER_PARK_UNTIL_FURTHER_NOTICE|'.__('Stationner et interrompre tous les programmes',__FILE__).
                ';CMD_MOWER_START_RESUME_SCHEDULE|'.__('Démarrer selon le programme',__FILE__).
                ';CMD_MOWER_START_24HOURS|'.__('Démarrer pendant 24h',__FILE__)
			  ,1)
        );
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
          
          // Battery Level
          $batteryLevel =  $gardena->getPropertyData($mower, gardena::ABILITY_BATTERY, gardena::PROPERTY_BATTERYLEVEL)->value;
					log::add(__CLASS__, 'debug', "Updating " . $equipment->getName() . " battery level [$batteryLevel]%");
					$equipment->checkAndUpdateCmd('batteryLevel', $batteryLevel);
          
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
  	public static function convertState($_state) {
        $state = $_state;
		switch ($_state) {
			case 'error': $state = __("Erreur", __FILE__); break;
			case 'error_at_power_up': $state = __("Erreur à la mise sous tension", __FILE__); break;
			case "off_disabled": $state = __("Eteint", __FILE__); break;
			case "off_hatch_closed":$state = __("Désactivé, démarrage manuel requis", __FILE__); break;
            case "off_hatch_closed_secondary_area":$state = __("Désactivé. Zone secondaire", __FILE__); break;
            case "off_hatch_open": $state = __("{mowerType, select, 14 {La tondeuse a besoin de votre attention. Veuillez suivre les instructions sur l'écran et appuyer sur ▷ pour confirmer votre entrée.} other {Désactivé. Trappe ouverte ou code PIN requis.}}", __FILE__); break;
            case "ok_charging": $state = __("En charge", __FILE__); break;
            case "ok_charging_with_date": $state = __("En charge. Prochain démarrage\\u00A0: {date}", __FILE__); break;
            case "ok_cutting": $state = __("Tonte", __FILE__); break;
            case "ok_cutting_timer_overridden": $state = __("Tonte, programme remplacé", __FILE__); break;
            case "ok_cutting_timer_overridden_secondary_area": $state = __("Tondre la zone secondaire jusqu'à ce que la batterie soit à plat", __FILE__); break;
            case "ok_cutting_timer_overridden_with_date": $state = __("Tonte jusque : {date}", __FILE__); break;
            case "ok_leaving": $state = __("Déplacement vers le point de départ", __FILE__); break;
            case "ok_searching": $state = __("Recherche", __FILE__); break;
            case "parked_autotimer": $state = __("Stationné, minuteur automatique", __FILE__); break;
            case "parked_autotimer_with_date": $state = __("Stationné, minuteur automatique. Prochain démarrage\\u00A0: {date}", __FILE__); break;
            case "parked_daily_limit_reached": $state = __("Terminé", __FILE__); break;
            case "parked_daily_limit_reached_with_date": $state = __("Terminé. Prochain démarrage\\u00A0: {date}", __FILE__); break;
            case "parked_park_selected": $state = __("Stationné", __FILE__); break;
            case "parked_park_selected_with_date": $state = __("Stationné. Prochain démarrage\\u00A0: {date}", __FILE__); break;
            case "parked_timer": $state = __("Stationné conformément au programme", __FILE__); break;
            case "parked_timer_with_date": $state = __("Stationné conformément au programme jusque\\u00A0: {date}", __FILE__); break;
            case "paused": $state = __("Arrêté", __FILE__); break;
            case "unknown": $state = __("Statut inconnu", __FILE__); break;
            case "wait_power_up": $state = __("Mise sous tension, veuillez patienter", __FILE__); break;
            case "wait_updating": $state = __("Mise à jour, veuillez patienter", __FILE__); break;
		}
		return $state;
	}

  /**************** Methods ****************/

  public function execute($_options = array()) {
      if ( $this->getLogicalId() == 'command' && $_options['select'] != "" )
      {
          log::add(__CLASS__,'info',"Execute ".$this->getLogicalId()." ".$_options['select']);
          $gardena = gardenasmartsystem::getGardena();
          $eqLogic = $this->getEqLogic();
          $mower = $gardena->getDeviceById($eqLogic->getLogicalId());
          if (is_object($mower)) {
            $cmdName=$_options['select'];
            log::add(__CLASS__,'debug',"Sending command ".$cmdName);
            $output = $gardena->sendCommand($mower, $gardena->$cmdName);
            log::add(__CLASS__,'debug',"Command output : ".$output);
          }
      }
  }
  public function formatValueWidget($_value) {
	  if($this->getLogicalId()=='status') {
          $displayStatus=self::convertState($_value);
          return $displayStatus;
      } else 
		  return $_value;
  }

  /********** Getters and setters **********/

}

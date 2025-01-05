<?php

namespace MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent;

use pocketmine\entity\object\Painting;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

use MHIGists\WorldGuardPlugin\Data\GuardData;
use MHIGists\WorldGuardPlugin\Main;
use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;

class Damage implements Listener{

	public mixed $tag;
    public Main $api;

    public function __construct(Main $api){
		$this->tag = WorldGuard::getInstance()->getTag();
		$this->api = $api;
	}

	public function onDamage(EntityDamageEvent $ev): void
    {
		$data = WorldData::getInstance();
		$entity = $ev->getEntity();
		$d = GuardData::getInstance();
		$pos = $entity->getPosition();
		$name = $data->getName($pos);
		if ($entity instanceof Player){
			if ($data->getBlockJoin($pos)){
				if ($d->getinvincible($name)){
					$ev->cancel();
				}
			}
		}
		if ($ev instanceof EntityDamageByEntityEvent){
			$p = $ev->getDamager();
			if ($ev->getCause() == 10){
				if ($d->getTNTDamage($name)){
					$ev->cancel();
				}
			}
			if ($p instanceof Player && $entity instanceof Player){
				if ($d->getPVP($name) || $d->getPVP($data->getName($p->getPosition()))){
					$p->sendMessage($this->tag. $this->api->getAPI()->getString("pvp"));
					$ev->cancel();
				}
			}
			if ($entity instanceof Player && ! $p instanceof Player){
				if ($d->getMobDamage($name)){
					$ev->cancel();
				}
			}
			if (! $entity instanceof Painting && ! $entity instanceof Player && $p instanceof Player){
				if ($d->getMobPVP($name)){
					$p->sendMessage($this->tag. $this->api->getAPI()->getString("pvpMob"));
					$ev->cancel();
				}
			}
		}
	}
}
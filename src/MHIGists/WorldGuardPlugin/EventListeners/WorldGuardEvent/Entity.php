<?php

namespace MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent;

use pocketmine\entity\object\Painting;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

use MHIGists\WorldGuardPlugin\Data\GuardData;
use MHIGists\WorldGuardPlugin\Main;
use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;

class Entity implements Listener{

	public Main $api;
	public mixed $tag;

	public function __construct(Main $api){
		$this->tag = WorldGuard::getInstance()->getTag();
		$this->api = $api;
	}

	public function onMobSpawn(EntitySpawnEvent $ev): void
    {
		$pos = $ev->getEntity()->getPosition();
		$guardData = GuardData::getInstance();
		$guard = WorldData::getInstance();
		if ($guard->getBlockJoin($pos)){
			if ($guardData->getMobSpawn($guard->getName($pos))){
				if (! $ev->getEntity() instanceof Player && ! $ev->getEntity() instanceof Painting){
					$ev->getEntity()->teleport(new Vector3(0, -99, 0));
					$ev->getEntity()->flagForDespawn();
				}
			}
		}
	}
}
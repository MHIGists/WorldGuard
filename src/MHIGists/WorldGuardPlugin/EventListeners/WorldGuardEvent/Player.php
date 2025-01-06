<?php

namespace MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent;

use pocketmine\block\ItemFrame;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;

use MHIGists\WorldGuardPlugin\Data\GuardData;
use MHIGists\WorldGuardPlugin\Main;
use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;

class Player implements Listener{

	public mixed $tag;
    public Main $api;

    public function __construct(Main $api){
		$this->tag = WorldGuard::getInstance()->getTag();
		$this->api = $api;
	}

	public function onChat(PlayerChatEvent $ev): void
    {
		$player = $ev->getPlayer();
		$pos = $player->getPosition();
		$data = WorldData::getInstance();
		$guardData = GuardData::getInstance();
		$name = $data->getName($pos);
		if ($data->getBlockJoin($pos)){
			if ($guardData->getChat($name)){
				if(! $player->hasPermission("WorldGuardPlugin.permission")){
					$player->sendMessage($this->tag. $this->api->getAPI()->getString("chat"));
					$ev->cancel();
				}
			}
		}
	}

	public function onInteractEvent(PlayerInteractEvent $ev) : bool{
		$player = $ev->getPlayer();
		$block = $ev->getBlock();
		$data = WorldData::getInstance();
		$guardData = GuardData::getInstance();
		$pos = $player->getPosition();
		$name = $data->getName($pos);
		$ac = $ev->getAction();
		if(! $player->hasPermission("WorldGuardPlugin.permission")){
			if($data->getBlockJoin($pos)){
				if ($guardData->getInteract($name)){
					if ($ac == 0 && ! $block instanceof ItemFrame){
						return true;
					}
					if(! $guardData->getMembers($name, $player->getName())){
						$player->sendMessage($this->tag. $this->api->getAPI()->getString("Use"));;
						$ev->cancel();
					}
				}
			}
		}
        return false;
	}

    public function onMove(PlayerMoveEvent $ev): void
    {
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();
        $data = WorldData::getInstance();
        $guardData = GuardData::getInstance();
        $zoneName = '';

        if (!$data->getBlockJoin($from) && $data->getBlockJoin($to)) {
            $player->sendMessage("You're now entering: " . $zoneName);
        }

        if ($data->getBlockJoin($from) && !$data->getBlockJoin($to)) {
            $player->sendMessage("You're now out of the $zoneName zone");
        }

        $pos = $player->getPosition();
        $name = $data->getName($pos);
        if ($data->getBlockJoin($pos) && $guardData->getFire($name)) {
            $player->extinguish();
        }
    }

}
<?php

namespace MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent;

use MHIGists\WorldGuardPlugin\Main;
use pocketmine\block\Lava;
use pocketmine\block\Water;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

use MHIGists\WorldGuardPlugin\Data\GuardData;
use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;

class BlockGuard implements Listener
{
    private string $tag;
    private Main $api;

    public function __construct(Main $api)
    {
        $this->tag = WorldGuard::getInstance()->getTag();
        $this->api = $api;
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $source = $event->getSource();
        $block = $event->getBlock();
        $pos = $block->getPosition();
        $guardData = GuardData::getInstance();
        $worldData = WorldData::getInstance();

        if ($worldData->getBlockJoin($pos)) {
            $regionName = $worldData->getName($pos);

            if (($source instanceof Lava && $guardData->getLava($regionName)) ||
                ($source instanceof Water && $guardData->getWater($regionName))) {
                $event->cancel();
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $blockPos = $event->getBlock()->getPosition();
        $worldData = WorldData::getInstance();
        $guardData = GuardData::getInstance();

        if (!$player->hasPermission("WorldGuardPlugin.permission")) {
            if ($worldData->getBlockJoin($blockPos)) {
                $regionName = $worldData->getName($blockPos);

                if ($guardData->getBuild($regionName) && !$guardData->getMembers($regionName, $player->getName())) {
                    $player->sendMessage($this->tag . $this->api->getAPI()->getString("BlockBreak"));
                    $event->cancel();
                }
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $blockPos = $event->getBlockAgainst()->getPosition();
        $worldData = WorldData::getInstance();
        $guardData = GuardData::getInstance();

        if (!$player->hasPermission("WorldGuardPlugin.permission")) {
            if ($worldData->getBlockJoin($blockPos)) {
                $regionName = $worldData->getName($blockPos);

                if ($guardData->getBuild($regionName) && !$guardData->getMembers($regionName, $player->getName())) {
                    $player->sendMessage($this->tag . $this->api->getAPI()->getString("BlockPlace"));
                    $event->cancel();
                }
            }
        }
    }

    public function onEntityPreExplode(EntityPreExplodeEvent $event): void
    {
        $explosionEntity = $event->getEntity();

        $explosionPos = $explosionEntity->getPosition();
        $worldData = WorldData::getInstance();
        $guardData = GuardData::getInstance();

        $radius = $event->getRadius();
        $world = $explosionEntity->getWorld();

        for ($x = $explosionPos->x - $radius; $x <= $explosionPos->x + $radius; $x++) {
            for ($y = $explosionPos->y - $radius; $y <= $explosionPos->y + $radius; $y++) {
                for ($z = $explosionPos->z - $radius; $z <= $explosionPos->z + $radius; $z++) {
                    $pos = new Position($x, $y, $z, $world);
                    if ($worldData->getBlockJoin($pos)) {
                        $regionName = $worldData->getName($pos);

                        if ($guardData->getTNT($regionName)) {
                            $event->setBlockBreaking(false);
                            return;
                        }
                    }
                }
            }
        }
    }
}

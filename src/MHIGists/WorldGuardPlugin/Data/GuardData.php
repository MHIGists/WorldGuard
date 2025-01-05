<?php

namespace MHIGists\WorldGuardPlugin\Data;

use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;
use MHIGists\WorldGuardPlugin\Main;

class GuardData
{
    use SingletonTrait;

    private WorldData $data;
    private WorldGuard $worldGuard;
    private ?Main $api;

    public function __construct()
    {
        self::setInstance($this);
        $this->api = Server::getInstance()->getPluginManager()->getPlugin("WorldGuardPlugin");
        $this->worldGuard = WorldGuard::getInstance();
        $this->data = WorldData::getInstance();
    }

    private function getFlag(string $name, string $flag): bool
    {
        return $this->data->isName($name) && isset($this->api->db["name"][$name]["flag"][$flag]);
    }

    public function getChat(string $name): bool
    {
        return $this->getFlag($name, "chat");
    }

    public function getMobSpawn(string $name): bool
    {
        return $this->getFlag($name, "mob-spawn");
    }

    public function getBuild(string $name): bool
    {
        return $this->getFlag($name, "build");
    }

    public function getInteract(string $name): bool
    {
        return $this->getFlag($name, "use");
    }

    public function getMembers(string $name, string $player): bool
    {
        return $this->data->isName($name) && isset($this->api->db["name"][$name]["member"][strtolower($player)]);
    }

    public function getTNT(string $name): bool
    {
        return $this->getFlag($name, "tnt");
    }

    public function getInvincible(string $name): bool
    {
        return $this->getFlag($name, "invincible");
    }

    public function getLava(string $name): bool
    {
        return $this->getFlag($name, "lava-flow");
    }

    public function getWater(string $name): bool
    {
        return $this->getFlag($name, "water");
    }

    public function getTNTDamage(string $name): bool
    {
        return $this->getFlag($name, "tnt-damage");
    }

    public function getPVP(string $name): bool
    {
        return $this->getFlag($name, "pvp");
    }

    public function getMobDamage(string $name): bool
    {
        return $this->getFlag($name, "mob-damage");
    }

    public function getMobPVP(string $name): bool
    {
        return $this->getFlag($name, "mob-pvp");
    }

    public function getFire(string $name): bool
    {
        return $this->getFlag($name, "fire");
    }
}
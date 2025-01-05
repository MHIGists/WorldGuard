<?php

namespace MHIGists\WorldGuardPlugin;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class WorldData
{

    use SingletonTrait;

    public ?\pocketmine\plugin\Plugin $api;
    public WorldGuard $worldguard;

    public function __construct()
    {
        self::setInstance($this);
        $this->api = Server::getInstance()->getPluginManager()->getPlugin("WorldGuardPlugin");
        $this->worldguard = WorldGuard::getInstance();
    }

    public function isName(string $name): bool
    {
        $bool = false;
        if (isset($this->api->db["name"][$name])) {
            $bool = true;
        }
        return $bool;
    }

    public function isInside(Position $position, $minX, $minY, $minZ, $maxX, $maxY, $maxZ, $world): bool
    {
        $position = Position::fromObject($position->floor(), $position->getWorld());
        $world = Server::getInstance()->getWorldManager()->getWorldByName($world);
        if ($world == null) {
            return false;
        }
        return $position->isValid() && $world === Server::getInstance()->getWorldManager()->getWorldByName($position->getWorld()->getFolderName()) &&
            $position->x >= min($minX, $maxX) && $position->x <= max($minX, $maxX) &&
            $position->y >= min($minY, $maxY) && $position->y <= max($minY, $maxY) &&
            $position->z >= min($minZ, $maxZ) && $position->z <= max($minZ, $maxZ);
    }

    public function WorldData($name): array|false
    {
        if ($this->isName($name)) {
            return $this->api->db["name"][$name];
        } else {
            return false;
        }
    }

    public function getName(Position $position): false|int|string
    {
        $bool = "";
        if (!isset($this->api->db["name"])) {
            return false;
        }
        foreach ($this->api->db["name"] as $name => $array) {
            if ($this->isName($name)) {
                [$minX, $minY, $minZ] = array_map('intval', explode(":", $array["pos1"] ?? "0:0:0"));
                [$maxX, $maxY, $maxZ] = array_map('intval', explode(":", $array["pos2"] ?? "0:0:0"));
                $world = $array["world"] ?? "";

                if ($this->isInside($position, $minX, $minY, $minZ, $maxX, $maxY, $maxZ, $world)) {
                    $bool = $name;
                }
            }
        }

        return $bool;
    }

    public function removeGuard(string $name): void
    {
        if ($this->isName($name)) {
            unset($this->api->db["name"][$name]);
        }
        $this->api->save();
    }

    public function getBlockJoin(Position $position): bool
    {
        if (!isset($this->api->db["name"])) {
            return false;
        }

        foreach ($this->api->db["name"] as $name => $array) {
            if ($this->isName($name)) {
                [$minX, $minY, $minZ] = array_map('intval', explode(":", $array["pos1"] ?? "0:0:0"));
                [$maxX, $maxY, $maxZ] = array_map('intval', explode(":", $array["pos2"] ?? "0:0:0"));
                $world = $array["world"] ?? "";

                if ($this->isInside($position, $minX, $minY, $minZ, $maxX, $maxY, $maxZ, $world)) {
                    return true;
                }
            }
        }

        return false;
    }


    public function isPortal(Player $p): bool
    {
        $bool = false;
        $name = strtolower($p->getName());
        if (isset($this->api->db["portal"][$name])) {
            $bool = true;
        }
        return $bool;
    }

    public function addMember(string $name, $pp): void
    {
        if ($this->isName($name)) {
            $this->api->db["name"][$name]["member"][$pp] = $pp;
        }
        $this->api->save();
    }

    public function removeMember(string $name, $pp): void
    {
        if ($this->isName($name)) {
            unset($this->api->db["name"][$name]["member"][$pp]);
        }
        $this->api->save();
    }

    public function worldFlagData($name, string $type, string $allow = "deny"): void
    {
        $a = new Type();
        if ($this->isName($name)) {
            if ($a->isType($type)) {
                if ($allow == "deny") {
                    $this->api->db["name"][$name]["flag"][$type] = true;
                } else {
                    unset($this->api->db["name"][$name]["flag"][$type]);
                }
                $this->api->save();
            }
        }
    }

    public function createGuard(Player $p, $name): void
    {
        if ($this->worldguard->isModel($p)) {
            if (!$this->isName($name)) {
                $data = $this->worldguard->getPlayerData($p)["last"] ?? null;
                if ($data == null) {
                    return;
                }
                $pos1 = $data["pos1"] ?? null;
                $pos2 = $data["pos2"] ?? null;
                if ($pos1 == null || $pos2 == null) {
                    return;
                }
                $ex = explode(":", $pos1);
                $world = $ex[3] ?? null;
                if ($world == null) {
                    return;
                }
                $this->api->db["name"][$name] = [
                    "member" => [],
                    "flag" => [],
                    "pos1" => $pos1,
                    "pos2" => $pos2,
                    "world" => $world
                ];
                $this->api->save();
                $this->worldguard->cancel($p);
            }
        }
    }
}
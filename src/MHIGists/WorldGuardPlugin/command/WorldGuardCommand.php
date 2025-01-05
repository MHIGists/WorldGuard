<?php

namespace MHIGists\WorldGuardPlugin\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use MHIGists\WorldGuardPlugin\Data\GuardData;
use MHIGists\WorldGuardPlugin\Language\ConfigGuard;
use MHIGists\WorldGuardPlugin\Main;
use MHIGists\WorldGuardPlugin\Type;
use MHIGists\WorldGuardPlugin\WorldData;
use MHIGists\WorldGuardPlugin\WorldGuard;

class WorldGuardCommand extends Command implements PluginOwned
{
    use PluginOwnedTrait;

    private Main $api;

    public function __construct(Main $api)
    {
        parent::__construct("rg", "worldGuard", null, ["region"]);
        $this->api = $api;
        $this->setPermission("WorldGuardPlugin.permission");
    }

    private function sendPlayerMessage(Player $player, string $messageKey, array $replacements = []): void
    {
        $message = $this->api->getAPI()->getString($messageKey);
        foreach ($replacements as $key => $value) {
            $message = str_replace("($key)", $value, $message);
        }
        $player->sendMessage($message);
    }

    private function isSenderValid(CommandSender $sender): ?Player
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->api->getAPI()->getString(ConfigGuard::ingame));
            return null;
        }

        if (!$this->testPermission($sender)) {
            return null;
        }

        return $sender;
    }

    public function flag(Player $player): void
    {
        $type = new Type();
        $i = implode(", ", $type->array);
        $this->sendPlayerMessage($player, "flagList", ["flagList" => $i]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $player = $this->isSenderValid($sender);
        if (!$player) {
            return;
        }

        $worldGuard = WorldGuard::getInstance();
        $worldData = WorldData::getInstance();

        if (!isset($args[0])) {
            $this->sendPlayerMessage($player, ConfigGuard::HELP);
            return;
        }

        switch (strtolower($args[0])) {
            case "pos":
            case "position":
                if ($worldGuard->isMode($player)) {
                    $this->sendPlayerMessage($player, ConfigGuard::isMode);
                    return;
                }
                $worldGuard->setMode($player);
                $this->sendPlayerMessage($player, ConfigGuard::position);
                break;

            case "define":
            case "d":
                if (!$worldGuard->isModel($player)) {
                    $this->sendPlayerMessage($player, "notMode");
                    return;
                }

                if (!isset($args[1])) {
                    $this->sendPlayerMessage($player, "noname");
                    return;
                }

                $regionName = $args[1];
                if ($worldData->isName($regionName)) {
                    $this->sendPlayerMessage($player, "isname", ["name" => $regionName]);
                } else {
                    $worldData->createGuard($player, $regionName);
                    $this->sendPlayerMessage($player, "create", ["name" => $regionName]);
                }
                break;

            case "cancel":
                $worldGuard->cancel($player);
                $this->sendPlayerMessage($player, "cancel");
                break;

            case "flag":
            case "f":
                if (isset($args[1]) && strtolower($args[1]) === "list") {
                    $this->flag($player);
                    return;
                }

                if (!isset($args[1], $args[2], $args[3])) {
                    $this->sendPlayerMessage($player, "f");
                    return;
                }

                $regionName = $args[1];
                if (!$worldData->isName($regionName)) {
                    $this->sendPlayerMessage($player, "isname", ["name" => $regionName]);
                    return;
                }

                $flagType = strtolower($args[2]);
                $flagValue = strtolower($args[3]);
                if (!in_array($flagValue, ["allow", "deny", "none"])) {
                    $this->sendPlayerMessage($player, "no3");
                    return;
                }

                $type = new Type();
                if (!$type->isType($flagType)) {
                    $this->sendPlayerMessage($player, "noType");
                    return;
                }

                $worldData->worldFlagData($regionName, $flagType, $flagValue);
                $this->sendPlayerMessage($player, "fadd", [
                    "name" => $regionName,
                    "flag" => $flagType,
                    "value" => $flagValue
                ]);
                break;

            case "info":
                if (!isset($args[1])) {
                    $this->sendPlayerMessage($player, "i");
                    return;
                }

                $regionName = $args[1];
                if (!$worldData->isName($regionName)) {
                    $this->sendPlayerMessage($player, "isname", ["name" => $regionName]);
                    return;
                }

                $this->sendRegionInfo($player, $regionName);
                break;
        }
    }

    private function sendRegionInfo(Player $player, string $regionName): void
    {
        $region = $this->api->db["name"][$regionName] ?? [];
        $flags = implode(", ", array_keys($region["flag"] ?? []));
        $members = implode(", ", array_keys($region["member"] ?? [])) ?: $this->api->getAPI()->getString("noMembers");
        $pos1 = implode(",", explode(":", $region["pos1"] ?? "0:0:0"));
        $pos2 = implode(",", explode(":", $region["pos2"] ?? "0:0:0"));
        $world = $region["world"] ?? "";

        $this->sendPlayerMessage($player, "list", [
            "region" => $regionName,
            "flag" => $flags,
            "members" => $members,
            "pos1" => $pos1,
            "pos2" => $pos2,
            "world" => $world
        ]);
    }
}

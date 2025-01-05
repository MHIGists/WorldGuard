<?php

namespace MHIGists\WorldGuardPlugin\EventListeners;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use MHIGists\WorldGuardPlugin\Main;
use MHIGists\WorldGuardPlugin\WorldGuard;

class Blocks implements Listener
{
    private Main $api;
    private WorldGuard $worldGuard;

    public function __construct(Main $api)
    {
        $this->api = $api;
        $this->worldGuard = WorldGuard::getInstance();
    }

    private function formatMessage(string $template, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getBlockPositionData($block): array
    {
        $pos = $block->getPosition();
        return [
            'x' => $pos->getX(),
            'y' => $pos->getY(),
            'z' => $pos->getZ(),
            'world' => $pos->getWorld()->getFolderName(),
        ];
    }

    private function handlePositionSetting($player, $block, string $messageKey, callable $callback): void
    {
        $data = $this->getBlockPositionData($block);
        $template = $this->api->getAPI()->getString($messageKey);
        $message = $this->formatMessage($template, [
            '(x)' => $data['x'],
            '(y)' => $data['y'],
            '(z)' => $data['z'],
        ]);
        $fullPosition = implode(":", [$data['x'], $data['y'], $data['z'], $data['world']]);

        $player->sendMessage($message);
        $callback($fullPosition);
    }

    public function onInteract(PlayerInteractEvent $ev): void
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();

        if ($this->worldGuard->isMode($player) && !$this->worldGuard->isPos1($player)) {
            $this->handlePositionSetting(
                $player,
                $block,
                "pos1",
                function ($fullPosition) use ($player, $ev) {
                    $this->worldGuard->setPos1($player, $fullPosition);
                    $ev->cancel();
                }
            );
        }
    }

    public function onBlockBreak(BlockBreakEvent $ev): void
    {
        $player = $ev->getPlayer();
        $block = $ev->getBlock();

        if ($this->worldGuard->isMode($player) && $this->worldGuard->isPos1($player)) {
            $playerData = $this->worldGuard->getPlayerData($player);

            $pos1 = explode(":", $playerData["pos1"]);
            $world = $pos1[3] ?? "";
            if ($world !== $player->getWorld()->getFolderName()) {
                $errorTemplate = $this->api->getAPI()->getString("createError1");
                $errorMessage = $this->formatMessage($errorTemplate, ['(world)' => $world]);
                $player->sendMessage($errorMessage);
                $ev->cancel();
                return;
            }

            $this->handlePositionSetting(
                $player,
                $block,
                "pos2",
                function ($fullPosition) use ($player, $playerData, $ev) {
                    $this->worldGuard->setMode1($player, $playerData["pos1"], $fullPosition);
                    $ev->cancel();
                }
            );
        }
    }
}

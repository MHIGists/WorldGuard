<?php

namespace MHIGists\WorldGuardPlugin;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Filesystem;

use Symfony\Component\Filesystem\Path;

use MHIGists\WorldGuardPlugin\command\WorldGuardCommand;
use MHIGists\WorldGuardPlugin\EventListeners\Blocks;
use MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent\BlockGuard;
use MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent\Damage;
use MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent\Entity;
use MHIGists\WorldGuardPlugin\EventListeners\WorldGuardEvent\Player;
use MHIGists\WorldGuardPlugin\Language\ABC;

class Main extends PluginBase implements Listener{

	public array $db = [];
	public ABC $abc;

	public function onEnable() : void{
		$path = Path::join($this->getDataFolder(), "worldGuard.json");
		if(file_exists($path)){
			$this->db = json_decode(Filesystem::fileGetContents($path), true);
		}

		$this->abc = new ABC($this);
		$this->abc->load($this->getConfig()->get("language"));

		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new WorldGuardCommand($this)
		]);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->registerEvents([
			$this,
			new Blocks($this),
			new BlockGuard($this),
			new Damage($this),
			new Player($this),
			new Entity($this)
			]
		);
	}

	public function registerEvents(array $events): void
    {
		foreach($events as $event){
			$this->getServer()->getPluginManager()->registerEvents($event, $this);
		}
	}

	public function getAPI(): ABC{
		return $this->abc;
	}

	public function onDisable(): void
	{
		Filesystem::safeFilePutContents(Path::join($this->getDataFolder(), "worldGuard.json"), json_encode($this->db, JSON_UNESCAPED_UNICODE));
	}

	public function save():void{
		$this->onDisable();
	}
}
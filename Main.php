<?php

namespace place;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase implements Listener {

    private $placedBlocks = [];
    private $blockCounter = [];
    private $playerInventory = [];

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("Place plugin enabled.");
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->saveInventory($player);
        $this->blockCounter[$player->getName()] = 0;
    }

    //public function onPlayerRespawn(PlayerRespawnEvent $event) {
     //$player = $event->getPlayer();
    //  $this->applyPlaceWorldSettings($player);
    

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if ($block->y === 27) {
            $event->setCancelled();
            $newBlock = $block->getLevel()->getBlock($block->subtract(0, 1, 0));
            $newBlock->getLevel()->setBlock($newBlock, $block, true, true);
            $this->placedBlocks[] = $newBlock;
            $this->blockCounter[$player->getName()]++;
        }
    }

    private function saveInventory(Player $player) {
        $this->playerInventory[$player->getName()] = $player->getInventory()->getContents();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool {
        if ($command->getName() === "place") {
            if ($sender instanceof Player) {
                $this->teleportToPlaceWorld($sender);
                $sender->setGamemode(Player::CREATIVE);
                $sender->removeAllEffects();
                $sender->getInventory()->clearAll();
                $woolColors = [0, 15, 4, 5, 13, 11, 3, 14, 7, 12, 10, 2, 6];
                foreach ($woolColors as $color) {
                    $sender->getInventory()->addItem(Item::get(Item::WOOL, $color, 64));
                }
                return true;
            } else {
                $sender->sendMessage("You can only use this command in-game.");
                return false;
            }
        } elseif ($command->getName() === "top-place") {
            $topPlayers = $this->getTopPlayers();
            $sender->sendMessage("§e» §fTop players with most placed blocks in place:");
            foreach ($topPlayers as $position => $playerData) {
                $sender->sendMessage("§6#" . ($position + 1) . " §7- §a" . $playerData["name"] . "§7: §e" . $playerData["blocks"] . " blocks");
            }
            return true;
        }
        return false;
    }

    private function teleportToPlaceWorld(Player $player) {
        $placeWorld = $this->getServer()->getLevelByName("place");
        if ($placeWorld !== null) {
            $spawn = $placeWorld->getSafeSpawn();
            $player->teleport($spawn);
            $player->sendMessage("§e» §fWelcome to Place! §e«");
        } else {
            $player->sendMessage("§cThe place world is not loaded!");
        }
    }

    private function getTopPlayers(): array {
    $playerData = yaml_parse_file($this->getDataFolder() . "players.yml");
    arsort($playerData);
    $topPlayers = [];
    $i = 0;
    foreach ($playerData as $playerName => $blocks) {
        if ($i >= 3) {
            break;
        }
        $topPlayers[] = ["name" => $playerName, "blocks" => $blocks];
        $i++;
    }
    return $topPlayers;
}
}



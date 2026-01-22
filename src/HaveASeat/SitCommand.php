<?php

declare(strict_types=1);

namespace HaveASeat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SitCommand extends Command {

    private SeatPlugin $plugin;

    public function __construct(SeatPlugin $plugin) {
        parent::__construct("sit", "Toggle the ability to sit on stairs");
        $this->plugin = $plugin;
        $this->setPermission("haveaseat.toggle");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return true;
        }

        $cooldown = $this->plugin->getConfig()->get("sit-command-cooldown", 5);
        $lastUse = $this->plugin->getCommandCooldown($sender);
        if ($lastUse !== null && (time() - $lastUse) < $cooldown) {
            $remaining = $cooldown - (time() - $lastUse);
            $sender->sendMessage("§cPlease wait $remaining seconds before using this command again.");
            return true;
        }

        $this->plugin->setCommandCooldown($sender, time());

        $enabled = $this->plugin->toggleSitting($sender);
        $sender->sendMessage($enabled ? "§aYou can now sit on stairs." : "§cYou can no longer sit on stairs.");
        return true;
    }
}
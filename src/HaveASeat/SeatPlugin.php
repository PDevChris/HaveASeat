<?php

declare(strict_types=1);

namespace HaveASeat;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\block\Stairs;
use pocketmine\block\Slab;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\event\server\DataPacketReceiveEvent;

class SeatPlugin extends PluginBase implements Listener {

    private Config $config;
    private Config $toggleConfig;
    /** @var int[] */
    private array $commandCooldowns = [];
    /** @var SeatData[] */
    private array $seats = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->toggleConfig = new Config($this->getDataFolder() . "toggle.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if ($this->config->get("register-sit-command", true)) {
            $this->getServer()->getCommandMap()->register("sit", new SitCommand($this));
        }
    }

    public function onDisable(): void {
        foreach ($this->seats as $seat) {
            $seat->stand();
        }
    }

    public function canApplyWorld(World $world): bool {
        $applyWorlds = $this->config->get("apply-worlds", true);
        if ($applyWorlds === true) {
            return true;
        }
        if (is_string($applyWorlds)) {
            $worlds = array_map('trim', explode(',', $applyWorlds));
            return in_array($world->getFolderName(), $worlds);
        }
        return false;
    }

    public function isAllowedHighHeight(): bool {
        return $this->config->get("allow-seat-high-height", true);
    }

    public function isAllowedUpsideDown(): bool {
        return $this->config->get("allow-seat-upsidedown", false);
    }

    public function isAllowedWhileSneaking(): bool {
        return $this->config->get("allow-seat-while-sneaking", true);
    }

    public function standWhenBreak(): bool {
        return $this->config->get("stand-up-when-break-block", true);
    }

    public function disableDamageWhenSit(): bool {
        return $this->config->get("disable-damage-when-sit", false);
    }

    public function isToggleEnabled(Player $player): bool {
        return $this->toggleConfig->get(strtolower($player->getName()), true);
    }

    public function isSitting(Player $player): bool {
        return isset($this->seats[$player->getName()]);
    }

    private function isBlockAllowed(Block $block): bool {
        $allowed = $this->config->get("allowed-blocks", ["stairs"]);
        foreach ($allowed as $type) {
            if (strtolower($type) === "stairs" && $block instanceof Stairs) {
                return true;
            }
            if (strtolower($type) === "slab" && $block instanceof Slab) {
                return true;
            }
            if (str_contains(strtolower($block->getName()), strtolower($type))) {
                return true;
            }
        }
        return false;
    }

    public function canSit(Player $player, Block $block): bool {
        if (!$this->isBlockAllowed($block)) {
            return false;
        }
        if ($this->isSitting($player)) {
            return false;
        }
        if (!$this->canApplyWorld($player->getWorld())) {
            return false;
        }
        if (!$this->isToggleEnabled($player)) {
            return false;
        }
        if ($block instanceof Stairs && !$this->isAllowedUpsideDown() && $block->isUpsideDown()) {
            return false;
        }
        if (!$this->isAllowedHighHeight() && $player->getPosition()->getY() < $block->getPosition()->getY()) {
            return false;
        }
        if (!$this->isAllowedWhileSneaking() && $player->isSneaking()) {
            return false;
        }
        if ($this->getSeatDataByPosition($block->getPosition()) !== null) {
            return false;
        }
        return true;
    }

    public function sit(Player $player, Block $block): void {
        $seat = new SeatData($player, $block);
        $this->seats[$player->getName()] = $seat;
        $seat->sit();
        if ($this->config->get("send-tip-when-sit", false)) {
            $tip = str_replace(
                ['@b', '@x', '@y', '@z'],
                [$block->getName(), (string)$block->getPosition()->getX(), (string)$block->getPosition()->getY(), (string)$block->getPosition()->getZ()],
                $this->config->get("send-tip-when-sit-message", "Sitting on @b")
            );
            $player->sendTip($tip);
        }
    }

    public function stand(Player $player): void {
        if (isset($this->seats[$player->getName()])) {
            $this->seats[$player->getName()]->stand();
            unset($this->seats[$player->getName()]);
        }
    }

    public function getSeatDataByPosition(Vector3 $pos): ?SeatData {
        foreach ($this->seats as $seat) {
            if ($seat->getBlock()->getPosition()->equals($pos)) {
                return $seat;
            }
        }
        return null;
    }

    public function toggleSitting(Player $player): bool {
        $name = strtolower($player->getName());
        $current = $this->toggleConfig->get($name, true);
        $new = !$current;
        $this->toggleConfig->set($name, $new);
        $this->toggleConfig->save();
        return $new;
    }

    public function getCommandCooldown(Player $player): ?int {
        return $this->commandCooldowns[strtolower($player->getName())] ?? null;
    }

    public function setCommandCooldown(Player $player, int $time): void {
        $this->commandCooldowns[strtolower($player->getName())] = $time;
    }

    // Event handlers
    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($this->canSit($player, $block)) {
            $existing = $this->getSeatDataByPosition($block->getPosition());
            if ($existing !== null) {
                $msg = str_replace(
                    ['@b', '@p'],
                    [$block->getName(), $existing->getPlayer()->getName()],
                    $this->config->get("try-to-sit-already-inuse", "Seat occupied by @p")
                );
                $player->sendMessage($msg);
            } else {
                $this->sit($player, $block);
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        if ($this->standWhenBreak()) {
            $this->removeSeatByPosition($event->getBlock()->getPosition());
        }
    }

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && $this->isSitting($entity) && $this->disableDamageWhenSit()) {
            $event->cancel();
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $this->stand($event->getPlayer());
    }

    public function onMove(PlayerMoveEvent $event): void {
        // Optional: adjust seat position if needed
    }

    public function onDataPacket(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if ($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
            $this->stand($event->getOrigin()->getPlayer());
        }
    }

    public function onJoin(PlayerJoinEvent $event): void {
        foreach ($this->seats as $seat) {
            $seat->sendTo($event->getPlayer());
        }
    }

    private function removeSeatByPosition(Vector3 $pos): void {
        foreach ($this->seats as $name => $seat) {
            if ($seat->getBlock()->getPosition()->equals($pos)) {
                $seat->stand();
                unset($this->seats[$name]);
                break;
            }
        }
    }
}
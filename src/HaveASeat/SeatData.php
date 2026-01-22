<?php

declare(strict_types=1);

namespace HaveASeat;

use pocketmine\player\Player;
use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\block\utils\SlabType;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\entity\Entity;

class SeatData {

    private Player $player;
    private Block $block;
    private int $entityId;

    public function __construct(Player $player, Block $block) {
        $this->player = $player;
        $this->block = $block;
        $this->entityId = mt_rand(1000000, 9999999); // Unique fake entity ID
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getBlock(): Block {
        return $this->block;
    }

    public function sit(): void {
        $yOffset = $this->block instanceof Slab ? ($this->block->getSlabType() === SlabType::TOP ? 1.0 : 0.5) : 1.5;
        $pos = $this->block->getPosition()->add(0.5, $yOffset, 0.5);

        $addPacket = new AddActorPacket();
        $addPacket->actorRuntimeId = $this->entityId;
        $addPacket->actorUniqueId = $this->entityId;
        $addPacket->type = "minecraft:chicken";
        $addPacket->position = $pos;
        $addPacket->motion = new Vector3(0, 0, 0);
        $addPacket->pitch = 0.0;
        $addPacket->yaw = 0.0;
        $addPacket->headYaw = 0.0;
        $addPacket->attributes = [];
        $addPacket->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty((1 << EntityMetadataFlags::IMMOBILE) | (1 << EntityMetadataFlags::INVISIBLE) | (1 << EntityMetadataFlags::SILENT))
        ];
        $addPacket->syncedProperties = new PropertySyncData([], [], []);

        $linkPacket = new SetActorLinkPacket();
        $linkPacket->link = new EntityLink($this->entityId, $this->player->getId(), EntityLink::TYPE_RIDER, true, true, 0.0);

        $players = $this->player->getWorld()->getPlayers();
        foreach ($players as $p) {
            $p->getNetworkSession()->sendDataPacket($addPacket);
            $p->getNetworkSession()->sendDataPacket($linkPacket);
        }

        $this->player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
    }

    public function stand(): void {
        $linkPacket = new SetActorLinkPacket();
        $linkPacket->link = new EntityLink($this->entityId, $this->player->getId(), EntityLink::TYPE_REMOVE, true, true, 0.0);

        $removePacket = new RemoveActorPacket();
        $removePacket->actorUniqueId = $this->entityId;

        $players = $this->player->getWorld()->getPlayers();
        foreach ($players as $p) {
            $p->getNetworkSession()->sendDataPacket($linkPacket);
            $p->getNetworkSession()->sendDataPacket($removePacket);
        }

        $this->player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);
    }

    public function sendTo(Player $target): void {
        $yOffset = $this->block instanceof Slab ? ($this->block->getSlabType() === SlabType::TOP ? 1.0 : 0.5) : 1.5;
        $pos = $this->block->getPosition()->add(0.5, $yOffset, 0.5);

        $addPacket = new AddActorPacket();
        $addPacket->actorRuntimeId = $this->entityId;
        $addPacket->actorUniqueId = $this->entityId;
        $addPacket->type = "minecraft:chicken";
        $addPacket->position = $pos;
        $addPacket->motion = new Vector3(0, 0, 0);
        $addPacket->pitch = 0.0;
        $addPacket->yaw = 0.0;
        $addPacket->headYaw = 0.0;
        $addPacket->attributes = [];
        $addPacket->metadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty((1 << EntityMetadataFlags::IMMOBILE) | (1 << EntityMetadataFlags::INVISIBLE) | (1 << EntityMetadataFlags::SILENT))
        ];
        $addPacket->syncedProperties = new PropertySyncData([], [], []);

        $linkPacket = new SetActorLinkPacket();
        $linkPacket->link = new EntityLink($this->entityId, $this->player->getId(), EntityLink::TYPE_RIDER, true, true, 0.0);

        $target->getNetworkSession()->sendDataPacket($addPacket);
        $target->getNetworkSession()->sendDataPacket($linkPacket);
    }
}
<?php

namespace gamegam\PortalPlugin;

use gamegam\PortalPlugin\event\EventListener;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;

class MoveTask extends Task
{

    private array $data = [];
    private Player $player;

    public function __construct(Player $player, array $data)
    {
        $this->player = $player;
        $this->data = $data;
    }

    public function onRun(): void
    {
        if ($this->player->isOnline()) {
            $p = $this->player;
            $pos = $p->getPosition();
            if (PortalLoader::getBlockJoin($pos)){
                $data = PortalLoader::getPortal($pos);
                $a = $data["도착지점"] ?? null;
                $delay = $data["delay"] ?? 0;
                // 입장 시간의 밀리초
                $join_mic = $delay + EventListener::$portal[$p->getName()];
                $porttal_name = $data["name"] ?? null;
                $v = $data["잠금여부"] ?? false;
                if ($v){
                    $p->sendActionBarMessage("§b[ §7포탈§b ]\n§a{$porttal_name}의 포탈은 현재 접속 불가능한 포탈입니다.");
                    return;
                }
                $join_time = $join_mic - microtime(true);
                $round = round($join_time, 2);
                if ($round <= 0){
                    $a_i = explode(":", $a);
                    $x = (int)$a_i[0] + 0.5;
                    $y = (int)$a_i[1] + 1;
                    $z = (int)$a_i[2] + 0.5;
                    $world = $a_i[3];
                    $a_pos = new Position($x, $y, $z, Server::getInstance()->getWorldManager()->getWorldByName($world));
                    $p->teleport($a_pos);
                    $p->sendTitle("§b[ §7포탈§b ]","§a{$porttal_name}으로 이동합니다.");
                }else{
                    $p->sendActionBarMessage("§b[ §7포탈§b ]\n{$porttal_name}으로 이동중입니다.\n{$round}");
                }
            }else{
                unset(PortalLoader::getInstance()->task[$p->getName()]);
                $this->getHandler()->cancel();
            }
        }
    }
}
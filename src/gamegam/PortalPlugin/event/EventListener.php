<?php

namespace gamegam\PortalPlugin\event;

use gamegam\PortalPlugin\PortalLoader;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\SingletonTrait;

class EventListener implements Listener
{

    public static array $portal = [];
    use SingletonTrait;

    public function __construct()
    {
        self::setInstance($this);
    }

    public function onQuit(PlayerQuitEvent $event){
        $name = $event->getPlayer()->getName();
        unset(PortalLoader::$mode[$name], self::$portal[$name], PortalLoader::$edit[$name]);
    }

    public function onBreak(BlockBreakEvent $event){
        $p = $event->getPlayer();
        $name = $p->getName();
        $block = $event->getBlock();
        $portal = PortalLoader::$mode[$name] ?? null;
        $pos = $block->getPosition();
        $all = $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ() . ":" . $pos->getWorld()->getFolderName();
        if ($portal !== null){
            $portal_name = $portal["name"] ?? null;
            if ($portal_name === null)return;
            // 포탈 생성
            if (!isset(PortalLoader::$mode[$name]["pos1"])){
                PortalLoader::$mode[$name]["pos1"] = $all;
                PortalLoader::$mode[$name]["world"] = $pos->getWorld()->getFolderName();
                $p->sendMessage("§d두번째 지점은 터치해 주세요.");
            }elseif (isset(PortalLoader::$mode[$name]["pos2"])){
                // 이동할지점
                if (!isset(PortalLoader::$db["data"][$portal_name])){
                    PortalLoader::$db["data"][$portal_name] = [
                        "name" => $portal_name,
                        "pos1" => $portal["pos1"],
                        "pos2" => $portal["pos2"],
                        "도착지점" => $all,
                        "잠금여부" => false,
                        "world" => $portal["world"] ?? $pos->getWorld()->getFolderName()
                    ];
                    $p->sendMessage("§d포탈을 생성했습니다.");
                    PortalLoader::cache_reload();
                }else{
                    $p->sendMessage("§d이미 생성된 포탈입니다.");
                }
                unset(PortalLoader::$mode[$name]);
            }
            $event->cancel();
        }else if (isset(PortalLoader::$edit[$name])){
            $portal_name = PortalLoader::$edit[$name]["name"];
            // 수정모드
            if (!isset(PortalLoader::$db["data"][$portal_name])){
                unset(PortalLoader::$edit[$name]);
                $p->sendMessage("§d생성되지 않는 포탈입니다. 작업을 종료합니다.");
            }else{
                if (!isset(PortalLoader::$edit[$name]["pos1"])){
                    PortalLoader::$edit[$name]["pos1"] = $all;
                    $p->sendMessage("§d두번째 지점은 터치해 주세요.");
                }else if (isset(PortalLoader::$edit[$name]["pos2"])){
                    if (!isset(PortalLoader::$db["data"][$portal_name])) {
                        unset(PortalLoader::$edit[$name]);
                        $p->sendMessage("§d생성되지 않는 포탈입니다. 작업을 종료합니다.");
                    }else{
                        PortalLoader::$db["data"][$portal_name]["pos1"] = PortalLoader::$edit[$name]["pos1"];
                        PortalLoader::$db["data"][$portal_name]["pos2"] = PortalLoader::$edit[$name]["pos2"];
                        $p->sendMessage("§d포탈을 수정했습니다.");
                        unset(PortalLoader::$edit[$name]);
                        PortalLoader::cache_reload();
                    }
                }
            }
            $event->cancel();
        }
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        $p = $event->getPlayer();
        $name = $p->getName();
        $pos = $event->getBlock()->getPosition();
        $all = $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ() . ":" . $pos->getWorld()->getFolderName();
        $portal = PortalLoader::$mode[$name] ?? null;
        if ($portal !== null){
            $portal_name = $portal["name"] ?? null;
            if ($portal_name === null){
                $p->sendMessage("포탈을 생성할 수 없습니다.");
                unset(PortalLoader::$mode[$name]);
                return;
            }
            if (isset($portal["pos1"])){
                if ($pos == $portal["pos1"]){
                    $p->sendMessage("§d똑같은 좌표에서 생성할 수 없습니다.");
                }else{
                    if (!isset(PortalLoader::$mode[$name]["pos2"])){
                        PortalLoader::$mode[$name]["pos2"] = $all;
                        $p->sendMessage("§d이동할 좌표를 선택해 주세요. (블럭 파괴)");
                    }
                }
            }
            $event->cancel();
        }
        if (isset(PortalLoader::$edit[$name]["pos1"])){
            $edit = PortalLoader::$edit[$name];
            if ($pos == $edit["pos1"]){
                $p->sendMessage("§d똑같은 좌표에서 생성할 수 없습니다.");
            }else {
                if (!isset(PortalLoader::$edit[$name]["pos2"])) {
                    PortalLoader::$edit[$name]["pos2"] = $all;
                    $p->sendMessage("§d이동할 좌표를 터치하세요");
                }
            }
            $event->cancel();
        }
    }

    public function onMove(PlayerMoveEvent $event){
        $p = $event->getPlayer();
        $pos = $p->getPosition();
        if (PortalLoader::getBlockJoin($pos)){
            $data = PortalLoader::getPortal($pos);
            $a = $data["도착지점"] ?? null;
            $porttal_name = $data["name"] ?? null;
            $v = $data["잠금여부"] ?? false;
            if ($a !== null){
                if (isset(self::$portal[$p->getName()]))return;
                PortalLoader::getInstance()->move($p, $data);
                self::$portal[$p->getName()] = microtime(true);
            }
        }else{
            unset(self::$portal[$p->getName()]);
        }
    }
}
<?php

namespace gamegam\PortalPlugin\cmd;

use gamegam\PortalPlugin\Form\PortalEditForm;
use gamegam\PortalPlugin\PortalLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\form\Form;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\Player;

class PortalCommand extends Command implements Form
{
    private string $portal;

    public function __construct(
         string $portal = ""
    )
    {
        parent::__construct("포탈관리", "포탈을 관리합니다.");
        $this->setPermission(DefaultPermissionNames::BROADCAST_ADMIN);
        $this->portal = $portal;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if ($sender instanceof Player){
            if (!isset($args[0])){
                $sender->sendMessage("§a/포탈관리 생성 [포탈 이름] | 포탈을 생성합니다.");
                $sender->sendMessage("§a/포탈관리 삭제 [포탈 이름] | 포탈을 삭제합니다.");
                $sender->sendMessage("§a/포탈관리 작업중단 | 작업중인걸 취소합니다.");
                $sender->sendMessage("§a/포탈관리 목록 | 생성한 모든 포탈을 확인합니다.");
                $sender->sendMessage("§a/포탈관리 잠금설정 [포탈 이름] | 해당 포탈의 이동 여부를 설정합니다.");
                $sender->sendMessage("§a/포탈관리 수정 [포탈 이름] | 해당 포탈을 수정합니다.");
            }else {
                if ($args[0] == "생성") {
                    if (!isset($args[1])) {
                        $sender->sendMessage("§a포탈 이름을 입력해 주세요.");
                    } else {
                        // 이미 생성된 포탈일경우
                        if (isset(PortalLoader::$db["data"][$args[1]])) {
                            $sender->sendMessage("§c이미 해당 포탈을 생성했습니다. /포탈관리 목록을 통해 생성된 포탈을 확인할 수 있습니다.");
                        } else {
                            unset(PortalLoader::$mode[$sender->getName()], PortalLoader::$edit[$sender->getName()]);
                            $pp = $args[1] ?? null;
                            $sender->sendMessage("§d첫번째 지점: 블럭파괴, 두번째지점 클릭");
                            PortalLoader::$mode[$sender->getName()]["name"] = $pp;
                        }
                    }
                }
                if ($args[0] == "삭제") {
                    if (!isset($args[1])) {
                        $sender->sendMessage("§a포탈 이름을 입력해 주세요.");
                    } else {
                        if (isset(PortalLoader::$db["data"][$args[1]])) {
                            unset(PortalLoader::$db["data"][$args[1]], PortalLoader::$cache[$args[1]]);
                            $sender->sendMessage("§a{$args[1]} 포탈을 삭제했습니다.");
                        } else {
                            $sender->sendMessage("§c해당 포탈을 찾을 수 없습니다.");
                        }
                    }
                }
                if ($args[0] == "작업중단") {
                    unset(PortalLoader::$mode[$sender->getName()], PortalLoader::$edit[$sender->getName()]);
                    $sender->sendMessage("§a작업을 중단했습니다.");
                }
                if ($args[0] == "목록") {
                    $i = implode(", ", array_keys(PortalLoader::$db["data"]));
                    $sender->sendMessage("§a생성된 포탈 목록: " . $i);
                }
                if ($args[0] == "잠금설정") {
                    if (!isset($args[1])) {
                        $sender->sendMessage("§a포탈 이름을 입력해 주세요.");
                    } else {
                        if (isset(PortalLoader::$db["data"][$args[1]])) {
                            $sender->sendForm(new PortalCommand($args[1]));
                        } else {
                            $sender->sendMessage("§c해당 포탈을 찾을 수 없습니다.");
                        }
                    }
                }
                if ($args[0] == "수정"){
                    if (!isset($args[1])) {
                        $sender->sendMessage("§a포탈 이름을 입력해 주세요.");
                    }else {
                        if (isset(PortalLoader::$db["data"][$args[1]])) {
                            $sender->sendForm(new PortalEditForm($args[1]));
                        } else {
                            $sender->sendMessage("§c해당 포탈을 찾을 수 없습니다.");
                        }
                    }
                }
            }
        }
    }

    public function jsonSerialize(): array
    {
        $data = PortalLoader::$db["data"][$this->portal] ?? [];
        $v = $data["잠금여부"] ?? false;
        $v_str = ($v) ? "§a활성화§r" : "§c비활성화§r";
        return [
            "type" => "custom_form",
            "title" => "",
            "content" => [[
                "type" => "toggle",
                "text" => "현재 포탈을 잠금여부를 설정할 수 있습니다.\n({$v_str})",
                "default" => $v,
            ]]
        ];
    }

    public function handleResponse(Player $player, $data): void{
        if (!isset($data[0])) return;
        $datas = PortalLoader::$db["data"][$this->portal] ?? [];
        if ($datas == []){
            $player->sendMessage("§c포탈 정보를 찾을 수 없습니다. 존재하는지 확인해 주십시오.");
        }else{
            PortalLoader::$db["data"][$this->portal]["잠금여부"] = $data[0];
            $str = ($data[0]) ? "§a활성화§r" : "§c비활성화§r";
            $player->sendMessage("해당 포탈의 잠금을 {$str}했습니다.");
        }
    }
}

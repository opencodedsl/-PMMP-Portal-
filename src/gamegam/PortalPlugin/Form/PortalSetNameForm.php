<?php

namespace gamegam\PortalPlugin\Form;

use gamegam\PortalPlugin\PortalLoader;
use pocketmine\form\Form;
use pocketmine\player\Player;

class PortalSetNameForm implements Form
{

    public function __construct(private string $name){
        $this->name = $name;
    }

    public function jsonSerialize(): mixed
    {
        return [
            "type" => "custom_form",
            "title" => "",
            "content" => [[
                "type" => "input",
                "text" => "변경할 포탈 이름을 입력해 주세요"
            ]]
        ];
    }

    public function handleResponse(Player $player, $data): void
    {
        if (!isset($data[0])) return;
        if (!isset(PortalLoader::$db["data"][$this->name]["name"])){
            $player->sendMessage("§c해당 포탈은 존재하지 않습니다.");
        }else{
            // 공백일경우
            if (empty($data[0]))return;
            PortalLoader::$db["data"][$this->name]["name"] = $data[0];
            $player->sendMessage("§b{$this->name} 이름을 {$data[0]}으로 변경했습니다.");
        }
    }
}
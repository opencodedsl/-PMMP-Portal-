<?php

namespace gamegam\PortalPlugin\Form;

use gamegam\PortalPlugin\PortalLoader;
use pocketmine\form\Form;
use pocketmine\player\Player;

class PortalSetDelayForm implements Form
{

    public string $name;

    public function __construct($name){
        $this->name = $name;
    }

    public function jsonSerialize(): array{
        return [
            "type" => "custom_form",
            "title" => "",
            "content" => [[
                "type" => "input",
                "text" => "포탈 딜레이를 입력해 주세요.",
            ]]
        ];
    }

    public function handleResponse(Player $player, $data): void
    {
        if (!isset($data[0])) return;
        $a = PortalLoader::setDelay($this->name, $data[0]);
        if ($a === null){
            $player->sendMessage("이 포탈은 딜레이를 설정할 수 없습니다.");
            return;
        }
        $player->sendMessage("포탈 딜레이 시간을 {$a}으로 설정했습니다.");
    }
}
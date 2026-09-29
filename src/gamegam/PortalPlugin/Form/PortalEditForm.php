<?php

namespace gamegam\PortalPlugin\Form;

use gamegam\PortalPlugin\PortalLoader;
use pocketmine\form\Form;
use pocketmine\player\Player;

class PortalEditForm implements Form
{

    private string $name;

    public function __construct(
        string $name = ""
    )
    {
        $this->name = $name;
    }

    public function jsonSerialize(): mixed
    {
        return [
            "type" => "form",
            "title" => "",
            "content" => "",
            "buttons" => [[
                "text" => "§l재위치 설정"
            ],
                [
                    "text" => "입장 딜레이 설정"
                ],
                [
                    "text" => "포탈 이름 변경"
                ]
            ]
        ];
    }

    public function handleResponse(Player $player, $data): void
    {
        if ($data === null) return;
        $form = match ($data) {
            0 => "position",
            1 => new PortalSetDelayForm($this->name),
            2 => new PortalSetNameForm($this->name),
            default => null,
        };
        if ($form === null) return;
        if ($form == "position"){
            // 터치 모드
            PortalLoader::$edit[$player->getName()]["name"] = $this->name;
            $player->sendMessage("§d수정 모드에 진입했습니다. /포탈관리 작업중단 명령어를 통해 중단 가능");
        }else{
            $player->sendForm($form);
        }
    }
}
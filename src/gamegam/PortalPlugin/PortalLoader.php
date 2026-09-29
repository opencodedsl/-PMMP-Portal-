<?php

namespace gamegam\PortalPlugin;

use gamegam\ARAServer\Server\ServerEventLister;
use gamegam\PortalPlugin\cmd\PortalCommand;
use gamegam\PortalPlugin\event\EventListener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\WorldException;
use Symfony\Component\Filesystem\Path;

class PortalLoader extends PluginBase
{

    use SingletonTrait;

    static array $mode = [];
    static array $edit = [];

    public static array $db = [];

    public static array $cache = [];
    public static array $grid = [];
    public array $task = [];

    public function onEnable(): void
    {

        $path = Path::join($this->getDataFolder(), "portal.json");
        if (file_exists($path)){
            self::$db = json_decode(file_get_contents($path), true);
        }

        // command
        $this->getServer()->getCommandMap()->registerAll($this->getName(), [
            new PortalCommand(),
        ]);
        // event
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        self::cache_reload();
    }

    public static function setDelay(
        string $name,
        $delay
    ): ?int{
        $code = null;
        if (isset(self::$db["data"][$name])){
            $delay = (int)$delay;
            $code = $delay;
            self::$db["data"][$name]["delay"] = $delay;
        }
        return $code;
    }

    public function move(
        Player $p,
        $data
    ){
        if (!isset($this->task[$p->getName()])){
            $this->getScheduler()->scheduleRepeatingTask(new \gamegam\PortalPlugin\MoveTask($p, $data), 0);
            $this->task[$p->getName()] = true;
        }
    }
    public static function cache_reload(){
        if (!isset(self::$db["data"]))return;
        foreach (self::$db["data"] as $key => $value){
            $pos1 = explode(":", $value["pos1"]);
            $pos2 = explode(":", $value["pos2"]);
            $a = $value["도착지점"] ?? null;
            $v = $value["잠금여부"] ?? false;
            try {
                Server::getInstance()->getWorldManager()->loadWorld($value["world"]);
            } catch (WorldException $e) {
                Server::getInstance()->getLogger()->error($e->getMessage());
            }

            $world = Server::getInstance()->getWorldManager()->getWorldByName($value["world"]);
            $worldName = $world->getFolderName();
            $cache = [
                'name' => $key,
                'minX' => (int)min($pos1[0], $pos2[0]),
                'maxX' => (int)max($pos1[0], $pos2[0]),
                'minY' => (int)min($pos1[1], $pos2[1]),
                'maxY' => (int)max($pos1[1], $pos2[1]),
                'minZ' => (int)min($pos1[2], $pos2[2]),
                'maxZ' => (int)max($pos1[2], $pos2[2]),
                "도착지점" => $a,
                "잠금여부" => $v,
                'world' => $worldName
            ];
            self::$cache[$key] = $cache;

            $minGX = $cache['minX'] >> 7;
            $maxGX = $cache['maxX'] >> 7;
            $minGZ = $cache['minZ'] >> 7;
            $maxGZ = $cache['maxZ'] >> 7;

            for ($gx = $minGX; $gx <= $maxGX; $gx++) {
                for ($gz = $minGZ; $gz <= $maxGZ; $gz++) {
                    self::$grid[$worldName][$gx][$gz][] = $key;
                }
            }
        }
    }

    public function onLoad(): void
    {
        self::setInstance($this);
    }
    public function save(): void{
        Filesystem::safeFilePutContents(Path::join($this->getDataFolder(), "portal.json"), json_encode(self::$db, JSON_UNESCAPED_UNICODE));
    }
    public function onDisable(): void{
        $this->save();
    }

    public static function isInside(Position $position, $minX, $minY, $minZ, $maxX, $maxY, $maxZ, $world): bool {
        $posX = $position->getFloorX();
        $posY = $position->getFloorY();
        $posZ = $position->getFloorZ();

        return $position->getWorld()->getFolderName() === $world
            && $posX >= min($minX, $maxX)
            && $posX <= max($minX, $maxX)
            && $posY >= min($minY, $maxY)
            && $posY <= max($minY, $maxY)
            && $posZ >= min($minZ, $maxZ)
            && $posZ <= max($minZ, $maxZ);
    }

    public static function getBlockJoin(Position $position): bool {
        $world = $position->getWorld()->getFolderName();
        $x = (int)$position->getX();
        $z = (int)$position->getZ();

        $gx = $x >> 7;
        $gz = $z >> 7;
        if (isset(self::$grid[$world][$gx][$gz])) {
            foreach (self::$grid[$world][$gx][$gz] as $zoneName) {
                $d = self::$cache[$zoneName] ?? null;
                if ($d === null){
                    unset(self::$cache[$zoneName]);
                    continue;
                }

                if (self::isInside($position, $d['minX'], $d['minY'], $d['minZ'], $d['maxX'], $d['maxY'], $d['maxZ'], $d["world"])){
                    return true;
                }
            }
        }

        return false;
    }

    public static function getPortal(Position $position): array{
        $world = $position->getWorld()->getFolderName();
        $x = (int)$position->getX();
        $z = (int)$position->getZ();

        $gx = $x >> 7;
        $gz = $z >> 7;

        if (isset(self::$grid[$world][$gx][$gz])) {
            foreach (self::$grid[$world][$gx][$gz] as $zoneName) {
                $d = self::$cache[$zoneName] ?? null;
                if ($d === null){
                    unset(self::$cache[$zoneName]);
                    continue;
                }

                if (self::isInside($position, $d['minX'], $d['minY'], $d['minZ'], $d['maxX'], $d['maxY'], $d['maxZ'], $d["world"])){
                    $data = self::$db["data"][$zoneName] ?? null;
                    return $data;
                }
            }
        }

        return [];
    }
}

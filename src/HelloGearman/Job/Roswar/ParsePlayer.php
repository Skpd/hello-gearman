<?php

namespace HelloGearman\Job\Roswar;

use DOMDocument;
use GearmanJob;
use HelloGearman\Job\JobInterface;
use PDO;

class ParsePlayer implements JobInterface
{
    private $baseUrl = 'http://www.roswar.ru/player/';
    /** @var \PDO */
    private $pdo;
    /** @var \PDOStatement */
    private $stmt;
    
    function __construct($databaseConfig)
    {
        $this->pdo = new PDO(
            "mysql:host={$databaseConfig['hostname']};dbname={$databaseConfig['database']}", 
            $databaseConfig['username'], 
            $databaseConfig['password'], 
            [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
        );

        $this->stmt = $this->pdo->prepare(
            'INSERT INTO roswar_players
                (`id`, `state`, `alignment`, `nickname`, `level`, `wins`, `loot`, `stats_health`, `stats_strength`, `stats_dexterity`, `stats_resistance`, `stats_intuition`, `stats_attention`, `stats_charism`, `coolness`)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `state` = ?, `alignment` = ?, `nickname` = ?, `level` = ?, `wins` = ?, `loot` = ?, `stats_health` = ?, `stats_strength` = ?, `stats_dexterity` = ?, `stats_resistance` = ?, `stats_intuition` = ?, `stats_attention` = ?, `stats_charism` = ?, `coolness` = ?'
        );
    }

    /** @return string */
    public function getName()
    {
        return 'roswar.parse-player';
    }

    public function doJob(GearmanJob $job)
    {
        $id = $job->workload();

        $player = [
            'id' => $id
        ];

        $doc = new DOMDocument;
        @$doc->loadHTML(file_get_contents($this->baseUrl . $id));

        $info = $doc->getElementById('pers-player-info');

        if (!$info) {
            $player['state'] = 'not-found';
            $this->insert($player);
            $job->sendComplete(json_encode($player));
            return;
        }

        $user = $info->childNodes->item(1)->firstChild;

        if ($user->childNodes->length !== 3) {
            $player['state'] = 'invalid';
            $this->insert($player);
            $job->sendComplete(json_encode($player));
            return;
        }

        $player['state'] = 'ok';

        $player['alignment'] = $user->firstChild->attributes->getNamedItem('class')->textContent;
        $player['nickname']  = $user->childNodes->item(1)->textContent;
        $player['level']     = (int) str_replace(['[', ']'], '', $user->childNodes->item(2)->textContent);
//        $player['id']        = (int) preg_replace('/[^\d]/', '', $user->childNodes->item(1)->attributes->getNamedItem('href')->textContent);

        $statistics = $doc->getElementById('statistics-accordion')->childNodes->item(2)->childNodes->item(1)->firstChild->childNodes;

        $player['wins'] = (int) preg_replace('/[^\d]/', '', $statistics->item(2)->textContent);
        $player['loot'] = (int) preg_replace('/[^\d]/', '', $statistics->item(4)->textContent);

        $stats = $doc->getElementById('stats-accordion')->childNodes->item(2)->firstChild->childNodes;

        foreach ($stats as $v) {
            /** @var $v \DOMNode */
            if ($v->hasAttributes()) {
                $setter = $v->attributes->getNamedItem('data-type')->textContent;
                $player['stats'][$setter] = intval($v->childNodes->item(1)->childNodes->item(2)->textContent);
            }
        }

        $player['coolness'] = array_sum($player['stats']);

        $this->insert($player);
        $job->sendComplete(json_encode($player));
    }

    private function insert(array $player)
    {
        $this->stmt->execute([
            $player['id'],
            $player['state'],
            $player['alignment'],
            $player['nickname'],
            $player['level'],
            $player['wins'],
            $player['loot'],
            $player['stats']['health'],
            $player['stats']['strength'],
            $player['stats']['dexterity'],
            $player['stats']['resistance'],
            $player['stats']['intuition'],
            $player['stats']['attention'],
            $player['stats']['charism'],
            $player['coolness'],
            $player['state'],
            $player['alignment'],
            $player['nickname'],
            $player['level'],
            $player['wins'],
            $player['loot'],
            $player['stats']['health'],
            $player['stats']['strength'],
            $player['stats']['dexterity'],
            $player['stats']['resistance'],
            $player['stats']['intuition'],
            $player['stats']['attention'],
            $player['stats']['charism'],
            $player['coolness'],
        ]);
    }
}
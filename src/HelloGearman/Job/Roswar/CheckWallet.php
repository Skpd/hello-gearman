<?php

namespace HelloGearman\Job\Roswar;

use GearmanJob;
use HelloGearman\Job\JobInterface;
use PDO;

class CheckWallet implements JobInterface
{
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

        $this->stmt = $this->pdo->prepare('UPDATE roswar_players SET money = ? WHERE id = ?');
    }

    /** @return string */
    public function getName()
    {
        return 'roswar.check-wallet';
    }

    public function doJob(GearmanJob $job)
    {
        $id = $job->workload();

        $login = $this->pdo->query('SELECT nickname FROM roswar_players WHERE id = ' . $id);

        if ($login->rowCount() < 1) {
            $job->sendFail();
            return;
        }

        $ch = curl_init('http://www.roswar.ru/login/');

        curl_setopt_array($ch, [
            CURLOPT_COOKIESESSION  => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['action' => 'login', 'email' => $login->fetchColumn(0)]),
            CURLOPT_COOKIEJAR      => 'php://memory'
        ]);

        curl_exec($ch);

        $info = curl_getinfo($ch);

        if ($info['redirect_url'] === 'http://www.roswar.ru/player/#login') {
            curl_setopt($ch, CURLOPT_URL, 'http://www.roswar.ru/player/');
            curl_setopt($ch, CURLOPT_POST, false);

            $content = curl_exec($ch);

            if (!empty($content) && preg_match('/Монет:\s(\d+)/i', $content, $m)) {
                $money = intval($m[1]);
                $this->stmt->execute([$money, $id]);
                $job->sendComplete(json_encode(['id' => $id, 'money' => $money]));
            } else {
                $job->sendFail();
                return;
            }
        } else {
            $job->sendFail();
            return;
        }
    }
}
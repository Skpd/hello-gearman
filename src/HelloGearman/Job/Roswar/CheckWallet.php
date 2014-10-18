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
        $data  = json_decode($job->workload(), true);
        $id    = $data['id'];
        $proxy = $data['proxy'];

        $login = $this->pdo->query('SELECT nickname FROM roswar_players WHERE id = ' . $id);

        if ($login->rowCount() < 1) {
            $job->sendComplete(json_encode(['id' => $id, 'status' => 'fail']));
            return;
        }

        $cookieFile = "/tmp/roswar_cookies/$id.cook";

        $ch = curl_init('http://www.roswar.ru/login/');

        curl_setopt_array($ch, [
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_HTTPHEADER     => ["Proxy-Connection:"],
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1",
            CURLOPT_COOKIESESSION  => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => 'php://memory',
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
        ]);

        if (!file_exists($cookieFile)) {
            echo "Dive in...\n";
            curl_setopt_array($ch, [
                CURLOPT_URL        => 'http://www.roswar.ru/login/',
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => http_build_query(['action' => 'login', 'email' => $login->fetchColumn(0)]),
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_URL        => 'http://www.roswar.ru/player/',
                CURLOPT_POST       => false,
            ]);
        }

        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        $content = curl_exec($ch);
        $info = curl_getinfo($ch);

        echo date(DATE_ATOM) . ": ($proxy) - " . $info['total_time'] . PHP_EOL;

        if (preg_match('/stats-accordion/i', $content)) {
            if (!empty($content) && preg_match('/Монет:\s(\d+)/i', $content, $m)) {
                $money = intval($m[1]);
                $this->stmt->execute([$money, $id]);
                $job->sendComplete(json_encode(['id' => $id, 'status' => 'ok', 'money' => $money]));
                return;
            } else {
                $job->sendComplete(json_encode(['id' => $id, 'status' => 'fail-money-parse']));
                return;
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
            $content = curl_exec($ch);
            curl_setopt($ch, CURLOPT_POST, true);

            if (preg_match('/вам нужно немного подождать/', $content)) {
                $job->sendComplete(json_encode(['id' => $id, 'status' => 'proxy-busy', 'proxy' => $proxy]));
                return;
            } else if (preg_match('/Неверное имя или этот персонаж уже защищен паролем/i', $content)) {
                $this->stmt->execute([null, $id]);
                $job->sendComplete(json_encode(['id' => $id, 'status' => 'fail-registered']));
                return;
            } else {
                $job->sendComplete(json_encode(['id' => $id, 'status' => 'fail-other']));
                return;
            }
        }
    }
}
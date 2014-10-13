<?php

namespace HelloGearman\Job\Roswar;

use DOMDocument;
use GearmanJob;
use HelloGearman\Job\JobInterface;

class ParsePlayer implements JobInterface
{
    private $baseUrl = 'http://www.roswar.ru/player/';

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
            $job->sendComplete(json_encode($player));
            return;
        }

        $user = $info->childNodes->item(1)->firstChild;

        if ($user->childNodes->length !== 3) {
            $player['state'] = 'invalid';
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

        $job->sendComplete(json_encode($player));
    }
}
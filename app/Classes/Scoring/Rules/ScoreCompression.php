<?php

namespace App\Classes\Scoring\Rules;

use App\Classes\Card\CardType;

class ScoreCompression
{

    const ROUNDING = 100000;

    private array $regions = [];
    private array $ages = [];
    private float $limit;

    private float $humoMaxLimit;
    private float $uzcadMaxLimit;

    public function __construct(float $limit)
    {
        $this->limit = $limit;
        $this->humoMaxLimit = config('test.max_region_limit_by_humo_card');
        $this->uzcadMaxLimit = config('test.max_region_limit_by_uzcard_card');
    }

    private function checkAgeLimit(int $age)
    {
        foreach ($this->ages as $ageItem) {
            if ($ageItem['from'] <= $age && $ageItem['to'] >= $age) {
                $this->limit -= ($this->limit / 100 * $ageItem['percent']);
                $this->limit = round($this->limit / self::ROUNDING) * self::ROUNDING;
                break;
            }
        }
    }

    private function checkRegionLimit(int $region, CardType $card)
    {
        foreach ($this->regions as $regionType) {
            if ($regionType == $region) {
                if ($card->isHumo() && $this->limit > $this->humoMaxLimit) {
                    $this->limit = $this->humoMaxLimit;
                }
                if ($card->isUzcard() && $this->limit > $this->uzcadMaxLimit) {
                    $this->limit = $this->uzcadMaxLimit;
                }
                break;
            }
        }
    }

    public function addAgeLimit(int $from, int $to, float $percent)
    {
        $this->ages[] = [
            'from' => $from,
            'to' => $to,
            'percent' => $percent,
        ];
    }

    public function addRegionLimit(int $region)
    {
        $this->regions[] = $region;
    }

    public function calculate(int $age, int $region, CardType $card): float
    {
        $this->checkAgeLimit($age);
        $this->checkRegionLimit($region, $card);
        return $this->limit;
    }


}

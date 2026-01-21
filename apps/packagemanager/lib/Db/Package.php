<?php

namespace OCA\PackageManager\Db;

use OCP\AppFramework\Db\Entity;

class Package extends Entity {
    protected string $name;
    protected string $code;
    protected float $price;
    protected string $quota;
    protected int $duration;
    protected string $unit;

    public function __construct(string $name = '', string $code = '', float $price = 0.0, string $quota = '', int $duration = 0, string $unit = '') {
        parent::__construct();
        // init default properties
        $this->name = $name;
        $this->code = $code;
        $this->price = $price;
        $this->quota = $quota;
        $this->duration = $duration;
        $this->unit = $unit;
    }

    public function getName() {
        return $this->getter('name');
    }

    public function setName(string $name) {
        $this->setter('name', [$name]);
    }

    public function getCode() {
        return $this->getter('code');
    }

    public function setCode(string $code) {
        $this->setter('code', [$code]);
    }

    public function getPrice() {
        return $this->getter('price');
    }   

    public function setPrice(float $price) {
        $this->setter('price', [$price]);
    }

    public function getQuota() {
        return $this->getter('quota');
    }

    public function setQuota(string $quota) {
        $this->setter('quota', [$quota]);
    }

    public function getDuration() {
        return $this->getter('duration');
    }

    public function setDuration(int $duration) {
        $this->setter('duration', [$duration]);
    }

    public function getUnit() {
        return $this->getter('unit');
    }

    public function setUnit(string $unit) {
        $this->setter('unit', [$unit]);
    }
}
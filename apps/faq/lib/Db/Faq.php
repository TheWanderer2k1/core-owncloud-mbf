<?php

namespace OCA\FAQ\Db;

use OCP\AppFramework\Db\Entity;

class Faq extends Entity {
    protected $question;
    protected $answer;
    protected $status;
    protected $updatedBy;
    protected $updatedDate;

    public function __construct() {
        parent::__construct();
        $this->addType('status', 'integer');
        $this->addType('updatedDate', 'integer');
    }

    public function getQuestion() {
        return $this->getter('question');
    }

    public function setQuestion($question) {
        $this->setter('question', [$question]);
    }

    public function getAnswer() {
        return $this->getter('answer');
    }

    public function setAnswer($answer) {
        $this->setter('answer', [$answer]);
    }

    public function getStatus() {
        return (int)$this->getter('status');
    }

    public function setStatus($status) {
        $this->setter('status', [(int)$status]);
    }

    public function getUpdatedBy() {
        return $this->getter('updatedBy');
    }

    public function setUpdatedBy($updatedBy) {
        $this->setter('updatedBy', [$updatedBy]);
    }

    public function getUpdatedDate() {
        return (int)$this->getter('updatedDate');
    }

    public function setUpdatedDate($updatedDate) {
        $this->setter('updatedDate', [(int)$updatedDate]);
    }
}

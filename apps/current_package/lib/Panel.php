<?php

namespace OCA\CurrentPackage;

use OCP\Settings\ISettings;
use OCP\IDBConnection;
use OCP\Template;
use OCP\IUserSession;

class Panel implements ISettings {

    /** @var IDbConnection */
    private $db;

    /** @var IUserSession */
    private $userSession;

    // Inject the database connection via constructor
    public function __construct(IDbConnection $db, IUserSession $userSession) {
        $this->db =$db;
        $this->userSession = $userSession;
    }

    public function getPanel() {
        $res = '';
        // Guard against early bootstrapping loops by checking if a session is
        // active. Generally, you should not touch userSession outside of this
        // if block. Failing to adhere may result in an infinite loop that
        // flood the memory and crash the web browser
        if ($this->userSession && $this->userSession->isLoggedIn()) {
            $sql = 'SELECT p.`duration`, p.`unit`, p.`price`, s.`status`, s.`end_at`, s.`start_at`, p.`name` AS `package_name`, p.`quota` ' .
                   'FROM `*PREFIX*packagemanager_subscription_status` s ' .
                   'JOIN `*PREFIX*packagemanager_packages` p ON s.`package_id` = p.`id` ' .
                   'WHERE s.`user_id` = :uid';

            $user = $this->userSession->getUser();
            $uid = $user ? $user->getUID() : '';
            //$query = $this->db->prepare('SELECT * FROM `*PREFIX*packagemanager_subscription_status` WHERE `user_id` = :uid');
            $query = $this->db->prepare($sql);
            $success = $query->execute(['uid' =>$uid]);
            if ($success) {
                $res = $query->fetchAll();
                if (count($res) > 1) {
                    throw new \Exception('Unexpected state: Found multiple subscription records for user.');
                }
            }
        }

        $tmpl = new Template('current_package', 'page');
        $tmpl->assign('data', $res);
        return $tmpl;
    }

    public function getSectionID() {
        return 'general';
    }

    public function getPriority() {
        return 99;
    }
}

<?php

namespace OCA\FAQ\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\FAQ\Db\Faq;
use OCA\FAQ\Db\FaqMapper;

class FaqController extends Controller {

    private $mapper;
    private $userSession;

    public function __construct(string $appName, IRequest $request, FaqMapper $mapper, IUserSession $userSession) {
        parent::__construct($appName, $request);
        $this->mapper = $mapper;
        $this->userSession = $userSession;
    }

    public function index(): TemplateResponse {
        $faqs = $this->mapper->findAll();
        $parameters = [
            'faqs' => $faqs
        ];
        return new TemplateResponse($this->appName, 'admin', $parameters, '');
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function create(string $question, string $answer, int $status): DataResponse {
        $user = $this->userSession->getUser();
        $username = $user ? $user->getDisplayName() : 'Admin';

        $faq = new Faq();
        $faq->setQuestion($question);
        $faq->setAnswer($answer);
        $faq->setStatus($status);
        $faq->setUpdatedBy($username);
        $faq->setUpdatedDate(time());

        $inserted = $this->mapper->insert($faq);
        return new DataResponse($inserted);
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function update(int $id, string $question, string $answer, int $status): DataResponse {
        try {
            $faq = $this->mapper->findById($id);
            $user = $this->userSession->getUser();
            $username = $user ? $user->getDisplayName() : 'Admin';

            $faq->setQuestion($question);
            $faq->setAnswer($answer);
            $faq->setStatus($status);
            $faq->setUpdatedBy($username);
            $faq->setUpdatedDate(time());

            $updated = $this->mapper->update($faq);
            return new DataResponse($updated);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @AdminRequired
     * @NoCSRFRequired
     */
    public function delete(int $id): DataResponse {
        try {
            $faq = $this->mapper->findById($id);
            $this->mapper->delete($faq);
            return new DataResponse(['id' => $id, 'status' => 'success']);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function publish(): DataResponse {
        $faqs = $this->mapper->findActive();
        $result = [];
        foreach ($faqs as $faq) {
            $result[] = [
                'id' => $faq->getId(),
                'question' => $faq->getQuestion(),
                'answer' => $faq->getAnswer(),
                'updated_by' => $faq->getUpdatedBy(),
                'updated_date' => $faq->getUpdatedDate()
            ];
        }
        return new DataResponse($result);
    }
}

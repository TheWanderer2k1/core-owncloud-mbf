<?php
/**
 * Package Manager - Page Controller
 */

namespace OCA\PackageManager\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IL10N;

class PageController extends Controller {

	/** @var IL10N */
	protected $l10n;
	
	public function __construct($appName, IRequest $request, IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
	}
	
	/**
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse('packagemanager', 'main', ['l' => $this->l10n]);
	}
}

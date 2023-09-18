<?php
/**
 * Ajax Show Editors API module
 *
 * @file
 * @ingroup API
 * @date 12 November 2020
 */
class ApiAjaxShowEditors extends ApiBase {

	/**
	 * Main entry point.
	 *
	 * @return bool
	 */
	public function execute() {
		// Need to have sufficient user rights to proceed...
		if ( !$this->getUser()->isAllowed( 'edit' ) ) {
			$this->dieWithError( 'badaccess-group0' );
		}

		// Get the request parameters
		$params = $this->extractRequestParams();

		// Ensure that the page ID is present and that it really is numeric
		$pageId = $params['pageid'];

		if ( !$pageId || $pageId === null || !is_numeric( $pageId ) ) {
			$this->dieWithError( [ 'apierror-missingparam', 'pageid' ] );
		}

		// We don't validate the username here
		$username = $params['username'];

		$output = AjaxShowEditors::getEditorListHTML( $pageId, $username );

		// Top level
		$this->getResult()->addValue( null, $this->getModuleName(),
			[ 'result' => $output ]
		);

		return true;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'pageid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'username' => [
				ApiBase::PARAM_TYPE => 'string',
				// *NOT* doing this so that the "Error: user not found" string
				// gets correctly output for anons
				// ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * @inheritDoc
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return [
			'action=ajaxshoweditors&pageid=666&username=Foo%20Bar' => 'apihelp-ajaxshoweditors-example-1'
		];
	}

}

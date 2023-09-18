<?php
/**
 * Hooked functions used by Ajax Show Editors.
 *
 * @file
 */
class AjaxShowEditorsHooks {
	/** @var array Listing of allowed values for ?action= URL param for which CSS & JS assets will be loaded */
	private static $allowedActions = [ 'edit', 'submit' ];

	/**
	 * Purge entries from our database table when a page is saved.
	 *
	 * @param WikiPage $page
	 * @param User $user
	 */
	public static function onPageContentSave( $page, User $user ) {
		global $wgCommandLineMode;

		if ( $wgCommandLineMode ) {
			return;
		}

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->delete(
			'editings',
			[
				'editings_page' => $page->getID(),
				'editings_actor' => $user->getActorId(),
			],
			__METHOD__
		);
	}

	/**
	 * Load AjaxShowEditors' CSS and JS assets on ?action=edit and also on ?action=submit (preview)
	 * but only for existing pages b/c nonexistent pages have page ID = 0, and the API module
	 * expects the page ID to be greater than 0, which is thus true only for existing pages
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		if (
			in_array( $out->getRequest()->getVal( 'action' ), self::$allowedActions ) &&
			$out->getTitle()->exists()
		) {
			$out->addModuleStyles( 'ext.ajaxshoweditors.styles' );
			$out->addModules( 'ext.ajaxshoweditors.scripts' );
		}
	}

	/**
	 * Show the box before the textarea, but only for existing pages b/c nonexistent pages
	 * have page ID = 0, and the API module expects the page ID to be greater than 0, which
	 * is thus true only for existing pages
	 *
	 * @param EditPage $editPage
	 */
	public static function onEditPageShowEditFormInitial( EditPage $editPage ) {
		$context = $editPage->getContext();
		if (
			in_array( $context->getRequest()->getVal( 'action' ), self::$allowedActions ) &&
			$context->getTitle()->exists()
		) {
			$context->getOutput()->addHTML(
				'<div id="ajax-se">' .
				'<p id="ajax-se-title">' . wfMessage( 'ajax-se-title' )->escaped() . '</p>' .
				'<p id="ajax-se-editors">' . wfMessage( 'ajax-se-pending' )->escaped() . '</p>' .
				'</div>'
			);
		}
	}

	/**
	 * Creates AjaxShowEditors' new database table when the sysadmin user runs
	 * /maintenance/update.php, the MediaWiki core updater script.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		$dbType = $db->getType();
		$dir = __DIR__ . '/../sql';
		$filename = 'editings.sql';
		if ( !in_array( $dbType, [ 'mysql', 'sqlite' ] ) ) {
			$filename = "editings.{$dbType}.sql";
		}

		$updater->addExtensionTable( 'editings', "{$dir}/{$filename}" );
	}

}

<?php
/**
 * Return a list of editors currently editing the article.
 */
class AjaxShowEditors {
	/**
	 * @param int $articleId Page ID number
	 * @param string $username
	 * @return string Properly escaped HTML suitable for output
	 */
	public static function getEditorListHTML( $articleId, $username ) {
		$articleId = intval( $articleId );

		// Validate request
		$title = Title::newFromID( $articleId );
		if ( !( $title ) ) {
			return wfMessage( 'ajax-se-pagedoesnotexist' )->escaped();
		}

		$user = User::newFromName( $username );
		if ( !$user || !$user instanceof User ) {
			return wfMessage( 'ajax-se-usernotfound' )->escaped();
		}

		// When did the user start editing?
		$dbr = wfGetDB( DB_REPLICA );
		$userStarted = $dbr->selectField(
			'editings',
			'editings_started',
			[
				'editings_actor' => $user->getActorId(),
				'editings_page' => $title->getArticleID(),
			],
			__METHOD__
		);

		// They just started editing, assume NOW
		if ( !$userStarted ) {
			$userStarted = $dbr->timestamp();
		}

		# Either create a new entry or update the touched timestamp.
		# This is done using a unique index on the database :
		# `editings_page_started` (`editings_page`,`editings_actor`,`editings_started`)

		$dbw = wfGetDB( DB_MASTER );
		$dbw->replace(
			'editings',
			[ 'editings_page', 'editings_actor', 'editings_started' ],
			[
				'editings_page' => $title->getArticleID(),
				'editings_actor' => $user->getActorId(),
				'editings_started' => $userStarted,
				'editings_touched' => $dbw->timestamp(),
			],
			__METHOD__
		);

		// Now we get the list of all editing users
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'editings',
			[ 'editings_actor', 'editings_started', 'editings_touched' ],
			[ 'editings_page' => $title->getArticleID() ],
			__METHOD__
		);

		$wikitext = '';
		$unix_now = wfTimestamp( TS_UNIX );
		$first = 1;
		foreach ( $res as $editor ) {
			// Check idling time
			$idle = $unix_now - wfTimestamp( TS_UNIX, $editor->editings_touched );

			global $wgAjaxShowEditorsTimeout;
			if ( $idle >= $wgAjaxShowEditorsTimeout ) {
				$dbw->delete(
					'editings',
					[
						'editings_page' => $title->getArticleID(),
						'editings_actor' => $editor->editings_actor,
					],
					__METHOD__
				);
				continue; // we will not show the user
			}

			if ( $first ) {
				$first = 0;
			} else {
				$wikitext .= ' ~  ';
			}

			$since = wfTimestamp( TS_DB, $editor->editings_started );
			$wikitext .= $since;

			// @todo FIXME/CHECKME: this feels a bit unnecessarily heavy to me...
			$u = User::newFromActorId( $editor->editings_actor );
			if ( !$u || !$u instanceof User ) {
				continue;
			}

			$wikitext .= ' ' . Linker::link(
				$u->getUserPage(),
				htmlspecialchars( $u->getName(), ENT_QUOTES )
			);

			$wikitext .= ' ' . wfMessage( 'ajax-se-idling', '<span>' . $idle . '</span>' )->text();
			/* @todo Eventually return a more programmatical response here...maybe something like this:
			$arr[$u->getName()] = [
				'idle' => $idle,
				'since' => $since,
				'first' => $first
			];
			*/
		}

		return $wikitext;
	}

}

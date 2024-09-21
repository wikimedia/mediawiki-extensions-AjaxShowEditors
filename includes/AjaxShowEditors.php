<?php
/**
 * Return a list of editors currently editing the article.
 */

use MediaWiki\MediaWikiServices;

class AjaxShowEditors {
	/**
	 * @param int $articleId Page ID number
	 * @param string $username Current user's username (only for registered users)
	 * @return string Properly escaped HTML suitable for output
	 */
	public static function getEditorListHTML( $articleId, $username ) {
		$articleId = intval( $articleId );

		// Validate request
		$title = Title::newFromID( $articleId );
		if ( !$title ) {
			return wfMessage( 'ajax-se-pagedoesnotexist' )->escaped();
		}

		$user = User::newFromName( $username );
		// Previously we used to check that $user was a valid User object and if not,
		// show an error message. *But* an obvious problem with that approach was that
		// it meant erroring out always in case of an anon tried to edit a page and view
		// the list of registered users currently editing the article.
		// The fix: *don't* show an error here and *do* allow getting the list from
		// DB _without_ adding any information about the current user to the DB when
		// $username is literally ''.
		// @todo FIXME: But shouldn't we store some info about anons anyway, i.e.
		// if we have multiple IP addresses trying to edit the page at the same time?
		if ( $user && $user instanceof User ) {
			// When did the user start editing?
			$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
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

			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			$dbw->replace(
				'editings',
				[ [ 'editings_page', 'editings_actor', 'editings_started' ] ],
				[
					'editings_page' => $title->getArticleID(),
					'editings_actor' => $user->getActorId(),
					'editings_started' => $userStarted,
					'editings_touched' => $dbw->timestamp(),
				],
				__METHOD__
			);
		}

		// Now we get the list of all editing users
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$res = $dbr->select(
			'editings',
			[ 'editings_actor', 'editings_started', 'editings_touched' ],
			[ 'editings_page' => $title->getArticleID() ],
			__METHOD__
		);

		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
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
				// we will not show the user
				continue;
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

			$wikitext .= ' ' . $linkRenderer->makeLink(
				$u->getUserPage(),
				$u->getName()
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

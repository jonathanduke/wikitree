<?php
/**
 *
 *
 * Created on Jan 4, 2008
 *
 * Copyright © 2008 Yuri Astrakhan "<Firstname><Lastname>@gmail.com",
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * API module to allow users to watch a page
 *
 * @ingroup API
 */
class ApiWatch extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$user = $this->getUser();
		if ( !$user->isLoggedIn() ) {
			$this->dieUsage( 'You must be logged-in to have a watchlist', 'notloggedin' );
		}
		$params = $this->extractRequestParams();
		// titles can handle basic request of 1 title,
		// but title is still supported for backward compatability
		if ( isset( $params['title'] ) ) {
			$title = Title::newFromText( $params['title'] );
			if ( !$title || $title->getNamespace() < 0 ) {
				$this->dieUsageMsg( array( 'invalidtitle', $params['title'] ) );
			}
			$res = $this->watchTitle( $title, $user, $params);
		} else {
			$pageSet = new ApiPageSet( $this );
			$pageSet->execute();
			$res = array();
			foreach ( $pageSet->getTitles() as $title ) {
				$r = $this->watchTitle( $title, $user, $params);
				$res[] = $r;
			}
		}
		$this->getResult()->addValue( null, $this->getModuleName(), $res );
	}
	private function watchTitle( $title, $user, $params ) {
		$res = array( 'title' => $title->getPrefixedText() );

		if ( $params['unwatch'] ) {
			$res['unwatched'] = '';
			$res['message'] = $this->msg( 'removedwatchtext', $title->getPrefixedText() )->title( $title )->parseAsBlock();
			$success = UnwatchAction::doUnwatch( $title, $user );
		} else {
			$res['watched'] = '';
			$res['message'] = $this->msg( 'addedwatchtext', $title->getPrefixedText() )->title( $title )->parseAsBlock();
			$success = WatchAction::doWatch( $title, $user );
		}
		if ( !$success ) {
			$this->dieUsageMsg( 'hookaborted' );
		}
		return $res;
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}

	public function needsToken() {
		return true;
	}

	public function getTokenSalt() {
		return 'watch';
	}

	public function getAllowedParams() {
		return array(
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true
			),
			'unwatch' => false,
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
		);
	}

	public function getParamDescription() {
		return array(
			'title' => 'The page to (un)watch',
			'unwatch' => 'If set the page will be unwatched rather than watched',
			'token' => 'A token previously acquired via prop=info',
		);
	}

	public function getResultProperties() {
		return array(
			'' => array(
				'title' => 'string',
				'unwatched' => 'boolean',
				'watched' => 'boolean',
				'message' => 'string'
			)
		);
	}

	public function getDescription() {
		return 'Add or remove a page from/to the current user\'s watchlist';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'notloggedin', 'info' => 'You must be logged-in to have a watchlist' ),
			array( 'invalidtitle', 'title' ),
			array( 'hookaborted' ),
		) );
	}

	public function getExamples() {
		return array(
			'api.php?action=watch&title=Main_Page' => 'Watch the page "Main Page"',
			'api.php?action=watch&title=Main_Page&unwatch=' => 'Unwatch the page "Main Page"',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/API:Watch';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}

<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\ServiceConstant;

class MailChimpService extends Service {
	/**
	 * @var \DrewM\MailChimp\MailChimp
	 */
	protected $mailChimpSDK;

	/**
	 * @return bool
	 */
	public function hasValidAuthenticationData() {
		if ( ! $this->authenticationData ) {
			return false;
		}

		$data = $this->authenticationData->getData();
		if ( ! isset( $data['access_token'] ) || ! isset( $data['dc'] ) ) {
			return false;
		}

		return true;
	}

	public function initializeNativeService() {
		try {
			$data               = $this->authenticationData->getData();
			$this->mailChimpSDK = new \DrewM\MailChimp\MailChimp( $data['access_token'] . '-' . $data['dc'] );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * @return RedirectResponse
	 */
	public function authenticate() {
		$login_url = MAILCHIMP_AUTH_URL . "?response_type=code&client_id=%s&redirect_uri=%s";

		return new RedirectResponse( 301, "RedirectResponse", sprintf(
			$login_url,
			MAILCHIMP_CLIENT_ID,
			MAILCHIMP_REDIRECT_URI
		) );
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function internalGetGroups() {
		$result = [];
		foreach ( $this->_getGroups() as $i => $row ) {
			$group = new Group();
			$group
				->setId( $row['id'] )
				->setName( $row['name'] );

			$result[ $i ] = $group;
		}

		return $result;
	}

	/**
	 * @param Group $group
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function internalGetFields( Group $group ) {
		$result = [];
		foreach ( $this->_getFields( $group->getId() ) as $i => $customField ) {
			$field = new Field();
			$field
				->setName( $customField['name'] )
				->setSlug( $customField['tag'] )
				->setRequired( $customField['required'] );

			$result[ $i ] = $field;
		}

		$emailField = new Field();
		$emailField
			->setName( 'Email' )
			->setSlug( ServiceConstant::EMAIL_FIELD )
			->setRequired( true );

		$result = array_merge( [ $emailField ], $result );

		return $result;
	}

	/**
	 * @param FieldMap $fieldMap
	 * @param $group_id
	 * @param $data
	 *
	 * @return mixed|void
	 * @throws \BrizyForms\Exception\FieldMapException
	 */
	protected function internalCreateMember( FieldMap $fieldMap, $group_id, array $data ) {
		$data = $fieldMap->transform( $data );

		$payload = [
			'email_address' => $data->getEmail(),
			'status'        => 'pending',
		];

		if ( count( $data->getFields() ) > 0 ) {
			$payload['merge_fields'] = $data->getFields();
		}

		$this->_createMember( $group_id, $payload );
	}

	/**
	 * @param FieldMap $fieldMap
	 * @param string $group_id
	 *
	 * @return FieldMap|mixed
	 * @throws \Exception
	 */
	protected function mapFields( FieldMap $fieldMap, $group_id ) {
		$existCustomFields = $this->_getFields( $group_id );

		foreach ( $fieldMap->toArray() as $fieldLink ) {
			if ( $fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD ) {
				$newCustomField = null;
				$name           = strip_tags( $fieldLink->getSourceTitle() );
				$key_exist      = array_search( $name, array_column( $existCustomFields, 'name' ) );
				if ( $key_exist === false ) {
					$payload        = [
						'name' => $name,
						'type' => 'text'
					];
					$newCustomField = $this->_createField( $group_id, $payload );
				}

				if ( $newCustomField ) {
					if ( ! isset( $newCustomField['tag'] ) ) {
						continue;
					}
					$tag = $newCustomField['tag'];
				} else {
					$tag = $existCustomFields[ $key_exist ]['tag'];
				}

				$fieldLink->setTarget( $tag );
			}
		}

		return $fieldMap;
	}

	/**
	 * @param $group_id
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function _getFields( $group_id ) {
		$customFields = $this->mailChimpSDK->get( "lists/{$group_id}/merge-fields?count=100" );

		if ( ! isset( $customFields['merge_fields'] ) ) {
			throw new ServiceException( "Invalid request" );
		}

		return $customFields['merge_fields'];
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	private function _getGroups() {
		$lists = $this->mailChimpSDK->get( 'lists?count=30' );

		if ( ! isset( $lists['lists'] ) ) {
			throw new ServiceException( "Invalid request" );
		}

		return $lists['lists'];
	}

	/**
	 * @param $group_id
	 * @param $payload
	 *
	 * @return array|false
	 */
	private function _createField( $group_id, $payload ) {
		return $this->mailChimpSDK->post( "lists/{$group_id}/merge-fields", $payload );
	}

	/**
	 * @param $group_id
	 * @param $payload
	 *
	 * @return array|false
	 */
	private function _createMember( $group_id, $payload ) {
		return $this->mailChimpSDK->post( "lists/{$group_id}/members", $payload );
	}
}
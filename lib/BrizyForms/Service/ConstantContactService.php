<?php

namespace BrizyForms\Service;

use BrizyForms\Exception\ServiceException;
use BrizyForms\Model\Account;
use BrizyForms\Model\Folder;
use BrizyForms\Model\GroupData;
use BrizyForms\ServiceFactory;
use BrizyForms\Utils\StringUtils;
use BrizyForms\FieldMap;
use BrizyForms\Model\Field;
use BrizyForms\Model\Group;
use BrizyForms\Model\RedirectResponse;
use BrizyForms\Model\Response;
use BrizyForms\ServiceConstant;
use PHPFUI\ConstantContact\Client;
use PHPFUI\ConstantContact\Definition\CustomFieldInput;
use PHPFUI\ConstantContact\V3\ContactCustomFields;

//dvrup4ri8QbXXaCTpDRkag

/**
 * Class ConstantContactService
 * @package BrizyForms\Service
 */
class ConstantContactService extends Service {

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @param FieldMap $fieldMap
	 * @param string $group_id
	 *
	 * @return mixed
	 */
	protected function mapFields( FieldMap $fieldMap, $group_id = null ) {
		$existCustomFields = $this->_getFields();
		foreach ( $fieldMap->toArray() as $fieldLink ) {
			if ( $fieldLink->getTarget() == ServiceConstant::AUTO_GENERATE_FIELD ) {
				$newCustomField = null;
				$perstag        = StringUtils::getSlug( $fieldLink->getSourceTitle() );
				$key_exist      = array_search( $fieldLink->getSourceTitle(), array_column( $existCustomFields, 'title' ) );
				if ( $key_exist === false ) {
					$payload        = [
						"title"          => $fieldLink->getSourceTitle(),
						"type"           => 1,
						"req"            => false,
						"perstag"        => "%" . strtoupper( $perstag ) . "%",
						"p[{$group_id}]" => $group_id,
					];


					$input = new CustomFieldInput(['label'=>$fieldLink->getSourceTitle(),'type'=>'string']);
					$contactCustomField = new ContactCustomFields($this->client);
					$result = $contactCustomField->post($input);
				}
				if ( $newCustomField ) {
					if ( (int) $newCustomField->success != 1 ) {
						continue;
					}
					$tag = "field[{$newCustomField->fieldid},0]";
				} else {
					$tag = "field[{$existCustomFields[$key_exist]['id']},0]";
				}
				$fieldLink->setTarget( $tag );
			}
		}

		return $fieldMap;
	}

	/**
	 * @param FieldMap $fieldMap
	 * @param null $group_id
	 * @param array $data
	 * @param bool $confirmation_email
	 *
	 * @return mixed|void
	 * @throws ServiceException
	 * @throws \BrizyForms\Exception\FieldMapException
	 */
	protected function internalCreateMember(
		FieldMap $fieldMap, $group_id = null, array $data = [], $confirmation_email = false
	) {
		$data   = $fieldMap->transform( $data );
		$status = 1;
		if ( $confirmation_email ) {
			$status = 0;
		}
		$contact      = [
			"email"               => $data->getEmail(),
			"p[{$group_id}]"      => $group_id,
			"status[{$group_id}]" => $status,
		];
		$contact      = array_merge( $contact, $data->getFields() );
		$contact_sync = $this->client->api( "contact/sync", $contact );
		if ( ! (int) $contact_sync->success ) {
			$this->logger->error( json_encode( $contact_sync ), [
				'service' => ServiceFactory::ACTIVECAMPAIGN,
				'method'  => 'internalCreateMember'
			] );
			throw new ServiceException( json_encode( $contact_sync ) );
		}
	}

	/**
	 * @param Folder|null $folder
	 *
	 * @return array|mixed
	 */
	protected function internalGetGroups( Folder $folder = null ) {

		$listEndPoint = new \PHPFUI\ConstantContact\V3\ContactLists( $this->client );
		$lists        = $listEndPoint->get();
		$response     = [];
		do {
			foreach ( (array) $lists as $key => $list ) {
				if ( is_numeric( $key ) ) {
					$group = new Group();
					$group->setId( $list->id )->setName( $list->name );
					$response[] = $group;
				}
			}
			$lists = $listEndPoint->next();
		} while ( $lists );

		return $response;
	}

	/**
	 * @param Group $group
	 *
	 * @return mixed
	 */
	protected function internalGetFields( Group $group = null ) {
		$clearFields = $this->_getFields();
		$defaults    = [
			new Field( 'Email', ServiceConstant::EMAIL_FIELD, true ),
			new Field( 'First Name', 'first_name', false ),
			new Field( 'Last Name', 'last_name', false ),
			new Field( 'Company Name', 'company_name', false ),
		];
		$response    = array_merge( $defaults, $clearFields );

		return $response;
	}

	/**
	 * @return bool
	 */
	protected function hasValidAuthenticationData() {
		if ( ! $this->authenticationData ) {
			return false;
		}
		$data = $this->authenticationData->getData();
		if ( ! isset( $data['client_key'] ) || ! isset( $data['client_secret'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return void
	 */
	protected function initializeNativeService() {
		$data         = $this->authenticationData->getData();
		$this->client = new Client( $data['client_key'], $data['client_secret'], $data['redirect_url'] );
	}

	/**
	 * @return array
	 */
	protected function internalGetAccountProperties() {
		return [
			[
				'name'  => 'client_key',
				'title' => 'API Key',
				'type'  => FieldTypes::TYPE_TEXT,
			],
			[
				'name'  => 'client_secret',
				'title' => 'Client Secret',
				'type'  => FieldTypes::TYPE_TEXT,
			],
			[
				'name'  => 'redirect_url',
				'title' => 'Redirect Url',
				'type'  => FieldTypes::TYPE_TEXT,
			],
		];
	}

	/**
	 * @param array $options
	 *
	 * @return RedirectResponse|Response|null
	 */
	public function authenticate( array $options = null ) {
		if ( ! $this->client ) {
			return new Response( 400, 'native service was not init' );
		}
		try {
			//$this->client->acquireAccessToken()

			$listEndPoint = new \PHPFUI\ConstantContact\V3\ContactLists( $this->client );
			$listEndPoint->get();
		} catch ( \Exception $e ) {
			return new Response( 401, 'Unauthenticated' );
		}

		return new Response( 200, 'Successfully authenticated' );
	}

	private function _getFields() {
		$endpoint = new \PHPFUI\ConstantContact\V3\ContactCustomFields( $this->client );
		$lists        = $endpoint->get();
		$response     = [];
		do {
			foreach ( (array) $lists as $key => $list ) {
				if ( is_numeric( $key ) ) {
					$field = new Field();
					$field->setName( $list->title )->setSlug( "field[{$list->id},0]" )->setRequired( false );
					$response[] = $field;
				}
			}
			$lists = $endpoint->next();
		} while ( $lists );

		return $response;

	}

	/**
	 * @param GroupData $groupData
	 *
	 * @return mixed
	 */
	protected function internalCreateGroup( GroupData $groupData ) {
		return null;
	}

	/**
	 * @param GroupData $groupData
	 *
	 * @return mixed
	 */
	protected function hasValidGroupData( GroupData $groupData ) {
		return true;
	}

	/**
	 * @return Account
	 * @throws ServiceException
	 */
	protected function internalGetAccount() {

		$data = $this->authenticationData->getData();

		return new Account( $data['client_key'] );
	}

	/**
	 * @return array|null
	 */
	protected function internalGetFolders() {
		return null;
	}

	/**
	 * @return array
	 */
	protected function internalGetGroupProperties() {
		return null;
	}


	/**
	 * @return boolean
	 */
	protected function internalHasConfirmation() {
		return false;
	}
}
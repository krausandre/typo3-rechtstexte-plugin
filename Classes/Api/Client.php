<?php
declare(strict_types=1);
namespace ERecht24\Er24Rechtstexte\Api;

class Client extends BaseApi
{
    /**
     * Function provides a list of all clients
     * @return ApiResponse
     */
    public function listClients() : ApiResponse
    {
        // Api Request
        $response = $this->handleResponse(
            $this->performRequest(
                $this->getApiUrl('v1/clients'),
                'GET'
            )
        );
        return $response;
    }

    /**
     * Function adds a client remotely
     * @return ApiResponse
     */
    public function addClient() : ApiResponse
    {

        // Api Request
        $response = $this->handleResponse(
            $this->performRequest(
                $this->getApiUrl('v1/clients'),
                'POST',
                $this->createRequestBody()
            )
        );

        return $response;

//        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($response);
//
//        // register default hooks
//        $this->registerAddClientHooks();
//
//        // Apply filter hooks
//        $response = apply_filters(
//            self::CLIENT_CREATED_FILTER,
//            $response
//        );
//
//        // Apply action hooks
//        do_action(
//        self::CLIENT_CREATED_ACTION,
//            $response
//        );
//
//        return $response;
    }

    /**
     * Function deletes client remotely
     * @param int $clientId
     * @return ApiResponse
     */
    public function deleteClient(int $clientId) : ApiResponse {

        // Api Request
        $response = $this->handleResponse(
            $this->performRequest(
                $this->getApiUrl('v1/clients/' . $clientId),
                'DELETE'
            )
        );
        return $response;
    }

    /**
     * Function executes test push
     * @param int $clientId
     * @return ApiResponse
     */
    public function testPushPing(int $clientId) : ApiResponse
    {

        // Api Request
        $response = $this->handleResponse(
            $this->performRequest(
                $this->getApiUrl('v1/clients/' . $clientId . '/testPush'),
                'POST'
            )
        );
        return $response;
    }

    /**
     * Function provides request body for add client method
     *
     * @return string
     */
    private function createRequestBody() : string
    {
        $request_body  = [];

        $typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();

        $request_body['push_method'] = 'GET';
        $request_body['push_uri']    = $this->domain . 'erecht24/v1/push';
        $request_body['cms']         = 'TYPO3';
        $request_body['cms_version'] = $typo3Version->getVersion();
        $request_body['plugin_name'] = 'eRecht24.de Rechtstexte für TYPO3';
        $request_body['author_mail'] = 'test@test.com';

        return json_encode($request_body, JSON_UNESCAPED_UNICODE);
    }
}

<?php
/**
 * Copyright (c) 2015 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * Class for LTI outcome service
 */
class ilExternalContentResultService
{
    /** @var ilDBInterface */
    protected $db;

    /**
     * @var string path of the plugin's base directory
     */
    protected string $plugin_path = '';

    /**
     * @var string  relative path of the plugin directory from the ILIAS directory
     */
    protected string $plugin_relative_path = 'Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent';

    /**
     * @var ilExternalContentResult|null LTI result for a user and object
     */
    protected ?ilExternalContentResult $result = null;

    /**
     * @var  array<string, mixed> properties: name => value
     */
    protected array $properties = [];

    /**
     * @var array<string, string> fields: name => value
     */
    protected array $fields = [];

    /**
     * @var string the message reference id
     */
    protected string $message_ref_id = '';

    /**
     * @var string  the requested operation
     */
    protected string $operation = '';

    /**
     * @var ilLogger
     */
    protected ilLogger $log;

    /**
     * Constructor: general initialisations
     */
    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->log = $DIC->logger()->root();
        $this->plugin_path = (string) realpath(dirname(__FILE__) . '/..');
    }

    /**
     * Handle an incoming request from the LTI tool provider
     */
    public function handleRequest()
    {
        try {
            // get the request as xml
            $xml = simplexml_load_file('php://input');
            $this->message_ref_id = (string) $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
            foreach($xml->imsx_POXBody->children() as $request) {
                $this->operation = str_replace('Request', '', $request->getName());
                $result_id = $request->resultRecord->sourcedGUID->sourcedId;
            }

            $this->result = ilExternalContentResult::getById($result_id);
            if (empty($this->result)) {
                $this->respondUnauthorized("sourcedId $result_id not found!");
                return;
            }

            // check the object status
            $this->readProperties($this->result->obj_id);
            if ($this->properties['availability_type'] == 0
                or $this->properties['lp_mode'] == 0) {
                $this->respondUnsupported();
                return;
            }

            // Verify the signature
            $this->readFields($this->properties['settings_id']);
            $result = $this->checkSignature($this->fields['KEY'], $this->fields['SECRET']);
            if ($result instanceof Exception) {
                $this->log->error($result->getMessage());
                $this->log->logStack();
                $this->respondUnauthorized($result->getMessage());
                return;
            }

            // Dispatch the operation
            switch($this->operation) {
                case 'readResult':
                    $this->readResult($request);
                    break;

                case 'replaceResult':
                    $this->replaceResult($request);
                    break;

                case 'deleteResult':
                    $this->deleteResult($request);
                    break;

                default:
                    $this->respondUnknown();
                    break;
            }
        } catch (Exception $exception) {
            $this->log->error($exception->getMessage());
            $this->log->logStack();
            $this->respondBadRequest($exception->getMessage());
        }
    }

    /**
     * Read a stored result
     * @param SimpleXMLElement $request
     */
    protected function readResult($request)
    {
        $response = $this->loadResponse('readResult.xml');
        $response = str_replace('{message_id}', md5(rand(0, 999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);
        $response = str_replace('{result}', $this->result->result, $response);

        header('Content-type: application/xml');
        echo $response;
    }

    /**
     * Replace a stored result
     * @param SimpleXMLElement $request
     */
    protected function replaceResult($request)
    {
        $result = (string) $request->resultRecord->result->resultScore->textString;
        if (!is_numeric($result)) {
            $code = "failure";
            $severity = "status";
            $description = "The result is not a number.";
        } elseif ($result < 0 or $result > 1) {
            $code = "failure";
            $severity = "status";
            $description = "The result is out of range from 0 to 1.";
        } else {
            $this->result->result = (float) $result;
            $this->result->save();

            if ($result >= $this->properties['lp_threshold']) {
                $lp_status = ilLPStatus::LP_STATUS_COMPLETED_NUM;
            } else {
                $lp_status = ilLPStatus::LP_STATUS_FAILED_NUM;
            }
            $lp_percentage = 100 * $result;
            ilExternalContentLPStatus::trackResult($this->result->usr_id, $this->result->obj_id, $lp_status, $lp_percentage);

            $code = "success";
            $severity = "status";
            $description = sprintf("Score for %s is now %s", $this->result->id, $this->result->result);
        }

        $response = $this->loadResponse('replaceResult.xml');
        $response = str_replace('{message_id}', md5(rand(0, 999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);
        $response = str_replace('{code}', $code, $response);
        $response = str_replace('{severity}', $severity, $response);
        $response = str_replace('{description}', $description, $response);

        header('Content-type: application/xml');
        echo $response;
    }

    /**
     * Delete a stored result
     * @param SimpleXMLElement $request
     */
    protected function deleteResult($request)
    {
        $this->result->result = null;
        $this->result->save();

        $lp_status = ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
        $lp_percentage = 0;
        ilExternalContentLPStatus::trackResult($this->result->usr_id, $this->result->obj_id, $lp_status, $lp_percentage);

        $code = "success";
        $severity = "status";

        $response = $this->loadResponse('deleteResult.xml');
        $response = str_replace('{message_id}', md5(rand(0, 999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);
        $response = str_replace('{code}', $code, $response);
        $response = str_replace('{severity}', $severity, $response);

        header('Content-type: application/xml');
        echo $response;
    }


    /**
     * Load the XML template for the response
     * @param string    $a_name file name
     * @return string   file content
     */
    protected function loadResponse($a_name)
    {
        return file_get_contents($this->plugin_path . '/responses/' . $a_name);
    }


    /**
     * Send a response that the operation is not supported
     * This depends on the status of the object
     */
    protected function respondUnsupported()
    {
        $response = $this->loadResponse('unsupported.xml');
        $response = str_replace('{message_id}', md5(rand(0, 999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);

        header('Content-type: application/xml');
        echo $response;
    }

    /**
     * Send a "unknown operation" response
     */
    protected function respondUnknown()
    {
        $response = $this->loadResponse('unknown.xml');
        $response = str_replace('{message_id}', md5(rand(0, 999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);

        header('Content-type: application/xml');
        echo $response;
    }


    /**
     * Send a "bad request" response
     * @param string  $message response message
     */
    protected function respondBadRequest($message = null)
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-type: text/plain');
        if (isset($message)) {
            echo $message;
        } else {
            echo 'This is not a well-formed LTI Basic Outcomes Service request.';
        }
    }


    /**
     * Send an "unauthorized" response
     * @param   string $message response message
     *
     */
    protected function respondUnauthorized($message = null)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-type: text/plain');
        if (isset($message)) {
            echo $message;
        } else {
            echo 'This request could not be authorized.';
        }
    }


    /**
     * Read the external content object properties
     *
     * @param integer $a_obj_id
     */
    private function readProperties($a_obj_id)
    {
        $query = "SELECT * FROM xxco_data_settings WHERE obj_id =" . $this->db->quote($a_obj_id, 'integer');
        $res = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($res)) {
            $this->properties = $row;
        }
    }

    /**
     * Read the external content object fields
     *
     * @param integer $a_settings_id
     */
    private function readFields($a_settings_id)
    {
        $query = "SELECT * FROM xxco_data_values WHERE settings_id =" . $this->db->quote($a_settings_id, 'integer');
        $res = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($res)) {
            $this->fields[$row['field_name']] = $row['field_value'];
        }
    }

    /**
     * Check the reqest signature
     * @return mixed	Exception or true, , not defined because of error in php 7.4
     */
    private function checkSignature($a_key, $a_secret)
    {
        $store = new TrivialOAuthDataStore();
        $store->add_consumer($this->fields['KEY'], $this->fields['SECRET']);

        $server = new \ILIAS\LTIOAuth\OAuthServer($store);
        $method = new \ILIAS\LTIOAuth\OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);

        // get the correct request url for checking the signature
        // this must corresond to the lis_outcome_service_url provided with the call of the tool
        // the variable ILAS_RESULT_URL is used for this
        // see \ilObjExternalContent::getResultUrl
        // The port and scheme might be wrong when HTTP is terminated by a load balancer
        // In this case the http_path in ilias.ini.php should be set correctly
        $result_url = str_replace($this->plugin_relative_path, '', ILIAS_HTTP_PATH);
        $result_url = rtrim($result_url, '/') . '/' . $this->plugin_relative_path . '/result.php?client_id=' . CLIENT_ID;
        $request = \ILIAS\LTIOAuth\OAuthRequest::from_request(null, $result_url);
        //$request = \ILIAS\LTIOAuth\OAuthRequest::from_request();

        // produces 'invalid signature in verify_request
        // seems not to be needed in ILIAS 8 because from_request does not use get_magic_quotes_gpc() anymore
        // $request = \ILIAS\LTIOAuth\OAuthRequest::from_request(null, null, $this->getParameters());
        try {
            $server->verify_request($request);
        } catch (Exception $e) {
            return $e;
        }
        return true;
    }

    /**
     * Get the Parameters from an OAuthRequest
     * Extracted from OAuthRequest::from_request to omit the deprecated get_magic_quotes_gpc()
     * @deprecated seems not to be needed in ILIAS 8 because from_request does not use get_magic_quotes_gpc() anymore
     * @see OAuthRequest::from_request
     * @return array
     */
    private function getParameters()
    {
        // Find request headers
        $request_headers = \ILIAS\LTIOAuth\OAuthUtil::get_headers();

        // Parse the query-string to find GET parameters
        $parameters = \ILIAS\LTIOAuth\OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

        $ourpost = (array) $_POST;

        // Add POST Parameters if they exist
        $parameters = array_merge($parameters, $ourpost);

        // We have a Authorization-header with OAuth data. Parse the header
        // and add those overriding any duplicates from GET or POST
        if (substr((string) ($request_headers['Authorization'] ?? ''), 0, 6) == "OAuth ") {
            $header_parameters = \ILIAS\LTIOAuth\OAuthUtil::split_header(
                $request_headers['Authorization']
            );
            $parameters = array_merge($parameters, $header_parameters);
        }

        return $parameters;
    }
}

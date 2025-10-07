<?php

/**
 * Copyright (c) 2015 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

use ceLTIc\LTI\OAuth\OAuthRequest;
use ceLTIc\LTI\OAuth\OAuthServer;
use ceLTIc\LTI\OAuth\OAuthSignatureMethod_HMAC_SHA1;
use ceLTIc\LTI\OAuthDataStore;
use ceLTIc\LTI\OAuth\OAuthUtil;

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
            foreach ($xml->imsx_POXBody->children() as $request) {
                $this->operation = str_replace('Request', '', $request->getName());
                $result_id = $request->resultRecord->sourcedGUID->sourcedId;
            }

            $this->result = ilExternalContentResult::getById($result_id);
            if (empty($this->result)) {
                $this->log->error("ExternalContent Result Service: sourcedId $result_id not found!");
                $this->respondUnauthorized();
                return;
            }

            // check the object status
            $this->readProperties($this->result->obj_id);
            if ($this->properties['availability_type'] == 0
                or $this->properties['lp_mode'] == 0) {
                $this->log->error("ExternalContent Result Service: storing results not allowed for obj_id" . $this->result->obj_id);
                $this->respondUnsupported();
                return;
            }

            // Verify the signature (will throw an exception)
            $this->readFields($this->properties['settings_id']);
            $this->checkSignature($this->fields['KEY'], $this->fields['SECRET']);

            // Dispatch the operation
            switch ($this->operation) {
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
            $this->log->error('ExternalContent Result Service: Incoming request failed: ' . $exception->getMessage());
            $this->log->debug($exception->getTraceAsString());
            $this->respondBadRequest();
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
     * Check the request signature
     * @throws Exception
     */
    private function checkSignature($a_key, $a_secret): void
    {
        $platform = new ilLTIPlatform();

        $platform->setKey($a_key);
        $platform->setSecret($a_secret);

        // This should be the ID of a registered platform, when ILIAS is the tool
        // Here we need an ID for the external tool when ILIAS is the platform
        // This is needed to check and save the nonce of result service calls from the external tool
        // As a workaround, we use the object id of the consumer object, a nonce will be saved with this consumer_pk
        $platform->setRecordId($this->result->obj_id);

        $store = new OAuthDataStore($platform);

        $server = new OAuthServer($store);
        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);

        $server = new OAuthServer($store);
        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);

        // Extract the parameters here to omit the request body from building the signature
        // see https://www.imsglobal.org/spec/lti-bo/v1p1
        // "The service endpoint must accept any well-formed request with properly formed headers that pass security checks"
        $request_headers = OAuthUtil::get_headers();
        if (isset($request_headers['Authorization']) && str_starts_with($request_headers['Authorization'], 'OAuth ')) {
            $parameters = OAuthUtil::split_header($request_headers['Authorization']);
        }

        // get the correct request url for checking the signature
        // this must correspond to the lis_outcome_service_url provided with the call of the tool
        // the variable ILIAS_RESULT_URL is used for this
        // see \ilObjExternalContent::getResultUrl
        // The port and scheme might be wrong when HTTP is terminated by a load balancer
        // In this case the http_path in ilias.ini.php should be set correctly
        $result_url = str_replace($this->plugin_relative_path, '', ILIAS_HTTP_PATH);
        $result_url = rtrim($result_url, '/') . '/' . $this->plugin_relative_path . '/result.php?client_id=' . CLIENT_ID;
        $request = OAuthRequest::from_request(null, $result_url, $parameters ?? []);

        // Don't catch an exception, give the caller a chance to handle it
        $server->verify_request($request);
    }
}

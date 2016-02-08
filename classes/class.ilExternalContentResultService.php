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
    /**
     * @var string  path of the plugin's base directory
     */
    protected $plugin_path = '';

    /**
     * @var ilExternalContentResult
     */
    protected $result = null;

    /**
     * @var  Array properties: name => value
     */
    protected $properties = array();

    /**
     * @var Array fields: name => value
     */
    protected $fields = array();

    /**
     * @var string the message reference id
     */
    protected $message_ref_id = '';
    /**
     * @var string  the requested operation
     */
    protected $operation = '';


    /**
     * Constructor: general initialisations
     */
    public function __construct()
    {
        $this->plugin_path = realpath(dirname(__FILE__).'/..');
    }

    /**
     * Handle an incoming request from the LTI tool provider
     */
    public function handleRequest()
    {
        try
        {
            // get the request as xml
            $xml = simplexml_load_file('php://input');
            $this->message_ref_id = (string) $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
            $request = current($xml->imsx_POXBody->children());
            $this->operation = str_replace('Request','', $request->getName());
            $result_id = $request->resultRecord->sourcedGUID->sourcedId;

            require_once ($this->plugin_path.'/classes/class.ilExternalContentResult.php');
            $this->result = ilExternalContentResult::getById($result_id);
            if (empty($this->result))
            {
                $this->respondUnauthorized("sourcedId $result_id not found!");
                return;
            }

            // check the object status
            $this->readProperties($this->result->obj_id);
            if ($this->properties['availability_type'] == 0
                or $this->properties['lp_mode'] == 0)
            {
                $this->respondUnsupported();
                return;
            }

            // Verify the signature
            $this->readFields($this->result->obj_id);
            $this->checkSignature($this->fields['KEY'], $this->fields['SECRET']);

            // Dispatch the operation
            switch($this->operation)
            {
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
        }
        catch (Exception $exception)
        {
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
        $response = str_replace('{message_id}', md5(rand(0,999999999)), $response);
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
        if (!is_numeric($result))
        {
            $code = "failure";
            $severity = "status";
            $description = "The result is not a number.";
        }
        elseif ($result < 0 or $result > 1)
        {
            $code = "failure";
            $severity = "status";
            $description = "The result is out of range from 0 to 1.";
        }
        else
        {
            $this->result->result = (float) $result;
            $this->result->save();

            require_once($this->plugin_path.'/classes/class.ilExternalContentLPStatus.php');
            if ($result >= $this->properties['lp_threshold'])
            {
                $lp_status = ilExternalContentLPStatus::LP_STATUS_COMPLETED_NUM;
            }
            else
            {
                $lp_status = ilExternalContentLPStatus::LP_STATUS_FAILED_NUM;
            }
            $lp_percentage = 100 * $result;
            ilExternalContentLPStatus::trackResult($this->result->usr_id, $this->result->obj_id, $lp_status, $lp_percentage);

            $code = "success";
            $severity = "status";
            $description = sprintf("Score for %s is now %s", $this->result->id, $this->result->result);
        }

        $response = $this->loadResponse('replaceResult.xml');
        $response = str_replace('{message_id}', md5(rand(0,999999999)), $response);
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

        require_once($this->plugin_path.'/classes/class.ilExternalContentLPStatus.php');
        $lp_status = ilExternalContentLPStatus::LP_STATUS_IN_PROGRESS_NUM;
        $lp_percentage = 0;
        ilExternalContentLPStatus::trackResult($this->result->usr_id, $this->result->obj_id, $lp_status, $lp_percentage);

        $code = "success";
        $severity = "status";

        $response = $this->loadResponse('deleteResult.xml');
        $response = str_replace('{message_id}', md5(rand(0,999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);
        $response = str_replace('{code}', $code, $response);
        $response = str_replace('{severity}', $severity, $response);

        header('Content-type: application/xml');
        echo $response;
    }


    /**
     * Load the XML template for the response
     * @param string    file name
     * @return string   file content
     */
    protected function loadResponse($a_name)
    {
        return file_get_contents($this->plugin_path .'/responses/'.$a_name);
    }


    /**
     * Send a response that the operation is not supported
     * This depends on the status of the object
     */
    protected function respondUnsupported()
    {
        $response = $this->loadResponse('unsupported.xml');
        $response = str_replace('{message_id}', md5(rand(0,999999999)), $response);
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
        $response = str_replace('{message_id}', md5(rand(0,999999999)), $response);
        $response = str_replace('{message_ref_id}', $this->message_ref_id, $response);
        $response = str_replace('{operation}', $this->operation, $response);

        header('Content-type: application/xml');
        echo $response;
    }


    /**
     * Send a "bad request" response
     * @param string  response message
     */
    protected function respondBadRequest($message = null)
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-type: text/plain');
        if (isset($message))
        {
            echo $message;
        }
        else
        {
            echo 'This is not a well-formed LTI Basic Outcomes Service request.';
        }
    }


    /**
     * Send an "unauthorized" response
     * @param   string response message
     *
     */
    protected function respondUnauthorized($message = null)
    {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-type: text/plain');
        if (isset($message))
        {
            echo $message;
        }
        else
        {
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
        global $ilDB;

        $query = "SELECT * FROM xxco_data_settings WHERE obj_id =" . $ilDB->quote($a_obj_id);
        $res = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($res))
        {
            $this->properties = $row;
        }
    }

    /**
     * Read the external content object fields
     *
     * @param integer $a_obj_id
     */
    private function readFields($a_obj_id)
    {
        global $ilDB;

        $query = "SELECT * FROM xxco_data_values WHERE obj_id =" . $ilDB->quote($a_obj_id);
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($res))
        {
            $this->fields[$row['field_name']] = $row['field_value'];
        }
    }

    /**
     * Check the reqest signature
     */
    private function checkSignature($a_key, $a_secret)
    {
        require_once ($this->plugin_path.'/lib/OAuth.php');
        require_once ($this->plugin_path.'/lib/TrivialOAuthDataStore.php');

        $store = new TrivialOAuthDataStore();
        $store->add_consumer($this->fields['KEY'], $this->fields['SECRET']);

        $server = new OAuthServer($store);
        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);

        $request = OAuthRequest::from_request();
        try
        {
            $server->verify_request($request);
        }
        catch (Exception $e)
        {
            $this->respondUnauthorized($e->getMessage());
            return;
        }
    }
} 
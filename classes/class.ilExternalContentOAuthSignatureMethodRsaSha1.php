<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE
 */

class ilExternalContentOAuthSignatureMethodRsaSha1 extends \ILIAS\LTIOAuth\OAuthSignatureMethod_RSA_SHA1
{
    protected function fetch_public_cert(&$request)
    {
        // not implemented yet, ideas are:
        // (1) do a lookup in a table of trusted certs keyed off of consumer
        // (2) fetch via http using a url provided by the requester
        // (3) some sort of specific discovery code based on request
        //
        // either way should return a string representation of the certificate
        throw new Exception("fetch_public_cert not implemented");
    }

    protected function fetch_private_cert(&$request)
    {
        // not implemented yet, ideas are:
        // (1) do a lookup in a table of trusted certs keyed off of consumer
        //
        // either way should return a string representation of the certificate
        throw new Exception("fetch_private_cert not implemented");
    }

}
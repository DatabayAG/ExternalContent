<?php

declare(strict_types=1);

use ceLTIc\LTI\OAuth\OAuthDataStore;
use ceLTIc\LTI\OAuth\OAuthToken;
use ceLTIc\LTI\OAuth\OAuthConsumer;

class ilExternalContentOAuthDataStore extends OAuthDataStore
{
    private array $consumers = [];

    public function add_consumer(string $consumer_key, string $consumer_secret): void
    {
        $this->consumers[$consumer_key] = new OauthConsumer($consumer_key, $consumer_secret);
    }

    public function lookup_consumer(string $consumer_key): \ceLTIc\LTI\OAuth\OAuthConsumer
    {
        return $this->consumers[$consumer_key] ?? new OAuthConsumer($consumer_key, '');
    }

    public function lookup_token(
        \ceLTIc\LTI\OAuth\OAuthConsumer $consumer,
        ?string $token_type,
        ?string $token
    ): \ceLTIc\LTI\OAuth\OAuthToken {
        return new OAuthToken('', '');
    }

    public function lookup_nonce(
        \ceLTIc\LTI\OAuth\OAuthConsumer $consumer,
        \ceLTIc\LTI\OAuth\OAuthToken $token,
        string $nonce,
        string $timestamp
    ): bool {
        return false;
    }

    public function new_request_token(\ceLTIc\LTI\OAuth\OAuthConsumer $consumer, ?string $callback = null): ?string
    {
        return null;
    }

    public function new_access_token(
        string $token,
        \ceLTIc\LTI\OAuth\OAuthConsumer $consumer,
        ?string $verifier = null
    ): ?string {
        return null;
    }
}

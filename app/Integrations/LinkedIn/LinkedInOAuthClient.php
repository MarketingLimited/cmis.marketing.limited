<?php

namespace App\Integrations\LinkedIn;

use App\Integrations\Base\OAuth2Client;

/**
 * LinkedIn OAuth Client
 *
 * Handles OAuth 2.0 authentication for LinkedIn Marketing API
 */
class LinkedInOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://www.linkedin.com/oauth/v2/authorization';
    protected string $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

    protected array $scopes = [
        'r_liteprofile',
        'r_emailaddress',
        'w_member_social',
        'r_organization_social',
        'w_organization_social',
        'rw_organization_admin',
    ];

    public function __construct(?array $config = null)
    {
        parent::__construct($config ?? [
            'client_id' => config('services.linkedin.client_id'),
            'client_secret' => config('services.linkedin.client_secret'),
            'redirect_uri' => config('services.linkedin.redirect_uri'),
        ]);
    }
}

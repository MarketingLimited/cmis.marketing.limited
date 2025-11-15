# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN_HERE}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can obtain your API token by authenticating via the /api/login endpoint with your credentials. The token should be included in the Authorization header as a Bearer token.

# Deeplinks Into the Admin Interface

OpenDXP supports deeplinks that open a specific element directly inside the admin UI
from an external application.

## URL Schema

```
https://YOUR-HOST/admin/login/deeplink?TYPE_ID_SUBTYPE
```

## Examples

### Documents

```text
https://acme.com/admin/login/deeplink?document_123_page
https://acme.com/admin/login/deeplink?document_45_snippet
https://acme.com/admin/login/deeplink?document_67_link
https://acme.com/admin/login/deeplink?document_8_hardlink
https://acme.com/admin/login/deeplink?document_9_email
```

### Assets

```text
https://acme.com/admin/login/deeplink?asset_23_image
https://acme.com/admin/login/deeplink?asset_34_document
https://acme.com/admin/login/deeplink?asset_56_folder
https://acme.com/admin/login/deeplink?asset_78_video
```

### Data Objects

```text
https://acme.com/admin/login/deeplink?object_24_object
https://acme.com/admin/login/deeplink?object_98_variant
https://acme.com/admin/login/deeplink?object_66_folder
```

## Behaviour

If the user is not yet logged in, the deeplink URL redirects to the login page first and
then opens the target element after successful authentication.
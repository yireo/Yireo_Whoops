# Usage
This module replaces the default error and exception handling of Magento 2, and replaces it with [Whoops](https://filp.github.io/whoops/). If you want to know what Whoops is, check out their site. There is no usage. This module replaces the default `Magento\Framework\App\Http` class with its own and then adds in Whoops. This means that whenever an exception or error is not caught in the code, it will be outputted using Whoops.

You can test for this yourself by adding some dummy code somewhere in Magento 2:

    trigger_error('test');
    
or:

    throw new RuntimeException('test');

That's how we tested things.

## Security
Since version 0.2.6, the following security measures apply:

- Whoops is only used in the **Developer Mode**. The setting **Override Production Mode error reporting** is ignored, because it showed server data, environment variables, cookies and session data to every visitor;
- When **Stores > Configuration > Advanced > Developer > Developer Client Restrictions > Allowed IPs** (`dev/restrict/allow_ips`) is set, Whoops is only shown to these IPs (or hostnames). Other visitors get the regular Magento error page;
- Sensitive values (like the `Authorization` header, the session cookies, login passwords and environment variables with names like `*PASSWORD*`, `*SECRET*`, `*TOKEN*` or `*_KEY`) are hidden on the Whoops page. Add more keys via the DI argument `hiddenSuperglobalKeys` of `Yireo\Whoops\Plugin\HttpApp`, or change the regular expression in the DI argument `hiddenKeyPattern`;
- The arguments of the functions in the stack trace are not shown on the Whoops page, because they often contain passwords or other credentials;
- Only the editors listed in the configuration are accepted. Any other value of `yireo_whoops/settings/editor` is ignored.

See the security advisories [YIREO-WHOOPS-2026-001](https://github.com/yireo/Yireo_Whoops/blob/master/security-advisories/YIREO-WHOOPS-2026-001.yaml) and [YIREO-WHOOPS-2026-002](https://github.com/yireo/Yireo_Whoops/blob/master/security-advisories/YIREO-WHOOPS-2026-002.yaml).


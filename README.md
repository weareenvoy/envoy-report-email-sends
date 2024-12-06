# Envoy Report Email Sends

### Setting up the Heartbeat Cron Job

Set up a server-side heartbeat cron job to call at regular intervals to ensure WordPress cron events run reliably. This will trigger events like `envoy_report_email_send_cron_hook`.

1. **Edit the crontab** by running the following command on your server:

   ```bash
   crontab -e
   ```

2. **Add the following line to trigger WordPress cron every 5 minutes (adjust the frequency as needed):**

   ```bash
   */5 * * * * /usr/local/bin/wp cron event run --due-now --path=/www/{wp-xxx.xxx.com}/current/web/wp >> /www/{wp-xxx.xxx.com}/shared/log/wp-cron.log 2>&1
   ```

### How do I install this plugin using composer?

Add this to your project's existing `composer.json`:
```
  "repositories": [

    ...

    {
      "type": "vcs",
      "url": "https://github.com/weareenvoy/wordpress-plugin-envoy-report-email-sends.git"
    }

    ...

  ],
```

Then from the terminal, run:

```
composer require envoy/envoy-report-email-sends;
```

The plugin files will be placed into your project directory at:
```
/web/app/plugins/envoy-report-email-sends
```

Add this to your project's `.gitignore`:

```
**/plugins/envoy-report-email-sends
```

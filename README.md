# Envoy Report Email Sends

# How do I install this plugin using composer?

Add this to your project's existing `composer.json`:
```
  "repositories": [

    ...

    {
      "type": "vcs",
      "url": "https://github.com/weareenvoy/envoy-report-email-sends.git"
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

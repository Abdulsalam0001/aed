# AEDMedia website

A responsive multi-page website for AEDMedia, a Los Angeles-based live entertainment and event production company.

## Pages
- `index.html` — Home
- `about.html` — About AEDMedia
- `productions.html` — Production capabilities and portfolio placeholder
- `bookings.html` — Bookings and partnerships
- `contact.html` — Contact form

## Run locally
Open `index.html` in a browser, or serve the folder with a PHP-enabled local server to test the contact endpoint.

## Contact form and private mail configuration

The contact form posts to `contact.php`. The endpoint sends enquiries using PHP `mail()`; it does not accept a recipient address from the visitor.

For security, the real configuration file must be stored **outside the public website directory**. If your website files are in `/home/ACCOUNT/public_html/`, create:

`/home/ACCOUNT/private/db.php`

Use this structure, replacing the example addresses with your own:

```php
<?php
declare(strict_types=1);

return [
    'contact_to' => 'YOUR_PRIVATE_ADMIN_INBOX@example.com',
    'from_email' => 'website@aemedia.co',
];
```

The `contact_to` value is the private inbox that receives website enquiries. The `from_email` value must be an address authorized by your hosting provider's mail service (ideally an address on your domain). Keep the actual `db.php` outside `public_html`; do not commit it to GitHub or place it in a publicly accessible folder. Restrict its file permissions (typically `600` where supported).

The PHP endpoint loads that file from the sibling `private` directory. If your hosting layout is different, adjust the `$configPath` near the top of `contact.php` to the correct absolute path outside the document root.

The endpoint also accepts POST only, checks the browser Origin when supplied, limits request size, validates fields, uses a hidden honeypot, and allows at most three attempts per IP in a 15-minute window. PHP `mail()` must be enabled and configured by the hosting provider. The hosting administrator should test delivery and review mail logs after setup.

## Hosting
The site uses HTML, CSS, JavaScript and PHP. The contact form requires a PHP-enabled host; it will not send mail when deployed as static-only GitHub Pages or Cloudflare Pages.

# AEDMedia website

A responsive multi-page static website for AEDMedia, a Los Angeles-based live entertainment and event production company.

## Pages
- `index.html` — Home
- `about.html` — About AEDMedia
- `productions.html` — Production capabilities and portfolio placeholder
- `bookings.html` — Bookings and partnerships
- `contact.html` — Contact form and email details

## Run locally
Open `index.html` in a browser, or serve the folder with any static file server.

## Contact form
The contact form uses a `mailto:` handoff to `contact@aemedia.co`. It pre-fills an email in the visitor's email application; it does not send or store submissions on the server. For direct web submissions, connect a trusted form backend or server-side email endpoint.

## Content notes
The site does not invent AEDMedia's company history, client list, or production credits. Add confirmed company milestones, client logos and event photography when supplied.


## Private PHP contact mail

The contact form posts to `contact.php`, which sends mail using PHP `mail()`. The recipient is never accepted from the visitor and is not hard-coded in the public repository.

Configure these environment variables in the hosting control panel (or the hosting provider's private PHP environment configuration):

- `AEDMEDIA_CONTACT_TO`: the private administrator inbox that receives enquiries.
- `AEDMEDIA_FROM_EMAIL`: a sender address on a domain authorized by the hosting mail service.

Do not put recipient settings, SMTP credentials, or other secrets in public files. The endpoint accepts POST only, checks the browser Origin when supplied, limits request size, validates fields, uses a hidden honeypot, and allows at most three attempts per IP in a 15-minute window. It fixes the recipient on the server, so this is not an open mail relay. PHP `mail()` must be enabled and configured by the hosting provider. The hosting administrator should also configure outbound mail limits and monitor mail logs.

## Hosting
This is plain HTML, CSS and PHP and can be hosted on a PHP-enabled web host. The contact form requires PHP with the mail function enabled.
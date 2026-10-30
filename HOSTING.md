# Hosting & clean URLs

The application lives in `public/`, but you don't want visitors to see
`.../public` in the address. There are three ways to get a clean root URL
(`https://sub.example.com/` or `http://localhost:8004/`):

## 1. Local development (PHP built-in server)
From the project root:

```
php -S localhost:8004 router.php
```

`router.php` serves the files in `public/` and routes everything else through
the app, so `http://localhost:8004` works with no `/public` in the URL.

## 2. Subdomain / shared hosting — point the document root at `public/`
In your hosting panel, when creating the subdomain, set its **document root**
to `.../Ujuzi/public`. This is the cleanest option and needs nothing else.

## 3. Subdomain / shared hosting — document root is the project folder
If the panel points the subdomain at the project root (not `public/`), the
root **`.htaccess`** maps every request into `public/` for you. Then set the
site address so generated links don't include `/public`:

- Open `app/logic_ujuzi/app/Core/Database.php` and set
  `'APP_URL' => 'https://sub.example.com'` (no trailing slash, no `/public`),
  **or** set it on the Setup → Platform step.

That's it — links, assets, emails and certificates all use that address.

`index.php` at the root is only a fallback for hosts without mod_rewrite; it
runs the app but static assets still need option 2 or 3 above.

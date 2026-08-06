# News app

Simple `news` app for local development.

- Place the `news` folder under `apps/` (already done).
- Ensure `apps/news/data/` is writable by the web server so the content file `news.json` can be updated.
- Open the page in your browser while logged in to ownCloud (ideally as admin):

  http://<your-owncloud-host>/index.php/apps/news/page.php

Notes:
- This is a minimal, self-contained admin page that stores two entries in `apps/news/data/news.json`.
- You can adapt the HTML and CSS in `page.php` to match more closely the existing UI components in this codebase.

# Word Orb Vocabulary -- WordPress Plugin

Embed vocabulary cards, interactive quizzes, and structured lessons from the Word Orb API into any WordPress site.

## Submission Guide for WordPress.org Plugin Directory

### Prerequisites

1. **WordPress.org account** -- Register at [login.wordpress.org](https://login.wordpress.org/register).
2. **Subversion (SVN)** installed on your machine.
3. **Plugin tested** on WordPress 5.8+ with PHP 7.4+.
4. **No external dependencies** -- This plugin is self-contained (no Composer, no npm build step).

### Pre-Submission Checklist

- [ ] Plugin header in `wordorb.php` includes all required fields (Name, Description, Version, Author, License).
- [ ] `readme.txt` follows [WordPress.org readme standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/).
- [ ] No references to "premium" or "pro" versions in the plugin.
- [ ] All strings are translatable via `__()` and `esc_html__()`.
- [ ] Text domain matches the plugin slug (`wordorb`).
- [ ] No external CDN calls -- all assets are local.
- [ ] API key is stored securely and never exposed in frontend HTML/JS.
- [ ] Plugin passes the [Plugin Check](https://wordpress.org/plugins/plugin-check/) tool.
- [ ] Tested with `WP_DEBUG` enabled and no warnings/errors.

### Submitting to WordPress.org

1. **Request hosting** at [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/).
   - Plugin name: `WordOrb Vocabulary`
   - Plugin slug: `wordorb`
   - Upload the plugin ZIP.

2. **Wait for review** -- The plugin review team will review your code. This typically takes 1-5 business days.

3. **Receive SVN access** -- Once approved, you will get an SVN repository URL like:
   ```
   https://plugins.svn.wordpress.org/wordorb/
   ```

4. **Check out the repository:**
   ```bash
   svn co https://plugins.svn.wordpress.org/wordorb/ wordorb-svn
   cd wordorb-svn
   ```

5. **Copy plugin files into `trunk/`:**
   ```bash
   cp -r /path/to/wordorb/* trunk/
   ```

6. **Tag the release:**
   ```bash
   svn cp trunk tags/1.0.0
   ```

7. **Add and commit:**
   ```bash
   svn add trunk/* tags/1.0.0/*
   svn ci -m "Initial release 1.0.0" --username your-wp-username
   ```

### Updating the Plugin

1. Make changes in `trunk/`.
2. Update the `Stable tag` in `readme.txt` and `Version` in `wordorb.php`.
3. Create a new tag: `svn cp trunk tags/1.0.1`
4. Commit: `svn ci -m "Release 1.0.1"`

### Adding Screenshots

Place screenshot files in `assets/` at the SVN root (not inside `trunk/`):

```
assets/
  screenshot-1.png   (Word card)
  screenshot-2.png   (Quiz)
  screenshot-3.png   (Lesson)
  screenshot-4.png   (Settings)
  screenshot-5.png   (Widget)
  screenshot-6.png   (Gutenberg block)
  banner-772x250.png (Plugin directory banner)
  icon-256x256.png   (Plugin icon)
```

### Directory Structure

```
wordorb/
  wordorb.php          Main plugin file
  readme.txt           WordPress.org format readme
  assets/
    css/
      wordorb.css      Frontend styles
    js/
      wordorb.js       Frontend quiz/lesson interactivity
```

### Support

- API Documentation: [wordorb.ai/docs](https://wordorb.ai/docs)
- Plugin Issues: Use the WordPress.org support forum after publishing.

=== WordOrb Vocabulary ===
Contributors: lessonoftheday
Tags: vocabulary, dictionary, education, quiz, language-learning
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed rich vocabulary cards, interactive quizzes, and structured 5-phase lessons from the WordOrb API. 162K words, 47 languages, 226K lessons.

== Description ==

**WordOrb Vocabulary** connects your WordPress site to the [WordOrb API](https://wordorb.ai) -- dictionary infrastructure for 162,000 English words with definitions, IPA pronunciation, etymology, and translations in 47 languages.

= Features =

* **Word Card Shortcode** -- `[wordorb_word word="courage"]` renders a beautiful vocabulary card with definition, pronunciation, etymology, and translations.
* **Quiz Shortcode** -- `[wordorb_quiz day="42" track="learn" count="5"]` renders an interactive multiple-choice and open-ended quiz.
* **Lesson Shortcode** -- `[wordorb_lesson day="1" track="learn" age="adult"]` renders a 5-phase lesson (hook, story, wonder, action, wisdom) with tabbed navigation.
* **Word of the Day Widget** -- Sidebar widget that displays the daily vocabulary lesson summary.
* **Gutenberg Block** -- Drag-and-drop WordOrb Word Card block for the block editor.
* **REST API Proxy** -- Server-side proxy at `/wp-json/wordorb/v1/` keeps your API key safe.
* **Transient Caching** -- API responses are cached for 1 hour (configurable) to reduce API calls.

= API Coverage =

* **Word Orb** -- 162K words, 47 languages, IPA, etymology, part of speech
* **Lesson Orb** -- 226K structured lessons, 4 tracks, 3 age groups, 10 archetypes
* **Quiz Orb** -- 21K interactive assessments, 6 question types

= Use Cases =

* Educational blogs and language-learning sites
* Vocabulary builders and flashcard pages
* Daily word features on any WordPress site
* Interactive quizzes for student engagement

== Installation ==

1. Upload the `wordorb` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > WordOrb** and enter your API key.
4. Use shortcodes, the widget, or the Gutenberg block in your content.

= Getting an API Key =

1. Visit [wordorb.ai](https://wordorb.ai) and sign up.
2. The free tier includes 50 API calls per day.
3. Paid plans start at $49/month for 5,000 daily calls.
4. Copy your API key (starts with `wo_`) into the plugin settings.

== Frequently Asked Questions ==

= Where do I get an API key? =

Sign up at [wordorb.ai](https://wordorb.ai). The free tier provides 50 daily API calls, enough for most personal blogs.

= Is the API key exposed to visitors? =

No. The plugin proxies all API calls through your WordPress server via the REST API. Your API key never appears in frontend code.

= How does caching work? =

API responses are stored as WordPress transients. The default TTL is 1 hour, configurable in Settings > WordOrb. This means each unique word lookup only calls the API once per hour.

= Can I customize the appearance? =

Yes. The plugin uses CSS custom properties (variables). Override them in your theme's stylesheet:

    :root {
        --wordorb-primary: #8b5cf6;
        --wordorb-radius: 12px;
    }

= What languages are supported? =

The Word Orb API supports translations in 47 languages including Spanish, French, German, Japanese, Korean, Arabic, Hindi, and many more.

= Does the quiz shortcode support different question types? =

Yes. Use the `type` attribute: `[wordorb_quiz day="1" track="learn" type="mc"]`. Valid types: `quiz`, `mc` (multiple choice), `tf` (true/false), `ab` (A/B), `open`, `reflection`.

== Screenshots ==

1. Word card rendered by `[wordorb_word]` shortcode.
2. Interactive quiz with multiple-choice questions.
3. 5-phase lesson with tabbed navigation.
4. Settings page with API key configuration.
5. Word of the Day sidebar widget.
6. Gutenberg block in the editor.

== Changelog ==

= 1.0.0 =
* Initial release.
* Word card shortcode with definition, IPA, etymology, translations.
* Interactive quiz shortcode with scoring and explanations.
* 5-phase lesson shortcode with tabbed navigation.
* Word of the Day sidebar widget.
* Gutenberg Word Card block.
* REST API proxy for secure API key handling.
* WordPress transient caching with configurable TTL.

== Upgrade Notice ==

= 1.0.0 =
Initial release of WordOrb Vocabulary for WordPress.

MooDuell module for Moodle
===========================

This Moodle module allows you to play questions from your existing Moodle question bank in the Univie MooDuell App.

Click here to download the app.

<a href='https://apps.apple.com/at/app/univie-mooduell/id1496638765'><img width="150px"  alt='Download from Apple App Store' src='https://developer.apple.com/app-store/marketing/guidelines/images/badge-download-on-the-app-store.svg'/></a>

<a href='https://play.google.com/store/apps/details?id=at.wunderbyte.univiemooduell&hl=de&pcampaignid=pcampaignidMKT-Other-global-all-co-prtnr-py-PartBadge-Mar2515-1'><img width="170px" alt='Get it on Google Play' src='https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png'/></a>

The App works exclusively with the Moodle Instance from the University of Vienna.

Installation
------------

Please follow <https://docs.moodle.org/en/Installing_plugins#Installing_a_plugin> for
the general instructions on how to install Moodle plugins.

When installing from uploaded ZIP package or via Git, the "mooduell" directory is
expected to be place under the "/mod" directory of your Moodle installation. 

You can't use this module fully withough using the App. Only the App allows actual playing of the quiz questions.
In the App, no installation is necessary. Just login via your University of Vienna Login.

Usage
-----

### For Teachers

* **Add the MooDuell Activity** to an existing Moodle course.
* **Create/Assign Questions**:
  - Use the "Add questions to category" button in the Questions tab (Overview for Teachers)
  - Select a question category and type
  - The plugin will route you to Moodle's question creation page with pre-selected category
  - Available question types: Multiple Choice (single/multiple), True/False, Numerical, Drag-and-Drop Word into Sentences
* **Manage Settings**:
  - Configure anonymous mode
  - Adjust gameplay settings
  - View and delete completed games
  - Monitor student scores and highscores
* **Minimum Requirement**: At least 9 playable questions per category for the activity to function

### For Students

* **In Moodle**: Can view basic statistics and see highscores
* **In App**: Play quiz games directly (requires Univie MooDuell App)

Supported Question Types
------------------------

- Multiple choice (single right answer)
- Multiple choice (multiple right answers)
- True/False
- Numerical (numeric input)
- Drag-and-Drop Word into Sentences

Author
------

The module has been written and is currently maintained by Wunderbyte GmbH <info@wunderbyte.at>

Technical Architecture
----------------------

### Components

1. **Moodle Plugin** (this repository)
   - Server-side PHP implementation
   - Web service API endpoints
   - Question bank integration
   - Manages games, scores, and user data
   - Web interface for teachers and students (Moodle view)

2. **Web Application** (`MooDuellWunderbyte`)
   - Ionic/Angular frontend (Capacitor-based mobile wrapper)
   - Communicates with plugin via Moodle webservices API
   - Deployed to `/mod/mooduell/app/` directory in plugin
   - Serves both web and mobile (iOS/Android via Capacitor)

3. **Mobile Apps**
   - **Official App**: Download from [App Store](https://apps.apple.com/at/app/univie-mooduell/id1496638765) / [Google Play](https://play.google.com/store/apps/details?id=at.wunderbyte.univiemooduell)
   - Alternative Flutter implementation available (`MooDuellFlutter`)

### Web App Deployment

The web application is automatically built and embedded in the plugin. To rebuild/deploy:

```bash
cd ../MooDuellWunderbyte
npm install
npm run build:moodle
npm run deploy:moodle-local
```

Then in Moodle: **Purge all caches** (Site administration → Cache → Purge all caches)

### API Endpoints

Web services are defined in `db/services.php`. Examples:
- `mod_mooduell_start_attempt` — Begin a new game
- `mod_mooduell_get_quiz_data` — Fetch quiz and questions
- `mod_mooduell_answer_question` — Submit answer
- `mod_mooduell_get_user_stats` — Fetch player statistics

Useful links
------------

* [Bug tracker](https://github.com/Wunderbyte-GmbH/moodle-mod_mooduell/issues)

License
-------

This program is free software: you can redistribute it and/or modify it under the
terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program. If not, see <http://www.gnu.org/licenses/>.

Announcement - Issues page merged with Turnitin's support page
================

In order to be able to better monitor incoming issues, we are consolidating the 3 issues pages for the 2 plagiarism plugins and Direct v2 with our existing support area.

The support area is how our team currently monitors issues for every other area of Turnitin. Therefore, we will be closing the Issues pages. If you have issues with the Turnitin plagiarism plugin, please raise them here: https://helpcenter.turnitin.com/hc/en-us/requests/new

The team appreciates your ongoing support and contributions. Thank you!

Turnitin Plagiarism plugin for Moodle
=====================================

Please be aware that the **Develop** branch should not be considered production ready, although it contains the latest fixes and features it may contain bugs. It should be avoided in favour of the **Master** branch which is the latest available branch that has been through the QA process. Please make any pull requests you would like to make to the develop branch.

For running behat tests in Moodle 3.9 and above, please use the behat_39+ branch. There are some deprecated form settings methods in Moodle that throw warnings during behat test runs causing failures, unfortunately this is the only way around this issue as we don't want have different plugin branches per Moodle version. 

To see what has changed in recent versions of the plagiarism plugin, see the [CHANGELOG](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/CHANGELOG.md).

If you would like to contribute to the plugin please see our [CONTRIBUTIONS](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/CONTRIBUTIONS.md) page.

If you are having issues, please consult our [TROUBLE SHOOTING](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/TROUBLESHOOTING.md) page.

Installation
------------

Before installing this plugin firstly make sure you are logged in as an Administrator and that you are using Moodle 4.1 or higher.

The Plagiarism Plugin can be used within the following Moodle modules:

- Assignments
- Forums
- Quiz
- Workshops

To install, you need to copy all the files into the plagiarism/turnitin directory in your Moodle installation. You should then go to `"Site Administration" > "Notifications"` where you should follow the on screen instructions.

Plagiarism plugins also need to be enabled before this plugin can be used. You can do this by going to `"Site Administration" > "Advanced Features"` and ticking the "Enable plagiarism plugins" box before saving.

You can set default values and whether the plugin is enabled within Moodle modules by going to `"Site Administration" > "Plugins" > "Plagiarism prevention" > "Turnitin plagiarism plugin"`.

To create/update assignments, process submissions and update grades your moodle environment will need to have cron job running regularly. For information on how to do this please consult https://docs.moodle.org/en/Cron.

On Behat tests
=====================================

Recently we fixed our existing Behat tests to work with Moodle 4.5 and 5.0. As is sometimes the case with Gherkin tests, there could be occasional timeouts or slight variations between browsers.

If you would like them yourself along with the other Moodle tests, please include these Environment variables (you can find them in your Site administration -> Plugin configuration):

TII_ACCOUNT: [your Turnitin account ID]
TII_SECRET: [your 8-character secret]
TII_APIBASEURL: "https://api.turnitin.com"

Unit Tests
=====================================

PHPUnit tests run automatically on every push and pull request via GitHub Actions
(see `.github/workflows/ci.yml`). The workflow uses
[moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci) to spin up a full
Moodle environment against PostgreSQL and runs the test suite across the supported
PHP and Moodle version matrix.

Tests require a running Moodle instance. The local Docker setup provides this via the
`moodle502-moodle-1` container.

Before running tests for the first time (or after rebuilding the container), initialise
the PHPUnit environment:

```bash
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  php admin/tool/phpunit/cli/init.php
"
```

If init fails with "Can not use database for testing, try different prefix", drop and
reinitialise:

```bash
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  php admin/tool/phpunit/cli/init.php --drop &&
  php admin/tool/phpunit/cli/init.php
"
```

### Run all unit tests

```bash
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  /usr/share/nginx/html/vendor/bin/phpunit \
    --configuration plagiarism/turnitin/tests/phpunit.xml
"
```

### Run a specific test class or method

```bash
# All tests in a class
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  /usr/share/nginx/html/vendor/bin/phpunit \
    --configuration plagiarism/turnitin/tests/phpunit.xml \
    --filter turnitin_forum_test
"

# A single test method
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  /usr/share/nginx/html/vendor/bin/phpunit \
    --configuration plagiarism/turnitin/tests/phpunit.xml \
    --filter test_get_submission_content_returns_content_for_new_submission
"
```

### Generate a code coverage report

PCOV is pre-installed in the Docker image. Run the suite with `--coverage-html` to produce
an HTML report, then copy it out of the container to view in a browser:

```bash
docker exec moodle502-moodle-1 bash -c "
  cd /usr/share/nginx/html/public &&
  /usr/share/nginx/html/vendor/bin/phpunit \
    --configuration plagiarism/turnitin/tests/phpunit.xml \
    --coverage-html /tmp/turnitin-coverage
" && \
docker cp moodle502-moodle-1:/tmp/turnitin-coverage /tmp/turnitin-coverage && \
open /tmp/turnitin-coverage/index.html
```

The report is scoped to the plugin's own code (`classes/`, `lib.php`, `locallib.php`) and
excludes Moodle core. This is configured via the `<source>` block in
`tests/phpunit.xml` and the `pcov.directory` setting baked into the Docker image.

Code Style
=====================================

The plugin follows the [Moodle coding standard](https://moodledev.io/general/development/policies/codingstyle).
Style is checked automatically in CI via `moodle-plugin-ci phpcs`. `phpcs` and its
auto-fixer `phpcbf` are pre-installed in the Docker image.

### Check for style violations

```bash
docker exec moodle502-moodle-1 bash -c "
  phpcs --standard=/opt/moodle-plugin-ci/vendor/moodlehq/moodle-cs/moodle \
        --extensions=php \
        --ignore=vendor,vendorjs \
        /usr/share/nginx/html/public/plagiarism/turnitin
"
```

### Auto-fix violations

The vast majority of violations can be fixed automatically:

```bash
docker exec moodle502-moodle-1 bash -c "
  phpcbf --standard=/opt/moodle-plugin-ci/vendor/moodlehq/moodle-cs/moodle \
         --extensions=php \
         --ignore=vendor,vendorjs \
         /usr/share/nginx/html/public/plagiarism/turnitin
"
```

Any remaining violations after running `phpcbf` require manual attention — typically
missing docblocks, variable naming, or comments that need punctuation.
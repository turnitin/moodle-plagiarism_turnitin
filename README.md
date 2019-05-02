Turnitin Plagiarism plugin for Moodle
=====================================

Please be aware that the **Develop** branch should not be considered production ready, although it contains the latest fixes and features it may contain bugs. It should be avoided in favour of the **Master** branch which is the latest available branch that has been through the QA process. Please make any pull requests you would like to make to the develop branch.

To see what has changed in recent versions of the plagiarism plugin, see the [CHANGELOG](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/CHANGELOG.md).

If you would like to contribute to the plugin please see our [CONTRIBUTIONS](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/CONTRIBUTIONS.md) page.

If you are having issues, please consult our [TROUBLE SHOOTING](https://github.com/turnitin/moodle-plagiarism_turnitin/blob/master/TROUBLESHOOTING.md) page.

Installation
------------

Before installing this plugin firstly make sure you are logged in as an Administrator and that you are using Moodle 3.1 or higher.

The Plagiarism Plugin can be used within the following Moodle modules:

- Assignments
- Forums
- Workshops

To install, you need to copy all the files into the plagiarism/turnitin directory in your Moodle installation. You should then go to `"Site Administration" > "Notifications"` where you should follow the on screen instructions.

Plagiarism plugins also need to be enabled before this plugin can be used. You can do this by going to `"Site Administration" > "Advanced Features"` and ticking the "Enable plagiarism plugins" box before saving.

You can set default values and whether the plugin is enabled within Moodle modules by going to `"Site Administration" > "Plugins" > "Plagiarism prevention" > "Turnitin plagiarism plugin"`.

To create/update assignments, process submissions and update grades your moodle environment will need to have cron job running regularly. For information on how to do this please consult http://docs.moodle.org/26/en/Cron.

Installation Instructions
=========================

This is the plagiarism plugin element of the Moodle direct version 2 package, which contains 3 elements for each respective plugin on Moodle. Each plugin requires that you are using Moodle 2.3 or higher. Before installing these plugins firstly make sure you are logged in as an Administrator.

Note: the turnitintooltwo module must be installed before you can use the turnitin plagiarism plugin.

The Plagiarism Plugin can be used within the following Moodle modules: Assignments, Forums and Workshops. To install, you need to copy the plagiarism/turnitin directory from the zip file in to your Moodle installations /plagiarism directory and go to "Site Administration" > "Notifications" where you should follow the on screen instructions. 

Plagiarism plugins also need to be enabled before this plugin can be used. You can do this by going to "Site Administration" > "Advanced Features" and ticking the "Enable plagiarism plugins" box before saving.

The Plagiarism Plugin will inherit connection settings from the turnitintooltwo module, but you can set default values and whether the plugin is enabled within Moodle modules by going to "Site Administration" > "Plugins" > "Plagiarism prevention" > "Turnitin plagiarism plugin".

To create/update assignments, process submissions and update grades your moodle environment will need to have cron job running regularly. For information on how to do this please consult http://docs.moodle.org/26/en/Cron.

Note that this plugin inherits it's Turnitin connection from the turnitintooltwo module.
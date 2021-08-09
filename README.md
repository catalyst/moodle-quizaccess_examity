# Examity
Description : 
Moodle / Examity integration 
https://www.examity.com

Provides an easy way to connect to Examity for online proctoring of your Moodle exam.

Branches
--------

| Moodle verion     | Branch           | PHP  |
| ----------------- | ---------------- | ---- |
| Moodle 3.5 to 3.8 | MOODLE_35_STABLE | 7.3+ |
| Moodle 3.9 and higher | MOODLE_39_STABLE | 7.3+ |

## Installing the plugin
Please see the instructions here for installing plugins in Moodle:
https://docs.moodle.org/en/Installing_plugins#Installing_a_plugin

## Configuring the plugin
### Api access:
Once you have installed the plugin, log in as the site administrator and go to the page: 
admin > Plugins > Activity modules > "Quiz", click on the link to "Examity" and fill out the API credentials as supplied by Examity

### Examity webservice access:
Examity makes web-services calls to your Moodle site to allow it to obtain user lists and other relevant information about your quizzes. This plugin automatically creates:
* Custom site-level role to allow the examity user to have the relevant capabilities.
* Custom web-service function that allows certain Moodle web-service functions to be called by examity.

While logged in as the site administrator, visit the Examity web services page under admin > plugins > activity modules > Quiz to help guide you through the configuration steps, including
* Enabling Web services on your site
* Enabling the Rest protocol
* Create a new user in your site with the username/email "developers@examity.com"
* Add this user to the site-level "examity" role.
* Create a web-services token - to create a new token, you must create a token for the developers@examity.com account, and select the custom "Examity" service already created - once you have created a token, please provide this token to Examity so they can connect to your site.

## Support
If you have issues with the plugin itself, please log them in github here: https://github.com/catalyst/moodle-quizaccess_examity/issues

This plugin was developed by Catalyst IT
https://www.catalyst.net.nz/


## License ##
Copyright: 2021 Catalyst IT

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

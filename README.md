# Token generator #

An admin tool provides a web service for generating Moodle web service tokens. It conceptually works the same way as /login/token.php, but via web services. See https://docs.moodle.org/dev/Creating_a_web_service_client#How_to_get_a_user_token

## Installation ##

Add the plugin to /admin/tool/token/

Run the Moodle upgrade.

## Configuration ##

The plugin has following settings.

* Token lifetime - allows configuring max lifetime for the generated tokens. Once generated tokens will be valid for configured time.
* Enabled auth methods - allows configuring auth methods to filter user by. Token can be generated for users with enabled auth methods only.
* Enabled user fields - allows configuring user fields for matching users, including unique custom user profile fields. These fields could be used in a web service call to match users in Moodle. Note: 'id' filed is always enabled.
* Enabled services - allows whitelisting only specific services for generating tokens using this plugin. Token can be generated for enabled services only.

## Usage ##

You need to configure your Moodle for using Web services. See documentation https://docs.moodle.org/310/en/Web_services

On the installation the plugin will automatically create **Token
Generator Service**, but it will be disabled by default. You should enable it and create a token for that service. Then you can call tool_token_get_token function baked to that service.

###  Example of GET request ###
https://example.local/webservice/rest/server.php?wstoken=r572f821c120ad147b244a939fdd7324&wsfunction=tool_token_get_token&moodlewsrestformat=json&idtype=username&idvalue=student&service=test_service

Response on success:

{ "userid": 12, "token": "c27319f9f198028db79a5d955c01d6cb", "validuntil": 0 }

## License ##

2021 Catalyst IT

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

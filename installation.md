The server needs to be able to be able to run the following program at the specified versions or newer:
•	Apache v2.2.15
•	PHP v5.6.14
•	Mysql v14.14 
1.	Setup Apache, PHP and MySql at the specified versions
2.	Run the contained MySQL script named caDB.sql in MySQL in order to setup the database.
3.	Create a special account for the PHP scripts to use the database. Make sure the account only has permission to modify, insert and delete records from the game’s database.
4.	Copy the files contained under ‘private’ into a private section of the server
5.	Copy the files in the ‘public’ section into the serving directory of Apache
6.	Modify the relative paths contained in ‘login.php’,’logout.php’ and ‘performAction.php’ in order to point towards the correct path for the private variable
7.	Configure SSL so that the game files and communications to the API are served over HTTPS, in order to protect data sent to the API, such as passwords and emails
8.	Configure the settings in the file caDB.ini to point towards the MySql server, give the username and password for the special PHP account and set the time zone for the local server 
N.B.: Make sure the database is encrypted, secured with a strong password and only accessible by the server hosting the game. This is due to the database storing personal data of email, as well as the usernames and salted hashes of the player’s passwords. 
After the initial setup of the server, it is vital to keep up to date with the latest software vulnerabilities and update the server software regularly, in order to avoid running old software that can be exploited to retrieve user’s data.
In order for the client to play, he only requires a modern browser, which supports ECMA script 6, and CSS animations. The recommended browser for playing is google chrome version 48 or newer.


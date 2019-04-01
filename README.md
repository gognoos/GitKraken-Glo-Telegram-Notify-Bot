# GitKraken Glo - Telegram Notify Bot!<br>
`GitKraken Glo - Telegram Notify Bot` allows you to use GitKraken Glo Webhooks to get real-time notify from Boards updates and changes such as Columns, Cards, Comments, Labels, Tasks, Descriptions, ... in your Telegram app!<br>
`GitKraken Glo - Telegram Notify Bot` written in `PHP language`.<br>
 
### Features:
- Support all Boards Event types
- Support Board Archived, Unarchived, Updated, Deleted, Labels Updated, Members Updated
- Support Column Added, Updated, Reordered, Archived, Unarchived, Deleted
- Support Card Added, Updated, Copied, Archived, Unarchived, Deleted, Reordered, Moved Column, Moved to Board, Moved from Board, Labels Updated, Assignees Updated
- Support Comment Added, Updated, Deleted
- Support Logs every requests to log file

### How to Setup:
- Create a new bot with Telegram BotFather
- Add bot Token in file `gkg-hendler.php`, `Line 3`
- Set Telegram bot webhook to project path by passing set as parameter, example `https://example.com/gkg-hendler.php?set`
- Get your Telegram user ID from bot by /getme command and set it in file `gkg-hendler.php`, `Line 6`
- Set GitKraken Glo webhook Payload URL to project path
- Set GitKraken Glo webhook Content Type to `application/json`
- Check wanted Trigger Events GitKraken Glo webhook settings
- Ready to go!

### Sample Screenshot:
![alt text](https://raw.githubusercontent.com/gognoos/GitKraken-Glo-Telegram-Notify-Bot/master/screenshot.jpg "GitKraken Glo - Telegram Notify Bot!")

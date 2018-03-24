# web-push-amphp
Browser notifications

### Installation

* Grab your VAPID keys by running `php cli/generate-vapid-keys.php`
* Grab your endpoint from your browser by running `php -S localhost:1337` while in `/public` and visiting
* Put all above into `/config/config.php`. You can find the sample in `/config.php`
* Run `php cli/sendNotification.php`

# URL Structure of Front-end User Profiles and Account Page

20 January 2026

Uppercase text indicates placeholders that should be replaced.

**HOME_URL** is the URL used to access the front end of your site.

### User Profile

* Pretty URL: **HOME_URL**/user/**USER_NICENAME**
* Regular URL: **HOME_URL**/?page_id=**ID-FOR-USER-PAGE**&describr_user=**USER_NICENAME**

### Account Page

* Pretty URL: **HOME_URL**/account
* Regular URL: **HOME_URL**/?page_id=**ID-FOR-ACCOUNT-PAGE**&describr_tab=general

The describr_user=**USER_ID** query variable can be added to either format of the account page URLs to locate the account page for a user other than the logged-in user.

The User and Account pages are created during the activation of the plugin and can be found on the Dashboard's Pages screen. 

Describr is compatible with WPML.

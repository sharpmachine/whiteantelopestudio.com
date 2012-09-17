=== Events Manager Pro ===
Contributors: netweblogic
Tags: events, event, event registration, event calendar, events calendar, event management, paypal, registration, ticket, tickets, ticketing, tickets, theme, widget, locations, maps, booking, attendance, attendee, buddypress, calendar, gigs, payment, payments, sports,
Requires at least: 3.1
Tested up to: 3.4.1
Stable tag: 2.2.1

== Description ==

Thank you for downloading Events Manager Pro!

Please check these pages for further information:

http://wp-events-plugin.com/documentation/ - lots of docs to help get you started
http://wp-events-plugin.com/tutorials/ - for advanced users, see how far you can take EM and Pro!

If you have any issues/questions with the plugin, or would like to request a feature, please visit:
http://wp-events-plugin.com/support/

== Installation ==

Please visit http://wp-events-plugin.com/documentation/installation/

== Changelog ==
= 2.2.1 =
* fixed MS network blog tables not being deleted by WP along with rest of blog
* fixed no-user mode bug showing assigned user information on the booking information page
* fixed reminder emails including long spanning events that already started 

= 2.2 =
* db table installation will take current blog prefix rather than determine if it's in global tables mode
* fixed transactions not deleting if event is already deleted
* fixed coupon dates not working
* added em_coupon_get_discount_text filter
* added paypal default language option
* added extra values to the epm_forms_output_field_input filter
* fixed multisite error when fetching transaction info
* fixed some form action calls (from add_action to do_action)
* added country to form field
* fixed extra blank field in form editor
* added user address field association, allowing for tighter integration with gateways
* added email reminders
* added option to show logged in users their registration fields in booking forms
* fixed PayPal gateway not taking pending payments into account and treating as in-progress (deleted automatically)
* fixed custom booking form not showing on forms outside of main event page
* fixed manual bookings not showing new user fields
* fixed default form install bug if pro installed first
* fixed some action typos on EMP_Forms editor html
* added em_coupon_is_valid filter
* fixed em_coupon_get_person filter typo
* added user password custom field
* added date and time picker custom fields
* added 'required' asterisks next to labels
* fixed required text fields not accepting a 0
* fixed paypal settings not saving if paypal email not supplied
* added custom tooltips to field labels

= 2.1.5 =
* fixed manual bookings not allowing admins booked to that event with double bookings disabled
* added missing error message on manual booking form admin-side validation
* fixed offline status not being editable if de-activated yet making a manual booking
* added classes to coupon code and authorize booking form elements
* fixed manual bookings bug for another user without a payment
* set status to pending rather than cancelled for re-review if partial refunds are made
* transactions now get deleted with bookings
* added manual delete transaction
* fix for multiple booking forms on one page
* further improvement to loading of a.net SDK to avoid plugin conflicts

= 2.1.4 =
* fixed authorize.net conflicts if SDK already loaded by another plugin
* added failed email message to offline bookings that go through
* improved fallback for javascript booking form failures (particularly paypal)
* added input class to text fields in booking form for coupons and gateways
* fixed manual booking link issues
* fixed authorize.net "invalid line 1" errors due to long ticket names
* fixed email regex settings not working (requires a resave of form settings)
* manual bookings accept partial payments
* fixed invalid coupons still allowing bookings to go through

= 2.1.3 =
* added gateway transaction id to booking collumns
* fixed form editor validation problems

= 2.1.2 =
* allowed form labels to accept HTML
* fixed paypal resume payment button
* fixed paypal payment status text
* modified coupon calculation to add tax after discount, if tax is added seperately
* made paypal bookings editable even if pending
* fixed various form editor bugs
* fixed email problems with paypal confirmations
* manual bookings now accept coupons and anonymous registrations, as well as custom payment amounts
* added more html css classes to booking form
* made update notices more user-friendly if pro-api-key isn't valid

= 2.1.1 =
* fixed coupon pagination problem
* fixed captcha failures due to duplicated checks
* fixed user fields and Array being shown for multi-option fields
* removed dev version checking (moving to free) and add one-off dev version check
* reverted to using .delegate() instead of jQuery 1.7+ .on() listener for compatibility

= 2.1 =
* offline payment confirmation window can be properly cancelled (bugfix)
* membership key options now showing properly in MS mode
* added custom user fields
* added custom booking forms per event
* detached booking form editor into a re-usable class for user fields and future custom forms

= 2.0.4 =
* fixed pro member key issue in MultiSite
* coupons saving properly in MS Global Tables mode.
* added coupon count and history

= 2.0.2 =
* added html filtering for ticket names sent to paypal
* fixed offline manual partial payemnt formats bug
* added some translatable strings
* membership key entry will force recheck of plugin updates
* fixed captcha includes breaking form submissions
* added classes to custom booking form html
* added cancel url to PayPal gateway
* fixed Authorize.net gateway creating wp accounts when CC info is bad

= 2.0 =
* fixed checkboxes defaulting to selected
* rewritten gateway API, add custom gatways much faster and efficiently
* added Authorize.net AIM Gateway
* added coupons feature Coupons
* restructured files
* various minor bug fixes
* updated Russian translation
* prevented from loading EMP if EM isn't activated

= 1.51 =
* fixed offline custom message not working
* fixed paypal ticket descriptions and special characters (using UTF-8)
* fixed view transactions blank page from gateways page

= 1.5 =
* paypal now pre-registers user before redirecting if applicable (more stable, more possibilities)
* added #_BOOKINGTXNID to booking placeholders for paypal transaction ID
* fixed placeholders for custom form fields
* html now accepted in booking form feedback in gateways
* small usability improvements to manual booking form
* transactions tabled now unified to reduce clutter
* paypal return url modified to use a static file (wp-admin/admin-ajax.php) and the previous url as a fallback

= 1.45 =
* fixed booking form placeholders
* #_CUSTOMBOOKING now works for #_CUSTOMBOOKINGREG fields
* html not escaped with slashes in custom booking gateway feedback messages

= 1.44 =
* fixed booking form regexes making inputs required
* paypal won't allow registered emails in guest mode
* paypal bookings only considered as pending if timeout is set (paypal pending payments view coming shortly)

= 1.43 =
* important bug fix for paypal bookings

= 1.42 =
* Custom registration booking placeholder fixed

= 1.41 =
* Updated to support version 5 (required)

= 1.39 =
* fixed yahoo field name for saving into booking regsitration
* fixed page navigation for pending payments
* fixed checklist booking saving bug
* paypal IPN soft fail introduced, to reduce alternante payment software 404s

= 1.38 =
* fixed minor php warning
* added em_gateway_paypal_get_paypal_vars filter
* fixed default custom form issue with validating emails in guest bookings
* fixed duplicate indexes in transaction table
* manual bookings by less than admins not impeded by permission errors

= 1.37 =
* allows negative manual payments
* paypal return url instructions corrected

= 1.36 =
* fixed bug which prevented transaction tables showing unregistered/deleted users.
* warning added if EM plugin version is too low
* update notices appear on the network admin area as well
* added cron tasks for paypal booking timeouts
* added return url option for paypal
* custom booking form information properly escaped and filtered
* paypal manual approvals won't take effect with normal approvals disabled
* offline and paypal pending spaces taken into account
* paypal and offline payments take tax into account (requires EM 4.213)
* fixed logo not being shown on paypal payment page
* payments in no-user mode accepted (requires EM 4.213)

= 1.35 =
* added alternative notification check for servers with old SSL Certificates
* added dev mode updates option in the events setttings page
* removed the main gateway JS
* manual bookings can now be done by all users with the right permissions
* paypal payments will not include free tickets during checkout paying, avoiding errors on paypal
* pot files updated
* German and Swedish translations updated
* fixed various warnings
* multiple alert boxes when confirming offline payments fixed
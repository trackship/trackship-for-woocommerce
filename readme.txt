=== TrackShip for WooCommerce  ===
Contributors: TrackShip
Tags: WooCommerce, delivery, shipment tracking, order tracking, tracking
Requires at least: 5.3
Tested up to: 5.7
Requires PHP: 7.0
Stable tag: 1.0.2
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TrackShip for WooCommerce integrates [TrackShip](https://trackship.info/) into your WooCommerce Store and auto-tracks your orders, automates your post-shipping workflow and allows you to provide a superior Post-Purchase experience to your customers.

== What is TrackShip ==

Trackship is a Multi-Carrier Shipment Tracking platform that supports 300+ shipping providers. Once TrackShip is connected to your store, it will automatically track all your shipments and proactively update the shipment status back to your store, until the package is delivered to the customer.

https://www.youtube.com/watch?v=PhnqDorKN_c

== Benefits ==

= Seamless integration with WooCommerce = 
Trackship for WooCommerce integrates TrackShip with your WooCommerce store and brings a branded tracking experience into the WooCommerce ecosystem. The shipment status updates are sent from your store. the tracking page is on your store and the tracking data is saved in the WooCommerce orders.

= Optimized for WooCommerce =
TrackShip does all the heavy lifting, once you ship your orders, the tracking info is exported to TrackShip and it checks the shipment status with the shipping providers every few hours and proactively updates your orders only when there is an update to the shipment status.

= Post-Shipping automation = 
TrackShip for WooCommerce adds a Custom order status “Delivered” to your store and automatically updates the orders status to “Delivered” once the shipments are delivered to your customers. When you know that the product is delivered , you can trigger post-purchase marketing campaigns based on the “Delivered”  order status and the delivery date.

= Save time on customer service = 
TrackShip displays up-to-date shipment status and tracking events for every order on your WooCommerce admin so your staff can quickly answer any post-shipping inquiries without the need to check the tracking status on the carriers website.

= Keep your Customers Informed with Shipping & Delivery updates =
Let your customers know where their order is at all times, you can send automatic emails and SMS notifications to update your customers on shipping delays and when their order is Out For Delivery, Delivered or failed delivery attempt.

= Further engage customers with a Tracking Page on your store = 
Direct your customers to a detailed tracking page on your store Instead of sending them to track their orders on the carriers websites.

== What's included? ==
* Tracking Page on your store
* Shipment Status & Delivery Email Notifications
* Custom Order Status “Delivered”
* Post-Shipping order status automation
* Shipment status and Est. delivery displays on the orders admins
* Filter orders by shipment status
* Tracking info widget on the Completed order status email
* Custom email templates
* White Label Tracking Page widget
* View order page display (tracking page widget)
* Tracking Analytics widget

== How does it work? ==
1. Signup for a [TrackShip](https://trackship.info/) account 
2. Connect your WooCommerce stores with TrackShip API
3. Setup TrackShip on your store, enable the tracking page and shipment status & Delivery updates.
4. Seat back and relax, TrackShip will Auto-track your Fulfilled orders and proactively update your orders whenever there is an update in the shipment tracking status, until the shipments are delivered to your customers.  

== Requirements ==

* [TrackShip](https://trackship.info/) account
* WooCommerce REST API enabled
* SSL Certificate - you must have secured site (HTTPS) to connect TrackShip to your store
* Pretty permalinks - navigate to Settings > Permalinks and make sure that the permalink structure is based on Post Name (TrackShip can’t work with the Plain option)
* Shipment Tracking Pugin (see compatibility)

== Compatibility ==

TrackShip will track orders when you add tracking numbers using one of the following shipment tracking extensions for WooCommerce:

* [Advanced Shipment Tracking (AST)](https://wordpress.org/plugins/woo-advanced-shipment-tracking/)
* [WooCommerce Official Shipment Tracking extension](https://woocommerce.com/products/shipment-tracking/?aff=4780)

== Integrations  ==

[SMS for WooCommerce](https://trackship.info/docs/trackship-for-woocommerce/compatibility/sms-for-woocommerce/) - Send automatic SMS updates for shipment status & delivery via Twilio, Nexmo or ClickSend.
[Checkout for WooCommerce](https://trackship.info/docs/trackship-for-woocommerce/compatibility/checkoutwc/) - Add Tracking Page widget to the Order received page when its set to be the view order page
[AutomateWoo](https://trackship.info/docs/trackship-for-woocommerce/compatibility/automatewoo/) - use the "Delivered" custom order status to trigger marketing automation based on the order delivery date.

== Documentation ==
Check out TrackShip for WooCommerce [documentation](https://trackship.info/docs/trackship-for-woocommerce/) for more details on how to set up and work with TrackShip

== Frequently Asked Questions ==
= I connected my store but the shipment status is not showing for my orders
The trigger to auto-track shipments by TrackShip is to add tracking to order and change the order status from Processing to Shipped (Completed). TrackShip will not automatically track orders that were Shipped before you connected your store.
You can trigger these orders to TrackShip by using the [Get Shipment Status](https://trackship.info/docs/setup-trackship-on-woocommerce/woocommerce-orders-admin/#get-shipment-status) option on the WooCommerce orders admin in the bulk actions menu.

= My store is connected but many of my orders still show “Connection error” shipment status
These messages are from before you connected your store, TrackShip auto-track shipments when you change the order status from Processing to Shipped (Completed). 
TrackShip will not automatically track orders that were shipped when you had a connection issue.
You can trigger these orders to TrackShip by using the [Get Shipment Status](https://trackship.info/docs/setup-trackship-on-woocommerce/woocommerce-orders-admin/#get-shipment-status) option on the WooCommerce orders admin in the bulk actions menu.
= What is a Shipment Tracker?
A Shipment Tracker represents One tracking number that you send to track on TrackShip, a tracker starts from when you ship the order until the shipment is finally delivered to the customers. When you add multiple tracking numbers to a single order, each tracking number is a separate Shipment Tracker.
= How often do you check for tracking status updates?
Trackship checks the shipment status with the shipping providers APIs every 2-4 hours. We check for updates more often once the package is in the "unknown" status, until the first tracking event is received from the providers API and when the shipment is out for delivery.
= Which shipping providers (carriers) do you support?
TrackShip supports 300+ [shipping providers](https://trackship.info/shipping-providers/) around the globe ,if you can find your carrier on our supported shipping providers list, you can suggest a shipping provider [here](https://trackship.info/docs/trackship-resources/suggest-a-shipping-provider/)
= Do you show the shipment status for orders on WooCommerce admin?
Yes, TrackShip adds a Shipment Status column on your orders admin and displays the shipment tracking status, last update date and the Est Delivery Date for every order that you shipped after connecting your store.

= If a shipment Tracker returns no result, does it count?
It doesn’t. When a shipment tracker is not supported by TrackShip or returned Unknown the Shipment tracker isn’t counted in your trackers balance.

= Do you offer Free Trials?
Yes, When you sign up for your TrackShip account,  you’ll get a free 50 shipments monthly plan, once you finish your trial balance, you can sign up for a paid subscription in order to continue to track additional shipments.

= Will I be charged when my free shipment trackers are finished?
No. You can fully test out TrackShip and all the features with the free trial Trackers without adding a credit card. It is completely up to you if you would like to carry on using TrackShip after your trial has ended).

== Changelog ==
= 1.0.3 =
* Dev - Order note added for Trackship, when tracking information sent to Trackship and shipment status change 
* Improved UI/UX - Shipment Tracking Column on orders admin list
* Dev - add track link to Shipment Tracking Column on orders admin list
* Dev - Improvment in tracking page popup.
* Fix - Tracking Widget Customizer – Show Only Last Event (was showing 2 last events)
* Twick - Tracking Page Widget on View Order Page – Always show “last event” view.

= 1.0.2 =
* Fix - Tracking Page link fixed in completed email

= 1.0.1 =
* Dev - translations updated.
* Improvement - tracking-form css updates.

= 1.0 =
* Initial version.

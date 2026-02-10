=== TrackShip for WooCommerce  ===
Contributors: TrackShip
Tags: WooCommerce, parcel tracking, woocommerce shipment tracking, order tracking, tracking
Requires at least: 6.2
Tested up to: 6.9.1
Requires PHP: 7.4
Stable tag: 1.9.8
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TrackShip auto-tracks orders, adds a branded tracking experience to your store and handles all customer touchpoints from shipping to delivery

== Description ==

TrackShip is a shipment tracking and post-purchase experience platform that helps WooCommerce businesses to provide an exceptional post-shipping experience to their customers, it helps to gain loyalty and trust and increase the repeat purchases, which is crucial for any eCommerce business to grow and succeed in the long run.

https://www.youtube.com/watch?v=QDKV2Irqz9M

== TrackShip Pricing ==

TrackShip offers a **15-day free trial** that allows you to track up to **100 shipments** with access to all premium features. During the trial period, you can experience everything TrackShip has to offer.
After the trial, if you choose not to go with a paid plan, you can continue using TrackShip on our **Free Plan**. The free plan allows you to track up to **50 shipments per month**, but with access to basic features only. For users who need to track more shipments or want advanced features such as SMS notifications, branded tracking pages, or enhanced analytics, we offer paid plans that scale according to your business needs.
For more details on pricing and features, please visit our [TrackShip Pricing Page](https://trackship.com/pricing/).

== Why use TrackShip? ==

= Automatic Shipment Tracking with 950+ Shipping carriers =
TrackShip auto-tracks your orders from shipping to delivery with 950+ shipping providers and carriers around the world. Our supported providers includes USPS, ePacket, Delhivery, Yun Express Tracking, UPS, Australia Post, FedEx, Aramex, DHL eCommerce, ELTA Courier, Colissimo, DHL Express, La Poste, DHLParcel NL, Purolator, 4px, Brazil Correios, Deutsche Post, Bpost, DHL, EMS, DPD.de, GLS, China Post, Loomis Express, DHL Express, PostNL International 3S, Royal Mail and more…
Check out the complete list of supported [shipping carriers](https://trackship.com/shipping-providers/).

= Take control of the post-purchase workflow = 
TrackShip allows merchants to take control of their post-shipping operations, further engage customers after shipping and do not rely on 3rd parties to provide service to your customers. The service is great for merchants and drop shippers that want to improve their customer service and provide superior shipping experience to their customers
= Improves customer experience =
TrackShip provides an easy way for customers to track their orders and receive real-time updates on their shipment status.
= Increases customer loyalty & Trust =
By providing customers with a seamless tracking experience, TrackShip helps to increase customer loyalty and repeat business.
= Reduce time spent on customer service =
TrackShip automates the tracking process, allowing merchants to spend less time on manual tracking and more time on other important aspects of their business.
= Provides valuable insights =
TrackShip provides merchants with valuable shipping & delivery insights such as delivery times and carrier performance data that can help them optimize their shipping strategy and improve their bottom line.
= Cost-effective =
TrackShip is a cost-effective solution for merchants looking to improve their shipping process and customer experience without a large investment in time or money.

== What's included? ==

* Shipments dashboard
* Fully Customized Tracking page on your store
* Shipment status & delivery notifications (email/SMS)
* Delivery Confirmation (custom order status “Delivered”)
* Shipping & delivery Analytics
* Notifications Log

== How does it work? ==
Once you add tracking information to Orders using a Shipment tracking plugin, the shipment details will be sent to track on TrackShip that will auto-track the shipments and proactively update your orders whenever there is an update in the shipment status until the shipments are delivered to your customers.

== Why is TrackShip the best Order Tracking Solution for WooCommerce? ==
TrackShip is easy to set up and seamlessly integrates into the WordPress admin. Unlike its alternatives like AfterShip, 17track, ParcelPanel, ShipStation, and other shipment tracking platforms, TrackShip provides a fully customizable tracking experience, more accurate tracking data, and it does not require you to go to a different dashboard to monitor shipments and manage your tracking operations. Most of its features are managed within the WordPress admin.

== Documentation ==
For more information, check out our [Documentation](https://docs.trackship.com/docs/trackship-for-woocommerce/)

== Requirements ==

* [TrackShip](https://trackship.com/) account
* WooCommerce REST API enabled
* SSL Certificate
* Pretty permalinks - navigate to Settings > Permalinks and make sure that the permalink structure is based on Post Name.
* Shipment Tracking Plugin

= Supported shipment tracking plugins for WooCommerce: =
* [Advanced Shipment Tracking AST]()
* [Advanced Shipment Tracking Pro (AST PRO)](https://www.zorem.com/products/woocommerce-advanced-shipment-tracking/)
* WooCommerce Shipment Tracking
* Orders Tracking for WooCommerce by VillaTheme
* YITH WooCommerce Order & Shipment Tracking by Yith

== Compatibility ==
We tested and added compatibility to the following plugins:
* [SMS for WooCommerce](https://www.zorem.com/product/sms-for-woocommerce/)
* Checkout for WooCommerce (CheckoutWC)
* AutomateWoo
* Dokan
* JWT Auth
* Kadence WooCommerce Email Designer
* YayMail – WooCommerce Email Customizer
* WooCommerce Email Template Customizer (Free)
* WooCommerce Email Template Customizer (Pro)
* WP HTML Mail – Email Template Designer
* Custom Order Numbers for WooCommerce
* Custom Order Numbers for WooCommerce Pro
* WooCommerce Sequential Order Numbers
* Sequential Order Numbers for WooCommerce
* Booster for WooCommerce
* Booster for WooCommerce Pro
* WP-Lister Lite for Amazon
* WP-Lister Pro for Amazon

== Documentation ==
Check out TrackShip for WooCommerce [documentation](https://docs.trackship.com/docs/trackship-for-woocommerce/) for more details on how to set up and work with TrackShip

== Frequently Asked Questions ==

= What is a Shipment Tracker?
A shipment tracks one tracking number from the time it's shipped until it has been delivered, no matter how many status events were created during its life cycle.

= Will TrackShip affect my site’s performance?
Not at all. When you fulfill an order, the shipping information is sent to TrackShip and it does all the heavy-lifting for you, we check the status of the shipment with the shipping provider every few hours and we update your store whenever there is an update in the status, and it does not impact your load time in any way.

= Do I need a developer to connect TrackShip to my store?
Absolutely not! You can easily connect your store with TrackShip in a few simple steps and start enjoying a branded tracking experience in less than 10 minutes..

= I connected my store but the shipment status is not showing for my orders
The trigger to auto-track shipments by TrackShip is to add tracking to the order and change the order status from Processing to Shipped (Completed). TrackShip will not automatically track orders that were Shipped before you connected your store.
You can trigger these orders to TrackShip by using the [Get Shipment Status](https://docs.trackship.com/docs/trackship-for-woocommerce/manage-orders/#get-shipment-status) option on the WooCommerce orders admin in the bulk actions menu.

= My store is connected but many of my orders still show a “Connection error” shipment status
These messages are from before you connected your store, TrackShip auto-track shipments when you change the order status from Processing to Shipped (Completed). 
TrackShip will not automatically track orders that were shipped when you had a connection issue.
You can trigger these orders to TrackShip by using the [Get Shipment Status](https://docs.trackship.com/docs/trackship-for-woocommerce/manage-orders/#get-shipment-status) option on the WooCommerce orders admin in the bulk actions menu.

= How often do you check for tracking status updates?
TrackShip checks the shipment status with the shipping providers APIs every 2-4 hours. We check for updates more often once the package is in the "unknown" status, until the first tracking event is received from the providers API and when the shipment is out for delivery.

= Which shipping providers (carriers) do you support?
TrackShip supports 950+ [shipping providers](https://trackship.com/shipping-providers/) around the globe ,if you can find your carrier on our supported shipping providers list, you can suggest a shipping provider [here](https://feedback.zorem.com/trackship)

= Do you show the shipment status for orders on WooCommerce admin?
Yes, TrackShip adds a Shipment Status column on your orders admin and displays the shipment tracking status, last update date, and the Est Delivery Date for every order that you shipped after connecting your store.

= If a shipment Tracker returns no result, does it count?
It doesn’t. When a shipment tracker is not supported by TrackShip or returned Unknown the Shipment tracker isn’t counted in your trackers balance.

= Do you offer Free Trials?
Yes, When you sign up for your TrackShip account,  you’ll get a free 50 shipments monthly plan, once you finish your trial balance, you can sign up for a paid subscription in order to continue to track additional shipments.

= Will I be charged when my free shipment trackers are finished?
No. You can fully test out TrackShip and all the features with the free trial Trackers without adding a credit card. It is completely up to you if you would like to carry on using TrackShip after your trial has ended.

== Screenshots ==

1. The shipments dashboard helps you quickly resolve shipping issues and take proactive action to keep customers happy.
2. Get a clear overview of your shipping and delivery operations with performance stats, issue alerts that need action, and TrackShip account and subscription status.
3. TrackShip general settings.
4. TrackShip Tracking Page
5. TrackShip for WooCommerce lets you send SMS updates to customers based on shipment and delivery status.
6. Lets you map shipping providers from external APIs to TrackShip providers.
7. Shipment emails trigger automatically by status, and you can choose which shipment statuses send notifications.
8. Fetch tracking for past orders, manage logs, and verify TrackShip database tables.
9. Fully customize the tracking widget with a live preview, choose what info to show and match the style and colors to your brand.
10. TrackShip adds a Shipments column showing tracking status and lets you filter orders by shipment status.
11. After adding tracking info and fulfilling the order, the shipment status appears in the Shipment Tracking panel.
12. Shipping and delivery email notifications are fully customizable, with editable templates and a live preview customizer.
13. You can view TrackShip Analytics and filter results by time range, shipment status, or provider.

== Changelog ==

= 1.9.8 - 2026-02-10 =
* New - Added WooCommerce Fulfillments integration for native shipment data sync.
* New - Added Setup tab to enable WooCommerce Fulfillments from TrackShip settings.
* New - Added WooCommerce Shipping plugin compatibility.
* Enhancement - Redesigned admin tracking widget to sidebar layout.
* Enhancement - Improved "is order shipped" logic to support fulfillment-based orders.
* Enhancement - Added formatted provider name display for WC Shipment Tracking.
* Fix - Fixed email heading input issue with single quote character.
* Fix - Fixed error on null order object in Late Shipment email.
* Fix - Addressed RouteApp compatibility issue.
* Fix - Fixed Map carrier condition for AST Pro.
* Update - Renamed front-end JS handle to avoid conflicts.
* Update - Uses `current_time` for store time instead of server time.
* Update - Removed redundant WooCommerce script and style registrations (jquery-tiptip, select2, selectWoo, wc-enhanced-select) as they are already registered by WooCommerce.
* Compatibility - Verified compatibility with WooCommerce version 10.5.0.
* Compatibility - Tested and confirmed compatibility with WordPress version 6.9.1.

= 1.9.7.1 - 2025-11-04 =
* Enhancement - Added Japanese translation for the plugin.
* Update - Updated translations to improve language support.
* Update - Ensured the tracking page always fetches the latest order when a tracking number is added to multiple orders.
* Enhancement - Added filters to allow specific HTML tags and CSS styles in admin emails (Exception, On Hold, Late Shipments).
* Enhancement - Introduced a setting to use the Villa Theme Email Customizer template.
* Enhancement - Replaced WooCommerce's deprecated tipTip with Dashicons for improved tooltip handling.
* Update - Added documentation link in the Integrations section.
* Compatibility - Verified compatibility with WooCommerce version 10.3.4.
* Compatibility - Tested and confirmed compatibility with WordPress version 6.8.3.

For a complete changelog history, please visit our [documentation](https://docs.trackship.com/docs/trackship-for-woocommerce/changelog/).

== Upgrade Notice ==

= 1.9.8 =
Major update! Now supports WooCommerce Fulfillments 
integration for native shipment data sync. Includes 
redesigned admin tracking widget, improved fulfillment-
based order support, and multiple bug fixes. Fully 
compatible with WooCommerce 10.5.0 and WordPress 6.9.1. 
All users recommended to upgrade.

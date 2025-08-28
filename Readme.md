# AutomaticOrderStates
Automatic order states to generate rules that automaticly assign right order state to order.

## ABOUT
This module extend default PrestaShop environment by automaticly apply defined order state to order.
Apply is done after validate order against specified rules.
For example, to order that status is "Awaiting bank wire payment" longer than 30 days we can automaticly assign new orders status "Canceled"
Apply process can be done manually or automaticly by add execution of this module to PrestaShop "Cron tasks manager" module.
Need any new specyfic rule for this module? Just write to me and I'll add it in very short time.

## SUPPORTED PRESTASHOP VERSIONS
1.5.4.X - 1.7.X

## FEATURES
- 8 available rules
a) Current order status - validate order against current order status
b) Current order status with date - validate order against current order status date
c) Payment method - validate order against payment method
d) Carrier method - validate order against carrier method
e) Previous order status - validate order against previous order status
f) Historical order status - validate order against historical order status
g) Historical order status with date - validate order against historical order status date
h) Order product - validate order against ordered product 
- two logic states to compare (equal/not equal) into current, previous and historical status rules
- support multistore
- unlimited number of automatic order statuses
- unlimited number of rules in automatic order statuses
- manual or automatic mode
- responisble design using standard PrestaShop controls

## EXAMPLE OF USE
1. Set status "Canceled" in case order have no payment by bank wire for longer than 30 days
- create automatic order status by select status "Canceled" and apply any name for example "Cancel bankwire orders"
- inside "Cancel bankwire orders" add first rule
- set type as "Current order status"
- set "Order status" as "Awaiting bank wire payment"
- inside "Cancel bankwire orders" add one second rule
- set type as "Current order status with date"
- set "Condition" as "more than"
- set "Value" as "30"

2. Send reminder email in case no valid cheque payment done for longer than 10 days
- create new PrestaShop order status called "Awaiting cheque payment after 1st remind"
- create and apply to that status any email template You want
- create automatic order status by select status "Awaiting cheque payment after 1st remind" and apply any name for example "Cheque 1st reminder"
-inside "Cheque 1st reminder" add first rule
- set type as "Current order status"
- set "Order status" as "Awaiting cheque payment"
-inside "Cheque 1st reminder" add second rule
- set type as "Current order status with date"
- set "Condition" as "more than"
- set "Value" as "10"

## INSTALLATION
Module use standard prestashop installation way.
Module add its own admin menu "Automatic Order Status" under "Orders" menu

## CONFIGURATION:
No configuration for this module

## THE BENEFITS FOR MERCHANTS
Merchants get tool that automaticly change orders statuses.
It reduce merchant's effort on handle manually change statuses on orders.
It's very usefull for bigger shops that have many orders.

## THE BENEFITS FOR CUSTOMERS
No benefits for customers

## KEYWORDS
order, history, status, state, cron, automatic

## DEMO
https://90.ip-145-239-80.eu/demo/automaticorderstates/admin_dev/
demo@demo.com / demodemo
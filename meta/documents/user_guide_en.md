# What is GO! Express? For what?

GO! Express & Logistics is the largest independent provider of express and courier services. One focus is traditionally on overnight delivery.

A courier service is ideal for time-sensitive or high-value goods shipments. A classic application is, for example, shipping food that needs to be frozen overnight so that the cold chain is not interrupted.

Use this plugin to run GO! to integrate into your plentymarkets system. It is then possible to carry out the familiar step of registering the shipping order in the shipping center and in plentyBase.

## Quickstart

In order to use this plugin, you must be registered as a sender at GO!. You will receive a username and password to configure the plugin afterwards.

**Use for your registration at GO! Express one of the following ways:**

- Phone: 0800 / 859 99 99
- [Email](mailto:info@general-overnight.com)
- [Contact form](https://www.general-overnight.com/deu_en/online-services/contact.html)

When contacting, please mention that you have the plentymarkets plugin for GO! Express found here in the Marketplace.

## Plugin Configuration

As soon as you have received the user data from GO! are available, you can store them in the plugin and generate your first shipping label.

### Deposit access data

To get started, you must first enable API access.

1. Open the menu **Plugins » Plugin set overview**.
2. Select the desired plugin set.
3. Click **GO! Express**.<br>→ A new view will open.
4. Select the **Global** section from the list.
5. Enter your username and password.
6. **Save** the settings.

Make sure that the mode is set to **DEMO** for all test scenarios. After adjusting the sender settings, you can register shipments in the shipping center and receive the appropriate transaction number. incl. label back.

As soon as you are from GO! have received the release for productive operation, you must set the switch to **FINAL** here.

### Shipper Settings

Enter your address data according to registration in the **Sender** area. You can also configure your pick-up time and an optional pick-up and or delivery notice under **Shipping**.

<div class="alert alert-warning" role="alert">
    The pick-up time window must be at least 120 minutes and shipments can only be registered at least 85 minutes in advance.
</div>

## GO! Express as a shipping option

If the plugin has been successfully installed and the tests have been successful, it is time to make the shipping service provider selectable as an option in the checkout of your shop.

1. Activate your **[delivery countries](https://knowledge.plentymarkets.com/en/slp/fulfillment/versand-vorbereiten#100)**
2. Create your (shipping)**[regions](https://knowledge.plentymarkets.com/en/slp/fulfillment/versand-vorbereiten#400)**
3. Create your **[Shipping Service Provider](https://knowledge.plentymarkets.com/en/slp/fulfillment/versand-vorbereiten#800)** _**GO! Express**_
  * Choose _**Sonstiges**_ in the _Shipping Service Provider_ column
  * Store `https://www.general-overnight.com/deu_en/sendungsverfolgung.html?reference=$PaketNr` as tracking URL
4. Create your **[shipping profiles](https://knowledge.plentymarkets.com/en/slp/fulfillment/versand-vorbereiten#1000)** and **[table of shipping charges](https://knowledge.plentymarkets.com/en/slp/fulfillment/versand-vorbereiten#1500)** for _**GO! Express**_

### GDPR: Information on data transmission (email and telephone)

You can configure this in your shipping profile using the option **[Transfer email and phone](https://knowledge.plentymarkets.com/en/slp/business-entscheidungen/rechtliches/dsgvo#700)**. The customer's email address is in the interface of GO! a required field. So you have to transfer these at least in some form.

<div class="alert alert-warning" role="alert">
    If you have activated the <strong>Agreement upon data transfer</strong> checkbox: If the person does not agree to the transfer of data and you have not entered an alternative email address in the shipping profile, an error message will be displayed and the order cannot be sent to GO! be registered.
</div>

## Credits

This plugin was kindly funded by [beefgourmet.de](https://www.beefgourmet.de/).

<sub><sup>Every single purchase helps with constant further development and the implementation of user requests. Thanks very much!</sup></sub>
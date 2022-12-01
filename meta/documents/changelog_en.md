# Release Notes for GO! Express

## v1.0.10 (2022-12-01)

### Added
- For advanced users: within the warehouse configuration, the sender data can now be overwritten depending on the client

## v1.0.9 (2022-11-17)

### Changed
- When registering the shipping, it is now checked whether 1.) the delivery address has a house number and 2. the length of the postal code matches the country (DE = 5 digits, AT = 4 digits). If this is not valid, the user receives an error message telling them to correct the delivery address (this behavior can be deactivated in the plugin configuration if necessary)

## v1.0.8 (2022-07-18)

### Fixed
- The "Minimum weight (g)" setting led to a problem when registering the shipping order if the shipping package did not have a stored weight.

## v1.0.7 (2022-06-23)

### Changed
- The setting "Minimum weight (g)" in the plugin configuration is no longer mandatory. If no number is entered there, a minimum of 200g is automatically transmitted.

### Fixed
- PHP 8 compatibility indicator set after source code check

## v1.0.6 (2022-06-03)

### Added
- New option "Get sender data from warehouse" under **Advanced**, so that individual sender/collection addresses per warehouse are transmitted via warehouse configuration. For this purpose, the address data of the warehouse must be maintained under **Setup » Stock » Warehouse** and a correct warehouse configuration entered
- Added a PDF label printer selection option under **Advanced** in the plugin configuration: Standard, Citizen and Zebra
- If the shipping package does not have a weight specification, a standard weight can be stored using the "Minimum weight (g)" option under **Shipping**
- New option to configure a delivery notice per package in the **Shipping** area (overwrites the previously set delivery notice per order!)
- Option "Transfer the telephone number as Abteilung additionally" in the **Shipping** area to make the customer's telephone number visible on the shipping label

### Changed
- Determining the country code for forwarding the phone number to GO! has been expanded to include several European supplier countries
- We cleaned up and drank way too much coffee

## v1.0.5 (2022-05-06)

### Changed
- In the description, the cross-references to the plentymarkets manual have been adjusted

## v1.0.4 (2022-04-06)

### Added
- New configuration lead time: all shipments after N minutes before the start of the pickup time will be postponed to the next business day
- It is now possible to determine via a selection in the configuration which number is transmitted as customer reference: Order ID, external order number or both

## v1.0.3 (2022-03-30)

### Changed
- The default URLs of the web service endpoints in the plugin configuration have been adjusted
- There is now a warning about pickup times in the description

### Fixed
- The plugin now registers its own shipping service provider so that the wizard for configuring the shipping settings can be run through. This fixes the error message "Plugin does not have configuration for this shipping profile" in the shipping process

### TODO
- Check the configuration of the shipping service provider and under **Orders » Shipping » Options** in the Shipping service provider column, instead of Sonstiges, select the new entry _**GO! Express Webservice**_

## v1.0.2 (2022-03-22)

### Added
- It is now possible to store a pick-up note in the shipping settings in the plugin configuration (this appears on the label)
- There is a new option "Saturday delivery active" in the shipping settings. Once this option is selected, Friday submissions will automatically be processed as Saturday deliveries

### Changed
- The phone number of the goods recipient is now transferred as a contact person, if available in the delivery/billing address
- If an external order number is maintained for the order, this is transferred instead of the order ID and printed on the label

### Fixed
- The description of the plugin has been improved (wrong information about the selection of the shipping service provider corrected; tool tip in the sender settings added)

## v1.0.1 (2022-02-17)

### Added
- Configuration option for web service endpoints under **Global**

### Changed
- Minor adjustments in the user guide

### TODO
- Update plugin configuration and set web service endpoints according to GO! Enter the given access data

## v1.0.0 (2022-02-11)

### Added
- Initial release

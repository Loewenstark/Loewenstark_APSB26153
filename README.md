# Loewenstark_APSB26153

During order placement, checks only the first and last names in the
customer details and billing and shipping addresses for the following
substrings (case-insensitive):

- `{{config`
- `<svg`
- `<img`

A match prevents order placement and displays a translatable error message.
Input values are not modified. Other fields are not checked.
Virtual orders without a shipping address are also supported.

The global observer for `sales_order_place_before` checks the final order
data before Magento processes the payment, regardless of the checkout
frontend. Validation also applies to API and admin orders that use
Magento's standard order placement flow.

## Activation

Run the following commands from the Magento project directory:

```sh
bin/magento module:enable Loewenstark_APSB26153
bin/magento setup:upgrade
bin/magento cache:clean
```

In production mode, also follow the standard deployment process, including
`bin/magento setup:di:compile`.

## Tests

```sh
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
    app/code/Loewenstark/APSB26153/Test/Unit
```

## Limitations

This module provides a targeted blocklist for the substrings listed above.
It does not replace Magento security updates or context-appropriate escaping
and is not a complete fix for an Adobe security bulletin.

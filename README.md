CurrencyConverterECB
====================

PHP class that allows to access currency exchange rates daily published by the European Central Bank (ECB).

All exchange rates are relative to the EUR.

This script was made for an existing project therefore you could want to change the design of the associated MySQL table and adapt the code accordingly.



Usage
-----

MySQL table is needed to store the exchange rates. All rates are relative to 1 EUR.

```mysql
CREATE TABLE IF NOT EXISTS `currency_exchange` (
  `exchange_rate_date` date NOT NULL,
  `CHF` float NOT NULL,
  `GBP` float NOT NULL,
  `JPY` float NOT NULL,
  `USD` float NOT NULL,
  PRIMARY KEY (`exchange_rate_date`)
)
```

Create a new object, if it will update the currency rates in the database.

```php
$CurrencyConverterECB = new CurrencyConverterECB($table_name, $mysqli);
// assuming $mysqli is a mysqli connection
```

Converting:
```php
// using current day rates
$CurrencyConverterECB->convert(1,'USD','EUR');
// using past rates that are stored in the database
$CurrencyConverterECB->convert(1,'USD','EUR', '2013-08-28');
```

You can also access the exchange rates array:
```php
$CurrencyConverterECB->exchange_rates;
```

References
----------

European Central Bank exchange rates:

http://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html

This code was inspired by the work of Simon Jarvis:

http://www.white-hat-web-design.co.uk/blog/php-currency-conversion-exchange-rates-xml/